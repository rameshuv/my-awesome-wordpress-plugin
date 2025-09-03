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
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user){
    $r = isset($_REQUEST['bhg_redirect']) ? $_REQUEST['bhg_redirect'] : '';
    if (!empty($r)) {
        $safe = esc_url_raw($r);
        $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $r_host = wp_parse_url($safe, PHP_URL_HOST);
        if (!$r_host || $r_host === $home_host) {
            return $safe;
        }
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

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $table = $wpdb->prefix . 'bhg_translations';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT value FROM $table WHERE `key` = %s", $key)
        );

        if ($row && isset($row->value)) {
            $cache[$key] = $row->value;
            return $row->value;
        }

        return $default;
    }
}

// Helper function to format currency
function bhg_format_currency($amount) {
    return '€' . number_format($amount, 2);
}

// Helper function to validate guess value
function bhg_validate_guess($guess) {
    $settings = get_option('bhg_settings', []);
    $min_guess = $settings['min_guess'] ?? 0;
    $max_guess = $settings['max_guess'] ?? 100000;

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


if (!function_exists('bhg_is_user_affiliate')) {
    function bhg_is_user_affiliate($user_id) {
        $val = get_user_meta($user_id, 'bhg_is_affiliate', true);
        return $val === '1' || $val === 1 || $val === true || $val === 'yes';
    }
}
if (!function_exists('bhg_get_user_affiliate_sites')) {
    function bhg_get_user_affiliate_sites($user_id) {
        $ids = get_user_meta($user_id, 'bhg_affiliate_sites', true);
        if (is_array($ids)) return array_map('absint', $ids);
        if (is_string($ids) && strlen($ids)) {
            return array_map('absint', array_filter(array_map('trim', explode(',', $ids))));
        }
        return array();
    }
}
if (!function_exists('bhg_set_user_affiliate_sites')) {
    function bhg_set_user_affiliate_sites($user_id, $site_ids) {
        $clean = array();
        if (is_array($site_ids)) {
            foreach ($site_ids as $sid) { $sid = absint($sid); if ($sid) $clean[] = $sid; }
        }
        update_user_meta($user_id, 'bhg_affiliate_sites', $clean);
    }
}


if (!function_exists('bhg_is_user_affiliate_for_site')) {
    function bhg_is_user_affiliate_for_site($user_id, $site_id) {
        if (!$site_id) return bhg_is_user_affiliate($user_id);
        $sites = bhg_get_user_affiliate_sites($user_id);
        return in_array(absint($site_id), array_map('absint', (array)$sites), true);
    }
}

if (!function_exists('bhg_render_affiliate_dot')) {
    function bhg_render_affiliate_dot($user_id, $hunt_affiliate_site_id = 0){
        $is_aff = bhg_is_user_affiliate_for_site($user_id, $hunt_affiliate_site_id);
        $cls   = $is_aff ? 'bhg-aff-green' : 'bhg-aff-red';
        $label = $is_aff ? esc_attr__('Affiliate', 'bonus-hunt-guesser') : esc_attr__('Non-affiliate', 'bonus-hunt-guesser');
        return '<span class="bhg-aff-dot ' . esc_attr($cls) . '" aria-label="' . $label . '"></span>';
    }
}
