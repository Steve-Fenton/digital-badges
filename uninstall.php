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

delete_option( 'fenton_digital_badges_issuer' );
delete_option( 'fenton_digital_badges_find_page_id' );
delete_option( 'fenton_digital_badges_lookup_secret' );
delete_option( 'fenton_digital_badges_db_version' );

$fenton_digital_badges_table = $wpdb->prefix . 'db_assertions';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table teardown on uninstall.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $fenton_digital_badges_table ) );

delete_post_meta_by_key( '_db_criteria_url' );
delete_post_meta_by_key( '_db_tags' );
