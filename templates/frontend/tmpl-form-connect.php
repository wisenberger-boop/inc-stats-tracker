<?php
/**
 * Frontend template — Connect submission form.
 *
 * Available variables (passed from IST_Frontend::render_connect_form or IST_Profile_Nav::content_form):
 *   $current_user  WP_User  The logged-in submitting member (system-assigned owner).
 *   $group_members object[] Each object: { ID, display_name, user_email }
 *                           Sourced from the configured BuddyBoss group.
 *   $atts          array    Shortcode attributes.
 *   $my_stats_url  string   (optional) URL to the My Stats summary page, used for
 *                           the "Back to My Stats" link in the success notice.
 *
 * Field semantics:
 *   member_user_id          System-assigned to the current user — not user-editable.
 *                           Included as a hidden field; the server handler always
 *                           overwrites this with get_current_user_id().
 *   connected_with_type     'member' | 'other'. Selects which recipient panel is active.
 *   connected_with_user_id  WP user ID of the other party (when type = member). The
 *                           server resolves connected_with_name from this ID.
 *   connected_with_name     Free-text name (when type = other). Required in other mode.
 *   meet_where              How / where you met. Required.
 *   entry_date              Date of the connect. Used for all reporting.
 *                           Defaults to today; may not be in the future.
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
<div class="ist-form-wrap ist-form-wrap--connect">

	<?php if ( $saved ) : ?>
		<div class="ist-notice ist-notice--success" role="alert">
			<?php esc_html_e( 'Connect saved successfully.', 'inc-stats-tracker' ); ?>
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

	<form class="ist-form ist-form--connect" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ist_submit_connect' ); ?>
		<input type="hidden" name="action" value="ist_submit_connect">

		<?php /* Submitting member — system-assigned to the current user. Not editable. */ ?>
		<input type="hidden" name="member_user_id" value="<?php echo esc_attr( $current_user->ID ); ?>">
		<p class="ist-submitter-info">
			<?php
			printf(
				/* translators: %s: the logged-in member's display name. */
				esc_html__( 'Submitting as: %s', 'inc-stats-tracker' ),
				'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
			);
			?>
		</p>

		<?php /* Connect party — group member or outside contact */ ?>
		<fieldset class="ist-fieldset">
			<legend><?php esc_html_e( 'Met With', 'inc-stats-tracker' ); ?></legend>

			<div class="ist-radio-group">
				<label class="ist-radio-label">
					<input type="radio" name="connected_with_type" value="member"
						class="ist-recipient-toggle" data-panel="ist-con-recipient-member">
					<?php esc_html_e( 'Group Member', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="connected_with_type" value="other"
						class="ist-recipient-toggle" data-panel="ist-con-recipient-other" checked>
					<?php esc_html_e( 'Other', 'inc-stats-tracker' ); ?>
				</label>
			</div>

			<?php /* Panel: select a member from the group */ ?>
			<div id="ist-con-recipient-member" class="ist-recipient-panel" aria-hidden="true">
				<label for="ist-con-with-member"><?php esc_html_e( 'Select Member', 'inc-stats-tracker' ); ?></label>
				<select id="ist-con-with-member" name="connected_with_user_id" disabled>
					<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
					<?php foreach ( $group_members as $member ) : ?>
						<option value="<?php echo esc_attr( $member->ID ); ?>">
							<?php echo esc_html( $member->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php /* Panel: free-text name for anyone outside the group */ ?>
			<div id="ist-con-recipient-other" class="ist-recipient-panel ist-visible">
				<label for="ist-con-with-name"><?php esc_html_e( 'Name', 'inc-stats-tracker' ); ?></label>
				<input type="text" id="ist-con-with-name" name="connected_with_name"
					placeholder="<?php esc_attr_e( 'Name of person you connected with', 'inc-stats-tracker' ); ?>">
			</div>
		</fieldset>

		<p>
			<label for="ist-con-date"><?php esc_html_e( 'Date of Connect', 'inc-stats-tracker' ); ?></label>
			<input type="date" id="ist-con-date" name="entry_date"
				value="<?php echo esc_attr( $today ); ?>"
				max="<?php echo esc_attr( $today ); ?>"
				required>
		</p>

		<?php /* Meet where — the medium of the connection */ ?>
		<fieldset class="ist-fieldset">
			<legend><?php esc_html_e( 'How did you meet?', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></legend>
			<div class="ist-radio-group">
				<label class="ist-radio-label">
					<input type="radio" name="meet_where" value="in-person" required>
					<?php esc_html_e( 'In person', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="meet_where" value="zoom">
					<?php esc_html_e( 'Zoom', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="meet_where" value="telephone">
					<?php esc_html_e( 'Telephone', 'inc-stats-tracker' ); ?>
				</label>
			</div>
		</fieldset>

		<p>
			<label for="ist-con-note"><?php esc_html_e( 'Topic of Conversation (optional)', 'inc-stats-tracker' ); ?></label>
			<textarea id="ist-con-note" name="note" rows="3"
				placeholder="<?php esc_attr_e( 'A general idea of what you discussed', 'inc-stats-tracker' ); ?>"></textarea>
		</p>

		<button type="submit" class="ist-btn ist-btn--primary"><?php esc_html_e( 'Log a Connect', 'inc-stats-tracker' ); ?></button>
	</form>

</div>
