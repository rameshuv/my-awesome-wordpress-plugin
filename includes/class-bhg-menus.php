<?php
/**
 * Admin menu handler.
 *
 * @package bonus-hunt-guesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BHG_Menus' ) ) {
	/**
	 * Handle admin menus and related helpers.
	 */
	class BHG_Menus {
		/**
		 * Singleton instance.
		 *
		 * @var BHG_Menus|null
		 */
		private static $instance = null;

		/**
		 * Whether the class has been initialized.
		 *
		 * @var bool
		 */
		private $initialized = false;

		/**
		 * Retrieve singleton instance.
		 *
		 * @return BHG_Menus
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
		 * Initialize the menus class.
		 *
		 * @return void
		 */
		public function init() {
			if ( $this->initialized ) {
				return;
			}
			$this->initialized = true;

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
			add_action( 'init', array( $this, 'register_locations' ), 5 );
		}

		/**
		 * Enqueue admin assets.
		 *
		 * @param string $hook Current admin page hook.
		 *
		 * @return void
		 */
		public function assets( $hook ) {
			if ( false !== strpos( $hook, 'bhg' ) ) {
				wp_enqueue_style( 'bhg-admin', BHG_PLUGIN_URL . 'assets/css/admin.css', array(), defined( 'BHG_VERSION' ) ? BHG_VERSION : null );
				wp_enqueue_script( 'bhg-admin', BHG_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), defined( 'BHG_VERSION' ) ? BHG_VERSION : null, true );
			}
		}
		/**
		 * Register admin menus.
		 *
		 * @return void
		 */
		public function admin_menu() {
			// Prevent duplicate top-level menu.
			global $menu;
			foreach ( (array) $menu as $item ) {
				if ( isset( $item[2] ) && 'bhg' === $item[2] ) {
					return;
				}
			}

			$cap  = $this->admin_capability();
			$slug = 'bhg';

			add_menu_page(
				__( 'Bonus Hunt Guesser', 'bonus-hunt-guesser' ),
				__( 'Bonus Hunt Guesser', 'bonus-hunt-guesser' ),
				$cap,
				$slug,
				array( $this, 'render_dashboard' ),
				'dashicons-awards',
				26
			);

			add_submenu_page( $slug, __( 'Dashboard', 'bonus-hunt-guesser' ), __( 'Dashboard', 'bonus-hunt-guesser' ), $cap, $slug, array( $this, 'render_dashboard' ) );
			add_submenu_page( $slug, __( 'Bonus Hunts', 'bonus-hunt-guesser' ), __( 'Bonus Hunts', 'bonus-hunt-guesser' ), $cap, 'bhg-bonus-hunts', array( $this, 'render_bonus_hunts' ) );
			add_submenu_page( $slug, __( 'Users', 'bonus-hunt-guesser' ), __( 'Users', 'bonus-hunt-guesser' ), $cap, 'bhg-users', array( $this, 'render_users' ) );
			add_submenu_page( $slug, __( 'Affiliate Websites', 'bonus-hunt-guesser' ), __( 'Affiliate Websites', 'bonus-hunt-guesser' ), $cap, 'bhg-affiliate-websites', array( $this, 'render_affiliates' ) );
			add_submenu_page( $slug, __( 'Tournaments', 'bonus-hunt-guesser' ), __( 'Tournaments', 'bonus-hunt-guesser' ), $cap, 'bhg-tournaments', array( $this, 'render_tournaments' ) );
			add_submenu_page( $slug, __( 'Translations', 'bonus-hunt-guesser' ), __( 'Translations', 'bonus-hunt-guesser' ), $cap, 'bhg-translations', array( $this, 'render_translations' ) );
			add_submenu_page( $slug, __( 'Settings', 'bonus-hunt-guesser' ), __( 'Settings', 'bonus-hunt-guesser' ), $cap, 'bhg-settings', array( $this, 'render_settings' ) );
			add_submenu_page( $slug, __( 'Database', 'bonus-hunt-guesser' ), __( 'Database', 'bonus-hunt-guesser' ), $cap, 'bhg-database', array( $this, 'render_database' ) );
			add_submenu_page( $slug, __( 'Tools', 'bonus-hunt-guesser' ), __( 'Tools', 'bonus-hunt-guesser' ), $cap, 'bhg-tools', array( $this, 'render_tools' ) );
		}

		/**
		 * Get the capability required to access admin pages.
		 *
		 * @return string
		 */
		private function admin_capability() {
			return apply_filters( 'bhg_admin_capability', 'manage_options' );
		}

		/**
		 * Render a specified admin view.
		 *
		 * @param string $view View name.
		 * @param array  $vars Variables to pass to the view.
		 *
		 * @return void
		 */
		public function view( $view, $vars = array() ) {
			if ( ! current_user_can( $this->admin_capability() ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
			}

			if ( is_array( $vars ) ) {
				foreach ( $vars as $key => $value ) {
					if ( is_string( $key ) && preg_match( '/^[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*$/', $key ) ) {
						${$key} = $value;
					}
				}
			}

			$header_path = BHG_PLUGIN_DIR . 'admin/views/header.php';
			if ( file_exists( $header_path ) ) {
				include $header_path;
			}

			$view      = sanitize_file_name( $view );
			$view_path = BHG_PLUGIN_DIR . 'admin/views/' . $view . '.php';
			if ( file_exists( $view_path ) ) {
				include $view_path;
			} else {
				echo '<div class=\'wrap\'><h2>' . esc_html__( 'View Not Found', 'bonus-hunt-guesser' ) . '</h2>';
				echo '<p>' . sprintf(
					/* translators: %s: requested view name. */
					esc_html__( 'The requested view "%s" was not found.', 'bonus-hunt-guesser' ),
					esc_html( $view )
				) . '</p></div>';
			}
		}

		/**
		 * Render the Dashboard page.
		 *
		 * @return void
		 */
		public function render_dashboard() {
			$this->view( 'dashboard' );
		}

		/**
		 * Render the Bonus Hunts page.
		 *
		 * @return void
		 */
		public function render_bonus_hunts() {
			$this->view( 'bonus-hunts' );
		}

		/**
		 * Render the Users page.
		 *
		 * @return void
		 */
		public function render_users() {
			$this->view( 'users' );
		}

		/**
		 * Render the Affiliate Websites page.
		 *
		 * @return void
		 */
		public function render_affiliates() {
			$this->view( 'affiliate-websites' );
		}

		/**
		 * Render the Tournaments page.
		 *
		 * @return void
		 */
		public function render_tournaments() {
			$this->view( 'tournaments' );
		}

		/**
		 * Render the Translations page.
		 *
		 * @return void
		 */
		public function render_translations() {
			$this->view( 'translations' );
		}

		/**
		 * Render the Settings page.
		 *
		 * @return void
		 */
		public function render_settings() {
			$this->view( 'settings' );
		}

		/**
		 * Render the Database page.
		 *
		 * @return void
		 */
		public function render_database() {
			$this->view( 'database' );
		}

		/**
		 * Render the Tools page.
		 *
		 * @return void
		 */
		public function render_tools() {
			$this->view( 'tools' );
		}

		/**
		 * Register navigation menu locations.
		 *
		 * @return void
		 */
		public function register_locations() {
			static $done = false;
			if ( $done ) {
				return;
			}
			$done = true;
			register_nav_menus(
				array(
					'bhg_menu_admin'    => __( 'BHG Menu — Admin/Moderators', 'bonus-hunt-guesser' ),
					'bhg_menu_loggedin' => __( 'BHG Menu — Logged-in Users', 'bonus-hunt-guesser' ),
					'bhg_menu_guests'   => __( 'BHG Menu — Guests', 'bonus-hunt-guesser' ),
				)
			);
		}
	}
}
