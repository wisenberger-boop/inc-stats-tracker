<?php
/**
 * Admin page — TYFCB records.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_TYFCB {

	public function page_tyfcb(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_tyfcb' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$service = new IST_Service_TYFCB();
		$records = $service->get_all();

		ist_get_template( 'admin/tmpl-tyfcb.php', compact( 'records' ) );
	}

	/**
	 * Handle the Delete action for a single TYFCB record.
	 *
	 * Expects: GET param `id` (int), nonce `ist_delete_tyfcb_{id}`.
	 * Capability: ist_manage_tyfcb.
	 */
	public function handle_delete(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_tyfcb' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid record ID.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_delete_tyfcb_' . $id );

		$service = new IST_Service_TYFCB();
		$deleted  = $service->delete( $id );

		wp_safe_redirect( add_query_arg(
			array(
				'page'    => 'ist-tyfcb',
				'deleted' => $deleted ? '1' : '0',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
