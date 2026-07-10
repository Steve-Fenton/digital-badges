<?php
/**
 * Fired during plugin activation.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation tasks.
 */
final class Activator {

	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		require_once DIGITAL_BADGES_PATH . 'includes/class-post-types.php';
		require_once DIGITAL_BADGES_PATH . 'includes/class-identity.php';
		require_once DIGITAL_BADGES_PATH . 'includes/class-assertion-repository.php';
		require_once DIGITAL_BADGES_PATH . 'includes/class-ob-endpoints.php';

		Post_Types::register();
		Assertion_Repository::create_table();
		Identity::ensure_secret();
		Ob_Endpoints::add_rewrite_rules();
		update_option( 'digital_badges_db_version', DIGITAL_BADGES_VERSION, false );
		flush_rewrite_rules();
	}
}
