<?php
/**
 * CSV importer for IST records.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Importer {

	/**
	 * Process an uploaded CSV file for a given record type.
	 *
	 * @param string $file_path  Absolute path to the temp CSV file.
	 * @param string $type       Record type: 'tyfcb', 'referrals', or 'connects'.
	 * @return array  { imported: int, skipped: int, errors: string[] }
	 */
	public function import_csv( string $file_path, string $type ): array {
		$result = array( 'imported' => 0, 'skipped' => 0, 'errors' => array() );

		if ( ! file_exists( $file_path ) ) {
			$result['errors'][] = __( 'File not found.', 'inc-stats-tracker' );
			return $result;
		}

		// TODO: parse rows, validate required columns, delegate to appropriate service.
		// Stub — implement per record type.

		return $result;
	}

	/**
	 * Return the expected CSV column headers for a record type.
	 *
	 * @param string $type
	 * @return string[]
	 */
	public function get_expected_headers( string $type ): array {
		// Importers should resolve submitted_by_user_id and thank_you_to_user_id from
		// user_email or user_login. thank_you_to_user_id may be blank when type = 'other'.
		$headers = array(
			'tyfcb'     => array( 'submitted_by_user_id', 'thank_you_to_type', 'thank_you_to_user_id', 'thank_you_to_name', 'amount', 'entry_date', 'note' ),
			'referrals' => array( 'referred_by_user_id', 'referred_to_name', 'referred_to_user_id', 'status', 'entry_date', 'note' ),
			'connects'  => array( 'member_user_id', 'connected_with_name', 'connected_with_user_id', 'connect_type', 'entry_date', 'note' ),
		);
		return $headers[ $type ] ?? array();
	}
}
