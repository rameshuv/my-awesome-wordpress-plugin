<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Front_Menus {
    public function __construct() {
        add_action('init', [$this, 'register_locations']);
        add_shortcode('bhg_nav', [$this, 'nav_shortcode']);
    }

    public function register_locations() {
        register_nav_menus([
            'bhg_menu_admin' => __('BHG Admin/Moderator Menu','bonus-hunt-guesser'),
            'bhg_menu_user'  => __('BHG Logged-in User Menu','bonus-hunt-guesser'),
            'bhg_menu_guest' => __('BHG Guest Menu','bonus-hunt-guesser'),
        ]);
    }

    /** [bhg_nav area="admin|user|guest"] */
    public function nav_shortcode($atts) {
        $a = shortcode_atts(['area' => 'guest'], $atts, 'bhg_nav');
        $loc = 'bhg_menu_guest';
        if ($a['area'] === 'admin' && current_user_can('edit_posts')) {
            $loc = 'bhg_menu_admin';
        } elseif ($a['area'] === 'user' && is_user_logged_in()) {
            $loc = 'bhg_menu_user';
        } elseif ($a['area'] === 'guest' && !is_user_logged_in()) {
            $loc = 'bhg_menu_guest';
        } elseif (is_user_logged_in()) {
            $loc = 'bhg_menu_user';
        }

        $out = wp_nav_menu([
            'theme_location' => $loc,
            'container' => 'nav',
            'container_class' => 'bhg-nav',
            'echo' => false,
        ]);

        if (!$out) {
            return '<!-- BHG menu not assigned: ' . esc_html($loc) . ' -->';
        }
        return $out;
    }
}


/* STAGE-5 MENU HELP */
if (is_admin()) {
    add_action('admin_notices', function(){
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( strpos( $page, 'bhg' ) !== false ) {
            echo '<div class="notice notice-info"><p>';
            esc_html_e('Reminder: Assign your BHG menus (Admin/Moderator, Logged-in, Guest) under Appearance → Menus → Manage Locations. Use shortcode [bhg_nav] to display.', 'bonus-hunt-guesser');
            echo '</p></div>';
        }
    });
}


// Render the correct menu location based on role/login
if (!function_exists('bhg_render_role_menu')) {
    function bhg_render_role_menu($args = array()) {
        if (current_user_can('manage_options') || current_user_can('moderate_comments')) {
            $loc = 'bhg_menu_admin';
        } elseif (is_user_logged_in()) {
            $loc = 'bhg_menu_user';
        } else {
            $loc = 'bhg_menu_guest';
        }
        $defaults = array(
            'theme_location' => $loc,
            'container'      => 'nav',
            'container_class'=> 'bhg-menu',
            'fallback_cb'    => false,
            'echo'           => false,
        );
        $args = wp_parse_args($args, $defaults);
        $menu = wp_nav_menu($args);
        if (!$menu) {
            // Fallback message is escaped
            $menu = '<nav class="bhg-menu"><ul><li>' . esc_html__('Menu not assigned.', 'bonus-hunt-guesser') . '</li></ul></nav>';
        }
        return $menu;
    }
}

// Shortcode: [bhg_menu]
if (!function_exists('bhg_menu_shortcode')) {
    function bhg_menu_shortcode($atts) {
        return bhg_render_role_menu();
    }
    add_shortcode('bhg_menu', 'bhg_menu_shortcode');
}
