<?php
/**
 * TYFCB model — data-access layer for ist_tyfcb.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Model_TYFCB {

	public function get( int $id ): ?object {
		return IST_DB::get_by_id( 'tyfcb', $id );
	}

	public function all( array $where = array() ): array {
		return IST_DB::get_rows( 'tyfcb', $where, 'entry_date DESC, id DESC' );
	}

	/**
	 * @param array $data  { submitted_by_user_id, submitted_by_name, thank_you_to_type,
	 *                       thank_you_to_name, amount, note, entry_date, created_by_user_id,
	 *                       thank_you_to_user_id (optional — omit to store NULL) }
	 * @return int|false
	 */
	public function create( array $data ): int|false {
		return IST_DB::insert( 'tyfcb', $data );
	}

	public function update( int $id, array $data ): int|false {
		return IST_DB::update( 'tyfcb', $data, array( 'id' => $id ) );
	}

	public function delete( int $id ): int|false {
		return IST_DB::delete( 'tyfcb', array( 'id' => $id ) );
	}
}
