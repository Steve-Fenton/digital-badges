<?php
/**
 * Public attestation page for a single assertion.
 *
 * Expected vars: $assertion, $badge, $issuer, $linkedin_url, $attestation_url,
 * $embed_url, $json_url, $image_url, $embed_code, $assertion_json, $share_text
 *
 * @package DigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$badge_name     = get_the_title( $badge );
$issuer_name    = $issuer['name'] !== '' ? $issuer['name'] : get_bloginfo( 'name' );
$issuer_logo    = \DigitalBadges\Issuer::image_url();
$issued_ts      = strtotime( (string) $assertion->issued_on . ' UTC' );
$issued_label   = false !== $issued_ts ? gmdate( 'j F Y', $issued_ts ) : (string) $assertion->issued_on;
$recipient_name = trim( (string) ( $assertion->recipient_name ?? '' ) );
$description    = \DigitalBadges\Badge_Class::description( $badge );
$expires_label  = '';

if ( ! empty( $assertion->expires ) ) {
	$expires_ts = strtotime( (string) $assertion->expires . ' UTC' );
	if ( false !== $expires_ts ) {
		$expires_label = gmdate( 'j F Y', $expires_ts );
	}
}
?>
<div class="db-attestation-shell">
	<div class="db-attestation-layout">
		<article class="db-attestation db-attestation--certificate" itemscope itemtype="https://schema.org/EducationalOccupationalCredential">
			<div class="db-attestation__frame">
				<p class="db-attestation__eyebrow"><?php esc_html_e( 'Verified Open Badge', 'digital-badges' ); ?></p>

				<?php if ( '' !== $image_url ) : ?>
					<figure class="db-attestation__figure">
						<img
							class="db-attestation__image"
							src="<?php echo esc_url( $image_url ); ?>"
							alt="<?php echo esc_attr( $badge_name ); ?>"
							itemprop="image"
							width="220"
							height="220"
						/>
					</figure>
				<?php endif; ?>

				<h1 class="db-attestation__title" itemprop="name"><?php echo esc_html( $badge_name ); ?></h1>

				<?php if ( '' !== $recipient_name ) : ?>
					<p class="db-attestation__earner">
						<span class="db-attestation__earner-label"><?php esc_html_e( 'This certifies that', 'digital-badges' ); ?></span>
						<span class="db-attestation__earner-name"><?php echo esc_html( $recipient_name ); ?></span>
						<span class="db-attestation__earner-label"><?php esc_html_e( 'has earned this badge', 'digital-badges' ); ?></span>
					</p>
				<?php else : ?>
					<p class="db-attestation__earner">
						<span class="db-attestation__earner-label"><?php esc_html_e( 'This certifies successful completion of the requirements for this badge', 'digital-badges' ); ?></span>
					</p>
				<?php endif; ?>

				<?php if ( '' !== $description ) : ?>
					<div class="db-attestation__description" itemprop="description">
						<?php echo esc_html( $description ); ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $assertion->evidence_url ) ) : ?>
					<p class="db-attestation__evidence">
						<a class="db-attestation__evidence-link" href="<?php echo esc_url( (string) $assertion->evidence_url ); ?>" rel="noopener noreferrer">
							<?php esc_html_e( 'View evidence', 'digital-badges' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<footer class="db-attestation__footer">
					<div class="db-attestation__footer-item">
						<span class="db-attestation__footer-label"><?php esc_html_e( 'Issued by', 'digital-badges' ); ?></span>
						<span class="db-attestation__issuer-row">
							<?php if ( '' !== $issuer_logo ) : ?>
								<img class="db-attestation__issuer-logo" src="<?php echo esc_url( $issuer_logo ); ?>" alt="" width="32" height="32" />
							<?php endif; ?>
							<span class="db-attestation__footer-value" itemprop="recognizedBy"><?php echo esc_html( $issuer_name ); ?></span>
						</span>
					</div>
					<div class="db-attestation__footer-item">
						<span class="db-attestation__footer-label"><?php esc_html_e( 'Issued on', 'digital-badges' ); ?></span>
						<span class="db-attestation__footer-value">
							<time datetime="<?php echo esc_attr( false !== $issued_ts ? gmdate( 'c', $issued_ts ) : '' ); ?>" itemprop="dateCreated">
								<?php echo esc_html( $issued_label ); ?>
							</time>
						</span>
					</div>
					<?php if ( '' !== $expires_label ) : ?>
						<div class="db-attestation__footer-item">
							<span class="db-attestation__footer-label"><?php esc_html_e( 'Expires', 'digital-badges' ); ?></span>
							<span class="db-attestation__footer-value"><?php echo esc_html( $expires_label ); ?></span>
						</div>
					<?php endif; ?>
				</footer>
			</div>
		</article>

		<section class="db-attestation-tools" aria-label="<?php esc_attr_e( 'Share and download', 'digital-badges' ); ?>">
			<div class="db-attestation__actions">
				<div class="db-attestation__actions-primary">
					<a class="db-attestation__btn db-attestation__btn--primary" href="<?php echo esc_url( $linkedin_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Add to LinkedIn', 'digital-badges' ); ?>
					</a>
					<?php if ( '' !== $image_url ) : ?>
						<a class="db-attestation__btn" href="<?php echo esc_url( $image_url ); ?>" download>
							<?php esc_html_e( 'Download badge', 'digital-badges' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="db-attestation__actions-secondary">
					<button
						type="button"
						class="db-attestation__btn db-attestation__btn--ghost"
						data-db-share
						data-db-share-title="<?php echo esc_attr( $badge_name ); ?>"
						data-db-share-text="<?php echo esc_attr( $share_text ); ?>"
						data-db-share-url="<?php echo esc_url( $attestation_url ); ?>"
						hidden
					>
						<?php esc_html_e( 'Share', 'digital-badges' ); ?>
					</button>
					<button
						type="button"
						class="db-attestation__btn db-attestation__btn--ghost"
						data-db-toggle="#db-assertion-json-panel"
						aria-expanded="false"
						aria-controls="db-assertion-json-panel"
					>
						<?php esc_html_e( 'Assertion JSON', 'digital-badges' ); ?>
					</button>
				</div>

				<div id="db-assertion-json-panel" class="db-attestation__json-panel" hidden>
					<div class="db-attestation__json-header">
						<label for="db-assertion-json"><?php esc_html_e( 'Assertion JSON', 'digital-badges' ); ?></label>
						<a class="db-attestation__json-link" href="<?php echo esc_url( $json_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Open raw JSON', 'digital-badges' ); ?>
						</a>
					</div>
					<textarea id="db-assertion-json" class="db-attestation__json-code" rows="14" readonly><?php echo esc_textarea( $assertion_json ); ?></textarea>
					<button type="button" class="db-attestation__btn db-attestation__btn--ghost" data-db-copy="#db-assertion-json">
						<?php esc_html_e( 'Copy JSON', 'digital-badges' ); ?>
					</button>
				</div>
			</div>

			<div class="db-attestation__embed">
				<h2 class="db-attestation__embed-title"><?php esc_html_e( 'Embed this badge', 'digital-badges' ); ?></h2>
				<p class="db-attestation__embed-intro"><?php esc_html_e( 'Copy this code to display the badge with a link to this page:', 'digital-badges' ); ?></p>
				<label class="screen-reader-text" for="db-embed-code"><?php esc_html_e( 'Embed code', 'digital-badges' ); ?></label>
				<textarea id="db-embed-code" class="db-attestation__embed-code" rows="3" readonly><?php echo esc_textarea( $embed_code ); ?></textarea>
				<button type="button" class="db-attestation__btn db-attestation__btn--ghost" data-db-copy="#db-embed-code">
					<?php esc_html_e( 'Copy embed code', 'digital-badges' ); ?>
				</button>
			</div>
		</section>
	</div>
</div>
