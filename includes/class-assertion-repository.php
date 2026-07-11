<?php
/**
 * Persistence for Open Badges assertions.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD and lookup for the assertions custom table.
 *
 * Direct $wpdb access is required; there is no WordPress API for this table.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom assertions table.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom assertions table.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom assertions table.
final class Assertion_Repository {

	public const TABLE_SUFFIX = 'db_assertions';

	/**
	 * Full table name including wpdb prefix.
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Create or upgrade the assertions table.
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uid varchar(64) NOT NULL,
			badge_post_id bigint(20) unsigned NOT NULL,
			recipient_identity varchar(128) NOT NULL,
			recipient_salt varchar(128) NOT NULL,
			recipient_lookup varchar(64) NOT NULL,
			recipient_name varchar(255) NOT NULL DEFAULT '',
			evidence_url text NULL,
			issued_on datetime NOT NULL,
			expires datetime NULL,
			revoked tinyint(1) NOT NULL DEFAULT 0,
			revoked_reason text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uid (uid),
			KEY recipient_lookup (recipient_lookup),
			KEY badge_post_id (badge_post_id),
			KEY issued_on (issued_on)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Drop the assertions table.
	 */
	public static function drop_table(): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', self::table_name() ) );
	}

	/**
	 * Generate a locally unique assertion uid.
	 */
	public static function generate_uid(): string {
		return bin2hex( random_bytes( 8 ) );
	}

	/**
	 * Insert a new assertion.
	 *
	 * @param array{
	 *   badge_post_id: int,
	 *   recipient_identity: string,
	 *   recipient_salt: string,
	 *   recipient_lookup: string,
	 *   recipient_name?: string,
	 *   evidence_url?: string,
	 *   issued_on?: string,
	 *   expires?: string|null
	 * } $data Assertion fields.
	 * @return object|null Inserted row or null on failure.
	 */
	public static function create( array $data ): ?object {
		global $wpdb;

		$uid       = self::generate_uid();
		$now       = current_time( 'mysql', true );
		$issued_on = ! empty( $data['issued_on'] ) ? (string) $data['issued_on'] : $now;
		$expires   = isset( $data['expires'] ) && is_string( $data['expires'] ) && '' !== $data['expires']
			? $data['expires']
			: null;

		$row = array(
			'uid'                => $uid,
			'badge_post_id'      => absint( $data['badge_post_id'] ),
			'recipient_identity' => (string) $data['recipient_identity'],
			'recipient_salt'     => (string) $data['recipient_salt'],
			'recipient_lookup'   => (string) $data['recipient_lookup'],
			'recipient_name'     => isset( $data['recipient_name'] ) ? (string) $data['recipient_name'] : '',
			'evidence_url'       => isset( $data['evidence_url'] ) ? (string) $data['evidence_url'] : '',
			'issued_on'          => $issued_on,
			'revoked'            => 0,
			'created_at'         => $now,
		);
		$formats = array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' );

		if ( null !== $expires ) {
			$row['expires'] = $expires;
			$formats[]      = '%s';
		}

		$inserted = $wpdb->insert( self::table_name(), $row, $formats );

		if ( false === $inserted ) {
			return null;
		}

		return self::find_by_uid( $uid );
	}

	/**
	 * Find an assertion by public uid.
	 */
	public static function find_by_uid( string $uid ): ?object {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE uid = %s LIMIT 1',
				self::table_name(),
				$uid
			)
		);

		return $row instanceof \stdClass ? $row : null;
	}

	/**
	 * Find non-revoked assertions for a lookup HMAC.
	 *
	 * @return list<object>
	 */
	public static function find_by_lookup( string $lookup_hash ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE recipient_lookup = %s AND revoked = 0 ORDER BY issued_on DESC',
				self::table_name(),
				$lookup_hash
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$rows,
				static function ( $row ): bool {
					return $row instanceof \stdClass;
				}
			)
		);
	}

	/**
	 * Whether a non-revoked assertion already exists for this badge and recipient lookup hash.
	 */
	public static function exists_for_badge_and_lookup( int $badge_post_id, string $lookup_hash ): bool {
		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE badge_post_id = %d AND recipient_lookup = %s AND revoked = 0',
				self::table_name(),
				$badge_post_id,
				$lookup_hash
			)
		);

		return $count > 0;
	}

	/**
	 * Paginated assertion list for admin.
	 *
	 * @return array{items: list<object>, total: int}
	 */
	public static function list_assertions( int $page = 1, int $per_page = 20, int $badge_post_id = 0 ): array {
		global $wpdb;

		$table    = self::table_name();
		$page     = max( 1, $page );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		if ( $badge_post_id > 0 ) {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE badge_post_id = %d',
					$table,
					$badge_post_id
				)
			);
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE badge_post_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$badge_post_id,
					$per_page,
					$offset
				)
			);
		} else {
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i',
					$table
				)
			);
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$table,
					$per_page,
					$offset
				)
			);
		}

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$items = array_values(
			array_filter(
				$rows,
				static function ( $row ): bool {
					return $row instanceof \stdClass;
				}
			)
		);

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Mark an assertion as revoked.
	 */
	public static function revoke( string $uid, string $reason = '' ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			self::table_name(),
			array(
				'revoked'        => 1,
				'revoked_reason' => $reason,
			),
			array( 'uid' => $uid ),
			array( '%d', '%s' ),
			array( '%s' )
		);

		return false !== $updated;
	}

	/**
	 * Restore a revoked assertion to active.
	 */
	public static function unrevoke( string $uid ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			self::table_name(),
			array(
				'revoked'        => 0,
				'revoked_reason' => '',
			),
			array(
				'uid'     => $uid,
				'revoked' => 1,
			),
			array( '%d', '%s' ),
			array( '%s', '%d' )
		);

		return false !== $updated && $updated > 0;
	}

	/**
	 * Permanently delete an assertion. Only succeeds when the assertion is revoked.
	 */
	public static function delete_revoked( string $uid ): bool {
		global $wpdb;

		$deleted = $wpdb->delete(
			self::table_name(),
			array(
				'uid'     => $uid,
				'revoked' => 1,
			),
			array( '%s', '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Public URL for hosted assertion JSON.
	 */
	public static function json_url( string $uid ): string {
		return home_url( '/ob/assertions/' . rawurlencode( $uid ) . '.json' );
	}

	/**
	 * Public attestation HTML URL.
	 */
	public static function attestation_url( string $uid ): string {
		return home_url( '/badges/assertion/' . rawurlencode( $uid ) . '/' );
	}

	/**
	 * Build BadgeAssertion array for JSON output.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function to_open_badges( object $assertion ): ?array {
		if ( ! empty( $assertion->revoked ) ) {
			return null;
		}

		$badge_id = (int) $assertion->badge_post_id;
		$badge    = Badge_Class::to_open_badges( $badge_id );

		if ( null === $badge ) {
			return null;
		}

		$issued_ts = strtotime( (string) $assertion->issued_on . ' UTC' );
		if ( false === $issued_ts ) {
			$issued_ts = time();
		}

		$data = array(
			'uid'       => (string) $assertion->uid,
			'recipient' => array(
				'type'     => 'email',
				'hashed'   => true,
				'salt'     => (string) $assertion->recipient_salt,
				'identity' => (string) $assertion->recipient_identity,
			),
			'badge'     => Badge_Class::json_url( $badge_id ),
			'verify'    => array(
				'type' => 'hosted',
				'url'  => self::json_url( (string) $assertion->uid ),
			),
			'issuedOn'  => $issued_ts,
		);

		$image = Badge_Class::image_url( $badge_id );
		if ( '' !== $image ) {
			$data['image'] = $image;
		}

		$evidence = (string) ( $assertion->evidence_url ?? '' );
		if ( '' !== $evidence ) {
			$data['evidence'] = $evidence;
		}

		if ( ! empty( $assertion->expires ) ) {
			$expires_ts = strtotime( (string) $assertion->expires . ' UTC' );
			if ( false !== $expires_ts ) {
				$data['expires'] = $expires_ts;
			}
		}

		return $data;
	}
}
