<?php
/**
 * Referral model — data-access layer for ist_referrals.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Model_Referral {

	public function get( int $id ): ?object {
		return IST_DB::get_by_id( 'referrals', $id );
	}

	public function all( array $where = array() ): array {
		return IST_DB::get_rows( 'referrals', $where, 'recorded_at DESC' );
	}

	/**
	 * @param array $data  { referred_by_user_id, referred_by_name, referred_to_name,
	 *                       status, note, entry_date, created_by_user_id,
	 *                       referred_to_user_id (optional — omit to store NULL) }
	 * @return int|false
	 */
	public function create( array $data ): int|false {
		return IST_DB::insert( 'referrals', $data );
	}

	public function update( int $id, array $data ): int|false {
		return IST_DB::update( 'referrals', $data, array( 'id' => $id ) );
	}

	public function delete( int $id ): int|false {
		return IST_DB::delete( 'referrals', array( 'id' => $id ) );
	}
}
