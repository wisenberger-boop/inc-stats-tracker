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
}
