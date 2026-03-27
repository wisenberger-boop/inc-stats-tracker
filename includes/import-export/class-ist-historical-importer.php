<?php
/**
 * One-time historical data importer.
 *
 * Reads the legacy Google Form CSV exports bundled with the plugin and inserts
 * them into the three IST database tables.
 *
 * Design notes:
 *  - Bypasses the service layer intentionally. Services enforce current group
 *    membership and capabilities; historical records from former members must
 *    be imported without those guards.
 *  - Uses IST_DB::insert() directly so table-name prefixing is handled
 *    consistently with the rest of the plugin.
 *  - User resolution uses only the member lookup CSV — not live WP API calls.
 *    This keeps the import fast and deterministic regardless of WP state.
 *  - Duplicate prevention: each raw CSV row is SHA1-hashed; hashes are stored
 *    in the `ist_import_hashes` WP option (autoload=false). Re-running the
 *    importer skips any row whose hash is already present.
 *  - Rows are never skipped solely because a user cannot be resolved.
 *    Unresolved submitters are imported with user_id = 0 and logged as warnings.
 *  - Only rows with an unparseable entry_date are skipped (no date = no useful
 *    reporting record).
 *
 * Source CSV files (relative to plugin root):
 *   docs/source-assets/csv/TYFCB.csv
 *   docs/source-assets/csv/referrals.csv
 *   docs/source-assets/csv/connects.csv
 *   docs/source-assets/csv/inc_member_lookup_template.csv
 *
 * TODO (cleanup): rename inc_member_lookup_template.csv → inc_member_lookup.csv
 *   once the admin has confirmed the file is not a blank template. The importer
 *   supports the current filename as-is via the LOOKUP_CSV constant.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Historical_Importer {

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	/** WP option key that stores the set of imported row hashes. */
	const HASHES_OPTION = 'ist_import_hashes';

	/** Source CSV paths, relative to IST_PLUGIN_DIR. */
	const TYFCB_CSV     = 'docs/source-assets/csv/TYFCB.csv';
	const REFERRALS_CSV = 'docs/source-assets/csv/referrals.csv';
	const CONNECTS_CSV  = 'docs/source-assets/csv/connects.csv';
	const LOOKUP_CSV    = 'docs/source-assets/csv/inc_member_lookup_template.csv';

	// -------------------------------------------------------------------------
	// Instance state
	// -------------------------------------------------------------------------

	/**
	 * Lookup index keyed by lowercase email → { user_id, display_name }.
	 *
	 * @var array<string, array{user_id: int, display_name: string}>
	 */
	private array $by_email = array();

	/**
	 * Lookup index keyed by lowercase display_name → { user_id, display_name }.
	 *
	 * @var array<string, array{user_id: int, display_name: string}>
	 */
	private array $by_display_name = array();

	/** WP user ID of the admin running the import — fallback for created_by_user_id. */
	private int $admin_user_id;

	// -------------------------------------------------------------------------
	// Construction
	// -------------------------------------------------------------------------

	public function __construct() {
		$this->admin_user_id = get_current_user_id() ?: 1;
		$this->load_member_lookup();
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Run all three imports in sequence and return combined results.
	 *
	 * @return array{ tyfcb: array, referrals: array, connects: array }
	 */
	public function import_all(): array {
		return array(
			'tyfcb'     => $this->import_tyfcb(),
			'referrals' => $this->import_referrals(),
			'connects'  => $this->import_connects(),
		);
	}

	/**
	 * Return the data-row count (excluding header) for each source CSV.
	 *
	 * @return array{ tyfcb: int|null, referrals: int|null, connects: int|null }
	 *         null = file not found on disk.
	 */
	public function get_csv_stats(): array {
		$out = array();
		foreach ( array(
			'tyfcb'     => self::TYFCB_CSV,
			'referrals' => self::REFERRALS_CSV,
			'connects'  => self::CONNECTS_CSV,
		) as $key => $rel ) {
			$path = IST_PLUGIN_DIR . $rel;
			if ( ! file_exists( $path ) ) {
				$out[ $key ] = null;
				continue;
			}
			$n  = 0;
			$fh = fopen( $path, 'r' );
			while ( fgetcsv( $fh ) !== false ) {
				$n++;
			}
			fclose( $fh );
			$out[ $key ] = max( 0, $n - 1 ); // subtract header row
		}
		return $out;
	}

	/**
	 * Return how many row hashes are currently stored (= rows already imported).
	 */
	public function get_imported_count(): int {
		return count( $this->get_hashes() );
	}

	/**
	 * Return whether the member lookup CSV file exists.
	 */
	public function lookup_file_exists(): bool {
		return file_exists( IST_PLUGIN_DIR . self::LOOKUP_CSV );
	}

	/**
	 * Return the number of members loaded from the lookup CSV.
	 */
	public function get_member_count(): int {
		return count( $this->by_email );
	}

	/**
	 * Clear the stored hashes so the import can be re-run from scratch.
	 */
	public function reset_hashes(): void {
		delete_option( self::HASHES_OPTION );
	}

	// -------------------------------------------------------------------------
	// Per-table importers
	// -------------------------------------------------------------------------

	/**
	 * Import TYFCB.csv → wp_ist_tyfcb.
	 *
	 * Column positions (0-indexed):
	 *   0  timestamp     → created_at  (date only; stored as YYYY-MM-DD 00:00:00)
	 *   1  email         → submitted_by_user_id via lookup; 0 if unresolved
	 *   2  your name     → submitted_by_name snapshot
	 *   3  date          → entry_date  (required; row skipped if unparseable)
	 *   4  thankyouto    → thank_you_to_{type,name,user_id}
	 *   5  amount        → amount (stripped of $ and commas)
	 *   6  business-type → business_type slug
	 *   7  referral-type → referral_type slug
	 *   8  comments      → note
	 *   9+ (trailing blank columns from Google Forms — ignored)
	 *
	 * @return array{ imported: int, skipped: int, warnings: string[], errors: string[] }
	 */
	public function import_tyfcb(): array {
		$result = $this->empty_result();
		$path   = IST_PLUGIN_DIR . self::TYFCB_CSV;

		$fh = $this->open_csv( $path, $result );
		if ( ! $fh ) {
			return $result;
		}

		$hashes     = $this->get_hashes();
		$header_read = false;
		$row_num    = 0;

		while ( ( $raw = fgetcsv( $fh ) ) !== false ) {
			$row_num++;

			if ( ! $header_read ) {
				$header_read = true;
				continue; // skip header — we use fixed positional indices
			}

			if ( $this->is_empty_row( $raw ) ) {
				continue;
			}

			$hash = sha1( implode( '|', $raw ) );
			if ( isset( $hashes[ $hash ] ) ) {
				$result['skipped']++;
				continue;
			}

			// ---- Field extraction (positional) --------------------------------
			$ts         = trim( $raw[0] ?? '' );
			$email      = trim( $raw[1] ?? '' );
			$their_name = trim( $raw[2] ?? '' );
			$raw_date   = trim( $raw[3] ?? '' );
			$raw_source = trim( $raw[4] ?? '' );
			$raw_amount = trim( $raw[5] ?? '' );
			$raw_btype  = trim( $raw[6] ?? '' );
			$raw_rtype  = trim( $raw[7] ?? '' );
			$note       = trim( $raw[8] ?? '' );

			// ---- entry_date (required) ----------------------------------------
			$entry_date = $this->parse_date( $raw_date );
			if ( ! $entry_date ) {
				$result['warnings'][] = "TYFCB row {$row_num}: unparseable date \"{$raw_date}\" — row skipped.";
				continue;
			}

			// ---- Submitter ----------------------------------------------------
			$submitter            = $this->lookup_by_email( $email );
			$submitted_by_user_id = $submitter ? (int) $submitter['user_id'] : 0;
			$submitted_by_name    = $their_name !== ''
				? $their_name
				: ( $submitter['display_name'] ?? '' );

			if ( ! $submitted_by_user_id ) {
				$result['warnings'][] = "TYFCB row {$row_num}: submitter email \"{$email}\" not in member lookup — imported with user_id=0.";
			}

			// ---- Source attribution -------------------------------------------
			[ $ty_type, $ty_name, $ty_user_id ] = $this->resolve_attribution( $raw_source );

			// ---- Amount -------------------------------------------------------
			$amount = IST_Service_TYFCB::normalize_amount( $raw_amount );

			// ---- Controlled vocabularies -------------------------------------
			$business_type = IST_Importer::normalize_business_type( $raw_btype );
			$referral_type = IST_Service_Referrals::normalize_referral_type( $raw_rtype );

			// ---- Build insert data -------------------------------------------
			$data = array(
				'submitted_by_user_id' => $submitted_by_user_id,
				'submitted_by_name'    => $submitted_by_name,
				'thank_you_to_type'    => $ty_type,
				'thank_you_to_name'    => $ty_name,
				'amount'               => number_format( $amount, 2, '.', '' ),
				'business_type'        => $business_type,
				'referral_type'        => $referral_type,
				'note'                 => $note,
				'entry_date'           => $entry_date,
				'created_by_user_id'   => $submitted_by_user_id ?: $this->admin_user_id,
			);

			// thank_you_to_user_id: omit from INSERT when null → MySQL stores NULL.
			if ( null !== $ty_user_id ) {
				$data['thank_you_to_user_id'] = $ty_user_id;
			}

			// created_at: omit when blank → MySQL uses DEFAULT CURRENT_TIMESTAMP.
			$created_at = $this->parse_datetime( $ts );
			if ( $created_at ) {
				$data['created_at'] = $created_at;
			}

			// ---- Insert -------------------------------------------------------
			$id = IST_DB::insert( 'tyfcb', $data );
			if ( false !== $id ) {
				$hashes[ $hash ] = 1;
				$result['imported']++;
			} else {
				global $wpdb;
				$result['errors'][] = "TYFCB row {$row_num}: DB insert failed — {$wpdb->last_error}";
			}
		}

		fclose( $fh );
		$this->save_hashes( $hashes );
		return $result;
	}

	/**
	 * Import referrals.csv → wp_ist_referrals.
	 *
	 * Column positions (0-indexed):
	 *   0  Timestamp       → created_at
	 *   1  Email           → referred_by_user_id via lookup
	 *   2  Your Name       → referred_by_name snapshot
	 *                        NOTE: some rows have an email address in this column
	 *                        (data entry error). Always use col 1 for resolution.
	 *   3  Date            → entry_date
	 *   4  Referral To     → referred_to_{name,user_id}
	 *   5  Referral Type   → referral_type slug
	 *   6  Referral Status → status slug
	 *   7  Referral Details → note
	 *
	 * @return array{ imported: int, skipped: int, warnings: string[], errors: string[] }
	 */
	public function import_referrals(): array {
		$result = $this->empty_result();
		$path   = IST_PLUGIN_DIR . self::REFERRALS_CSV;

		$fh = $this->open_csv( $path, $result );
		if ( ! $fh ) {
			return $result;
		}

		$hashes      = $this->get_hashes();
		$header_read = false;
		$row_num     = 0;

		while ( ( $raw = fgetcsv( $fh ) ) !== false ) {
			$row_num++;

			if ( ! $header_read ) {
				$header_read = true;
				continue;
			}

			if ( $this->is_empty_row( $raw ) ) {
				continue;
			}

			$hash = sha1( implode( '|', $raw ) );
			if ( isset( $hashes[ $hash ] ) ) {
				$result['skipped']++;
				continue;
			}

			$ts          = trim( $raw[0] ?? '' );
			$email       = trim( $raw[1] ?? '' );
			$their_name  = trim( $raw[2] ?? '' );
			$raw_date    = trim( $raw[3] ?? '' );
			$raw_ref_to  = trim( $raw[4] ?? '' );
			$raw_rtype   = trim( $raw[5] ?? '' );
			$raw_status  = trim( $raw[6] ?? '' );
			$note        = trim( $raw[7] ?? '' );

			// ---- entry_date --------------------------------------------------
			$entry_date = $this->parse_date( $raw_date );
			if ( ! $entry_date ) {
				$result['warnings'][] = "Referrals row {$row_num}: unparseable date \"{$raw_date}\" — row skipped.";
				continue;
			}

			// ---- Submitter ---------------------------------------------------
			$submitter           = $this->lookup_by_email( $email );
			$referred_by_user_id = $submitter ? (int) $submitter['user_id'] : 0;
			$referred_by_name    = $their_name !== ''
				? $their_name
				: ( $submitter['display_name'] ?? '' );

			if ( ! $referred_by_user_id ) {
				$result['warnings'][] = "Referrals row {$row_num}: submitter email \"{$email}\" not in member lookup — imported with user_id=0.";
			}

			// ---- Referral target --------------------------------------------
			$referred_to_name  = $raw_ref_to;
			$ref_to_match      = $this->lookup_by_display_name( $raw_ref_to );
			$referred_to_uid   = $ref_to_match ? (int) $ref_to_match['user_id'] : null;

			// ---- Controlled vocabularies ------------------------------------
			$referral_type = IST_Service_Referrals::normalize_referral_type( $raw_rtype );
			$status        = IST_Service_Referrals::normalize_referral_status( $raw_status );

			// ---- Build insert data ------------------------------------------
			$data = array(
				'referred_by_user_id' => $referred_by_user_id,
				'referred_by_name'    => $referred_by_name,
				'referred_to_name'    => $referred_to_name,
				'referral_type'       => $referral_type,
				'status'              => $status,
				'note'                => $note,
				'entry_date'          => $entry_date,
				'created_by_user_id'  => $referred_by_user_id ?: $this->admin_user_id,
			);

			if ( null !== $referred_to_uid ) {
				$data['referred_to_user_id'] = $referred_to_uid;
			}

			$created_at = $this->parse_datetime( $ts );
			if ( $created_at ) {
				$data['created_at'] = $created_at;
			}

			$id = IST_DB::insert( 'referrals', $data );
			if ( false !== $id ) {
				$hashes[ $hash ] = 1;
				$result['imported']++;
			} else {
				global $wpdb;
				$result['errors'][] = "Referrals row {$row_num}: DB insert failed — {$wpdb->last_error}";
			}
		}

		fclose( $fh );
		$this->save_hashes( $hashes );
		return $result;
	}

	/**
	 * Import connects.csv → wp_ist_connects.
	 *
	 * Column positions (0-indexed):
	 *   0  Timestamp → created_at
	 *   1  Email     → member_user_id via lookup
	 *   2  Name      → member_display_name snapshot
	 *   3  Date      → entry_date
	 *   4  Met With  → connected_with_{name,user_id}
	 *                  Three variants: "Other", email address, or display name.
	 *   5  Where     → meet_where slug
	 *   6  Topic     → note (may contain embedded newlines — fgetcsv handles)
	 *
	 * @return array{ imported: int, skipped: int, warnings: string[], errors: string[] }
	 */
	public function import_connects(): array {
		$result = $this->empty_result();
		$path   = IST_PLUGIN_DIR . self::CONNECTS_CSV;

		$fh = $this->open_csv( $path, $result );
		if ( ! $fh ) {
			return $result;
		}

		$hashes      = $this->get_hashes();
		$header_read = false;
		$row_num     = 0;

		while ( ( $raw = fgetcsv( $fh ) ) !== false ) {
			$row_num++;

			if ( ! $header_read ) {
				$header_read = true;
				continue;
			}

			if ( $this->is_empty_row( $raw ) ) {
				continue;
			}

			$hash = sha1( implode( '|', $raw ) );
			if ( isset( $hashes[ $hash ] ) ) {
				$result['skipped']++;
				continue;
			}

			$ts           = trim( $raw[0] ?? '' );
			$email        = trim( $raw[1] ?? '' );
			$their_name   = trim( $raw[2] ?? '' );
			$raw_date     = trim( $raw[3] ?? '' );
			$raw_met_with = trim( $raw[4] ?? '' );
			$raw_where    = trim( $raw[5] ?? '' );
			$note         = trim( $raw[6] ?? '' );

			// ---- entry_date --------------------------------------------------
			$entry_date = $this->parse_date( $raw_date );
			if ( ! $entry_date ) {
				$result['warnings'][] = "Connects row {$row_num}: unparseable date \"{$raw_date}\" — row skipped.";
				continue;
			}

			// ---- Submitter ---------------------------------------------------
			$submitter           = $this->lookup_by_email( $email );
			$member_user_id      = $submitter ? (int) $submitter['user_id'] : 0;
			$member_display_name = $their_name !== ''
				? $their_name
				: ( $submitter['display_name'] ?? '' );

			if ( ! $member_user_id ) {
				$result['warnings'][] = "Connects row {$row_num}: submitter email \"{$email}\" not in member lookup — imported with user_id=0.";
			}

			// ---- Connected with ----------------------------------------------
			[ $cw_name, $cw_user_id ] = $this->resolve_connected_with( $raw_met_with );

			// ---- meet_where --------------------------------------------------
			$meet_where = IST_Service_Connects::normalize_meet_where( $raw_where );

			// ---- Build insert data ------------------------------------------
			$data = array(
				'member_user_id'      => $member_user_id,
				'member_display_name' => $member_display_name,
				'connected_with_name' => $cw_name,
				'meet_where'          => $meet_where,
				'note'                => $note,
				'entry_date'          => $entry_date,
				'created_by_user_id'  => $member_user_id ?: $this->admin_user_id,
			);

			if ( null !== $cw_user_id ) {
				$data['connected_with_user_id'] = $cw_user_id;
			}

			$created_at = $this->parse_datetime( $ts );
			if ( $created_at ) {
				$data['created_at'] = $created_at;
			}

			$id = IST_DB::insert( 'connects', $data );
			if ( false !== $id ) {
				$hashes[ $hash ] = 1;
				$result['imported']++;
			} else {
				global $wpdb;
				$result['errors'][] = "Connects row {$row_num}: DB insert failed — {$wpdb->last_error}";
			}
		}

		fclose( $fh );
		$this->save_hashes( $hashes );
		return $result;
	}

	// -------------------------------------------------------------------------
	// Member lookup loading
	// -------------------------------------------------------------------------

	/**
	 * Parse the member lookup CSV and populate the two in-memory indexes.
	 *
	 * Lookup CSV columns (positional, 0-indexed):
	 *   0  user_id
	 *   1  display_name
	 *   2  first_name
	 *   3  last_name
	 *   4  email
	 *   5  user_login
	 *   6  buddyboss_group_id
	 *   7  buddyboss_group_name
	 *   8  is_active_member
	 *   9  notes
	 *
	 * All email and display_name lookups are case-insensitive (strtolower).
	 */
	private function load_member_lookup(): void {
		$path = IST_PLUGIN_DIR . self::LOOKUP_CSV;
		if ( ! file_exists( $path ) ) {
			return;
		}

		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			return;
		}

		$header_read = false;

		while ( ( $row = fgetcsv( $fh ) ) !== false ) {
			if ( ! $header_read ) {
				$header_read = true;
				continue; // skip header
			}

			// Skip trailing blank rows (lookup CSV has many).
			if ( $this->is_empty_row( $row ) ) {
				continue;
			}

			$user_id      = (int) ( $row[0] ?? 0 );
			$display_name = trim( $row[1] ?? '' );
			$email        = strtolower( trim( $row[4] ?? '' ) );

			if ( ! $user_id || ! $display_name ) {
				continue;
			}

			$entry = array(
				'user_id'      => $user_id,
				'display_name' => $display_name,
			);

			if ( $email ) {
				$this->by_email[ $email ] = $entry;
			}

			$this->by_display_name[ strtolower( $display_name ) ] = $entry;
		}

		fclose( $fh );
	}

	// -------------------------------------------------------------------------
	// Resolution helpers
	// -------------------------------------------------------------------------

	/**
	 * Look up a member entry by email address (case-insensitive, trimmed).
	 *
	 * @return array{ user_id: int, display_name: string }|null
	 */
	private function lookup_by_email( string $email ): ?array {
		$key = strtolower( trim( $email ) );
		return $key !== '' ? ( $this->by_email[ $key ] ?? null ) : null;
	}

	/**
	 * Look up a member entry by display name (case-insensitive exact match).
	 *
	 * Conservative by design: "Susan Short" matches; "Susan Short (via BNI)" does not.
	 *
	 * @return array{ user_id: int, display_name: string }|null
	 */
	private function lookup_by_display_name( string $name ): ?array {
		$key = strtolower( trim( $name ) );
		return $key !== '' ? ( $this->by_display_name[ $key ] ?? null ) : null;
	}

	/**
	 * Resolve TYFCB "thankyouto" into the three attribution fields.
	 *
	 * Returns [ thank_you_to_type, thank_you_to_name, thank_you_to_user_id|null ]
	 *
	 * Rules:
	 *   - Empty source    → type='other', name='', user_id=null
	 *   - Display name match → type='member', name=canonical display_name, user_id=int
	 *   - No match        → type='other', name=raw trimmed string, user_id=null
	 */
	private function resolve_attribution( string $raw ): array {
		$raw = trim( $raw );

		if ( $raw === '' ) {
			return array( 'other', '', null );
		}

		$match = $this->lookup_by_display_name( $raw );
		if ( $match ) {
			return array( 'member', $match['display_name'], (int) $match['user_id'] );
		}

		return array( 'other', $raw, null );
	}

	/**
	 * Resolve connects "Met With" field into [ connected_with_name, user_id|null ].
	 *
	 * Three cases handled:
	 *   1. Literal "Other" (case-insensitive) → store as 'Other', no user_id.
	 *   2. Contains '@' (email address)        → try lookup_by_email(); if matched,
	 *      store canonical display_name + user_id; else store email as name.
	 *   3. Anything else                        → try lookup_by_display_name();
	 *      if matched, use canonical display_name + user_id; else store raw.
	 */
	private function resolve_connected_with( string $raw ): array {
		$raw = trim( $raw );

		if ( $raw === '' ) {
			return array( '', null );
		}

		if ( strtolower( $raw ) === 'other' ) {
			return array( 'Other', null );
		}

		// Looks like an email address.
		if ( strpos( $raw, '@' ) !== false ) {
			$match = $this->lookup_by_email( $raw );
			if ( $match ) {
				return array( $match['display_name'], (int) $match['user_id'] );
			}
			return array( $raw, null ); // store email as name — not a known member
		}

		// Standard display name lookup.
		$match = $this->lookup_by_display_name( $raw );
		if ( $match ) {
			return array( $match['display_name'], (int) $match['user_id'] );
		}

		return array( $raw, null );
	}

	// -------------------------------------------------------------------------
	// Date / time helpers
	// -------------------------------------------------------------------------

	/**
	 * Parse a date string in M/D/YYYY format (with or without leading zeros).
	 * Also accepts YYYY-MM-DD as a fallback for any already-normalised values.
	 *
	 * @return string Y-m-d, or '' if unparseable.
	 */
	private function parse_date( string $raw ): string {
		$raw = trim( $raw );
		if ( $raw === '' ) {
			return '';
		}

		$d = DateTime::createFromFormat( '!n/j/Y', $raw )
			?: DateTime::createFromFormat( '!m/d/Y', $raw )
			?: DateTime::createFromFormat( '!Y-m-d', $raw );

		return $d ? $d->format( 'Y-m-d' ) : '';
	}

	/**
	 * Convert a date-only CSV timestamp to a MySQL DATETIME string.
	 *
	 * Returns '' when unparseable; the caller should then omit created_at from
	 * the INSERT so MySQL uses its DEFAULT CURRENT_TIMESTAMP.
	 */
	private function parse_datetime( string $raw ): string {
		$date = $this->parse_date( $raw );
		return $date !== '' ? $date . ' 00:00:00' : '';
	}

	// -------------------------------------------------------------------------
	// CSV / I-O helpers
	// -------------------------------------------------------------------------

	/**
	 * Open a CSV file for reading; populate $result['errors'] and return false
	 * on failure.
	 *
	 * @param  string $path    Absolute filesystem path.
	 * @param  array  &$result Result array to append errors to.
	 * @return resource|false
	 */
	private function open_csv( string $path, array &$result ) {
		if ( ! file_exists( $path ) ) {
			$result['errors'][] = "Source file not found: {$path}";
			return false;
		}
		$fh = fopen( $path, 'r' );
		if ( ! $fh ) {
			$result['errors'][] = "Cannot open file for reading: {$path}";
			return false;
		}
		return $fh;
	}

	/**
	 * Return true when every field in a CSV row is an empty string.
	 * Used to skip the many trailing blank rows in Google Form exports.
	 */
	private function is_empty_row( array $row ): bool {
		foreach ( $row as $cell ) {
			if ( $cell !== '' ) {
				return false;
			}
		}
		return true;
	}

	// -------------------------------------------------------------------------
	// Hash persistence
	// -------------------------------------------------------------------------

	/**
	 * Return the stored hash set as an associative array (hash => 1).
	 * Using associative array enables O(1) isset() checks.
	 */
	private function get_hashes(): array {
		return (array) get_option( self::HASHES_OPTION, array() );
	}

	/**
	 * Persist the hash set.
	 * autoload=false: this option can be large and is only needed on import runs.
	 */
	private function save_hashes( array $hashes ): void {
		update_option( self::HASHES_OPTION, $hashes, false );
	}

	// -------------------------------------------------------------------------
	// Misc helpers
	// -------------------------------------------------------------------------

	/** Return a fresh empty result array. */
	private function empty_result(): array {
		return array(
			'imported' => 0,
			'skipped'  => 0,
			'warnings' => array(),
			'errors'   => array(),
		);
	}
}
