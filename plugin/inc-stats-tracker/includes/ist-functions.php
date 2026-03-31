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
 * Return the form page URLs for the three submission forms.
 *
 * Priority:
 *   1. Admin-configured WordPress page URLs (ist_settings form_url_* keys).
 *      Use this when the forms live on dedicated WP pages with shortcodes.
 *   2. BuddyBoss profile sub-nav URLs registered by IST_Profile_Nav.
 *      These are generated automatically for any logged-in user with an
 *      active BuddyPress/BuddyBoss install. No manual configuration needed.
 *
 * The sub-nav fallback is why form CTAs appear on both My Stats and Group
 * Stats Reports without any admin setup — the submit-actions partial reads
 * this function and will always receive real URLs when BP is active.
 *
 * Returns empty string for any URL that cannot be resolved (e.g. on a
 * non-BP install where no settings have been configured).
 *
 * @return array { tyfcb: string, referral: string, connect: string }
 */
function ist_get_form_urls(): array {
	$settings = ist_get_settings();

	$urls = array(
		'tyfcb'   => esc_url_raw( $settings['form_url_tyfcb']   ?? '' ),
		'referral' => esc_url_raw( $settings['form_url_referral'] ?? '' ),
		'connect'  => esc_url_raw( $settings['form_url_connect']  ?? '' ),
	);

	// Fallback: BuddyBoss/BuddyPress profile sub-nav URLs.
	// Only applied on the front end for logged-in users with BP active.
	// IST_Profile_Nav::get_base_url() is safe to call here because this
	// function is called at runtime, not at file-parse time.
	if ( ! is_admin() && is_user_logged_in() && class_exists( 'IST_Profile_Nav' ) ) {
		$base = IST_Profile_Nav::get_base_url(); // e.g. https://example.com/members/jane/ist-my-stats/
		// Guard against an empty or relative-only base (e.g. if bp_loggedin_user_domain returns '').
		if ( $base && str_starts_with( $base, 'http' ) ) {
			if ( empty( $urls['tyfcb'] ) ) {
				$urls['tyfcb'] = $base . IST_Profile_Nav::SLUG_TYFCB . '/';
			}
			if ( empty( $urls['referral'] ) ) {
				$urls['referral'] = $base . IST_Profile_Nav::SLUG_REFERRAL . '/';
			}
			if ( empty( $urls['connect'] ) ) {
				$urls['connect'] = $base . IST_Profile_Nav::SLUG_CONNECT . '/';
			}
		}
	}

	return $urls;
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
