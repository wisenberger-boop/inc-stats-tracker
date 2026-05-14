<?php
/**
 * Read-only REST API endpoints for INC Stats Tracker.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_REST_API {

	/**
	 * REST namespace.
	 */
	private const NAMESPACE = 'inc-stats-tracker/v1';

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/my-summary',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_my_summary' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				'schema' => array( $this, 'get_my_summary_schema' ),
			)
		);
	}

	/**
	 * Require a logged-in WordPress user.
	 *
	 * @return true|WP_Error
	 */
	public function permissions_check() {
		if ( is_user_logged_in() ) {
			return true;
		}

		return new WP_Error(
			'rest_not_logged_in',
			__( 'You must be logged in to view your stats summary.', 'inc-stats-tracker' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Return the current user's fiscal-year stats summary.
	 *
	 * @return WP_REST_Response
	 */
	public function get_my_summary(): WP_REST_Response {
		$user_id  = get_current_user_id();
		$today    = wp_date( 'Y-m-d' );
		$fy_start = IST_Fiscal_Year::get_fy_start( $today );
		$user_ids = array( $user_id );

		$tyfcb = IST_Stats_Query::tyfcb_totals( $fy_start, $today, $user_ids );
		$refs  = IST_Stats_Query::referral_totals( $fy_start, $today, $user_ids );
		$cons  = IST_Stats_Query::connect_totals( $fy_start, $today, $user_ids );

		return new WP_REST_Response(
			array(
				'user_id' => $user_id,
				'period'  => array(
					'label' => 'Current fiscal year',
					'start' => $fy_start,
					'end'   => $today,
				),
				'totals'  => array(
					'closed_business_amount' => (float) ( $tyfcb['amount'] ?? 0 ),
					'closed_business_count'  => (int) ( $tyfcb['count'] ?? 0 ),
					'referral_count'         => (int) ( $refs['count'] ?? 0 ),
					'connect_count'          => (int) ( $cons['count'] ?? 0 ),
				),
				'recent'  => array(
					'closed_business' => $this->format_closed_business_rows( IST_Stats_Query::tyfcb_recent( $user_id ) ),
					'referrals'       => $this->format_referral_rows( IST_Stats_Query::referral_recent( $user_id ) ),
					'connects'        => $this->format_connect_rows( IST_Stats_Query::connect_recent( $user_id ) ),
				),
			),
			200
		);
	}

	/**
	 * Return the response schema for the my-summary route.
	 *
	 * @return array
	 */
	public function get_my_summary_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'inc_stats_my_summary',
			'type'       => 'object',
			'properties' => array(
				'user_id' => array(
					'type' => 'integer',
				),
				'period'  => array(
					'type'       => 'object',
					'properties' => array(
						'label' => array( 'type' => 'string' ),
						'start' => array( 'type' => 'string' ),
						'end'   => array( 'type' => 'string' ),
					),
				),
				'totals'  => array(
					'type'       => 'object',
					'properties' => array(
						'closed_business_amount' => array( 'type' => 'number' ),
						'closed_business_count'  => array( 'type' => 'integer' ),
						'referral_count'         => array( 'type' => 'integer' ),
						'connect_count'          => array( 'type' => 'integer' ),
					),
				),
				'recent'  => array(
					'type'       => 'object',
					'properties' => array(
						'closed_business' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'referrals'       => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
						'connects'        => array(
							'type'  => 'array',
							'items' => array( 'type' => 'object' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Format recent closed business rows for REST output.
	 *
	 * @param array $rows Raw rows from IST_Stats_Query.
	 * @return array
	 */
	private function format_closed_business_rows( array $rows ): array {
		return array_map(
			static function ( $row ): array {
				return array(
					'id'                => (int) $row->id,
					'entry_date'        => (string) $row->entry_date,
					'thank_you_to_name' => (string) $row->thank_you_to_name,
					'amount'            => (float) $row->amount,
					'business_type'     => (string) $row->business_type,
					'referral_type'     => (string) $row->referral_type,
					'note'              => (string) $row->note,
				);
			},
			$rows
		);
	}

	/**
	 * Format recent referral rows for REST output.
	 *
	 * @param array $rows Raw rows from IST_Stats_Query.
	 * @return array
	 */
	private function format_referral_rows( array $rows ): array {
		return array_map(
			static function ( $row ): array {
				return array(
					'id'               => (int) $row->id,
					'entry_date'       => (string) $row->entry_date,
					'referred_to_name' => (string) $row->referred_to_name,
					'referral_type'    => (string) $row->referral_type,
					'status'           => (string) $row->status,
					'note'             => (string) $row->note,
				);
			},
			$rows
		);
	}

	/**
	 * Format recent connect rows for REST output.
	 *
	 * @param array $rows Raw rows from IST_Stats_Query.
	 * @return array
	 */
	private function format_connect_rows( array $rows ): array {
		return array_map(
			static function ( $row ): array {
				return array(
					'id'                  => (int) $row->id,
					'entry_date'          => (string) $row->entry_date,
					'connected_with_name' => (string) $row->connected_with_name,
					'meet_where'          => (string) $row->meet_where,
					'note'                => (string) $row->note,
				);
			},
			$rows
		);
	}
}
