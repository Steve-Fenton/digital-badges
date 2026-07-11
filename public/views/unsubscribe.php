<?php
/**
 * Unsubscribe confirmation page.
 *
 * Expected vars:
 * - $fendigibadge_success (bool)
 *
 * @package FentonDigitalBadges
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fendigibadge_success = isset( $fendigibadge_success ) && $fendigibadge_success;
?>
<div class="fendigibadge-unsubscribe">
	<header class="fendigibadge-unsubscribe__header">
		<h1 class="fendigibadge-unsubscribe__title"><?php esc_html_e( 'Email notifications', 'fenton-digital-badges' ); ?></h1>
	</header>

	<?php if ( $fendigibadge_success ) : ?>
		<p class="fendigibadge-unsubscribe__notice" role="status">
			<?php esc_html_e( 'You will not receive future badge lookup emails at this address.', 'fenton-digital-badges' ); ?>
		</p>
	<?php else : ?>
		<p class="fendigibadge-unsubscribe__notice" role="status">
			<?php esc_html_e( 'This unsubscribe link is invalid or could not be verified.', 'fenton-digital-badges' ); ?>
		</p>
	<?php endif; ?>
</div>
