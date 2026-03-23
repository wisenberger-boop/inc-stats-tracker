<?php
/**
 * BuddyBoss / BuddyPress profile nav — "My Stats" tab.
 *
 * Registers a nav item on the logged-in user's own profile only (self-view guard).
 * Renders "My Stats" content via IST_Stats_Query when the tab is active.
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

	/**
	 * Register the "My Stats" profile tab.
	 *
	 * Hooked to bp_setup_nav.
	 */
	public static function register(): void {
		if ( ! function_exists( 'bp_core_new_nav_item' ) ) {
			return;
		}

		// Only show the tab on the logged-in user's own profile.
		if ( ! bp_is_my_profile() ) {
			return;
		}

		$user_id     = bp_loggedin_user_id();
		$user_domain = bp_loggedin_user_domain();

		bp_core_new_nav_item( array(
			'name'                    => __( 'My Stats', 'inc-stats-tracker' ),
			'slug'                    => self::SLUG,
			'parent_url'              => $user_domain,
			'default_subnav_slug'     => self::SLUG,
			'screen_function'         => array( static::class, 'screen' ),
			'position'                => 80,
			'show_for_displayed_user' => false, // only on own profile
			'user_has_access'         => ( $user_id && current_user_can( 'ist_submit_records' ) ),
		) );
	}

	/**
	 * Screen callback — set the page title and hook the template.
	 */
	public static function screen(): void {
		add_action( 'bp_template_content', array( static::class, 'content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Render the My Stats page content.
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
		$today      = wp_date( 'Y-m-d' );
		$month_start = wp_date( 'Y-m-01' );
		$month_end   = $today;

		$fy_start = IST_Fiscal_Year::get_fy_start( $today, $group_id );
		$fy_end   = $today;
		$fy_label = IST_Fiscal_Year::get_label( $today, $group_id );

		// -----------------------------------------------------------------------
		// KPI data — scoped to this user only.
		// -----------------------------------------------------------------------
		$user_ids = array( $user_id );

		$tyfcb_month   = IST_Stats_Query::tyfcb_totals( $month_start, $month_end, $user_ids );
		$tyfcb_fy      = IST_Stats_Query::tyfcb_totals( $fy_start, $fy_end, $user_ids );
		$ref_month     = IST_Stats_Query::referral_totals( $month_start, $month_end, $user_ids );
		$ref_fy        = IST_Stats_Query::referral_totals( $fy_start, $fy_end, $user_ids );
		$con_month     = IST_Stats_Query::connect_totals( $month_start, $month_end, $user_ids );
		$con_fy        = IST_Stats_Query::connect_totals( $fy_start, $fy_end, $user_ids );

		// -----------------------------------------------------------------------
		// Recent records.
		// -----------------------------------------------------------------------
		$tyfcb_recent    = IST_Stats_Query::tyfcb_recent( $user_id );
		$referral_recent = IST_Stats_Query::referral_recent( $user_id );
		$connect_recent  = IST_Stats_Query::connect_recent( $user_id );

		$form_urls = ist_get_form_urls();

		ist_get_template( 'frontend/tmpl-profile-my-stats.php', compact(
			'fy_label',
			'month_start',
			'tyfcb_month', 'tyfcb_fy',
			'ref_month', 'ref_fy',
			'con_month', 'con_fy',
			'tyfcb_recent', 'referral_recent', 'connect_recent',
			'form_urls'
		) );
	}
}
