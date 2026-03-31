<?php
/**
 * Admin page — Group Roster (read-only).
 *
 * Displays the current member list sourced from the configured BuddyBoss group.
 * This plugin does not manage the member roster; that is BuddyBoss's responsibility.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Members {

	public function page_members(): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_view_dashboard' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$members = IST_Service_Members::get_group_members();

		ist_get_template( 'admin/tmpl-members.php', compact( 'members' ) );
	}
}
