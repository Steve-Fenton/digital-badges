<?php
/**
 * Claim / add recipient name via one-time link.
 *
 * Expected vars:
 * - $fendigibadge_error (string)
 * - $fendigibadge_step (string): enter|confirm|invalid|done
 * - $fendigibadge_token (string)
 * - $fendigibadge_name (string)
 * - $fendigibadge_badge_title (string)
 * - $fendigibadge_form_action (string)
 * - $fendigibadge_attestation_url (string)
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fendigibadge_error           = isset( $fendigibadge_error ) && is_string( $fendigibadge_error ) ? $fendigibadge_error : '';
$fendigibadge_step            = isset( $fendigibadge_step ) && is_string( $fendigibadge_step ) ? $fendigibadge_step : 'invalid';
$fendigibadge_token           = isset( $fendigibadge_token ) && is_string( $fendigibadge_token ) ? $fendigibadge_token : '';
$fendigibadge_name            = isset( $fendigibadge_name ) && is_string( $fendigibadge_name ) ? $fendigibadge_name : '';
$fendigibadge_badge_title     = isset( $fendigibadge_badge_title ) && is_string( $fendigibadge_badge_title ) ? $fendigibadge_badge_title : '';
$fendigibadge_form_action     = isset( $fendigibadge_form_action ) && is_string( $fendigibadge_form_action ) && '' !== $fendigibadge_form_action
	? $fendigibadge_form_action
	: home_url( '/badges/claim-name/' );
$fendigibadge_attestation_url = isset( $fendigibadge_attestation_url ) && is_string( $fendigibadge_attestation_url ) ? $fendigibadge_attestation_url : '';
?>
<div class="fendigibadge-claim-name">
	<header class="fendigibadge-claim-name__header">
		<h1 class="fendigibadge-claim-name__title"><?php esc_html_e( 'Add your name', 'fenton-digital-badges' ); ?></h1>
		<?php if ( '' !== $fendigibadge_badge_title && 'invalid' !== $fendigibadge_step ) : ?>
			<p class="fendigibadge-claim-name__intro">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: badge title */
						__( 'Claim your certificate for “%s” by adding the name that should appear on it.', 'fenton-digital-badges' ),
						$fendigibadge_badge_title
					)
				);
				?>
			</p>
		<?php endif; ?>
	</header>

	<?php if ( '' !== $fendigibadge_error ) : ?>
		<p class="fendigibadge-claim-name__error" role="alert"><?php echo esc_html( $fendigibadge_error ); ?></p>
	<?php endif; ?>

	<?php if ( 'invalid' === $fendigibadge_step ) : ?>
		<p class="fendigibadge-claim-name__notice" role="status">
			<?php esc_html_e( 'This link is invalid, has already been used, or has expired. Contact whoever issued your badge if you still need to add your name.', 'fenton-digital-badges' ); ?>
		</p>
	<?php elseif ( 'done' === $fendigibadge_step ) : ?>
		<p class="fendigibadge-claim-name__notice" role="status">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: recipient name */
					__( 'Your name has been saved as “%s”.', 'fenton-digital-badges' ),
					$fendigibadge_name
				)
			);
			?>
		</p>
		<?php if ( '' !== $fendigibadge_attestation_url ) : ?>
			<p class="fendigibadge-claim-name__actions">
				<a class="fendigibadge-claim-name__btn fendigibadge-claim-name__btn--primary" href="<?php echo esc_url( $fendigibadge_attestation_url ); ?>">
					<?php esc_html_e( 'View your certificate', 'fenton-digital-badges' ); ?>
				</a>
			</p>
		<?php endif; ?>
	<?php elseif ( 'confirm' === $fendigibadge_step ) : ?>
		<div class="fendigibadge-claim-name__confirm" role="status">
			<p class="fendigibadge-claim-name__confirm-label"><?php esc_html_e( 'Please confirm your name', 'fenton-digital-badges' ); ?></p>
			<p class="fendigibadge-claim-name__confirm-name"><?php echo esc_html( $fendigibadge_name ); ?></p>
			<p class="fendigibadge-claim-name__confirm-hint"><?php esc_html_e( 'This is how it will appear on your certificate. You can go back to adjust it before saving.', 'fenton-digital-badges' ); ?></p>
		</div>

		<form class="fendigibadge-claim-name__form" method="post" action="<?php echo esc_url( $fendigibadge_form_action ); ?>">
			<?php wp_nonce_field( 'fendigibadge_claim_name', 'fendigibadge_claim_name_nonce' ); ?>
			<input type="hidden" name="fendigibadge_claim_token" value="<?php echo esc_attr( $fendigibadge_token ); ?>" />
			<input type="hidden" name="fendigibadge_claim_name" value="<?php echo esc_attr( $fendigibadge_name ); ?>" />
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
		<form class="fendigibadge-claim-name__form" method="post" action="<?php echo esc_url( $fendigibadge_form_action ); ?>">
			<?php wp_nonce_field( 'fendigibadge_claim_name', 'fendigibadge_claim_name_nonce' ); ?>
			<input type="hidden" name="fendigibadge_claim_token" value="<?php echo esc_attr( $fendigibadge_token ); ?>" />
			<input type="hidden" name="fendigibadge_claim_action" value="preview" />
			<label class="fendigibadge-claim-name__label" for="fendigibadge_claim_name"><?php esc_html_e( 'Your name', 'fenton-digital-badges' ); ?></label>
			<div class="fendigibadge-claim-name__row">
				<input
					class="fendigibadge-claim-name__input"
					type="text"
					name="fendigibadge_claim_name"
					id="fendigibadge_claim_name"
					value="<?php echo esc_attr( $fendigibadge_name ); ?>"
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
