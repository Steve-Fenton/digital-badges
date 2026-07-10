<?php
/**
 * Admin-facing functionality.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin hooks and assets.
 */
final class Admin {

	/**
	 * Wire admin hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public static function register_menu(): void {
		add_submenu_page(
			'edit.php?post_type=db_badge',
			__( 'Settings', 'digital-badges' ),
			__( 'Settings', 'digital-badges' ),
			'manage_options',
			'digital-badges-settings',
			array( self::class, 'render_settings_page' )
		);
	}

	/**
	 * Render the settings page stub.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Digital Badges settings will live here.', 'digital-badges' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Enqueue admin CSS/JS.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen || 'db_badge' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'digital-badges-admin',
			DIGITAL_BADGES_URL . 'admin/css/admin.css',
			array(),
			DIGITAL_BADGES_VERSION
		);

		wp_enqueue_script(
			'digital-badges-admin',
			DIGITAL_BADGES_URL . 'admin/js/admin.js',
			array(),
			DIGITAL_BADGES_VERSION,
			true
		);
	}
}
