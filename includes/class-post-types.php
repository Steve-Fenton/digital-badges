<?php
/**
 * Custom post types and taxonomies.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

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
					'name'          => __( 'Badges', 'digital-badges' ),
					'singular_name' => __( 'Badge', 'digital-badges' ),
					'add_new_item'  => __( 'Add New Badge', 'digital-badges' ),
					'edit_item'     => __( 'Edit Badge', 'digital-badges' ),
					'view_item'     => __( 'View Badge', 'digital-badges' ),
					'search_items'  => __( 'Search Badges', 'digital-badges' ),
					'not_found'     => __( 'No badges found.', 'digital-badges' ),
				),
				'public'              => true,
				'has_archive'         => true,
				'show_in_rest'        => true,
				'menu_icon'           => 'dashicons-awards',
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'rewrite'             => array( 'slug' => 'badges' ),
			)
		);
	}
}
