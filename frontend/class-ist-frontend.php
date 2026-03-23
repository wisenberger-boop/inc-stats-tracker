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
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style(
			'ist-frontend',
			IST_PLUGIN_URL . 'assets/css/ist-frontend.css',
			array(),
			IST_VERSION
		);

		wp_enqueue_script(
			'ist-frontend',
			IST_PLUGIN_URL . 'assets/js/ist-frontend.js',
			array( 'jquery' ),
			IST_VERSION,
			true
		);

		wp_localize_script( 'ist-frontend', 'istFrontend', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ist_frontend_nonce' ),
		) );
	}

	public function render_tyfcb_form( array $atts ): string {
		$group_members = IST_Service_Members::get_group_members();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-tyfcb.php', compact( 'atts', 'group_members' ) );
		return ob_get_clean();
	}

	public function render_referral_form( array $atts ): string {
		$group_members = IST_Service_Members::get_group_members();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-referral.php', compact( 'atts', 'group_members' ) );
		return ob_get_clean();
	}

	public function render_connect_form( array $atts ): string {
		$group_members = IST_Service_Members::get_group_members();
		ob_start();
		ist_get_template( 'frontend/tmpl-form-connect.php', compact( 'atts', 'group_members' ) );
		return ob_get_clean();
	}
}
