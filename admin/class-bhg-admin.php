<?php
if (!defined('ABSPATH')) exit;

class BHG_Admin {

    public function __construct(){
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('admin_post_bhg_save_hunt', [$this, 'save_hunt']);
        add_action('admin_post_bhg_submit_guess', [$this, 'submit_guess']);
        add_action('admin_post_bhg_close_hunt', [$this, 'close_hunt']);
        add_action('admin_post_bhg_save_translation', [$this, 'save_translation']);
        add_action('admin_post_bhg_save_affiliate', [$this, 'save_affiliate']);
        add_action('admin_post_bhg_delete_affiliate', [$this, 'delete_affiliate']);
        add_action('admin_post_bhg_save_user_affiliates', [$this, 'save_user_affiliates']);
        add_action('admin_post_bhg_save_ad', [$this, 'save_ad']);
        add_action('admin_post_bhg_rebuild_tournaments', [$this, 'rebuild_tournaments']);
        add_action('admin_post_bhg_reset_demo', [$this, 'handle_reset_demo']);
    }

    public function admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'bhg-') === false) {
            return;
        }
        
        wp_enqueue_style('bhg-admin', BHG_PLUGIN_URL . 'assets/css/admin.css', array(), BHG_VERSION);
        wp_enqueue_script('bhg-admin', BHG_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), BHG_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('bhg-admin', 'bhg_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bhg_nonce')
        ));
        
        // Enqueue WordPress editor for advertising page
        if ($hook === 'toplevel_page_bhg-ads') {
            wp_enqueue_editor();
        }
    }

    public function menu(){
        $cap = 'manage_options';
        $slug = 'bhg';
        
        // Main menu
        add_menu_page(
            __('Bonus Hunt Guesser', 'bonus-hunt-guesser'),
            __('Bonus Hunt Guesser', 'bonus-hunt-guesser'),
            $cap,
            $slug,
            [$this, 'dashboard'],
            'dashicons-chart-area',
            30
        );
        
        // Submenus - fixed to remove duplicate BHG Tools
        add_submenu_page($slug, __('Dashboard', 'bonus-hunt-guesser'), __('Dashboard', 'bonus-hunt-guesser'), $cap, 'bhg-dashboard', [$this, 'dashboard']);
        add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts', [$this,'bonus_hunts']);
        add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments', [$this,'tournaments']);
        add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'), __('Users', 'bonus-hunt-guesser'), $cap, 'bhg-users', [$this,'users']);
        add_submenu_page($slug, __('Affiliates', 'bonus-hunt-guesser'), __('Affiliates', 'bonus-hunt-guesser'), $cap, 'bhg-affiliates', [$this,'affiliates']);
        add_submenu_page($slug, __('Advertising', 'bonus-hunt-guesser'), __('Advertising', 'bonus-hunt-guesser'), $cap, 'bhg-ads', [$this,'advertising']);
        add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'), __('Translations', 'bonus-hunt-guesser'), $cap, 'bhg-translations', [$this,'translations']);
        add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'), __('Database', 'bonus-hunt-guesser'), $cap, 'bhg-database', [$this,'database']);
        add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'), __('Settings', 'bonus-hunt-guesser'), $cap, 'bhg-settings', [$this,'settings']);
        add_submenu_page($slug, __('BHG Tools', 'bonus-hunt-guesser'), __('BHG Tools', 'bonus-hunt-guesser'), $cap, 'bhg-tools', [$this,'bhg_tools_page']);
        
        // Remove the duplicate BHG Tools menu item that was created by the main menu
        remove_submenu_page($slug, $slug);
    }

    public function dashboard(){
        global $wpdb;
        $winners = $wpdb->get_results("
            SELECT h.title, u.display_name, h.final_balance, h.winner_diff, h.closed_at
            FROM {$wpdb->prefix}bhg_bonus_hunts h
            LEFT JOIN {$wpdb->users} u ON h.winner_user_id = u.ID
            WHERE h.status = 'closed' AND h.winner_user_id IS NOT NULL
            ORDER BY h.closed_at DESC
            LIMIT 5
        ");
        include BHG_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function bonus_hunts(){ 
        // Enhanced to handle edit/create views
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $id > 0) {
            include BHG_PLUGIN_DIR . 'admin/views/edit-bonus-hunt.php';
        } else if ($action === 'create') {
            include BHG_PLUGIN_DIR . 'admin/views/edit-bonus-hunt.php';
        } else {
            include BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php';
        }
    }
    
    public function tournaments(){ 
        // Enhanced to handle edit/create views
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $id > 0) {
            include BHG_PLUGIN_DIR . 'admin/views/edit-tournament.php';
        } else if ($action === 'create') {
            include BHG_PLUGIN_DIR . 'admin/views/edit-tournament.php';
        } else {
            include BHG_PLUGIN_DIR . 'admin/views/tournaments.php';
        }
    }
    
    public function users(){ 
        // Enhanced to handle edit views and make rows clickable
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $id > 0) {
            include BHG_PLUGIN_DIR . 'admin/views/edit-user.php';
        } else {
            include BHG_PLUGIN_DIR . 'admin/views/users.php';
        }
    }
    
    public function affiliates(){ include BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php'; }
    
    public function advertising(){ 
        // Enhanced with HTML editor instead of link field
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($action === 'edit' && $id > 0) {
            include BHG_PLUGIN_DIR . 'admin/views/edit-ad.php';
        } else if ($action === 'create') {
            include BHG_PLUGIN_DIR . 'admin/views/edit-ad.php';
        } else {
            include BHG_PLUGIN_DIR . 'admin/views/advertising.php';
        }
    }
    
    public function translations(){ 
        // Fixed to show translations
        include BHG_PLUGIN_DIR . 'admin/views/translations.php'; 
    }
    
    public function database(){ 
        // Fixed to show database information
        include BHG_PLUGIN_DIR . 'admin/views/database.php'; 
    }
    
    public function settings(){ 
        if (class_exists('BHG_Settings')) {
            BHG_Settings::render(); 
        } else {
            include BHG_PLUGIN_DIR . 'admin/views/settings.php';
        }
    }

    private function table($name){
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    public function save_hunt(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_save_hunt')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table = $this->table('bhg_bonus_hunts');

        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'starting_balance' => floatval($_POST['starting_balance'] ?? 0),
            'num_bonuses' => intval($_POST['num_bonuses'] ?? 0),
            'prizes' => wp_kses_post($_POST['prizes'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'open'),
            'affiliate_site_id' => ($_POST['affiliate_site_id'] ?? '') !== '' ? intval($_POST['affiliate_site_id']) : null,
        ];

        $hunt_id = 0;
        if (!empty($_POST['id'])) {
            $hunt_id = intval($_POST['id']);
            $wpdb->update($table, $data, ['id' => $hunt_id]);
        } else {
            $wpdb->insert($table, $data);
            $hunt_id = (int) $wpdb->insert_id;
        }

        // If this save left the hunt closed with a winner, ensure tournaments updated
        try {
            $hunt = $wpdb->get_row($wpdb->prepare("SELECT id, status, winner_user_id, closed_at FROM $table WHERE id=%d", $hunt_id));
            if ($hunt && $hunt->status === 'closed' && !empty($hunt->winner_user_id)) {
                $this->update_tournament_results((int)$hunt->id, (int)$hunt->winner_user_id, $hunt->closed_at);
            }
        } catch (Throwable $e) {
            if (function_exists('error_log')) error_log('[BHG] Tournament update (save_hunt) error: ' . $e->getMessage());
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&updated=1'));
        exit;
    }

    public function close_hunt(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_close_hunt')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table  = $this->table('bhg_bonus_hunts');
        $g_table = $this->table('bhg_guesses');

        $id = intval($_POST['id'] ?? 0);
        $final = isset($_POST['final_balance']) ? floatval($_POST['final_balance']) : null;

        if (!$id || $final === null) {
            wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&error=final_balance_required'));
            exit;
        }

        $guesses = $wpdb->get_results($wpdb->prepare("SELECT * FROM $g_table WHERE hunt_id=%d ORDER BY created_at ASC", $id));
        $winner_user_id = null;
        $winner_diff = null;

        foreach ($guesses as $g){
            $val = isset($g->guess_value) ? $g->guess_value : (isset($g->guess_amount) ? $g->guess_amount : 0);
            $diff = abs(floatval($val) - $final);
            if ($winner_diff === null || $diff < $winner_diff){
                $winner_diff = $diff;
                $winner_user_id = intval($g->user_id);
            }
        }

        $closed_at = current_time('mysql');

        $wpdb->update($table, [
            'status' => 'closed',
            'final_balance' => $final,
            'winner_user_id' => $winner_user_id,
            'winner_diff' => $winner_diff,
            'closed_at' => $closed_at,
        ], ['id' => $id]);

        // Send emails
        $headers = [];
        $from = get_bloginfo('admin_email');
        $settings = get_option('bhg_settings', []);
        if (is_array($settings) && !empty($settings['email_from'])) $from = $settings['email_from'];
        if ($from) $headers[] = "From: " . sanitize_email($from);

        $user_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_id FROM $g_table WHERE hunt_id=%d", $id));
        $hunt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $id));

        $subject_all = sprintf(__('Results: %s closed', 'bonus-hunt-guesser'), $hunt ? $hunt->title : __('Bonus Hunt', 'bonus-hunt-guesser'));
        $body_all = sprintf(
            "%s\n\n%s: €%s\n%s: %s\n",
            function_exists('bhg_t') ? bhg_t('email_results_title', 'The Bonus Hunt has been closed!') : 'The Bonus Hunt has been closed!',
            function_exists('bhg_t') ? bhg_t('email_final_balance', 'Final Balance') : 'Final Balance',
            number_format_i18n($final, 2),
            function_exists('bhg_t') ? bhg_t('email_winner', 'Winner') : 'Winner',
            $winner_user_id ? get_the_author_meta('user_login', $winner_user_id) : __('N/A','bonus-hunt-guesser')
        );

        foreach ($user_ids as $uid){
            $u = get_userdata($uid);
            if ($u && $u->user_email){
                @wp_mail($u->user_email, $subject_all, $body_all, $headers);
            }
        }

        if ($winner_user_id){
            $u = get_userdata($winner_user_id);
            if ($u && $u->user_email){
                $subject_w = function_exists('bhg_t') ? bhg_t('email_congrats_subject', 'Congratulations! You won the Bonus Hunt') : 'Congratulations! You won the Bonus Hunt';
                $body_w = sprintf(
                    "%s\n\n%s: %s\n%s: €%s\n",
                    function_exists('bhg_t') ? bhg_t('email_congrats_body', 'You had the closest guess. Great job!') : 'You had the closest guess. Great job!',
                    function_exists('bhg_t') ? bhg_t('email_hunt', 'Hunt') : 'Hunt',
                    $hunt ? $hunt->title : __('Bonus Hunt','bonus-hunt-guesser'),
                    function_exists('bhg_t') ? bhg_t('email_final_balance', 'Final Balance') : 'Final Balance',
                    number_format_i18n($final, 2)
                );
                @wp_mail($u->user_email, $subject_w, $body_w, $headers);
            }

            try {
                $this->update_tournament_results((int)$id, (int)$winner_user_id, $closed_at);
            } catch (Throwable $e) {
                if (function_exists('error_log')) error_log('[BHG] Tournament update (close_hunt) error: ' . $e->getMessage());
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&closed=1'));
        exit;
    }

    public function save_translation(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_save_translation')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table = $this->table('bhg_translations');
        $key = sanitize_key($_POST['t_key'] ?? '');
        $val = wp_kses_post($_POST['t_value'] ?? '');
        if (!$key) {
            wp_safe_redirect(admin_url('admin.php?page=bhg-translations&error=1'));
            exit;
        }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE t_key=%s", $key));
        if ($exists) {
            $wpdb->update($table, ['t_value' => $val], ['id' => $exists]);
        } else {
            $wpdb->insert($table, ['t_key' => $key, 't_value' => $val]);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-translations&updated=1'));
        exit;
    }

    public function save_affiliate(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_save_affiliate')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = function_exists('bhg_slugify') ? bhg_slugify($_POST['slug'] ?? $name) : sanitize_title($_POST['slug'] ?? $name);
        $url  = esc_url_raw($_POST['url'] ?? '');
        if (!empty($_POST['id'])) {
            $wpdb->update($table, ['name'=>$name,'slug'=>$slug,'url'=>$url], ['id'=>intval($_POST['id'])]);
        } else {
            $wpdb->insert($table, ['name'=>$name,'slug'=>$slug,'url'=>$url]);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&updated=1'));
        exit;
    }

    public function delete_affiliate(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_delete_affiliate')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $id = intval($_POST['id'] ?? 0);
        if ($id) $wpdb->delete($table, ['id'=>$id]);
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&deleted=1'));
        exit;
    }

    public function save_ad(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_save_ad')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $table = $this->table('bhg_ads');
        
        // Removed link field, using HTML editor for message
        $data = [
            'message' => wp_kses_post($_POST['message'] ?? ''),
            'placement' => sanitize_text_field($_POST['placement'] ?? 'footer'),
            'visibility' => sanitize_text_field($_POST['visibility'] ?? 'all'),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        if (!empty($_POST['id'])) {
            $wpdb->update($table, $data, ['id'=>intval($_POST['id'])]);
        } else {
            $wpdb->insert($table, $data);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-ads&updated=1'));
        exit;
    }

    public function rebuild_tournaments(){
        if (!current_user_can('manage_options')) wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        check_admin_referer('bhg_rebuild_tournaments');

        global $wpdb;
        $t_table = $wpdb->prefix . 'bhg_tournaments';
        $r_table = $wpdb->prefix . 'bhg_tournament_results';
        $h_table = $wpdb->prefix . 'bhg_bonus_hunts';

        // Reset tournaments and results
        $wpdb->query("TRUNCATE TABLE $r_table");
        $wpdb->query("DELETE FROM $t_table");

        // Fetch closed hunts with winners
        $closed = $wpdb->get_results("SELECT id, winner_user_id, closed_at FROM $h_table WHERE status='closed' AND winner_user_id IS NOT NULL AND closed_at IS NOT NULL");

        $ensure_tournament = function($period, $period_key, $title = null) use ($wpdb, $t_table){
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t_table WHERE period=%s AND period_key=%s", $period, $period_key));
            if ($id) return (int)$id;
            if ($title === null) $title = ucfirst($period) . ' ' . $period_key;
            $wpdb->insert($t_table, [
                'title' => $title,
                'period' => $period,
                'period_key' => $period_key,
                'status' => 'active',
                'created_at' => current_time('mysql'),
            ]);
            return (int)$wpdb->insert_id;
        };

        foreach ($closed as $row){
            $ts = strtotime($row->closed_at);
            if (!$ts) continue;
            $user_id = (int)$row->winner_user_id;
            if (!$user_id) continue;

            $isoYear = date('o', $ts);
            $week = str_pad(date('W', $ts), 2, '0', STR_PAD_LEFT);
            $weekKey = $isoYear . '-W' . $week;      // e.g. 2025-W34
            $monthKey = date('Y-m', $ts);            // e.g. 2025-08
            $yearKey = date('Y', $ts);               // e.g. 2025

            $weekly_id  = $ensure_tournament('weekly',  $weekKey);
            $monthly_id = $ensure_tournament('monthly', $monthKey, date('F Y', $ts) . ' Tournament');
            $yearly_id  = $ensure_tournament('yearly',  $yearKey,  $yearKey . ' Tournament');

            foreach ([$weekly_id, $monthly_id, $yearly_id] as $tid){
                // Upsert: add win
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $r_table (tournament_id, user_id, wins) VALUES (%d, %d, 1)
                     ON DUPLICATE KEY UPDATE wins = wins + 1",
                    $tid, $user_id
                ));
            }
        }

        wp_redirect(add_query_arg(['page'=>'bhg-tournaments', 'bhg_rebuilt'=>'1'], admin_url('admin.php')));
        exit;
    }

    private function update_tournament_results($hunt_id, $winner_user_id, $closed_at = null) {
        global $wpdb;
        if (!$winner_user_id) return;

        $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
        if ($closed_at) {
            $ts = strtotime($closed_at);
        } else {
            $closed_at = $wpdb->get_var($wpdb->prepare("SELECT closed_at FROM $hunts_table WHERE id=%d", $hunt_id));
            $ts = $closed_at ? strtotime($closed_at) : time();
        }

        $isoYear = date('o', $ts);
        $week = str_pad(date('W', $ts), 2, '0', STR_PAD_LEFT);
        $weekKey = $isoYear . '-W' . $week;
        $monthKey = date('Y-m', $ts);
        $yearKey = date('Y', $ts);

        $tournaments_table = $wpdb->prefix . 'bhg_tournaments';
        $results_table = $wpdb->prefix . 'bhg_tournament_results';

        $ensure_tournament = function($period, $period_key, $title = null) use ($wpdb, $tournaments_table){
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $tournaments_table WHERE period=%s AND period_key=%s", $period, $period_key));
            if ($id) return (int)$id;
            if ($title === null) $title = ucfirst($period) . ' ' . $period_key;
            $wpdb->insert($tournaments_table, [
                'title' => $title,
                'period' => $period,
                'period_key' => $period_key,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ]);
            return (int)$wpdb->insert_id;
        };

        $weekly_id  = $ensure_tournament('weekly',  $weekKey);
        $monthly_id = $ensure_tournament('monthly', $monthKey, date('F Y', $ts) . ' Tournament');
        $yearly_id  = $ensure_tournament('yearly',  $yearKey,  $yearKey . ' Tournament');

        foreach ([$weekly_id, $monthly_id, $yearly_id] as $tid){
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $results_table (tournament_id, user_id, wins) VALUES (%d, %d, 1)
                 ON DUPLICATE KEY UPDATE wins = wins + 1",
                $tid, (int)$winner_user_id
            ));
        }
    }

    public function submit_guess(){
        if (!is_user_logged_in()) wp_die(__('You must be logged in','bonus-hunt-guesser'));
        if (empty($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_submit_guess')) wp_die(__('Invalid nonce','bonus-hunt-guesser'));
        $uid = get_current_user_id();
        $hunt_id = isset($_POST['hunt_id']) ? intval($_POST['hunt_id']) : 0;
        $guess = isset($_POST['guess_value']) ? floatval($_POST['guess_value']) : 0;
        if ($hunt_id<=0 || $guess<0) wp_die(__('Invalid input','bonus-hunt-guesser'));
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_guesses';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id=%d AND hunt_id=%d", $uid, $hunt_id));
        if ($exists){
            $wpdb->update($table, ['guess_value'=>$guess, 'updated_at'=>current_time('mysql', 1)], ['id'=>$exists], ['%f','%s'], ['%d']);
        } else {
            $wpdb->insert($table, ['user_id'=>$uid, 'hunt_id'=>$hunt_id, 'guess_value'=>$guess, 'created_at'=>current_time('mysql', 1)], ['%d','%d','%f','%s']);
        }
        wp_safe_redirect(wp_get_referer() ?: home_url('/'));
        exit;
    }

    public function bhg_tools_page(){
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>BHG Tools</h1>
            
            <div class="bhg-tools-container">
                <div class="bhg-tool-card">
                    <h2><?php _e('Demo Data', 'bonus-hunt-guesser'); ?></h2>
                    <p><?php _e('Reset and reinsert demo data. This will wipe current demo rows in hunts, guesses, tournaments, winners and ads tables.', 'bonus-hunt-guesser'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="bhg_reset_demo">
                        <?php wp_nonce_field('bhg_reset_demo','bhg_nonce'); ?>
                        <p><button type="submit" class="button button-primary"><?php _e('Reset Demo Data', 'bonus-hunt-guesser'); ?></button></p>
                    </form>
                </div>
                
                <div class="bhg-tool-card">
                    <h2><?php _e('System Information', 'bonus-hunt-guesser'); ?></h2>
                    <p><?php _e('View system information and plugin status.', 'bonus-hunt-guesser'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bhg-tools&tab=system'); ?>" class="button"><?php _e('View System Info', 'bonus-hunt-guesser'); ?></a>
                </div>
                
                <div class="bhg-tool-card">
                    <h2><?php _e('Database Tools', 'bonus-hunt-guesser'); ?></h2>
                    <p><?php _e('Optimize database tables and manage plugin data.', 'bonus-hunt-guesser'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=bhg-database'); ?>" class="button"><?php _e('Database Management', 'bonus-hunt-guesser'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_reset_demo(){
        if (empty($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_reset_demo')){
            wp_die(__('Invalid request','bonus-hunt-guesser'));
        }
        if (function_exists('bhg_reset_demo_and_seed')){
            bhg_reset_demo_and_seed();
            update_option('bhg_demo_notice', 1);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-tools'));
        exit;
    } 

    public function save_user_affiliates(){
        if (class_exists('BHG_Utils')) { 
            BHG_Utils::require_cap(); 
            if (!BHG_Utils::verify_nonce('bhg_save_user_affiliates')) wp_die('Bad nonce'); 
        }
        global $wpdb;
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) wp_safe_redirect(admin_url('admin.php?page=bhg-users'));
        $selected = $_POST['affiliate_site_id'] ?? [];
        if (!is_array($selected)) $selected = [$selected];
        // clear existing
        $table = $wpdb->prefix . 'bhg_user_affiliates';
        $wpdb->delete($table, ['user_id' => $user_id], ['%d']);
        foreach($selected as $aid){
            $aid = intval($aid);
            if ($aid > 0) $wpdb->insert($table, ['user_id'=>$user_id,'affiliate_site_id'=>$aid]);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-users&updated_affiliates=1'));
        exit;
    }
}