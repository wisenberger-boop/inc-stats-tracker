<?php
/**
 * Admin template — TYFCB records list.
 *
 * Available variables:
 *   $records  object[]  Rows from wp_ist_tyfcb.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-tyfcb">
	<h1><?php esc_html_e( 'TYFCB Records', 'inc-stats-tracker' ); ?></h1>

	<?php if ( empty( $records ) ) : ?>
		<p><?php esc_html_e( 'No TYFCB records found.', 'inc-stats-tracker' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:50px"><?php esc_html_e( 'ID', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Reporting Member', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Business Source', 'inc-stats-tracker' ); ?></th>
					<th style="width:120px"><?php esc_html_e( 'Amount', 'inc-stats-tracker' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Biz Type', 'inc-stats-tracker' ); ?></th>
					<th style="width:90px"><?php esc_html_e( 'Ref Type', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Business Date', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $records as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->submitted_by_name ); ?></td>
						<td>
							<?php echo esc_html( $row->thank_you_to_name ); ?>
							<?php if ( 'other' === $row->thank_you_to_type ) : ?>
								<span class="ist-tag ist-tag--other"><?php esc_html_e( 'Other', 'inc-stats-tracker' ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( ist_format_currency( (float) $row->amount ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $row->business_type ) ); ?></td>
						<td><?php echo esc_html( '' !== $row->referral_type ? ucwords( str_replace( '-', ' ', $row->referral_type ) ) : '—' ); ?></td>
						<td><?php echo esc_html( $row->entry_date ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
