<?php
/**
 * Frontend template — "My Stats" BuddyBoss profile tab.
 *
 * Available variables (passed via ist_get_template):
 *   $fy_label        string    e.g. "FY 2025–26"
 *   $fy_progress     array     From IST_Fiscal_Year::get_progress() — passed as-is to partial.
 *   $month_start     string    Y-m-01 for current month
 *   $tyfcb_month     array     { amount, count }
 *   $tyfcb_fy        array     { amount, count }
 *   $ref_month       array     { count }
 *   $ref_fy          array     { count }
 *   $con_month       array     { count }
 *   $con_fy          array     { count }
 *   $tyfcb_recent    array     Recent TYFCB rows
 *   $referral_recent array     Recent referral rows
 *   $connect_recent  array     Recent connect rows
 *   $trend_data      array     3-month trend data from IST_Stats_Query::three_month_trend().
 *                              Each item: { label, tyfcb_amount, ref_count, con_count }
 *   $tyfcb_rollup      array     From IST_Stats_Query::tyfcb_attribution_rollup() — FY scope.
 *   $tyfcb_coverage    array     From IST_Stats_Query::tyfcb_model_coverage() — FY scope.
 *   $tyfcb_by_source   array     From IST_Stats_Query::tyfcb_by_attribution_source() — FY scope.
 *   $tyfcb_by_rel      array     From IST_Stats_Query::tyfcb_by_relationship_type() — FY scope.
 *   $tyfcb_by_referrer array     From IST_Stats_Query::tyfcb_by_referrer_type() — FY scope.
 *   $form_urls       array     { tyfcb, referral, connect } — used on Group Stats only;
 *                              My Stats uses modal triggers instead.
 *   $group_members   object[]  Each object: { ID, display_name, user_email }
 *   $current_user    WP_User   The logged-in submitting member.
 *   $my_stats_url    string    URL to this summary page (for back-links inside modals).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$month_label = wp_date( 'F Y', strtotime( $month_start ) );
$atts        = array(); // Shortcode atts not applicable on profile nav pages.
?>
<div class="ist-my-stats">

	<h2><?php esc_html_e( 'My Stats', 'inc-stats-tracker' ); ?></h2>

	<?php /* ----------------------------------------------------------------
	   KPI Metric Cards — 2-column grid, FY value primary / month secondary
	   --------------------------------------------------------------- */ ?>
	<div class="ist-kpi-grid">
		<?php
		ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
			'label'       => __( 'Closed Business (Amount)', 'inc-stats-tracker' ),
			'month_value' => $tyfcb_month['amount'],
			'fy_value'    => $tyfcb_fy['amount'],
			'format'      => 'currency',
			'fy_label'    => $fy_label,
			'month_label' => $month_label,
		) );
		ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
			'label'       => __( 'Closed Business (Count)', 'inc-stats-tracker' ),
			'month_value' => $tyfcb_month['count'],
			'fy_value'    => $tyfcb_fy['count'],
			'format'      => 'count',
			'fy_label'    => $fy_label,
			'month_label' => $month_label,
		) );
		ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
			'label'       => __( 'Referrals You Gave', 'inc-stats-tracker' ),
			'month_value' => $ref_month['count'],
			'fy_value'    => $ref_fy['count'],
			'format'      => 'count',
			'fy_label'    => $fy_label,
			'month_label' => $month_label,
		) );
		ist_get_template( 'frontend/partials/tmpl-kpi-row.php', array(
			'label'       => __( 'Connects Logged', 'inc-stats-tracker' ),
			'month_value' => $con_month['count'],
			'fy_value'    => $con_fy['count'],
			'format'      => 'count',
			'fy_label'    => $fy_label,
			'month_label' => $month_label,
		) );
		?>
	</div>

	<?php /* ----------------------------------------------------------------
	   Fiscal Year Progress
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-fy-progress.php', $fy_progress ); ?>


	<?php /* ----------------------------------------------------------------
	   YTD Same-Point Comparison
	   --------------------------------------------------------------- */ ?>
	<?php if ( ! empty( $ytd_data ) ) : ?>
	<div class="ist-section-divider">
		<h3 class="ist-section-divider__label">
			<?php
			printf(
				/* translators: %s: e.g. "FY 2025-26 vs FY 2024-25" */
				esc_html__( 'Year-to-Date vs %s — Same Point', 'inc-stats-tracker' ),
				esc_html( $prior_fy_label )
			);
			?>
		</h3>
	</div>
	<?php ist_get_template( 'frontend/partials/tmpl-ytd-comparison.php', array(
		'ytd_data'         => $ytd_data,
		'current_fy_label' => $fy_label . ' YTD',
		'prior_fy_label'   => $prior_fy_label . ' same point',
	) ); ?>
	<?php endif; ?>
	<?php /* ----------------------------------------------------------------
	   3-Month Charts
	   --------------------------------------------------------------- */ ?>
	<?php if ( ! empty( $trend_data ) ) : ?>

	<?php
	$_biz_chart = wp_json_encode( array(
		'labels'   => array_column( $trend_data, 'label' ),
		'datasets' => array( array( 'data' => array_map( 'floatval', array_column( $trend_data, 'tyfcb_amount' ) ) ) ),
	) );
	$_rc_chart = wp_json_encode( array(
		'labels'   => array_column( $trend_data, 'label' ),
		'datasets' => array(
			array( 'data' => array_map( 'intval', array_column( $trend_data, 'ref_count' ) ) ),
			array( 'data' => array_map( 'intval', array_column( $trend_data, 'con_count' ) ) ),
		),
	) );
	?>

	<section class="ist-chart-section">
		<h3 class="ist-chart-title"><?php esc_html_e( 'Closed Business — 3-Month Trend', 'inc-stats-tracker' ); ?></h3>
		<div class="ist-chart-wrap">
			<canvas class="ist-chart"
				data-chart-type="business-trend"
				data-chart="<?php echo esc_attr( $_biz_chart ); ?>"></canvas>
		</div>
	</section>

	<section class="ist-chart-section">
		<h3 class="ist-chart-title"><?php esc_html_e( 'Referrals &amp; Connects — 3-Month Comparison', 'inc-stats-tracker' ); ?></h3>
		<div class="ist-chart-wrap">
			<canvas class="ist-chart"
				data-chart-type="ref-con-comparison"
				data-chart="<?php echo esc_attr( $_rc_chart ); ?>"></canvas>
		</div>
	</section>

	<p class="ist-chart-note"><?php esc_html_e( '* Current month is month-to-date.', 'inc-stats-tracker' ); ?></p>

	<?php endif; ?>


	<?php /* ----------------------------------------------------------------
	   FY Monthly Charts
	   --------------------------------------------------------------- */ ?>
	<?php if ( ! empty( $fy_monthly_data ) ) : ?>

	<div class="ist-section-divider">
		<h3 class="ist-section-divider__label">
			<?php
			printf(
				/* translators: %s: fiscal year label */
				esc_html__( 'Fiscal Year by Month — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
	</div>

	<?php
	$_fy_biz_chart = wp_json_encode( array(
		'labels'   => array_column( $fy_monthly_data, 'label' ),
		'datasets' => array( array( 'data' => array_map( 'floatval', array_column( $fy_monthly_data, 'tyfcb_amount' ) ) ) ),
	) );
	$_fy_rc_chart = wp_json_encode( array(
		'labels'   => array_column( $fy_monthly_data, 'label' ),
		'datasets' => array(
			array( 'data' => array_map( 'intval', array_column( $fy_monthly_data, 'ref_count' ) ) ),
			array( 'data' => array_map( 'intval', array_column( $fy_monthly_data, 'con_count' ) ) ),
		),
	) );
	?>

	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				esc_html__( 'Closed Business by Month — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
		<div class="ist-chart-wrap">
			<canvas class="ist-chart"
				data-chart-type="business-trend"
				data-chart="<?php echo esc_attr( $_fy_biz_chart ); ?>"></canvas>
		</div>
	</section>

	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				esc_html__( 'Referrals &amp; Connects by Month — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
		<div class="ist-chart-wrap">
			<canvas class="ist-chart"
				data-chart-type="ref-con-comparison"
				data-chart="<?php echo esc_attr( $_fy_rc_chart ); ?>"></canvas>
		</div>
	</section>

	<p class="ist-chart-note"><?php esc_html_e( '* Current month is month-to-date.', 'inc-stats-tracker' ); ?></p>

	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Closed Business Attribution
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-tyfcb-attribution.php', array(
		'rollup_data'   => $tyfcb_rollup,
		'coverage_data' => $tyfcb_coverage,
		'attr_source'   => $tyfcb_by_source,
		'attr_rel_type' => $tyfcb_by_rel,
		'attr_referrer' => $tyfcb_by_referrer,
		'fy_label'      => $fy_label,
	) ); ?>

	<?php /* ----------------------------------------------------------------
	   Recent Records
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-recent-records.php', compact(
		'tyfcb_recent', 'referral_recent', 'connect_recent'
	) ); ?>

</div>

<?php /* ====================================================================
   Hidden form containers — content is moved into the modal on demand.
   Rendered here so they are always available regardless of JS timing.
   ==================================================================== */ ?>

<div id="ist-modal-form-tyfcb" class="ist-modal-form-src" hidden>
	<?php ist_get_template( 'frontend/tmpl-form-tyfcb.php', compact( 'group_members', 'current_user', 'my_stats_url', 'atts' ) ); ?>
</div>

<div id="ist-modal-form-referral" class="ist-modal-form-src" hidden>
	<?php ist_get_template( 'frontend/tmpl-form-referral.php', compact( 'group_members', 'current_user', 'my_stats_url', 'atts' ) ); ?>
</div>

<div id="ist-modal-form-connect" class="ist-modal-form-src" hidden>
	<?php ist_get_template( 'frontend/tmpl-form-connect.php', compact( 'group_members', 'current_user', 'my_stats_url', 'atts' ) ); ?>
</div>

<?php /* ====================================================================
   Modal overlay — shared across all three form types.
   JS populates .ist-modal__body by moving the active form container here.
   ==================================================================== */ ?>

<div id="ist-modal" class="ist-modal" role="dialog" aria-modal="true" hidden>
	<div class="ist-modal__backdrop"></div>
	<div class="ist-modal__panel">
		<button class="ist-modal__close" type="button"
			aria-label="<?php esc_attr_e( 'Close', 'inc-stats-tracker' ); ?>">&#10005;</button>
		<div class="ist-modal__body"></div>
	</div>
</div>
