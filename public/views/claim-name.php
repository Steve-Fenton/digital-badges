<?php
/**
 * Claim / add recipient name via one-time link.
 *
 * Expected vars:
 * - $error (string)
 * - $step (string): enter|confirm|invalid|done
 * - $token (string)
 * - $name (string)
 * - $badge_title (string)
 * - $form_action (string)
 * - $attestation_url (string)
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$error           = isset( $error ) && is_string( $error ) ? $error : '';
$step            = isset( $step ) && is_string( $step ) ? $step : 'invalid';
$token           = isset( $token ) && is_string( $token ) ? $token : '';
$name            = isset( $name ) && is_string( $name ) ? $name : '';
$badge_title     = isset( $badge_title ) && is_string( $badge_title ) ? $badge_title : '';
$form_action     = isset( $form_action ) && is_string( $form_action ) && '' !== $form_action
	? $form_action
	: home_url( '/badges/claim-name/' );
$attestation_url = isset( $attestation_url ) && is_string( $attestation_url ) ? $attestation_url : '';
?>
<div class="fendigibadge-claim-name">
	<header class="fendigibadge-claim-name__header">
		<h1 class="fendigibadge-claim-name__title"><?php esc_html_e( 'Add your name', 'fenton-digital-badges' ); ?></h1>
		<?php if ( '' !== $badge_title && 'invalid' !== $step ) : ?>
			<p class="fendigibadge-claim-name__intro">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: badge title */
						__( 'Claim your certificate for “%s” by adding the name that should appear on it.', 'fenton-digital-badges' ),
						$badge_title
					)
				);
				?>
			</p>
		<?php endif; ?>
	</header>

	<?php if ( '' !== $error ) : ?>
		<p class="fendigibadge-claim-name__error" role="alert"><?php echo esc_html( $error ); ?></p>
	<?php endif; ?>

	<?php if ( 'invalid' === $step ) : ?>
		<p class="fendigibadge-claim-name__notice" role="status">
			<?php esc_html_e( 'This link is invalid, has already been used, or has expired. Request a new link from the find badges form if you still need to add your name.', 'fenton-digital-badges' ); ?>
		</p>
		<p class="fendigibadge-claim-name__actions">
			<a class="fendigibadge-claim-name__btn" href="<?php echo esc_url( home_url( '/badges/find/' ) ); ?>">
				<?php esc_html_e( 'Find your badges', 'fenton-digital-badges' ); ?>
			</a>
		</p>
	<?php elseif ( 'done' === $step ) : ?>
		<p class="fendigibadge-claim-name__notice" role="status">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: recipient name */
					__( 'Your name has been saved as “%s”.', 'fenton-digital-badges' ),
					$name
				)
			);
			?>
		</p>
		<?php if ( '' !== $attestation_url ) : ?>
			<p class="fendigibadge-claim-name__actions">
				<a class="fendigibadge-claim-name__btn fendigibadge-claim-name__btn--primary" href="<?php echo esc_url( $attestation_url ); ?>">
					<?php esc_html_e( 'View your certificate', 'fenton-digital-badges' ); ?>
				</a>
			</p>
		<?php endif; ?>
	<?php elseif ( 'confirm' === $step ) : ?>
		<div class="fendigibadge-claim-name__confirm" role="status">
			<p class="fendigibadge-claim-name__confirm-label"><?php esc_html_e( 'Please confirm your name', 'fenton-digital-badges' ); ?></p>
			<p class="fendigibadge-claim-name__confirm-name"><?php echo esc_html( $name ); ?></p>
			<p class="fendigibadge-claim-name__confirm-hint"><?php esc_html_e( 'This is how it will appear on your certificate. You can go back to adjust it before saving.', 'fenton-digital-badges' ); ?></p>
		</div>

		<form class="fendigibadge-claim-name__form" method="post" action="<?php echo esc_url( $form_action ); ?>">
			<?php wp_nonce_field( 'fendigibadge_claim_name', 'fendigibadge_claim_name_nonce' ); ?>
			<input type="hidden" name="fendigibadge_claim_token" value="<?php echo esc_attr( $token ); ?>" />
			<input type="hidden" name="fendigibadge_claim_name" value="<?php echo esc_attr( $name ); ?>" />
			<div class="fendigibadge-claim-name__actions">
				<button class="fendigibadge-claim-name__btn" type="submit" name="fendigibadge_claim_action" value="edit">
					<?php esc_html_e( 'Adjust name', 'fenton-digital-badges' ); ?>
				</button>
				<button class="fendigibadge-claim-name__btn fendigibadge-claim-name__btn--primary" type="submit" name="fendigibadge_claim_action" value="confirm">
					<?php esc_html_e( 'Confirm and save', 'fenton-digital-badges' ); ?>
				</button>
			</div>
		</form>
	<?php else : ?>
		<form class="fendigibadge-claim-name__form" method="post" action="<?php echo esc_url( $form_action ); ?>">
			<?php wp_nonce_field( 'fendigibadge_claim_name', 'fendigibadge_claim_name_nonce' ); ?>
			<input type="hidden" name="fendigibadge_claim_token" value="<?php echo esc_attr( $token ); ?>" />
			<input type="hidden" name="fendigibadge_claim_action" value="preview" />
			<label class="fendigibadge-claim-name__label" for="fendigibadge_claim_name"><?php esc_html_e( 'Your name', 'fenton-digital-badges' ); ?></label>
			<div class="fendigibadge-claim-name__row">
				<input
					class="fendigibadge-claim-name__input"
					type="text"
					name="fendigibadge_claim_name"
					id="fendigibadge_claim_name"
					value="<?php echo esc_attr( $name ); ?>"
					required
					autocomplete="name"
					maxlength="255"
				/>
				<button class="fendigibadge-claim-name__btn fendigibadge-claim-name__btn--primary" type="submit">
					<?php esc_html_e( 'Continue', 'fenton-digital-badges' ); ?>
				</button>
			</div>
		</form>
	<?php endif; ?>
</div>
