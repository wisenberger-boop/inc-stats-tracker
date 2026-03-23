<?php
/**
 * Admin template — Referral records list.
 *
 * Available variables:
 *   $records  object[]  Rows from wp_ist_referrals.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-referrals">
	<h1><?php esc_html_e( 'Referral Records', 'inc-stats-tracker' ); ?></h1>

	<?php if ( empty( $records ) ) : ?>
		<p><?php esc_html_e( 'No referral records found.', 'inc-stats-tracker' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:50px"><?php esc_html_e( 'ID', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Referred By', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Referred To', 'inc-stats-tracker' ); ?></th>
					<th style="width:100px"><?php esc_html_e( 'Status', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Referral Date', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $records as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->referred_by_name ); ?></td>
						<td><?php echo esc_html( $row->referred_to_name ); ?></td>
						<td><?php echo esc_html( $row->status ); ?></td>
						<td><?php echo esc_html( $row->entry_date ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
