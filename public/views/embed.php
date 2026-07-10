<?php
/**
 * Embeddable badge widget.
 *
 * Expected vars: $assertion, $badge, $attestation_url, $image_url
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$badge_name = get_the_title( $badge );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html( $badge_name ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( DIGITAL_BADGES_URL . 'public/css/public.css' ); ?>?ver=<?php echo esc_attr( DIGITAL_BADGES_VERSION ); ?>" />
	<style>html,body{margin:0;padding:0;background:transparent;}</style>
</head>
<body class="db-embed-body">
	<a class="db-embed" href="<?php echo esc_url( $attestation_url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php if ( '' !== $image_url ) : ?>
			<img class="db-embed__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $badge_name ); ?>" width="140" height="140" />
		<?php endif; ?>
		<span class="db-embed__title"><?php echo esc_html( $badge_name ); ?></span>
	</a>
</body>
</html>
