<?php
/**
 * CSV exporter for IST records.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Exporter {

	/**
	 * Stream a CSV download for the given record type and filters.
	 *
	 * Exits after output.
	 *
	 * @param string $type    Record type: 'tyfcb', 'referrals', or 'connects'.
	 * @param array  $filters Optional WHERE filters passed to the model.
	 */
	public function export_csv( string $type, array $filters = array() ): void {
		if ( ! IST_Capabilities::current_user_can( 'ist_export_records' ) ) {
			wp_die( esc_html__( 'You do not have permission to export records.', 'inc-stats-tracker' ) );
		}

		$rows = $this->get_rows( $type, $filters );

		$filename = sprintf( 'ist-%s-%s.csv', $type, gmdate( 'Y-m-d' ) );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! empty( $rows ) ) {
			fputcsv( $output, array_keys( (array) $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $output, (array) $row );
			}
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * Fetch rows from the appropriate model.
	 *
	 * @param string $type
	 * @param array  $filters
	 * @return array
	 */
	private function get_rows( string $type, array $filters ): array {
		switch ( $type ) {
			case 'tyfcb':
				return ( new IST_Model_TYFCB() )->all( $filters );
			case 'referrals':
				return ( new IST_Model_Referral() )->all( $filters );
			case 'connects':
				return ( new IST_Model_Connect() )->all( $filters );
			default:
				return array();
		}
	}
}
