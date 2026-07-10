<?php
/**
 * Public attestation page for a single assertion.
 *
 * Expected vars: $assertion, $badge, $issuer, $linkedin_url, $attestation_url,
 * $embed_url, $json_url, $image_url, $embed_code, $assertion_json, $share_text
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fenton_digital_badges_badge_name     = get_the_title( $badge );
$fenton_digital_badges_issuer_name    = $issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' );
$fenton_digital_badges_issuer_logo    = \FentonDigitalBadges\Issuer::image_url();
$fenton_digital_badges_issued_ts      = strtotime( (string) $assertion->issued_on . ' UTC' );
$fenton_digital_badges_issued_label   = false !== $fenton_digital_badges_issued_ts ? gmdate( 'j F Y', $fenton_digital_badges_issued_ts ) : (string) $assertion->issued_on;
$fenton_digital_badges_recipient_name = trim( (string) ( $assertion->recipient_name ?? '' ) );
$fenton_digital_badges_description    = \FentonDigitalBadges\Badge_Class::description( $badge );
$fenton_digital_badges_expires_label  = '';

if ( ! empty( $assertion->expires ) ) {
	$fenton_digital_badges_expires_ts = strtotime( (string) $assertion->expires . ' UTC' );
	if ( false !== $fenton_digital_badges_expires_ts ) {
		$fenton_digital_badges_expires_label = gmdate( 'j F Y', $fenton_digital_badges_expires_ts );
	}
}
?>
<div class="db-attestation-shell">
	<div class="db-attestation-layout">
		<article class="db-attestation db-attestation--certificate" itemscope itemtype="https://schema.org/EducationalOccupationalCredential">
			<div class="db-attestation__frame">
				<p class="db-attestation__eyebrow"><?php esc_html_e( 'Verified Open Badge', 'fenton-digital-badges' ); ?></p>

				<?php if ( '' !== $image_url ) : ?>
					<figure class="db-attestation__figure">
						<img
							class="db-attestation__image"
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $fenton_digital_badges_badge_name ); ?>"
							itemprop="image"
							width="220"
							height="220"
						/>
					</figure>
				<?php endif; ?>

				<h1 class="db-attestation__title" itemprop="name"><?php echo esc_html( $fenton_digital_badges_badge_name ); ?></h1>

				<?php if ( '' !== $fenton_digital_badges_recipient_name ) : ?>
					<p class="db-attestation__earner">
						<span class="db-attestation__earner-label"><?php esc_html_e( 'This certifies that', 'fenton-digital-badges' ); ?></span>
						<span class="db-attestation__earner-name"><?php echo esc_html( $fenton_digital_badges_recipient_name ); ?></span>
						<span class="db-attestation__earner-label"><?php esc_html_e( 'has earned this badge', 'fenton-digital-badges' ); ?></span>
					</p>
				<?php else : ?>
					<p class="db-attestation__earner">
						<span class="db-attestation__earner-label"><?php esc_html_e( 'This certifies successful completion of the requirements for this badge', 'fenton-digital-badges' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( '' !== $fenton_digital_badges_description ) : ?>
					<div class="db-attestation__description" itemprop="description">
						<?php echo esc_html( $fenton_digital_badges_description ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $assertion->evidence_url ) ) : ?>
					<p class="db-attestation__evidence">
						<a class="db-attestation__evidence-link" href="<?php echo esc_url( (string) $assertion->evidence_url ); ?>" rel="noopener noreferrer">
							<?php esc_html_e( 'View evidence', 'fenton-digital-badges' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<footer class="db-attestation__footer">
					<div class="db-attestation__footer-item">
						<span class="db-attestation__footer-label"><?php esc_html_e( 'Issued by', 'fenton-digital-badges' ); ?></span>
						<span class="db-attestation__issuer-row">
							<?php if ( '' !== $fenton_digital_badges_issuer_logo ) : ?>
								<img class="db-attestation__issuer-logo" src="<?php echo esc_url( $fenton_digital_badges_issuer_logo ); ?>" alt="" width="32" height="32" />
							<?php endif; ?>
							<span class="db-attestation__footer-value" itemprop="recognizedBy"><?php echo esc_html( $fenton_digital_badges_issuer_name ); ?></span>
						</span>
					</div>
					<div class="db-attestation__footer-item">
						<span class="db-attestation__footer-label"><?php esc_html_e( 'Issued on', 'fenton-digital-badges' ); ?></span>
						<span class="db-attestation__footer-value">
							<time datetime="<?php echo esc_attr( false !== $fenton_digital_badges_issued_ts ? gmdate( 'c', $fenton_digital_badges_issued_ts ) : '' ); ?>" itemprop="dateCreated">
								<?php echo esc_html( $fenton_digital_badges_issued_label ); ?>
							</time>
						</span>
					</div>
					<?php if ( '' !== $fenton_digital_badges_expires_label ) : ?>
						<div class="db-attestation__footer-item">
							<span class="db-attestation__footer-label"><?php esc_html_e( 'Expires', 'fenton-digital-badges' ); ?></span>
							<span class="db-attestation__footer-value"><?php echo esc_html( $fenton_digital_badges_expires_label ); ?></span>
						</div>
					<?php endif; ?>
				</footer>
			</div>
		</article>

		<section class="db-attestation-tools" aria-label="<?php esc_attr_e( 'Share and download', 'fenton-digital-badges' ); ?>">
			<div class="db-attestation__actions">
				<div class="db-attestation__actions-primary">
					<a class="db-attestation__btn db-attestation__btn--primary" href="<?php echo esc_url( $linkedin_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Add to LinkedIn', 'fenton-digital-badges' ); ?>
					</a>
					<?php if ( '' !== $image_url ) : ?>
						<a class="db-attestation__btn" href="<?php echo esc_url( $image_url ); ?>" download>
							<?php esc_html_e( 'Download badge', 'fenton-digital-badges' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="db-attestation__actions-secondary">
					<button
						type="button"
						class="db-attestation__btn db-attestation__btn--ghost"
						data-db-share
						data-db-share-title="<?php echo esc_attr( $fenton_digital_badges_badge_name ); ?>"
						data-db-share-text="<?php echo esc_attr( $share_text ); ?>"
						data-db-share-url="<?php echo esc_url( $attestation_url ); ?>"
						hidden
					>
						<?php esc_html_e( 'Share', 'fenton-digital-badges' ); ?>
					</button>
					<button
						type="button"
						class="db-attestation__btn db-attestation__btn--ghost"
						data-db-toggle="#db-assertion-json-panel"
						aria-expanded="false"
						aria-controls="db-assertion-json-panel"
					>
						<?php esc_html_e( 'Assertion JSON', 'fenton-digital-badges' ); ?>
					</button>
				</div>

				<div id="db-assertion-json-panel" class="db-attestation__json-panel" hidden>
					<div class="db-attestation__json-header">
						<label for="db-assertion-json"><?php esc_html_e( 'Assertion JSON', 'fenton-digital-badges' ); ?></label>
						<a class="db-attestation__json-link" href="<?php echo esc_url( $json_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open raw JSON', 'fenton-digital-badges' ); ?>
						</a>
					</div>
					<textarea id="db-assertion-json" class="db-attestation__json-code" rows="14" readonly><?php echo esc_textarea( $assertion_json ); ?></textarea>
					<button type="button" class="db-attestation__btn db-attestation__btn--ghost" data-db-copy="#db-assertion-json">
						<?php esc_html_e( 'Copy JSON', 'fenton-digital-badges' ); ?>
					</button>
				</div>
			</div>

			<div class="db-attestation__embed">
				<h2 class="db-attestation__embed-title"><?php esc_html_e( 'Embed this badge', 'fenton-digital-badges' ); ?></h2>
				<p class="db-attestation__embed-intro"><?php esc_html_e( 'Copy this code to display the badge with a link to this page:', 'fenton-digital-badges' ); ?></p>
				<label class="screen-reader-text" for="db-embed-code"><?php esc_html_e( 'Embed code', 'fenton-digital-badges' ); ?></label>
				<textarea id="db-embed-code" class="db-attestation__embed-code" rows="3" readonly><?php echo esc_textarea( $embed_code ); ?></textarea>
				<button type="button" class="db-attestation__btn db-attestation__btn--ghost" data-db-copy="#db-embed-code">
					<?php esc_html_e( 'Copy embed code', 'fenton-digital-badges' ); ?>
				</button>
			</div>
		</section>
	</div>
</div>
