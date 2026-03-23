<?php
/**
 * Frontend template — Referral submission form.
 *
 * Available variables:
 *   $group_members  object[]  Each object: { ID, display_name, user_email }
 *   $atts           array     Shortcode attributes.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$today = wp_date( 'Y-m-d' );
?>
<form class="ist-form ist-form--referral" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'ist_submit_referral' ); ?>
	<input type="hidden" name="action" value="ist_submit_referral">

	<p>
		<label for="ist-ref-by"><?php esc_html_e( 'Referring Member', 'inc-stats-tracker' ); ?></label>
		<select id="ist-ref-by" name="referred_by_user_id" required>
			<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
			<?php foreach ( $group_members as $member ) : ?>
				<option value="<?php echo esc_attr( $member->ID ); ?>"
					<?php selected( $member->ID, get_current_user_id() ); ?>>
					<?php echo esc_html( $member->display_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<p>
		<label for="ist-ref-to"><?php esc_html_e( 'Referred To (Name or Business)', 'inc-stats-tracker' ); ?></label>
		<input type="text" id="ist-ref-to" name="referred_to_name" required>
	</p>

	<p>
		<label for="ist-ref-date"><?php esc_html_e( 'Referral Date', 'inc-stats-tracker' ); ?></label>
		<input type="date" id="ist-ref-date" name="entry_date"
			value="<?php echo esc_attr( $today ); ?>" required>
	</p>

	<?php /* Referral Status — how was the referral passed? */ ?>
	<fieldset class="ist-fieldset">
		<legend><?php esc_html_e( 'Referral Status', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></legend>
		<div class="ist-radio-group ist-radio-group--stack">
			<label class="ist-radio-label">
				<input type="radio" name="status" value="emailed" required>
				<?php esc_html_e( 'Emailed', 'inc-stats-tracker' ); ?>
			</label>
			<label class="ist-radio-label">
				<input type="radio" name="status" value="gave-phone">
				<?php esc_html_e( 'Gave Phone Number', 'inc-stats-tracker' ); ?>
			</label>
			<label class="ist-radio-label">
				<input type="radio" name="status" value="will-initiate">
				<?php esc_html_e( 'Said you would initiate contact', 'inc-stats-tracker' ); ?>
			</label>
		</div>
	</fieldset>

	<?php /* Referral Type — how does this referral relate to the group? */ ?>
	<fieldset class="ist-fieldset">
		<legend><?php esc_html_e( 'Referral Type', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></legend>
		<div class="ist-radio-group">
			<label class="ist-radio-label">
				<input type="radio" name="referral_type" value="inside" required>
				<?php esc_html_e( 'Inside', 'inc-stats-tracker' ); ?>
			</label>
			<label class="ist-radio-label">
				<input type="radio" name="referral_type" value="outside">
				<?php esc_html_e( 'Outside', 'inc-stats-tracker' ); ?>
			</label>
			<label class="ist-radio-label">
				<input type="radio" name="referral_type" value="tier-3">
				<?php esc_html_e( 'Tier 3', 'inc-stats-tracker' ); ?>
			</label>
		</div>
		<p class="ist-field-hint"><?php esc_html_e( 'Inside = our group · Outside = outside our group · Tier 3 = a referral of a referral', 'inc-stats-tracker' ); ?></p>
	</fieldset>

	<p>
		<label for="ist-ref-note"><?php esc_html_e( 'Referral Details', 'inc-stats-tracker' ); ?></label>
		<textarea id="ist-ref-note" name="note" rows="3" required
			placeholder="<?php esc_attr_e( 'Include contact information, description of needs, etc.', 'inc-stats-tracker' ); ?>"></textarea>
	</p>

	<button type="submit" class="ist-btn"><?php esc_html_e( 'Submit Referral', 'inc-stats-tracker' ); ?></button>
</form>
