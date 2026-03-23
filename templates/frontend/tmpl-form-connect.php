<?php
/**
 * Frontend template — Connect submission form.
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
<form class="ist-form ist-form--connect" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'ist_submit_connect' ); ?>
	<input type="hidden" name="action" value="ist_submit_connect">

	<p>
		<label for="ist-con-member"><?php esc_html_e( 'Member', 'inc-stats-tracker' ); ?></label>
		<select id="ist-con-member" name="member_user_id" required>
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
		<label for="ist-con-with"><?php esc_html_e( 'Met With', 'inc-stats-tracker' ); ?></label>
		<input type="text" id="ist-con-with" name="connected_with_name" required
			placeholder="<?php esc_attr_e( 'Name of person you connected with', 'inc-stats-tracker' ); ?>">
	</p>

	<p>
		<label for="ist-con-date"><?php esc_html_e( 'Date of Connection', 'inc-stats-tracker' ); ?></label>
		<input type="date" id="ist-con-date" name="entry_date"
			value="<?php echo esc_attr( $today ); ?>" required>
	</p>

	<?php /* Meet where — the medium of the connection */ ?>
	<fieldset class="ist-fieldset">
		<legend><?php esc_html_e( 'Where did you meet?', 'inc-stats-tracker' ); ?> <span aria-hidden="true">*</span></legend>
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
		<label for="ist-con-note"><?php esc_html_e( 'Topic of Conversation', 'inc-stats-tracker' ); ?></label>
		<textarea id="ist-con-note" name="note" rows="3"
			placeholder="<?php esc_attr_e( 'A general idea of what you discussed', 'inc-stats-tracker' ); ?>"></textarea>
	</p>

	<button type="submit" class="ist-btn"><?php esc_html_e( 'Submit Connect', 'inc-stats-tracker' ); ?></button>
</form>
