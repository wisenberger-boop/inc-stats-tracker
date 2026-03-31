<?php
/**
 * Partial — single KPI metric card.
 *
 * Renders a self-contained card div. Parent templates wrap these in
 * <div class="ist-kpi-grid"> (2-column grid).
 *
 * Available variables:
 *   $label        string  Human-readable stat label (e.g. "Closed Business (Amount)").
 *   $month_value  mixed   This-month value (float for currency, int for counts).
 *   $fy_value     mixed   This-FY value (same type).
 *   $format       string  'currency' | 'count'  Controls display formatting.
 *   $fy_label     string  Fiscal year label, e.g. "FY 2025–26".
 *   $month_label  string  Month label, e.g. "March 2026". Defaults to "This Month".
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$month_display = ( 'currency' === $format )
	? ist_format_currency( (float) $month_value )
	: number_format( (int) $month_value );

$fy_display = ( 'currency' === $format )
	? ist_format_currency( (float) $fy_value )
	: number_format( (int) $fy_value );

$month_period = ! empty( $month_label ) ? $month_label : __( 'This Month', 'inc-stats-tracker' );
?>
<div class="ist-kpi-card">
	<div class="ist-kpi-card__label"><?php echo esc_html( $label ); ?></div>
	<div class="ist-kpi-card__values">
		<div class="ist-kpi-card__value ist-kpi-card__value--fy">
			<span class="ist-kpi-card__number"><?php echo esc_html( $fy_display ); ?></span>
			<span class="ist-kpi-card__period"><?php echo esc_html( $fy_label ); ?></span>
		</div>
		<div class="ist-kpi-card__value ist-kpi-card__value--month">
			<span class="ist-kpi-card__number"><?php echo esc_html( $month_display ); ?></span>
			<span class="ist-kpi-card__period"><?php echo esc_html( $month_period ); ?></span>
		</div>
	</div>
</div>
