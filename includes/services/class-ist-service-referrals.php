<?php
/**
 * Referrals service — business logic layer.
 *
 * Field semantics:
 *
 *  referred_by_user_id  WP user ID of the group member who gave the referral. Must
 *                       be a valid WP user and a current group member (when configured).
 *
 *  referred_by_name     Snapshot of the referring member's display_name at insert time.
 *                       Write-once.
 *
 *  referred_to_name     Always populated. The name of the person or business the
 *                       referral was passed to. Free-text; required.
 *
 *  referred_to_user_id  Nullable. WP user ID if the recipient happens to be a group
 *                       member. Schema supports it; not surfaced in the MVP frontend form.
 *
 *  referral_type        How the referral relates to the group. Canonical slugs:
 *                       'inside' | 'outside' | 'tier-3'. Required; '' accepted for
 *                       historical import.
 *
 *  status               Handoff method — how the referral was passed. NOT a lifecycle
 *                       status. Canonical slugs: 'emailed' | 'gave-phone' | 'will-initiate'.
 *                       '' accepted for historical import (source CSV has blank rows).
 *
 *  note                 Referral details. Required on new records (form enforces this);
 *                       '' accepted for historical import.
 *
 *  entry_date           User-supplied date of the referral event. Used for reporting.
 *
 *  created_by_user_id   WP user who entered the record.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Service_Referrals {

	/**
	 * Allowed referral_type values. Shared vocabulary with TYFCB.
	 * Empty string permitted for historical import of blank CSV rows.
	 */
	private const VALID_REFERRAL_TYPES = array( 'inside', 'outside', 'tier-3' );

	/**
	 * Allowed status (handoff method) values.
	 * These describe HOW the referral was passed, not a lifecycle state.
	 * Empty string permitted for historical import of blank CSV rows.
	 *
	 * Slug          Form label                      CSV label
	 * ------------- ------------------------------- -----------------------------------
	 * emailed        Emailed                         "Given your card, or emailed"
	 * gave-phone     Gave Phone Number               (implied)
	 * will-initiate  Said you would initiate contact "Told them you would call"
	 */
	private const VALID_STATUSES = array( 'emailed', 'gave-phone', 'will-initiate' );

	private IST_Model_Referral $model;

	public function __construct() {
		$this->model = new IST_Model_Referral();
	}

	/**
	 * Create a referral record from raw (unsanitized) POST input.
	 *
	 * @param array $input  Raw POST data.
	 * @return int|WP_Error
	 */
	public function create_from_input( array $input ): int|WP_Error {

		// -----------------------------------------------------------------------
		// Referring member.
		// -----------------------------------------------------------------------
		$referred_by_user_id = absint( $input['referred_by_user_id'] ?? 0 );

		if ( ! $referred_by_user_id ) {
			return new WP_Error( 'ist_missing_member', __( 'A referring member is required.', 'inc-stats-tracker' ) );
		}

		$referring_user = get_userdata( $referred_by_user_id );
		if ( ! $referring_user ) {
			return new WP_Error( 'ist_invalid_member', __( 'The selected referring member is not a valid user.', 'inc-stats-tracker' ) );
		}

		// Group membership guard: enforced only when a group is configured.
		$group_id = IST_Service_Members::get_configured_group_id();
		if ( $group_id && ! IST_Service_Members::is_group_member( $referred_by_user_id, $group_id ) ) {
			return new WP_Error( 'ist_not_group_member', __( 'The selected referring member is not in the configured group.', 'inc-stats-tracker' ) );
		}

		$referred_by_name = $referring_user->display_name;

		// -----------------------------------------------------------------------
		// Referral recipient — name required; may be resolved from user ID.
		// -----------------------------------------------------------------------
		$referred_to_type    = sanitize_key( $input['referred_to_type'] ?? 'other' );
		$referred_to_user_id = absint( $input['referred_to_user_id'] ?? 0 ) ?: null;
		$referred_to_name    = sanitize_text_field( $input['referred_to_name'] ?? '' );

		// When the member panel is active, resolve name (and validate the user).
		if ( 'member' === $referred_to_type && $referred_to_user_id ) {
			$recipient_user = get_userdata( $referred_to_user_id );
			if ( ! $recipient_user ) {
				return new WP_Error( 'ist_invalid_recipient', __( 'The selected recipient member is not a valid user.', 'inc-stats-tracker' ) );
			}
			$referred_to_name = $recipient_user->display_name;
		}

		if ( '' === $referred_to_name ) {
			return new WP_Error( 'ist_missing_referred_to', __( 'A referral recipient name is required.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Referral type — required for new records; '' accepted for historical import.
		// -----------------------------------------------------------------------
		$referral_type = sanitize_key( $input['referral_type'] ?? '' );
		if ( '' !== $referral_type && ! in_array( $referral_type, self::VALID_REFERRAL_TYPES, true ) ) {
			return new WP_Error( 'ist_invalid_referral_type', __( 'Referral type must be Inside, Outside, or Tier 3.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Status (handoff method) — '' accepted for historical import of blank rows.
		// -----------------------------------------------------------------------
		$status = sanitize_key( $input['status'] ?? '' );
		if ( '' !== $status && ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return new WP_Error( 'ist_invalid_status', __( 'Referral status must be Emailed, Gave Phone Number, or Said you would initiate contact.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Entry date — required.
		// -----------------------------------------------------------------------
		$entry_date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $entry_date ) {
			return new WP_Error( 'ist_missing_date', __( 'A referral date is required.', 'inc-stats-tracker' ) );
		}

		// Note (referral details) — required for new records.
		$note = sanitize_textarea_field( $input['note'] ?? '' );

		// referred_to_user_id omitted from data when null so MySQL stores NULL.
		$data = array(
			'referred_by_user_id' => $referred_by_user_id,
			'referred_by_name'    => $referred_by_name,
			'referred_to_name'    => $referred_to_name,
			'referral_type'       => $referral_type,
			'status'              => $status,
			'note'                => $note,
			'entry_date'          => $entry_date,
			'created_by_user_id'  => get_current_user_id(),
		);

		if ( null !== $referred_to_user_id ) {
			$data['referred_to_user_id'] = $referred_to_user_id;
		}

		$id = $this->model->create( $data );
		return $id !== false ? $id : new WP_Error( 'ist_db_error', __( 'Could not save referral record.', 'inc-stats-tracker' ) );
	}

	public function get_all( array $where = array() ): array {
		return $this->model->all( $where );
	}

	public function get( int $id ): ?object {
		return $this->model->get( $id );
	}

	/**
	 * Update the status (handoff method) of a referral record.
	 *
	 * @param int    $id
	 * @param string $status  Must be one of VALID_STATUSES or ''.
	 * @return bool
	 */
	public function update_status( int $id, string $status ): bool {
		if ( '' !== $status && ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return false;
		}
		return false !== $this->model->update( $id, array( 'status' => $status ) );
	}

	public function delete( int $id ): bool {
		return false !== $this->model->delete( $id );
	}

	/**
	 * Normalise a raw referral status string (CSV label or form label) to its canonical slug.
	 *
	 * CSV labels differ from form labels; both map to the same canonical slug.
	 *
	 * @param string $raw  Raw value from CSV or form.
	 * @return string  Canonical slug, or '' if unrecognised.
	 */
	public static function normalize_referral_status( string $raw ): string {
		static $map = array(
			'given your card, or emailed'    => 'emailed',
			'emailed'                        => 'emailed',
			'gave phone number'              => 'gave-phone',
			'gave your phone number'         => 'gave-phone',
			'gave-phone'                     => 'gave-phone',
			'told them you would call'       => 'will-initiate',
			'said you would initiate contact' => 'will-initiate',
			'will-initiate'                  => 'will-initiate',
			'will initiate'                  => 'will-initiate',
		);
		$key = strtolower( trim( $raw ) );
		return $map[ $key ] ?? '';
	}

	/**
	 * Normalise a raw referral type string to its canonical slug.
	 *
	 * @param string $raw  Raw value from CSV or form (e.g. "Tier 3", "tier-3", "Inside").
	 * @return string  Canonical slug, or '' if unrecognised.
	 */
	public static function normalize_referral_type( string $raw ): string {
		static $map = array(
			'inside'  => 'inside',
			'outside' => 'outside',
			'tier 3'  => 'tier-3',
			'tier-3'  => 'tier-3',
			'tier3'   => 'tier-3',
		);
		$key = strtolower( trim( $raw ) );
		return $map[ $key ] ?? '';
	}
}
