<?php
/**
 * Admin — Historical Import page.
 *
 * Provides a one-time import UI for loading legacy Google Form CSV data into
 * the three IST database tables. Import is idempotent: re-running skips rows
 * that were already imported (identified by SHA1 row hash).
 *
 * Requires manage_options capability.
 *
 * POST actions:
 *   ist_run_historical_import  — runs IST_Historical_Importer::import_all()
 *   ist_reset_import_hashes    — clears the hash store so the import can be
 *                                re-run from scratch (destructive — prompts JS
 *                                confirm in the template)
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Admin_Import {

	/** Transient key prefix — per-user so concurrent admins don't clash. */
	const TRANSIENT_PREFIX = 'ist_import_results_';

	/** Seconds to keep the results transient. */
	const TRANSIENT_TTL = 600;

	// -------------------------------------------------------------------------
	// Page render
	// -------------------------------------------------------------------------

	/**
	 * Render the Import Historical Data admin page.
	 */
	public function page_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		$importer          = new IST_Historical_Importer();
		$csv_stats         = $importer->get_csv_stats();
		$member_count      = $importer->get_member_count();
		$lookup_found      = $importer->lookup_file_exists();
		$imported_count    = $importer->get_imported_count();
		$legacy_native_count = $importer->get_legacy_native_count();

		// Pick up results stored by the POST handler after redirect.
		$transient_key = self::TRANSIENT_PREFIX . get_current_user_id();
		$results       = get_transient( $transient_key );
		delete_transient( $transient_key );

		ist_get_template( 'admin/tmpl-import.php', compact(
			'csv_stats',
			'member_count',
			'lookup_found',
			'imported_count',
			'legacy_native_count',
			'results'
		) );
	}

	// -------------------------------------------------------------------------
	// POST handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle the "Run Import" form submission.
	 *
	 * Runs all three CSV imports, stores results in a short-lived transient,
	 * then redirects back to the import page.
	 */
	public function handle_run_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_run_historical_import' );

		// Allow extra time — ~2700 rows across three tables.
		set_time_limit( 300 );

		$importer = new IST_Historical_Importer();
		$results  = $importer->import_all();

		set_transient(
			self::TRANSIENT_PREFIX . get_current_user_id(),
			$results,
			self::TRANSIENT_TTL
		);

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'ist-import', 'import_done' => '1' ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Handle the "Reset Import Hashes" form submission.
	 *
	 * Clears the hash store so the next "Run Import" will re-insert all rows.
	 * This is intentionally destructive and requires a JS confirm in the UI.
	 */
	public function handle_reset_hashes(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_reset_import_hashes' );

		$importer = new IST_Historical_Importer();
		$importer->reset_hashes();

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'ist-import', 'hashes_reset' => '1' ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Handle the "Mark Legacy Rows as Imported" form submission.
	 *
	 * Updates data_source='native' → 'import' across all three tables.
	 * Intended for dev/staging installs where rows were imported before the
	 * data_source column existed (pre-0.2.26). Should NOT be run on a live
	 * installation that already has genuine native member submissions.
	 */
	public function handle_mark_legacy(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_mark_legacy_as_imported' );

		$importer = new IST_Historical_Importer();
		$counts   = $importer->mark_legacy_as_imported();
		$total    = array_sum( $counts );

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'ist-import', 'legacy_marked' => $total ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Handle the "Purge Imported Records" form submission.
	 *
	 * Deletes all rows flagged data_source='import' from all three tables and
	 * clears the hash store. Only affects imported rows — native plugin
	 * submissions are never touched. Requires a JS confirm in the UI.
	 */
	public function handle_purge_imported(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'inc-stats-tracker' ) );
		}

		check_admin_referer( 'ist_purge_imported_records' );

		$importer = new IST_Historical_Importer();
		$counts   = $importer->purge_imported();

		$total = array_sum( $counts );

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'ist-import', 'purge_done' => $total ),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
