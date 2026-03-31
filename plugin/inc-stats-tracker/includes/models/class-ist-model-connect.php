<?php
/**
 * Connect model — data-access layer for ist_connects.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Model_Connect {

	public function get( int $id ): ?object {
		return IST_DB::get_by_id( 'connects', $id );
	}

	public function all( array $where = array() ): array {
		return IST_DB::get_rows( 'connects', $where, 'entry_date DESC, id DESC' );
	}

	/**
	 * @param array $data  { member_user_id, member_display_name, connected_with_name,
	 *                       connect_type, note, entry_date, created_by_user_id,
	 *                       connected_with_user_id (optional — omit to store NULL) }
	 * @return int|false
	 */
	public function create( array $data ): int|false {
		return IST_DB::insert( 'connects', $data );
	}

	public function update( int $id, array $data ): int|false {
		return IST_DB::update( 'connects', $data, array( 'id' => $id ) );
	}

	public function delete( int $id ): int|false {
		return IST_DB::delete( 'connects', array( 'id' => $id ) );
	}
}
