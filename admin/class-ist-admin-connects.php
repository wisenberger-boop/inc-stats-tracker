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
}
