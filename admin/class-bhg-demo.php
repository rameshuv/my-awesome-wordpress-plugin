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
		check_admin_referer( 'bhg_demo_reseed' );
		global $wpdb;

		// Wipe demo data.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bhg_bonus_hunts WHERE title LIKE '%(Demo)%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bhg_tournaments WHERE title LIKE '%(Demo)%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Insert demo hunt.
		$wpdb->insert(
			"{$wpdb->prefix}bhg_bonus_hunts",
			array(
				'title'            => 'Sample Hunt (Demo)',
				'starting_balance' => 1000,
				'num_bonuses'      => 5,
				'status'           => 'open',
			)
		);

		// Insert demo tournament.
		$wpdb->insert(
			"{$wpdb->prefix}bhg_tournaments",
			array(
				'title'  => 'August Tournament (Demo)',
				'status' => 'active',
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=bhg-tools&demo_reset=1' ) );
		exit;
	}
}
new BHG_Demo();
