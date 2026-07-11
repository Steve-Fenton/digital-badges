<?php
/**
 * One-time data migrations for prefix renames.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrates legacy identifiers to the fendigibadge prefix without data loss.
 */
final class Upgrade {

	/**
	 * Schema version that introduced the fendigibadge prefix rename.
	 *
	 * Kept separate from FENDIGIBADGE_VERSION so package.sh can bump the
	 * plugin version without changing when this one-time migration applies.
	 */
	public const PREFIX_RENAME_VERSION = '0.1.19';

	/**
	 * Legacy post type slug.
	 */
	public const LEGACY_POST_TYPE = 'db_badge';

	/**
	 * Legacy assertions table suffix.
	 */
	public const LEGACY_TABLE_SUFFIX = 'db_assertions';

	/**
	 * Whether the stored DB version still needs the prefix rename migration.
	 */
	public static function needs_prefix_rename( string $stored_version ): bool {
		if ( '' === $stored_version ) {
			return true;
		}

		return version_compare( $stored_version, self::PREFIX_RENAME_VERSION, '<' );
	}

	/**
	 * Run all prefix migrations that may be needed after an update.
	 */
	public static function migrate(): void {
		self::migrate_post_type();
		self::migrate_post_meta();
		self::migrate_options();
		self::migrate_assertions_table();
	}

	/**
	 * Rename stored badge posts from the legacy post type.
	 */
	private static function migrate_post_type(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_type' => 'fendigibadge_badge' ),
			array( 'post_type' => self::LEGACY_POST_TYPE ),
			array( '%s' ),
			array( '%s' )
		);
	}

	/**
	 * Rename badge meta keys to the new prefix.
	 */
	private static function migrate_post_meta(): void {
		global $wpdb;

		$map = array(
			'_db_criteria_url' => '_fendigibadge_criteria_url',
			'_db_tags'         => '_fendigibadge_tags',
		);

		foreach ( $map as $legacy => $current ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade.
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_key' => $current ),
				array( 'meta_key' => $legacy ),
				array( '%s' ),
				array( '%s' )
			);
		}
	}

	/**
	 * Copy legacy options to new keys, then remove the old keys.
	 */
	private static function migrate_options(): void {
		$map = array(
			'fenton_digital_badges_issuer'        => 'fendigibadge_issuer',
			'fenton_digital_badges_find_page_id'  => 'fendigibadge_find_page_id',
			'fenton_digital_badges_lookup_secret' => 'fendigibadge_lookup_secret',
			'fenton_digital_badges_db_version'    => 'fendigibadge_db_version',
		);

		foreach ( $map as $legacy => $current ) {
			if ( false !== get_option( $current, false ) ) {
				delete_option( $legacy );
				continue;
			}

			$legacy_value = get_option( $legacy, null );

			if ( null === $legacy_value ) {
				continue;
			}

			update_option( $current, $legacy_value, false );
			delete_option( $legacy );
		}
	}

	/**
	 * Rename the assertions table when the legacy name is still present.
	 */
	private static function migrate_assertions_table(): void {
		global $wpdb;

		$legacy  = $wpdb->prefix . self::LEGACY_TABLE_SUFFIX;
		$current = $wpdb->prefix . 'fendigibadge_assertions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade.
		$legacy_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time upgrade.
		$current_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $current ) );

		if ( $legacy !== $legacy_exists ) {
			return;
		}

		if ( $current === $current_exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- One-time table rename; identifiers cannot be placeholders.
		$wpdb->query( "RENAME TABLE `{$legacy}` TO `{$current}`" );
	}
}
