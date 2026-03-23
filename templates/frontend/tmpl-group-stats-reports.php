<?php
/**
 * Frontend template — "Group Stats Reports" BuddyBoss group tab.
 *
 * Available variables (passed via ist_get_template):
 *   $fy_label             string  e.g. "FY 2025–26"
 *   $month_start          string  Y-m-01 for current month
 *   $tyfcb_month          array   { amount, count }
 *   $tyfcb_fy             array   { amount, count }
 *   $ref_month            array   { count }
 *   $ref_fy               array   { count }
 *   $con_month            array   { count }
 *   $con_fy               array   { count }
 *   $tyfcb_leaderboard    array   Rows from IST_Stats_Query::tyfcb_leaderboard()
 *   $referral_leaderboard array   Rows from IST_Stats_Query::referral_leaderboard()
 *   $connect_leaderboard  array   Rows from IST_Stats_Query::connect_leaderboard()
 *   $form_urls            array   { tyfcb, referral, connect }
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$month_label = wp_date( 'F Y', strtotime( $month_start ) );
?>
<div class="ist-group-stats-reports">

	<h2><?php esc_html_e( 'Group Stats Reports', 'inc-stats-tracker' ); ?></h2>

	<?php /* ----------------------------------------------------------------
	   KPI Summary Table
	   --------------------------------------------------------------- */ ?>
	<div class="ist-kpi-section">
		<table class="ist-table ist-kpi-table">
			<thead>
				<tr>
					<th></th>
					<th><?php echo esc_html( $month_label ); ?></th>
					<th><?php echo esc_html( $fy_label ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
					'label'       => __( 'Closed Business (Amount)', 'inc-stats-tracker' ),
					'month_value' => $tyfcb_month['amount'],
					'fy_value'    => $tyfcb_fy['amount'],
					'format'      => 'currency',
					'fy_label'    => $fy_label,
				) );
				ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
					'label'       => __( 'Closed Business (Count)', 'inc-stats-tracker' ),
					'month_value' => $tyfcb_month['count'],
					'fy_value'    => $tyfcb_fy['count'],
					'format'      => 'count',
					'fy_label'    => $fy_label,
				) );
				ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
					'label'       => __( 'Referrals Given', 'inc-stats-tracker' ),
					'month_value' => $ref_month['count'],
					'fy_value'    => $ref_fy['count'],
					'format'      => 'count',
					'fy_label'    => $fy_label,
				) );
				ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
					'label'       => __( 'Connects Logged', 'inc-stats-tracker' ),
					'month_value' => $con_month['count'],
					'fy_value'    => $con_fy['count'],
					'format'      => 'count',
					'fy_label'    => $fy_label,
				) );
				?>
			</tbody>
		</table>
	</div>

	<?php /* ----------------------------------------------------------------
	   Submit Action Buttons
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-submit-actions.php', array( 'form_urls' => $form_urls ) ); ?>

	<?php /* ----------------------------------------------------------------
	   Leaderboards (FY scope)
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-leaderboard.php', compact(
		'tyfcb_leaderboard', 'referral_leaderboard', 'connect_leaderboard', 'fy_label'
	) ); ?>

</div>
