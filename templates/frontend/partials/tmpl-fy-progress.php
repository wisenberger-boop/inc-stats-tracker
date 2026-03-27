<?php
/**
 * Partial — Fiscal Year Progress module.
 *
 * Contextual dashboard information only. Does NOT affect how stat totals are
 * calculated — all totals remain driven by entry_date in IST_Stats_Query.
 *
 * Available variables (all from IST_Fiscal_Year::get_progress()):
 *   $fy_start        string  Y-m-d start of fiscal year.
 *   $fy_end          string  Y-m-d end of fiscal year.
 *   $fy_label        string  e.g. "FY 2025–26"
 *   $total_days      int     Calendar days in this fiscal year.
 *   $elapsed_days    int     Days elapsed from FY start through today.
 *   $remaining_days  int     Days remaining in the fiscal year.
 *   $percent_elapsed float   Percentage complete (0–100, one decimal).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Format the FY date range for display: "Jul 1, 2025 – Jun 30, 2026"
$range_display = sprintf(
	/* translators: 1: FY start date, 2: FY end date */
	__( '%1$s – %2$s', 'inc-stats-tracker' ),
	wp_date( 'M j, Y', strtotime( $fy_start ) ),
	wp_date( 'M j, Y', strtotime( $fy_end ) )
);
?>
<div class="ist-fy-progress">
	<div class="ist-fy-progress__header">
		<span class="ist-fy-progress__label"><?php echo esc_html( $fy_label ); ?></span>
		<span class="ist-fy-progress__range"><?php echo esc_html( $range_display ); ?></span>
	</div>

	<div class="ist-fy-progress__bar-wrap" title="<?php echo esc_attr( $percent_elapsed ); ?>% complete">
		<div class="ist-fy-progress__bar" style="width: <?php echo esc_attr( min( 100, $percent_elapsed ) ); ?>%"></div>
	</div>

	<div class="ist-fy-progress__meta">
		<span class="ist-fy-progress__pct"><?php echo esc_html( $percent_elapsed ); ?>%</span>
		<span class="ist-fy-progress__days">
			<?php
			printf(
				/* translators: 1: elapsed days, 2: remaining days */
				esc_html__( '%1$s days elapsed · %2$s days remaining', 'inc-stats-tracker' ),
				number_format( $elapsed_days ),
				number_format( $remaining_days )
			);
			?>
		</span>
	</div>
</div>
