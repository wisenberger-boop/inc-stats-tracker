<?php
/**
 * TYFCB service — business logic layer.
 *
 * Field semantics enforced by this service:
 *
 *  submitted_by_user_id  The group member reporting the closed business — i.e. the
 *                        person who received the business and is giving thanks. Must
 *                        be a valid WP user and a current group member (when a group
 *                        is configured).
 *
 *  submitted_by_name     Snapshot of the reporting member's display_name written at
 *                        insert time. Write-once; never updated.
 *
 *  thank_you_to_type     Explicit attribution type. 'member' = the source can be
 *                        resolved to a WP user record (past or present). 'other' =
 *                        the source cannot or should not be tied to a WP user ID
 *                        (external source, indirect referral, etc.). Never inferred
 *                        from NULL; always stored explicitly.
 *
 *  thank_you_to_user_id  WP user ID of the thanked source when type = 'member'.
 *                        NULL when type = 'other'. Validated against get_userdata()
 *                        but does NOT require current group membership — past members
 *                        with active WP accounts are valid.
 *
 *  thank_you_to_name     Always populated. When type = 'member': snapshot of that
 *                        user's display_name at insert time. When type = 'other':
 *                        the free-text source name entered by the submitter.
 *                        Write-once; never updated.
 *
 *  business_type         Whether the closed business is new or repeat. Canonical
 *                        slugs: 'new' | 'repeat'. Required on new records; empty
 *                        string is accepted only for historical import.
 *
 *  referral_type         How the business originated relative to the group. Canonical
 *                        slugs: 'inside' | 'outside' | 'tier-3'. Required on new
 *                        records; empty string accepted for historical import.
 *
 *  amount                Dollar value of the closed business. Raw input may include
 *                        currency formatting ($1,234.00); normalise_amount() strips
 *                        these before casting to float.
 *
 *  entry_date            User-supplied date of the closed business / reporting event.
 *                        Used for all date-range filters and reporting. Required.
 *
 *  created_by_user_id    WP user who physically entered the record. Identical to
 *                        submitted_by_user_id in self-service entry; differs when an
 *                        admin enters data on behalf of a member.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Service_TYFCB {

	/** Allowed values for thank_you_to_type. */
	private const VALID_SOURCE_TYPES = array( 'member', 'other' );

	/** Allowed values for business_type. Empty string permitted for historical import. */
	private const VALID_BUSINESS_TYPES = array( 'new', 'repeat' );

	/** Allowed values for referral_type. Empty string permitted for historical import. */
	private const VALID_REFERRAL_TYPES = array( 'inside', 'outside', 'tier-3' );

	// -----------------------------------------------------------------------
	// Enhanced attribution model constants (attribution_model = 'enhanced').
	// Legacy records (attribution_model = 'legacy') use only the fields above.
	// -----------------------------------------------------------------------

	/** Allowed values for revenue_attribution_source. */
	private const VALID_ATTRIBUTION_SOURCES = array(
		'current_member_referral',
		'former_member_referral',
		'third_party_extended_referral',
		'direct_non_referral',
		'unknown_other',
	);

	/** Allowed values for revenue_relationship_type. */
	private const VALID_RELATIONSHIP_TYPES = array(
		'new_project_initial_engagement',
		'recurring_revenue_ongoing_support',
		'expansion_existing_client',
		'repeat_business',
		'other',
	);

	/** Allowed values for referral_lineage_type. Empty string = not provided (OK). */
	private const VALID_LINEAGE_TYPES = array(
		'direct',
		'indirect_downstream',
		'ongoing_revenue_from_earlier_referral',
		'unknown',
	);

	/**
	 * Allowed values for original_referrer_type.
	 *
	 * 'current_member' — referrer is a current WP/BuddyBoss user; user_id validated.
	 * 'former_member'  — referrer was a past group member; free-text name only.
	 * 'other'          — referrer is a non-member third party; free-text name only.
	 *
	 * The legacy input value 'member' is normalised to 'current_member' at service time
	 * so the import path continues to work without changes.
	 */
	private const VALID_REFERRER_TYPES = array( 'current_member', 'former_member', 'other' );

	/**
	 * Attribution sources that involve a referrer.
	 * When the source is in this list, original_referrer_* fields are required.
	 */
	private const REFERRAL_SOURCES = array(
		'current_member_referral',
		'former_member_referral',
		'third_party_extended_referral',
	);

	/**
	 * Map attribution source → legacy referral_type slug for reporting compat.
	 * Sources without a meaningful mapping store '' (permitted for historical import).
	 */
	private const ATTRIBUTION_SOURCE_TO_REFERRAL_TYPE = array(
		'current_member_referral'       => 'inside',
		'former_member_referral'        => 'inside',
		'third_party_extended_referral' => 'tier-3',
		'direct_non_referral'           => '',
		'unknown_other'                 => '',
	);

	private IST_Model_TYFCB $model;

	public function __construct() {
		$this->model = new IST_Model_TYFCB();
	}

	/**
	 * Create a new TYFCB record from raw (unsanitized) POST input.
	 *
	 * @param array $input  Raw POST data.
	 * @return int|WP_Error  New record ID on success.
	 */
	public function create_from_input( array $input ): int|WP_Error {

		// -----------------------------------------------------------------------
		// Submitting member — the group member reporting the closed business.
		// -----------------------------------------------------------------------
		$submitted_by_user_id = absint( $input['submitted_by_user_id'] ?? 0 );

		if ( ! $submitted_by_user_id ) {
			return new WP_Error( 'ist_missing_member', __( 'A reporting member is required.', 'inc-stats-tracker' ) );
		}

		$submitted_user = get_userdata( $submitted_by_user_id );
		if ( ! $submitted_user ) {
			return new WP_Error( 'ist_invalid_member', __( 'The selected reporting member is not a valid user.', 'inc-stats-tracker' ) );
		}

		// Group membership guard: enforced only when a group is configured.
		$group_id = IST_Service_Members::get_configured_group_id();
		if ( $group_id && ! IST_Service_Members::is_group_member( $submitted_by_user_id, $group_id ) ) {
			return new WP_Error( 'ist_not_group_member', __( 'The selected reporting member is not in the configured group.', 'inc-stats-tracker' ) );
		}

		// Snapshot — captured at insert time, write-once.
		$submitted_by_name = $submitted_user->display_name;

		// -----------------------------------------------------------------------
		// Attribution model — 'enhanced' for new submissions, 'legacy' for all
		// other paths (direct API calls, imports, anything not using the new form).
		// -----------------------------------------------------------------------
		$attribution_model = sanitize_key( $input['attribution_model'] ?? 'legacy' );
		if ( ! in_array( $attribution_model, array( 'legacy', 'enhanced' ), true ) ) {
			$attribution_model = 'legacy';
		}

		// -----------------------------------------------------------------------
		// Branch: enhanced vs legacy attribution paths.
		// -----------------------------------------------------------------------

		// Shared fields initialised here; each branch assigns its own values.
		$thank_you_to_type    = 'other';
		$thank_you_to_user_id = null;
		$thank_you_to_name    = '';
		$referral_type        = '';
		$revenue_attribution_source = '';
		$revenue_relationship_type  = '';
		$client_payer_name          = '';
		$original_referrer_name     = '';
		$original_referrer_user_id  = null;
		$original_referrer_type     = '';
		$referral_lineage_type      = '';
		$attribution_notes          = '';

		if ( 'enhanced' === $attribution_model ) {

			// -------------------------------------------------------------------
			// ENHANCED PATH — new form submission with rich attribution fields.
			// -------------------------------------------------------------------

			// Revenue Attribution Source — required.
			$revenue_attribution_source = sanitize_key( $input['revenue_attribution_source'] ?? '' );
			if ( ! in_array( $revenue_attribution_source, self::VALID_ATTRIBUTION_SOURCES, true ) ) {
				return new WP_Error( 'ist_missing_attribution_source', __( 'Please select a revenue attribution source.', 'inc-stats-tracker' ) );
			}

			// Original Referrer — required when source is a referral-type.
			if ( in_array( $revenue_attribution_source, self::REFERRAL_SOURCES, true ) ) {
				$original_referrer_type = sanitize_key( $input['original_referrer_type'] ?? 'other' );

				// Normalise legacy import value 'member' → 'current_member'.
				if ( 'member' === $original_referrer_type ) {
					$original_referrer_type = 'current_member';
				}

				// Guard against unexpected values.
				if ( ! in_array( $original_referrer_type, self::VALID_REFERRER_TYPES, true ) ) {
					$original_referrer_type = 'other';
				}

				if ( 'current_member' === $original_referrer_type ) {
					$raw_referrer_id = absint( $input['original_referrer_user_id'] ?? 0 );
					if ( ! $raw_referrer_id ) {
						return new WP_Error( 'ist_missing_referrer', __( 'Please select the group member who referred this business.', 'inc-stats-tracker' ) );
					}
					$referrer_user = get_userdata( $raw_referrer_id );
					if ( ! $referrer_user ) {
						return new WP_Error( 'ist_invalid_referrer', __( 'The selected referrer is not a valid user.', 'inc-stats-tracker' ) );
					}
					$original_referrer_user_id = $raw_referrer_id;
					$original_referrer_name    = $referrer_user->display_name;

					// Derive legacy thank_you_to_* for leaderboard / reporting compat.
					$thank_you_to_type    = 'member';
					$thank_you_to_user_id = $raw_referrer_id;
					$thank_you_to_name    = $referrer_user->display_name;
				} else {
					// former_member or other — free-text name only; no user_id.
					$original_referrer_name = self::normalize_name( sanitize_text_field( $input['original_referrer_name'] ?? '' ) );
					if ( '' === $original_referrer_name ) {
						$err = ( 'former_member' === $original_referrer_type )
							? __( 'Please enter the former member\'s name.', 'inc-stats-tracker' )
							: __( 'Please enter the name of the person who referred this business.', 'inc-stats-tracker' );
						return new WP_Error( 'ist_missing_referrer_name', $err );
					}
					// Derive legacy thank_you_to_* for reporting compat.
					$thank_you_to_type = 'other';
					$thank_you_to_name = $original_referrer_name;
				}
			}
			// Non-referral sources: thank_you_to_* stays at defaults (type=other, name='').
			// original_referrer_type stays '' — nothing to record.

			// Revenue Relationship Type — required.
			$revenue_relationship_type = sanitize_key( $input['revenue_relationship_type'] ?? '' );
			if ( ! in_array( $revenue_relationship_type, self::VALID_RELATIONSHIP_TYPES, true ) ) {
				return new WP_Error( 'ist_missing_relationship_type', __( 'Please select a revenue relationship type.', 'inc-stats-tracker' ) );
			}

			// Referral Lineage Type — optional.
			$referral_lineage_type = sanitize_key( $input['referral_lineage_type'] ?? '' );
			if ( '' !== $referral_lineage_type && ! in_array( $referral_lineage_type, self::VALID_LINEAGE_TYPES, true ) ) {
				$referral_lineage_type = '';
			}

			// Client / Payer Name — optional.
			$client_payer_name = sanitize_text_field( $input['client_payer_name'] ?? '' );

			// Attribution Notes — optional.
			$attribution_notes = sanitize_textarea_field( $input['attribution_notes'] ?? '' );

			// Auto-derive legacy referral_type from attribution source for reporting compat.
			$referral_type = self::ATTRIBUTION_SOURCE_TO_REFERRAL_TYPE[ $revenue_attribution_source ] ?? '';

		} else {

			// -------------------------------------------------------------------
			// LEGACY PATH — existing form field names; behaviour unchanged.
			// -------------------------------------------------------------------

			// Attribution type — explicit, never inferred from NULL.
			$thank_you_to_type = sanitize_key( $input['thank_you_to_type'] ?? 'member' );
			if ( ! in_array( $thank_you_to_type, self::VALID_SOURCE_TYPES, true ) ) {
				$thank_you_to_type = 'member';
			}

			if ( 'member' === $thank_you_to_type ) {
				$raw_user_id = absint( $input['thank_you_to_user_id'] ?? 0 );
				if ( ! $raw_user_id ) {
					return new WP_Error( 'ist_missing_source', __( 'A source member is required when attribution type is Member.', 'inc-stats-tracker' ) );
				}
				$thanked_user = get_userdata( $raw_user_id );
				if ( ! $thanked_user ) {
					return new WP_Error( 'ist_invalid_source', __( 'The selected source is not a valid user.', 'inc-stats-tracker' ) );
				}
				$thank_you_to_user_id = $raw_user_id;
				$thank_you_to_name    = $thanked_user->display_name;
			} else {
				$thank_you_to_name = self::normalize_name( sanitize_text_field( $input['thank_you_to_name'] ?? '' ) );
				if ( '' === $thank_you_to_name ) {
					return new WP_Error( 'ist_missing_source_name', __( 'A source name is required when attribution type is Other Source.', 'inc-stats-tracker' ) );
				}
			}

			// Referral type — required for new records; '' accepted for historical import.
			$referral_type = sanitize_key( $input['referral_type'] ?? '' );
			if ( '' !== $referral_type && ! in_array( $referral_type, self::VALID_REFERRAL_TYPES, true ) ) {
				return new WP_Error( 'ist_invalid_referral_type', __( 'Referral type must be Inside, Outside, or Tier 3.', 'inc-stats-tracker' ) );
			}
		}

		// -----------------------------------------------------------------------
		// Amount — strip currency formatting before casting.
		// -----------------------------------------------------------------------
		$amount = self::normalize_amount( $input['amount'] ?? '0' );
		if ( $amount <= 0 ) {
			return new WP_Error( 'ist_invalid_amount', __( 'Closed business amount must be greater than zero.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Business type — required for new records; '' accepted for historical import.
		// -----------------------------------------------------------------------
		$business_type = sanitize_key( $input['business_type'] ?? '' );
		if ( '' !== $business_type && ! in_array( $business_type, self::VALID_BUSINESS_TYPES, true ) ) {
			return new WP_Error( 'ist_invalid_business_type', __( 'Business type must be New or Repeat.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Entry date — required, used for all reporting queries.
		// -----------------------------------------------------------------------
		$entry_date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $entry_date ) {
			return new WP_Error( 'ist_missing_date', __( 'A business date is required.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Note — optional free text, preserved across both models.
		// -----------------------------------------------------------------------
		$note = sanitize_textarea_field( $input['note'] ?? '' );

		// -----------------------------------------------------------------------
		// Build and insert record.
		// created_at is set by MySQL DEFAULT CURRENT_TIMESTAMP — never set in PHP.
		// Nullable user ID fields are omitted from $data to let MySQL use DEFAULT NULL.
		// -----------------------------------------------------------------------
		$data = array(
			'submitted_by_user_id'       => $submitted_by_user_id,
			'submitted_by_name'          => $submitted_by_name,
			'thank_you_to_type'          => $thank_you_to_type,
			'thank_you_to_name'          => $thank_you_to_name,
			'amount'                     => $amount,
			'business_type'              => $business_type,
			'referral_type'              => $referral_type,
			'note'                       => $note,
			'attribution_model'          => $attribution_model,
			'revenue_attribution_source' => $revenue_attribution_source,
			'revenue_relationship_type'  => $revenue_relationship_type,
			'client_payer_name'          => $client_payer_name,
			'original_referrer_name'     => $original_referrer_name,
			'original_referrer_type'     => $original_referrer_type,
			'referral_lineage_type'      => $referral_lineage_type,
			'attribution_notes'          => $attribution_notes,
			'entry_date'                 => $entry_date,
			'created_by_user_id'         => get_current_user_id(),
		);

		if ( null !== $thank_you_to_user_id ) {
			$data['thank_you_to_user_id'] = $thank_you_to_user_id;
		}

		if ( null !== $original_referrer_user_id ) {
			$data['original_referrer_user_id'] = $original_referrer_user_id;
		}

		$id = $this->model->create( $data );
		return $id !== false ? $id : new WP_Error( 'ist_db_error', __( 'Could not save TYFCB record.', 'inc-stats-tracker' ) );
	}

	public function get_all( array $where = array() ): array {
		return $this->model->all( $where );
	}

	public function get( int $id ): ?object {
		return $this->model->get( $id );
	}

	public function delete( int $id ): bool {
		return false !== $this->model->delete( $id );
	}

	/**
	 * Strip currency formatting from a raw amount string and return a float.
	 *
	 * Handles CSV-style values like "$4,824" or "$1,234.56".
	 * Strips everything except digits and the decimal point before casting.
	 *
	 * @param string|float|int $raw
	 * @return float
	 */
	public static function normalize_amount( $raw ): float {
		// Remove $, commas, spaces, and any other non-numeric chars except '.'.
		return (float) preg_replace( '/[^0-9.]/', '', (string) $raw );
	}

	/**
	 * Conservatively normalize a free-text person name.
	 *
	 * Applied to original_referrer_name (former_member / other paths) and to
	 * thank_you_to_name (legacy other path) so GROUP-BY attribution queries
	 * are not fragmented by whitespace variations.
	 *
	 * Rules applied:
	 *   - Trim leading and trailing whitespace.
	 *   - Normalize tabs, newlines, carriage returns, and vertical tabs to a
	 *     single space (sanitize_text_field strips some of these, but an
	 *     explicit pass makes the intent clear and handles any edge cases).
	 *   - Collapse any run of internal whitespace (including non-breaking
	 *     spaces, \u00A0) to a single ASCII space.
	 *
	 * NOT applied:
	 *   - Capitalization — too many valid patterns (van der Berg, O'Brien,
	 *     McSmith, ALLCAPS abbreviations) where ucwords() would produce wrong
	 *     output. The submitter's intent is preserved exactly.
	 *   - Punctuation — preserved as entered.
	 *   - Fuzzy deduplication — not in scope for this layer; handled at the
	 *     reporting / admin UI level if ever needed.
	 *
	 * @param string $raw  Already sanitized via sanitize_text_field().
	 * @return string
	 */
	private static function normalize_name( string $raw ): string {
		// Collapse vertical whitespace characters to a plain space.
		$name = preg_replace( '/[\r\n\t\v\f]+/', ' ', $raw );
		// Collapse runs of any Unicode whitespace (including \u00A0) to one space.
		$name = preg_replace( '/\p{Z}{2,}/u', ' ', (string) $name );
		return trim( (string) $name );
	}
}
