<?php
/**
 * Frontend template — Referral submission form.
 *
 * Available variables (passed from IST_Frontend::render_referral_form or IST_Profile_Nav::content_form):
 *   $current_user  WP_User  The logged-in submitting member (system-assigned owner).
 *   $group_members object[] Each object: { ID, display_name, user_email }
 *                           Sourced from the configured BuddyBoss group.
 *   $atts          array    Shortcode attributes.
 *   $my_stats_url  string   (optional) URL to the My Stats summary page, used for
 *                           the "Back to My Stats" link in the success notice.
 *
 * Field semantics:
 *   referred_by_user_id  System-assigned to the current user — not user-editable.
 *                        Included as a hidden field; the server handler always
 *                        overwrites this with get_current_user_id().
 *   referred_to_type     'member' | 'other'. Selects which recipient panel is active.
 *   referred_to_user_id  WP user ID of the recipient (when type = member). The
 *                        server resolves referred_to_name and email from this ID.
 *   referred_to_name     Free-text name (when type = other). Required in other mode.
 *   referred_to_email    Recipient email address (when type = other). Used to send
 *                        the referral notification when handoff method is 'emailed'.
 *   referral_type        How the referral relates to the group. Required.
 *   status               How the referral was handed off (handoff method). Required.
 *   entry_date           Date the referral was given. Used for all reporting.
 *                        Defaults to today; may not be in the future.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$today = wp_date( 'Y-m-d' );

// Success / error notices from a previous submission redirect.
$saved = isset( $_GET['ist_saved'] ) && '1' === $_GET['ist_saved']; // phpcs:ignore WordPress.Security.NonceVerification
$error = isset( $_GET['ist_error'] ) ? sanitize_text_field( wp_unslash( $_GET['ist_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="ist-form-wrap ist-form-wrap--referral">

	<?php if ( $saved ) : ?>
		<div class="ist-notice ist-notice--success" role="alert">
			<?php esc_html_e( 'Referral saved successfully.', 'inc-stats-tracker' ); ?>
			<?php if ( ! empty( $my_stats_url ) ) : ?>
				<a class="ist-notice__back" href="<?php echo esc_url( $my_stats_url ); ?>">
					<?php esc_html_e( '← Back to My Stats', 'inc-stats-tracker' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $error ) : ?>
		<div class="ist-notice ist-notice--error" role="alert">
			<?php echo esc_html( urldecode( $error ) ); ?>
		</div>
	<?php endif; ?>

	<form class="ist-form ist-form--referral" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ist_submit_referral' ); ?>
		<input type="hidden" name="action" value="ist_submit_referral">

		<?php /* Submitting member — system-assigned to the current user. Not editable. */ ?>
		<input type="hidden" name="referred_by_user_id" value="<?php echo esc_attr( $current_user->ID ); ?>">
		<p class="ist-submitter-info">
			<?php
			printf(
				/* translators: %s: the logged-in member's display name. */
				esc_html__( 'Submitting as: %s', 'inc-stats-tracker' ),
				'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
			);
			?>
		</p>

		<?php /* Referral recipient — group member or outside contact */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'Referred To', 'inc-stats-tracker' ); ?>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Referred To', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Referred To', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'Who are you referring, and to whom are you sending them?||Group Member — choose this when you are sending a prospect or contact TO a current group member (they will receive the business opportunity).||Other — choose this when you are sending someone to a person or business outside the group, or when you are logging a referral you made to anyone not currently on the group roster.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>

			<div class="ist-radio-group">
				<label class="ist-radio-label">
					<input type="radio" name="referred_to_type" value="member"
						class="ist-recipient-toggle" data-panel="ist-ref-recipient-member" checked>
					<?php esc_html_e( 'Group Member', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="referred_to_type" value="other"
						class="ist-recipient-toggle" data-panel="ist-ref-recipient-other">
					<?php esc_html_e( 'Other', 'inc-stats-tracker' ); ?>
				</label>
			</div>

			<?php /* Panel: select a member from the group */ ?>
			<div id="ist-ref-recipient-member" class="ist-recipient-panel ist-visible">
				<label for="ist-ref-to-member"><?php esc_html_e( 'Select Member', 'inc-stats-tracker' ); ?></label>
				<select id="ist-ref-to-member" name="referred_to_user_id">
					<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
					<?php foreach ( $group_members as $member ) : ?>
						<option value="<?php echo esc_attr( $member->ID ); ?>">
							<?php echo esc_html( $member->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php /* Panel: free-text name and email for any non-member recipient */ ?>
			<div id="ist-ref-recipient-other" class="ist-recipient-panel" aria-hidden="true">
				<label for="ist-ref-to-name"><?php esc_html_e( 'Name or Business', 'inc-stats-tracker' ); ?></label>
				<input type="text" id="ist-ref-to-name" name="referred_to_name"
					placeholder="<?php esc_attr_e( 'Name of the person or business you referred', 'inc-stats-tracker' ); ?>">
				<label for="ist-ref-to-email" style="margin-top:10px;"><?php esc_html_e( 'Email Address (optional)', 'inc-stats-tracker' ); ?></label>
				<input type="email" id="ist-ref-to-email" name="referred_to_email"
					placeholder="<?php esc_attr_e( 'recipient@example.com', 'inc-stats-tracker' ); ?>">
				<p class="ist-field-hint"><?php esc_html_e( 'If you chose "Emailed introduction" below, a referral notification will be sent to this address.', 'inc-stats-tracker' ); ?></p>
			</div>
		</fieldset>

		<p>
			<label for="ist-ref-date">
				<?php esc_html_e( 'Referral Date', 'inc-stats-tracker' ); ?>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Referral Date', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Referral Date', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'The date you made or initiated this referral. Use today\'s date if you\'re logging it right away. If you\'re catching up on a past referral, enter the actual date it happened — this is what appears in reporting.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</label>
			<input type="date" id="ist-ref-date" name="entry_date"
				value="<?php echo esc_attr( $today ); ?>"
				max="<?php echo esc_attr( $today ); ?>"
				required>
		</p>

		<?php /* Referral Status — how was the referral handed off? */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'How did you hand it off?', 'inc-stats-tracker' ); ?>
				<span aria-hidden="true"> *</span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Handoff Method', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Handoff Method', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'How did you connect the two parties?||Emailed introduction — you sent a mutual introduction email. If you enter an email address for the recipient below, a referral notification will be sent automatically.||Gave phone number — you shared the recipient\'s contact information verbally or in writing.||Said you would initiate contact — you told the referral recipient that you or someone else would reach out on their behalf.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>
			<div class="ist-radio-group ist-radio-group--stack">
				<label class="ist-radio-label">
					<input type="radio" name="status" value="emailed" required>
					<?php esc_html_e( 'Emailed introduction', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="status" value="gave-phone">
					<?php esc_html_e( 'Gave phone number', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="status" value="will-initiate">
					<?php esc_html_e( 'Said you would initiate contact', 'inc-stats-tracker' ); ?>
				</label>
			</div>
		</fieldset>

		<?php /* Referral Type — how does this referral relate to the group? */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'Referral Type', 'inc-stats-tracker' ); ?>
				<span aria-hidden="true"> *</span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Referral Type', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Referral Type', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'How does this referral relate to your group?||Inside — you referred a prospect or contact TO a current group member (they receive the business). This is the most common type.||Outside — you referred a group member or member\'s contact TO someone outside the group.||Tier 3 — this referral was passed along through a chain that originated from a prior referral within the group (a referral of a referral).', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>
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
			<label for="ist-ref-note">
				<?php esc_html_e( 'Referral Details', 'inc-stats-tracker' ); ?>
				<span class="ist-label-optional"><?php esc_html_e( '(optional)', 'inc-stats-tracker' ); ?></span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Referral Details', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Referral Details', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'Any context that helps the recipient follow up effectively: what the prospect needs, the best way to reach them, background on the relationship, or how you know them. This note is private to your group and does not appear in public reports.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</label>
			<textarea id="ist-ref-note" name="note" rows="3"
				placeholder="<?php esc_attr_e( 'Contact information, description of need, any relevant context', 'inc-stats-tracker' ); ?>"></textarea>
		</p>

		<button type="submit" class="ist-btn ist-btn--primary"><?php esc_html_e( 'Log a Referral', 'inc-stats-tracker' ); ?></button>
	</form>

</div>
