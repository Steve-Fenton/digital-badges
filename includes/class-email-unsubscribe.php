<?php
/**
 * Unsubscribe list and signed links for badge notification emails.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores opted-out emails and builds / verifies secure unsubscribe URLs.
 */
final class Email_Unsubscribe {

	public const OPTION_KEY = 'fendigibadge_unsubscribed_emails';

	/**
	 * Whether an email is on the unsubscribe list.
	 */
	public static function is_unsubscribed( string $email ): bool {
		$normalized = Identity::normalize_email( $email );

		if ( '' === $normalized ) {
			return false;
		}

		return in_array( $normalized, self::get_list(), true );
	}

	/**
	 * Add a normalized email to the unsubscribe list.
	 */
	public static function add( string $email ): void {
		$normalized = Identity::normalize_email( $email );

		if ( '' === $normalized || ! Identity::is_valid_email( $normalized ) ) {
			return;
		}

		$list = self::get_list();

		if ( in_array( $normalized, $list, true ) ) {
			return;
		}

		$list[] = $normalized;
		update_option( self::OPTION_KEY, $list, false );
	}

	/**
	 * Public unsubscribe URL for an email (HMAC-signed; embeds the address).
	 */
	public static function unsubscribe_url( string $email ): string {
		$token = self::create_token( $email );

		if ( '' === $token ) {
			return '';
		}

		return home_url( '/badges/unsubscribe/' . rawurlencode( $token ) . '/' );
	}

	/**
	 * Build a signed token that embeds the email address.
	 */
	public static function create_token( string $email ): string {
		$normalized = Identity::normalize_email( $email );

		if ( ! Identity::is_valid_email( $normalized ) ) {
			return '';
		}

		$payload = self::base64url_encode( $normalized );
		$sig     = hash_hmac( 'sha256', 'unsubscribe|' . $normalized, Identity::ensure_secret() );

		return $payload . '.' . $sig;
	}

	/**
	 * Resolve a token to a normalized email when the signature is valid.
	 */
	public static function email_from_token( string $token ): ?string {
		$token = trim( $token );

		if ( '' === $token || ! str_contains( $token, '.' ) ) {
			return null;
		}

		$parts = explode( '.', $token, 2 );
		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
			return null;
		}

		$email = self::base64url_decode( $parts[0] );
		if ( null === $email || ! Identity::is_valid_email( $email ) ) {
			return null;
		}

		$normalized = Identity::normalize_email( $email );
		$expected   = hash_hmac( 'sha256', 'unsubscribe|' . $normalized, Identity::ensure_secret() );

		if ( ! hash_equals( $expected, strtolower( $parts[1] ) ) ) {
			return null;
		}

		return $normalized;
	}

	/**
	 * Unsubscribe when the token is valid and the email hash matches at least one badge.
	 *
	 * @return bool True when the address was (or already is) unsubscribed.
	 */
	public static function process_token( string $token ): bool {
		$email = self::email_from_token( $token );

		if ( null === $email ) {
			return false;
		}

		if ( self::is_unsubscribed( $email ) ) {
			return true;
		}

		$results = Assertion_Repository::find_by_lookup( Identity::lookup_hash( $email ) );

		if ( array() === $results ) {
			return false;
		}

		self::add( $email );

		return true;
	}

	/**
	 * Stored unsubscribe list (normalized emails).
	 *
	 * @return list<string>
	 */
	private static function get_list(): array {
		$list = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $list ) ) {
			return array();
		}

		$out = array();
		foreach ( $list as $item ) {
			if ( ! is_string( $item ) ) {
				continue;
			}
			$normalized = Identity::normalize_email( $item );
			if ( '' !== $normalized && Identity::is_valid_email( $normalized ) ) {
				$out[] = $normalized;
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * URL-safe base64 encode.
	 */
	private static function base64url_encode( string $value ): string {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	/**
	 * URL-safe base64 decode.
	 */
	private static function base64url_decode( string $value ): ?string {
		$padded = strtr( $value, '-_', '+/' );
		$pad    = strlen( $padded ) % 4;
		if ( 0 !== $pad ) {
			$padded .= str_repeat( '=', 4 - $pad );
		}

		$decoded = base64_decode( $padded, true );

		return is_string( $decoded ) ? $decoded : null;
	}
}
