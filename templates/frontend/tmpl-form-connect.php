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
		<label for="ist-con-with"><?php esc_html_e( 'Connected With', 'inc-stats-tracker' ); ?></label>
		<input type="text" id="ist-con-with" name="connected_with_name" required>
	</p>

	<p>
		<label for="ist-con-type"><?php esc_html_e( 'Connect Type', 'inc-stats-tracker' ); ?></label>
		<select id="ist-con-type" name="connect_type">
			<option value="one-to-one"><?php esc_html_e( 'One-to-One', 'inc-stats-tracker' ); ?></option>
			<option value="group"><?php esc_html_e( 'Group', 'inc-stats-tracker' ); ?></option>
		</select>
	</p>

	<p>
		<label for="ist-con-date"><?php esc_html_e( 'Connect Date', 'inc-stats-tracker' ); ?></label>
		<input type="date" id="ist-con-date" name="entry_date"
			value="<?php echo esc_attr( $today ); ?>" required>
	</p>

	<p>
		<label for="ist-con-note"><?php esc_html_e( 'Note', 'inc-stats-tracker' ); ?></label>
		<textarea id="ist-con-note" name="note" rows="3"></textarea>
	</p>

	<button type="submit" class="ist-btn"><?php esc_html_e( 'Submit Connect', 'inc-stats-tracker' ); ?></button>
</form>
