<?php
/**
 * Frontend template — TYFCB / Closed Business submission form.
 *
 * Available variables (passed from IST_Frontend::render_tyfcb_form or IST_Profile_Nav::content_form):
 *   $current_user  WP_User  The logged-in submitting member (system-assigned owner).
 *   $group_members object[] Each object: { ID, display_name, user_email }
 *                           Sourced from the configured BuddyBoss group.
 *   $atts          array    Shortcode attributes.
 *   $my_stats_url  string   (optional) URL to the My Stats summary page, used for
 *                           the "Back to My Stats" link in the success notice.
 *
 * Field semantics:
 *   submitted_by_user_id  System-assigned to the current user — not user-editable.
 *                         Included as a hidden field for transparency; the server
 *                         handler always overwrites this with get_current_user_id().
 *   thank_you_to_type     'member' or 'other'. Explicit; never inferred.
 *   thank_you_to_user_id  WP user ID of the thanked source (when type = member).
 *   thank_you_to_name     Free-text source name (when type = other).
 *   entry_date            Date the business closed. Required for reporting.
 *                         Defaults to today; may not be in the future.
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
<div class="ist-form-wrap ist-form-wrap--tyfcb">

	<?php if ( $saved ) : ?>
		<div class="ist-notice ist-notice--success" role="alert">
			<?php esc_html_e( 'Closed business record saved successfully.', 'inc-stats-tracker' ); ?>
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

	<form class="ist-form ist-form--tyfcb" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ist_submit_tyfcb' ); ?>
		<input type="hidden" name="action" value="ist_submit_tyfcb">

		<?php /* Submitting member — system-assigned to the current user. Not editable. */ ?>
		<input type="hidden" name="submitted_by_user_id" value="<?php echo esc_attr( $current_user->ID ); ?>">
		<p class="ist-submitter-info">
			<?php
			printf(
				/* translators: %s: the logged-in member's display name. */
				esc_html__( 'Submitting as: %s', 'inc-stats-tracker' ),
				'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
			);
			?>
		</p>

		<?php /* Business source — who is being thanked */ ?>
		<fieldset class="ist-fieldset">
			<legend><?php esc_html_e( 'Business Source', 'inc-stats-tracker' ); ?></legend>

			<div class="ist-radio-group">
				<label class="ist-radio-label">
					<input type="radio" name="thank_you_to_type" value="member"
						class="ist-source-toggle" data-panel="ist-source-member" checked>
					<?php esc_html_e( 'Group Member', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="thank_you_to_type" value="other"
						class="ist-source-toggle" data-panel="ist-source-other">
					<?php esc_html_e( 'Other Source', 'inc-stats-tracker' ); ?>
				</label>
			</div>

			<?php /* Panel: select a member from the group */ ?>
			<div id="ist-source-member" class="ist-source-panel ist-visible">
				<label for="ist-tyfcb-thank-you-to"><?php esc_html_e( 'Select Member', 'inc-stats-tracker' ); ?></label>
				<select id="ist-tyfcb-thank-you-to" name="thank_you_to_user_id">
					<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
					<?php foreach ( $group_members as $member ) : ?>
						<option value="<?php echo esc_attr( $member->ID ); ?>">
							<?php echo esc_html( $member->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php /* Panel: free-text name for any non-member or indirect source */ ?>
			<div id="ist-source-other" class="ist-source-panel" aria-hidden="true">
				<label for="ist-tyfcb-other-name"><?php esc_html_e( 'Source Name', 'inc-stats-tracker' ); ?></label>
				<input type="text" id="ist-tyfcb-other-name" name="thank_you_to_name"
					placeholder="<?php esc_attr_e( 'Name or description of the business source', 'inc-stats-tracker' ); ?>"
					disabled>
			</div>
		</fieldset>

		<p>
			<label for="ist-tyfcb-amount"><?php esc_html_e( 'Closed Business Amount ($)', 'inc-stats-tracker' ); ?></label>
			<input type="number" id="ist-tyfcb-amount" name="amount" step="0.01" min="0.01" required>
		</p>

		<?php /* Business Type — is this new or repeat business? */ ?>
		<fieldset class="ist-fieldset">
			<legend><?php esc_html_e( 'Business Type', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></legend>
			<div class="ist-radio-group">
				<label class="ist-radio-label">
					<input type="radio" name="business_type" value="new" required>
					<?php esc_html_e( 'New', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="business_type" value="repeat">
					<?php esc_html_e( 'Repeat', 'inc-stats-tracker' ); ?>
				</label>
			</div>
		</fieldset>

		<?php /* Referral Type — how did this business originate relative to the group? */ ?>
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
			<label for="ist-tyfcb-date"><?php esc_html_e( 'Business Date', 'inc-stats-tracker' ); ?></label>
			<input type="date" id="ist-tyfcb-date" name="entry_date"
				value="<?php echo esc_attr( $today ); ?>"
				max="<?php echo esc_attr( $today ); ?>"
				required>
		</p>

		<p>
			<label for="ist-tyfcb-note"><?php esc_html_e( 'Note (optional)', 'inc-stats-tracker' ); ?></label>
			<textarea id="ist-tyfcb-note" name="note" rows="3"></textarea>
		</p>

		<button type="submit" class="ist-btn ist-btn--primary"><?php esc_html_e( 'Log Closed Business', 'inc-stats-tracker' ); ?></button>
	</form>

</div>
