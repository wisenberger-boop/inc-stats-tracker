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

		global $wpdb;

		$summary = array(
			'tyfcb_count'      => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ist_tyfcb" ),
			'tyfcb_amount'     => (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM {$wpdb->prefix}ist_tyfcb" ),
			'referrals_total'  => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ist_referrals" ),
			'connects_total'   => (int)   $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ist_connects" ),
		);

		ist_get_template( 'admin/tmpl-reports.php', compact( 'summary' ) );
	}
}
