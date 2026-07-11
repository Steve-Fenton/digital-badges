<?php
/**
 * Main plugin bootstrap.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

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
		require_once FENDIGIBADGE_PATH . 'includes/class-post-types.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-identity.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-issuer.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-badge-class.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-assertion-repository.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-csv-issuer.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-linkedin.php';
		require_once FENDIGIBADGE_PATH . 'includes/class-ob-endpoints.php';
		require_once FENDIGIBADGE_PATH . 'admin/class-admin.php';
		require_once FENDIGIBADGE_PATH . 'public/class-public.php';
	}

	/**
	 * Register hooks.
	 */
	private function hooks(): void {
		add_action( 'init', array( Post_Types::class, 'register' ) );
		add_action( 'init', array( $this, 'maybe_upgrade_schema' ) );

		Ob_Endpoints::init();

		if ( is_admin() ) {
			Admin::init();
		}

		Public_Facing::init();
	}

	/**
	 * Ensure DB table and lookup secret exist after updates.
	 */
	public function maybe_upgrade_schema(): void {
		$stored_version = get_option( 'fendigibadge_db_version', '' );

		if ( FENDIGIBADGE_VERSION === $stored_version ) {
			return;
		}

		Assertion_Repository::create_table();
		Identity::ensure_secret();
		Ob_Endpoints::add_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( 'fendigibadge_db_version', FENDIGIBADGE_VERSION, false );
	}
}
