<?php
/**
 * Admin page — Referral records.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Referrals {

	public function page_referrals(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_referrals' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$service = new IST_Service_Referrals();
		$records = $service->get_all();

		ist_get_template( 'admin/tmpl-referrals.php', compact( 'records' ) );
	}
}
