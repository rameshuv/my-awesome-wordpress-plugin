<?php
// phpcs:ignoreFile -- Legacy view requires refactoring for WordPress coding standards.
/**
 * Demo tools view.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

$notice = '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( 1 === absint( wp_unslash( $_GET['demo_reset'] ?? '' ) ) ) {
	$notice = __( 'Demo data reseeded.', 'bonus-hunt-guesser' );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Demo Tools', 'bonus-hunt-guesser' ); ?></h1>
	<?php if ( $notice ) : ?>
	<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="bhg_demo_reseed">
	<?php wp_nonce_field( 'bhg_demo_reseed' ); ?>
	<p><?php esc_html_e( 'This will delete all demo data and pages, then recreate fresh demo content.', 'bonus-hunt-guesser' ); ?></p>
	<p><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Reset & Reseed Demo Data', 'bonus-hunt-guesser' ); ?>"></p>
	</form>
</div>
