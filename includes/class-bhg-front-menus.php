<?php
if (!defined('ABSPATH')) exit;

class BHG_Front_Menus {
    public function __construct() {
        add_action('init', [$this, 'register_locations']);
        add_shortcode('bhg_nav', [$this, 'nav_shortcode']);
    }

    public function register_locations() {
        register_nav_menus([
            'bhg_admin_menu' => __('BHG Admin/Moderator Menu','bonus-hunt-guesser'),
            'bhg_user_menu'  => __('BHG Logged-in User Menu','bonus-hunt-guesser'),
            'bhg_guest_menu' => __('BHG Guest Menu','bonus-hunt-guesser'),
        ]);
    }

    /** [bhg_nav area="admin|user|guest"] */
    public function nav_shortcode($atts) {
        $a = shortcode_atts(['area' => 'guest'], $atts, 'bhg_nav');
        $loc = 'bhg_guest_menu';
        if ($a['area'] === 'admin' && current_user_can('edit_posts')) {
            $loc = 'bhg_admin_menu';
        } elseif ($a['area'] === 'user' && is_user_logged_in()) {
            $loc = 'bhg_user_menu';
        } elseif ($a['area'] === 'guest' && !is_user_logged_in()) {
            $loc = 'bhg_guest_menu';
        } elseif (is_user_logged_in()) {
            $loc = 'bhg_user_menu';
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
        if (isset($_GET['page']) && strpos($_GET['page'],'bhg')!==false) {
            echo '<div class="notice notice-info"><p>';
            esc_html_e('Reminder: Assign your BHG menus (Admin/Moderator, Logged-in, Guest) under Appearance → Menus → Manage Locations. Use shortcode [bhg_nav] to display.', 'bonus-hunt-guesser');
            echo '</p></div>';
        }
    });
}
