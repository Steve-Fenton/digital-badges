<?php
/**
 * Public attestation page for a single assertion.
 *
 * Expected vars: $assertion, $badge, $issuer, $linkedin_url, $attestation_url,
 * $json_url, $image_url, $earn_url, $assertion_json, $share_text
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fendigibadge_badge_name     = get_the_title( $badge );
$fendigibadge_issuer_name    = $issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' );
$fendigibadge_issuer_logo    = \FentonDigitalBadges\Issuer::image_url();
$fendigibadge_issued_ts      = strtotime( (string) $assertion->issued_on . ' UTC' );
$fendigibadge_issued_label   = false !== $fendigibadge_issued_ts ? gmdate( 'j F Y', $fendigibadge_issued_ts ) : (string) $assertion->issued_on;
$fendigibadge_recipient_name = trim( (string) ( $assertion->recipient_name ?? '' ) );
$fendigibadge_description    = \FentonDigitalBadges\Badge_Class::description( $badge );
$fendigibadge_expires_label  = '';

if ( ! empty( $assertion->expires ) ) {
	$fendigibadge_expires_ts = strtotime( (string) $assertion->expires . ' UTC' );
	if ( false !== $fendigibadge_expires_ts ) {
		$fendigibadge_expires_label = gmdate( 'j F Y', $fendigibadge_expires_ts );
	}
}
?>
<div class="fendigibadge-attestation-shell">
	<div class="fendigibadge-attestation-layout">
		<article class="fendigibadge-attestation fendigibadge-attestation--certificate" itemscope itemtype="https://schema.org/EducationalOccupationalCredential">
			<div class="fendigibadge-attestation__frame">
				<p class="fendigibadge-attestation__eyebrow"><?php esc_html_e( 'Verified Open Badge', 'fenton-digital-badges' ); ?></p>

				<?php if ( '' !== $image_url ) : ?>
					<figure class="fendigibadge-attestation__figure">
						<img
							class="fendigibadge-attestation__image"
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $fendigibadge_badge_name ); ?>"
							itemprop="image"
							width="220"
							height="220"
						/>
					</figure>
				<?php endif; ?>

				<h1 class="fendigibadge-attestation__title" itemprop="name"><?php echo esc_html( $fendigibadge_badge_name ); ?></h1>

				<?php if ( '' !== $fendigibadge_recipient_name ) : ?>
					<p class="fendigibadge-attestation__earner">
						<span class="fendigibadge-attestation__earner-label"><?php esc_html_e( 'This certifies that', 'fenton-digital-badges' ); ?></span>
						<span class="fendigibadge-attestation__earner-name"><?php echo esc_html( $fendigibadge_recipient_name ); ?></span>
						<span class="fendigibadge-attestation__earner-label"><?php esc_html_e( 'has earned this badge', 'fenton-digital-badges' ); ?></span>
					</p>
				<?php else : ?>
					<p class="fendigibadge-attestation__earner">
						<span class="fendigibadge-attestation__earner-label"><?php esc_html_e( 'This certifies successful completion of the requirements for this badge', 'fenton-digital-badges' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( '' !== $fendigibadge_description ) : ?>
					<div class="fendigibadge-attestation__description" itemprop="description">
						<?php echo esc_html( $fendigibadge_description ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $assertion->evidence_url ) ) : ?>
					<p class="fendigibadge-attestation__evidence">
						<a class="fendigibadge-attestation__evidence-link" href="<?php echo esc_url( (string) $assertion->evidence_url ); ?>" rel="noopener noreferrer">
							<?php esc_html_e( 'View evidence', 'fenton-digital-badges' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<footer class="fendigibadge-attestation__footer">
					<div class="fendigibadge-attestation__footer-item">
						<span class="fendigibadge-attestation__footer-label"><?php esc_html_e( 'Issued by', 'fenton-digital-badges' ); ?></span>
						<span class="fendigibadge-attestation__issuer-row">
							<?php if ( '' !== $fendigibadge_issuer_logo ) : ?>
								<img class="fendigibadge-attestation__issuer-logo" src="<?php echo esc_url( $fendigibadge_issuer_logo ); ?>" alt="" width="32" height="32" />
							<?php endif; ?>
							<span class="fendigibadge-attestation__footer-value" itemprop="recognizedBy"><?php echo esc_html( $fendigibadge_issuer_name ); ?></span>
						</span>
					</div>
					<div class="fendigibadge-attestation__footer-item">
						<span class="fendigibadge-attestation__footer-label"><?php esc_html_e( 'Issued on', 'fenton-digital-badges' ); ?></span>
						<span class="fendigibadge-attestation__footer-value">
							<time datetime="<?php echo esc_attr( false !== $fendigibadge_issued_ts ? gmdate( 'c', $fendigibadge_issued_ts ) : '' ); ?>" itemprop="dateCreated">
								<?php echo esc_html( $fendigibadge_issued_label ); ?>
							</time>
						</span>
					</div>
					<?php if ( '' !== $fendigibadge_expires_label ) : ?>
						<div class="fendigibadge-attestation__footer-item">
							<span class="fendigibadge-attestation__footer-label"><?php esc_html_e( 'Expires', 'fenton-digital-badges' ); ?></span>
							<span class="fendigibadge-attestation__footer-value"><?php echo esc_html( $fendigibadge_expires_label ); ?></span>
						</div>
					<?php endif; ?>
				</footer>
			</div>
		</article>

		<section class="fendigibadge-attestation-tools" aria-label="<?php esc_attr_e( 'Share and download', 'fenton-digital-badges' ); ?>">
			<div class="fendigibadge-attestation__actions">
				<div class="fendigibadge-attestation__actions-primary">
					<a class="fendigibadge-attestation__btn fendigibadge-attestation__btn--primary" href="<?php echo esc_url( $linkedin_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Add to LinkedIn', 'fenton-digital-badges' ); ?>
					</a>
					<button
						type="button"
						class="fendigibadge-attestation__btn"
						data-fendigibadge-share
						data-fendigibadge-share-title="<?php echo esc_attr( $fendigibadge_badge_name ); ?>"
						data-fendigibadge-share-text="<?php echo esc_attr( $share_text ); ?>"
						data-fendigibadge-share-url="<?php echo esc_url( $attestation_url ); ?>"
						<?php if ( '' !== $image_url ) : ?>
							data-fendigibadge-share-image="<?php echo esc_url( $image_url ); ?>"
						<?php endif; ?>
						hidden
					>
						<?php esc_html_e( 'Share', 'fenton-digital-badges' ); ?>
					</button>
					<?php if ( '' !== $earn_url ) : ?>
						<a class="fendigibadge-attestation__btn" href="<?php echo esc_url( $earn_url ); ?>" rel="noopener noreferrer">
							<?php esc_html_e( 'Earn this badge', 'fenton-digital-badges' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="fendigibadge-attestation__actions-secondary">
					<?php if ( '' !== $image_url ) : ?>
						<a class="fendigibadge-attestation__btn fendigibadge-attestation__btn--ghost" href="<?php echo esc_url( $image_url ); ?>" download>
							<?php esc_html_e( 'Download badge', 'fenton-digital-badges' ); ?>
						</a>
					<?php endif; ?>
					<button
						type="button"
						class="fendigibadge-attestation__btn fendigibadge-attestation__btn--ghost"
						data-fendigibadge-toggle="#fendigibadge-assertion-json-panel"
						aria-expanded="false"
						aria-controls="fendigibadge-assertion-json-panel"
					>
						<?php esc_html_e( 'Assertion JSON', 'fenton-digital-badges' ); ?>
					</button>
				</div>

				<div id="fendigibadge-assertion-json-panel" class="fendigibadge-attestation__json-panel" hidden>
					<div class="fendigibadge-attestation__json-header">
						<label for="fendigibadge-assertion-json"><?php esc_html_e( 'Assertion JSON', 'fenton-digital-badges' ); ?></label>
						<a class="fendigibadge-attestation__json-link" href="<?php echo esc_url( $json_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open raw JSON', 'fenton-digital-badges' ); ?>
						</a>
					</div>
					<textarea id="fendigibadge-assertion-json" class="fendigibadge-attestation__json-code" rows="14" readonly><?php echo esc_textarea( $assertion_json ); ?></textarea>
					<button type="button" class="fendigibadge-attestation__btn fendigibadge-attestation__btn--ghost" data-fendigibadge-copy="#fendigibadge-assertion-json">
						<?php esc_html_e( 'Copy JSON', 'fenton-digital-badges' ); ?>
					</button>
				</div>
			</div>
		</section>
	</div>
</div>
