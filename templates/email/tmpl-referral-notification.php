<?php
/**
 * Email template — referral handoff notification.
 *
 * Sent to the referral recipient when the handoff method is "Emailed introduction".
 * Rendered via ob_start() in ist_send_referral_notification() and passed to wp_mail().
 *
 * Available variables:
 *   $referred_by_name  string  Display name of the referring INC member.
 *   $referred_by_email string  Email of the referring member (shown in closing).
 *   $referred_to_name  string  Name or business of the recipient.
 *   $referral_type     string  Canonical slug: 'inside' | 'outside' | 'tier-3'.
 *   $note              string  Referral context/details entered by the member.
 *   $entry_date        string  Y-m-d date of the referral event.
 *
 * To customise the introduction paragraph use the filter:
 *   add_filter( 'ist_referral_notification_intro', function( $intro, $args ) {
 *       return 'Your custom intro here.';
 *   }, 10, 2 );
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Human-readable referral type labels.
$type_labels = array(
	'inside'  => __( 'Inside (within the INC group)', 'inc-stats-tracker' ),
	'outside' => __( 'Outside (outside the INC group)', 'inc-stats-tracker' ),
	'tier-3'  => __( 'Tier 3 (referral of a referral)', 'inc-stats-tracker' ),
);
$type_label = $type_labels[ $referral_type ] ?? ucfirst( str_replace( '-', ' ', $referral_type ) );

// Format the date using the site's configured date format.
$formatted_date = $entry_date
	? wp_date( get_option( 'date_format', 'F j, Y' ), strtotime( $entry_date ) )
	: '';

/**
 * Introduction paragraph shown at the top of the email.
 *
 * Filterable so site owners can customise messaging without editing templates.
 * The second argument passes all referral args for conditional logic if needed.
 */
$intro = apply_filters(
	'ist_referral_notification_intro',
	sprintf(
		/* translators: %s: referring member's display name. */
		__( '<strong>%s</strong> is a member of the Inclusive Networking Coalition and has personally sent you a business referral through our network.', 'inc-stats-tracker' ),
		esc_html( $referred_by_name )
	),
	compact( 'referred_by_name', 'referred_by_email', 'referred_to_name', 'referral_type', 'note', 'entry_date' )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php esc_html_e( 'INC Referral Notification', 'inc-stats-tracker' ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f4f6f9;">
  <tr>
    <td align="center" style="padding:32px 16px;">

      <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
             style="max-width:560px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">

        <!-- ── Header bar ──────────────────────────────────── -->
        <tr>
          <td style="background-color:#1e4e8c;padding:20px 32px;">
            <p style="margin:0;font-size:13px;font-weight:700;color:#ffffff;letter-spacing:0.6px;text-transform:uppercase;">
              <?php esc_html_e( 'INC Networking — Business Referral', 'inc-stats-tracker' ); ?>
            </p>
          </td>
        </tr>

        <!-- ── Greeting + intro ────────────────────────────── -->
        <tr>
          <td style="padding:32px 32px 0;">

            <?php if ( $referred_to_name ) : ?>
            <p style="margin:0 0 20px;font-size:16px;line-height:1.5;color:#1a1a1a;">
              <?php
              printf(
                  /* translators: %s: recipient name or business. */
                  esc_html__( 'Hi %s,', 'inc-stats-tracker' ),
                  esc_html( $referred_to_name )
              );
              ?>
            </p>
            <?php endif; ?>

            <p style="margin:0 0 16px;font-size:15px;line-height:1.65;color:#1a1a1a;">
              <?php echo wp_kses( $intro, array( 'strong' => array(), 'em' => array() ) ); ?>
            </p>

            <p style="margin:0;font-size:15px;line-height:1.65;color:#374151;">
              <?php esc_html_e( 'Referrals through INC represent a personal endorsement. Please review the details below and follow up promptly — timely responses strengthen the trust and relationships that make our network valuable for everyone.', 'inc-stats-tracker' ); ?>
            </p>

          </td>
        </tr>

        <!-- ── Divider ─────────────────────────────────────── -->
        <tr>
          <td style="padding:28px 32px 0;">
            <hr style="border:none;border-top:2px solid #e8f0fa;margin:0;">
          </td>
        </tr>

        <!-- ── Referral details block ──────────────────────── -->
        <tr>
          <td style="padding:24px 32px 0;">

            <p style="margin:0 0 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#6b7280;">
              <?php esc_html_e( 'Referral Details', 'inc-stats-tracker' ); ?>
            </p>

            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">

              <?php if ( $formatted_date ) : ?>
              <tr>
                <td style="padding:7px 0;font-size:13px;color:#6b7280;width:120px;vertical-align:top;white-space:nowrap;">
                  <?php esc_html_e( 'Date', 'inc-stats-tracker' ); ?>
                </td>
                <td style="padding:7px 0 7px 16px;font-size:14px;color:#1a1a1a;font-weight:600;vertical-align:top;">
                  <?php echo esc_html( $formatted_date ); ?>
                </td>
              </tr>
              <?php endif; ?>

              <tr>
                <td style="padding:7px 0;font-size:13px;color:#6b7280;width:120px;vertical-align:top;white-space:nowrap;">
                  <?php esc_html_e( 'Referred by', 'inc-stats-tracker' ); ?>
                </td>
                <td style="padding:7px 0 7px 16px;font-size:14px;color:#1a1a1a;font-weight:600;vertical-align:top;">
                  <?php echo esc_html( $referred_by_name ); ?>
                </td>
              </tr>

              <?php if ( $type_label ) : ?>
              <tr>
                <td style="padding:7px 0;font-size:13px;color:#6b7280;width:120px;vertical-align:top;white-space:nowrap;">
                  <?php esc_html_e( 'Referral type', 'inc-stats-tracker' ); ?>
                </td>
                <td style="padding:7px 0 7px 16px;font-size:14px;color:#1a1a1a;font-weight:600;vertical-align:top;">
                  <?php echo esc_html( $type_label ); ?>
                </td>
              </tr>
              <?php endif; ?>

              <?php if ( $note ) : ?>
              <tr>
                <td style="padding:7px 0;font-size:13px;color:#6b7280;width:120px;vertical-align:top;white-space:nowrap;">
                  <?php esc_html_e( 'Details', 'inc-stats-tracker' ); ?>
                </td>
                <td style="padding:7px 0 7px 16px;font-size:14px;color:#1a1a1a;line-height:1.65;vertical-align:top;">
                  <?php echo nl2br( esc_html( $note ) ); ?>
                </td>
              </tr>
              <?php endif; ?>

            </table>
          </td>
        </tr>

        <!-- ── Divider ─────────────────────────────────────── -->
        <tr>
          <td style="padding:24px 32px 0;">
            <hr style="border:none;border-top:1px solid #e2e8f0;margin:0;">
          </td>
        </tr>

        <!-- ── Closing ─────────────────────────────────────── -->
        <tr>
          <td style="padding:24px 32px 32px;">

            <?php if ( $referred_by_email ) : ?>
            <p style="margin:0 0 14px;font-size:14px;line-height:1.65;color:#374151;">
              <?php
              printf(
                  /* translators: 1: referring member name, 2: their email address. */
                  wp_kses(
                      __( 'To connect with %1$s directly, you can reach them at <a href="mailto:%2$s" style="color:#1e4e8c;text-decoration:underline;">%2$s</a>.', 'inc-stats-tracker' ),
                      array( 'a' => array( 'href' => array(), 'style' => array() ) )
                  ),
                  esc_html( $referred_by_name ),
                  esc_html( $referred_by_email )
              );
              ?>
            </p>
            <?php endif; ?>

            <p style="margin:0;font-size:14px;line-height:1.65;color:#374151;">
              <?php esc_html_e( 'Thank you for being part of the INC network. We hope this referral leads to a great connection.', 'inc-stats-tracker' ); ?>
            </p>

          </td>
        </tr>

        <!-- ── Footer ──────────────────────────────────────── -->
        <tr>
          <td style="background-color:#f0f4f9;padding:14px 32px;border-top:1px solid #e2e8f0;">
            <p style="margin:0;font-size:11px;color:#9ca3af;text-align:center;line-height:1.5;">
              <?php esc_html_e( 'This message was sent through the INC Stats Tracker referral system. Please do not reply to this email — use the contact above to reach your referring member directly.', 'inc-stats-tracker' ); ?>
            </p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
