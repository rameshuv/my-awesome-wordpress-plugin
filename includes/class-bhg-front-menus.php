<?php
/**
 * Front-end menu handling.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end menus.
 */
class BHG_Front_Menus {

	/**
	 * Set up hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_locations' ) );
		add_shortcode( 'bhg_nav', array( $this, 'nav_shortcode' ) );
		add_shortcode( 'bhg_menu', array( __CLASS__, 'menu_shortcode' ) );
	}

	/**
	 * Register menu locations.
	 *
	 * @return void
	 */
	public function register_locations() {
		register_nav_menus(
			array(
				'bhg_menu_admin' => __( 'BHG Admin/Moderator Menu', 'bonus-hunt-guesser' ),
				'bhg_menu_user'  => __( 'BHG Logged-in User Menu', 'bonus-hunt-guesser' ),
				'bhg_menu_guest' => __( 'BHG Guest Menu', 'bonus-hunt-guesser' ),
			)
		);
	}

	/**
	 * Render navigation based on provided attributes.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Menu markup.
	 */
	public function nav_shortcode( $atts ) {
		$a   = shortcode_atts( array( 'area' => 'guest' ), $atts, 'bhg_nav' );
		$loc = 'bhg_menu_guest';
		if ( 'admin' === $a['area'] && current_user_can( 'edit_posts' ) ) {
			$loc = 'bhg_menu_admin';
		} elseif ( 'user' === $a['area'] && is_user_logged_in() ) {
			$loc = 'bhg_menu_user';
		} elseif ( 'guest' === $a['area'] && ! is_user_logged_in() ) {
			$loc = 'bhg_menu_guest';
		} elseif ( is_user_logged_in() ) {
			$loc = 'bhg_menu_user';
		}

		$out = wp_nav_menu(
			array(
				'theme_location'  => $loc,
				'container'       => 'nav',
				'container_class' => 'bhg-nav',
				'echo'            => false,
			)
		);

		if ( ! $out ) {
			return '<!-- BHG menu not assigned: ' . esc_html( $loc ) . ' -->';
		}
		return $out;
	}

	/**
	 * Render the correct menu location based on role/login.
	 *
	 * @param array $args Menu arguments.
	 * @return string Menu markup.
	 */
	public static function render_role_menu( $args = array() ) {
		if ( current_user_can( 'manage_options' ) || current_user_can( 'moderate_comments' ) ) {
			$loc = 'bhg_menu_admin';
		} elseif ( is_user_logged_in() ) {
			$loc = 'bhg_menu_user';
		} else {
			$loc = 'bhg_menu_guest';
		}

		$defaults = array(
			'theme_location'  => $loc,
			'container'       => 'nav',
			'container_class' => 'bhg-menu',
			'fallback_cb'     => false,
			'echo'            => false,
		);
		$args     = wp_parse_args( $args, $defaults );
		$menu     = wp_nav_menu( $args );
		if ( ! $menu ) {
			// Fallback message is escaped.
			$menu = '<nav class="bhg-menu"><ul><li>' . esc_html__( 'Menu not assigned.', 'bonus-hunt-guesser' ) . '</li></ul></nav>';
		}
		return $menu;
	}

	/**
	 * Shortcode: [bhg_menu].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Menu markup.
	 */
	public static function menu_shortcode( $atts ) {
		unset( $atts );
		return self::render_role_menu();
	}
}

/* Stage-5 menu help. */
if ( is_admin() ) {
	add_action(
		'admin_notices',
		function () {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification
			if ( strpos( $page, 'bhg' ) !== false ) {
				echo '<div class="notice notice-info"><p>';
				esc_html_e( 'Reminder: Assign your BHG menus (Admin/Moderator, Logged-in, Guest) under Appearance → Menus → Manage Locations. Use shortcode [bhg_nav] to display.', 'bonus-hunt-guesser' );
				echo '</p></div>';
			}
		}
	);
}
