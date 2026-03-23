<?php
/**
 * Admin template — Group Roster (read-only).
 *
 * Available variables:
 *   $members  object[]  Each object: { ID, display_name, user_email }
 *                       Sourced from the configured BuddyBoss group via IST_Service_Members.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-members">
	<h1><?php esc_html_e( 'Group Roster', 'inc-stats-tracker' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Members are sourced from the configured BuddyBoss group. To add or remove members, manage them through BuddyBoss Groups.', 'inc-stats-tracker' ); ?>
	</p>

	<?php if ( empty( $members ) ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'No group members found. Configure a BuddyBoss group ID in plugin settings.', 'inc-stats-tracker' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width:80px"><?php esc_html_e( 'User ID', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Display Name', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Email', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $members as $member ) : ?>
					<tr>
						<td><?php echo esc_html( $member->ID ); ?></td>
						<td><?php echo esc_html( $member->display_name ); ?></td>
						<td><?php echo esc_html( $member->user_email ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php
			printf(
				/* translators: %d: number of group members */
				esc_html__( '%d member(s) in group.', 'inc-stats-tracker' ),
				count( $members )
			);
			?>
		</p>
	<?php endif; ?>
</div>
