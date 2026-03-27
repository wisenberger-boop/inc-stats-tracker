<?php
/**
 * BuddyBoss / BuddyPress profile nav — "My Stats" tab.
 *
 * Registers a nav item on the logged-in user's own profile only (self-view guard).
 *
 * Sub-nav structure under "My Stats":
 *   summary        → KPI table, FY Progress, recent records (default landing)
 *   log-tyfcb      → Inline TYFCB / Closed Business submission form
 *   log-referral   → Inline Referral submission form
 *   log-connect    → Inline Connect submission form
 *
 * The sub-nav URLs (e.g. /members/{user}/ist-my-stats/log-tyfcb/) are used
 * as the fallback form URLs in ist_get_form_urls() so form CTAs appear on
 * both My Stats and Group Stats Reports without requiring manual URL
 * configuration in plugin settings.
 *
 * Requires BuddyPress / BuddyBoss Platform to be active.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Profile_Nav {

	/** BP nav slug for this tab. */
	const SLUG = 'ist-my-stats';

	/** Sub-nav slugs for each inline form page. */
	const SLUG_SUMMARY  = 'summary';
	const SLUG_TYFCB    = 'log-tyfcb';
	const SLUG_REFERRAL = 'log-referral';
	const SLUG_CONNECT  = 'log-connect';

	// -------------------------------------------------------------------------
	// Registration
	// -------------------------------------------------------------------------

	/**
	 * Register the "My Stats" profile tab and its sub-nav form pages.
	 *
	 * Hooked to bp_setup_nav.
	 */
	public static function register(): void {
		if ( ! function_exists( 'bp_core_new_nav_item' ) ) {
			return;
		}

		// BuddyBoss routes profile URLs by matching what was registered during
		// bp_setup_nav against the displayed user's domain. Registration must
		// always happen when viewing any member profile — not only on self-view —
		// otherwise BuddyBoss finds no handler for /members/{user}/ist-my-stats/
		// and returns a 404. Access is controlled via user_has_access below.
		$displayed_user_id = bp_displayed_user_id();
		if ( ! $displayed_user_id ) {
			return; // Not on a member profile page — nothing to register.
		}

		// Always use the displayed user's domain for nav registration so the
		// registered parent_url matches what BuddyBoss resolves from the URL.
		$user_domain = bp_displayed_user_domain();
		$base_url    = trailingslashit( $user_domain . self::SLUG );

		// Access gate: own profile. Gates parent tab, summary, and all form sub-nav routes.
		// Form submission access is enforced in content_form() via group membership —
		// no custom capability required. See IST_Capabilities for admin-level caps.
		$can_view = bp_is_my_profile();

		// Register the parent nav item. Visible only on own profile ($can_view),
		// hidden on other members' profiles (show_for_displayed_user = false).
		bp_core_new_nav_item( array(
			'name'                    => __( 'My Stats', 'inc-stats-tracker' ),
			'slug'                    => self::SLUG,
			'parent_url'              => $user_domain,
			'default_subnav_slug'     => self::SLUG_SUMMARY,
			'position'                => 80,
			'show_for_displayed_user' => false, // only on own profile
			'user_has_access'         => $can_view,
		) );

		// Summary sub-nav: always register when $can_view so /ist-my-stats/ and
		// /ist-my-stats/summary/ resolve correctly regardless of submit capability.
		if ( ! $can_view ) {
			return;
		}

		bp_core_new_subnav_item( array(
			'name'            => __( 'Summary', 'inc-stats-tracker' ),
			'slug'            => self::SLUG_SUMMARY,
			'parent_slug'     => self::SLUG,
			'parent_url'      => $base_url,
			'screen_function' => array( static::class, 'screen' ),
			'position'        => 10,
			'user_has_access' => true,
		) );

		// Log Closed Business form page.
		bp_core_new_subnav_item( array(
			'name'            => __( 'Log Closed Business', 'inc-stats-tracker' ),
			'slug'            => self::SLUG_TYFCB,
			'parent_slug'     => self::SLUG,
			'parent_url'      => $base_url,
			'screen_function' => array( static::class, 'screen_form' ),
			'position'        => 20,
			'user_has_access' => true,
		) );

		// Log a Referral form page.
		bp_core_new_subnav_item( array(
			'name'            => __( 'Log a Referral', 'inc-stats-tracker' ),
			'slug'            => self::SLUG_REFERRAL,
			'parent_slug'     => self::SLUG,
			'parent_url'      => $base_url,
			'screen_function' => array( static::class, 'screen_form' ),
			'position'        => 30,
			'user_has_access' => true,
		) );

		// Log a Connect form page.
		bp_core_new_subnav_item( array(
			'name'            => __( 'Log a Connect', 'inc-stats-tracker' ),
			'slug'            => self::SLUG_CONNECT,
			'parent_slug'     => self::SLUG,
			'parent_url'      => $base_url,
			'screen_function' => array( static::class, 'screen_form' ),
			'position'        => 40,
			'user_has_access' => true,
		) );
	}

	// -------------------------------------------------------------------------
	// Screen callbacks
	// -------------------------------------------------------------------------

	/**
	 * Screen callback for the Summary sub-nav (KPI + recent records).
	 */
	public static function screen(): void {
		add_action( 'bp_template_content', array( static::class, 'content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Screen callback for the three inline form sub-nav pages.
	 *
	 * Uses bp_current_action() to determine which form to show, so all three
	 * sub-nav items can share this single screen function.
	 */
	public static function screen_form(): void {
		add_action( 'bp_template_content', array( static::class, 'content_form' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	// -------------------------------------------------------------------------
	// Content callbacks
	// -------------------------------------------------------------------------

	/**
	 * Render the My Stats summary content (KPI table, FY progress, recent records).
	 */
	public static function content(): void {
		$user_id  = bp_loggedin_user_id();
		$group_id = IST_Service_Members::get_configured_group_id();

		// Require group membership when a group is configured.
		if ( $group_id && ! IST_Service_Members::is_group_member( $user_id, $group_id ) ) {
			echo '<p>' . esc_html__( 'My Stats is available to group members only.', 'inc-stats-tracker' ) . '</p>';
			return;
		}

		// -----------------------------------------------------------------------
		// Date ranges.
		// -----------------------------------------------------------------------
		$today       = wp_date( 'Y-m-d' );
		$month_start = wp_date( 'Y-m-01' );
		$month_end   = $today;

		$fy_start    = IST_Fiscal_Year::get_fy_start( $today, $group_id );
		$fy_end      = $today;
		$fy_label    = IST_Fiscal_Year::get_label( $today, $group_id );
		$fy_progress = IST_Fiscal_Year::get_progress( $today, $group_id );

		// -----------------------------------------------------------------------
		// KPI data — scoped to this user only.
		// -----------------------------------------------------------------------
		$user_ids = array( $user_id );

		$tyfcb_month = IST_Stats_Query::tyfcb_totals( $month_start, $month_end, $user_ids );
		$tyfcb_fy    = IST_Stats_Query::tyfcb_totals( $fy_start, $fy_end, $user_ids );
		$ref_month   = IST_Stats_Query::referral_totals( $month_start, $month_end, $user_ids );
		$ref_fy      = IST_Stats_Query::referral_totals( $fy_start, $fy_end, $user_ids );
		$con_month   = IST_Stats_Query::connect_totals( $month_start, $month_end, $user_ids );
		$con_fy      = IST_Stats_Query::connect_totals( $fy_start, $fy_end, $user_ids );

		// -----------------------------------------------------------------------
		// Recent records.
		// -----------------------------------------------------------------------
		$tyfcb_recent    = IST_Stats_Query::tyfcb_recent( $user_id );
		$referral_recent = IST_Stats_Query::referral_recent( $user_id );
		$connect_recent  = IST_Stats_Query::connect_recent( $user_id );

		// -----------------------------------------------------------------------
		// 3-month trend data for charts.
		// -----------------------------------------------------------------------
		$trend_data = IST_Stats_Query::three_month_trend( $today, $user_ids );

		// -----------------------------------------------------------------------
		// FY monthly trend and YTD same-point comparison.
		// -----------------------------------------------------------------------
		$fy_monthly_data = IST_Stats_Query::fy_monthly_trend( $fy_start, $today, $user_ids );

		$prior_equiv_end = wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( $today ) ) );
		$prior_fy_start  = IST_Fiscal_Year::get_fy_start( $prior_equiv_end, $group_id );
		$prior_fy_label  = IST_Fiscal_Year::get_label( $prior_equiv_end, $group_id );
		$ytd_data        = IST_Stats_Query::ytd_comparison(
			$fy_start, $today,
			$prior_fy_start, $prior_equiv_end,
			$user_ids
		);

		$form_urls     = ist_get_form_urls();
		$group_members = IST_Service_Members::get_group_members();
		$current_user  = wp_get_current_user();
		$my_stats_url  = trailingslashit( bp_loggedin_user_domain() . self::SLUG . '/' . self::SLUG_SUMMARY );

		ist_get_template( 'frontend/tmpl-profile-my-stats.php', compact(
			'fy_label',
			'fy_progress',
			'month_start',
			'tyfcb_month', 'tyfcb_fy',
			'ref_month', 'ref_fy',
			'con_month', 'con_fy',
			'tyfcb_recent', 'referral_recent', 'connect_recent',
			'trend_data',
			'fy_monthly_data', 'ytd_data', 'prior_fy_label',
			'form_urls',
			'group_members', 'current_user', 'my_stats_url'
		) );
	}

	/**
	 * Render an inline form page.
	 *
	 * Determines which form to render from bp_current_action():
	 *   log-tyfcb    → TYFCB / Closed Business form
	 *   log-referral → Referral form
	 *   log-connect  → Connect form
	 */
	public static function content_form(): void {
		$user_id  = bp_loggedin_user_id();
		$group_id = IST_Service_Members::get_configured_group_id();

		// Access guard — same rules as IST_Frontend::access_guard().
		if ( ! $user_id || ! is_user_logged_in() ) {
			echo '<p class="ist-notice ist-notice--error">'
				. sprintf(
					/* translators: %s: login URL. */
					wp_kses(
						__( 'Please <a href="%s">log in</a> to submit a record.', 'inc-stats-tracker' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( wp_login_url( bp_loggedin_user_domain() . self::SLUG . '/' ) )
				)
				. '</p>';
			return;
		}

		if ( $group_id && ! IST_Service_Members::is_group_member( $user_id, $group_id ) ) {
			echo '<p class="ist-notice ist-notice--error">'
				. esc_html__( 'This form is available to group members only.', 'inc-stats-tracker' )
				. '</p>';
			return;
		}

		// Shared template variables.
		$group_members = IST_Service_Members::get_group_members();
		$current_user  = wp_get_current_user();
		$my_stats_url  = trailingslashit( bp_loggedin_user_domain() . self::SLUG . '/' . self::SLUG_SUMMARY );
		$atts          = array();

		// Map the current sub-nav slug to its template.
		$template_map = array(
			self::SLUG_TYFCB    => 'frontend/tmpl-form-tyfcb.php',
			self::SLUG_REFERRAL => 'frontend/tmpl-form-referral.php',
			self::SLUG_CONNECT  => 'frontend/tmpl-form-connect.php',
		);

		$form_slug = bp_current_action();
		$template  = $template_map[ $form_slug ] ?? '';

		if ( ! $template ) {
			echo '<p>' . esc_html__( 'Form not found.', 'inc-stats-tracker' ) . '</p>';
			return;
		}

		ist_get_template( $template, compact( 'group_members', 'current_user', 'my_stats_url', 'atts' ) );
	}

	// -------------------------------------------------------------------------
	// Static URL helpers
	// -------------------------------------------------------------------------

	/**
	 * Return the base URL for the My Stats sub-nav for a given user.
	 *
	 * Used by ist_get_form_urls() to generate form fallback URLs.
	 * Returns empty string when BuddyPress is not active or user is not
	 * logged in.
	 *
	 * @return string  e.g. "https://example.com/members/jane/ist-my-stats/"
	 */
	public static function get_base_url(): string {
		if ( ! function_exists( 'bp_loggedin_user_domain' ) || ! is_user_logged_in() ) {
			return '';
		}
		return trailingslashit( bp_loggedin_user_domain() . self::SLUG );
	}
}
