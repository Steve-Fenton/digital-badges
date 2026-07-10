<?php
/**
 * Bulk CSV badge issuance.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses CSV rows and creates assertions without storing emails.
 */
final class Csv_Issuer {

	/**
	 * Issue badges from CSV text.
	 *
	 * Expected columns: email (required), name, evidence, expires.
	 *
	 * @return array{issued: int, errors: list<string>}
	 */
	public static function issue_from_csv( int $badge_post_id, string $csv_text ): array {
		$issued = 0;
		$errors = array();

		if ( ! Issuer::is_configured() ) {
			return array(
				'issued' => 0,
				'errors' => array( __( 'Configure the issuing organization in Settings before issuing badges.', 'fenton-digital-badges' ) ),
			);
		}

		if ( ! Badge_Class::is_issuable( $badge_post_id ) ) {
			return array(
				'issued' => 0,
				'errors' => array( __( 'Select a published badge with a featured image and criteria URL.', 'fenton-digital-badges' ) ),
			);
		}

		$rows = self::parse_csv( $csv_text );

		if ( array() === $rows ) {
			return array(
				'issued' => 0,
				'errors' => array( __( 'No data rows found in the CSV.', 'fenton-digital-badges' ) ),
			);
		}

		foreach ( $rows as $index => $row ) {
			$line = isset( $row['_line'] ) ? (int) $row['_line'] : ( $index + 1 );
			unset( $row['_line'] );

			$email = isset( $row['email'] ) ? (string) $row['email'] : '';

			if ( ! Identity::is_valid_email( $email ) ) {
				$errors[] = sprintf(
					/* translators: %d: CSV line number */
					__( 'Line %d: invalid or missing email.', 'fenton-digital-badges' ),
					$line
				);
				continue;
			}

			$salt     = Identity::generate_salt();
			$identity = Identity::open_badges_identity( $email, $salt );
			$lookup   = Identity::lookup_hash( $email );

			$name     = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
			$evidence = isset( $row['evidence'] ) ? esc_url_raw( (string) $row['evidence'] ) : '';
			$expires_raw = isset( $row['expires'] ) ? (string) $row['expires'] : '';

			if ( self::expires_is_invalid( $expires_raw ) ) {
				$errors[] = sprintf(
					/* translators: %d: CSV line number */
					__( 'Line %d: invalid expires date (use YYYY-MM-DD).', 'fenton-digital-badges' ),
					$line
				);
				continue;
			}

			$expires = self::normalize_expires( $expires_raw );

			$assertion = Assertion_Repository::create(
				array(
					'badge_post_id'      => $badge_post_id,
					'recipient_identity' => $identity,
					'recipient_salt'     => $salt,
					'recipient_lookup'   => $lookup,
					'recipient_name'     => $name,
					'evidence_url'       => $evidence,
					'expires'            => $expires,
				)
			);

			// Email discarded; only hashes remain in $assertion / DB.
			unset( $email, $identity, $lookup, $salt );

			if ( null === $assertion ) {
				$errors[] = sprintf(
					/* translators: %d: CSV line number */
					__( 'Line %d: failed to create assertion.', 'fenton-digital-badges' ),
					$line
				);
				continue;
			}

			++$issued;
		}

		return array(
			'issued' => $issued,
			'errors' => $errors,
		);
	}

	/**
	 * Default column order when the CSV has no header row.
	 *
	 * @var list<string>
	 */
	private const DEFAULT_COLUMNS = array( 'email', 'name', 'evidence', 'expires' );

	/**
	 * Parse CSV text into associative rows keyed by header.
	 *
	 * Accepts either a header row (`email,name,...`) or headerless rows
	 * starting with an email address.
	 *
	 * @return list<array<string, string>>
	 */
	public static function parse_csv( string $csv_text ): array {
		$csv_text = trim( $csv_text );

		if ( '' === $csv_text ) {
			return array();
		}

		// Strip UTF-8 BOM.
		if ( str_starts_with( $csv_text, "\xEF\xBB\xBF" ) ) {
			$csv_text = substr( $csv_text, 3 );
		}

		$lines = preg_split( '/\r\n|\r|\n/', $csv_text );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		$raw_rows = array();

		foreach ( $lines as $line ) {
			$data = str_getcsv( $line );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$joined = trim( implode( '', array_map( 'strval', $data ) ) );
			if ( '' === $joined ) {
				continue;
			}

			$raw_rows[] = array_map(
				static function ( $cell ): string {
					return trim( (string) $cell );
				},
				$data
			);
		}

		if ( array() === $raw_rows ) {
			return array();
		}

		$first      = $raw_rows[0];
		$has_header = self::row_looks_like_header( $first );

		if ( $has_header ) {
			$columns = array_map(
				static function ( string $col ): string {
					return strtolower( $col );
				},
				$first
			);
			$data_rows = array_slice( $raw_rows, 1 );
			$line_base = 2;
		} else {
			$columns   = self::DEFAULT_COLUMNS;
			$data_rows = $raw_rows;
			$line_base = 1;
		}

		$rows = array();

		foreach ( $data_rows as $offset => $data ) {
			$row = array(
				'_line' => $line_base + $offset,
			);

			foreach ( $columns as $i => $key ) {
				if ( '' === $key || '_line' === $key ) {
					continue;
				}
				$row[ $key ] = isset( $data[ $i ] ) ? $data[ $i ] : '';
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Whether the first CSV row is a header rather than recipient data.
	 *
	 * @param list<string> $row First CSV row cells.
	 */
	private static function row_looks_like_header( array $row ): bool {
		if ( array() === $row ) {
			return false;
		}

		$normalized = array_map(
			static function ( string $cell ): string {
				return strtolower( trim( $cell ) );
			},
			$row
		);

		if ( in_array( 'email', $normalized, true ) ) {
			return true;
		}

		// First cell looks like an email → treat as data (no header).
		return ! Identity::is_valid_email( $normalized[0] ?? '' );
	}

	/**
	 * Normalize an optional expires value to MySQL datetime or null.
	 *
	 * Empty string → null (no expiry). Invalid non-empty → null and caller checks original.
	 */
	private static function normalize_expires( string $value ): ?string {
		$value = trim( $value );

		if ( '' === $value ) {
			return null;
		}

		$ts = strtotime( $value );

		if ( false === $ts ) {
			return null;
		}

		// Distinguish invalid: if strtotime failed we already returned null.
		// For values that parse, store UTC mysql datetime.
		return gmdate( 'Y-m-d H:i:s', $ts );
	}

	/**
	 * Whether a non-empty expires string failed to parse.
	 */
	public static function expires_is_invalid( string $value ): bool {
		$value = trim( $value );

		if ( '' === $value ) {
			return false;
		}

		return false === strtotime( $value );
	}
}
