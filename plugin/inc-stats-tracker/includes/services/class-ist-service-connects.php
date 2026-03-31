<?php
/**
 * Connects service — business logic layer.
 *
 * Field semantics:
 *
 *  member_user_id         WP user ID of the group member logging the connect. Must be
 *                         a valid WP user and a current group member (when configured).
 *
 *  member_display_name    Snapshot of that member's display_name at insert time. Write-once.
 *
 *  connected_with_name    Always populated. Name of the other party in the connect.
 *                         Free-text; required.
 *
 *  connected_with_user_id Nullable. WP user ID if the other party is a group member.
 *                         Schema supports it; not surfaced in the MVP frontend form.
 *
 *  meet_where             Where the connection took place. Canonical slugs:
 *                         'in-person' | 'zoom' | 'telephone'. Required; '' accepted
 *                         for historical import of any blank rows.
 *                         Source form labels: "In person" | "Zoom" | "Telephone".
 *
 *  entry_date             User-supplied date of the connect meeting. Used for reporting.
 *
 *  created_by_user_id     WP user who entered the record.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Service_Connects {

	/**
	 * Allowed meet_where values — where the connection took place.
	 * Empty string permitted for historical import of blank rows.
	 *
	 * Slug        Form label     CSV value
	 * ----------- -------------- ----------
	 * in-person   In person      In person
	 * zoom        Zoom           Zoom
	 * telephone   Telephone      Telephone
	 */
	private const VALID_MEET_WHERES = array( 'in-person', 'zoom', 'telephone' );

	private IST_Model_Connect $model;

	public function __construct() {
		$this->model = new IST_Model_Connect();
	}

	/**
	 * Create a connect record from raw (unsanitized) POST input.
	 *
	 * @param array $input  Raw POST data.
	 * @return int|WP_Error
	 */
	public function create_from_input( array $input ): int|WP_Error {

		// -----------------------------------------------------------------------
		// Member logging the connect.
		// -----------------------------------------------------------------------
		$member_user_id = absint( $input['member_user_id'] ?? 0 );

		if ( ! $member_user_id ) {
			return new WP_Error( 'ist_missing_member', __( 'A member is required.', 'inc-stats-tracker' ) );
		}

		$member_user = get_userdata( $member_user_id );
		if ( ! $member_user ) {
			return new WP_Error( 'ist_invalid_member', __( 'The selected member is not a valid user.', 'inc-stats-tracker' ) );
		}

		// Group membership guard: enforced only when a group is configured.
		$group_id = IST_Service_Members::get_configured_group_id();
		if ( $group_id && ! IST_Service_Members::is_group_member( $member_user_id, $group_id ) ) {
			return new WP_Error( 'ist_not_group_member', __( 'The selected member is not in the configured group.', 'inc-stats-tracker' ) );
		}

		$member_display_name = $member_user->display_name;

		// -----------------------------------------------------------------------
		// Other party — name required; may be resolved from user ID.
		// -----------------------------------------------------------------------
		$connected_with_type    = sanitize_key( $input['connected_with_type'] ?? 'other' );
		$connected_with_user_id = absint( $input['connected_with_user_id'] ?? 0 ) ?: null;
		$connected_with_name    = sanitize_text_field( $input['connected_with_name'] ?? '' );

		// When the member panel is active, resolve name (and validate the user).
		if ( 'member' === $connected_with_type && $connected_with_user_id ) {
			$other_user = get_userdata( $connected_with_user_id );
			if ( ! $other_user ) {
				return new WP_Error( 'ist_invalid_connected_with', __( 'The selected member is not a valid user.', 'inc-stats-tracker' ) );
			}
			$connected_with_name = $other_user->display_name;
		}

		if ( '' === $connected_with_name ) {
			return new WP_Error( 'ist_missing_connected_with', __( 'A connected-with name is required.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Meet where — required for new records; '' accepted for historical import.
		// -----------------------------------------------------------------------
		$meet_where = sanitize_key( $input['meet_where'] ?? '' );
		if ( '' !== $meet_where && ! in_array( $meet_where, self::VALID_MEET_WHERES, true ) ) {
			return new WP_Error( 'ist_invalid_meet_where', __( 'Meeting location must be In person, Zoom, or Telephone.', 'inc-stats-tracker' ) );
		}

		// -----------------------------------------------------------------------
		// Entry date — required.
		// -----------------------------------------------------------------------
		$entry_date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $entry_date ) {
			return new WP_Error( 'ist_missing_date', __( 'A connect date is required.', 'inc-stats-tracker' ) );
		}

		$note = sanitize_textarea_field( $input['note'] ?? '' );

		// connected_with_user_id omitted from data when null so MySQL stores NULL.
		$data = array(
			'member_user_id'      => $member_user_id,
			'member_display_name' => $member_display_name,
			'connected_with_name' => $connected_with_name,
			'meet_where'          => $meet_where,
			'note'                => $note,
			'entry_date'          => $entry_date,
			'created_by_user_id'  => get_current_user_id(),
		);

		if ( null !== $connected_with_user_id ) {
			$data['connected_with_user_id'] = $connected_with_user_id;
		}

		$id = $this->model->create( $data );
		return $id !== false ? $id : new WP_Error( 'ist_db_error', __( 'Could not save connect record.', 'inc-stats-tracker' ) );
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
	 * Normalise a raw meet_where string (CSV value or form label) to its canonical slug.
	 *
	 * @param string $raw  Raw value from CSV or form (e.g. "In person", "in person", "Zoom").
	 * @return string  Canonical slug, or '' if unrecognised.
	 */
	public static function normalize_meet_where( string $raw ): string {
		static $map = array(
			'in person'  => 'in-person',
			'in-person'  => 'in-person',
			'inperson'   => 'in-person',
			'zoom'       => 'zoom',
			'telephone'  => 'telephone',
			'phone'      => 'telephone',
		);
		$key = strtolower( trim( $raw ) );
		return $map[ $key ] ?? '';
	}
}
