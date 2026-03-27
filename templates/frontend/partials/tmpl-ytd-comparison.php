<?php
/**
 * Partial — Year-to-date same-point comparison cards.
 *
 * Shows current FY YTD totals alongside the equivalent elapsed period from the
 * prior fiscal year. All three stat types are rendered as side-by-side values
 * with an absolute delta and percentage change.
 *
 * Available variables:
 *   $ytd_data         array  From IST_Stats_Query::ytd_comparison(). Keys:
 *                              current: { tyfcb_amount, tyfcb_count, ref_count, con_count }
 *                              prior:   { tyfcb_amount, tyfcb_count, ref_count, con_count }
 *   $current_fy_label string e.g. "FY 2025–26"
 *   $prior_fy_label   string e.g. "FY 2024–25"
 *
 * Rendering note: deltas are computed inline to avoid defining a PHP function
 * in a template (which would fatal on any second include).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$_c = $ytd_data['current'];
$_p = $ytd_data['prior'];

$_items = array(
	array(
		'label'   => __( 'Closed Business', 'inc-stats-tracker' ),
		'current' => $_c['tyfcb_amount'],
		'prior'   => $_p['tyfcb_amount'],
		'format'  => 'currency',
	),
	array(
		'label'   => __( 'Referrals Given', 'inc-stats-tracker' ),
		'current' => (float) $_c['ref_count'],
		'prior'   => (float) $_p['ref_count'],
		'format'  => 'count',
	),
	array(
		'label'   => __( 'Connects Logged', 'inc-stats-tracker' ),
		'current' => (float) $_c['con_count'],
		'prior'   => (float) $_p['con_count'],
		'format'  => 'count',
	),
);
?>
<div class="ist-ytd-grid">
	<?php foreach ( $_items as $_item ) :
		$_diff = $_item['current'] - $_item['prior'];
		$_abs  = abs( $_diff );

		if ( 'currency' === $_item['format'] ) {
			$_current_display = ist_format_currency( (float) $_item['current'] );
			$_prior_display   = ist_format_currency( (float) $_item['prior'] );
			$_diff_display    = ( $_diff >= 0 ? '+' : '-' ) . ist_format_currency( $_abs );
		} else {
			$_current_display = number_format( (int) $_item['current'] );
			$_prior_display   = number_format( (int) $_item['prior'] );
			$_diff_display    = ( $_diff >= 0 ? '+' : '-' ) . number_format( (int) $_abs );
		}

		// Percentage change — only shown when prior period has data.
		$_pct_str = '';
		if ( $_item['prior'] > 0 ) {
			$_pct     = round( ( $_diff / (float) $_item['prior'] ) * 100, 1 );
			$_pct_str = ' (' . ( $_pct >= 0 ? '+' : '' ) . $_pct . '%)';
		}

		$_delta_class = ( $_diff < 0 ) ? 'ist-ytd-card__delta--down' : 'ist-ytd-card__delta--up';
		if ( 0.0 === (float) $_diff ) {
			$_delta_class = 'ist-ytd-card__delta--neutral';
		}
	?>
	<div class="ist-ytd-card">
		<div class="ist-ytd-card__label"><?php echo esc_html( $_item['label'] ); ?></div>
		<div class="ist-ytd-card__row ist-ytd-card__row--current">
			<span class="ist-ytd-card__value"><?php echo esc_html( $_current_display ); ?></span>
			<span class="ist-ytd-card__period"><?php echo esc_html( $current_fy_label ); ?></span>
		</div>
		<div class="ist-ytd-card__row ist-ytd-card__row--prior">
			<span class="ist-ytd-card__value"><?php echo esc_html( $_prior_display ); ?></span>
			<span class="ist-ytd-card__period"><?php echo esc_html( $prior_fy_label ); ?></span>
		</div>
		<div class="ist-ytd-card__delta <?php echo esc_attr( $_delta_class ); ?>">
			<?php echo esc_html( $_diff_display . $_pct_str ); ?>
		</div>
	</div>
	<?php endforeach; ?>
</div>
