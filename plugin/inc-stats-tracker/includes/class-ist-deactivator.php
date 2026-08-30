<?php
/**
 * Handles plugin deactivation.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Deactivator {

	/**
	 * Run on plugin deactivation.
	 *
	 * DB tables, options, and data are intentionally preserved on deactivation.
	 * This plugin does not provide an automatic uninstall/data-removal routine;
	 * destructive removal requires an explicit, separately reviewed procedure.
	 */
	public static function deactivate(): void {
		IST_Capabilities::remove_caps();
		flush_rewrite_rules();
	}
}
