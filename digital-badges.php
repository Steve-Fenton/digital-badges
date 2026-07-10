<?php
/**
 * Plugin Name:       Digital Badges
 * Plugin URI:        https://github.com/fenton/digital-badges
 * Description:       Issue, manage, and display digital badges.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Fenton
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       digital-badges
 * Domain Path:       /languages
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DIGITAL_BADGES_VERSION', '0.1.0' );
define( 'DIGITAL_BADGES_FILE', __FILE__ );
define( 'DIGITAL_BADGES_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIGITAL_BADGES_URL', plugin_dir_url( __FILE__ ) );

require_once DIGITAL_BADGES_PATH . 'includes/class-plugin.php';
require_once DIGITAL_BADGES_PATH . 'includes/class-activator.php';
require_once DIGITAL_BADGES_PATH . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, array( \DigitalBadges\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \DigitalBadges\Deactivator::class, 'deactivate' ) );

/**
 * Boot the plugin.
 */
function digital_badges(): \DigitalBadges\Plugin {
	return \DigitalBadges\Plugin::instance();
}

digital_badges();
