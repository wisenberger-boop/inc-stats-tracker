<?php
/**
 * Frontend — shortcode registration and asset enqueuing.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Frontend {

	/**
	 * Register shortcodes.
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'ist_tyfcb_form',    array( $this, 'render_tyfcb_form' ) );
		add_shortcode( 'ist_referral_form', array( $this, 'render_referral_form' ) );
		add_shortcode( 'ist_connect_form',  array( $this, 'render_connect_form' ) );
	}

	/**
	 * Enqueue frontend CSS and JS.
	 *
	 * Always enqueues on pages using IST shortcodes; also enqueues on
	 * BuddyBoss profile and group pages where the IST tabs appear.
	 */
	public function enqueue_assets(): void {
		// On BuddyPress/BuddyBoss pages, only enqueue when viewing our tabs.
		if ( function_exists( 'bp_is_active' ) ) {
			$on_profile_tab = function_exists( 'bp_is_user' ) && bp_is_user()
				&& function_exists( 'bp_is_current_component' )
				&& bp_is_current_component( IST_Profile_Nav::SLUG );

			$on_group_tab = function_exists( 'bp_is_group' ) && bp_is_group()
				&& function_exists( 'bp_is_current_action' )
				&& bp_is_current_action( 'ist-group-stats' );

			// On BP pages that are NOT our tabs, skip enqueue.
			if ( ( function_exists( 'bp_is_user' ) && bp_is_user() && ! $on_profile_tab )
				|| ( function_exists( 'bp_is_group' ) && bp_is_group() && ! $on_group_tab ) ) {
				return;
			}
		}

		wp_enqueue_style(
			'ist-frontend',
			IST_PLUGIN_URL . 'assets/css/ist-frontend.css',
			array(),
			IST_VERSION
		);

		// Register Chart.js from CDN (loaded in footer).
		// Version pinned so behaviour is predictable across environments.
		wp_register_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		wp_enqueue_script(
			'ist-frontend',
			IST_PLUGIN_URL . 'assets/js/ist-frontend.js',
			array( 'jquery', 'chartjs' ),
			IST_VERSION,
			true
		);

		wp_localize_script( 'ist-frontend', 'istFrontend', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ist_frontend_nonce' ),
		) );
	}

	public function render_tyfcb_form( array $atts ): string {
		$guard = $this->access_guard();
		if ( $guard ) {
			return $guard;
		}
		$group_members = IST_Service_Members::get_group_members();
		$current_user  = wp_get_current_user();
		$my_stats_url  = $this->get_my_stats_url();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-tyfcb.php', compact( 'atts', 'group_members', 'current_user', 'my_stats_url' ) );
		return ob_get_clean();
	}

	public function render_referral_form( array $atts ): string {
		$guard = $this->access_guard();
		if ( $guard ) {
			return $guard;
		}
		$group_members = IST_Service_Members::get_group_members();
		$current_user  = wp_get_current_user();
		$my_stats_url  = $this->get_my_stats_url();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-referral.php', compact( 'atts', 'group_members', 'current_user', 'my_stats_url' ) );
		return ob_get_clean();
	}

	public function render_connect_form( array $atts ): string {
		$guard = $this->access_guard();
		if ( $guard ) {
			return $guard;
		}
		$group_members = IST_Service_Members::get_group_members();
		$current_user  = wp_get_current_user();
		$my_stats_url  = $this->get_my_stats_url();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-connect.php', compact( 'atts', 'group_members', 'current_user', 'my_stats_url' ) );
		return ob_get_clean();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the My Stats summary URL for the current user.
	 *
	 * Used to power the "← Back to My Stats" link in form success notices.
	 * Returns an empty string when BuddyPress is inactive or the user is not
	 * logged in — the templates skip the link when the value is empty.
	 *
	 * @return string
	 */
	private function get_my_stats_url(): string {
		if ( ! class_exists( 'IST_Profile_Nav' ) ) {
			return '';
		}
		$base = IST_Profile_Nav::get_base_url();
		return $base ? trailingslashit( $base . IST_Profile_Nav::SLUG_SUMMARY ) : '';
	}

	/**
	 * Check login status and group membership.
	 *
	 * Returns an HTML error string when access should be denied, or an empty
	 * string when the current user may proceed to the form.
	 *
	 * @return string  Non-empty = deny with this message. Empty = allow.
	 */
	private function access_guard(): string {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<p class="ist-notice ist-notice--error">'
				. sprintf(
					/* translators: %s: login page URL. */
					wp_kses(
						__( 'Please <a href="%s">log in</a> to submit a record.', 'inc-stats-tracker' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( $login_url )
				)
				. '</p>';
		}

		$group_id = IST_Service_Members::get_configured_group_id();
		if ( $group_id && ! IST_Service_Members::is_group_member( get_current_user_id(), $group_id ) ) {
			return '<p class="ist-notice ist-notice--error">'
				. esc_html__( 'This form is available to group members only.', 'inc-stats-tracker' )
				. '</p>';
		}

		return '';
	}
}
