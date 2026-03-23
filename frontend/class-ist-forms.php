<?php
/**
 * Frontend form submission handlers.
 *
 * Hooked via admin-post.php actions in ist-hooks.php.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Forms {

	/**
	 * Handle TYFCB form submission.
	 */
	public function handle_tyfcb(): void {
		$this->verify_nonce( 'ist_submit_tyfcb' );

		$service = new IST_Service_TYFCB();
		$result  = $service->create_from_input( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

		$this->redirect_after( $result, 'tyfcb' );
	}

	/**
	 * Handle referral form submission.
	 */
	public function handle_referral(): void {
		$this->verify_nonce( 'ist_submit_referral' );

		$service = new IST_Service_Referrals();
		$result  = $service->create_from_input( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

		$this->redirect_after( $result, 'referrals' );
	}

	/**
	 * Handle connect form submission.
	 */
	public function handle_connect(): void {
		$this->verify_nonce( 'ist_submit_connect' );

		$service = new IST_Service_Connects();
		$result  = $service->create_from_input( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

		$this->redirect_after( $result, 'connects' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function verify_nonce( string $action ): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), $action ) ) {
			wp_die( esc_html__( 'Invalid request.', 'inc-stats-tracker' ) );
		}
	}

	/**
	 * Redirect back with a success or error query arg.
	 *
	 * @param int|WP_Error $result
	 * @param string       $type
	 */
	private function redirect_after( int|WP_Error $result, string $type ): void {
		$redirect = wp_get_referer() ?: admin_url( 'admin.php?page=ist-' . $type );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( array(
				'ist_error' => urlencode( $result->get_error_message() ),
			), $redirect ) );
		} else {
			wp_safe_redirect( add_query_arg( 'ist_saved', '1', $redirect ) );
		}
		exit;
	}
}
