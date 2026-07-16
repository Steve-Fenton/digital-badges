<?php
/**
 * Badge notification emails (issue).
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

namespace FentonDigitalBadges;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds and sends badge list emails with shared From / unsubscribe handling.
 */
final class Badge_Mailer {

	/**
	 * Default opening text for issue-badge emails.
	 */
	public static function default_issue_intro(): string {
		return sprintf(
			/* translators: %s: site name */
			__( 'A new badge has been issued to you on %s.', 'fenton-digital-badges' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);
	}

	/**
	 * Default sign-off for issue-badge emails.
	 */
	public static function default_issue_signoff(): string {
		return __( 'Congratulations!', 'fenton-digital-badges' );
	}

	/**
	 * Email attestation URL for a newly issued badge.
	 *
	 * @param string $email     Recipient email.
	 * @param object $assertion Assertion row.
	 */
	public static function send_issue( string $email, object $assertion ): void {
		if ( Email_Unsubscribe::is_unsubscribed( $email ) ) {
			return;
		}

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$issuer    = Issuer::get();

		$subject = sprintf(
			/* translators: %s: site name */
			__( "You've been issued a badge on %s", 'fenton-digital-badges' ),
			$site_name
		);

		$intro = trim( $issuer['issue_email'] );
		if ( '' === $intro ) {
			$intro = self::default_issue_intro();
		}

		$signoff = trim( $issuer['issue_email_signoff'] );
		if ( '' === $signoff ) {
			$signoff = self::default_issue_signoff();
		}

		self::send( $email, $subject, $intro, $signoff, self::entries_from_assertions( array( $assertion ) ) );
	}

	/**
	 * Build badge list entries from assertion rows.
	 *
	 * @param list<object> $results Assertion rows.
	 * @return list<array{name: string, url: string, claim_url: string}>
	 */
	private static function entries_from_assertions( array $results ): array {
		$entries = array();
		$seen    = array();

		foreach ( $results as $row ) {
			$uid = (string) $row->uid;
			if ( isset( $seen[ $uid ] ) ) {
				continue;
			}
			$seen[ $uid ] = true;

			$url   = Assertion_Repository::attestation_url( $uid );
			$badge = get_post( (int) $row->badge_post_id );
			$name  = ( $badge instanceof \WP_Post ) ? get_the_title( $badge ) : '';

			if ( '' === $url ) {
				continue;
			}

			$claim_url = '';
			if ( '' === trim( (string) ( $row->recipient_name ?? '' ) ) ) {
				$claim_token = Name_Claim::issue_for_assertion( $row );
				if ( is_string( $claim_token ) && '' !== $claim_token ) {
					$claim_url = Name_Claim::claim_url( $claim_token );
				}
			}

			$entries[] = array(
				'name'      => '' !== $name ? $name : $uid,
				'url'       => $url,
				'claim_url' => $claim_url,
			);
		}

		return $entries;
	}

	/**
	 * Send a badge list email.
	 *
	 * @param string                                         $email   Recipient email.
	 * @param string                                         $subject Subject line.
	 * @param string                                         $intro   Opening body text.
	 * @param string                                         $signoff Closing body text.
	 * @param list<array{name: string, url: string, claim_url: string}> $entries Badge blocks.
	 */
	private static function send( string $email, string $subject, string $intro, string $signoff, array $entries ): void {
		if ( array() === $entries ) {
			return;
		}

		$blocks = array();
		foreach ( $entries as $entry ) {
			$block = $entry['name'];

			if ( '' !== $entry['claim_url'] ) {
				$block .= "\n\n" . __( 'Claim your certificate and add your name with the following one-time link:', 'fenton-digital-badges' );
				$block .= "\n" . $entry['claim_url'];
			}

			$block .= "\n" . $entry['url'];

			$blocks[] = $block;
		}

		$body  = $intro;
		$body .= "\n\n" . implode( "\n\n", $blocks );
		$body .= "\n\n" . $signoff;

		$unsubscribe_url = Email_Unsubscribe::unsubscribe_url( $email );
		if ( '' !== $unsubscribe_url ) {
			$body .= "\n\n" . __( 'You can stop all future badge notifications by clicking the link below:', 'fenton-digital-badges' );
			$body .= "\n" . $unsubscribe_url;
		}

		$body .= "\n";

		$issuer        = Issuer::get();
		$from_email    = $issuer['sending_email'];
		$from_name     = $issuer['sending_display_name'];
		$from_email_cb = null;
		$from_name_cb  = null;

		if ( '' !== $from_email && is_email( $from_email ) ) {
			$from_email_cb = static function () use ( $from_email ): string {
				return $from_email;
			};
			add_filter( 'wp_mail_from', $from_email_cb );
		}

		if ( '' !== $from_name ) {
			$from_name_cb = static function () use ( $from_name ): string {
				return $from_name;
			};
			add_filter( 'wp_mail_from_name', $from_name_cb );
		}

		wp_mail( $email, $subject, $body );

		if ( null !== $from_email_cb ) {
			remove_filter( 'wp_mail_from', $from_email_cb );
		}

		if ( null !== $from_name_cb ) {
			remove_filter( 'wp_mail_from_name', $from_name_cb );
		}
	}
}
