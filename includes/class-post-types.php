<?php
/**
 * Custom post types and taxonomies.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers badge-related content types.
 */
final class Post_Types {

	/**
	 * Register post types and taxonomies.
	 */
	public static function register(): void {
		register_post_type(
			'db_badge',
			array(
				'labels'              => array(
					'name'          => __( 'Badges', 'fenton-digital-badges' ),
					'singular_name' => __( 'Badge', 'fenton-digital-badges' ),
					'add_new_item'  => __( 'Add New Badge', 'fenton-digital-badges' ),
					'edit_item'     => __( 'Edit Badge', 'fenton-digital-badges' ),
					'view_item'     => __( 'View Badge', 'fenton-digital-badges' ),
					'search_items'  => __( 'Search Badges', 'fenton-digital-badges' ),
					'not_found'     => __( 'No badges found.', 'fenton-digital-badges' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-awards',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				// Avoid clashing with public routes under /badges/assertion/, /badges/find/, etc.
				'rewrite'             => array( 'slug' => 'badge' ),
			)
		);
	}
}
