<?php
/**
 * Fiscal-year date calculations.
 *
 * All fiscal-year math is isolated here. No report query, template, or service
 * should perform FY date arithmetic directly — always call this class.
 *
 * The fiscal year start month is configurable per BuddyBoss group and stored in
 * `ist_group_config[ $group_id ]['fiscal_year_start_month']`. When no config is
 * found, the default (July = month 7) is used.
 *
 * Usage:
 *   // Dates for today's fiscal year (active group auto-resolved):
 *   $start = IST_Fiscal_Year::get_fy_start();   // e.g. '2025-07-01'
 *   $end   = IST_Fiscal_Year::get_fy_end();     // e.g. '2026-06-30'
 *   $label = IST_Fiscal_Year::get_label();      // e.g. 'FY 2025–26'
 *
 *   // Both current and prior FY in one call (for report KPI cards):
 *   $fy = IST_Fiscal_Year::get_current_and_prior();
 *   // $fy['current']['start'], $fy['current']['end'], $fy['current']['label']
 *   // $fy['prior']['start'],   $fy['prior']['end'],   $fy['prior']['label']
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Fiscal_Year {

	/**
	 * Fallback fiscal year start month when no group config is set.
	 * 7 = July, matching the current chapter default.
	 */
	private const DEFAULT_START_MONTH = 7;

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Return the fiscal year start month (1–12) for a group.
	 *
	 * @param int $group_id  BuddyBoss group ID. 0 = resolve from configured group.
	 * @return int  Month number 1–12.
	 */
	public static function get_start_month( int $group_id = 0 ): int {
		$config = self::resolve_group_config( $group_id );
		$month  = (int) ( $config['fiscal_year_start_month'] ?? self::DEFAULT_START_MONTH );
		return ( $month >= 1 && $month <= 12 ) ? $month : self::DEFAULT_START_MONTH;
	}

	/**
	 * Return the first day (Y-m-d) of the fiscal year that contains $reference_date.
	 *
	 * @param string $reference_date  Y-m-d. Defaults to today in the site timezone.
	 * @param int    $group_id        0 = resolve from configured group.
	 * @return string  e.g. '2025-07-01'
	 */
	public static function get_fy_start( string $reference_date = '', int $group_id = 0 ): string {
		$ref         = new DateTime( $reference_date ?: wp_date( 'Y-m-d' ) );
		$month       = (int) $ref->format( 'n' );
		$year        = (int) $ref->format( 'Y' );
		$start_month = self::get_start_month( $group_id );

		// If we haven't yet reached the start month this calendar year,
		// the fiscal year began in the previous calendar year.
		$fy_start_year = ( $month >= $start_month ) ? $year : $year - 1;
		return sprintf( '%04d-%02d-01', $fy_start_year, $start_month );
	}

	/**
	 * Return the last day (Y-m-d) of the fiscal year that contains $reference_date.
	 *
	 * @param string $reference_date  Y-m-d. Defaults to today.
	 * @param int    $group_id        0 = resolve from configured group.
	 * @return string  e.g. '2026-06-30'
	 */
	public static function get_fy_end( string $reference_date = '', int $group_id = 0 ): string {
		$start = new DateTime( self::get_fy_start( $reference_date, $group_id ) );
		$start->modify( '+1 year -1 day' );
		return $start->format( 'Y-m-d' );
	}

	/**
	 * Return a human-readable fiscal year label for a reference date.
	 *
	 * Examples:
	 *   start_month = 7, reference = 2026-03-15  → 'FY 2025–26'
	 *   start_month = 7, reference = 2026-08-10  → 'FY 2026–27'
	 *   start_month = 1, reference = 2026-05-01  → 'FY 2026'  (calendar year)
	 *
	 * @param string $reference_date  Y-m-d. Defaults to today.
	 * @param int    $group_id        0 = resolve from configured group.
	 * @return string
	 */
	public static function get_label( string $reference_date = '', int $group_id = 0 ): string {
		$fy_start    = self::get_fy_start( $reference_date, $group_id );
		$start_month = self::get_start_month( $group_id );
		$fy_year     = (int) substr( $fy_start, 0, 4 );

		if ( 1 === $start_month ) {
			// Fiscal year aligns with calendar year — single-year label.
			return 'FY ' . $fy_year;
		}

		// Cross-calendar-year fiscal year — show both years, e.g. 'FY 2025–26'.
		return sprintf( "FY %d\u{2013}%02d", $fy_year, ( $fy_year + 1 ) % 100 );
	}

	/**
	 * Return date ranges for the current and prior fiscal years.
	 *
	 * Convenience wrapper for report KPI cards that need both periods simultaneously.
	 * All four date strings are Y-m-d and safe to interpolate directly into SQL
	 * WHERE clauses via $wpdb->prepare().
	 *
	 * @param string $reference_date  Y-m-d. Defaults to today.
	 * @param int    $group_id        0 = resolve from configured group.
	 * @return array {
	 *     @type array $current { start: string, end: string, label: string }
	 *     @type array $prior   { start: string, end: string, label: string }
	 * }
	 */
	public static function get_current_and_prior( string $reference_date = '', int $group_id = 0 ): array {
		$ref_date      = $reference_date ?: wp_date( 'Y-m-d' );
		$current_start = self::get_fy_start( $ref_date, $group_id );
		$current_end   = self::get_fy_end( $ref_date, $group_id );

		// Prior FY: step back one day from the current FY start.
		$prior_ref_dt = new DateTime( $current_start );
		$prior_ref_dt->modify( '-1 day' );
		$prior_ref   = $prior_ref_dt->format( 'Y-m-d' );
		$prior_start = self::get_fy_start( $prior_ref, $group_id );
		$prior_end   = self::get_fy_end( $prior_ref, $group_id );

		return array(
			'current' => array(
				'start' => $current_start,
				'end'   => $current_end,
				'label' => self::get_label( $ref_date, $group_id ),
			),
			'prior'   => array(
				'start' => $prior_start,
				'end'   => $prior_end,
				'label' => self::get_label( $prior_ref, $group_id ),
			),
		);
	}

	/**
	 * Return fiscal year progress metrics for a given reference date.
	 *
	 * All date arithmetic uses day-counts so the result is timezone-safe when
	 * called via wp_date(). The returned array is intentionally flat so it can
	 * be extracted directly into a template via ist_get_template().
	 *
	 * Assumption: "today" is the last elapsed day (inclusive). A FY that starts
	 * today has 1 elapsed day. A FY that ends today is 100 % complete.
	 *
	 * This method is context-only — it does NOT change how stat totals are
	 * queried. Totals are always driven by entry_date in IST_Stats_Query.
	 *
	 * @param string $reference_date  Y-m-d. Defaults to today in site timezone.
	 * @param int    $group_id        0 = resolve from configured group.
	 * @return array {
	 *     @type string $fy_start        Y-m-d start of fiscal year.
	 *     @type string $fy_end          Y-m-d end of fiscal year.
	 *     @type string $fy_label        Human-readable label (e.g. "FY 2025–26").
	 *     @type int    $total_days      Calendar days in this fiscal year (365 or 366).
	 *     @type int    $elapsed_days    Days elapsed from fy_start through today (≥ 1).
	 *     @type int    $remaining_days  Days from today through fy_end (≥ 0).
	 *     @type float  $percent_elapsed Percentage 0–100, rounded to one decimal.
	 * }
	 */
	public static function get_progress( string $reference_date = '', int $group_id = 0 ): array {
		$today    = $reference_date ?: wp_date( 'Y-m-d' );
		$fy_start = self::get_fy_start( $today, $group_id );
		$fy_end   = self::get_fy_end( $today, $group_id );
		$fy_label = self::get_label( $today, $group_id );

		$start_dt   = new DateTime( $fy_start );
		$end_dt     = new DateTime( $fy_end );
		$today_dt   = new DateTime( $today );

		// Total days = end − start + 1 (both endpoints inclusive).
		$total_days = (int) $start_dt->diff( $end_dt )->days + 1;

		// Elapsed days = today − start + 1 (today counts as an elapsed day).
		$elapsed_days = (int) $start_dt->diff( $today_dt )->days + 1;
		// Clamp: should always be ≥ 1 and ≤ total_days, but guard defensively.
		$elapsed_days = max( 1, min( $elapsed_days, $total_days ) );

		$remaining_days  = $total_days - $elapsed_days;
		$percent_elapsed = round( ( $elapsed_days / $total_days ) * 100, 1 );

		return array(
			'fy_start'        => $fy_start,
			'fy_end'          => $fy_end,
			'fy_label'        => $fy_label,
			'total_days'      => $total_days,
			'elapsed_days'    => $elapsed_days,
			'remaining_days'  => $remaining_days,
			'percent_elapsed' => $percent_elapsed,
		);
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Resolve the group config array for a group ID.
	 * Falls back to the configured group when $group_id = 0.
	 *
	 * @param int $group_id
	 * @return array
	 */
	private static function resolve_group_config( int $group_id ): array {
		if ( ! $group_id ) {
			$group_id = IST_Service_Members::get_configured_group_id();
		}
		return ist_get_group_config( $group_id );
	}
}
