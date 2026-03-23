<?php
/**
 * Roles and capabilities for INC Stats Tracker.
 *
 * Custom caps are granted/removed on activation/deactivation via IST_Activator.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Capabilities {

	/**
	 * All custom capabilities this plugin introduces.
	 *
	 * @var array<string, string>  cap => human label
	 */
	public const CAPS = array(
		'ist_view_dashboard'  => 'View INC Stats Dashboard',
		'ist_submit_records'  => 'Submit INC Stats Records',
		'ist_manage_tyfcb'    => 'Manage TYFCB Records',
		'ist_manage_referrals'=> 'Manage Referral Records',
		'ist_manage_connects' => 'Manage Connect Records',
		'ist_view_reports'    => 'View Reports',
		'ist_import_records'  => 'Import Records',
		'ist_export_records'  => 'Export Records',
	);

	/**
	 * Grant all caps to the administrator role on activation.
	 */
	public static function add_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( array_keys( self::CAPS ) as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Remove all custom caps on deactivation.
	 */
	public static function remove_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( array_keys( self::CAPS ) as $cap ) {
			$admin->remove_cap( $cap );
		}
	}

	/**
	 * Check if the current user has a given IST capability.
	 *
	 * @param string $cap One of the caps defined in self::CAPS.
	 * @return bool
	 */
	public static function current_user_can( string $cap ): bool {
		return current_user_can( $cap );
	}
}
