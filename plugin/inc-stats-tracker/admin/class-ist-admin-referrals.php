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

	/**
	 * Handle the Delete action for a single Referral record.
	 *
	 * Expects: GET param `id` (int), nonce `ist_delete_referral_{id}`.
	 * Capability: ist_manage_referrals.
	 */
	public function handle_delete(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_manage_referrals' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$id = absint( $_GET['id'] ?? 0 );
		if ( ! $id ) {
			wp_die( esc_html__( 'Invalid record ID.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_delete_referral_' . $id );

		$service = new IST_Service_Referrals();
		$deleted  = $service->delete( $id );

		wp_safe_redirect( add_query_arg(
			array(
				'page'    => 'ist-referrals',
				'deleted' => $deleted ? '1' : '0',
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
