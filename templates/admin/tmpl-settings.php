<?php
/**
 * Admin template — Plugin settings page.
 *
 * Available variables:
 *   $settings     array  Contents of ist_settings option.
 *   $group_id     int    Currently configured BuddyBoss group ID (0 if not set).
 *   $group_config array  Contents of ist_group_config[ $group_id ] (may be empty).
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_fy_month  = (int) ( $group_config['fiscal_year_start_month'] ?? 7 );
$records_per_page  = (int) ( $settings['records_per_page'] ?? 25 );
$form_url_tyfcb    = esc_attr( $settings['form_url_tyfcb']   ?? '' );
$form_url_referral = esc_attr( $settings['form_url_referral'] ?? '' );
$form_url_connect  = esc_attr( $settings['form_url_connect']  ?? '' );
?>
<div class="wrap ist-settings">
	<h1><?php esc_html_e( 'INC Stats — Settings', 'inc-stats-tracker' ); ?></h1>

	<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved.', 'inc-stats-tracker' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ist_save_settings' ); ?>
		<input type="hidden" name="action" value="ist_save_settings">

		<table class="form-table" role="presentation">

			<?php /* --------------------------------------------------------
			   BuddyBoss Group
			   ------------------------------------------------------- */ ?>
			<tr>
				<th scope="row">
					<label for="ist-bb-group-id">
						<?php esc_html_e( 'BuddyBoss Group ID', 'inc-stats-tracker' ); ?>
					</label>
				</th>
				<td>
					<input type="number" id="ist-bb-group-id" name="bb_group_id"
						value="<?php echo esc_attr( $group_id ); ?>"
						min="0" class="small-text">
					<p class="description">
						<?php esc_html_e( 'The BuddyBoss Group whose members are tracked. Set to 0 to disable group membership enforcement.', 'inc-stats-tracker' ); ?>
					</p>
				</td>
			</tr>

			<?php /* --------------------------------------------------------
			   Fiscal Year Start Month
			   ------------------------------------------------------- */ ?>
			<tr>
				<th scope="row">
					<label for="ist-fy-start">
						<?php esc_html_e( 'Fiscal Year Start Month', 'inc-stats-tracker' ); ?>
					</label>
				</th>
				<td>
					<select id="ist-fy-start" name="fiscal_year_start_month">
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>"
								<?php selected( $m, $current_fy_month ); ?>>
								<?php echo esc_html( wp_date( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
							</option>
						<?php endfor; ?>
					</select>
					<p class="description">
						<?php
						// Show the current fiscal year label so the admin can verify the setting.
						$fy_label = IST_Fiscal_Year::get_label( '', $group_id );
						printf(
							/* translators: %s is a fiscal year label such as "FY 2025–26". */
							esc_html__( 'The month in which the fiscal year begins (applies to this group\'s reports). Current fiscal year: %s.', 'inc-stats-tracker' ),
							'<strong>' . esc_html( $fy_label ) . '</strong>'
						);
						?>
					</p>
				</td>
			</tr>

			<?php /* --------------------------------------------------------
			   Records Per Page
			   ------------------------------------------------------- */ ?>
			<tr>
				<th scope="row">
					<label for="ist-records-per-page">
						<?php esc_html_e( 'Records Per Page', 'inc-stats-tracker' ); ?>
					</label>
				</th>
				<td>
					<input type="number" id="ist-records-per-page" name="records_per_page"
						value="<?php echo esc_attr( $records_per_page ); ?>"
						min="1" max="500" class="small-text">
					<p class="description">
						<?php esc_html_e( 'Number of records shown per page in admin list views.', 'inc-stats-tracker' ); ?>
					</p>
				</td>
			</tr>

		<?php /* --------------------------------------------------------
		   Form Page URLs (BuddyBoss submit-action links)
		   ------------------------------------------------------- */ ?>
		<tr>
			<th scope="row">
				<label for="ist-form-url-tyfcb">
					<?php esc_html_e( 'TYFCB Form Page URL', 'inc-stats-tracker' ); ?>
				</label>
			</th>
			<td>
				<input type="url" id="ist-form-url-tyfcb" name="form_url_tyfcb"
					value="<?php echo $form_url_tyfcb; // pre-escaped above ?>"
					class="regular-text">
				<p class="description">
					<?php esc_html_e( 'Full URL of the page containing [ist_tyfcb_form]. Used by the My Stats and Group Stats submit buttons.', 'inc-stats-tracker' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="ist-form-url-referral">
					<?php esc_html_e( 'Referral Form Page URL', 'inc-stats-tracker' ); ?>
				</label>
			</th>
			<td>
				<input type="url" id="ist-form-url-referral" name="form_url_referral"
					value="<?php echo $form_url_referral; // pre-escaped above ?>"
					class="regular-text">
				<p class="description">
					<?php esc_html_e( 'Full URL of the page containing [ist_referral_form].', 'inc-stats-tracker' ); ?>
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="ist-form-url-connect">
					<?php esc_html_e( 'Connect Form Page URL', 'inc-stats-tracker' ); ?>
				</label>
			</th>
			<td>
				<input type="url" id="ist-form-url-connect" name="form_url_connect"
					value="<?php echo $form_url_connect; // pre-escaped above ?>"
					class="regular-text">
				<p class="description">
					<?php esc_html_e( 'Full URL of the page containing [ist_connect_form].', 'inc-stats-tracker' ); ?>
				</p>
			</td>
		</tr>

		</table>

		<?php submit_button( __( 'Save Settings', 'inc-stats-tracker' ) ); ?>
	</form>
</div>
