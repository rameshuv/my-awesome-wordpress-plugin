<?php
/**
 * Plugin Name: Bonus Hunt Guesser
 * Plugin URI: https://yourdomain.com/
 * Description: Comprehensive bonus hunt management system with tournaments, leaderboards, and user guessing functionality
 * Version: 8.0.03
 * Author: Bonus Hunt Guesser Development Team
 * Text Domain: bonus-hunt-guesser
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.5.5
 * License: GPLv2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BHG_VERSION', '8.0.03');
define('BHG_MIN_WP', '5.5.5');
define('BHG_PLUGIN_FILE', __FILE__);
define('BHG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BHG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BHG_TABLE_PREFIX', 'bhg_');

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'BHG_') !== 0) {
        return;
    }
    
    $class_map = [
        'BHG_DB' => 'includes/class-bhg-db.php',
        'BHG_Admin' => 'admin/class-bhg-admin.php',
        'BHG_Shortcodes' => 'includes/class-bhg-shortcodes.php',
        'BHG_Logger' => 'includes/class-bhg-logger.php',
        'BHG_Settings' => 'includes/class-bhg-settings.php',
        'BHG_Utils' => 'includes/class-bhg-utils.php',
        'BHG_Menus' => 'includes/class-bhg-menus.php',
        'BHG_Models' => 'includes/class-bhg-models.php',
        'BHG_Demo' => 'admin/class-bhg-demo.php',
    ];
    
    if (isset($class_map[$class])) {
        $file_path = BHG_PLUGIN_DIR . $class_map[$class];
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// Include helper functions
require_once BHG_PLUGIN_DIR . 'includes/helpers.php';

// Activation hook: create tables and set default options
register_activation_hook(__FILE__, 'bhg_activate_plugin');
function bhg_activate_plugin() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    
    if (!class_exists('BHG_DB')) {
        require_once BHG_PLUGIN_DIR . 'includes/class-bhg-db.php';
    }
    
    $db = new BHG_DB();
    $db->install();
    
    // Set default options
    add_option('bhg_version', BHG_VERSION);
    add_option('bhg_settings', [
        'allow_guess_edit' => 1,
        'ads_enabled' => 1,
        'email_from' => get_bloginfo('admin_email'),
        'min_guess' => 0,
        'max_guess' => 100000,
    ]);
    
    // Seed demo data if empty
    bhg_seed_demo_if_empty();
    update_option('bhg_demo_notice', 1);
}

// Deactivation hook (no destructive actions)
register_deactivation_hook(__FILE__, function() {
    // Keep data intact by default
});

// Initialize plugin
add_action('plugins_loaded', 'bhg_init_plugin');
function bhg_init_plugin() {
    // Load text domain
    load_plugin_textdomain('bonus-hunt-guesser', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Initialize components
    if (is_admin()) {
        new BHG_Admin();
        if (class_exists('BHG_Demo')) {
            new BHG_Demo();
        }
    }
    
    new BHG_Shortcodes();
    
    // Initialize menus using the singleton pattern
    if (class_exists('BHG_Menus')) {
        BHG_Menus::get_instance()->init();
    }
    
    if (class_exists('BHG_DB')) {
        BHG_DB::migrate();
    }
    
    if (class_exists('BHG_Utils')) {
        BHG_Utils::init_hooks();
    }
}

// Enqueue admin assets
add_action('admin_enqueue_scripts', 'bhg_enqueue_admin_assets');
function bhg_enqueue_admin_assets($hook) {
    if (strpos($hook, 'bhg') === false) {
        return;
    }
    
    wp_enqueue_style('bhg-admin', BHG_PLUGIN_URL . 'assets/css/admin.css', [], BHG_VERSION);
    wp_enqueue_script('bhg-admin', BHG_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], BHG_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('bhg-admin', 'bhg_admin_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bhg_admin_nonce'),
        'i18n' => [
            'confirm_delete' => __('Are you sure you want to delete this item?', 'bonus-hunt-guesser'),
            'error' => __('An error occurred. Please try again.', 'bonus-hunt-guesser'),
        ]
    ]);
}

// Enqueue public assets
add_action('wp_enqueue_scripts', 'bhg_enqueue_public_assets');
function bhg_enqueue_public_assets() {
    wp_enqueue_style('bhg-public', BHG_PLUGIN_URL . 'assets/css/public.css', [], BHG_VERSION);
    wp_enqueue_script('bhg-public', BHG_PLUGIN_URL . 'assets/js/public.js', ['jquery'], BHG_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('bhg-public', 'bhg_public_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bhg_public_nonce'),
        'is_logged_in' => is_user_logged_in(),
        'i18n' => [
            'guess_required' => __('Please enter your guess.', 'bonus-hunt-guesser'),
            'guess_numeric' => __('Please enter a valid number.', 'bonus-hunt-guesser'),
            'guess_range' => __('Please enter a value between 0 and 100,000.', 'bonus-hunt-guesser'),
            'affiliate_user' => __('Affiliate User', 'bonus-hunt-guesser'),
            'non_affiliate_user' => __('Non-Affiliate User', 'bonus-hunt-guesser'),
        ]
    ]);
}

// Frontend ads rendering
function bhg_should_show_ad($visibility) {
    if ($visibility === 'all') {
        return true;
    }
    if ($visibility === 'logged_in') {
        return is_user_logged_in();
    }
    if ($visibility === 'guests') {
        return !is_user_logged_in();
    }
    if ($visibility === 'affiliates') {
        return is_user_logged_in() && bhg_is_affiliate();
    }
    if ($visibility === 'non_affiliates') {
        return !is_user_logged_in() || !bhg_is_affiliate();
    }
    return true;
}

function bhg_build_ads_query($table, $placement = 'footer') {
    global $wpdb;
    
    // Check if 'active' column exists
    $col = $wpdb->get_var($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'active'", 
        DB_NAME, 
        $wpdb->prefix . 'bhg_ads'
    ));
    
    if ($col) {
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE active = %d AND placement = %s ORDER BY id DESC", 
            1, 
            $placement
        );
    } else {
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE placement = %s ORDER BY id DESC", 
            $placement
        );
    }
    
    return $sql;
}

function bhg_render_ads_footer() {
    if (is_admin()) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'bhg_ads';
    $ads = $wpdb->get_results(bhg_build_ads_query($table, 'footer'));
    
    if (!$ads) {
        return;
    }
    
    echo '<div class="bhg-ads bhg-ads-footer">';
    foreach ($ads as $ad) {
        if (!bhg_should_show_ad($ad->visibility)) {
            continue;
        }
        
        $msg = wpautop(wp_kses_post($ad->message));
        if (!empty($ad->link)) {
            $msg .= ' <a href="' . esc_url($ad->link) . '" target="_blank" rel="nofollow noopener">' . 
                    esc_html__('Learn more', 'bonus-hunt-guesser') . '</a>';
        }
        
        echo '<div class="bhg-ad-item">' . $msg . '</div>';
    }
    echo '</div>';
}
add_action('wp_footer', 'bhg_render_ads_footer');

// Admin menu tool to reset demo data
add_action('admin_menu', 'bhg_add_tools_menu');
function bhg_add_tools_menu() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    add_submenu_page(
        'bhg', 
        __('Tools', 'bonus-hunt-guesser'), 
        __('Tools', 'bonus-hunt-guesser'), 
        'manage_options', 
        'bhg-tools', 
        'bhg_tools_page'
    );
}

function bhg_tools_page() {
    $url = wp_nonce_url(admin_url('admin-post.php?action=bhg_reset_demo'), 'bhg_reset_demo');
    echo '<div class="wrap"><h1>' . esc_html__('Bonus Hunt Tools', 'bonus-hunt-guesser') . '</h1><p><a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Reset Demo Data', 'bonus-hunt-guesser') . '</a></p></div>';
}

add_action('admin_post_bhg_reset_demo', 'bhg_handle_reset_demo');
function bhg_handle_reset_demo() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied', 'bonus-hunt-guesser'));
    }
    
    check_admin_referer('bhg_reset_demo');
    bhg_reset_demo_and_seed();
    wp_safe_redirect(admin_url('admin.php?page=bhg-tools&reset=1'));
    exit;
}

// Admin notice after activation/reset
add_action('admin_notices', 'bhg_admin_notices');
function bhg_admin_notices() {
    if (get_option('bhg_demo_notice')) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             esc_html__('Demo data inserted successfully. You can reset it anytime from BHG Tools.', 'bon极s-hunt-guesser') . 
             '</p></div>';
        delete_option('bhg_demo_notice');
    }
    
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'bhg') !== false && current_user_can('manage_options')) {
        $url = admin_url('admin.php?page=bhg-tools');
        echo '<div class="notice notice-info"><p>' . 
             sprintf(
                 esc_html__('Need demo data? Visit %s to reset.', 'bonus-hunt-guesser'), 
                 '<a href="' . esc_url($url) . '">Bonus Hunt → Tools</a>'
             ) . 
             '</p></div>';
    }
}

// Demo data installer (only if tables are empty)
function bhg_seed_demo_if_empty() {
    global $wpdb;
    
    // Check hunts
    $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
    $count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$hunts_table}"));
    
    if ($count > 0) {
        return;
    }

    // Insert active demo hunt
    $wpdb->insert($hunts_table, [
        'title' => 'Demo Hunt 1',
        'starting_balance' => 1000.00,
        'num_bonuses' => 10,
        'prizes' => '€100, €50, €25 vouchers',
        'status' => 'open',
        'created_at' => current_time('mysql', 1)
    ]);
    $demo_hunt_id = intval($wpdb->insert_id);

    // Insert closed demo hunt
    $wpdb->insert($hunts_table, [
        'title' => 'Closed Demo Hunt',
        'starting_balance' => 1500.00,
        'num_bonuses' => 12,
        'prizes' => 'Gold, Silver, Bronze',
        'status' => 'closed',
        'final_balance' => 14875.50,
        'closed_at' => current_time('mysql', 1),
        'created_at' => current_time('mysql', 1)
    ]);
    $closed_hunt_id = intval($wpdb->insert_id);

    // Insert winners
    $winners_table = $wpdb->prefix . 'bhg_hunt_winners';
    $wpdb->insert($winners_table, ['hunt_id' => $closed_hunt_id, 'user_id' => 1, 'position' => 1]);
    $wpdb->insert($winners_table, ['hunt_id' => $closed_hunt_id, 'user_id' => 2, 'position' => 2]);
    $wpdb->insert($winners_table, ['hunt_id' => $closed_hunt_id, 'user_id' => 3, 'position' => 3]);

    // Insert tournament
    $tour_table = $wpdb->prefix . 'bhg_tournaments';
    $wpdb->insert($tour_table, [
        'title' => 'Demo Tournament 1',
        'period' => 'month',
        'period_key' => '2025-08',
        'status' => 'active',
        'created_at' => current_time('mysql', 1)
    ]);

    // Insert demo ad
    $ads_table = $wpdb->prefix . 'bhg_ads';
    $wpdb->insert($ads_table, [
        'message' => 'Try our Premium Membership!',
        'link' => 'https://example.com',
        'placement' => 'footer',
        'visibility' => 'all',
        'active' => 1,
        'created_at' => current_time('mysql', 1)
    ]);

    // Create demo users if they don't exist
    foreach (['demo1', 'demo2', 'demo3'] as $username) {
        if (!username_exists($username)) {
            wp_create_user($username, $username . '123', $username . '@example.com');
        }
    }

    // Log
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('[BHG DEMO] Inserted demo data on activation');
    }
}

// Reset demo data
function bh极_reset_demo_and_seed() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Insufficient permissions', 'bonus-hunt-guesser'));
    }
    
    global $wpdb;
    $tables = [
        $wpdb->prefix . 'bhg_guesses',
        $wpdb->prefix . 'bhg_hunt_winners',
        $wpdb->prefix . 'bhg_bonus_hunts',
        $wpdb->prefix . 'bhg_tournaments',
        $wpdb->prefix . 'bhg_ads'
    ];
    
    foreach ($tables as $table) {
        $wpdb->query("DELETE FROM {$table}");
    }
    
    bhg_seed_demo_if_empty();
    
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        $u = wp_get_current_user();
        error_log('[BHG DEMO] Demo data reset by admin (user: ' . ($u ? $u->user_login : 'unknown') . ')');
    }
}

// AJAX handler for loading leaderboard data
add_action('wp_ajax_bhg_load_leaderboard', 'bhg_load_leaderboard_ajax');
add_action('wp_ajax_nopriv_bhg_load_leaderboard', 'bhg_load_leaderboard_ajax');

function bhg_load_leaderboard_ajax() {
    check_ajax_referer('bhg_public_nonce', 'nonce');
    
    if (!isset($_POST['timeframe'])) {
        wp_send_json_error('Invalid timeframe');
    }
    
    $timeframe = sanitize_text_field($_POST['timeframe']);
    
    // Generate leaderboard HTML based on timeframe
    $html = bhg_generate_leaderboard_html($timeframe);
    
    wp_send_json_success($html);
}

// Helper function to generate leaderboard HTML
function bhg_generate_leaderboard_html($timeframe) {
    // This function should be implemented in includes/helpers.php
    // Placeholder implementation
    return '<div class="bhg-leaderboard" data-timeframe="' . esc_attr($timeframe) . '">Leaderboard content for ' . $timeframe . '</div>';
}

// Helper function to check if user is affiliate
function bhg_is_affiliate($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    return (bool) get_user_meta($user_id, 'bhg_affiliate_status', true);
}

// Add user profile fields for affiliate status
add_action('show_user_profile', 'bhg_extra_user_profile_fields');
add_action('edit_user_profile', 'bhg_extra_user_profile_fields');

function bhg_extra_user_profile_fields($user) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $affiliate_status = get_user_meta($user->ID, 'bhg_affiliate_status', true);
    ?>
    <h3><?php esc_html_e('Bonus Hunt Guesser Information', 'bonus-hunt-guesser'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="bhg_affiliate_status"><?php esc_html_e('Affiliate Status', 'bonus-hunt-guesser'); ?></label></th>
            <td>
                <input type="checkbox" name="bhg_affiliate_status" id="bhg_affiliate_status" value="1" <?php checked($affiliate_status, 1); ?> />
                <span class="description"><?php esc_html_e('Check if this user is an affiliate.', 'bonus-hunt-guesser'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'bhg_save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'bhg_save_extra_user_profile_fields');

function bhg_save_extra_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    $affiliate_status = isset($_POST['bhg_affiliate_status']) ? 1 : 0;
    update_user_meta($user_id, 'bhg_affiliate_status', $affiliate_status);
}