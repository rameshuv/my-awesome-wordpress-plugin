<?php
/**
* Utility functions and helpers for Bonus Hunt Guesser plugin.
*
* @package Bonus_Hunt_Guesser
*/

// phpcs:disable WordPress.Files.FileOrganization

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* General utility methods used throughout the plugin.
*/
class BHG_Utils {
	/**
	 * Register hooks used by utility functions.
	 *
	 * @return void
	 */
	public static function init_hooks() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
	}

	/**
	 * Register shortcodes handled in the shortcode constructor.
	 *
	 * @return void
	 */
	public static function register_shortcodes() {
		// Handled in BHG_Shortcodes constructor, kept for legacy.
	}

	/**
	 * Retrieve plugin settings merged with defaults.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_settings() {
		$defaults = array(
			'allow_guess_edit' => 1,
			'ads_enabled'      => 1,
			'email_from'       => get_bloginfo( 'admin_email' ),
		);
		$opt      = get_option( 'bhg_settings', array() );
		if ( ! is_array( $opt ) ) {
			$opt = array();
		}
		return wp_parse_args( $opt, $defaults );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $data New settings data.
	 * @return array Updated settings.
	 */
	public static function update_settings( $data ) {
		$current = self::get_settings();
		$new     = array_merge( $current, $data );
		update_option( 'bhg_settings', $new );
		return $new;
	}

	/**
	 * Require manage options capability or abort.
	 *
	 * @return void
	 */
	public static function require_cap() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'bonus-hunt-guesser' ) );
		}
	}

	/**
	 * Output a nonce field for the given action.
	 *
	 * @param string $action Action name.
	 * @return void
	 */
	public static function nonce_field( $action ) {
		wp_nonce_field( $action, $action . '_nonce' );
	}

	/**
	 * Verify a nonce for the given action.
	 *
	 * @param string $action Action name.
	 * @return bool Whether the nonce is valid.
	 */
	public static function verify_nonce( $action ) {
		return isset( $_POST[ $action . '_nonce' ] )
			&& wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ $action . '_nonce' ] ) ),
				$action
			);
	}

	/**
	 * Execute a callback during template redirect after conditionals are set up.
	 *
	 * @param callable $cb Callback to execute.
	 * @return void
	 */
	public static function safe_query_conditionals( callable $cb ) {
		add_action(
			'template_redirect',
			function () use ( $cb ) {
				$cb();
			}
		);
	}
}

// phpcs:enable WordPress.Files.FileOrganization
