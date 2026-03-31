<?php
/**
 * Admin settings page — BuddyBoss group configuration and fiscal year.
 *
 * Handles both the page render and the save action (admin_post_ist_save_settings).
 * Settings are split across two WordPress options:
 *
 *   ist_settings      Plugin-wide: bb_group_id, date_format, records_per_page.
 *   ist_group_config  Per-group: fiscal_year_start_month (keyed by BB group ID).
 *
 * Requires manage_options capability. No plugin-specific cap is needed because
 * these settings affect plugin-wide behaviour and should be restricted to WP admins.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Settings {

	/**
	 * Render the settings page.
	 */
	public function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$settings     = ist_get_settings();
		$group_id     = (int) ( $settings['bb_group_id'] ?? 0 );
		$group_config = ist_get_group_config( $group_id );

		ist_get_template( 'admin/tmpl-settings.php', compact( 'settings', 'group_id', 'group_config' ) );
	}

	/**
	 * Handle the settings form submission (admin_post_ist_save_settings).
	 *
	 * Validates, saves ist_settings and ist_group_config, then redirects back
	 * to the settings page with an updated=1 query arg.
	 */
	public function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_save_settings' );

		// -----------------------------------------------------------------------
		// Sanitize and validate inputs.
		// -----------------------------------------------------------------------
		$bb_group_id             = absint( $_POST['bb_group_id'] ?? 0 );
		$fiscal_year_start_month = absint( $_POST['fiscal_year_start_month'] ?? 7 );
		$records_per_page        = absint( $_POST['records_per_page'] ?? 25 );
		$form_url_tyfcb          = esc_url_raw( wp_unslash( $_POST['form_url_tyfcb']   ?? '' ) );
		$form_url_referral       = esc_url_raw( wp_unslash( $_POST['form_url_referral'] ?? '' ) );
		$form_url_connect        = esc_url_raw( wp_unslash( $_POST['form_url_connect']  ?? '' ) );

		if ( $fiscal_year_start_month < 1 || $fiscal_year_start_month > 12 ) {
			$fiscal_year_start_month = 7;
		}

		if ( $records_per_page < 1 || $records_per_page > 500 ) {
			$records_per_page = 25;
		}

		// -----------------------------------------------------------------------
		// Persist ist_settings (plugin-wide).
		// Preserve keys we don't manage on this screen (date_format, etc.).
		// -----------------------------------------------------------------------
		$settings                     = ist_get_settings();
		$settings['bb_group_id']      = $bb_group_id;
		$settings['records_per_page'] = $records_per_page;
		$settings['form_url_tyfcb']   = $form_url_tyfcb;
		$settings['form_url_referral'] = $form_url_referral;
		$settings['form_url_connect']  = $form_url_connect;
		update_option( 'ist_settings', $settings );

		// -----------------------------------------------------------------------
		// Persist ist_group_config for the (new) group ID.
		// Other groups' entries are preserved untouched.
		// -----------------------------------------------------------------------
		$group_config = (array) get_option( 'ist_group_config', array() );
		if ( ! isset( $group_config[ $bb_group_id ] ) ) {
			$group_config[ $bb_group_id ] = array();
		}
		$group_config[ $bb_group_id ]['fiscal_year_start_month'] = $fiscal_year_start_month;
		update_option( 'ist_group_config', $group_config );

		// Flush the member transient cache in case the group ID changed.
		IST_Service_Members::flush_cache();

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'ist-settings', 'updated' => '1' ),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
