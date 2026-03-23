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

// Form submission hooks.
$ist_forms = new IST_Forms();
add_action( 'admin_post_ist_submit_tyfcb',    array( $ist_forms, 'handle_tyfcb' ) );
add_action( 'admin_post_ist_submit_referral', array( $ist_forms, 'handle_referral' ) );
add_action( 'admin_post_ist_submit_connect',  array( $ist_forms, 'handle_connect' ) );
