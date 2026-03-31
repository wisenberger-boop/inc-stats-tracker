<?php
/**
 * Admin page — Connect records.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Connects {

	public function page_connects(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_connects' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$service = new IST_Service_Connects();
		$records = $service->get_all();

		ist_get_template( 'admin/tmpl-connects.php', compact( 'records' ) );
	}

	/**
	 * Handle the Delete action for a single Connect record.
	 *
	 * Expects: GET param `id` (int), nonce `ist_delete_connect_{id}`.
	 * Capability: ist_manage_connects.
	 */
	public function handle_delete(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_connects' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid record ID.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_delete_connect_' . $id );

		$service = new IST_Service_Connects();
		$deleted  = $service->delete( $id );

		wp_safe_redirect( add_query_arg(
			array(
				'page'    => 'ist-connects',
				'deleted' => $deleted ? '1' : '0',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
