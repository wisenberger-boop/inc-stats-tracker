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

	<?php if ( ! empty( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<?php if ( '1' === $_GET['deleted'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Record deleted.', 'inc-stats-tracker' ); ?></p>
			</div>
		<?php else : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'Delete failed. The record may not exist or could not be removed.', 'inc-stats-tracker' ); ?></p>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( empty( $records ) ) : ?>
		<p><?php esc_html_e( 'No connect records found.', 'inc-stats-tracker' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:50px"><?php esc_html_e( 'ID', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Connected With', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Where', 'inc-stats-tracker' ); ?></th>
					<th style="width:110px"><?php esc_html_e( 'Connect Date', 'inc-stats-tracker' ); ?></th>
					<th style="width:80px"><?php esc_html_e( 'Source', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $records as $row ) : ?>
					<?php
					$delete_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=ist_delete_connect&id=' . (int) $row->id ),
						'ist_delete_connect_' . (int) $row->id
					);
					$source = ( 'import' === $row->data_source ) ? 'import' : 'native';
					?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td>
							<?php echo esc_html( $row->member_display_name ); ?>
							<div class="row-actions">
								<span class="delete">
									<a href="<?php echo esc_url( $delete_url ); ?>"
									   onclick="return confirm('<?php esc_attr_e( 'Permanently delete this connect record? This cannot be undone.', 'inc-stats-tracker' ); ?>')"
									   style="color:#b32d2e;">
										<?php esc_html_e( 'Delete', 'inc-stats-tracker' ); ?>
									</a>
								</span>
							</div>
						</td>
						<td><?php echo esc_html( $row->connected_with_name ); ?></td>
						<td><?php
							$where_labels = array(
								'in-person' => __( 'In person', 'inc-stats-tracker' ),
								'zoom'      => __( 'Zoom', 'inc-stats-tracker' ),
								'telephone' => __( 'Telephone', 'inc-stats-tracker' ),
							);
							echo esc_html( $where_labels[ $row->meet_where ] ?? ( '' !== $row->meet_where ? $row->meet_where : '—' ) );
						?></td>
						<td><?php echo esc_html( $row->entry_date ); ?></td>
						<td><span class="ist-source-tag ist-source-tag--<?php echo esc_attr( $source ); ?>"><?php echo esc_html( $source ); ?></span></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
