<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Admin {

    public function __construct() {
        // Menus
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_notices', [$this, 'admin_notices']);
        // Handlers
        add_action('admin_post_bhg_delete_guess',      [$this, 'handle_delete_guess']);
        add_action('admin_post_bhg_save_hunt',         [$this, 'handle_save_hunt']);
        add_action('admin_post_bhg_save_ad',           [$this, 'handle_save_ad']);
        add_action('admin_post_bhg_tournament_save',   [$this, 'handle_save_tournament']);
        add_action('admin_post_bhg_save_affiliate',    [$this, 'handle_save_affiliate']);
        add_action('admin_post_bhg_delete_affiliate',  [$this, 'handle_delete_affiliate']);
        add_action('admin_post_bhg_save_settings',     [$this, 'handle_save_settings']);
        add_action('admin_post_bhg_save_user_meta',    [$this, 'handle_save_user_meta']);
    }

    /** Register admin menus and pages */
    public function menu() {
        $cap  = 'manage_options';
        $slug = 'bhg';

        add_menu_page(
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            __('Bonus Hunt', 'bonus-hunt-guesser'),
            $cap,
            $slug,
            [$this, 'dashboard'],
            'dashicons-awards',
            55
        );

        add_submenu_page($slug, __('Dashboard', 'bonus-hunt-guesser'),   __('Dashboard', 'bonus-hunt-guesser'),   $cap, $slug,                         [$this, 'dashboard']);
        add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts',             [$this, 'bonus_hunts']);
        add_submenu_page($slug, __('Results', 'bonus-hunt-guesser'),     __('Results', 'bonus-hunt-guesser'),     $cap, 'bhg-bonus-hunts-results',     [$this, 'bonus_hunts_results']);
        add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments',             [$this, 'tournaments']);
        add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'),       __('Users', 'bonus-hunt-guesser'),       $cap, 'bhg-users',                   [$this, 'users']);
        add_submenu_page($slug, __('Affiliates', 'bonus-hunt-guesser'),  __('Affiliates', 'bonus-hunt-guesser'),  $cap, 'bhg-affiliates',              [$this, 'affiliates']);
        add_submenu_page($slug, __('Advertising', 'bonus-hunt-guesser'), __('Advertising', 'bonus-hunt-guesser'), $cap, 'bhg-ads',                     [$this, 'advertising']);
        add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'),__('Translations', 'bonus-hunt-guesser'),$cap, 'bhg-translations',            [$this, 'translations']);
        add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'),    __('Database', 'bonus-hunt-guesser'),    $cap, 'bhg-database',                [$this, 'database']);
        add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'),    __('Settings', 'bonus-hunt-guesser'),    $cap, 'bhg-settings',                [$this, 'settings']);
        add_submenu_page($slug, __('BHG Tools', 'bonus-hunt-guesser'),   __('BHG Tools', 'bonus-hunt-guesser'),   $cap, 'bhg-tools',                   [$this, 'bhg_tools_page']);

        remove_submenu_page($slug, $slug);
    }

    // -------------------- Views --------------------
    public function dashboard()           { require BHG_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function bonus_hunts()         { require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php'; }
    public function bonus_hunts_results() { require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts-results.php'; }
    public function tournaments()         { require BHG_PLUGIN_DIR . 'admin/views/tournaments.php'; }
    public function users()               { require BHG_PLUGIN_DIR . 'admin/views/users.php'; }
    public function affiliates()          { 
        $view = BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php';
        if (file_exists($view)) { require $view; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Affiliates', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('Affiliate management UI not provided yet.', 'bonus-hunt-guesser') . '</p></div>'; }
    }
    public function advertising()         { require BHG_PLUGIN_DIR . 'admin/views/advertising.php'; }
    public function translations()        { 
        $view = BHG_PLUGIN_DIR . 'admin/views/translations.php';
        if (file_exists($view)) { require $view; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Translations', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No translations UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
    }
    public function database()            { 
        $view = BHG_PLUGIN_DIR . 'admin/views/database.php';
        if (file_exists($view)) { require $view; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Database', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No database UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
    }
    public function settings()            { 
        $view = BHG_PLUGIN_DIR . 'admin/views/settings.php';
        if (file_exists($view)) { require $view; }
        else { echo '<div class="wrap"><h1>' . esc_html__('Settings', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No settings UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
    }
    public function bhg_tools_page()      { 
        $view = BHG_PLUGIN_DIR . 'admin/views/demo-tools.php';
        if (file_exists($view)) { require $view; }
        else { echo '<div class="wrap"><h1>' . esc_html__('BHG Tools', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No tools UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
    }

    // -------------------- Handlers --------------------
    public function handle_delete_guess() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_delete_guess');
        global $wpdb;
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        $guess_id = isset($_POST['guess_id']) ? (int) $_POST['guess_id'] : 0;
        if ($guess_id) { $wpdb->delete($guesses_table, ['id' => $guess_id], ['%d']); }
        wp_redirect(wp_get_referer() ?: admin_url('admin.php?page=bhg-bonus-hunts')); exit;
    }

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
        $affiliate_site = isset($_POST['affiliate_site_id']) ? (int) $_POST['affiliate_site_id'] : 0;
        $final_balance  = (isset($_POST['final_balance']) && $_POST['final_balance'] !== '') ? (float) $_POST['final_balance'] : null;
        $status         = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'open';

        $data = [
            'title'            => $title,
            'starting_balance' => $starting,
            'num_bonuses'      => $num_bonuses,
            'prizes'           => $prizes,
            'winners_count'    => $winners_count,
            'affiliate_site_id'=> $affiliate_site,
            'final_balance'    => $final_balance,
            'status'           => $status,
            'updated_at'       => current_time('mysql'),
        ];

        if ($id) { $wpdb->update($hunts_table, $data, ['id' => $id]); }
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($hunts_table, $data); }

        wp_redirect(admin_url('admin.php?page=bhg-bonus-hunts')); exit;
    }

    public function handle_save_ad() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_save_ad');
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_ads';

        $id       = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title    = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $content  = isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '';
        $link     = isset($_POST['link_url']) ? esc_url_raw(wp_unslash($_POST['link_url'])) : '';
        $place    = isset($_POST['placement']) ? sanitize_text_field($_POST['placement']) : 'none';
        $visible  = isset($_POST['visible_to']) ? sanitize_text_field($_POST['visible_to']) : 'all';
        $targets  = isset($_POST['target_pages']) ? sanitize_text_field(wp_unslash($_POST['target_pages'])) : '';
        $active   = isset($_POST['active']) ? 1 : 0;

        $data = [
            'title'        => $title,
            'content'      => $content,
            'link_url'     => $link,
            'placement'    => $place,
            'visible_to'   => $visible,
            'target_pages' => $targets,
            'active'       => $active,
            'updated_at'   => current_time('mysql'),
        ];

        if ($id) { $wpdb->update($table, $data, ['id' => $id]); }
        else { $data['created_at'] = current_time('mysql'); $wpdb->insert($table, $data); }

        wp_redirect(admin_url('admin.php?page=bhg-ads')); exit;
    }

    public function handle_save_tournament() {
    if (!current_user_can('manage_options')) {
        wp_redirect(add_query_arg('bhg_msg','noaccess', admin_url('admin.php?page=bhg-tournaments')));
        exit;
    }
    if (!check_admin_referer('bhg_tournament_save_action')) {
        wp_redirect(add_query_arg('bhg_msg','nonce', admin_url('admin.php?page=bhg-tournaments')));
        exit;
    }
    global $wpdb;
    $t = $wpdb->prefix . 'bhg_tournaments';
    $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $data  = [
        'title'       => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
        'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
        'type'        => isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'weekly',
        'start_date'  => isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : null,
        'end_date'    => isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : null,
        'status'      => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active',
        'updated_at'  => current_time('mysql')
    ];
    try {
        if ($id > 0) {
            $wpdb->update($t, $data, ['id'=>$id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($t, $data);
        }
        wp_redirect(add_query_arg('bhg_msg','t_saved', admin_url('admin.php?page=bhg-tournaments')));
        exit;
    } catch (Throwable $e) {
        if (function_exists('error_log')) error_log('[BHG] tournament save error: ' . $e->getMessage());
        wp_redirect(add_query_arg('bhg_msg','t_error', admin_url('admin.php?page=bhg-tournaments')));
        exit;
    }
}
public function handle_save_affiliate() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_save_affiliate');
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_affiliates';
        $id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name  = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $url   = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        $status= isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        $data = ['name'=>$name, 'url'=>$url, 'status'=>$status, 'updated_at'=> current_time('mysql')];
        if ($id) {
            $wpdb->update($table, $data, ['id'=>$id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
        }
        wp_redirect(admin_url('admin.php?page=bhg-affiliates'));
        exit;
    }

    public function handle_delete_affiliate() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_delete_affiliate');
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_affiliates';
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id) { $wpdb->delete($table, ['id'=>$id], ['%d']); }
        wp_redirect(admin_url('admin.php?page=bhg-affiliates'));
        exit;
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options')) wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_save_settings');
        $opts = [
            'allow_guess_edit_until_close' => isset($_POST['allow_guess_edit_until_close']) ? 'yes' : 'no',
            'guesses_max' => isset($_POST['guesses_max']) ? max(1, (int) $_POST['guesses_max']) : 1,
        ];
        foreach ($opts as $k => $v) {
            update_option('bhg_' . $k, $v, false);
        }
        wp_redirect(admin_url('admin.php?page=bhg-settings&updated=1'));
        exit;
    }

    public function handle_save_user_meta() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No permission', 'bonus-hunt-guesser'));
        }
        check_admin_referer('bhg_save_user_meta');
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        if ($user_id) {
            $real_name    = isset($_POST['bhg_real_name']) ? sanitize_text_field(wp_unslash($_POST['bhg_real_name'])) : '';
            $is_affiliate = isset($_POST['bhg_is_affiliate']) ? 1 : 0;
            update_user_meta($user_id, 'bhg_real_name', $real_name);
            update_user_meta($user_id, 'bhg_is_affiliate', $is_affiliate);
        }
        wp_redirect(admin_url('admin.php?page=bhg-users'));
        exit;
    }


    public function admin_notices() {
        if (!current_user_can('manage_options')) return;
        if (!isset($_GET['bhg_msg'])) return;
        $msg = sanitize_text_field(wp_unslash($_GET['bhg_msg']));
        $map = [
            't_saved'   => __('Tournament saved.', 'bonus-hunt-guesser'),
            't_error'   => __('Could not save tournament. Check logs.', 'bonus-hunt-guesser'),
            'nonce'     => __('Security check failed. Please retry.', 'bonus-hunt-guesser'),
            'noaccess'  => __('You do not have permission to do that.', 'bonus-hunt-guesser'),
        ];
        $class = (strpos($msg, 'error') !== false || $msg === 'nonce' || $msg === 'noaccess') ? 'notice notice-error' : 'notice notice-success';
        $text = isset($map[$msg]) ? $map[$msg] : esc_html($msg);
        echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($text) . '</p></div>';
    }
}
