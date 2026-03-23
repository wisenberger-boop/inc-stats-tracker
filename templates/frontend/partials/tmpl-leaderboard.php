<?php
/**
 * Partial — leaderboard table (group stats view only).
 *
 * Available variables:
 *   $tyfcb_leaderboard    array  Rows from IST_Stats_Query::tyfcb_leaderboard().
 *                                Each row: { name, user_id, amount, count }
 *   $referral_leaderboard array  Rows from IST_Stats_Query::referral_leaderboard().
 *                                Each row: { user_id, name, count }
 *   $connect_leaderboard  array  Rows from IST_Stats_Query::connect_leaderboard().
 *                                Each row: { user_id, name, count }
 *   $period_label         string Human-readable label for the period (e.g. "FY 2025–26").
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ist-leaderboard">

	<?php /* ----- TYFCB leaderboard ----- */ ?>
	<h3>
		<?php
		printf(
			/* translators: %s: period label such as "FY 2025–26". */
			esc_html__( 'Top Closed Business Sources — %s', 'inc-stats-tracker' ),
			esc_html( $period_label )
		);
		?>
	</h3>
	<p class="ist-leaderboard-note">
		<?php esc_html_e( 'Ranked by total amount credited to each source across all group member submissions.', 'inc-stats-tracker' ); ?>
	</p>
	<?php if ( $tyfcb_leaderboard ) : ?>
		<table class="ist-table ist-leaderboard-table">
			<thead>
				<tr>
					<th class="ist-rank"><?php esc_html_e( '#', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Source', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Total Amount', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Records', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tyfcb_leaderboard as $rank => $entry ) : ?>
					<tr>
						<td class="ist-rank"><?php echo esc_html( $rank + 1 ); ?></td>
						<td><?php echo esc_html( $entry['name'] ); ?></td>
						<td><?php echo esc_html( ist_format_currency( $entry['amount'] ) ); ?></td>
						<td><?php echo esc_html( number_format( $entry['count'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No data for this period.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

	<?php /* ----- Referral leaderboard ----- */ ?>
	<h3>
		<?php
		printf(
			/* translators: %s: period label. */
			esc_html__( 'Top Referral Givers — %s', 'inc-stats-tracker' ),
			esc_html( $period_label )
		);
		?>
	</h3>
	<?php if ( $referral_leaderboard ) : ?>
		<table class="ist-table ist-leaderboard-table">
			<thead>
				<tr>
					<th class="ist-rank"><?php esc_html_e( '#', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Referrals Given', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $referral_leaderboard as $rank => $entry ) : ?>
					<tr>
						<td class="ist-rank"><?php echo esc_html( $rank + 1 ); ?></td>
						<td><?php echo esc_html( $entry['name'] ); ?></td>
						<td><?php echo esc_html( number_format( $entry['count'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No data for this period.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

	<?php /* ----- Connect leaderboard ----- */ ?>
	<h3>
		<?php
		printf(
			/* translators: %s: period label. */
			esc_html__( 'Top Connect Loggers — %s', 'inc-stats-tracker' ),
			esc_html( $period_label )
		);
		?>
	</h3>
	<?php if ( $connect_leaderboard ) : ?>
		<table class="ist-table ist-leaderboard-table">
			<thead>
				<tr>
					<th class="ist-rank"><?php esc_html_e( '#', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></th>
					<th><?php esc_html_e( 'Connects Logged', 'inc-stats-tracker' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $connect_leaderboard as $rank => $entry ) : ?>
					<tr>
						<td class="ist-rank"><?php echo esc_html( $rank + 1 ); ?></td>
						<td><?php echo esc_html( $entry['name'] ); ?></td>
						<td><?php echo esc_html( number_format( $entry['count'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="ist-empty"><?php esc_html_e( 'No data for this period.', 'inc-stats-tracker' ); ?></p>
	<?php endif; ?>

</div>
