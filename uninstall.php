<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'digital_badges_issuer' );
delete_option( 'digital_badges_lookup_secret' );
delete_option( 'digital_badges_db_version' );

$table = $wpdb->prefix . 'db_assertions';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is constructed from prefix.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

delete_post_meta_by_key( '_db_criteria_url' );
delete_post_meta_by_key( '_db_tags' );
