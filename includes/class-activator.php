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
		Post_Types::register();
		flush_rewrite_rules();
	}
}
