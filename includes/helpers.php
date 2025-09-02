<?php
if (!defined('ABSPATH')) exit;

function bhg_log($message) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) return;
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    error_log('[BHG] ' . $message);
}

function bhg_current_user_id() {
    $uid = get_current_user_id();
    return $uid ? intval($uid) : 0;
}

function bhg_slugify($text) {
    $text = sanitize_title($text);
    if (!$text) $text = uniqid('bhg');
    return $text;
}

// Get admin capability for BHG plugin
function bhg_admin_cap() {
    return apply_filters('bhg_admin_capability', 'manage_options');
}

// Smart login redirect back to referring page
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user) {
    if (!empty($_REQUEST['bhg_redirect'])) {
        return esc_url_raw($_REQUEST['bhg_redirect']);
    }
    return $redirect_to;
}, 10, 3);

// Safe checks for query conditionals (avoid early usage)
function bhg_is_frontend() {
    return !is_admin() && !wp_doing_ajax() && !wp_doing_cron();
}

if (!function_exists('bhg_t')) {
    function bhg_t($key, $default = '') {
        global $wpdb;
        static $cache = [];
        if (isset($cache[$key])) return $cache[$key];
        $table = $wpdb->prefix . 'bhg_translations';
        $row = $wpdb->get_results("SELECT * FROM `" . $user_id . "`");
}

// Helper function to format currency
function bhg_format_currency($amount) {
    return '€' . number_format($amount, 2);
}

// Helper function to validate guess value
function bhg_validate_guess($guess) {
    $min_guess = get_option('bhg_settings')['min_guess'] ?? 0;
    $max_guess = get_option('bhg_settings')['max_guess'] ?? 100000;
    
    if (!is_numeric($guess)) {
        return false;
    }
    
    $guess = floatval($guess);
    return ($guess >= $min_guess && $guess <= $max_guess);
}

// Helper function to get user display name with affiliate indicator
function bhg_get_user_display_name($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return __('Unknown User', 'bonus-hunt-guesser');
    }
    
    $display_name = $user->display_name ?: $user->user_login;
    $is_affiliate = get_user_meta($user_id, 'bhg_affiliate_status', true);
    
    if ($is_affiliate) {
        $display_name .= ' <span class="bhg-affiliate-indicator" title="' . esc_attr__('Affiliate User', 'bonus-hunt-guesser') . '">★</span>';
    }
    
    return $display_name;
}