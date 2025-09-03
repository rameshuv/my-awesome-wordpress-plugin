<?php
if (!defined('ABSPATH')) { exit; }

if (!class_exists('BHG_Login_Redirect')) {
class BHG_Login_Redirect {
    public function __construct() {
        add_filter('login_redirect', array($this, 'core_login_redirect'), 10, 3);

        // Nextend Social Login compatibility if plugin active
        if (function_exists('NextendSocialLogin')) {
            add_filter('nsl_login_redirect', array($this, 'nextend_redirect'), 10, 3);
        }
    }

    public function core_login_redirect($redirect_to, $requested, $user) {
        if (!empty($_REQUEST['redirect_to'])) {
            return esc_url_raw($_REQUEST['redirect_to']);
        }
        // Fall back to referer if safe
        $ref = wp_get_referer();
        if ($ref) { return esc_url_raw($ref); }
        return $redirect_to ?: home_url('/');
    }

    public function nextend_redirect($redirect_to, $user, $provider) {
        if (!empty($_REQUEST['redirect_to'])) {
            return esc_url_raw($_REQUEST['redirect_to']);
        }
        $ref = wp_get_referer();
        if ($ref) { return esc_url_raw($ref); }
        return $redirect_to ?: home_url('/');
    }
}}
new BHG_Login_Redirect();
