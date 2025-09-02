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
    add_option('bhg_plugin_settings', [
        'allow_guess_changes' => 'yes',
        'default_tournament_period' => 'monthly',
        'min_guess_amount' => 0,
        'max_guess_amount' => 100000,
        'ads_enabled' => 1,
        'email_from' => get_bloginfo('admin_email'),
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
    
    // Register form handlers
    add_action('admin_post_bhg_save_bonus_hunt', 'bhg_handle_bonus_hunt_save');
    add_action('admin_post_nopriv_bhg_save_bonus_hunt', 'bhg_handle_bonus_hunt_save_unauth');
    add_action('admin_post_bhg_submit_guess', 'bhg_handle_guess_submission');
    add_action('admin_post_nopriv_bhg_submit_guess', 'bhg_handle_guess_submission_unauth');
    add_action('admin_post_bhg_save_settings', 'bhg_handle_settings_save');
}

// Form handler for settings save
function bhg_handle_settings_save() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to perform this action.', 'bonus-hunt-guesser'));
    }
    
    // Verify nonce
    if (!isset($_POST['bhg_settings_nonce']) || !wp_verify_nonce($_POST['bhg_settings_nonce'], 'bhg_save_settings_nonce')) {
        wp_redirect(admin_url('admin.php?page=bhg_settings&error=nonce_failed'));
        exit;
    }
    
    // Sanitize and validate data
    $settings = array();
    
    if (isset($_POST['bhg_default_tournament_period'])) {
        $period = sanitize_text_field($_POST['bhg_default_tournament_period']);
        if (in_array($period, array('weekly', 'monthly', 'yearly'))) {
            $settings['default_tournament_period'] = $period;
        }
    }
    
    if (isset($_POST['bhg_max_guess_amount'])) {
        $max = floatval($_POST['bhg_max_guess_amount']);
        if ($max >= 0) {
            $settings['max_guess_amount'] = $max;
        }
    }
    
    if (isset($_POST['bhg_min_guess_amount'])) {
        $min = floatval($_POST['bhg_min_guess_amount']);
        if ($min >= 0) {
            $settings['min_guess_amount'] = $min;
        }
    }
    
    // Validate that min is not greater than max
    if (isset($settings['min_guess_amount']) && isset($settings['max_guess_amount']) && 
        $settings['min_guess_amount'] > $settings['max_guess_amount']) {
        wp_redirect(admin_url('admin.php?page=bhg_settings&error=invalid_data'));
        exit;
    }
    
    if (isset($_POST['bhg_allow_guess_changes'])) {
        $allow = sanitize_text_field($_POST['bhg_allow_guess_changes']);
        if (in_array($allow, array('yes', 'no'))) {
            $settings['allow_guess_changes'] = $allow;
        }
    }
    
    if (isset($_POST['bhg_ads_enabled'])) {
        $ads_enabled = sanitize_text_field($_POST['bhg_ads_enabled']);
        $settings['ads_enabled'] = $ads_enabled === '1' ? 1 : 0;
    }
    
    if (isset($_POST['bhg_email_from'])) {
        $email_from = sanitize_email($_POST['bhg_email_from']);
        if ($email_from) {
            $settings['email_from'] = $email_from;
        }
    }
    
    // Save settings
    update_option('bhg_plugin_settings', $settings);
    
    // Redirect back to settings page
    wp_redirect(admin_url('admin.php?page=bhg_settings&message=saved'));
    exit;
}

// Form handler for bonus hunt save (admin)
function bhg_handle_bonus_hunt_save() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bhg_form_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    // Process form data
    global $wpdb;
    $table_name = $wpdb->prefix . 'bhg_bonus_hunts';
    
    $data = [
        'title' => sanitize_text_field($_POST['title']),
        'starting_balance' => floatval($_POST['starting_balance']),
        'num_bonuses' => intval($_POST['num_bonuses']),
        'prizes' => sanitize_textarea_field($_POST['prizes']),
        'status' => sanitize_text_field($_POST['status']),
        'updated_at' => current_time('mysql', 1)
    ];
    
    if (isset($_POST['final_balance'])) {
        $data['final_balance'] = floatval($_POST['final_balance']);
    }
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing hunt
        $wpdb->update(
            $table_name,
            $data,
            ['id' => intval($_POST['id'])]
        );
    } else {
        // Insert new hunt
        $data['created_at'] = current_time('mysql', 1);
        $wpdb->insert($table_name, $data);
    }
    
    wp_redirect(admin_url('admin.php?page=bhg_bonus_hunts&message=saved'));
    exit;
}

function bhg_handle_bonus_hunt_save_unauth() {
    wp_die('You must be logged in to submit this form');
}

// Form handler for guess submission (frontend)
function bhg_handle_guess_submission() {
    // Verify nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bhg_guess_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to submit a guess');
    }
    
    // Process guess submission
    global $wpdb;
    $table_name = $wpdb->prefix . 'bhg_guesses';
    $user_id = get_current_user_id();
    $hunt_id = intval($_POST['hunt_id']);
    $guess = floatval($_POST['guess']);
    
    // Check if user already has a guess for this hunt
    $existing_guess = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$table_name} WHERE user_id = %d AND hunt_id = %d",
        $user_id, $hunt_id
    ));
    
    if ($existing_guess) {
        // Update existing guess
        $wpdb->update(
            $table_name,
            [
                'guess' => $guess,
                'updated_at' => current_time('mysql', 1)
            ],
            ['id' => $existing_guess]
        );
    } else {
        // Insert new guess
        $wpdb->insert($table_name, [
            'user_id' => $user_id,
            'hunt_id' => $hunt_id,
            'guess' => $guess,
            'created_at' => current_time('mysql', 1),
            'updated_at' => current_time('mysql', 1)
        ]);
    }
    
    // Redirect back to the page with success message
    $redirect_url = add_query_arg('guess_submitted', '1', wp_get_referer());
    wp_redirect($redirect_url);
    exit;
}

function bhg_handle_guess_submission_unauth() {
    wp_die('You must be logged in to submit a guess');
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
    $col = $wpdb->get_results("SELECT * FROM `" . $table . "`");
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