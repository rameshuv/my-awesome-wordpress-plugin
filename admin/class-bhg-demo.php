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
                $hunts_table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$hunts_table} WHERE title LIKE %s", '%(Demo)%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $tours_table = esc_sql( $wpdb->prefix . 'bhg_tournaments' );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$tours_table} WHERE title LIKE %s", '%(Demo)%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Insert demo hunt.
                $wpdb->insert(
                        $hunts_table,
			array(
				'title'            => 'Sample Hunt (Demo)',
				'starting_balance' => 1000,
				'num_bonuses'      => 5,
				'status'           => 'open',
			)
		);

		// Insert demo tournament.
                $wpdb->insert(
                        $tours_table,
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
