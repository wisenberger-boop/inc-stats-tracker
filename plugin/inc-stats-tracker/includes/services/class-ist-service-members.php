<?php
/**
 * BuddyBoss group member service.
 *
 * All BuddyBoss Platform API calls are isolated to this class.
 * No other file in this plugin should call BuddyBoss functions directly.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Service_Members {

	/**
	 * Transient key prefix for group member cache.
	 */
	private const TRANSIENT_PREFIX = 'ist_group_members_';

	/**
	 * Cache TTL in seconds (15 minutes).
	 */
	private const CACHE_TTL = 900;

	/**
	 * Return the configured BuddyBoss group ID from plugin settings.
	 *
	 * @return int  0 if not yet configured.
	 */
	public static function get_configured_group_id(): int {
		$settings = ist_get_settings();
		return absint( $settings['bb_group_id'] ?? 0 );
	}

	/**
	 * Return normalised member objects for a BuddyBoss group.
	 *
	 * Results are cached in a transient for CACHE_TTL seconds. Returns an empty
	 * array if BuddyBoss Platform is not active or no group is configured.
	 *
	 * Each returned object has three properties:
	 *   ID           (int)    WordPress user ID
	 *   display_name (string) User display name
	 *   user_email   (string) User email address
	 *
	 * @param int $group_id  Defaults to the plugin-configured group.
	 * @return object[]
	 */
	public static function get_group_members( int $group_id = 0 ): array {
		if ( ! $group_id ) {
			$group_id = self::get_configured_group_id();
		}

		if ( ! $group_id || ! function_exists( 'groups_get_group_members' ) ) {
			return array();
		}

		$transient_key = self::TRANSIENT_PREFIX . $group_id;
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$result  = groups_get_group_members( array(
			'group_id'            => $group_id,
			'per_page'            => 500,
			'exclude_admins_mods' => false,
		) );

		$members = array();

		if ( ! empty( $result['members'] ) ) {
			foreach ( $result['members'] as $bp_member ) {
				$members[] = (object) array(
					'ID'           => (int) $bp_member->ID,
					'display_name' => $bp_member->display_name,
					'user_email'   => $bp_member->user_email,
				);
			}

			// Sort alphabetically by display name for consistent dropdown ordering.
			usort( $members, static fn( $a, $b ) => strcmp( $a->display_name, $b->display_name ) );
		}

		set_transient( $transient_key, $members, self::CACHE_TTL );

		return $members;
	}

	/**
	 * Flush the cached member list for a group.
	 *
	 * Call this when group membership changes are known (e.g. after a BuddyBoss
	 * group member join/leave hook fires).
	 *
	 * @param int $group_id  Defaults to the configured group.
	 */
	public static function flush_cache( int $group_id = 0 ): void {
		if ( ! $group_id ) {
			$group_id = self::get_configured_group_id();
		}
		if ( $group_id ) {
			delete_transient( self::TRANSIENT_PREFIX . $group_id );
		}
	}

	/**
	 * Check whether a WP user is a current member of the given group.
	 *
	 * Returns false if BuddyBoss Platform is not active or no group is configured.
	 *
	 * @param int $user_id
	 * @param int $group_id  Defaults to the configured group.
	 * @return bool
	 */
	public static function is_group_member( int $user_id, int $group_id = 0 ): bool {
		if ( ! $user_id || ! function_exists( 'groups_is_user_member' ) ) {
			return false;
		}

		if ( ! $group_id ) {
			$group_id = self::get_configured_group_id();
		}

		if ( ! $group_id ) {
			return false;
		}

		return (bool) groups_is_user_member( $user_id, $group_id );
	}
}
