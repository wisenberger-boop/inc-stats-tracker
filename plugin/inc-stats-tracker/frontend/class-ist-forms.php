<?php
/**
 * Frontend form submission handlers.
 *
 * Hooked via admin-post.php actions in ist-hooks.php.
 *
 * Security model:
 *  - Nonce verification happens first (rejects tampering / CSRF).
 *  - submitted_by_user_id / referred_by_user_id / member_user_id are always
 *    overwritten from get_current_user_id() — never trusted from POST.
 *  - Group membership is enforced by the service layer.
 *  - Non-logged-in users are redirected to the login page by the nopriv handler.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Forms {

	/**
	 * Handle TYFCB form submission (logged-in users only).
	 */
	public function handle_tyfcb(): void {
		$this->verify_nonce( 'ist_submit_tyfcb' );
		$this->require_login();

		// Force system-assigned ownership — ignore any submitted_by_user_id in POST.
		$input                         = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$input['submitted_by_user_id'] = get_current_user_id();

		$date_check = $this->check_entry_date( $input );
		if ( is_wp_error( $date_check ) ) {
			$this->redirect_after( $date_check, 'tyfcb' );
		}

		$service = new IST_Service_TYFCB();
		$result  = $service->create_from_input( $input );

		$this->redirect_after( $result, 'tyfcb' );
	}

	/**
	 * Handle referral form submission (logged-in users only).
	 */
	public function handle_referral(): void {
		$this->verify_nonce( 'ist_submit_referral' );
		$this->require_login();

		// Force system-assigned ownership.
		$input                        = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$input['referred_by_user_id'] = get_current_user_id();

		$date_check = $this->check_entry_date( $input );
		if ( is_wp_error( $date_check ) ) {
			$this->redirect_after( $date_check, 'referrals' );
		}

		$service = new IST_Service_Referrals();
		$result  = $service->create_from_input( $input );

		// Send referral notification when handoff method is "Emailed introduction".
		if ( ! is_wp_error( $result ) && 'emailed' === sanitize_key( $input['status'] ?? '' ) ) {
			$this->maybe_send_referral_notification( $input );
		}

		$this->redirect_after( $result, 'referrals' );
	}

	/**
	 * Fire the referral email notification after a successful 'emailed' handoff save.
	 *
	 * Resolves the recipient email from either the selected group member's account
	 * (referred_to_type = 'member') or the manually entered address (referred_to_type = 'other').
	 * Notification failure is intentionally silent — it does not affect the redirect.
	 *
	 * @param array $input  Sanitized POST input with forced referred_by_user_id set.
	 */
	private function maybe_send_referral_notification( array $input ): void {
		$referred_to_type    = sanitize_key( $input['referred_to_type'] ?? 'other' );
		$referred_to_user_id = absint( $input['referred_to_user_id'] ?? 0 );

		// Resolve recipient email.
		if ( 'member' === $referred_to_type && $referred_to_user_id ) {
			$recipient = get_userdata( $referred_to_user_id );
			$to_email  = $recipient ? $recipient->user_email : '';
			$to_name   = $recipient ? $recipient->display_name : '';
		} else {
			$to_email = sanitize_email( $input['referred_to_email'] ?? '' );
			$to_name  = sanitize_text_field( $input['referred_to_name'] ?? '' );
		}

		if ( ! is_email( $to_email ) ) {
			return; // No valid email — skip silently.
		}

		$referring_user = get_userdata( (int) $input['referred_by_user_id'] );

		ist_send_referral_notification( array(
			'referred_by_name'  => $referring_user ? $referring_user->display_name : '',
			'referred_by_email' => $referring_user ? $referring_user->user_email : '',
			'referred_to_name'  => $to_name,
			'referred_to_email' => $to_email,
			'referral_type'     => sanitize_key( $input['referral_type'] ?? '' ),
			'note'              => sanitize_textarea_field( $input['note'] ?? '' ),
			'entry_date'        => ist_sanitize_date( $input['entry_date'] ?? '' ),
		) );
	}

	/**
	 * Handle connect form submission (logged-in users only).
	 */
	public function handle_connect(): void {
		$this->verify_nonce( 'ist_submit_connect' );
		$this->require_login();

		// Force system-assigned ownership.
		$input                   = $_POST; // phpcs:ignore WordPress.Security.NonceVerification
		$input['member_user_id'] = get_current_user_id();

		$date_check = $this->check_entry_date( $input );
		if ( is_wp_error( $date_check ) ) {
			$this->redirect_after( $date_check, 'connects' );
		}

		$service = new IST_Service_Connects();
		$result  = $service->create_from_input( $input );

		$this->redirect_after( $result, 'connects' );
	}

	/**
	 * Redirect non-logged-in POST attempts to the login page.
	 *
	 * Registered on admin_post_nopriv_ist_submit_* hooks.
	 * The referer is passed as the login redirect target so users return to
	 * the form page after signing in.
	 */
	public function handle_nopriv(): void {
		wp_safe_redirect( wp_login_url( wp_get_referer() ?: home_url() ) );
		exit;
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
	 * Redirect to the login page if the current user is not logged in.
	 *
	 * This is a belt-and-suspenders check: admin_post_nopriv_* should fire
	 * for non-logged-in users and the nopriv handler should redirect before
	 * any of the handle_* methods run. This guard handles any edge case where
	 * a logged-out user somehow reaches admin_post_{action}.
	 */
	private function require_login(): void {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( wp_get_referer() ?: home_url() ) );
			exit;
		}
	}

	/**
	 * Reject future entry_date values for front-end submissions.
	 *
	 * The services accept any valid Y-m-d (to allow admin backfill). This guard
	 * enforces the member-facing rule that entry_date cannot be in the future.
	 * It runs before the service is called so the error message is consistent.
	 *
	 * @param array $input  The POST input array (already has forced ownership set).
	 * @return true|WP_Error  true = OK, WP_Error = reject.
	 */
	private function check_entry_date( array $input ): true|WP_Error {
		$date = ist_sanitize_date( $input['entry_date'] ?? '' );
		if ( '' === $date ) {
			// The service will also catch this — just return OK here and let it handle it.
			return true;
		}
		if ( $date > wp_date( 'Y-m-d' ) ) {
			return new WP_Error(
				'ist_future_date',
				__( 'Entry date cannot be in the future.', 'inc-stats-tracker' )
			);
		}
		return true;
	}

	/**
	 * Redirect back with a success or error query arg.
	 *
	 * On success:   redirects to the referring page with ?ist_saved=1.
	 * On error:     redirects to the referring page with ?ist_error=<message>.
	 *
	 * The referring page is the form page in normal flow, so the form template
	 * can display the appropriate notice and the user stays on the form page.
	 *
	 * @param int|WP_Error $result
	 * @param string       $type   Slug used for admin fallback URL (e.g. 'tyfcb').
	 */
	private function redirect_after( int|WP_Error $result, string $type ): void {
		$redirect = wp_get_referer() ?: admin_url( 'admin.php?page=ist-' . $type );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg(
				'ist_error',
				rawurlencode( $result->get_error_message() ),
				$redirect
			) );
		} else {
			wp_safe_redirect( add_query_arg( 'ist_saved', '1', $redirect ) );
		}
		exit;
	}
}
