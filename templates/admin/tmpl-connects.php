<?php
/**
 * Admin template — Connect records list.
 *
 * Available variables:
 *   $records  object[]  Rows from wp_ist_connects.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-connects">
	<h1><?php esc_html_e( 'Connect Records', 'inc-stats-tracker' ); ?></h1>

	<?php if ( empty( $records ) ) : ?>
		<p><?php esc_html_e( 'No connect records found.', 'inc-stats-tracker' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:50px"><?php esc_html_e( 'ID', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Connected With', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Type', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Connect Date', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $records as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->member_display_name ); ?></td>
						<td><?php echo esc_html( $row->connected_with_name ); ?></td>
						<td><?php echo esc_html( $row->connect_type ); ?></td>
						<td><?php echo esc_html( $row->entry_date ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
