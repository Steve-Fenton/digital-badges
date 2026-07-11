<?php
/**
 * Find badges by email form.
 *
 * Expected vars: $error (string), $searched (bool),
 * $fendigibadge_form_action (string), $fendigibadge_show_header (bool, optional).
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $fendigibadge_form_action ) || ! is_string( $fendigibadge_form_action ) || '' === $fendigibadge_form_action ) {
	$fendigibadge_form_action = home_url( '/badges/find/' );
}

$fendigibadge_show_header = isset( $fendigibadge_show_header ) ? (bool) $fendigibadge_show_header : true;
?>
<div class="fendigibadge-find">
	<?php if ( $fendigibadge_show_header ) : ?>
		<header class="fendigibadge-find__header">
			<h1 class="fendigibadge-find__title"><?php esc_html_e( 'Find your badges', 'fenton-digital-badges' ); ?></h1>
			<p class="fendigibadge-find__intro"><?php esc_html_e( 'Enter the email address used when your badge was issued. We look up a hash of your email — the address itself is not stored. If we find badges, we will email you the links.', 'fenton-digital-badges' ); ?></p>
		</header>
	<?php endif; ?>

	<?php if ( ! ( $searched && '' === $error ) ) : ?>
		<form class="fendigibadge-find__form" method="post" action="<?php echo esc_url( $fendigibadge_form_action ); ?>">
			<?php wp_nonce_field( 'fendigibadge_find_badges', 'fendigibadge_find_nonce' ); ?>
			<label class="fendigibadge-find__label" for="fendigibadge_email"><?php esc_html_e( 'Email address', 'fenton-digital-badges' ); ?></label>
			<div class="fendigibadge-find__row">
				<input class="fendigibadge-find__input" type="email" name="fendigibadge_email" id="fendigibadge_email" required autocomplete="email" />
				<button class="fendigibadge-find__submit" type="submit"><?php esc_html_e( 'Find badges', 'fenton-digital-badges' ); ?></button>
			</div>
		</form>
	<?php endif; ?>

	<?php if ( '' !== $error ) : ?>
		<p class="fendigibadge-find__error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( $searched && '' === $error ) : ?>
		<p class="fendigibadge-find__notice" role="status"><?php esc_html_e( "We'll take a look and email you if you have any badges.", 'fenton-digital-badges' ); ?></p>
	<?php endif; ?>
</div>
