<?php
if (!defined('ABSPATH')) exit;

class BHG_Admin {

    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'menu']);
        // Admin actions
        add_action('admin_post_bhg_delete_guess', [$this, 'handle_delete_guess']);
        add_action('admin_post_bhg_save_hunt',    [$this, 'handle_save_hunt']);
        add_action('admin_post_bhg_save_ad',      [$this, 'handle_save_ad']);
    }

    /** Register admin menus and pages */
    public function menu() {
        $cap  = 'manage_options';
        $slug = 'bhg';

        // Top-level page
        add_menu_page(
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            $cap,
            $slug,
            [$this, 'dashboard'],
            'dashicons-awards',
            55
        );

        // Explicit Dashboard submenu
        add_submenu_page($slug, __('Dashboard', 'bonus-hunt-guesser'), __('Dashboard', 'bonus-hunt-guesser'), $cap, $slug, [$this, 'dashboard']);

        // Other submenus
        add_submenu_page($slug, __('Bonus Hunts',   'bonus-hunt-guesser'), __('Bonus Hunts',   'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts',         [$this, 'bonus_hunts']);
        add_submenu_page($slug, __('Results',       'bonus-hunt-guesser'), __('Results',       'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts-results', [$this, 'bonus_hunts_results']);
        add_submenu_page($slug, __('Tournaments',   'bonus-hunt-guesser'), __('Tournaments',   'bonus-hunt-guesser'), $cap, 'bhg-tournaments',         [$this, 'tournaments']);
        add_submenu_page($slug, __('Users',         'bonus-hunt-guesser'), __('Users',         'bonus-hunt-guesser'), $cap, 'bhg-users',               [$this, 'users']);
        add_submenu_page($slug, __('Affiliates',    'bonus-hunt-guesser'), __('Affiliates',    'bonus-hunt-guesser'), $cap, 'bhg-affiliates',          [$this, 'affiliates']);
        add_submenu_page($slug, __('Advertising',   'bonus-hunt-guesser'), __('Advertising',   'bonus-hunt-guesser'), $cap, 'bhg-ads',                 [$this, 'advertising']);
        add_submenu_page($slug, __('Translations',  'bonus-hunt-guesser'), __('Translations',  'bonus-hunt-guesser'), $cap, 'bhg-translations',         [$this, 'translations']);
        add_submenu_page($slug, __('Database',      'bonus-hunt-guesser'), __('Database',      'bonus-hunt-guesser'), $cap, 'bhg-database',            [$this, 'database']);
        add_submenu_page($slug, __('Settings',      'bonus-hunt-guesser'), __('Settings',      'bonus-hunt-guesser'), $cap, 'bhg-settings',            [$this, 'settings']);
        add_submenu_page($slug, __('BHG Tools',     'bonus-hunt-guesser'), __('BHG Tools',     'bonus-hunt-guesser'), $cap, 'bhg-tools',               [$this, 'bhg_tools_page']);
    }

    // ----- Views ------------------------------------------------------------

    public function dashboard() {
        require BHG_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function bonus_hunts() {
        require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php';
    }

    public function bonus_hunts_results() {
        require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts-results.php';
    }

    public function tournaments() {
        require BHG_PLUGIN_DIR . 'admin/views/tournaments.php';
    }

    public function users() {
        require BHG_PLUGIN_DIR . 'admin/views/users.php';
    }

    public function affiliates() {
        // If you have a view, load it. Otherwise show a placeholder to avoid errors.
        $view = BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Affiliates', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('Affiliate management UI not provided yet.', 'bonus-hunt-guesser') . '</p></div>';
        }
    }

    public function advertising() {
        require BHG_PLUGIN_DIR . 'admin/views/advertising.php';
    }

    public function translations() {
        $view = BHG_PLUGIN_DIR . 'admin/views/translations.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Translations', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No translations UI found.', 'bonus-hunt-guesser') . '</p></div>';
        }
    }

    public function database() {
        $view = BHG_PLUGIN_DIR . 'admin/views/database.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Database', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No database UI found.', 'bonus-hunt-guesser') . '</p></div>';
        }
    }

    public function settings() {
        $view = BHG_PLUGIN_DIR . 'admin/views/settings.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Settings', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No settings UI found.', 'bonus-hunt-guesser') . '</p></div>';
        }
    }

    public function bhg_tools_page() {
        $view = BHG_PLUGIN_DIR . 'admin/views/demo-tools.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('BHG Tools', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No tools UI found.', 'bonus-hunt-guesser') . '</p></div>';
        }
    }

    // ----- Handlers ---------------------------------------------------------

    /** Delete a single guess (POST) */
    public function handle_delete_guess() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_delete_guess');
        global $wpdb;
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        $guess_id = isset($_POST['guess_id']) ? (int) $_POST['guess_id'] : 0;
        if ($guess_id) {
            $wpdb->delete($guesses_table, ['id' => $guess_id], ['%d']);
        }
        wp_redirect(wp_get_referer() ?: admin_url('admin.php?page=bhg-bonus-hunts'));
        exit;
    }

    /** Create/Update a hunt (POST) */
    public function handle_save_hunt() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_save_hunt');
        global $wpdb;
        $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';

        $id             = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title          = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $starting       = isset($_POST['starting_balance']) ? (float) $_POST['starting_balance'] : 0;
        $num_bonuses    = isset($_POST['num_bonuses']) ? (int) $_POST['num_bonuses'] : 0;
        $prizes         = isset($_POST['prizes']) ? wp_kses_post(wp_unslash($_POST['prizes'])) : '';
        $winners_count  = isset($_POST['winners_count']) ? max(1, (int) $_POST['winners_count']) : 3;
        $final_balance  = (isset($_POST['final_balance']) && $_POST['final_balance'] !== '') ? (float) $_POST['final_balance'] : null;
        $status         = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'open';

        $data = [
            'title'            => $title,
            'starting_balance' => $starting,
            'num_bonuses'      => $num_bonuses,
            'prizes'           => $prizes,
            'winners_count'    => $winners_count,
            'final_balance'    => $final_balance,
            'status'           => $status,
            'updated_at'       => current_time('mysql'),
        ];

        if ($id) {
            $wpdb->update($hunts_table, $data, ['id' => $id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($hunts_table, $data);
        }

        wp_redirect(admin_url('admin.php?page=bhg-bonus-hunts'));
        exit;
    }

    /** Save ad (create/update) */
    public function handle_save_ad() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_save_ad');
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_ads';

        $id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $msg   = isset($_POST['message']) ? wp_kses_post(wp_unslash($_POST['message'])) : '';
        $link  = isset($_POST['link']) ? esc_url_raw(wp_unslash($_POST['link'])) : '';
        $place = isset($_POST['placement']) ? sanitize_text_field($_POST['placement']) : 'none';
        $vis   = isset($_POST['visibility']) ? sanitize_text_field($_POST['visibility']) : 'all';

        $data = [
            'title'     => $title,
            'content'   => $msg,
            'link_url'  => $link,
            'placement' => $place,
            'visible_to'=> $vis,
            'updated_at'=> current_time('mysql'),
        ];

        if ($id) {
            $wpdb->update($table, $data, ['id' => $id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
        }

        wp_redirect(admin_url('admin.php?page=bhg-ads'));
        exit;
    }
}
