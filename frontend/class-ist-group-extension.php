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
			'name'              => __( 'Group Stats Reports', 'inc-stats-tracker' ),
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

		$fy_start = IST_Fiscal_Year::get_fy_start( $today, $group_id );
		$fy_end   = $today;
		$fy_label = IST_Fiscal_Year::get_label( $today, $group_id );

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

		$form_urls = ist_get_form_urls();

		ist_get_template( 'frontend/tmpl-group-stats-reports.php', compact(
			'fy_label',
			'month_start',
			'tyfcb_month', 'tyfcb_fy',
			'ref_month', 'ref_fy',
			'con_month', 'con_fy',
			'tyfcb_leaderboard', 'referral_leaderboard', 'connect_leaderboard',
			'form_urls'
		) );
	}
}
