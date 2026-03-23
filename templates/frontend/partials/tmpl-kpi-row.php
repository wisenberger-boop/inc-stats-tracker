<?php
/**
 * Partial — single KPI row in a stats summary table.
 *
 * Available variables:
 *   $label        string  Human-readable stat label (e.g. "Closed Business").
 *   $month_value  mixed   This-month value (float for currency, int for counts).
 *   $fy_value     mixed   This-FY value (same type).
 *   $format       string  'currency' | 'count'  Controls display formatting.
 *   $fy_label     string  Fiscal year label for the column header (e.g. "FY 2025–26").
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
?>
<tr class="ist-kpi-row">
	<th scope="row" class="ist-kpi-label"><?php echo esc_html( $label ); ?></th>
	<td class="ist-kpi-month"><?php echo esc_html( $month_display ); ?></td>
	<td class="ist-kpi-fy"><?php echo esc_html( $fy_display ); ?></td>
</tr>
