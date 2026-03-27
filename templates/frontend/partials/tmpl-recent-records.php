<?php
/**
 * Partial — recent submissions table (profile / My Stats view only).
 *
 * Available variables:
 *   $tyfcb_recent    array  Recent TYFCB rows from IST_Stats_Query::tyfcb_recent().
 *   $referral_recent array  Recent referral rows from IST_Stats_Query::referral_recent().
 *   $connect_recent  array  Recent connect rows from IST_Stats_Query::connect_recent().
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ist-recent-records">

	<?php /* ----- TYFCB ----- */ ?>
	<h3><?php esc_html_e( 'Recent Closed Business', 'inc-stats-tracker' ); ?></h3>
	<?php if ( $tyfcb_recent ) : ?>
		<table class="ist-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Source', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Type', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tyfcb_recent as $row ) : ?>
					<tr>
						<td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $row->entry_date ) ) ); ?></td>
						<td><?php echo esc_html( $row->thank_you_to_name ); ?></td>
						<td><?php echo esc_html( ist_format_currency( (float) $row->amount ) ); ?></td>
						<td><?php echo esc_html( ucfirst( $row->business_type ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No closed business records yet.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

	<?php /* ----- Referrals ----- */ ?>
	<h3><?php esc_html_e( 'Recent Referrals You Gave', 'inc-stats-tracker' ); ?></h3>
	<?php if ( $referral_recent ) : ?>
		<table class="ist-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Referred To', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Type', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Handoff', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$status_labels = array(
					'emailed'       => __( 'Emailed', 'inc-stats-tracker' ),
					'gave-phone'    => __( 'Gave Phone Number', 'inc-stats-tracker' ),
					'will-initiate' => __( 'Said you would initiate contact', 'inc-stats-tracker' ),
				);
				foreach ( $referral_recent as $row ) :
				?>
					<tr>
						<td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $row->entry_date ) ) ); ?></td>
						<td><?php echo esc_html( $row->referred_to_name ); ?></td>
						<td><?php echo esc_html( ucwords( str_replace( '-', ' ', $row->referral_type ) ) ); ?></td>
						<td><?php echo esc_html( $status_labels[ $row->status ] ?? $row->status ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No referrals recorded yet.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

	<?php /* ----- Connects ----- */ ?>
	<h3><?php esc_html_e( 'Recent Connects', 'inc-stats-tracker' ); ?></h3>
	<?php if ( $connect_recent ) : ?>
		<table class="ist-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Met With', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Where', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$where_labels = array(
					'in-person' => __( 'In Person', 'inc-stats-tracker' ),
					'zoom'      => __( 'Zoom', 'inc-stats-tracker' ),
					'telephone' => __( 'Telephone', 'inc-stats-tracker' ),
				);
				foreach ( $connect_recent as $row ) :
				?>
					<tr>
						<td><?php echo esc_html( wp_date( 'M j, Y', strtotime( $row->entry_date ) ) ); ?></td>
						<td><?php echo esc_html( $row->connected_with_name ); ?></td>
						<td><?php echo esc_html( $where_labels[ $row->meet_where ] ?? $row->meet_where ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No connects recorded yet.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

</div>
