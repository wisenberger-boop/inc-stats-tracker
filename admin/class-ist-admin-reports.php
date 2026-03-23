<?php
/**
 * Admin page — Reporting dashboard.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Reports {

	public function page_reports(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_view_reports' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		// TODO: aggregate summary data for the reporting period.
		$summary = array(
			'tyfcb_total'     => 0,
			'referrals_total' => 0,
			'connects_total'  => 0,
		);

		ist_get_template( 'admin/tmpl-reports.php', compact( 'summary' ) );
	}
}
