<?php
/**
 * Partial — Closed Business attribution section.
 *
 * Shared by tmpl-profile-my-stats.php and tmpl-group-stats-reports.php.
 * Renders two tiers of attribution reporting for the fiscal year scope:
 *
 *   Tier 1 — Cross-era rollup
 *     Covers ALL records (legacy + enhanced). Three conservative buckets:
 *     Referral-Attributed / Direct / Non-Referral / Unclassified.
 *     Safe to show for any FY period regardless of data vintage.
 *
 *   Tier 2 — Enhanced-only breakdowns (shown only when enhanced records exist)
 *     Three views, each gated individually on non-empty data:
 *       1. Revenue Attribution Source chart — 5-bucket horizontal bar.
 *       2. Revenue Relationship Type chart — 5-bucket horizontal bar.
 *       3. Referral Origin table — Current Member / Former Member / Other
 *          (covers referral-attributed enhanced records only).
 *     Clearly labeled as covering newer enhanced records only.
 *     The entire Tier 2 block is hidden until the first enhanced record exists.
 *
 * Available variables (passed from parent template via ist_get_template):
 *   $rollup_data   array   From IST_Stats_Query::tyfcb_attribution_rollup().
 *   $coverage_data array   From IST_Stats_Query::tyfcb_model_coverage().
 *   $attr_source   array   From IST_Stats_Query::tyfcb_by_attribution_source().
 *   $attr_rel_type array   From IST_Stats_Query::tyfcb_by_relationship_type().
 *   $attr_referrer array   From IST_Stats_Query::tyfcb_by_referrer_type().
 *   $fy_label      string  e.g. "FY 2025–26". Used in section/chart headings.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------------------------------------------------
// Guard: nothing to show if there are no TYFCB records in this FY scope.
// -----------------------------------------------------------------------
$_enhanced_count = (int) ( $coverage_data['enhanced']['count'] ?? 0 );
$_legacy_count   = (int) ( $coverage_data['legacy']['count']   ?? 0 );
$_total_count    = $_enhanced_count + $_legacy_count;

if ( 0 === $_total_count ) {
	return;
}

// -----------------------------------------------------------------------
// Rollup bucket totals — used for the cross-era chips.
// -----------------------------------------------------------------------
$_ra  = $rollup_data['referral_attributed']         ?? array( 'amount' => 0.0, 'count' => 0 );
$_nr  = $rollup_data['non_referral']                ?? array( 'amount' => 0.0, 'count' => 0 );
$_unk = $rollup_data['unknown_legacy_unclassified']  ?? array( 'amount' => 0.0, 'count' => 0 );

// -----------------------------------------------------------------------
// Slug → display label maps.
// These are inline arrays (not functions) so the template is safe to
// include more than once without a PHP fatal on function redeclaration.
// -----------------------------------------------------------------------
$_source_labels = array(
	'current_member_referral'        => __( 'Current Member Referral', 'inc-stats-tracker' ),
	'former_member_referral'         => __( 'Former Member Referral', 'inc-stats-tracker' ),
	'third_party_extended_referral'  => __( 'Third-Party / Tier 3', 'inc-stats-tracker' ),
	'direct_non_referral'            => __( 'Direct / Non-Referral', 'inc-stats-tracker' ),
	'unknown_other'                  => __( 'Unknown / Other', 'inc-stats-tracker' ),
);
$_rel_labels = array(
	'new_project_initial_engagement'    => __( 'New Project', 'inc-stats-tracker' ),
	'recurring_revenue_ongoing_support' => __( 'Recurring / Ongoing', 'inc-stats-tracker' ),
	'expansion_existing_client'         => __( 'Client Expansion', 'inc-stats-tracker' ),
	'repeat_business'                   => __( 'Repeat Business', 'inc-stats-tracker' ),
	'other'                             => __( 'Other', 'inc-stats-tracker' ),
);
$_referrer_labels = array(
	'current_member' => __( 'Current Member', 'inc-stats-tracker' ),
	'former_member'  => __( 'Former Member',  'inc-stats-tracker' ),
	'other'          => __( 'Other',          'inc-stats-tracker' ),
);

// -----------------------------------------------------------------------
// Coverage note wording — honest about mixed data vintage.
// -----------------------------------------------------------------------
if ( $_enhanced_count > 0 && $_legacy_count > 0 ) {
	$_coverage_note = sprintf(
		/* translators: 1: enhanced count, 2: total count */
		__( '%1$d of %2$d closed business records in this period include detailed attribution data. The cross-era summary below covers all records; the detailed charts below that cover enhanced records only.', 'inc-stats-tracker' ),
		$_enhanced_count,
		$_total_count
	);
} elseif ( 0 === $_enhanced_count ) {
	$_coverage_note = sprintf(
		/* translators: %d: total record count */
		__( 'All %d records in this period use legacy attribution. The rollup below gives the best available view across all records. Detailed attribution charts will appear here once records are submitted using the updated form.', 'inc-stats-tracker' ),
		$_total_count
	);
} else {
	// All enhanced — ideal state.
	$_coverage_note = sprintf(
		/* translators: %d: enhanced record count */
		__( 'All %d records in this period include detailed attribution data.', 'inc-stats-tracker' ),
		$_enhanced_count
	);
}
?>

<?php /* ----------------------------------------------------------------
   Section divider
   --------------------------------------------------------------- */ ?>
<div class="ist-section-divider">
	<h3 class="ist-section-divider__label">
		<?php
		printf(
			/* translators: %s: fiscal year label e.g. "FY 2025-26" */
			esc_html__( 'Closed Business Attribution — %s', 'inc-stats-tracker' ),
			esc_html( $fy_label )
		);
		?>
	</h3>
</div>

<?php /* ----------------------------------------------------------------
   Attribution coverage note
   --------------------------------------------------------------- */ ?>
<p class="ist-coverage-note"><?php echo esc_html( $_coverage_note ); ?></p>

<?php /* ----------------------------------------------------------------
   Cross-era rollup chips — all records, conservative buckets.
   Safe to show regardless of data vintage.
   --------------------------------------------------------------- */ ?>
<div class="ist-rollup-chips">

	<div class="ist-rollup-chip ist-rollup-chip--referral">
		<p class="ist-rollup-chip__label"><?php esc_html_e( 'Referral-Attributed', 'inc-stats-tracker' ); ?></p>
		<p class="ist-rollup-chip__amount">$<?php echo esc_html( number_format( (float) $_ra['amount'], 0 ) ); ?></p>
		<p class="ist-rollup-chip__count">
			<?php
			echo esc_html( sprintf(
				/* translators: %d: number of records */
				_n( '%d record', '%d records', (int) $_ra['count'], 'inc-stats-tracker' ),
				(int) $_ra['count']
			) );
			?>
		</p>
	</div>

	<div class="ist-rollup-chip ist-rollup-chip--non-referral">
		<p class="ist-rollup-chip__label"><?php esc_html_e( 'Direct / Non-Referral', 'inc-stats-tracker' ); ?></p>
		<p class="ist-rollup-chip__amount">$<?php echo esc_html( number_format( (float) $_nr['amount'], 0 ) ); ?></p>
		<p class="ist-rollup-chip__count">
			<?php
			echo esc_html( sprintf(
				/* translators: %d: number of records */
				_n( '%d record', '%d records', (int) $_nr['count'], 'inc-stats-tracker' ),
				(int) $_nr['count']
			) );
			?>
		</p>
	</div>

	<div class="ist-rollup-chip ist-rollup-chip--unknown">
		<p class="ist-rollup-chip__label"><?php esc_html_e( 'Unclassified', 'inc-stats-tracker' ); ?></p>
		<p class="ist-rollup-chip__amount">$<?php echo esc_html( number_format( (float) $_unk['amount'], 0 ) ); ?></p>
		<p class="ist-rollup-chip__count">
			<?php
			echo esc_html( sprintf(
				/* translators: %d: number of records */
				_n( '%d record', '%d records', (int) $_unk['count'], 'inc-stats-tracker' ),
				(int) $_unk['count']
			) );
			?>
		</p>
	</div>

</div>

<?php /* ----------------------------------------------------------------
   Enhanced-only attribution breakdowns.
   Shown only when at least one enhanced record exists in this FY scope.
   --------------------------------------------------------------- */ ?>
<?php if ( $_enhanced_count > 0 ) : ?>

	<p class="ist-enhanced-intro">
		<?php
		printf(
			/* translators: 1: enhanced record count, 2: singular/plural "record(s)", 3: enhanced badge HTML */
			esc_html__( 'The breakdowns below are based on %1$d %2$s %3$s submitted with the updated form.', 'inc-stats-tracker' ),
			$_enhanced_count,
			/* translators: label for singular/plural records */
			esc_html( _n( 'record', 'records', $_enhanced_count, 'inc-stats-tracker' ) ),
			'<span class="ist-enhanced-badge">' . esc_html__( 'Enhanced Records Only', 'inc-stats-tracker' ) . '</span>'
		);
		?>
	</p>

	<?php /* Revenue Attribution Source chart */ ?>
	<?php if ( ! empty( $attr_source ) ) : ?>
	<?php
	$_src_labels = array();
	$_src_data   = array();
	foreach ( $attr_source as $_row ) {
		$_src_labels[] = $_source_labels[ $_row['source'] ] ?? $_row['source'];
		$_src_data[]   = (float) $_row['amount'];
	}
	$_src_chart = wp_json_encode( array(
		'labels'   => $_src_labels,
		'datasets' => array( array(
			'label' => __( 'Closed Business Amount', 'inc-stats-tracker' ),
			'data'  => $_src_data,
		) ),
	) );
	$_src_h = max( 120, count( $attr_source ) * 44 + 48 );
	?>
	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				/* translators: %s: fiscal year label */
				esc_html__( 'Revenue Attribution Source — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
			<span class="ist-enhanced-badge"><?php esc_html_e( 'Enhanced Only', 'inc-stats-tracker' ); ?></span>
		</h3>
		<div class="ist-chart-wrap ist-chart-wrap--hbar" style="height:<?php echo esc_attr( $_src_h ); ?>px">
			<canvas class="ist-chart"
				data-chart-type="attribution-horizontal"
				data-chart="<?php echo esc_attr( $_src_chart ); ?>"></canvas>
		</div>
	</section>
	<?php endif; ?>

	<?php /* Revenue Relationship Type chart */ ?>
	<?php if ( ! empty( $attr_rel_type ) ) : ?>
	<?php
	$_rel_label_vals = array();
	$_rel_data       = array();
	foreach ( $attr_rel_type as $_row ) {
		$_rel_label_vals[] = $_rel_labels[ $_row['relationship_type'] ] ?? $_row['relationship_type'];
		$_rel_data[]       = (float) $_row['amount'];
	}
	$_rel_chart = wp_json_encode( array(
		'labels'   => $_rel_label_vals,
		'datasets' => array( array(
			'label' => __( 'Closed Business Amount', 'inc-stats-tracker' ),
			'data'  => $_rel_data,
		) ),
	) );
	$_rel_h = max( 120, count( $attr_rel_type ) * 44 + 48 );
	?>
	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				/* translators: %s: fiscal year label */
				esc_html__( 'Revenue Relationship Type — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
			<span class="ist-enhanced-badge"><?php esc_html_e( 'Enhanced Only', 'inc-stats-tracker' ); ?></span>
		</h3>
		<div class="ist-chart-wrap ist-chart-wrap--hbar" style="height:<?php echo esc_attr( $_rel_h ); ?>px">
			<canvas class="ist-chart"
				data-chart-type="attribution-horizontal"
				data-chart="<?php echo esc_attr( $_rel_chart ); ?>"></canvas>
		</div>
	</section>
	<?php endif; ?>

	<?php /* Referral Origin summary table — referral-attributed enhanced records only */ ?>
	<?php if ( ! empty( $attr_referrer ) ) : ?>
	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				/* translators: %s: fiscal year label */
				esc_html__( 'Referral Origin — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
			<span class="ist-enhanced-badge"><?php esc_html_e( 'Enhanced Only', 'inc-stats-tracker' ); ?></span>
		</h3>
		<table class="ist-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Referrer Type', 'inc-stats-tracker' ); ?></th>
					<th style="text-align:right"><?php esc_html_e( 'Amount', 'inc-stats-tracker' ); ?></th>
					<th style="text-align:right"><?php esc_html_e( 'Records', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $attr_referrer as $_ref_row ) : ?>
				<tr>
					<td><?php echo esc_html( $_referrer_labels[ $_ref_row['referrer_type'] ] ?? $_ref_row['referrer_type'] ); ?></td>
					<td style="text-align:right">$<?php echo esc_html( number_format( (float) $_ref_row['amount'], 0 ) ); ?></td>
					<td style="text-align:right"><?php echo esc_html( (int) $_ref_row['count'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="ist-chart-note"><?php esc_html_e( '* Covers referral-attributed enhanced records only.', 'inc-stats-tracker' ); ?></p>
	</section>
	<?php endif; ?>

<?php endif; // $_enhanced_count > 0 ?>
