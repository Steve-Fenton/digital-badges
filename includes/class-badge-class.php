<?php
/**
 * Open Badges BadgeClass helpers for db_badge posts.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds BadgeClass JSON and manages badge meta.
 */
final class Badge_Class {

	public const META_CRITERIA_URL = '_db_criteria_url';
	public const META_TAGS         = '_db_tags';

	/**
	 * Public URL for a hosted BadgeClass JSON document.
	 */
	public static function json_url( int $badge_post_id ): string {
		return home_url( '/ob/badges/' . $badge_post_id . '.json' );
	}

	/**
	 * Criteria URL for a badge (meta or permalink fallback).
	 */
	public static function criteria_url( int $badge_post_id ): string {
		$stored = get_post_meta( $badge_post_id, self::META_CRITERIA_URL, true );

		if ( is_string( $stored ) && '' !== $stored ) {
			return $stored;
		}

		$permalink = get_permalink( $badge_post_id );

		return is_string( $permalink ) ? $permalink : '';
	}

	/**
	 * Tags for a badge.
	 *
	 * @return list<string>
	 */
	public static function tags( int $badge_post_id ): array {
		$stored = get_post_meta( $badge_post_id, self::META_TAGS, true );

		if ( ! is_string( $stored ) || '' === trim( $stored ) ) {
			return array();
		}

		$parts = array_map( 'trim', explode( ',', $stored ) );

		return array_values(
			array_filter(
				$parts,
				static function ( string $tag ): bool {
					return '' !== $tag;
				}
			)
		);
	}

	/**
	 * Featured image URL for a badge.
	 */
	public static function image_url( int $badge_post_id ): string {
		$url = get_the_post_thumbnail_url( $badge_post_id, 'full' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Description text for a badge.
	 */
	public static function description( \WP_Post $badge ): string {
		$excerpt = trim( (string) $badge->post_excerpt );

		if ( '' !== $excerpt ) {
			return wp_strip_all_tags( $excerpt );
		}

		return wp_strip_all_tags( (string) $badge->post_content );
	}

	/**
	 * Whether a badge post can be issued (published + image).
	 */
	public static function is_issuable( int $badge_post_id ): bool {
		$badge = get_post( $badge_post_id );

		if ( ! $badge || 'db_badge' !== $badge->post_type || 'publish' !== $badge->post_status ) {
			return false;
		}

		return '' !== self::image_url( $badge_post_id ) && '' !== self::criteria_url( $badge_post_id );
	}

	/**
	 * Build BadgeClass array for JSON output.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function to_open_badges( int $badge_post_id ): ?array {
		$badge = get_post( $badge_post_id );

		if ( ! $badge || 'db_badge' !== $badge->post_type || 'publish' !== $badge->post_status ) {
			return null;
		}

		$image = self::image_url( $badge_post_id );
		$criteria = self::criteria_url( $badge_post_id );

		if ( '' === $image || '' === $criteria ) {
			return null;
		}

		$data = array(
			'name'        => get_the_title( $badge ),
			'description' => self::description( $badge ),
			'image'       => $image,
			'criteria'    => $criteria,
			'issuer'      => Issuer::json_url(),
		);

		$tags = self::tags( $badge_post_id );
		if ( $tags ) {
			$data['tags'] = $tags;
		}

		return $data;
	}

	/**
	 * Save badge meta from the edit screen.
	 */
	public static function save_meta( int $post_id ): void {
		if ( ! isset( $_POST['db_badge_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['db_badge_meta_nonce'] ) ), 'db_badge_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$criteria = isset( $_POST['db_criteria_url'] ) ? esc_url_raw( wp_unslash( (string) $_POST['db_criteria_url'] ) ) : '';
		$tags     = isset( $_POST['db_tags'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['db_tags'] ) ) : '';

		update_post_meta( $post_id, self::META_CRITERIA_URL, $criteria );
		update_post_meta( $post_id, self::META_TAGS, $tags );
	}
}
