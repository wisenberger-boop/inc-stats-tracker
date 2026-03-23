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

	/**
	 * Allowed values for thank_you_to_type.
	 */
	private const VALID_SOURCE_TYPES = array( 'member', 'other' );

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
		// Attribution type — explicit, never inferred from NULL.
		// -----------------------------------------------------------------------
		$thank_you_to_type = sanitize_key( $input['thank_you_to_type'] ?? 'member' );
		if ( ! in_array( $thank_you_to_type, self::VALID_SOURCE_TYPES, true ) ) {
			$thank_you_to_type = 'member';
		}

		// -----------------------------------------------------------------------
		// Thanked source — resolved differently per type.
		// -----------------------------------------------------------------------
		$thank_you_to_user_id = null; // explicitly null; not included in INSERT when null.
		$thank_you_to_name    = '';

		if ( 'member' === $thank_you_to_type ) {
			// Source is a WP user. Does not require current group membership.
			$raw_user_id = absint( $input['thank_you_to_user_id'] ?? 0 );

			if ( ! $raw_user_id ) {
				return new WP_Error( 'ist_missing_source', __( 'A source member is required when attribution type is Member.', 'inc-stats-tracker' ) );
			}

			$thanked_user = get_userdata( $raw_user_id );
			if ( ! $thanked_user ) {
				return new WP_Error( 'ist_invalid_source', __( 'The selected source is not a valid user.', 'inc-stats-tracker' ) );
			}

			$thank_you_to_user_id = $raw_user_id;
			$thank_you_to_name    = $thanked_user->display_name; // Snapshot — ignore any submitted name.

		} else {
			// Source is 'other': free-text name only, no user ID.
			$thank_you_to_name = sanitize_text_field( $input['thank_you_to_name'] ?? '' );
			if ( '' === $thank_you_to_name ) {
				return new WP_Error( 'ist_missing_source_name', __( 'A source name is required when attribution type is Other Source.', 'inc-stats-tracker' ) );
			}
		}

		// -----------------------------------------------------------------------
		// Amount.
		// -----------------------------------------------------------------------
		$amount = (float) ( $input['amount'] ?? 0 );
		if ( $amount <= 0 ) {
			return new WP_Error( 'ist_invalid_amount', __( 'Closed business amount must be greater than zero.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Entry date — required, used for all reporting queries.
		// -----------------------------------------------------------------------
		$entry_date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $entry_date ) {
			return new WP_Error( 'ist_missing_date', __( 'A business date is required.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Optional note.
		// -----------------------------------------------------------------------
		$note = sanitize_textarea_field( $input['note'] ?? '' );

		// -----------------------------------------------------------------------
		// Build and insert record.
		// created_at is set by MySQL DEFAULT CURRENT_TIMESTAMP — never set in PHP.
		// thank_you_to_user_id is omitted from data when null so MySQL stores NULL.
		// -----------------------------------------------------------------------
		$data = array(
			'submitted_by_user_id' => $submitted_by_user_id,
			'submitted_by_name'    => $submitted_by_name,
			'thank_you_to_type'    => $thank_you_to_type,
			'thank_you_to_name'    => $thank_you_to_name,
			'amount'               => $amount,
			'note'                 => $note,
			'entry_date'           => $entry_date,
			'created_by_user_id'   => get_current_user_id(),
		);

		// Include user ID only when set; omitting it lets MySQL use DEFAULT NULL.
		if ( null !== $thank_you_to_user_id ) {
			$data['thank_you_to_user_id'] = $thank_you_to_user_id;
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
}
