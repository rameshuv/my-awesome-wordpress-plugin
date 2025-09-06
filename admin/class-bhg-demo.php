<?php
/**
 * Demo data seeding utilities.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles demo reseeding actions.
 */
class BHG_Demo {
	/**
	 * Register handlers for demo reseeding.
	 */
	public function __construct() {
		add_action( 'admin_post_bhg_demo_reseed', array( $this, 'reseed' ) );
	}

	/**
	 * Reseed demo data.
	 *
	 * @return void
	 */
	public function reseed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
		}

		check_admin_referer( 'bhg_demo_reseed' );

		bhg_reset_demo_and_seed();

		wp_safe_redirect( admin_url( 'admin.php?page=bhg-tools&demo_reset=1' ) );
		exit;
	}
}
new BHG_Demo();
