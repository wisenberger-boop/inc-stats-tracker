<?php
/**
 * Handles plugin activation.
 *
 * Creates database tables and sets default options.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_defaults();
		flush_rewrite_rules();
	}

	/**
	 * Run on admin_init to apply DB schema changes after a plugin update.
	 *
	 * dbDelta() adds new columns but never drops or renames them, so this is
	 * safe to run on every admin load — the version guard keeps it cheap.
	 */
	public static function maybe_upgrade(): void {
		if ( get_option( 'ist_db_version' ) !== IST_VERSION ) {
			self::create_tables();
			update_option( 'ist_db_version', IST_VERSION );
		}
	}

	/**
	 * Create custom DB tables via dbDelta.
	 *
	 * Column naming conventions:
	 *  - *_user_id columns reference wp_users.ID directly (no plugin member table).
	 *  - *_name columns store a display-name snapshot written at insert time.
	 *  - entry_date  : user-supplied date of the business/reporting event. Used for reporting.
	 *  - created_at  : auto-set MySQL insert timestamp. Audit/sort use only.
	 *  - updated_at  : auto-updated MySQL timestamp on any row change. NULL until first edit.
	 *  - created_by_user_id : WP user who physically entered the record.
	 *
	 * Controlled vocabularies (enforced by services, not DB constraints):
	 *  - business_type  : 'new' | 'repeat'
	 *  - referral_type  : 'inside' | 'outside' | 'tier-3'
	 *  - status (referrals, handoff method) : 'emailed' | 'gave-phone' | 'will-initiate'
	 *  - meet_where     : 'in-person' | 'zoom' | 'telephone'
	 *
	 * Migration note: dbDelta adds new columns to existing tables but does NOT drop or
	 * rename columns. If upgrading from a prior install that has connect_type, that column
	 * will remain inert until a future ALTER TABLE migration removes it.
	 */
	private static function create_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		// -----------------------------------------------------------------------
		// TYFCB (Thank You for Closed Business) records.
		//
		// submitted_by_user_id : WP user ID of the member reporting the closed business.
		// submitted_by_name    : Snapshot of that member's display name at insert time.
		// thank_you_to_type    : 'member' when the source is a resolvable WP user;
		//                        'other' when the source cannot be tied to a WP user ID.
		// thank_you_to_user_id : WP user ID of the thanked source. NULL when type = 'other'.
		// thank_you_to_name    : Always populated. Snapshot when type = 'member';
		//                        free-text source name when type = 'other'.
		// business_type        : 'new' | 'repeat'. Empty string permitted for historical import.
		// referral_type        : 'inside' | 'outside' | 'tier-3'. Empty string for historical import.
		// -----------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}ist_tyfcb (
			id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			submitted_by_user_id BIGINT(20) UNSIGNED NOT NULL,
			submitted_by_name    VARCHAR(255) NOT NULL DEFAULT '',
			thank_you_to_type    VARCHAR(20) NOT NULL DEFAULT 'member',
			thank_you_to_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			thank_you_to_name    VARCHAR(255) NOT NULL DEFAULT '',
			amount               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
			business_type        VARCHAR(20) NOT NULL DEFAULT '',
			referral_type        VARCHAR(20) NOT NULL DEFAULT '',
			note                 TEXT,
			entry_date           DATE NOT NULL,
			created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			created_by_user_id   BIGINT(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY submitted_by_user_id (submitted_by_user_id),
			KEY thank_you_to_user_id (thank_you_to_user_id),
			KEY entry_date (entry_date)
		) $charset;";

		// -----------------------------------------------------------------------
		// Referral records.
		//
		// referred_by_user_id : WP user ID of the group member who gave the referral.
		// referred_by_name    : Snapshot of that member's display name at insert time.
		// referred_to_name    : Always populated. Name of the person or business
		//                       receiving the referral.
		// referred_to_user_id : Nullable. WP user ID if the recipient is a group member.
		// referral_type       : 'inside' | 'outside' | 'tier-3'.
		// status              : Handoff method — 'emailed' | 'gave-phone' | 'will-initiate'.
		//                       NOT a lifecycle status. Empty string permitted for historical import.
		// -----------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}ist_referrals (
			id                  BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			referred_by_user_id BIGINT(20) UNSIGNED NOT NULL,
			referred_by_name    VARCHAR(255) NOT NULL DEFAULT '',
			referred_to_name    VARCHAR(255) NOT NULL DEFAULT '',
			referred_to_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			referral_type       VARCHAR(20) NOT NULL DEFAULT '',
			status              VARCHAR(50) NOT NULL DEFAULT '',
			note                TEXT,
			entry_date          DATE NOT NULL,
			created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at          DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			created_by_user_id  BIGINT(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY referred_by_user_id (referred_by_user_id),
			KEY entry_date (entry_date)
		) $charset;";

		// -----------------------------------------------------------------------
		// Connect records.
		//
		// member_user_id         : WP user ID of the group member logging the connect.
		// member_display_name    : Snapshot of that member's display name at insert time.
		// connected_with_name    : Always populated. Name of the other party.
		// connected_with_user_id : Nullable. WP user ID if the other party is a group member.
		// meet_where             : Meeting medium — 'in-person' | 'zoom' | 'telephone'.
		// -----------------------------------------------------------------------
		$sql[] = "CREATE TABLE {$wpdb->prefix}ist_connects (
			id                     BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			member_user_id         BIGINT(20) UNSIGNED NOT NULL,
			member_display_name    VARCHAR(255) NOT NULL DEFAULT '',
			connected_with_name    VARCHAR(255) NOT NULL DEFAULT '',
			connected_with_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			meet_where             VARCHAR(50) NOT NULL DEFAULT '',
			note                   TEXT,
			entry_date             DATE NOT NULL,
			created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at             DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			created_by_user_id     BIGINT(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY member_user_id (member_user_id),
			KEY entry_date (entry_date)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'ist_db_version', IST_VERSION );
	}

	/**
	 * Set default plugin options.
	 *
	 * add_option() is a no-op when the option already exists, so these calls
	 * are safe to run on re-activation without overwriting saved settings.
	 */
	private static function set_defaults(): void {
		add_option( 'ist_settings', array(
			'date_format'      => 'Y-m-d',
			'records_per_page' => 25,
			'bb_group_id'      => 0,
		) );

		// Per-group configuration. Keyed by BuddyBoss group ID.
		// Starts empty; the Settings screen writes the first entry when a group is configured.
		// IST_Fiscal_Year::get_start_month() falls back to July (7) when no entry exists.
		add_option( 'ist_group_config', array() );
	}
}
