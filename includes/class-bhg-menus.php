<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (!class_exists('BHG_Menus')) {
class BHG_Menus {
    private static $instance = null;
    private $initialized = false;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        if ($this->initialized) { return; }
        $this->initialized = true;

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('init', array($this, 'register_locations'), 5);
    }

    public function assets($hook) {
        if (strpos($hook, 'bhg') !== false) {
            wp_enqueue_style('bhg-admin', BHG_PLUGIN_URL . 'assets/css/admin.css', array(), defined('BHG_VERSION') ? BHG_VERSION : null);
            wp_enqueue_script('bhg-admin', BHG_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), defined('BHG_VERSION') ? BHG_VERSION : null, true);
        }
    }

    public function admin_menu() {
        // Prevent duplicate top-level menu
        global $menu;
        foreach ((array)$menu as $item) {
            if (isset($item[2]) && $item[2] === 'bhg') {
                return;
            }
        }

        $cap  = $this->admin_capability();
        $slug = 'bhg';

        add_menu_page(
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            $cap,
            $slug,
            array($this, 'render_dashboard'),
            'dashicons-awards',
            26
        );

        add_submenu_page($slug, __('Dashboard', 'bonus-hunt-guesser'), __('Dashboard', 'bonus-hunt-guesser'), $cap, $slug, array($this, 'render_dashboard'));
        add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts', array($this, 'render_bonus_hunts'));
        add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'), __('Users', 'bonus-hunt-guesser'), $cap, 'bhg-users', array($this, 'render_users'));
        add_submenu_page($slug, __('Affiliate Websites', 'bonus-hunt-guesser'), __('Affiliate Websites', 'bonus-hunt-guesser'), $cap, 'bhg-affiliate-websites', array($this, 'render_affiliates'));
        add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments', array($this, 'render_tournaments'));
        add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'), __('Translations', 'bonus-hunt-guesser'), $cap, 'bhg-translations', array($this, 'render_translations'));
        add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'), __('Settings', 'bonus-hunt-guesser'), $cap, 'bhg-settings', array($this, 'render_settings'));
        add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'), __('Database', 'bonus-hunt-guesser'), $cap, 'bhg-database', array($this, 'render_database'));
        add_submenu_page($slug, __('Tools', 'bonus-hunt-guesser'), __('Tools', 'bonus-hunt-guesser'), $cap, 'bhg-tools', array($this, 'render_tools'));
    }

    private function admin_capability() {
        return apply_filters('bhg_admin_capability', 'manage_options');
    }

    public function view($view, $vars = array()) {
        if (!current_user_can($this->admin_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        if (is_array($vars)) { extract($vars); }

        $header_path = BHG_PLUGIN_DIR . 'admin/views/header.php';
        if (file_exists($header_path)) {
            include $header_path;
        }

        $view_path = BHG_PLUGIN_DIR . 'admin/views/' . $view . '.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            echo '<div class="wrap"><h2>' . esc_html__('View Not Found', 'bonus-hunt-guesser') . '</h2>';
            echo '<p>' . sprintf(esc_html__('The requested view "%s" was not found.', 'bonus-hunt-guesser'), esc_html($view)) . '</p></div>';
        }
    }

    public function render_dashboard()      { $this->view('dashboard'); }
    public function render_bonus_hunts()    { $this->view('bonus-hunts'); }
    public function render_users()          { $this->view('users'); }
    public function render_affiliates()     { $this->view('affiliate-websites'); }
    public function render_tournaments()    { $this->view('tournaments'); }
    public function render_translations()   { $this->view('translations'); }
    public function render_settings()       { $this->view('settings'); }
    public function render_database()       { $this->view('database'); }
    public function render_tools()          { $this->view('tools'); }

    public function register_locations() {
        static $done = false;
        if ($done) return;
        $done = true;
        register_nav_menus(array(
            'bhg_menu_admin'    => __('BHG Menu — Admin/Moderators', 'bonus-hunt-guesser'),
            'bhg_menu_loggedin' => __('BHG Menu — Logged-in Users', 'bonus-hunt-guesser'),
            'bhg_menu_guests'   => __('BHG Menu — Guests', 'bonus-hunt-guesser'),
        ));
    }
}}

// Bootstrap once
if (class_exists('BHG_Menus')) {
    BHG_Menus::get_instance()->init();
}
