<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'fendigibadge_issuer' );
delete_option( 'fendigibadge_find_page_id' );
delete_option( 'fendigibadge_lookup_secret' );
delete_option( 'fendigibadge_db_version' );

// Legacy option keys (pre-fendigibadge prefix).
delete_option( 'fenton_digital_badges_issuer' );
delete_option( 'fenton_digital_badges_find_page_id' );
delete_option( 'fenton_digital_badges_lookup_secret' );
delete_option( 'fenton_digital_badges_db_version' );

$fendigibadge_tables = array(
	$wpdb->prefix . 'fendigibadge_assertions',
	$wpdb->prefix . 'db_assertions',
);

foreach ( $fendigibadge_tables as $fendigibadge_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table teardown on uninstall.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $fendigibadge_table ) );
}

delete_post_meta_by_key( '_fendigibadge_criteria_url' );
delete_post_meta_by_key( '_fendigibadge_tags' );
delete_post_meta_by_key( '_db_criteria_url' );
delete_post_meta_by_key( '_db_tags' );
