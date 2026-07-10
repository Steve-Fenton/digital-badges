<?php
/**
 * Main plugin bootstrap.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get the singleton instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load dependencies.
	 */
	private function includes(): void {
		require_once DIGITAL_BADGES_PATH . 'includes/class-post-types.php';
		require_once DIGITAL_BADGES_PATH . 'admin/class-admin.php';
		require_once DIGITAL_BADGES_PATH . 'public/class-public.php';
	}

	/**
	 * Register hooks.
	 */
	private function hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( Post_Types::class, 'register' ) );

		if ( is_admin() ) {
			Admin::init();
		}

		Public_Facing::init();
	}

	/**
	 * Load plugin translations.
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'digital-badges',
			false,
			dirname( plugin_basename( DIGITAL_BADGES_FILE ) ) . '/languages'
		);
	}
}
