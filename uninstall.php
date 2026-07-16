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
delete_option( 'fendigibadge_attestation_page_id' );
delete_option( 'fendigibadge_lookup_secret' );
delete_option( 'fendigibadge_unsubscribed_emails' );
delete_option( 'fendigibadge_db_version' );

$fendigibadge_table = $wpdb->prefix . 'fendigibadge_assertions';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Custom table teardown on uninstall.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $fendigibadge_table ) );

delete_post_meta_by_key( '_fendigibadge_criteria_url' );
delete_post_meta_by_key( '_fendigibadge_earn_url' );
delete_post_meta_by_key( '_fendigibadge_tags' );
