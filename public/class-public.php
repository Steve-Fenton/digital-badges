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
		add_shortcode( 'digital_badge', array( self::class, 'render_badge_shortcode' ) );
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
	 * Shortcode stub: [digital_badge id="123"]
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

		return sprintf(
			'<div class="digital-badge" data-badge-id="%d"><h3 class="digital-badge__title">%s</h3></div>',
			$badge_id,
			esc_html( get_the_title( $badge ) )
		);
	}
}
