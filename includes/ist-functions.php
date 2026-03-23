<?php
/**
 * Global utility functions for INC Stats Tracker.
 *
 * Keep these stateless and side-effect free.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize and validate a date string, returning a Y-m-d value or empty string.
 *
 * @param string $date
 * @return string
 */
function ist_sanitize_date( string $date ): string {
	$d = DateTime::createFromFormat( 'Y-m-d', sanitize_text_field( $date ) );
	return ( $d && $d->format( 'Y-m-d' ) === $date ) ? $date : '';
}

/**
 * Return a formatted currency string.
 *
 * @param float  $amount
 * @param string $symbol
 * @return string
 */
function ist_format_currency( float $amount, string $symbol = '$' ): string {
	return $symbol . number_format( $amount, 2 );
}

/**
 * Load a template file from /templates, passing optional variables.
 *
 * @param string $template  Relative path inside /templates (e.g. 'admin/tmpl-dashboard.php').
 * @param array  $vars      Variables to extract into template scope.
 */
function ist_get_template( string $template, array $vars = array() ): void {
	$path = IST_PLUGIN_DIR . 'templates/' . ltrim( $template, '/' );
	if ( ! file_exists( $path ) ) {
		// translators: %s is the template path.
		wp_die( esc_html( sprintf( __( 'IST template not found: %s', 'inc-stats-tracker' ), $path ) ) );
	}
	if ( $vars ) {
		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
	}
	include $path;
}

/**
 * Return the current IST settings array.
 *
 * @return array
 */
function ist_get_settings(): array {
	return (array) get_option( 'ist_settings', array() );
}

/**
 * Return the form page URLs stored in ist_settings.
 *
 * Keys: 'tyfcb', 'referral', 'connect'.
 * Values are full URLs to the WP pages that host the corresponding shortcodes.
 * Empty string means no page has been configured.
 *
 * @return array { tyfcb: string, referral: string, connect: string }
 */
function ist_get_form_urls(): array {
	$settings = ist_get_settings();
	return array(
		'tyfcb'   => esc_url_raw( $settings['form_url_tyfcb']   ?? '' ),
		'referral' => esc_url_raw( $settings['form_url_referral'] ?? '' ),
		'connect'  => esc_url_raw( $settings['form_url_connect']  ?? '' ),
	);
}

/**
 * Return the config array for a specific BuddyBoss group.
 *
 * All group-specific settings (e.g. fiscal_year_start_month) live in
 * ist_group_config keyed by BuddyBoss group ID. This function is the
 * single read point for that data.
 *
 * @param int $group_id  BuddyBoss group ID. 0 = resolve from ist_settings['bb_group_id'].
 * @return array  Group config, or empty array if no config exists for this group.
 */
function ist_get_group_config( int $group_id = 0 ): array {
	if ( ! $group_id ) {
		$settings = ist_get_settings();
		$group_id = (int) ( $settings['bb_group_id'] ?? 0 );
	}
	$all_config = (array) get_option( 'ist_group_config', array() );
	return (array) ( $all_config[ $group_id ] ?? array() );
}
