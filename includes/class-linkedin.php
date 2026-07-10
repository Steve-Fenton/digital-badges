<?php
/**
 * LinkedIn certification share URL builder.
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

namespace DigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds LinkedIn Add to Profile certification links.
 */
final class LinkedIn {

	/**
	 * Build a LinkedIn certification add URL for an assertion.
	 */
	public static function certification_url( object $assertion, \WP_Post $badge ): string {
		$issuer    = Issuer::get();
		$issued_ts = strtotime( (string) $assertion->issued_on . ' UTC' );

		if ( false === $issued_ts ) {
			$issued_ts = time();
		}

		$params = array(
			'startTask'        => 'CERTIFICATION_NAME',
			'name'             => get_the_title( $badge ),
			'issueYear'        => gmdate( 'Y', $issued_ts ),
			'issueMonth'       => (string) (int) gmdate( 'n', $issued_ts ),
			'certUrl'          => Assertion_Repository::attestation_url( (string) $assertion->uid ),
			'certId'           => (string) $assertion->uid,
		);

		if ( '' !== $issuer['linkedin_organization_id'] ) {
			$params['organizationId'] = $issuer['linkedin_organization_id'];
		} else {
			$params['organizationName'] = $issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' );
		}

		if ( ! empty( $assertion->expires ) ) {
			$expires_ts = strtotime( (string) $assertion->expires . ' UTC' );
			if ( false !== $expires_ts ) {
				$params['expirationYear']  = gmdate( 'Y', $expires_ts );
				$params['expirationMonth'] = (string) (int) gmdate( 'n', $expires_ts );
			}
		}

		return 'https://www.linkedin.com/profile/add?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}
}
