<?php
/**
 * Admin — menu registration and asset enqueuing.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin {

	/**
	 * Register top-level and sub-menus.
	 */
	public function register_menus(): void {
		add_menu_page(
			__( 'INC Stats', 'inc-stats-tracker' ),
			__( 'INC Stats', 'inc-stats-tracker' ),
			'ist_view_dashboard',
			'ist-dashboard',
			array( $this, 'page_dashboard' ),
			'dashicons-chart-bar',
			30
		);

		$subpages = array(
			array( 'ist-tyfcb',     __( 'TYFCB', 'inc-stats-tracker' ),        'ist_manage_tyfcb',     'IST_Admin_TYFCB',     'page_tyfcb' ),
			array( 'ist-referrals', __( 'Referrals', 'inc-stats-tracker' ),    'ist_manage_referrals', 'IST_Admin_Referrals', 'page_referrals' ),
			array( 'ist-connects',  __( 'Connects', 'inc-stats-tracker' ),     'ist_manage_connects',  'IST_Admin_Connects',  'page_connects' ),
			array( 'ist-members',   __( 'Group Roster', 'inc-stats-tracker' ), 'ist_view_dashboard',   'IST_Admin_Members',   'page_members' ),
			array( 'ist-reports',   __( 'Reports', 'inc-stats-tracker' ),      'ist_view_reports',     'IST_Admin_Reports',   'page_reports' ),
			array( 'ist-settings',  __( 'Settings', 'inc-stats-tracker' ),     'manage_options',       'IST_Admin_Settings',  'page_settings' ),
		);

		foreach ( $subpages as $sub ) {
			[ $slug, $title, $cap, $class, $method ] = $sub;
			add_submenu_page(
				'ist-dashboard',
				$title,
				$title,
				$cap,
				$slug,
				array( new $class(), $method )
			);
		}
	}

	/**
	 * Enqueue admin CSS and JS on IST pages only.
	 *
	 * @param string $hook  Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( $hook, 'ist-' ) ) {
			return;
		}

		wp_enqueue_style(
			'ist-admin',
			IST_PLUGIN_URL . 'assets/css/ist-admin.css',
			array(),
			IST_VERSION
		);

		wp_enqueue_script(
			'ist-admin',
			IST_PLUGIN_URL . 'assets/js/ist-admin.js',
			array( 'jquery' ),
			IST_VERSION,
			true
		);

		wp_localize_script( 'ist-admin', 'istAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ist_admin_nonce' ),
		) );
	}

	/**
	 * Render the main dashboard page.
	 */
	public function page_dashboard(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_view_dashboard' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}
		ist_get_template( 'admin/tmpl-dashboard.php' );
	}
}
