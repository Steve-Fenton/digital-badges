<?php
/**
 * One-time links for earners to add a missing recipient name.
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Issues, validates, and consumes name-claim tokens.
 */
final class Name_Claim {

	/**
	 * How long a claim link remains valid after it is issued.
	 */
	public const TOKEN_TTL = WEEK_IN_SECONDS;

	/**
	 * Create (or replace) a one-time claim token for an assertion missing a name.
	 *
	 * @return string|null Token string, or null when the assertion cannot be claimed.
	 */
	public static function issue_for_assertion( object $assertion ): ?string {
		$uid  = isset( $assertion->uid ) ? (string) $assertion->uid : '';
		$name = isset( $assertion->recipient_name ) ? trim( (string) $assertion->recipient_name ) : '';

		if ( '' === $uid || '' !== $name || ! empty( $assertion->revoked ) ) {
			return null;
		}

		$token   = bin2hex( random_bytes( 32 ) );
		$expires = gmdate( 'Y-m-d H:i:s', time() + self::TOKEN_TTL );

		if ( ! Assertion_Repository::set_name_claim_token( $uid, $token, $expires ) ) {
			return null;
		}

		return $token;
	}

	/**
	 * Resolve a valid, unused claim token to its assertion row.
	 */
	public static function find_assertion( string $token ): ?object {
		$token = self::normalize_token( $token );

		if ( '' === $token ) {
			return null;
		}

		$row = Assertion_Repository::find_by_name_claim_token( $token );

		if ( null === $row ) {
			return null;
		}

		// Only nameless assertions may be claimed.
		if ( '' !== trim( (string) ( $row->recipient_name ?? '' ) ) ) {
			return null;
		}

		return $row;
	}

	/**
	 * Public URL for a claim token.
	 */
	public static function claim_url( string $token ): string {
		$token = self::normalize_token( $token );

		if ( '' === $token ) {
			return '';
		}

		return home_url( '/badges/claim-name/' . rawurlencode( $token ) . '/' );
	}

	/**
	 * Save a confirmed name and invalidate the one-time link.
	 *
	 * @return bool True when the name was saved and the token cleared.
	 */
	public static function confirm_name( string $token, string $name ): bool {
		$row = self::find_assertion( $token );

		if ( null === $row ) {
			return false;
		}

		$name = sanitize_text_field( $name );

		if ( '' === $name ) {
			return false;
		}

		return Assertion_Repository::update_recipient_name( (string) $row->uid, $name );
	}

	/**
	 * Normalize a token from a URL or form field.
	 */
	public static function normalize_token( string $token ): string {
		$token = strtolower( trim( $token ) );

		if ( '' === $token || ! preg_match( '/^[a-f0-9]{64}$/', $token ) ) {
			return '';
		}

		return $token;
	}
}
