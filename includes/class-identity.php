<?php
/**
 * Email identity hashing for Open Badges.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes emails and produces OB identity hashes plus lookup HMACs.
 */
final class Identity {

	public const OPTION_SECRET = 'fendigibadge_lookup_secret';

	/**
	 * Normalize an email for hashing.
	 */
	public static function normalize_email( string $email ): string {
		return strtolower( trim( $email ) );
	}

	/**
	 * Whether a string looks like a usable email address.
	 */
	public static function is_valid_email( string $email ): bool {
		$normalized = self::normalize_email( $email );

		return '' !== $normalized && (bool) is_email( $normalized );
	}

	/**
	 * Ensure a site-specific lookup secret exists.
	 */
	public static function ensure_secret(): string {
		$secret = get_option( self::OPTION_SECRET, '' );

		if ( ! is_string( $secret ) || '' === $secret ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( self::OPTION_SECRET, $secret, false );
		}

		return $secret;
	}

	/**
	 * Generate a random salt for an assertion.
	 */
	public static function generate_salt( int $length = 32 ): string {
		return bin2hex( random_bytes( max( 8, (int) ceil( $length / 2 ) ) ) );
	}

	/**
	 * Build the Open Badges IdentityHash value (sha256$hex).
	 */
	public static function open_badges_identity( string $email, string $salt ): string {
		$normalized = self::normalize_email( $email );

		return 'sha256$' . hash( 'sha256', $normalized . $salt );
	}

	/**
	 * Deterministic lookup key for finding assertions by email (never public).
	 */
	public static function lookup_hash( string $email ): string {
		$normalized = self::normalize_email( $email );

		return hash_hmac( 'sha256', $normalized, self::ensure_secret() );
	}
}
