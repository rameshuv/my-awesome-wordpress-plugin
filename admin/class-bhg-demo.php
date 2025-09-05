<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Demo {
	public function __construct() {
		add_action('admin_menu', [$this,'demo_menu']);
		add_action('admin_post_bhg_demo_reseed', [$this,'reseed']);
	}

	public function demo_menu() {
		add_submenu_page(
			'bhg_dashboard',
			__('Demo Tools','bonus-hunt-guesser'),
			__('Demo Tools','bonus-hunt-guesser'),
			'manage_options',
			'bhg_demo',
			[$this,'render_demo']
		);
	}

	public function render_demo() {
		echo '<div class="wrap"><h1>Demo Tools</h1>';
               echo '<form method="post" action="'.admin_url('admin-post.php').'">';
               echo '<input type="hidden" name="action" value="bhg_demo_reseed" />';
               wp_nonce_field( 'bhg_demo_reseed' );
               submit_button(__('Reset & Reseed Demo','bonus-hunt-guesser'));
               echo '</form></div>';
       }

       public function reseed() {
               check_admin_referer( 'bhg_demo_reseed' );
               global $wpdb;

               // Wipe demo data
               $wpdb->query( "DELETE FROM {$wpdb->prefix}bhg_bonus_hunts WHERE title LIKE '%(Demo)%'" );
               $wpdb->query( "DELETE FROM {$wpdb->prefix}bhg_tournaments WHERE title LIKE '%(Demo)%'" );

		// Insert demo hunt
		$wpdb->insert("{$wpdb->prefix}bhg_bonus_hunts",[
			'title'=>'Sample Hunt (Demo)',
			'starting_balance'=>1000,
			'num_bonuses'=>5,
			'status'=>'open'
		]);

		// Insert demo tournament
		$wpdb->insert("{$wpdb->prefix}bhg_tournaments",[
			'title'=>'August Tournament (Demo)',
			'status'=>'active'
		]);

		wp_safe_redirect( admin_url( 'admin.php?page=bhg_demo&demo_reset=1' ) );
		exit;
	}
}
new BHG_Demo();
