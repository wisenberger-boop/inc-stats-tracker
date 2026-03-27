<?php
/**
 * Frontend template — "Group Stats Reports" BuddyBoss group tab.
 *
 * Available variables (passed via ist_get_template):
 *   $fy_label             string    e.g. "FY 2025–26"
 *   $month_start          string    Y-m-01 for current month
 *   $tyfcb_month          array     { amount, count }
 *   $tyfcb_fy             array     { amount, count }
 *   $ref_month            array     { count }
 *   $ref_fy               array     { count }
 *   $con_month            array     { count }
 *   $con_fy               array     { count }
 *   $tyfcb_leaderboard    array     Rows from IST_Stats_Query::tyfcb_leaderboard()
 *   $referral_leaderboard array     Rows from IST_Stats_Query::referral_leaderboard()
 *   $connect_leaderboard  array     Rows from IST_Stats_Query::connect_leaderboard()
 *   $fy_progress          array     From IST_Fiscal_Year::get_progress() — passed as-is to partial.
 *   $trend_data           array     3-month trend from IST_Stats_Query::three_month_trend().
 *                                   Each item: { label, tyfcb_amount, ref_count, con_count }
 *   $form_urls            array     { tyfcb, referral, connect } — used as link hrefs in the
 *                                   action row and as fallback direct-navigation targets.
 *   $group_members        object[]  Each object: { ID, display_name, user_email }
 *   $current_user         WP_User   The logged-in submitting member.
 *   $my_stats_url         string    URL to the My Stats summary page (for back-links in forms).
 *   $atts                 array     Shortcode attributes (empty array on group tab pages).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$month_label = wp_date( 'F Y', strtotime( $month_start ) );
$atts        = $atts ?? array();
?>
<div class="ist-group-stats-reports">

	<?php /* ----------------------------------------------------------------
	   Page header — title + lightweight action links in the same row.
	   Links use the ist-header-action style (outline, not filled) so they
	   read as secondary controls beside the page title rather than a primary
	   call-to-action row.  When #ist-modal is present the initModals() href
	   interceptor catches these clicks and opens a modal instead of navigating.
	   --------------------------------------------------------------- */ ?>
	<div class="ist-page-header">
		<h2><?php esc_html_e( 'INC Stats Reports', 'inc-stats-tracker' ); ?></h2>
		<?php
		$_header_actions = array(
			array( 'url' => $form_urls['tyfcb']    ?? '', 'label' => __( '+ Log Business',  'inc-stats-tracker' ) ),
			array( 'url' => $form_urls['referral'] ?? '', 'label' => __( '+ Log Referral',  'inc-stats-tracker' ) ),
			array( 'url' => $form_urls['connect']  ?? '', 'label' => __( '+ Log Connect',   'inc-stats-tracker' ) ),
		);
		$_visible_actions = array_filter( $_header_actions, static fn( $a ) => ! empty( $a['url'] ) );
		if ( $_visible_actions ) :
		?>
		<div class="ist-header-actions">
			<?php foreach ( $_visible_actions as $_action ) : ?>
				<a href="<?php echo esc_url( $_action['url'] ); ?>" class="ist-header-action">
					<?php echo esc_html( $_action['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

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
				/* translators: %s: prior fiscal year label */
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
		<h3 class="ist-chart-title"><?php esc_html_e( 'Group Closed Business — 3-Month Trend', 'inc-stats-tracker' ); ?></h3>
		<div class="ist-chart-wrap">
			<canvas class="ist-chart"
				data-chart-type="business-trend"
				data-chart="<?php echo esc_attr( $_biz_chart ); ?>"></canvas>
		</div>
	</section>

	<section class="ist-chart-section">
		<h3 class="ist-chart-title"><?php esc_html_e( 'Group Referrals &amp; Connects — 3-Month Comparison', 'inc-stats-tracker' ); ?></h3>
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
				esc_html__( 'Fiscal Year by Month â %s', 'inc-stats-tracker' ),
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
				esc_html__( 'Group Closed Business by Month â %s', 'inc-stats-tracker' ),
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
				esc_html__( 'Group Referrals &amp; Connects by Month â %s', 'inc-stats-tracker' ),
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
	   Leaderboard charts — Top Referral Givers + Top Connect Loggers
	   Horizontal bar charts using the same data as the leaderboard tables.
	   Only rendered when data exists; tables below remain as fallback.
	   --------------------------------------------------------------- */ ?>
	<?php if ( $referral_leaderboard || $connect_leaderboard ) : ?>

	<?php if ( $referral_leaderboard ) :
		$_ref_slice  = array_slice( $referral_leaderboard, 0, 8 );
		$_ref_chart  = wp_json_encode( array(
			'labels'   => array_column( $_ref_slice, 'name' ),
			'datasets' => array( array(
				'label' => __( 'Referrals Given', 'inc-stats-tracker' ),
				'data'  => array_map( 'intval', array_column( $_ref_slice, 'count' ) ),
			) ),
		) );
		$_ref_h = max( 120, min( count( $_ref_slice ) * 44 + 48, 380 ) );
	?>
	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				/* translators: %s: fiscal year label e.g. "FY 2025-26". */
				esc_html__( 'Top Referral Givers — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
		<div class="ist-chart-wrap ist-chart-wrap--hbar" style="height:<?php echo esc_attr( $_ref_h ); ?>px">
			<canvas class="ist-chart"
				data-chart-type="leaderboard-horizontal"
				data-chart="<?php echo esc_attr( $_ref_chart ); ?>"></canvas>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $connect_leaderboard ) :
		$_con_slice  = array_slice( $connect_leaderboard, 0, 8 );
		$_con_chart  = wp_json_encode( array(
			'labels'   => array_column( $_con_slice, 'name' ),
			'datasets' => array( array(
				'label' => __( 'Connects Logged', 'inc-stats-tracker' ),
				'data'  => array_map( 'intval', array_column( $_con_slice, 'count' ) ),
			) ),
		) );
		$_con_h = max( 120, min( count( $_con_slice ) * 44 + 48, 380 ) );
	?>
	<section class="ist-chart-section">
		<h3 class="ist-chart-title">
			<?php
			printf(
				/* translators: %s: fiscal year label e.g. "FY 2025-26". */
				esc_html__( 'Top Connect Loggers — %s', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
		<div class="ist-chart-wrap ist-chart-wrap--hbar" style="height:<?php echo esc_attr( $_con_h ); ?>px">
			<canvas class="ist-chart"
				data-chart-type="leaderboard-horizontal"
				data-chart="<?php echo esc_attr( $_con_chart ); ?>"></canvas>
		</div>
	</section>
	<?php endif; ?>

	<?php endif; ?>

	<?php /* ----------------------------------------------------------------
	   Leaderboards (FY scope) — detail tables
	   --------------------------------------------------------------- */ ?>
	<?php ist_get_template( 'frontend/partials/tmpl-leaderboard.php', array(
		'tyfcb_leaderboard'    => $tyfcb_leaderboard,
		'referral_leaderboard' => $referral_leaderboard,
		'connect_leaderboard'  => $connect_leaderboard,
		'period_label'         => $fy_label,  // leaderboard partial expects $period_label.
	) ); ?>

</div>

<?php /* ====================================================================
   Hidden form containers — content is moved into the modal on demand.
   Rendered here so they are always available regardless of JS timing.
   The submit-action links above link to the same form URLs; when JS is
   active and #ist-modal is present, the initModals() href interceptor
   catches those link clicks and opens the form in the modal instead.
   When JS is disabled the links navigate to the direct form pages normally.
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
