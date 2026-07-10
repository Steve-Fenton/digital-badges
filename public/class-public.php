<?php
/**
 * Public-facing functionality.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end hooks and assets.
 */
final class Public_Facing {

	/**
	 * Wire public hooks.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_action( 'wp_head', array( self::class, 'maybe_output_og_tags' ), 5 );
		add_shortcode( 'digital_badge', array( self::class, 'render_badge_shortcode' ) );
		add_shortcode( 'digital_badges_find', array( self::class, 'render_find_shortcode' ) );
	}

	/**
	 * Enqueue front-end CSS/JS.
	 */
	public static function enqueue_assets(): void {
		wp_enqueue_style(
			'digital-badges-public',
			DIGITAL_BADGES_URL . 'public/css/public.css',
			array(),
			DIGITAL_BADGES_VERSION
		);

		wp_enqueue_script(
			'digital-badges-public',
			DIGITAL_BADGES_URL . 'public/js/public.js',
			array(),
			DIGITAL_BADGES_VERSION,
			true
		);
	}

	/**
	 * Output Open Graph tags on attestation pages when theme chrome is used.
	 */
	public static function maybe_output_og_tags(): void {
		if ( 'attestation' !== get_query_var( 'db_ob' ) ) {
			return;
		}

		$uid = sanitize_text_field( (string) get_query_var( 'db_ob_uid' ) );
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
	 * Shortcode: [digital_badge id="123"]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function render_badge_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'digital_badge'
		);

		$badge_id = absint( $atts['id'] );

		if ( ! $badge_id ) {
			return '';
		}

		$badge = get_post( $badge_id );

		if ( ! $badge || 'db_badge' !== $badge->post_type || 'publish' !== $badge->post_status ) {
			return '';
		}

		$image = Badge_Class::image_url( $badge_id );
		$html  = '<div class="digital-badge" data-badge-id="' . esc_attr( (string) $badge_id ) . '">';

		if ( '' !== $image ) {
			$html .= '<img class="digital-badge__image" src="' . esc_url( $image ) . '" alt="' . esc_attr( get_the_title( $badge ) ) . '" />';
		}

		$html .= '<h3 class="digital-badge__title">' . esc_html( get_the_title( $badge ) ) . '</h3>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Shortcode: [digital_badges_find]
	 */
	public static function render_find_shortcode(): string {
		$results  = array();
		$error    = '';
		$searched = false;

		if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['db_find_nonce'] ) ) {
			$searched = true;
			$results  = Ob_Endpoints::process_lookup_request( $error );
		}

		ob_start();
		$vars = compact( 'results', 'error', 'searched' );
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- scoped template vars.
		extract( $vars, EXTR_SKIP );
		include DIGITAL_BADGES_PATH . 'public/views/find.php';

		return (string) ob_get_clean();
	}
}
