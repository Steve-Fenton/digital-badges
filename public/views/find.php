<?php
/**
 * Find badges by email form and results.
 *
 * Expected vars: $results (list<object>), $error (string), $searched (bool)
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="db-find">
	<header class="db-find__header">
		<h1 class="db-find__title"><?php esc_html_e( 'Find your badges', 'digital-badges' ); ?></h1>
		<p class="db-find__intro"><?php esc_html_e( 'Enter the email address used when your badge was issued. We look up a hash of your email — the address itself is not stored.', 'digital-badges' ); ?></p>
	</header>

	<form class="db-find__form" method="post" action="">
		<?php wp_nonce_field( 'db_find_badges', 'db_find_nonce' ); ?>
		<label class="db-find__label" for="db_email"><?php esc_html_e( 'Email address', 'digital-badges' ); ?></label>
		<div class="db-find__row">
			<input class="db-find__input" type="email" name="db_email" id="db_email" required autocomplete="email" />
			<button class="db-find__submit" type="submit"><?php esc_html_e( 'Find badges', 'digital-badges' ); ?></button>
		</div>
	</form>

	<?php if ( '' !== $error ) : ?>
		<p class="db-find__error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( $searched && '' === $error ) : ?>
		<section class="db-find__results" aria-live="polite">
			<?php if ( array() === $results ) : ?>
				<p class="db-find__empty"><?php esc_html_e( 'No badges found for that email address.', 'digital-badges' ); ?></p>
			<?php else : ?>
				<ul class="db-find__list">
					<?php foreach ( $results as $row ) : ?>
						<?php
						$badge = get_post( (int) $row->badge_post_id );
						if ( ! $badge ) {
							continue;
						}
						$image = \DigitalBadges\Badge_Class::image_url( (int) $badge->ID );
						$url   = \DigitalBadges\Assertion_Repository::attestation_url( (string) $row->uid );
						$issued_ts = strtotime( (string) $row->issued_on . ' UTC' );
						$issued_label = false !== $issued_ts ? gmdate( 'Y-m-d', $issued_ts ) : (string) $row->issued_on;
						?>
						<li class="db-find__item">
							<a class="db-find__card" href="<?php echo esc_url( $url ); ?>">
								<?php if ( '' !== $image ) : ?>
									<img class="db-find__image" src="<?php echo esc_url( $image ); ?>" alt="" width="72" height="72" />
								<?php endif; ?>
								<span class="db-find__meta">
									<span class="db-find__badge-name"><?php echo esc_html( get_the_title( $badge ) ); ?></span>
									<span class="db-find__issued"><?php echo esc_html( $issued_label ); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>
