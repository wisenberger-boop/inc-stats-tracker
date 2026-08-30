<?php
/**
 * Partial — single KPI metric card.
 *
 * Renders a self-contained card div. Parent templates wrap these in
 * <div class="ist-kpi-grid"> (2-column grid).
 *
 * Available variables:
 *   $label        string  Human-readable stat label (e.g. "Closed Business (Amount)").
 *   $month_value  mixed   This-month value (float for currency, int for counts).
 *   $fy_value     mixed   This-FY value (same type).
 *   $format       string  'currency' | 'count'  Controls display formatting.
 *   $fy_label     string  Fiscal year label, e.g. "FY 2025–26 YTD".
 *   $month_label  string  Month label, e.g. "March 2026 (MTD)". Defaults to "This Month".
 *   $prior_value  mixed   Optional. Prior-period comparison value (same type as $fy_value).
 *   $prior_label  string  Optional. Label for the prior value, e.g. "FY 2024–25 YTD".
 *                         When provided, renders a third value row beneath the FY row.
 *   $emphasize_month bool Optional. Give the MTD number the same emphasis as the FY number.
 *   $leaderboard               array  Optional. Member rows with name/count and optional amount.
 *   $leaderboard_id            string Optional. Unique heading ID for aria-labelledby.
 *   $leaderboard_label         string Optional. Heading for the embedded member leaderboard.
 *   $leaderboard_value_type    string Optional. 'amount' (default) or 'count'.
 *   $leaderboard_unit_singular string Optional. Singular count unit. Defaults to "submission".
 *   $leaderboard_unit_plural   string Optional. Plural count unit. Defaults to "submissions".
 *   $leaderboard_empty_message string Optional. Empty-state copy for this metric.
 *   $fy_secondary_value        int    Optional. Count context displayed beside the FY value.
 *   $month_secondary_value     int    Optional. Count context displayed beside the MTD value.
 *   $secondary_unit_singular   string Optional. Singular unit for secondary counts.
 *   $secondary_unit_plural     string Optional. Plural unit for secondary counts.
 *   $full_width                bool   Optional. Span both columns of the KPI grid.
 *   $compact_values            bool   Optional. Arrange FY and MTD values side-by-side on desktop.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$month_display = ( 'currency' === $format )
	? ist_format_currency( (float) $month_value )
	: number_format( (int) $month_value );

$fy_display = ( 'currency' === $format )
	? ist_format_currency( (float) $fy_value )
	: number_format( (int) $fy_value );

$month_period = ! empty( $month_label ) ? $month_label : __( 'This Month', 'inc-stats-tracker' );

$has_prior      = isset( $prior_value ) && isset( $prior_label );
$month_class    = ! empty( $emphasize_month ) ? ' ist-kpi-card__value--month-emphasis' : '';
$has_leaderboard = isset( $leaderboard );
$leaderboard_heading_id = ! empty( $leaderboard_id )
	? sanitize_html_class( $leaderboard_id )
	: wp_unique_id( 'ist-kpi-leaderboard-' );
$leaderboard_value_type = ( $leaderboard_value_type ?? 'amount' ) === 'count' ? 'count' : 'amount';
$leaderboard_unit_singular = $leaderboard_unit_singular ?? __( 'submission', 'inc-stats-tracker' );
$leaderboard_unit_plural = $leaderboard_unit_plural ?? __( 'submissions', 'inc-stats-tracker' );
$leaderboard_empty_message = $leaderboard_empty_message
	?? __( 'No closed business submissions this month.', 'inc-stats-tracker' );
$secondary_unit_singular = $secondary_unit_singular ?? __( 'submission', 'inc-stats-tracker' );
$secondary_unit_plural = $secondary_unit_plural ?? __( 'submissions', 'inc-stats-tracker' );
$format_secondary_count = static function ( $value ) use ( $secondary_unit_singular, $secondary_unit_plural ): string {
	$count = (int) $value;
	return sprintf(
		'%1$s %2$s',
		number_format_i18n( $count ),
		1 === $count ? $secondary_unit_singular : $secondary_unit_plural
	);
};
$fy_secondary_display = isset( $fy_secondary_value )
	? $format_secondary_count( $fy_secondary_value )
	: '';
$month_secondary_display = isset( $month_secondary_value )
	? $format_secondary_count( $month_secondary_value )
	: '';
$card_class = ! empty( $full_width ) ? ' ist-kpi-card--full' : '';
$values_class = ! empty( $compact_values ) ? ' ist-kpi-card__values--inline' : '';
$prior_display   = '';
if ( $has_prior ) {
	$prior_display = ( 'currency' === $format )
		? ist_format_currency( (float) $prior_value )
		: number_format( (int) $prior_value );
}
?>
<div class="ist-kpi-card<?php echo esc_attr( $card_class ); ?>">
	<div class="ist-kpi-card__label"><?php echo esc_html( $label ); ?></div>
	<div class="ist-kpi-card__values<?php echo esc_attr( $values_class ); ?>">
		<div class="ist-kpi-card__value ist-kpi-card__value--fy">
			<div class="ist-kpi-card__figure">
				<span class="ist-kpi-card__number"><?php echo esc_html( $fy_display ); ?></span>
				<?php if ( $fy_secondary_display ) : ?>
					<span class="ist-kpi-card__secondary"><?php echo esc_html( $fy_secondary_display ); ?></span>
				<?php endif; ?>
			</div>
			<span class="ist-kpi-card__period"><?php echo esc_html( $fy_label ); ?></span>
		</div>
		<?php if ( $has_prior ) : ?>
		<div class="ist-kpi-card__value ist-kpi-card__value--prior">
			<span class="ist-kpi-card__number"><?php echo esc_html( $prior_display ); ?></span>
			<span class="ist-kpi-card__period"><?php echo esc_html( $prior_label ); ?></span>
		</div>
		<?php endif; ?>
		<div class="ist-kpi-card__value ist-kpi-card__value--month<?php echo esc_attr( $month_class ); ?>">
			<div class="ist-kpi-card__figure">
				<span class="ist-kpi-card__number"><?php echo esc_html( $month_display ); ?></span>
				<?php if ( $month_secondary_display ) : ?>
					<span class="ist-kpi-card__secondary"><?php echo esc_html( $month_secondary_display ); ?></span>
				<?php endif; ?>
			</div>
			<span class="ist-kpi-card__period"><?php echo esc_html( $month_period ); ?></span>
		</div>
	</div>
	<?php if ( $has_leaderboard ) : ?>
	<section class="ist-kpi-leaderboard" aria-labelledby="<?php echo esc_attr( $leaderboard_heading_id ); ?>">
		<h3 id="<?php echo esc_attr( $leaderboard_heading_id ); ?>" class="ist-kpi-leaderboard__title">
			<?php echo esc_html( $leaderboard_label ?? __( 'Monthly Member Leaders', 'inc-stats-tracker' ) ); ?>
		</h3>
		<?php if ( $leaderboard ) : ?>
		<ol class="ist-kpi-leaderboard__list" role="list">
			<?php $rank = 0; ?>
			<?php foreach ( $leaderboard as $row ) : ?>
			<?php $rank++; ?>
			<li class="ist-kpi-leaderboard__item">
				<span class="ist-kpi-leaderboard__rank" aria-hidden="true"><?php echo esc_html( $rank . '.' ); ?></span>
				<span class="ist-kpi-leaderboard__name"><?php echo esc_html( $row['name'] ); ?></span>
				<span class="ist-kpi-leaderboard__totals">
					<?php
					$count = (int) ( $row['count'] ?? 0 );
					$count_display = sprintf(
						'%1$s %2$s',
						number_format_i18n( $count ),
						1 === $count ? $leaderboard_unit_singular : $leaderboard_unit_plural
					);
					?>
					<?php if ( 'amount' === $leaderboard_value_type ) : ?>
						<strong><?php echo esc_html( ist_format_currency( (float) ( $row['amount'] ?? 0 ) ) ); ?></strong>
						<span><?php echo esc_html( $count_display ); ?></span>
					<?php else : ?>
						<strong><?php echo esc_html( $count_display ); ?></strong>
					<?php endif; ?>
				</span>
			</li>
			<?php endforeach; ?>
		</ol>
		<?php else : ?>
		<p class="ist-kpi-leaderboard__empty">
			<?php echo esc_html( $leaderboard_empty_message ); ?>
		</p>
		<?php endif; ?>
	</section>
	<?php endif; ?>
</div>
