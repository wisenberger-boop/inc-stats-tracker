<?php
/**
 * Frontend partial — current fiscal-year closed business history with edit forms.
 *
 * Available variables:
 *   $tyfcb_history array
 *   $group_members object[]
 *   $fy_label      string
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$source_labels = array(
	'current_member_referral'       => __( 'Current Member Referral', 'inc-stats-tracker' ),
	'former_member_referral'        => __( 'Former Member Referral', 'inc-stats-tracker' ),
	'third_party_extended_referral' => __( 'Third-Party / Extended Referral', 'inc-stats-tracker' ),
	'direct_non_referral'           => __( 'Direct / Non-Referral', 'inc-stats-tracker' ),
	'unknown_other'                 => __( 'Unknown / Other', 'inc-stats-tracker' ),
);

$relationship_labels = array(
	'new_project_initial_engagement'  => __( 'New Project / Initial Engagement', 'inc-stats-tracker' ),
	'recurring_revenue_ongoing_support' => __( 'Recurring Revenue / Ongoing Support', 'inc-stats-tracker' ),
	'expansion_existing_client'       => __( 'Expansion of Existing Client', 'inc-stats-tracker' ),
	'repeat_business'                 => __( 'Repeat Business', 'inc-stats-tracker' ),
	'other'                           => __( 'Other', 'inc-stats-tracker' ),
);

$lineage_labels = array(
	''                                      => __( 'Not specified', 'inc-stats-tracker' ),
	'direct'                                => __( 'Direct', 'inc-stats-tracker' ),
	'indirect_downstream'                   => __( 'Indirect / Downstream', 'inc-stats-tracker' ),
	'ongoing_revenue_from_earlier_referral' => __( 'Ongoing Revenue from Earlier Referral', 'inc-stats-tracker' ),
	'unknown'                               => __( 'Unknown', 'inc-stats-tracker' ),
);

$referrer_type_labels = array(
	'current_member' => __( 'Current Group Member', 'inc-stats-tracker' ),
	'former_member'  => __( 'Former Group Member', 'inc-stats-tracker' ),
	'other'          => __( 'Other Person / Non-Member', 'inc-stats-tracker' ),
);

$business_type_labels = array(
	'new'    => __( 'New', 'inc-stats-tracker' ),
	'repeat' => __( 'Repeat', 'inc-stats-tracker' ),
);

$referral_type_labels = array(
	'inside'  => __( 'Inside', 'inc-stats-tracker' ),
	'outside' => __( 'Outside', 'inc-stats-tracker' ),
	'tier-3'  => __( 'Tier 3', 'inc-stats-tracker' ),
);
?>

<section class="ist-tyfcb-history">
	<div class="ist-section-divider">
		<h3 class="ist-section-divider__label">
			<?php
			printf(
				/* translators: %s: fiscal year label */
				esc_html__( 'Closed Business Submissions — %s YTD', 'inc-stats-tracker' ),
				esc_html( $fy_label )
			);
			?>
		</h3>
	</div>

	<?php if ( empty( $tyfcb_history ) ) : ?>
		<p class="ist-empty-state"><?php esc_html_e( 'No closed business submissions found for the current fiscal year.', 'inc-stats-tracker' ); ?></p>
	<?php else : ?>
		<div class="ist-history-list">
			<?php foreach ( $tyfcb_history as $record ) : ?>
				<?php
				$record_id         = (int) $record->id;
				$is_enhanced       = 'enhanced' === (string) $record->attribution_model;
				$source_label      = $is_enhanced
					? ( $source_labels[ $record->revenue_attribution_source ] ?? __( 'Not specified', 'inc-stats-tracker' ) )
					: ( $record->thank_you_to_name ?: __( 'Not specified', 'inc-stats-tracker' ) );
				$relationship      = $relationship_labels[ $record->revenue_relationship_type ] ?? __( 'Not specified', 'inc-stats-tracker' );
				$business_type     = $business_type_labels[ $record->business_type ] ?? __( 'Not specified', 'inc-stats-tracker' );
				$referrer_type     = $record->original_referrer_type ?: 'other';
				$referrer_name     = $record->original_referrer_name ?: $record->thank_you_to_name;
				?>
				<details class="ist-history-item">
					<summary class="ist-history-item__summary">
						<span class="ist-history-item__date"><?php echo esc_html( mysql2date( get_option( 'date_format' ), $record->entry_date ) ); ?></span>
						<span class="ist-history-item__source"><?php echo esc_html( $source_label ); ?></span>
						<span class="ist-history-item__payer"><?php echo esc_html( $record->client_payer_name ?: __( '—', 'inc-stats-tracker' ) ); ?></span>
						<span class="ist-history-item__amount"><?php echo esc_html( ist_format_currency( (float) $record->amount ) ); ?></span>
						<span class="ist-history-item__edit"><?php esc_html_e( 'Edit', 'inc-stats-tracker' ); ?></span>
					</summary>

					<form class="ist-history-edit-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'ist_update_tyfcb_' . $record_id ); ?>
						<input type="hidden" name="action" value="ist_update_tyfcb">
						<input type="hidden" name="record_id" value="<?php echo esc_attr( $record_id ); ?>">
						<input type="hidden" name="attribution_model" value="<?php echo esc_attr( $record->attribution_model ?: 'enhanced' ); ?>">

						<div class="ist-history-edit-grid">
							<p>
								<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-date"><?php esc_html_e( 'Business Date', 'inc-stats-tracker' ); ?></label>
								<input type="date" id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-date" name="entry_date" value="<?php echo esc_attr( $record->entry_date ); ?>" max="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" required>
							</p>
							<p>
								<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-amount"><?php esc_html_e( 'Amount', 'inc-stats-tracker' ); ?></label>
								<input type="number" id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-amount" name="amount" value="<?php echo esc_attr( $record->amount ); ?>" min="0.01" step="0.01" required>
							</p>
							<p>
								<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-business-type"><?php esc_html_e( 'Business Type', 'inc-stats-tracker' ); ?></label>
								<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-business-type" name="business_type">
									<option value=""><?php esc_html_e( 'Not specified', 'inc-stats-tracker' ); ?></option>
									<?php foreach ( $business_type_labels as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $record->business_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</p>
							<p>
								<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-client"><?php esc_html_e( 'Client / Payer Name', 'inc-stats-tracker' ); ?></label>
								<input type="text" id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-client" name="client_payer_name" value="<?php echo esc_attr( $record->client_payer_name ); ?>">
							</p>
						</div>

						<?php if ( $is_enhanced ) : ?>
							<div class="ist-history-edit-grid ist-history-edit-grid--wide">
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-source"><?php esc_html_e( 'Revenue Attribution Source', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-source" name="revenue_attribution_source" required>
										<?php foreach ( $source_labels as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $record->revenue_attribution_source, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-relationship"><?php esc_html_e( 'Revenue Relationship Type', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-relationship" name="revenue_relationship_type" required>
										<?php foreach ( $relationship_labels as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $record->revenue_relationship_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-type"><?php esc_html_e( 'Original Referrer Type', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-type" name="original_referrer_type">
										<?php foreach ( $referrer_type_labels as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $referrer_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-member"><?php esc_html_e( 'Current Member Referrer', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-member" name="original_referrer_user_id">
										<option value=""><?php esc_html_e( 'Not selected', 'inc-stats-tracker' ); ?></option>
										<?php foreach ( $group_members as $member ) : ?>
											<option value="<?php echo esc_attr( $member->ID ); ?>" <?php selected( (int) $record->original_referrer_user_id, (int) $member->ID ); ?>>
												<?php echo esc_html( $member->display_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-name"><?php esc_html_e( 'Former / Other Referrer Name', 'inc-stats-tracker' ); ?></label>
									<input type="text" id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-referrer-name" name="original_referrer_name" value="<?php echo esc_attr( $referrer_name ); ?>">
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-lineage"><?php esc_html_e( 'Referral Lineage', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-lineage" name="referral_lineage_type">
										<?php foreach ( $lineage_labels as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $record->referral_lineage_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
							</div>
						<?php else : ?>
							<div class="ist-history-edit-grid">
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-source-type"><?php esc_html_e( 'Source Type', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-source-type" name="thank_you_to_type">
										<option value="member" <?php selected( $record->thank_you_to_type, 'member' ); ?>><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></option>
										<option value="other" <?php selected( $record->thank_you_to_type, 'other' ); ?>><?php esc_html_e( 'Other Source', 'inc-stats-tracker' ); ?></option>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-member"><?php esc_html_e( 'Source Member', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-member" name="thank_you_to_user_id">
										<option value=""><?php esc_html_e( 'Not selected', 'inc-stats-tracker' ); ?></option>
										<?php foreach ( $group_members as $member ) : ?>
											<option value="<?php echo esc_attr( $member->ID ); ?>" <?php selected( (int) $record->thank_you_to_user_id, (int) $member->ID ); ?>>
												<?php echo esc_html( $member->display_name ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-name"><?php esc_html_e( 'Other Source Name', 'inc-stats-tracker' ); ?></label>
									<input type="text" id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-name" name="thank_you_to_name" value="<?php echo esc_attr( $record->thank_you_to_name ); ?>">
								</p>
								<p>
									<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-referral-type"><?php esc_html_e( 'Referral Type', 'inc-stats-tracker' ); ?></label>
									<select id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-legacy-referral-type" name="referral_type">
										<option value=""><?php esc_html_e( 'Not specified', 'inc-stats-tracker' ); ?></option>
										<?php foreach ( $referral_type_labels as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $record->referral_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
							</div>
						<?php endif; ?>

						<p>
							<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-attribution-notes"><?php esc_html_e( 'Attribution Notes', 'inc-stats-tracker' ); ?></label>
							<textarea id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-attribution-notes" name="attribution_notes" rows="2"><?php echo esc_textarea( $record->attribution_notes ); ?></textarea>
						</p>
						<p>
							<label for="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-note"><?php esc_html_e( 'Note', 'inc-stats-tracker' ); ?></label>
							<textarea id="ist-tyfcb-<?php echo esc_attr( $record_id ); ?>-note" name="note" rows="2"><?php echo esc_textarea( $record->note ); ?></textarea>
						</p>
						<div class="ist-history-edit-actions">
							<button type="submit" class="ist-btn ist-btn--primary"><?php esc_html_e( 'Save Changes', 'inc-stats-tracker' ); ?></button>
							<span class="ist-history-item__meta">
								<?php
								printf(
									/* translators: %s: record source such as native/import. */
									esc_html__( 'Source: %s', 'inc-stats-tracker' ),
									esc_html( $record->data_source )
								);
								?>
							</span>
						</div>
					</form>
				</details>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
