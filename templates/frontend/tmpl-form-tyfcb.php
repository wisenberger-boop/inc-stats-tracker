<?php
/**
 * Frontend template — TYFCB submission form.
 *
 * Available variables:
 *   $group_members  object[]  Each object: { ID, display_name, user_email }
 *                             Sourced from the configured BuddyBoss group.
 *   $atts           array     Shortcode attributes.
 *
 * Field semantics:
 *   submitted_by_user_id  The reporting member — who received the closed business.
 *   thank_you_to_type     'member' or 'other'. Explicit; never inferred.
 *   thank_you_to_user_id  WP user ID of the thanked source (when type = member).
 *   thank_you_to_name     Free-text source name (when type = other).
 *   entry_date            Date the business closed. Required for reporting.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$today = wp_date( 'Y-m-d' );
?>
<form class="ist-form ist-form--tyfcb" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'ist_submit_tyfcb' ); ?>
	<input type="hidden" name="action" value="ist_submit_tyfcb">

	<?php /* Reporting member — the person who received the closed business */ ?>
	<p>
		<label for="ist-tyfcb-submitted-by"><?php esc_html_e( 'Reporting Member', 'inc-stats-tracker' ); ?></label>
		<select id="ist-tyfcb-submitted-by" name="submitted_by_user_id" required>
			<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
			<?php foreach ( $group_members as $member ) : ?>
				<option value="<?php echo esc_attr( $member->ID ); ?>"
					<?php selected( $member->ID, get_current_user_id() ); ?>>
					<?php echo esc_html( $member->display_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>

	<?php /* Business source — who is being thanked */ ?>
	<fieldset class="ist-fieldset">
		<legend><?php esc_html_e( 'Business Source', 'inc-stats-tracker' ); ?></legend>

		<div class="ist-radio-group">
			<label class="ist-radio-label">
				<input type="radio" name="thank_you_to_type" value="member"
					class="ist-source-toggle" data-panel="ist-source-member" checked>
				<?php esc_html_e( 'Current Member', 'inc-stats-tracker' ); ?>
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
			value="<?php echo esc_attr( $today ); ?>" required>
	</p>

	<p>
		<label for="ist-tyfcb-note"><?php esc_html_e( 'Note', 'inc-stats-tracker' ); ?></label>
		<textarea id="ist-tyfcb-note" name="note" rows="3"></textarea>
	</p>

	<button type="submit" class="ist-btn"><?php esc_html_e( 'Submit TYFCB', 'inc-stats-tracker' ); ?></button>
</form>
