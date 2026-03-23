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
	 * Note: DB tables and data are intentionally preserved on deactivation.
	 * Use uninstall.php for full data removal.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
