<?php
/**
 * Admin template — Dashboard.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ist-dashboard">
	<h1><?php esc_html_e( 'INC Stats — Dashboard', 'inc-stats-tracker' ); ?></h1>

	<div class="ist-stat-cards">
		<div class="ist-card">
			<h2><?php esc_html_e( 'TYFCB', 'inc-stats-tracker' ); ?></h2>
			<p class="ist-card__count"><?php echo esc_html( number_format( $counts['tyfcb'] ) ); ?></p>
		</div>
		<div class="ist-card">
			<h2><?php esc_html_e( 'Referrals', 'inc-stats-tracker' ); ?></h2>
			<p class="ist-card__count"><?php echo esc_html( number_format( $counts['referrals'] ) ); ?></p>
		</div>
		<div class="ist-card">
			<h2><?php esc_html_e( 'Connects', 'inc-stats-tracker' ); ?></h2>
			<p class="ist-card__count"><?php echo esc_html( number_format( $counts['connects'] ) ); ?></p>
		</div>
	</div>
</div>
