<?php
if (!defined('ABSPATH')) exit;

/**
 * BHG Smart Login Redirect (filter-based; no direct wp_redirect during output)
 */
class BHG_Login_Redirect {
    public static function init() {
        add_filter('login_redirect', [__CLASS__, 'filter_login_redirect'], 10, 3);
    }

    public static function filter_login_redirect($redirect_to, $requested, $user) {
        // Respect ?bhg_redirect=<url> (encoded) if present on login page or request chain
        if (isset($_REQUEST['bhg_redirect'])) {
            $url = wp_validate_redirect(urldecode($_REQUEST['bhg_redirect']), $redirect_to ?: home_url('/'));
            return $url;
        }
        return $redirect_to ?: home_url('/');
    }
}
BHG_Login_Redirect::init();
