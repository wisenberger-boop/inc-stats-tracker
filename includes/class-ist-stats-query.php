<?php
/**
 * Aggregation queries used by both the profile "My Stats" tab and the
 * group "Group Stats Reports" tab.
 *
 * All methods accept a $user_ids parameter that scopes rows by submitter:
 *   - TYFCB    : submitted_by_user_id
 *   - Referrals: referred_by_user_id
 *   - Connects : member_user_id
 *
 * Leaderboard ranking columns differ from the scope column:
 *   - tyfcb_leaderboard    : ranks by credited source (thank_you_to_name) —
 *                            answers "who is credited most by group members".
 *   - referral_leaderboard : ranks by referral giver (referred_by_user_id) —
 *                            answers "who gives the most referrals".
 *   - connect_leaderboard  : ranks by connect logger (member_user_id) —
 *                            answers "who logs the most connects".
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Stats_Query {

	// -------------------------------------------------------------------------
	// Totals
	// -------------------------------------------------------------------------

	/**
	 * Aggregate TYFCB amount and record count for a date range.
	 *
	 * @param string $date_start  Y-m-d inclusive start.
	 * @param string $date_end    Y-m-d inclusive end.
	 * @param array  $user_ids    Limit to these submitted_by_user_id values. Empty = no filter.
	 * @return array { amount: float, count: int }
	 */
	public static function tyfcb_totals( string $date_start, string $date_end, array $user_ids = array() ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_tyfcb';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT COALESCE(SUM(amount),0) AS amount, COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND submitted_by_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$row = $wpdb->get_row( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array(
			'amount' => (float) ( $row->amount ?? 0 ),
			'count'  => (int)   ( $row->count  ?? 0 ),
		);
	}

	/**
	 * Count referral records for a date range.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @param array  $user_ids  Limit to these referred_by_user_id values.
	 * @return array { count: int }
	 */
	public static function referral_totals( string $date_start, string $date_end, array $user_ids = array() ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_referrals';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND referred_by_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$count = $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'count' => (int) $count );
	}

	/**
	 * Count connect records for a date range.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @param array  $user_ids  Limit to these member_user_id values.
	 * @return array { count: int }
	 */
	public static function connect_totals( string $date_start, string $date_end, array $user_ids = array() ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_connects';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND member_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$count = $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'count' => (int) $count );
	}

	// -------------------------------------------------------------------------
	// Leaderboards
	// -------------------------------------------------------------------------

	/**
	 * TYFCB leaderboard: ranks credited business sources by total amount.
	 *
	 * Ranking column: thank_you_to_name (display name snapshot written at insert).
	 * Scope parameter: submitted_by_user_id — which group members' submissions to include.
	 *
	 * Result rows: { name, user_id (int|null), amount (float), count (int) }
	 * Ordered by amount DESC, count DESC.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @param array  $user_ids  Scope to these submitters. Empty = all records.
	 * @param int    $limit
	 * @return array
	 */
	public static function tyfcb_leaderboard(
		string $date_start,
		string $date_end,
		array $user_ids = array(),
		int $limit = 10
	): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_tyfcb';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT thank_you_to_name AS name,
		                 MAX(thank_you_to_user_id) AS user_id,
		                 SUM(amount) AS amount,
		                 COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND submitted_by_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$sql   .= ' GROUP BY thank_you_to_name ORDER BY amount DESC, count DESC LIMIT %d';
		$args[] = max( 1, $limit );

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $rows ) {
			return array();
		}

		return array_map( static function ( $row ) {
			return array(
				'name'    => $row->name,
				'user_id' => $row->user_id ? (int) $row->user_id : null,
				'amount'  => (float) $row->amount,
				'count'   => (int) $row->count,
			);
		}, $rows );
	}

	/**
	 * Referral leaderboard: ranks group members by referrals given.
	 *
	 * Ranking column: referred_by_user_id.
	 * Scope parameter: referred_by_user_id (same column — scope and rank are the same).
	 *
	 * Result rows: { user_id (int), name (string), count (int) }
	 * Ordered by count DESC.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @param array  $user_ids  Scope to these referral givers. Empty = all records.
	 * @param int    $limit
	 * @return array
	 */
	public static function referral_leaderboard(
		string $date_start,
		string $date_end,
		array $user_ids = array(),
		int $limit = 10
	): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_referrals';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT referred_by_user_id AS user_id,
		                 MAX(referred_by_name) AS name,
		                 COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND referred_by_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$sql   .= ' GROUP BY referred_by_user_id ORDER BY count DESC LIMIT %d';
		$args[] = max( 1, $limit );

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $rows ) {
			return array();
		}

		return array_map( static function ( $row ) {
			return array(
				'user_id' => (int) $row->user_id,
				'name'    => $row->name,
				'count'   => (int) $row->count,
			);
		}, $rows );
	}

	/**
	 * Connect leaderboard: ranks group members by connects logged.
	 *
	 * Ranking column: member_user_id.
	 * Scope parameter: member_user_id (same column).
	 *
	 * Result rows: { user_id (int), name (string), count (int) }
	 * Ordered by count DESC.
	 *
	 * @param string $date_start
	 * @param string $date_end
	 * @param array  $user_ids  Scope to these connect loggers. Empty = all records.
	 * @param int    $limit
	 * @return array
	 */
	public static function connect_leaderboard(
		string $date_start,
		string $date_end,
		array $user_ids = array(),
		int $limit = 10
	): array {
		global $wpdb;

		$table = $wpdb->prefix . 'ist_connects';
		$args  = array( $date_start, $date_end );
		$sql   = "SELECT member_user_id AS user_id,
		                 MAX(member_display_name) AS name,
		                 COUNT(*) AS count
		          FROM {$table}
		          WHERE entry_date BETWEEN %s AND %s";

		if ( $user_ids ) {
			$sql  .= ' AND member_user_id IN (' . self::placeholders( $user_ids ) . ')';
			$args  = array_merge( $args, array_map( 'absint', $user_ids ) );
		}

		$sql   .= ' GROUP BY member_user_id ORDER BY count DESC LIMIT %d';
		$args[] = max( 1, $limit );

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $rows ) {
			return array();
		}

		return array_map( static function ( $row ) {
			return array(
				'user_id' => (int) $row->user_id,
				'name'    => $row->name,
				'count'   => (int) $row->count,
			);
		}, $rows );
	}

	// -------------------------------------------------------------------------
	// Recent records (profile view)
	// -------------------------------------------------------------------------

	/**
	 * Most recent TYFCB records submitted by a specific user.
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @return array  Raw DB rows.
	 */
	public static function tyfcb_recent( int $user_id, int $limit = 5 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ist_tyfcb';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT id, entry_date, thank_you_to_name, amount, business_type, referral_type, note
			 FROM {$table}
			 WHERE submitted_by_user_id = %d
			 ORDER BY entry_date DESC, created_at DESC
			 LIMIT %d",
			$user_id,
			max( 1, $limit )
		) ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Most recent referrals given by a specific user.
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @return array  Raw DB rows.
	 */
	public static function referral_recent( int $user_id, int $limit = 5 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ist_referrals';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT id, entry_date, referred_to_name, referral_type, status, note
			 FROM {$table}
			 WHERE referred_by_user_id = %d
			 ORDER BY entry_date DESC, created_at DESC
			 LIMIT %d",
			$user_id,
			max( 1, $limit )
		) ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Most recent connects logged by a specific user.
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @return array  Raw DB rows.
	 */
	public static function connect_recent( int $user_id, int $limit = 5 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'ist_connects';
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT id, entry_date, connected_with_name, meet_where, note
			 FROM {$table}
			 WHERE member_user_id = %d
			 ORDER BY entry_date DESC, created_at DESC
			 LIMIT %d",
			$user_id,
			max( 1, $limit )
		) ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a comma-separated string of %d placeholders for use in IN clauses.
	 *
	 * @param array $items  Any non-empty array.
	 * @return string  e.g. "%d,%d,%d"
	 */
	private static function placeholders( array $items ): string {
		return implode( ',', array_fill( 0, count( $items ), '%d' ) );
	}
}
