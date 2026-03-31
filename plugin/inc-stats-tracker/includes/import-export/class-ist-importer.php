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
		// Normalisation helpers below must be called before passing values to services:
		//   - self::normalize_amount()         for tyfcb.amount
		//   - IST_Service_Referrals::normalize_referral_type()  for referral_type on both tyfcb and referrals
		//   - IST_Service_Referrals::normalize_referral_status() for referrals.status
		//   - IST_Service_Connects::normalize_meet_where()      for connects.meet_where
		//   - self::normalize_business_type()  for tyfcb.business_type
		//
		// TODO (historical import): resolving 'thank_you_to_type' for TYFCB rows requires
		// matching the CSV 'thankyouto' value against WP user display names. Values that do
		// not match any WP user should be treated as type='other'. See normalization note
		// in docs/database-schema.md — this requires a dedicated import-cleanup pass.

		return $result;
	}

	/**
	 * Return the expected CSV column headers for a record type.
	 *
	 * These are the canonical plugin column names that the import CSV should use.
	 * The legacy Google Form CSVs (timestamp, email, your name, thankyouto, etc.)
	 * must be remapped to these headers before import.
	 *
	 * @param string $type
	 * @return string[]
	 */
	public function get_expected_headers( string $type ): array {
		$headers = array(
			'tyfcb'     => array(
				'submitted_by_user_id',
				'thank_you_to_type',
				'thank_you_to_user_id',
				'thank_you_to_name',
				'amount',
				'business_type',
				'referral_type',
				'entry_date',
				'note',
			),
			'referrals' => array(
				'referred_by_user_id',
				'referred_to_name',
				'referred_to_user_id',
				'referral_type',
				'status',
				'entry_date',
				'note',
			),
			'connects'  => array(
				'member_user_id',
				'connected_with_name',
				'connected_with_user_id',
				'meet_where',
				'entry_date',
				'note',
			),
		);
		return $headers[ $type ] ?? array();
	}

	/**
	 * Strip currency formatting from a raw amount string and return a float.
	 *
	 * Handles CSV-style values like "$4,824" or "$1,234.56".
	 *
	 * @param string|float|int $raw
	 * @return float
	 */
	public static function normalize_amount( $raw ): float {
		return IST_Service_TYFCB::normalize_amount( $raw );
	}

	/**
	 * Normalise a raw business_type string to its canonical slug.
	 *
	 * @param string $raw  e.g. "New", "new", "Repeat"
	 * @return string  'new' | 'repeat' | ''
	 */
	public static function normalize_business_type( string $raw ): string {
		static $map = array(
			'new'    => 'new',
			'repeat' => 'repeat',
		);
		$key = strtolower( trim( $raw ) );
		return $map[ $key ] ?? '';
	}
}
