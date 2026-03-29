<?php
/**
 * Centralized hook registration.
 *
 * Wire actions and filters here rather than scattering add_action() calls
 * throughout individual class files. Each class should expose public methods;
 * this file connects them to WordPress.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Admin hooks.
if ( is_admin() ) {
	$ist_admin = new IST_Admin();
	add_action( 'admin_menu',            array( $ist_admin, 'register_menus' ) );
	add_action( 'admin_enqueue_scripts', array( $ist_admin, 'enqueue_assets' ) );
}

// Frontend hooks.
$ist_frontend = new IST_Frontend();
add_action( 'init',                  array( $ist_frontend, 'register_shortcodes' ) );
add_action( 'wp_enqueue_scripts',    array( $ist_frontend, 'enqueue_assets' ) );

// Form submission hooks — logged-in users.
$ist_forms = new IST_Forms();
add_action( 'admin_post_ist_submit_tyfcb',    array( $ist_forms, 'handle_tyfcb' ) );
add_action( 'admin_post_ist_submit_referral', array( $ist_forms, 'handle_referral' ) );
add_action( 'admin_post_ist_submit_connect',  array( $ist_forms, 'handle_connect' ) );

// Form submission hooks — non-logged-in users (redirect to login).
add_action( 'admin_post_nopriv_ist_submit_tyfcb',    array( $ist_forms, 'handle_nopriv' ) );
add_action( 'admin_post_nopriv_ist_submit_referral', array( $ist_forms, 'handle_nopriv' ) );
add_action( 'admin_post_nopriv_ist_submit_connect',  array( $ist_forms, 'handle_nopriv' ) );

// BuddyBoss / BuddyPress profile nav — "My Stats" tab.
add_action( 'bp_setup_nav', array( 'IST_Profile_Nav', 'register' ), 10 );

// DB schema upgrade check — runs on every admin load but is cheap (option lookup).
add_action( 'admin_init', array( 'IST_Activator', 'maybe_upgrade' ) );

// Settings save hook (admin only — logged-in WP admins).
if ( is_admin() ) {
	$ist_settings_handler = new IST_Admin_Settings();
	add_action( 'admin_post_ist_save_settings', array( $ist_settings_handler, 'handle_save_settings' ) );

	// Historical import POST handlers.
	$ist_import_handler = new IST_Admin_Import();
	add_action( 'admin_post_ist_run_historical_import',  array( $ist_import_handler, 'handle_run_import' ) );
	add_action( 'admin_post_ist_reset_import_hashes',    array( $ist_import_handler, 'handle_reset_hashes' ) );
	add_action( 'admin_post_ist_mark_legacy_as_imported', array( $ist_import_handler, 'handle_mark_legacy' ) );
	add_action( 'admin_post_ist_purge_imported_records',  array( $ist_import_handler, 'handle_purge_imported' ) );
}
