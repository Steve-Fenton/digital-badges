<?php
/**
 * Plugin Name:       Fenton Digital Badges
 * Plugin URI:        https://github.com/Steve-Fenton/digital-badges
 * Description:       Issue, manage, and display digital badges.
 * Version:           0.1.19
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Fenton
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fenton-digital-badges
 * Domain Path:       /languages
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FENDIGIBADGE_VERSION', '0.1.19' );
define( 'FENDIGIBADGE_FILE', __FILE__ );
define( 'FENDIGIBADGE_PATH', plugin_dir_path( __FILE__ ) );
define( 'FENDIGIBADGE_URL', plugin_dir_url( __FILE__ ) );

// Legacy constant aliases (pre-fendigibadge prefix).
if ( ! defined( 'FENTON_DIGITAL_BADGES_VERSION' ) ) {
	define( 'FENTON_DIGITAL_BADGES_VERSION', FENDIGIBADGE_VERSION );
}
if ( ! defined( 'FENTON_DIGITAL_BADGES_FILE' ) ) {
	define( 'FENTON_DIGITAL_BADGES_FILE', FENDIGIBADGE_FILE );
}
if ( ! defined( 'FENTON_DIGITAL_BADGES_PATH' ) ) {
	define( 'FENTON_DIGITAL_BADGES_PATH', FENDIGIBADGE_PATH );
}
if ( ! defined( 'FENTON_DIGITAL_BADGES_URL' ) ) {
	define( 'FENTON_DIGITAL_BADGES_URL', FENDIGIBADGE_URL );
}

require_once FENDIGIBADGE_PATH . 'includes/class-plugin.php';
require_once FENDIGIBADGE_PATH . 'includes/class-activator.php';
require_once FENDIGIBADGE_PATH . 'includes/class-deactivator.php';

register_activation_hook( __FILE__, array( \FentonDigitalBadges\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \FentonDigitalBadges\Deactivator::class, 'deactivate' ) );

/**
 * Boot the plugin.
 */
function fendigibadge(): \FentonDigitalBadges\Plugin {
	return \FentonDigitalBadges\Plugin::instance();
}

/**
 * Legacy bootstrap alias.
 */
function fenton_digital_badges(): \FentonDigitalBadges\Plugin {
	return fendigibadge();
}

fendigibadge();
