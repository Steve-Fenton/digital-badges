<?php
/**
 * Public-facing functionality.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end hooks and assets.
 */
final class Public_Facing {

	/**
	 * Option key for the optional Find badges WordPress page.
	 */
	public const FIND_PAGE_OPTION = 'fendigibadge_find_page_id';

	/**
	 * Wire public hooks.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_prevent_find_cache' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_head', array( self::class, 'maybe_output_og_tags' ), 5 );
		add_shortcode( 'fendigibadge', array( self::class, 'render_badge_shortcode' ) );
		add_shortcode( 'fendigibadge_find', array( self::class, 'render_find_shortcode' ) );
		// Legacy shortcode aliases (pre-fendigibadge prefix).
		add_shortcode( 'fenton_digital_badge', array( self::class, 'render_badge_shortcode' ) );
		add_shortcode( 'fenton_digital_badges_find', array( self::class, 'render_find_shortcode' ) );
	}

	/**
	 * Published page selected to host /badges/find/, if any.
	 */
	public static function get_find_page(): ?\WP_Post {
		$page_id = absint( get_option( self::FIND_PAGE_OPTION, 0 ) );

		if ( $page_id <= 0 ) {
			return null;
		}

		$page = get_post( $page_id );

		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			return null;
		}

		return $page;
	}

	/**
	 * Resolve a public view path, allowing theme overrides.
	 *
	 * Themes may provide:
	 * - fendigibadge/{view}.php
	 * - fendigibadge-{view}.php
	 * - fenton-digital-badges/{view}.php (legacy)
	 * - fenton-digital-badges-{view}.php (legacy)
	 *
	 * @param string $view View name (e.g. find, attestation).
	 */
	public static function locate_view( string $view ): string {
		$view = sanitize_file_name( $view );

		$theme = locate_template(
			array(
				'fendigibadge/' . $view . '.php',
				'fendigibadge-' . $view . '.php',
				'fenton-digital-badges/' . $view . '.php',
				'fenton-digital-badges-' . $view . '.php',
			)
		);

		if ( is_string( $theme ) && '' !== $theme ) {
			return $theme;
		}

		return FENDIGIBADGE_PATH . 'public/views/' . $view . '.php';
	}

	/**
	 * Avoid caching pages that embed the find-badges form (nonce must stay fresh).
	 */
	public static function maybe_prevent_find_cache(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$find_page = self::get_find_page();
		$is_find_page = $find_page instanceof \WP_Post && (int) $find_page->ID === (int) $post->ID;

		$has_find_shortcode = has_shortcode( $post->post_content, 'fendigibadge_find' )
			|| has_shortcode( $post->post_content, 'fenton_digital_badges_find' );

		if ( ! $is_find_page && ! $has_find_shortcode ) {
			return;
		}

		Ob_Endpoints::prevent_caching();
	}

	/**
	 * Enqueue front-end CSS/JS.
	 */
	public static function enqueue_assets(): void {
		wp_enqueue_style(
			'fendigibadge-public',
			FENDIGIBADGE_URL . 'public/css/public.css',
			array(),
			FENDIGIBADGE_VERSION
		);

		wp_enqueue_script(
			'fendigibadge-public',
			FENDIGIBADGE_URL . 'public/js/public.js',
			array(),
			FENDIGIBADGE_VERSION,
			true
		);
	}

	/**
	 * Output Open Graph tags on attestation pages when theme chrome is used.
	 */
	public static function maybe_output_og_tags(): void {
		if ( 'attestation' !== get_query_var( 'fendigibadge_ob' ) ) {
			return;
		}

		$uid = sanitize_text_field( (string) get_query_var( 'fendigibadge_ob_uid' ) );
		$row = Assertion_Repository::find_by_uid( $uid );

		if ( null === $row || ! empty( $row->revoked ) ) {
			return;
		}

		$badge = get_post( (int) $row->badge_post_id );

		if ( ! $badge ) {
			return;
		}

		$title       = get_the_title( $badge );
		$description = Badge_Class::description( $badge );
		$url         = Assertion_Repository::attestation_url( $uid );
		$image       = Badge_Class::image_url( (int) $badge->ID );

		printf( '<meta property="og:type" content="website" />' . "\n" );
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );

		if ( '' !== $image ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
		}

		printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
	}

	/**
	 * Shortcode: [fendigibadge id="123"]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function render_badge_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'fendigibadge'
		);

		$badge_id = absint( $atts['id'] );

		if ( ! $badge_id ) {
			return '';
		}

		$badge = get_post( $badge_id );

		if ( ! $badge || Post_Types::BADGE !== $badge->post_type || 'publish' !== $badge->post_status ) {
			return '';
		}

		$image = Badge_Class::image_url( $badge_id );
		$html  = '<div class="fendigibadge-badge" data-badge-id="' . esc_attr( (string) $badge_id ) . '">';

		if ( '' !== $image ) {
			$html .= '<img class="fendigibadge-badge__image" src="' . esc_url( $image ) . '" alt="' . esc_attr( get_the_title( $badge ) ) . '" />';
		}

		$html .= '<h3 class="fendigibadge-badge__title">' . esc_html( get_the_title( $badge ) ) . '</h3>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Shortcode: [fendigibadge_find]
	 */
	public static function render_find_shortcode(): string {
		Ob_Endpoints::prevent_caching();

		$error    = '';
		$searched = false;

		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';

		if ( 'POST' === $request_method ) {
			$searched = true;
			Ob_Endpoints::process_lookup_request( $error );
		}

		// Prefer the stable /badges/find/ endpoint when this request is that route
		// (including when a selected page supplies the template).
		if ( 'find' === get_query_var( 'fendigibadge_ob' ) ) {
			$fendigibadge_form_action = home_url( '/badges/find/' );
		} else {
			$fendigibadge_form_action = get_permalink();
			if ( ! is_string( $fendigibadge_form_action ) || '' === $fendigibadge_form_action ) {
				$fendigibadge_form_action = home_url( '/badges/find/' );
			}
		}

		ob_start();
		$fendigibadge_show_header = false;
		$vars = compact( 'error', 'searched', 'fendigibadge_form_action', 'fendigibadge_show_header' );
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template vars.
		extract( $vars, EXTR_SKIP );
		include self::locate_view( 'find' );

		return (string) ob_get_clean();
	}
}
