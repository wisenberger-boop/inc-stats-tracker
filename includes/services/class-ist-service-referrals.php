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
	 * Allowed status values.
	 */
	private const VALID_STATUSES = array( 'open', 'closed', 'converted' );

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
		// Referral recipient — free-text name required.
		// -----------------------------------------------------------------------
		$referred_to_name = sanitize_text_field( $input['referred_to_name'] ?? '' );
		if ( '' === $referred_to_name ) {
			return new WP_Error( 'ist_missing_referred_to', __( 'A referral recipient name is required.', 'inc-stats-tracker' ) );
		}

		// Optional user ID for recipient — schema support; not committed in MVP frontend.
		$referred_to_user_id = absint( $input['referred_to_user_id'] ?? 0 ) ?: null;

		// -----------------------------------------------------------------------
		// Status.
		// -----------------------------------------------------------------------
		$status = sanitize_key( $input['status'] ?? 'open' );
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			$status = 'open';
		}

		// -----------------------------------------------------------------------
		// Entry date — required.
		// -----------------------------------------------------------------------
		$entry_date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $entry_date ) {
			return new WP_Error( 'ist_missing_date', __( 'A referral date is required.', 'inc-stats-tracker' ) );
		}

		$note = sanitize_textarea_field( $input['note'] ?? '' );

		// referred_to_user_id omitted from data when null so MySQL stores NULL.
		$data = array(
			'referred_by_user_id' => $referred_by_user_id,
			'referred_by_name'    => $referred_by_name,
			'referred_to_name'    => $referred_to_name,
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
	 * Update the status of a referral record.
	 *
	 * @param int    $id
	 * @param string $status  Must be one of VALID_STATUSES.
	 * @return bool
	 */
	public function update_status( int $id, string $status ): bool {
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return false;
		}
		return false !== $this->model->update( $id, array( 'status' => $status ) );
	}

	public function delete( int $id ): bool {
		return false !== $this->model->delete( $id );
	}
}
