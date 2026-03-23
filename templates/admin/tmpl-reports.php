<?php
/**
 * Admin template — Reports.
 *
 * Available variables:
 *   $summary  array  Aggregated counts/totals.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-reports">
	<h1><?php esc_html_e( 'Reports', 'inc-stats-tracker' ); ?></h1>

	<table class="widefat fixed">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Metric', 'inc-stats-tracker' ); ?></th>
				<th><?php esc_html_e( 'Total', 'inc-stats-tracker' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'TYFCB', 'inc-stats-tracker' ); ?></td>
				<td><?php echo esc_html( $summary['tyfcb_total'] ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Referrals', 'inc-stats-tracker' ); ?></td>
				<td><?php echo esc_html( $summary['referrals_total'] ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Connects', 'inc-stats-tracker' ); ?></td>
				<td><?php echo esc_html( $summary['connects_total'] ); ?></td>
			</tr>
		</tbody>
	</table>
</div>
