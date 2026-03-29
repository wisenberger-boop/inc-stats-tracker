<?php
/**
 * Admin template — Historical Data Import page.
 *
 * Available variables (passed via ist_get_template):
 *   $csv_stats      array   { tyfcb: int|null, referrals: int|null, connects: int|null }
 *                           null = file not found on disk.
 *   $member_count   int     Members loaded from the lookup CSV.
 *   $lookup_found   bool    Whether the member lookup CSV exists.
 *   $imported_count int     Number of row hashes already stored (= rows imported).
 *   $results        array|false  Import results after a run, or false if not yet run.
 *                           Shape: { tyfcb: result, referrals: result, connects: result }
 *                           Each result: { imported, skipped, warnings[], errors[] }
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$all_csvs_found = ( null !== $csv_stats['tyfcb'] )
	&& ( null !== $csv_stats['referrals'] )
	&& ( null !== $csv_stats['connects'] );

$total_csv_rows = ( $csv_stats['tyfcb'] ?? 0 )
	+ ( $csv_stats['referrals'] ?? 0 )
	+ ( $csv_stats['connects'] ?? 0 );

$already_complete = $imported_count > 0 && $imported_count >= $total_csv_rows;
?>
<div class="wrap ist-import">
	<h1><?php esc_html_e( 'Import Historical Data', 'inc-stats-tracker' ); ?></h1>

	<?php /* ---- Notice banners ------------------------------------------ */ ?>

	<?php if ( ! empty( $_GET['import_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Import run complete. See results below.', 'inc-stats-tracker' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $_GET['hashes_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'Import history cleared. Running the importer again will re-insert all rows.', 'inc-stats-tracker' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['legacy_marked'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %d: number of rows re-tagged. */
					esc_html__( 'Done — %d row(s) re-tagged as imported. You can now use Purge Imported Records to reset and re-import.', 'inc-stats-tracker' ),
					(int) $_GET['legacy_marked'] // phpcs:ignore WordPress.Security.NonceVerification
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['purge_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %d: number of rows deleted. */
					esc_html__( 'Purge complete — %d imported row(s) deleted. Import history also cleared. You can now re-run the import.', 'inc-stats-tracker' ),
					(int) $_GET['purge_done'] // phpcs:ignore WordPress.Security.NonceVerification
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $already_complete && ! $results ) : ?>
		<div class="notice notice-info">
			<p>
				<?php
				printf(
					/* translators: %d: number of imported rows. */
					esc_html__( 'All %d rows have already been imported. Re-running the importer will skip all of them.', 'inc-stats-tracker' ),
					esc_html( number_format( $imported_count ) )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php /* ---- Source file status -------------------------------------- */ ?>
	<h2><?php esc_html_e( 'Source Files', 'inc-stats-tracker' ); ?></h2>
	<table class="widefat striped" style="max-width:680px;">
		<thead>
			<tr>
				<th><?php esc_html_e( 'File', 'inc-stats-tracker' ); ?></th>
				<th><?php esc_html_e( 'Status', 'inc-stats-tracker' ); ?></th>
				<th><?php esc_html_e( 'Data rows', 'inc-stats-tracker' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$file_rows = array(
				array( 'docs/source-assets/csv/TYFCB.csv',                          __( 'TYFCB',           'inc-stats-tracker' ), $csv_stats['tyfcb'] ),
				array( 'docs/source-assets/csv/referrals.csv',                      __( 'Referrals',       'inc-stats-tracker' ), $csv_stats['referrals'] ),
				array( 'docs/source-assets/csv/connects.csv',                       __( 'Connects',        'inc-stats-tracker' ), $csv_stats['connects'] ),
				array( 'docs/source-assets/csv/inc_member_lookup_template.csv',     __( 'Member Lookup',   'inc-stats-tracker' ), $lookup_found ? $member_count : null ),
			);
			foreach ( $file_rows as [ $rel_path, $label, $count ] ) :
				$found = null !== $count;
			?>
				<tr>
					<td>
						<code><?php echo esc_html( $rel_path ); ?></code>
					</td>
					<td>
						<?php if ( $found ) : ?>
							<span style="color:#00a32a;">&#10003; <?php esc_html_e( 'Found', 'inc-stats-tracker' ); ?></span>
						<?php else : ?>
							<span style="color:#d63638;">&#10007; <?php esc_html_e( 'Not found', 'inc-stats-tracker' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $found ? esc_html( number_format( (int) $count ) ) : '—'; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="2"><?php esc_html_e( 'Total data rows', 'inc-stats-tracker' ); ?></th>
				<th><?php echo esc_html( number_format( $total_csv_rows ) ); ?></th>
			</tr>
		</tfoot>
	</table>

	<?php if ( $imported_count > 0 ) : ?>
		<p style="margin-top:12px;">
			<?php
			printf(
				/* translators: %d: number of already-imported rows. */
				esc_html__( '%d row(s) already imported (will be skipped on next run).', 'inc-stats-tracker' ),
				esc_html( number_format( $imported_count ) )
			);
			?>
		</p>
	<?php endif; ?>

	<?php /* ---- Run Import form ----------------------------------------- */ ?>
	<h2><?php esc_html_e( 'Run Import', 'inc-stats-tracker' ); ?></h2>

	<?php if ( ! $all_csvs_found || ! $lookup_found ) : ?>
		<div class="notice notice-error inline">
			<p><?php esc_html_e( 'One or more required files are missing. Please upload all source CSV files before running the import.', 'inc-stats-tracker' ); ?></p>
		</div>
	<?php else : ?>
		<p>
			<?php esc_html_e( 'Clicking "Run Import" will read all three CSV files and insert any rows that have not been imported before. Already-imported rows are silently skipped. The operation is safe to run more than once.', 'inc-stats-tracker' ); ?>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ist_run_historical_import' ); ?>
			<input type="hidden" name="action" value="ist_run_historical_import">
			<?php submit_button( __( 'Run Import', 'inc-stats-tracker' ), 'primary', 'submit', false ); ?>
		</form>
	<?php endif; ?>

	<?php /* ---- Results table ------------------------------------------- */ ?>
	<?php if ( $results ) : ?>
		<h2><?php esc_html_e( 'Last Import Results', 'inc-stats-tracker' ); ?></h2>
		<table class="widefat striped" style="max-width:680px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Imported', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Skipped (dup)', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Warnings', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Errors', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$table_labels = array(
					'tyfcb'     => 'wp_ist_tyfcb',
					'referrals' => 'wp_ist_referrals',
					'connects'  => 'wp_ist_connects',
				);
				foreach ( $table_labels as $key => $label ) :
					$r = $results[ $key ] ?? array();
				?>
					<tr>
						<td><code><?php echo esc_html( $label ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) ( $r['imported'] ?? 0 ) ) ); ?></td>
						<td><?php echo esc_html( number_format( (int) ( $r['skipped']  ?? 0 ) ) ); ?></td>
						<td><?php echo esc_html( number_format( count( $r['warnings'] ?? array() ) ) ); ?></td>
						<td><?php echo esc_html( number_format( count( $r['errors']   ?? array() ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php /* ---- Warnings detail ------------------------------------- */ ?>
		<?php
		$all_warnings = array();
		$all_errors   = array();
		foreach ( array( 'tyfcb', 'referrals', 'connects' ) as $key ) {
			foreach ( $results[ $key ]['warnings'] ?? array() as $w ) {
				$all_warnings[] = $w;
			}
			foreach ( $results[ $key ]['errors'] ?? array() as $e ) {
				$all_errors[] = $e;
			}
		}
		?>

		<?php if ( $all_errors ) : ?>
			<h3 style="color:#d63638;"><?php esc_html_e( 'Errors', 'inc-stats-tracker' ); ?></h3>
			<ul class="ist-import-log ist-import-log--errors">
				<?php foreach ( $all_errors as $msg ) : ?>
					<li><?php echo esc_html( $msg ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $all_warnings ) : ?>
			<details>
				<summary style="cursor:pointer;font-weight:600;margin-top:16px;">
					<?php
					printf(
						/* translators: %d: warning count. */
						esc_html__( 'Warnings (%d) — rows imported with user_id=0 or other issues', 'inc-stats-tracker' ),
						count( $all_warnings )
					);
					?>
				</summary>
				<ul class="ist-import-log" style="max-height:400px;overflow-y:auto;border:1px solid #ddd;padding:12px 12px 12px 28px;margin-top:8px;background:#fafafa;">
					<?php foreach ( $all_warnings as $msg ) : ?>
						<li style="margin-bottom:4px;font-size:13px;"><?php echo esc_html( $msg ); ?></li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endif; ?>

		<?php if ( ! $all_errors && ! $all_warnings ) : ?>
			<p style="color:#00a32a;">
				&#10003; <?php esc_html_e( 'Import completed with no warnings or errors.', 'inc-stats-tracker' ); ?>
			</p>
		<?php endif; ?>

	<?php endif; /* $results */ ?>

	<?php /* ---- Danger zone --------------------------------------------- */ ?>
	<hr style="margin:40px 0 24px;">
	<h2><?php esc_html_e( 'Reset Import History', 'inc-stats-tracker' ); ?></h2>
	<p>
		<?php esc_html_e( 'Clears the record of which rows have been imported. The next "Run Import" will attempt to re-insert every row. Use this only if you need to re-import from scratch after clearing the database tables manually.', 'inc-stats-tracker' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	      onsubmit="return confirm('<?php esc_attr_e( 'This will reset the import history. If you then run the import, ALL rows will be re-inserted. Are you sure?', 'inc-stats-tracker' ); ?>')">
		<?php wp_nonce_field( 'ist_reset_import_hashes' ); ?>
		<input type="hidden" name="action" value="ist_reset_import_hashes">
		<?php submit_button( __( 'Reset Import History', 'inc-stats-tracker' ), 'delete', 'submit', false ); ?>
	</form>

	<?php if ( $legacy_native_count > 0 ) : ?>
	<hr style="margin:40px 0 24px;">
	<h2><?php esc_html_e( 'Mark Legacy Rows as Imported', 'inc-stats-tracker' ); ?></h2>
	<div class="notice notice-warning inline" style="margin:0 0 16px;">
		<p>
			<strong><?php esc_html_e( 'Pre-0.2.26 migration utility — dev/staging only.', 'inc-stats-tracker' ); ?></strong>
			<?php esc_html_e( 'Do not run this on a live installation that already has genuine native member submissions.', 'inc-stats-tracker' ); ?>
		</p>
	</div>
	<p>
		<?php
		printf(
			/* translators: %d: number of rows with data_source='native'. */
			esc_html__( '%d row(s) currently have data_source = "native". This likely means they were imported before version 0.2.26 added source tracking, and received the column default instead of being tagged as imported.', 'inc-stats-tracker' ),
			esc_html( number_format( $legacy_native_count ) )
		);
		?>
	</p>
	<p>
		<?php esc_html_e( 'Clicking the button below will re-tag all of those rows as data_source = "import", making them visible to the Purge Imported Records tool.', 'inc-stats-tracker' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	      onsubmit="return confirm('<?php esc_attr_e( 'This will re-tag all data_source="native" rows as "import". Only do this if ALL existing rows are pre-0.2.26 historical imports with no native member submissions. Continue?', 'inc-stats-tracker' ); ?>')">
		<?php wp_nonce_field( 'ist_mark_legacy_as_imported' ); ?>
		<input type="hidden" name="action" value="ist_mark_legacy_as_imported">
		<?php submit_button( __( 'Mark Legacy Rows as Imported', 'inc-stats-tracker' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php endif; ?>

	<hr style="margin:40px 0 24px;">
	<h2><?php esc_html_e( 'Purge Imported Records', 'inc-stats-tracker' ); ?></h2>
	<p>
		<?php esc_html_e( 'Permanently deletes all database rows that were inserted by the historical importer (data_source = "import") from all three tables, then clears the import history. Native plugin submissions are never affected.', 'inc-stats-tracker' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'Use this when you need to re-import from scratch — for example, after updating the source CSV files or the member lookup. After purging, simply run the import again.', 'inc-stats-tracker' ); ?>
	</p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	      onsubmit="return confirm('<?php esc_attr_e( 'This will permanently delete all imported records. Native plugin submissions will not be affected. Are you sure?', 'inc-stats-tracker' ); ?>')">
		<?php wp_nonce_field( 'ist_purge_imported_records' ); ?>
		<input type="hidden" name="action" value="ist_purge_imported_records">
		<?php submit_button( __( 'Purge Imported Records', 'inc-stats-tracker' ), 'delete', 'submit', false ); ?>
	</form>

</div>
