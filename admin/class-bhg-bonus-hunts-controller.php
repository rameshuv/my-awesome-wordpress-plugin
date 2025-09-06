<?php
/**
 * Admin controller for bonus hunt forms.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHG_Bonus_Hunts_Controller' ) ) {
	/**
	 * Handles create, update and delete actions for bonus hunts.
	 */
	class BHG_Bonus_Hunts_Controller {
		/**
		 * Singleton instance.
		 *
		 * @var BHG_Bonus_Hunts_Controller|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return BHG_Bonus_Hunts_Controller
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {}

		/**
		 * Initialize hooks.
		 *
		 * @return void
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'handle_form_submissions' ) );
		}

		/**
		 * Retrieve data for bonus hunt admin views.
		 *
		 * @return array
		 */
		public function get_admin_view_vars() {
			$db = new BHG_DB();

			return array(
				'bonus_hunts'     => $db->get_all_bonus_hunts(),
				'affiliate_sites' => $db->get_affiliate_websites(),
			);
		}


		/**
		 * Handle bonus hunt form submissions.
		 *
		 * @return void
		 */
		public function handle_form_submissions() {
			if ( empty( $_POST['bhg_action'] ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
			}

			$action       = sanitize_text_field( wp_unslash( $_POST['bhg_action'] ) );
			$nonce        = isset( $_POST['bhg_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bhg_nonce'] ) ) : '';
			$nonce_action = 'bhg_' . $action;

			if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
				wp_die( 'Security check failed' );
			}

			$db      = new BHG_DB();
			$message = 'error';

			switch ( $action ) {
				case 'create_bonus_hunt':
					$title             = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
					$starting_balance  = floatval( $_POST['starting_balance'] ?? 0 );
					$num_bonuses       = intval( $_POST['num_bonuses'] ?? 0 );
					$prizes            = sanitize_textarea_field( wp_unslash( $_POST['prizes'] ?? '' ) );
					$status            = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
					$affiliate_site_id = isset( $_POST['affiliate_site_id'] ) ? intval( $_POST['affiliate_site_id'] ) : 0;

					$result = $db->create_bonus_hunt(
						array(
							'title'             => $title,
							'starting_balance'  => $starting_balance,
							'num_bonuses'       => $num_bonuses,
							'prizes'            => $prizes,
							'status'            => $status,
							'affiliate_site_id' => $affiliate_site_id,
							'created_by'        => get_current_user_id(),
							'created_at'        => current_time( 'mysql' ),
						)
					);

					$message = $result ? 'success' : 'error';
					break;

				case 'update_bonus_hunt':
					$id                = intval( $_POST['id'] ?? 0 );
					$title             = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
					$starting_balance  = floatval( $_POST['starting_balance'] ?? 0 );
					$num_bonuses       = intval( $_POST['num_bonuses'] ?? 0 );
					$prizes            = sanitize_textarea_field( wp_unslash( $_POST['prizes'] ?? '' ) );
					$status            = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
					$final_balance     = isset( $_POST['final_balance'] ) ? floatval( $_POST['final_balance'] ) : null;
					$affiliate_site_id = isset( $_POST['affiliate_site_id'] ) ? intval( $_POST['affiliate_site_id'] ) : 0;

					$result = $db->update_bonus_hunt(
						$id,
						array(
							'title'             => $title,
							'starting_balance'  => $starting_balance,
							'num_bonuses'       => $num_bonuses,
							'prizes'            => $prizes,
							'status'            => $status,
							'final_balance'     => $final_balance,
							'affiliate_site_id' => $affiliate_site_id,
						)
					);

					if ( $result && 'closed' === $status && null !== $final_balance ) {
						if ( class_exists( 'BHG_Models' ) ) {
							$winner_ids = BHG_Models::close_hunt( $id, $final_balance );
							if ( function_exists( 'bhg_send_hunt_results_email' ) ) {
								bhg_send_hunt_results_email( $id, $winner_ids );
							}
						}
					}

					$message = $result ? 'updated' : 'error';
					break;

				case 'delete_bonus_hunt':
					$id      = intval( $_POST['id'] ?? 0 );
					$result  = $db->delete_bonus_hunt( $id );
					$message = $result ? 'deleted' : 'error';
					break;
			}

			$url = esc_url_raw( add_query_arg( 'message', $message, wp_get_referer() ) );
			wp_safe_redirect( $url );
			exit;
		}
	}
}
