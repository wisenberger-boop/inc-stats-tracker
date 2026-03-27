<?php
/**
 * Email notifications for INC Stats Tracker.
 *
 * Contains transactional email helpers. These are called after records are
 * successfully saved — never from the data or service layer directly.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send a referral handoff notification to the referral recipient.
 *
 * Called by IST_Forms::handle_referral() only when the submitting member
 * chose "Emailed introduction" as their handoff method and a valid recipient
 * email address is available.
 *
 * The subject line and intro paragraph are both filterable so site owners
 * can customise the messaging without editing templates:
 *
 *   add_filter( 'ist_referral_notification_subject', fn( $s, $args ) => '...', 10, 2 );
 *   add_filter( 'ist_referral_notification_intro',   fn( $i, $args ) => '...', 10, 2 );
 *
 * @param array $args {
 *   @type string $referred_by_name  Display name of the referring INC member.
 *   @type string $referred_by_email Email of the referring member (used as Reply-To).
 *   @type string $referred_to_name  Name or business receiving the referral.
 *   @type string $referred_to_email Validated recipient email address.
 *   @type string $referral_type     Canonical slug: 'inside' | 'outside' | 'tier-3'.
 *   @type string $note              Referral context/details from the member.
 *   @type string $entry_date        Y-m-d date of the referral event.
 * }
 * @return bool  True if wp_mail accepted the message, false on validation failure.
 */
function ist_send_referral_notification( array $args ): bool {
	$to = sanitize_email( $args['referred_to_email'] ?? '' );
	if ( ! is_email( $to ) ) {
		return false;
	}

	$referred_by_name  = $args['referred_by_name']  ?? '';
	$referred_by_email = $args['referred_by_email'] ?? '';
	$referred_to_name  = $args['referred_to_name']  ?? '';
	$referral_type     = $args['referral_type']     ?? '';
	$note              = $args['note']              ?? '';
	$entry_date        = $args['entry_date']        ?? '';

	$subject = apply_filters(
		'ist_referral_notification_subject',
		sprintf(
			/* translators: %s: referring member's display name. */
			__( 'You have received a referral from %s — INC Networking', 'inc-stats-tracker' ),
			$referred_by_name
		),
		$args
	);

	// Render the HTML body from the email template.
	ob_start();
	ist_get_template( 'email/tmpl-referral-notification.php', compact(
		'referred_by_name',
		'referred_by_email',
		'referred_to_name',
		'referral_type',
		'note',
		'entry_date'
	) );
	$body = ob_get_clean();

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
	);

	// Set Reply-To so recipient can respond directly to the referring member.
	if ( $referred_by_name && $referred_by_email ) {
		$headers[] = 'Reply-To: ' . $referred_by_name . ' <' . $referred_by_email . '>';
	}

	return wp_mail( $to, $subject, $body, $headers );
}
