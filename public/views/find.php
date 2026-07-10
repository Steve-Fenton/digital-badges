<?php
/**
 * Find badges by email form.
 *
 * Expected vars: $error (string), $searched (bool),
 * $fenton_digital_badges_form_action (string), $fenton_digital_badges_show_header (bool, optional).
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $fenton_digital_badges_form_action ) || ! is_string( $fenton_digital_badges_form_action ) || '' === $fenton_digital_badges_form_action ) {
	$fenton_digital_badges_form_action = home_url( '/badges/find/' );
}

$fenton_digital_badges_show_header = isset( $fenton_digital_badges_show_header ) ? (bool) $fenton_digital_badges_show_header : true;
?>
<div class="db-find">
	<?php if ( $fenton_digital_badges_show_header ) : ?>
		<header class="db-find__header">
			<h1 class="db-find__title"><?php esc_html_e( 'Find your badges', 'fenton-digital-badges' ); ?></h1>
			<p class="db-find__intro"><?php esc_html_e( 'Enter the email address used when your badge was issued. We look up a hash of your email — the address itself is not stored. If we find badges, we will email you the links.', 'fenton-digital-badges' ); ?></p>
		</header>
	<?php endif; ?>

	<form class="db-find__form" method="post" action="<?php echo esc_url( $fenton_digital_badges_form_action ); ?>">
		<?php wp_nonce_field( 'db_find_badges', 'db_find_nonce' ); ?>
		<label class="db-find__label" for="db_email"><?php esc_html_e( 'Email address', 'fenton-digital-badges' ); ?></label>
		<div class="db-find__row">
			<input class="db-find__input" type="email" name="db_email" id="db_email" required autocomplete="email" />
			<button class="db-find__submit" type="submit"><?php esc_html_e( 'Find badges', 'fenton-digital-badges' ); ?></button>
		</div>
	</form>

	<?php if ( '' !== $error ) : ?>
		<p class="db-find__error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( $searched && '' === $error ) : ?>
		<p class="db-find__notice" role="status"><?php esc_html_e( "We'll take a look and email you if you have any badges.", 'fenton-digital-badges' ); ?></p>
	<?php endif; ?>
</div>
