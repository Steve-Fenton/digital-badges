<?php
/**
 * Embeddable badge widget.
 *
 * Expected vars: $assertion, $badge, $attestation_url, $image_url
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fenton_digital_badges_badge_name = get_the_title( $badge );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html( $fenton_digital_badges_badge_name ); ?></title>
	<?php
	\FentonDigitalBadges\Public_Facing::enqueue_assets();
	wp_print_styles( 'fenton-digital-badges-public' );
	?>
	<style>html,body{margin:0;padding:0;background:transparent;}</style>
</head>
<body class="db-embed-body">
	<a class="db-embed" href="<?php echo esc_url( $attestation_url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php if ( '' !== $image_url ) : ?>
			<img class="db-embed__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $fenton_digital_badges_badge_name ); ?>" width="140" height="140" />
		<?php endif; ?>
		<span class="db-embed__title"><?php echo esc_html( $fenton_digital_badges_badge_name ); ?></span>
	</a>
</body>
</html>
