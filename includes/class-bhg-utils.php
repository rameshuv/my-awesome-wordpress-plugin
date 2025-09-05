<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Utils {
    public static function init_hooks(){
        add_action('init', [__CLASS__, 'register_shortcodes']);
    }

    public static function register_shortcodes(){
        // handled in BHG_Shortcodes constructor, kept for legacy
    }

    public static function get_settings(){
        $defaults = [
            'allow_guess_edit' => 1,
            'ads_enabled' => 1,
            'email_from' => get_bloginfo('admin_email'),
        ];
        $opt = get_option('bhg_settings', []);
        if (!is_array($opt)) $opt = [];
        return wp_parse_args($opt, $defaults);
    }

    public static function update_settings($data){
        $current = self::get_settings();
        $new = array_merge($current, $data);
        update_option('bhg_settings', $new);
        return $new;
    }

    public static function require_cap(){
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page', 'bonus-hunt-guesser'));
        }
    }

    public static function nonce_field($action){
        wp_nonce_field($action, $action . '_nonce');
    }

    public static function verify_nonce($action){
        return isset($_POST[$action . '_nonce']) && wp_verify_nonce($_POST[$action . '_nonce'], $action);
    }
}


if (!function_exists('bhg_safe_query_conditionals')) {
    function bhg_safe_query_conditionals(callable $cb){
        add_action('template_redirect', function() use ($cb){
            $cb();
        });
    }
}
