<?php
/**
 * Frontend template — TYFCB / Closed Business submission form (enhanced attribution model).
 *
 * Available variables (passed from IST_Frontend::render_tyfcb_form or IST_Profile_Nav::content_form):
 *   $current_user  WP_User  The logged-in submitting member (system-assigned owner).
 *   $group_members object[] Each object: { ID, display_name, user_email }
 *                           Sourced from the configured BuddyBoss group.
 *   $atts          array    Shortcode attributes.
 *   $my_stats_url  string   (optional) URL to the My Stats summary page, used for
 *                           the "Back to My Stats" link in the success notice.
 *
 * Attribution model: all new submissions from this form use attribution_model = 'enhanced'.
 * Legacy records (attribution_model = 'legacy') created before this form version remain
 * unchanged. The service layer handles both paths.
 *
 * Field map (enhanced path):
 *   revenue_attribution_source  Required. How the business originated.
 *   original_referrer_type      'member' | 'other'. Required when source is a referral type.
 *   original_referrer_user_id   WP user ID. Required when referrer type = member.
 *   original_referrer_name      Free text. Required when referrer type = other.
 *   referral_lineage_type       Optional. Lineage context for referral-sourced business.
 *   revenue_relationship_type   Required. Nature of the revenue relative to the client.
 *   business_type               Required. 'new' | 'repeat'. Kept for reporting compatibility.
 *   client_payer_name           Optional. Name of the client / payer.
 *   amount                      Required. Dollar value of the closed business.
 *   entry_date                  Required. Date the business closed.
 *   attribution_notes           Optional. Free-text notes on attribution context.
 *   note                        Optional. General note.
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
		<input type="hidden" name="attribution_model" value="enhanced">

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

		<?php /* ----------------------------------------------------------------
		       Revenue Attribution Source — how did this business originate?
		       ---------------------------------------------------------------- */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'Revenue Attribution Source', 'inc-stats-tracker' ); ?>
				<span aria-hidden="true"> *</span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Revenue Attribution Source', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Revenue Attribution Source', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'How did this closed business originate relative to your group?||Current Member Referral — a current group member referred this client or opportunity to you directly.||Former Member Referral — a past group member (no longer active) made the referral.||Third-Party / Extended Referral (Tier 3) — someone outside the group referred you, often through a chain that began with a member connection.||Direct / Non-Referral — you brought in this business independently, with no referral involved.||Unknown / Other — the origin is unclear or does not fit the categories above.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>

			<div class="ist-radio-group ist-radio-group--stacked">
				<label class="ist-radio-label">
					<input type="radio" name="revenue_attribution_source" value="current_member_referral"
						class="ist-attribution-source" data-shows-referrer="1" required>
					<?php esc_html_e( 'Current Member Referral', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_attribution_source" value="former_member_referral"
						class="ist-attribution-source" data-shows-referrer="1">
					<?php esc_html_e( 'Former Member Referral', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_attribution_source" value="third_party_extended_referral"
						class="ist-attribution-source" data-shows-referrer="1">
					<?php esc_html_e( 'Third-Party / Extended Referral (Tier 3)', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_attribution_source" value="direct_non_referral"
						class="ist-attribution-source" data-shows-referrer="0">
					<?php esc_html_e( 'Direct / Non-Referral', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_attribution_source" value="unknown_other"
						class="ist-attribution-source" data-shows-referrer="0">
					<?php esc_html_e( 'Unknown / Other', 'inc-stats-tracker' ); ?>
				</label>
			</div>
		</fieldset>

		<?php /* ----------------------------------------------------------------
		       Original Referrer — who made the referral?
		       Shown only when a referral-type attribution source is selected.
		       ---------------------------------------------------------------- */ ?>
		<div id="ist-tyfcb-referrer-details" class="ist-conditional-section" aria-hidden="true">

			<fieldset class="ist-fieldset">
				<legend>
					<?php esc_html_e( 'Original Referrer', 'inc-stats-tracker' ); ?>
					<span aria-hidden="true"> *</span>
					<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Original Referrer', 'inc-stats-tracker' ); ?>"
						data-help-title="<?php esc_attr_e( 'Original Referrer', 'inc-stats-tracker' ); ?>"
						data-help-body="<?php esc_attr_e( 'Who made the referral that led to this closed business?||Current Group Member — the referrer is active in your group right now. Select them from the member list.||Former Group Member — the referrer was once a member of your group but is no longer active. Enter their name manually.||Other Person / Non-Member — the referrer has no membership connection to the group (e.g. a Tier 3 contact or outside professional). Enter their name manually.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
				</legend>

				<div class="ist-radio-group ist-radio-group--stacked">
					<label class="ist-radio-label">
						<input type="radio" name="original_referrer_type" value="current_member"
							class="ist-referrer-toggle" data-panel="ist-referrer-current" checked>
						<?php esc_html_e( 'Current Group Member', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="original_referrer_type" value="former_member"
							class="ist-referrer-toggle" data-panel="ist-referrer-former">
						<?php esc_html_e( 'Former Group Member', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="original_referrer_type" value="other"
							class="ist-referrer-toggle" data-panel="ist-referrer-other">
						<?php esc_html_e( 'Other Person / Non-Member', 'inc-stats-tracker' ); ?>
					</label>
				</div>

				<?php /* Panel: current group member — select from active group roster */ ?>
				<div id="ist-referrer-current" class="ist-source-panel ist-visible">
					<label for="ist-tyfcb-referrer-current-id"><?php esc_html_e( 'Select Member', 'inc-stats-tracker' ); ?></label>
					<select id="ist-tyfcb-referrer-current-id" name="original_referrer_user_id">
						<option value=""><?php esc_html_e( '— Select Member —', 'inc-stats-tracker' ); ?></option>
						<?php foreach ( $group_members as $member ) : ?>
							<option value="<?php echo esc_attr( $member->ID ); ?>">
								<?php echo esc_html( $member->display_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<?php /* Panel: former group member — free text, name only */ ?>
				<div id="ist-referrer-former" class="ist-source-panel" aria-hidden="true">
					<label for="ist-tyfcb-referrer-former-name"><?php esc_html_e( 'Former Member Name', 'inc-stats-tracker' ); ?></label>
					<input type="text" id="ist-tyfcb-referrer-former-name" name="original_referrer_name"
						placeholder="<?php esc_attr_e( 'Name of the former member who made the referral', 'inc-stats-tracker' ); ?>"
						disabled>
				</div>

				<?php /* Panel: other person — free text */ ?>
				<div id="ist-referrer-other" class="ist-source-panel" aria-hidden="true">
					<label for="ist-tyfcb-referrer-other-name"><?php esc_html_e( 'Their Name or Contact', 'inc-stats-tracker' ); ?></label>
					<input type="text" id="ist-tyfcb-referrer-other-name" name="original_referrer_name"
						placeholder="<?php esc_attr_e( 'Name of the person who made the referral', 'inc-stats-tracker' ); ?>"
						disabled>
				</div>
			</fieldset>

			<?php /* Referral Lineage Type — optional context for how the referral was structured */ ?>
			<fieldset class="ist-fieldset">
				<legend>
					<?php esc_html_e( 'Referral Lineage', 'inc-stats-tracker' ); ?>
					<span class="ist-legend-optional"><?php esc_html_e( '(optional)', 'inc-stats-tracker' ); ?></span>
					<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Referral Lineage', 'inc-stats-tracker' ); ?>"
						data-help-title="<?php esc_attr_e( 'Referral Lineage', 'inc-stats-tracker' ); ?>"
						data-help-body="<?php esc_attr_e( 'How does this closed business relate to the original referral?||Direct — the referral led straight to this engagement with no intermediary steps.||Indirect / Downstream — the business came through a chain of connections that originated with the referral.||Ongoing Revenue from Earlier Referral — this is continued or recurring income from a client originally referred in a prior period.||Unknown — you\'re not sure how the referral chain connects to this revenue.||Leave blank if you\'re not sure or this field doesn\'t apply.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
				</legend>

				<div class="ist-radio-group ist-radio-group--stacked">
					<label class="ist-radio-label">
						<input type="radio" name="referral_lineage_type" value="">
						<?php esc_html_e( '— Not specified —', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="referral_lineage_type" value="direct">
						<?php esc_html_e( 'Direct', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="referral_lineage_type" value="indirect_downstream">
						<?php esc_html_e( 'Indirect / Downstream', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="referral_lineage_type" value="ongoing_revenue_from_earlier_referral">
						<?php esc_html_e( 'Ongoing Revenue from Earlier Referral', 'inc-stats-tracker' ); ?>
					</label>
					<label class="ist-radio-label">
						<input type="radio" name="referral_lineage_type" value="unknown">
						<?php esc_html_e( 'Unknown', 'inc-stats-tracker' ); ?>
					</label>
				</div>
			</fieldset>

		</div><?php /* end #ist-tyfcb-referrer-details */ ?>

		<?php /* ----------------------------------------------------------------
		       Revenue Relationship Type — nature of this revenue
		       ---------------------------------------------------------------- */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'Revenue Relationship Type', 'inc-stats-tracker' ); ?>
				<span aria-hidden="true"> *</span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Revenue Relationship Type', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Revenue Relationship Type', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'What type of revenue engagement does this closed business represent?||New Project / Initial Engagement — first-time work with this client.||Recurring Revenue / Ongoing Support — a retainer, subscription, or continuous service arrangement.||Expansion of Existing Client — additional or upsell work with a client you already serve.||Repeat Business — a returning client placing a new, discrete order or project.||Other — any arrangement that doesn\'t fit the categories above.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>

			<div class="ist-radio-group ist-radio-group--stacked">
				<label class="ist-radio-label">
					<input type="radio" name="revenue_relationship_type" value="new_project_initial_engagement" required>
					<?php esc_html_e( 'New Project / Initial Engagement', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_relationship_type" value="recurring_revenue_ongoing_support">
					<?php esc_html_e( 'Recurring Revenue / Ongoing Support', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_relationship_type" value="expansion_existing_client">
					<?php esc_html_e( 'Expansion of Existing Client', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_relationship_type" value="repeat_business">
					<?php esc_html_e( 'Repeat Business', 'inc-stats-tracker' ); ?>
				</label>
				<label class="ist-radio-label">
					<input type="radio" name="revenue_relationship_type" value="other">
					<?php esc_html_e( 'Other', 'inc-stats-tracker' ); ?>
				</label>
			</div>
		</fieldset>

		<?php /* ----------------------------------------------------------------
		       Business Type — new or repeat (kept for reporting compatibility)
		       ---------------------------------------------------------------- */ ?>
		<fieldset class="ist-fieldset">
			<legend>
				<?php esc_html_e( 'Business Type', 'inc-stats-tracker' ); ?>
				<span aria-hidden="true"> *</span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Business Type', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Business Type', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'Is this new business with a client, or repeat business from an existing client relationship?||New — you have not previously done business with this client (or this is a brand-new engagement category).||Repeat — this client has paid you before and is coming back for more work.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</legend>
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

		<?php /* ----------------------------------------------------------------
		       Amount
		       ---------------------------------------------------------------- */ ?>
		<p>
			<label for="ist-tyfcb-amount"><?php esc_html_e( 'Closed Business Amount ($)', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></label>
			<input type="number" id="ist-tyfcb-amount" name="amount" step="0.01" min="0.01" required>
		</p>

		<?php /* ----------------------------------------------------------------
		       Client / Payer Name — optional
		       ---------------------------------------------------------------- */ ?>
		<p>
			<label for="ist-tyfcb-client-name">
				<?php esc_html_e( 'Client / Payer Name', 'inc-stats-tracker' ); ?>
				<span class="ist-label-optional"><?php esc_html_e( '(optional)', 'inc-stats-tracker' ); ?></span>
			</label>
			<input type="text" id="ist-tyfcb-client-name" name="client_payer_name"
				placeholder="<?php esc_attr_e( 'Name of the business or individual who paid you', 'inc-stats-tracker' ); ?>">
		</p>

		<?php /* ----------------------------------------------------------------
		       Business Date
		       ---------------------------------------------------------------- */ ?>
		<p>
			<label for="ist-tyfcb-date"><?php esc_html_e( 'Business Date', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></label>
			<input type="date" id="ist-tyfcb-date" name="entry_date"
				value="<?php echo esc_attr( $today ); ?>"
				max="<?php echo esc_attr( $today ); ?>"
				required>
		</p>

		<?php /* ----------------------------------------------------------------
		       Attribution Notes — optional
		       ---------------------------------------------------------------- */ ?>
		<p>
			<label for="ist-tyfcb-attribution-notes">
				<?php esc_html_e( 'Attribution Notes', 'inc-stats-tracker' ); ?>
				<span class="ist-label-optional"><?php esc_html_e( '(optional)', 'inc-stats-tracker' ); ?></span>
				<button type="button" class="ist-help-icon" aria-label="<?php esc_attr_e( 'Help: Attribution Notes', 'inc-stats-tracker' ); ?>"
					data-help-title="<?php esc_attr_e( 'Attribution Notes', 'inc-stats-tracker' ); ?>"
					data-help-body="<?php esc_attr_e( 'Use this field to capture any context about the referral chain or attribution that the structured fields above don\'t fully describe. For example: how the referral was made, timing details, or background on the relationship.', 'inc-stats-tracker' ); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none"/><circle cx="8" cy="5.5" r="1" fill="currentColor"/><rect x="7.25" y="7.5" width="1.5" height="4" rx="0.75" fill="currentColor"/></svg></button>
			</label>
			<textarea id="ist-tyfcb-attribution-notes" name="attribution_notes" rows="2"
				placeholder="<?php esc_attr_e( 'Any additional context about how this business was attributed…', 'inc-stats-tracker' ); ?>"></textarea>
		</p>

		<?php /* ----------------------------------------------------------------
		       General Note — optional
		       ---------------------------------------------------------------- */ ?>
		<p>
			<label for="ist-tyfcb-note">
				<?php esc_html_e( 'Note', 'inc-stats-tracker' ); ?>
				<span class="ist-label-optional"><?php esc_html_e( '(optional)', 'inc-stats-tracker' ); ?></span>
			</label>
			<textarea id="ist-tyfcb-note" name="note" rows="3"></textarea>
		</p>

		<button type="submit" class="ist-btn ist-btn--primary"><?php esc_html_e( 'Log Closed Business', 'inc-stats-tracker' ); ?></button>
	</form>

</div>
