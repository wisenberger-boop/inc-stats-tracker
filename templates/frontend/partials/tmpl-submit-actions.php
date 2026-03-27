<?php
/**
 * Partial — submit-action link buttons for TYFCB, Referral, and Connect.
 *
 * Available variables:
 *   $form_urls  array  { tyfcb: string, referral: string, connect: string }
 *                      Empty string means the URL is not configured — button is hidden.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$buttons = array(
	array(
		'url'   => $form_urls['tyfcb'] ?? '',
		'label' => __( 'Log Closed Business', 'inc-stats-tracker' ),
		'class' => 'ist-action-tyfcb',
	),
	array(
		'url'   => $form_urls['referral'] ?? '',
		'label' => __( 'Log a Referral', 'inc-stats-tracker' ),
		'class' => 'ist-action-referral',
	),
	array(
		'url'   => $form_urls['connect'] ?? '',
		'label' => __( 'Log a Connect', 'inc-stats-tracker' ),
		'class' => 'ist-action-connect',
	),
);

$visible = array_filter( $buttons, static fn( $b ) => ! empty( $b['url'] ) );

if ( ! $visible ) {
	if ( current_user_can( 'manage_options' ) ) {
		$settings_url = admin_url( 'admin.php?page=ist-settings' );
		printf(
			'<p class="ist-notice ist-notice--error">%s <a href="%s">%s</a></p>',
			esc_html__( 'INC Stats: Form page URLs are not configured.', 'inc-stats-tracker' ),
			esc_url( $settings_url ),
			esc_html__( 'Go to Settings →', 'inc-stats-tracker' )
		);
	}
	return;
}
?>
<div class="ist-submit-actions">
	<?php foreach ( $visible as $btn ) : ?>
		<a href="<?php echo esc_url( $btn['url'] ); ?>"
		   class="ist-submit-btn <?php echo esc_attr( $btn['class'] ); ?>">
			<?php echo esc_html( $btn['label'] ); ?>
		</a>
	<?php endforeach; ?>
</div>
