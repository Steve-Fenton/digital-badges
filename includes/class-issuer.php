<?php
/**
 * Site-wide Open Badges issuer organization.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes issuer settings and builds IssuerOrganization JSON.
 */
final class Issuer {

	public const OPTION_KEY = 'fendigibadge_issuer';

	/**
	 * Default issuer option values.
	 *
	 * @return array{name: string, url: string, email: string, description: string, image_id: int, linkedin_organization_id: string, sending_email: string, sending_display_name: string, find_email: string, find_email_signoff: string}
	 */
	public static function defaults(): array {
		return array(
			'name'                     => '',
			'url'                      => home_url( '/' ),
			'email'                    => '',
			'description'              => '',
			'image_id'                 => 0,
			'linkedin_organization_id' => '',
			'sending_email'            => '',
			'sending_display_name'     => '',
			'find_email'               => '',
			'find_email_signoff'       => '',
		);
	}

	/**
	 * Get issuer settings.
	 *
	 * @return array{name: string, url: string, email: string, description: string, image_id: int, linkedin_organization_id: string, sending_email: string, sending_display_name: string, find_email: string, find_email_signoff: string}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged = array_merge( self::defaults(), $stored );
		$merged['image_id']                 = absint( $merged['image_id'] ?? 0 );
		$merged['name']                     = (string) ( $merged['name'] ?? '' );
		$merged['url']                      = (string) ( $merged['url'] ?? '' );
		$merged['email']                    = (string) ( $merged['email'] ?? '' );
		$merged['description']              = (string) ( $merged['description'] ?? '' );
		$merged['linkedin_organization_id'] = (string) ( $merged['linkedin_organization_id'] ?? '' );
		$merged['sending_email']            = (string) ( $merged['sending_email'] ?? '' );
		$merged['sending_display_name']     = (string) ( $merged['sending_display_name'] ?? '' );
		$merged['find_email']               = (string) ( $merged['find_email'] ?? '' );
		$merged['find_email_signoff']       = (string) ( $merged['find_email_signoff'] ?? '' );

		return $merged;
	}

	/**
	 * Whether the issuer has the minimum required fields for OB 1.0.
	 */
	public static function is_configured(): bool {
		$issuer = self::get();

		return '' !== $issuer['name'] && '' !== $issuer['url'];
	}

	/**
	 * Public URL for the hosted IssuerOrganization JSON.
	 */
	public static function json_url(): string {
		return home_url( '/ob/issuer.json' );
	}

	/**
	 * Build IssuerOrganization array for JSON output.
	 *
	 * @return array<string, string>
	 */
	public static function to_open_badges(): array {
		$issuer = self::get();
		$data   = array(
			'name' => $issuer['name'],
			'url'  => $issuer['url'],
		);

		if ( '' !== $issuer['description'] ) {
			$data['description'] = $issuer['description'];
		}

		if ( '' !== $issuer['email'] ) {
			$data['email'] = $issuer['email'];
		}

		$image_url = self::image_url();
		if ( '' !== $image_url ) {
			$data['image'] = $image_url;
		}

		return $data;
	}

	/**
	 * Issuer logo URL, if set.
	 */
	public static function image_url(): string {
		$issuer = self::get();

		if ( $issuer['image_id'] <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $issuer['image_id'], 'full' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Sanitize and persist issuer settings from a settings form.
	 *
	 * @param mixed $input Raw option input.
	 * @return array{name: string, url: string, email: string, description: string, image_id: int, linkedin_organization_id: string, sending_email: string, sending_display_name: string, find_email: string, find_email_signoff: string}
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();

		if ( ! is_array( $input ) ) {
			return self::get();
		}

		$name          = isset( $input['name'] ) ? sanitize_text_field( (string) $input['name'] ) : '';
		$url           = isset( $input['url'] ) ? esc_url_raw( (string) $input['url'] ) : '';
		$email         = isset( $input['email'] ) ? sanitize_email( (string) $input['email'] ) : '';
		$description   = isset( $input['description'] ) ? sanitize_textarea_field( (string) $input['description'] ) : '';
		$image_id      = isset( $input['image_id'] ) ? absint( $input['image_id'] ) : 0;
		$linkedin_id   = isset( $input['linkedin_organization_id'] ) ? preg_replace( '/\D+/', '', (string) $input['linkedin_organization_id'] ) : '';
		$sending_email = isset( $input['sending_email'] ) ? sanitize_email( (string) $input['sending_email'] ) : '';
		$sending_name  = isset( $input['sending_display_name'] ) ? sanitize_text_field( (string) $input['sending_display_name'] ) : '';
		$find_email    = isset( $input['find_email'] ) ? sanitize_textarea_field( (string) $input['find_email'] ) : '';
		$find_signoff  = isset( $input['find_email_signoff'] ) ? sanitize_textarea_field( (string) $input['find_email_signoff'] ) : '';

		if ( '' === $url ) {
			$url = $defaults['url'];
		}

		return array(
			'name'                     => $name,
			'url'                      => $url,
			'email'                    => $email,
			'description'              => $description,
			'image_id'                 => $image_id,
			'linkedin_organization_id' => is_string( $linkedin_id ) ? $linkedin_id : '',
			'sending_email'            => $sending_email,
			'sending_display_name'     => $sending_name,
			'find_email'               => $find_email,
			'find_email_signoff'       => $find_signoff,
		);
	}
}
