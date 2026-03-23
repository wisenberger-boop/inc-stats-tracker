<?php
/**
 * Database helper — thin wrapper around $wpdb for IST tables.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_DB {

	/**
	 * Return a prefixed table name.
	 *
	 * @param string $table Unprefixed table slug (e.g. 'tyfcb').
	 * @return string
	 */
	public static function table( string $table ): string {
		global $wpdb;
		return $wpdb->prefix . 'ist_' . $table;
	}

	/**
	 * Insert a row and return the new ID, or false on failure.
	 *
	 * @param string $table  Unprefixed table slug.
	 * @param array  $data   Associative array of column => value.
	 * @param array  $format Optional printf-style format array.
	 * @return int|false
	 */
	public static function insert( string $table, array $data, array $format = array() ): int|false {
		global $wpdb;
		$result = $wpdb->insert( self::table( $table ), $data, $format ?: null );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update rows matching $where and return affected rows, or false on failure.
	 *
	 * @param string $table        Unprefixed table slug.
	 * @param array  $data         Columns to update.
	 * @param array  $where        WHERE conditions.
	 * @param array  $data_format  Format for $data.
	 * @param array  $where_format Format for $where.
	 * @return int|false
	 */
	public static function update( string $table, array $data, array $where, array $data_format = array(), array $where_format = array() ): int|false {
		global $wpdb;
		return $wpdb->update( self::table( $table ), $data, $where, $data_format ?: null, $where_format ?: null );
	}

	/**
	 * Delete rows matching $where.
	 *
	 * @param string $table        Unprefixed table slug.
	 * @param array  $where        WHERE conditions.
	 * @param array  $where_format Format for $where.
	 * @return int|false
	 */
	public static function delete( string $table, array $where, array $where_format = array() ): int|false {
		global $wpdb;
		return $wpdb->delete( self::table( $table ), $where, $where_format ?: null );
	}

	/**
	 * Fetch a single row by ID.
	 *
	 * @param string $table Unprefixed table slug.
	 * @param int    $id    Row ID.
	 * @return object|null
	 */
	public static function get_by_id( string $table, int $id ): ?object {
		global $wpdb;
		$t = self::table( $table );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d LIMIT 1", $id ) );
	}

	/**
	 * Fetch multiple rows with optional simple WHERE equality conditions.
	 *
	 * For complex queries, build SQL directly in the relevant service/model.
	 *
	 * @param string $table  Unprefixed table slug.
	 * @param array  $where  Associative array of column => value (all joined with AND).
	 * @param string $order  ORDER BY clause (e.g. 'recorded_at DESC').
	 * @param int    $limit  Max rows (0 = unlimited).
	 * @return array
	 */
	public static function get_rows( string $table, array $where = array(), string $order = 'id DESC', int $limit = 0 ): array {
		global $wpdb;
		$t      = self::table( $table );
		$sql    = "SELECT * FROM {$t}";
		$values = array();

		if ( ! empty( $where ) ) {
			$clauses = array();
			foreach ( $where as $col => $val ) {
				$clauses[] = "`{$col}` = %s";
				$values[]  = $val;
			}
			$sql .= ' WHERE ' . implode( ' AND ', $clauses );
		}

		$sql .= " ORDER BY {$order}";

		if ( $limit > 0 ) {
			$sql   .= ' LIMIT %d';
			$values[] = $limit;
		}

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $sql, $values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql ) ?: array();
	}
}
