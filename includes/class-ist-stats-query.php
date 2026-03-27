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
	// Trend data (chart support)
	// -------------------------------------------------------------------------

	/**
	 * Three-month trend data for chart rendering.
	 *
	 * Returns 3 calendar-month buckets ordered oldest → newest:
	 *   [0] Two months ago (full month)
	 *   [1] Last month     (full month)
	 *   [2] Current month  (month-to-date, end = $today)
	 *
	 * Months are calendar months — not fiscal months. The current-month bucket
	 * is always month-to-date so today's data is included immediately.
	 *
	 * Implementation: calls the existing totals methods 3 × 3 = 9 queries.
	 * Each query is a simple indexed COUNT/SUM — acceptable cost.
	 *
	 * @param string $today    Y-m-d  Reference date (normally today).
	 * @param array  $user_ids Scope to these user IDs. Empty = all records.
	 * @return array  Three items, each:
	 *   {
	 *     label        string  e.g. "Jan 2026"
	 *     tyfcb_amount float   Closed Business total amount
	 *     ref_count    int     Referral count
	 *     con_count    int     Connect count
	 *   }
	 */
	public static function three_month_trend( string $today, array $user_ids = array() ): array {
		$buckets = array();

		for ( $offset = 2; $offset >= 0; $offset-- ) {
			$month_ts = strtotime( "-{$offset} months", strtotime( $today ) );
			$start    = wp_date( 'Y-m-01', $month_ts );
			$end      = ( 0 === $offset ) ? $today : wp_date( 'Y-m-t', $month_ts );
			$label    = wp_date( 'M Y', $month_ts );

			$tyfcb = self::tyfcb_totals( $start, $end, $user_ids );
			$refs  = self::referral_totals( $start, $end, $user_ids );
			$cons  = self::connect_totals( $start, $end, $user_ids );

			$buckets[] = array(
				'label'        => $label,
				'tyfcb_amount' => $tyfcb['amount'],
				'ref_count'    => $refs['count'],
				'con_count'    => $cons['count'],
			);
		}

		return $buckets;
	}

	// -------------------------------------------------------------------------
	// FY monthly trend (chart support)
	// -------------------------------------------------------------------------

	/**
	 * Monthly totals for every elapsed month in the current fiscal year.
	 *
	 * Returns one bucket per calendar month from $fy_start through the month
	 * containing $today, ordered oldest → newest. The current month is always
	 * month-to-date (end = $today). All prior months use their full end date.
	 *
	 * Implementation: 3 queries × N elapsed months. For a typical 9-month FY
	 * elapsed period this is 27 queries — each a simple indexed COUNT/SUM.
	 *
	 * @param string $fy_start  Y-m-d first day of the current fiscal year.
	 * @param string $today     Y-m-d reference date (normally today).
	 * @param array  $user_ids  Scope to these user IDs. Empty = all records.
	 * @return array  One item per elapsed FY month, each:
	 *   {
	 *     label        string  e.g. "Jul 2025"
	 *     tyfcb_amount float
	 *     ref_count    int
	 *     con_count    int
	 *   }
	 */
	public static function fy_monthly_trend( string $fy_start, string $today, array $user_ids = array() ): array {
		$buckets  = array();
		$cursor   = new DateTime( $fy_start ); // always 1st of a month
		$today_dt = new DateTime( $today );
		$today_ym = $today_dt->format( 'Y-m' );

		while ( $cursor->format( 'Y-m' ) <= $today_ym ) {
			$start     = $cursor->format( 'Y-m-01' );
			$cursor_ym = $cursor->format( 'Y-m' );

			// End-of-month calculation: do NOT use wp_date( 'Y-m-t', strtotime( $start ) ).
			// strtotime() parses $start as UTC midnight; wp_date() converts to the site
			// timezone. On any UTC-minus site (all US timezones) midnight UTC July 1 is
			// still June 30 locally, so 'Y-m-t' returns the last day of the PREVIOUS month,
			// making every prior-month BETWEEN clause inverted (start > end = 0 rows).
			// DateTime::modify() stays in the object's own timezone (UTC) — no conversion.
			if ( $cursor_ym === $today_ym ) {
				$end = $today;
			} else {
				$end_dt = clone $cursor;
				$end_dt->modify( 'last day of this month' );
				$end = $end_dt->format( 'Y-m-d' );
			}

			$label = wp_date( 'M Y', strtotime( $start ) );

			$tyfcb = self::tyfcb_totals( $start, $end, $user_ids );
			$refs  = self::referral_totals( $start, $end, $user_ids );
			$cons  = self::connect_totals( $start, $end, $user_ids );

			$buckets[] = array(
				'label'        => $label,
				'tyfcb_amount' => $tyfcb['amount'],
				'ref_count'    => $refs['count'],
				'con_count'    => $cons['count'],
			);

			$cursor->modify( '+1 month' );
		}

		return $buckets;
	}

	// -------------------------------------------------------------------------
	// YTD same-point comparison
	// -------------------------------------------------------------------------

	/**
	 * Year-to-date totals for two equivalent date windows (current FY YTD vs
	 * the same elapsed point in the prior fiscal year).
	 *
	 * This method is date-range-agnostic: the caller computes both windows.
	 * Typical usage:
	 *   $current_start = IST_Fiscal_Year::get_fy_start( $today, $group_id )
	 *   $current_end   = $today
	 *   $prior_end     = wp_date( 'Y-m-d', strtotime( '-1 year', strtotime( $today ) ) )
	 *   $prior_start   = IST_Fiscal_Year::get_fy_start( $prior_end, $group_id )
	 *
	 * @param string $current_start  Y-m-d start of the current FY.
	 * @param string $current_end    Y-m-d today (current FY YTD end).
	 * @param string $prior_start    Y-m-d start of the prior FY.
	 * @param string $prior_end      Y-m-d same elapsed calendar point, one year ago.
	 * @param array  $user_ids       Scope. Empty = all records.
	 * @return array {
	 *     current: { tyfcb_amount, tyfcb_count, ref_count, con_count }
	 *     prior:   { tyfcb_amount, tyfcb_count, ref_count, con_count }
	 * }
	 */
	public static function ytd_comparison(
		string $current_start,
		string $current_end,
		string $prior_start,
		string $prior_end,
		array  $user_ids = array()
	): array {
		$ct = self::tyfcb_totals( $current_start, $current_end, $user_ids );
		$cr = self::referral_totals( $current_start, $current_end, $user_ids );
		$cc = self::connect_totals( $current_start, $current_end, $user_ids );

		$pt = self::tyfcb_totals( $prior_start, $prior_end, $user_ids );
		$pr = self::referral_totals( $prior_start, $prior_end, $user_ids );
		$pc = self::connect_totals( $prior_start, $prior_end, $user_ids );

		return array(
			'current' => array(
				'tyfcb_amount' => $ct['amount'],
				'tyfcb_count'  => $ct['count'],
				'ref_count'    => $cr['count'],
				'con_count'    => $cc['count'],
			),
			'prior'   => array(
				'tyfcb_amount' => $pt['amount'],
				'tyfcb_count'  => $pt['count'],
				'ref_count'    => $pr['count'],
				'con_count'    => $pc['count'],
			),
		);
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
