<?php
/**
 * BuddyBoss / BuddyPress group extension — "Group Stats Reports" tab.
 *
 * Extends BP_Group_Extension to add a "Group Stats Reports" tab to the
 * configured BuddyBoss group. Shows KPI summaries and leaderboards for all
 * three stat types across This Month and This Fiscal Year periods.
 *
 * Register via:
 *   bp_register_group_extension( 'IST_Group_Extension' );
 *
 * Requires BuddyPress / BuddyBoss Platform to be active and
 * BP_Group_Extension to be defined (included with BP).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BP_Group_Extension' ) ) {
	return;
}

class IST_Group_Extension extends BP_Group_Extension {

	/**
	 * Set up the extension.
	 */
	public function __construct() {
		$args = array(
			'name'              => __( 'INC Stats Reports', 'inc-stats-tracker' ),
			'slug'              => 'ist-group-stats',
			'nav_item_position' => 80,
			'enable_nav_item'   => true,
			'screens'           => array(
				'admin' => array( 'enabled' => false ),
				'create' => array( 'enabled' => false ),
			),
		);

		parent::init( $args );
	}

	/**
	 * Only show this tab for the configured BuddyBoss group.
	 *
	 * @param int $group_id  Current group ID.
	 * @return bool
	 */
	public function enable_nav_item( $group_id ) {
		$configured = IST_Service_Members::get_configured_group_id();
		return $configured && ( (int) $group_id === $configured );
	}

	/**
	 * Render the group tab content.
	 *
	 * @param int $group_id  Current group ID.
	 */
	public function display( $group_id = null ) {
		$group_id = $group_id ?: bp_get_current_group_id();

		// Confirm this is the configured group.
		$configured_group = IST_Service_Members::get_configured_group_id();
		if ( ! $configured_group || (int) $group_id !== $configured_group ) {
			echo '<p>' . esc_html__( 'Group Stats are not available for this group.', 'inc-stats-tracker' ) . '</p>';
			return;
		}

		// -----------------------------------------------------------------------
		// Build the member user_ids scope (all current group members).
		// -----------------------------------------------------------------------
		$members  = IST_Service_Members::get_group_members();
		$user_ids = array_column( $members, 'ID' );

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
		// KPI totals — all group members combined.
		// -----------------------------------------------------------------------
		$tyfcb_month = IST_Stats_Query::tyfcb_totals( $month_start, $month_end, $user_ids );
		$tyfcb_fy    = IST_Stats_Query::tyfcb_totals( $fy_start, $fy_end, $user_ids );
		$ref_month   = IST_Stats_Query::referral_totals( $month_start, $month_end, $user_ids );
		$ref_fy      = IST_Stats_Query::referral_totals( $fy_start, $fy_end, $user_ids );
		$con_month   = IST_Stats_Query::connect_totals( $month_start, $month_end, $user_ids );
		$con_fy      = IST_Stats_Query::connect_totals( $fy_start, $fy_end, $user_ids );

		// -----------------------------------------------------------------------
		// Leaderboards — FY scope only.
		// -----------------------------------------------------------------------
		$tyfcb_leaderboard    = IST_Stats_Query::tyfcb_leaderboard( $fy_start, $fy_end, $user_ids );
		$referral_leaderboard = IST_Stats_Query::referral_leaderboard( $fy_start, $fy_end, $user_ids );
		$connect_leaderboard  = IST_Stats_Query::connect_leaderboard( $fy_start, $fy_end, $user_ids );

		// 3-month trend data for charts.
		$trend_data = IST_Stats_Query::three_month_trend( $today, $user_ids );

		// FY monthly trend and YTD same-point comparison.
		$fy_monthly_data = IST_Stats_Query::fy_monthly_trend( $fy_start, $today, $user_ids );

		$prior_equiv_end = wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( $today ) ) );
		$prior_fy_start  = IST_Fiscal_Year::get_fy_start( $prior_equiv_end, $group_id );
		$prior_fy_label  = IST_Fiscal_Year::get_label( $prior_equiv_end, $group_id );
		$ytd_data        = IST_Stats_Query::ytd_comparison(
			$fy_start, $today,
			$prior_fy_start, $prior_equiv_end,
			$user_ids
		);

		// -----------------------------------------------------------------------
		// Attribution reporting — FY scope, all group members.
		// -----------------------------------------------------------------------
		$tyfcb_rollup      = IST_Stats_Query::tyfcb_attribution_rollup( $fy_start, $fy_end, $user_ids );
		$tyfcb_coverage    = IST_Stats_Query::tyfcb_model_coverage( $fy_start, $fy_end, $user_ids );
		$tyfcb_by_source   = IST_Stats_Query::tyfcb_by_attribution_source( $fy_start, $fy_end, $user_ids );
		$tyfcb_by_rel      = IST_Stats_Query::tyfcb_by_relationship_type( $fy_start, $fy_end, $user_ids );
		$tyfcb_by_referrer = IST_Stats_Query::tyfcb_by_referrer_type( $fy_start, $fy_end, $user_ids );

		$form_urls = ist_get_form_urls();

		// Required by the form templates rendered inside the modal containers.
		$group_members = $members;
		$current_user  = wp_get_current_user();
		$my_stats_url  = IST_Profile_Nav::get_base_url() . IST_Profile_Nav::SLUG_SUMMARY . '/';
		$atts          = array();

		ist_get_template( 'frontend/tmpl-group-stats-reports.php', compact(
			'fy_label',
			'fy_progress',
			'month_start',
			'tyfcb_month', 'tyfcb_fy',
			'ref_month', 'ref_fy',
			'con_month', 'con_fy',
			'tyfcb_leaderboard', 'referral_leaderboard', 'connect_leaderboard',
			'trend_data',
			'fy_monthly_data', 'ytd_data', 'prior_fy_label',
			'tyfcb_rollup', 'tyfcb_coverage', 'tyfcb_by_source', 'tyfcb_by_rel', 'tyfcb_by_referrer',
			'form_urls',
			'group_members', 'current_user', 'my_stats_url', 'atts'
		) );
	}
}
