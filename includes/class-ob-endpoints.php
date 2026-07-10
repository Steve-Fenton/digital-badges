<?php
/**
 * Public Open Badges JSON endpoints and human pages.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrite rules and template_redirect handlers for OB + claim UI.
 */
final class Ob_Endpoints {

	/**
	 * Wire endpoint hooks.
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'register_query_vars' ) );
		add_action( 'template_redirect', array( self::class, 'handle_request' ) );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Add body classes on plugin public pages.
	 *
	 * @param list<string> $classes Body classes.
	 * @return list<string>
	 */
	public static function body_class( array $classes ): array {
		$type = get_query_var( 'db_ob' );

		if ( ! is_string( $type ) || '' === $type ) {
			return $classes;
		}

		$classes[] = 'digital-badges';
		$classes[] = 'digital-badges--' . sanitize_html_class( $type );

		return $classes;
	}

	/**
	 * Register rewrite rules.
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule( '^ob/issuer\.json$', 'index.php?db_ob=issuer', 'top' );
		add_rewrite_rule( '^ob/badges/([0-9]+)\.json$', 'index.php?db_ob=badge&db_ob_id=$matches[1]', 'top' );
		add_rewrite_rule( '^ob/assertions/([^/]+)\.json$', 'index.php?db_ob=assertion&db_ob_uid=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/assertion/([^/]+)/?$', 'index.php?db_ob=attestation&db_ob_uid=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/embed/([^/]+)/?$', 'index.php?db_ob=embed&db_ob_uid=$matches[1]', 'top' );
		add_rewrite_rule( '^badges/find/?$', 'index.php?db_ob=find', 'top' );
	}

	/**
	 * Register custom query vars.
	 *
	 * @param list<string> $vars Query vars.
	 * @return list<string>
	 */
	public static function register_query_vars( array $vars ): array {
		$vars[] = 'db_ob';
		$vars[] = 'db_ob_id';
		$vars[] = 'db_ob_uid';

		return $vars;
	}

	/**
	 * Dispatch endpoint requests.
	 */
	public static function handle_request(): void {
		$type = get_query_var( 'db_ob' );

		if ( ! is_string( $type ) || '' === $type ) {
			return;
		}

		switch ( $type ) {
			case 'issuer':
				self::serve_issuer_json();
				break;
			case 'badge':
				self::serve_badge_json();
				break;
			case 'assertion':
				self::serve_assertion_json();
				break;
			case 'attestation':
				self::serve_attestation_page();
				break;
			case 'embed':
				self::serve_embed_page();
				break;
			case 'find':
				self::serve_find_page();
				break;
		}
	}

	/**
	 * Serve IssuerOrganization JSON.
	 */
	private static function serve_issuer_json(): void {
		if ( ! Issuer::is_configured() ) {
			self::json_error( 404, array( 'error' => 'Issuer not configured' ) );
		}

		self::json_response( Issuer::to_open_badges() );
	}

	/**
	 * Serve BadgeClass JSON.
	 */
	private static function serve_badge_json(): void {
		$id   = absint( get_query_var( 'db_ob_id' ) );
		$data = Badge_Class::to_open_badges( $id );

		if ( null === $data ) {
			self::json_error( 404, array( 'error' => 'Badge not found' ) );
		}

		self::json_response( $data );
	}

	/**
	 * Serve BadgeAssertion JSON (or 410 when revoked).
	 */
	private static function serve_assertion_json(): void {
		$uid = sanitize_text_field( (string) get_query_var( 'db_ob_uid' ) );
		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row ) {
			self::json_error( 404, array( 'error' => 'Assertion not found' ) );
		}

		if ( ! empty( $row->revoked ) ) {
			status_header( 410 );
			nocache_headers();
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( array( 'revoked' => true ) );
			exit;
		}

		$data = Assertion_Repository::to_open_badges( $row );

		if ( null === $data ) {
			self::json_error( 404, array( 'error' => 'Assertion not available' ) );
		}

		self::json_response( $data );
	}

	/**
	 * Serve public attestation HTML page.
	 */
	private static function serve_attestation_page(): void {
		$uid = sanitize_text_field( (string) get_query_var( 'db_ob_uid' ) );
		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row || ! empty( $row->revoked ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		$badge = get_post( (int) $row->badge_post_id );

		if ( ! $badge || 'db_badge' !== $badge->post_type ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		$issuer          = Issuer::get();
		$linkedin_url    = LinkedIn::certification_url( $row, $badge );
		$attestation_url = Assertion_Repository::attestation_url( $uid );
		$embed_url       = Assertion_Repository::embed_url( $uid );
		$json_url        = Assertion_Repository::json_url( $uid );
		$image_url       = Badge_Class::image_url( (int) $badge->ID );
		$embed_code      = sprintf(
			'<iframe src="%s" title="%s" width="180" height="220" frameborder="0" loading="lazy"></iframe>',
			esc_url( $embed_url ),
			esc_attr( get_the_title( $badge ) )
		);

		$assertion_data = Assertion_Repository::to_open_badges( $row );
		$assertion_json = is_array( $assertion_data )
			? (string) wp_json_encode( $assertion_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
			: '';

		self::render_view(
			'attestation',
			array(
				'assertion'       => $row,
				'badge'           => $badge,
				'issuer'          => $issuer,
				'linkedin_url'    => $linkedin_url,
				'attestation_url' => $attestation_url,
				'embed_url'       => $embed_url,
				'json_url'        => $json_url,
				'image_url'       => $image_url,
				'embed_code'      => $embed_code,
				'assertion_json'  => $assertion_json,
				'share_text'      => sprintf(
					/* translators: 1: badge name, 2: issuer name */
					__( 'I earned the %1$s badge from %2$s', 'digital-badges' ),
					get_the_title( $badge ),
					$issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' )
				),
			)
		);
	}

	/**
	 * Serve embeddable badge HTML.
	 */
	private static function serve_embed_page(): void {
		$uid = sanitize_text_field( (string) get_query_var( 'db_ob_uid' ) );
		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row || ! empty( $row->revoked ) ) {
			status_header( 404 );
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			echo esc_html__( 'Badge not found.', 'digital-badges' );
			exit;
		}

		$badge = get_post( (int) $row->badge_post_id );

		if ( ! $badge || 'db_badge' !== $badge->post_type ) {
			status_header( 404 );
			nocache_headers();
			header( 'Content-Type: text/html; charset=utf-8' );
			echo esc_html__( 'Badge not found.', 'digital-badges' );
			exit;
		}

		// Allow this minimal document to be embedded on other sites.
		header( 'Content-Security-Policy: frame-ancestors *' );

		self::render_view(
			'embed',
			array(
				'assertion'       => $row,
				'badge'           => $badge,
				'attestation_url' => Assertion_Repository::attestation_url( $uid ),
				'image_url'       => Badge_Class::image_url( (int) $badge->ID ),
			),
			false
		);
	}

	/**
	 * Serve find-badges page (also used by shortcode via Public_Facing).
	 */
	private static function serve_find_page(): void {
		$results = array();
		$error   = '';
		$searched = false;

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$searched = true;
			$results  = self::process_lookup_request( $error );
		}

		self::render_view(
			'find',
			array(
				'results'  => $results,
				'error'    => $error,
				'searched' => $searched,
			)
		);
	}

	/**
	 * Process email lookup POST (shared by page + shortcode).
	 *
	 * @param string $error Error message by reference.
	 * @return list<object>
	 */
	public static function process_lookup_request( string &$error ): array {
		$error = '';

		$nonce = isset( $_POST['db_find_nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['db_find_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'db_find_badges' ) ) {
			$error = __( 'Invalid request. Please try again.', 'digital-badges' );
			return array();
		}

		$email = isset( $_POST['db_email'] ) ? sanitize_email( wp_unslash( (string) $_POST['db_email'] ) ) : '';

		if ( ! Identity::is_valid_email( $email ) ) {
			$error = __( 'Please enter a valid email address.', 'digital-badges' );
			return array();
		}

		// Simple transient rate limit by IP.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'db_find_' . md5( $ip );
		$hits = (int) get_transient( $key );

		if ( $hits >= 20 ) {
			$error = __( 'Too many lookups. Please wait a few minutes and try again.', 'digital-badges' );
			return array();
		}

		set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

		$lookup = Identity::lookup_hash( $email );
		// Discard plaintext email after hashing.
		unset( $email );

		return Assertion_Repository::find_by_lookup( $lookup );
	}

	/**
	 * Render a view template and exit.
	 *
	 * @param string               $view View name.
	 * @param array<string, mixed> $vars Template vars.
	 * @param bool                 $use_theme_chrome Whether to wrap with get_header/footer.
	 */
	private static function render_view( string $view, array $vars, bool $use_theme_chrome = true ): void {
		$path = DIGITAL_BADGES_PATH . 'public/views/' . $view . '.php';

		if ( ! is_readable( $path ) ) {
			status_header( 500 );
			exit;
		}

		self::prepare_front_end_request( $view, $vars );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template vars.
		extract( $vars, EXTR_SKIP );

		status_header( 200 );
		nocache_headers();

		if ( $use_theme_chrome && 'embed' !== $view ) {
			Public_Facing::enqueue_assets();
			// Print assets directly so theme/optimizers cannot drop them on these custom routes.
			add_action( 'wp_head', array( self::class, 'print_public_styles' ), 5 );
			add_action( 'wp_footer', array( self::class, 'print_public_scripts' ), 20 );
			get_header();
			include $path;
			get_footer();
		} else {
			include $path;
		}

		exit;
	}

	/**
	 * Print public CSS for attestation/find pages.
	 */
	public static function print_public_styles(): void {
		$href = DIGITAL_BADGES_URL . 'public/css/public.css?ver=' . rawurlencode( DIGITAL_BADGES_VERSION );
		printf(
			"<link rel='stylesheet' id='digital-badges-public-css' href='%s' media='all' />\n",
			esc_url( $href )
		);
	}

	/**
	 * Print public JS for attestation/find pages.
	 */
	public static function print_public_scripts(): void {
		$src = DIGITAL_BADGES_URL . 'public/js/public.js?ver=' . rawurlencode( DIGITAL_BADGES_VERSION );
		printf(
			"<script id='digital-badges-public-js' src='%s'></script>\n",
			esc_url( $src )
		);
	}

	/**
	 * Stop themes treating badge pages as another post/home, and set a sensible title.
	 *
	 * @param string               $view View name.
	 * @param array<string, mixed> $vars Template vars.
	 */
	private static function prepare_front_end_request( string $view, array $vars ): void {
		global $wp_query, $post;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->init_query_flags();
			$wp_query->is_404      = false;
			$wp_query->is_home     = false;
			$wp_query->is_front_page = false;
			$wp_query->is_singular = false;
			$wp_query->is_single   = false;
			$wp_query->is_page     = false;
			$wp_query->is_archive  = false;
			$wp_query->posts       = array();
			$wp_query->post_count  = 0;
			$wp_query->queried_object = null;
			$wp_query->queried_object_id = 0;
			$wp_query->post = null;
		}

		$post = null;

		// Avoid leaking another post's canonical/oEmbed into the head.
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

		$title = '';
		$canonical = '';

		if ( isset( $vars['badge'] ) && $vars['badge'] instanceof \WP_Post ) {
			$title = get_the_title( $vars['badge'] );
		} elseif ( 'find' === $view ) {
			$title = __( 'Find your badges', 'digital-badges' );
			$canonical = home_url( '/badges/find/' );
		}

		if ( isset( $vars['attestation_url'] ) && is_string( $vars['attestation_url'] ) ) {
			$canonical = $vars['attestation_url'];
		}

		if ( '' !== $title ) {
			add_filter(
				'pre_get_document_title',
				static function () use ( $title ): string {
					return $title;
				},
				999
			);
		}

		if ( '' !== $canonical ) {
			add_action(
				'wp_head',
				static function () use ( $canonical ): void {
					printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
				},
				2
			);
		}
	}

	/**
	 * Output JSON and exit.
	 *
	 * @param array<string, mixed> $data Payload.
	 */
	private static function json_response( array $data ): void {
		status_header( 200 );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Output JSON error and exit.
	 *
	 * @param int                  $status HTTP status.
	 * @param array<string, mixed> $data Payload.
	 */
	private static function json_error( int $status, array $data ): void {
		status_header( $status );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		echo wp_json_encode( $data );
		exit;
	}
}
