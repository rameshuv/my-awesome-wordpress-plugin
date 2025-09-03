<?php
if (!defined('ABSPATH')) exit;

class BHG_Admin {

    public function __construct(){
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_bhg_save_hunt', [$this, 'save_hunt']);
        add_action('admin_post_bhg_submit_guess', [$this, 'submit_guess']);
        add_action('admin_post_bhg_close_hunt', [$this, 'close_hunt']);
        add_action('admin_post_bhg_save_translation', [$this, 'save_translation']);
        add_action('admin_post_bhg_save_affiliate', [$this, 'save_affiliate']);
        add_action('admin_post_bhg_delete_affiliate', [$this, 'delete_affiliate']);
        add_action('admin_post_bhg_save_ad', [$this, 'save_ad']);
        add_action('admin_post_bhg_rebuild_tournaments', [$this, 'rebuild_tournaments']);
        add_action('admin_post_bhg_reset_demo', [$this, 'handle_reset_demo']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_init', [$this, 'check_nonce_referer']);
        
        // Add tournament initialization
        add_action('init', [$this, 'init_tournaments']);
    }

    public function init_tournaments() {
        // Ensure current tournaments exist
        $this->ensure_current_tournaments();
    }

    public function check_nonce_referer() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'bhg') === 0 && !current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
    }

    public function handle_form_submissions() {
        if (isset($_POST['action']) && $_POST['action'] === 'bhg_save_bonus_hunt') {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bhg_form_nonce')) {
                wp_die(__('Security check failed', 'bonus-hunt-guesser'));
            }
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
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

            $format = ['%s', '%f', '%d', '%s', '%s', '%d'];
            $hunt_id = 0;
            
            if (!empty($_POST['id'])) {
                $hunt_id = intval($_POST['id']);
                $result = $wpdb->update($table, $data, ['id' => $hunt_id], $format, ['%d']);
                if (false === $result) {
                    wp_die(__('Error updating bonus hunt', 'bonus-hunt-guesser'));
                }
            } else {
                $result = $wpdb->insert($table, $data, $format);
                if (false === $result) {
                    wp_die(__('Error creating bonus hunt', 'bonus-hunt-guesser'));
                }
                $hunt_id = (int) $wpdb->insert_id;
            }

            try {
                $hunt = $wpdb->get_row($wpdb->prepare("SELECT id, status, winner_user_id, closed_at FROM $table WHERE id=%d", $hunt_id));
                if ($hunt && $hunt->status === 'closed' && !empty($hunt->winner_user_id)) {
                    $this->update_tournament_results((int)$hunt->id, (int)$hunt->winner_user_id, $hunt->closed_at);
                }
            } catch (Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[BHG] Tournament update (save_hunt) error: ' . $e->getMessage());
                }
            }

            wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&updated=1'));
            exit;
        }
    }

    public function menu(){
        $cap = 'manage_options';
        $slug = 'bhg';
        add_menu_page(__('Bonus Hunt', 'bonus-hunt-guesser'), __('Bonus Hunt', 'bonus-hunt-guesser'), $cap, $slug, [$this, 'dashboard'], 'dashicons-awards', 55);
        add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts', [$this,'bonus_hunts']);
        add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments', [$this,'tournaments']);
        add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'), __('Users', 'bonus-hunt-guesser'), $cap, 'bhg-users', [$this,'users']);
        add_submenu_page($slug, __('Affiliates', 'bonus-hunt-guesser'), __('Affiliates', 'bonus-hunt-guesser'), $cap, 'bhg-affiliates', [$this,'affiliates']);
        add_submenu_page($slug, __('Advertising', 'bonus-hunt-guesser'), __('Advertising', 'bonus-hunt-guesser'), $cap, 'bhg-ads', [$this,'advertising']);
        add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'), __('Translations', 'bonus-hunt-guesser'), $cap, 'bhg-translations', [$this,'translations']);
        add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'), __('Database', 'bonus-hunt-guesser'), $cap, 'bhg-database', [$this,'database']);
        add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'), __('Settings', 'bonus-hunt-guesser'), $cap, 'bhg-settings', [$this,'settings']);
        add_submenu_page($slug, __('BHG Tools', 'bonus-hunt-guesser'), __('BHG Tools', 'bonus-hunt-guesser'), $cap, 'bhg-tools', [$this,'bhg_tools_page']);
    }

    public function dashboard(){
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        
        global $wpdb;
        $winners = $wpdb->get_results($wpdb->prepare(
            "SELECT h.title, u.display_name, h.final_balance, h.winner_diff, h.closed_at
            FROM {$wpdb->prefix}bhg_bonus_hunts h
            LEFT JOIN {$wpdb->users} u ON h.winner_user_id = u.ID
            WHERE h.status = 'closed' AND h.winner_user_id IS NOT NULL
            ORDER BY h.closed_at DESC
            LIMIT %d",
            5
        ));
        include BHG_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function bonus_hunts(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php'; 
    }
    
    public function tournaments(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        
        // Ensure tournaments exist
        $this->ensure_current_tournaments();
        
        // Display the tournaments page
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Tournaments', 'bonus-hunt-guesser') . '</h1>';
        
        echo '<div class="tab-wrapper">';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="#weekly" class="nav-tab nav-tab-active">' . esc_html__('Weekly', 'bonus-hunt-guesser') . '</a>';
        echo '<a href="#monthly" class="nav-tab">' . esc_html__('Monthly', 'bonus-hunt-guesser') . '</a>';
        echo '<a href="#yearly" class="nav-tab">' . esc_html__('Yearly', 'bonus-hunt-guesser') . '</a>';
        echo '</h2>';
        
        echo '<div id="weekly" class="tab-content">';
        echo '<h3>' . esc_html__('Weekly Tournament Standings', 'bonus-hunt-guesser') . '</h3>';
        $this->display_tournament_table('weekly');
        echo '</div>';
        
        echo '<div id="monthly" class="tab-content" style="display:none;">';
        echo '<h3>' . esc_html__('Monthly Tournament Standings', 'bonus-hunt-guesser') . '</h3>';
        $this->display_tournament_table('monthly');
        echo '</div>';
        
        echo '<div id="yearly" class="tab-content" style="display:none;">';
        echo '<h3>' . esc_html__('Yearly Tournament Standings', 'bonus-hunt-guesser') . '</h3>';
        $this->display_tournament_table('yearly');
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Add JavaScript for tab functionality
        echo '<script>
        jQuery(document).ready(function($) {
            $(\'.nav-tab-wrapper a\').click(function(e) {
                e.preventDefault();
                $(\'.nav-tab-wrapper a\').removeClass(\'nav-tab-active\');
                $(this).addClass(\'nav-tab-active\');
                $(\'.tab-content\').hide();
                $($(this).attr(\'href\')).show();
            });
        });
        </script>';
    }
    
    private function display_tournament_table($type) {
        global $wpdb;
        
        $current_period = $this->get_current_period($type);
        $table_name = $wpdb->prefix . 'bhg_tournaments';
        $wins_table = $wpdb->prefix . 'bhg_tournament_results';
        $users_table = $wpdb->prefix . 'users';
        
        // Get current tournament
        $tournament = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE type = %s AND period = %s",
            $type, $current_period
        ));
        
        if (!$tournament) {
            echo '<p>' . esc_html__('No tournament data available.', 'bonus-hunt-guesser') . '</p>';
            return;
        }
        
        // Get tournament wins
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT w.user_id, u.user_login, w.wins 
             FROM $wins_table w 
             JOIN $users_table u ON w.user_id = u.ID 
             WHERE w.tournament_id = %d 
             ORDER BY w.wins DESC, w.last_win_date ASC 
             LIMIT 100",
            $tournament->id
        ));
        
        if (empty($results)) {
            echo '<p>' . esc_html__('No data available.', 'bonus-hunt-guesser') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>
            <th>' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>
            <th>' . esc_html__('Username', 'bonus-hunt-guesser') . '</th>
            <th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th>
        </tr></thead>';
        echo '<tbody>';
        
        $position = 1;
        foreach ($results as $row) {
            echo '<tr>
                <td>' . $position . '</td>
                <td>' . esc_html($row->user_login) . '</td>
                <td>' . $row->wins . '</td>
            </tr>';
            $position++;
        }
        
        echo '</tbody></table>';
    }
    
    private function get_current_period($type) {
        switch ($type) {
            case 'weekly':
                return date('Y-W');
            case 'monthly':
                return date('Y-m');
            case 'yearly':
                return date('Y');
            default:
                return '';
        }
    }
    
    private function ensure_current_tournaments() {
        global $wpdb;
        
        $types = ['weekly', 'monthly', 'yearly'];
        $periods = [
            'weekly' => date('Y-W'),
            'monthly' => date('Y-m'),
            'yearly' => date('Y')
        ];
        
        $table_name = $wpdb->prefix . 'bhg_tournaments';
        
        foreach ($types as $type) {
            $period = $periods[$type];
            
            // Check if tournament exists
            $tournament = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE type = %s AND period = %s",
                $type, $period
            ));
            
            if (!$tournament) {
                // Create the tournament
                $start_date = $this->get_period_start_date($type, $period);
                $end_date = $this->get_period_end_date($type, $period);
                
                $wpdb->insert($table_name, [
                    'type' => $type,
                    'period' => $period,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 'active'
                ], ['%s', '%s', '%s', '%s', '%s', '%s']);
            }
        }
    }
    
    private function get_period_start_date($type, $period) {
        switch ($type) {
            case 'weekly':
                // Parse year and week from period (format: Y-W)
                list($year, $week) = explode('-', $period);
                return date('Y-m-d', strtotime($year . 'W' . $week . '1'));
            case 'monthly':
                return $period . '-01';
            case 'yearly':
                return $period . '-01-01';
            default:
                return date('Y-m-d');
        }
    }
    
    private function get_period_end_date($type, $period) {
        switch ($type) {
            case 'weekly':
                list($year, $week) = explode('-', $period);
                return date('Y-m-d', strtotime($year . 'W' . $week . '7'));
            case 'monthly':
                return date('Y-m-t', strtotime($period . '-01'));
            case 'yearly':
                return $period . '-12-31';
            default:
                return date('Y-m-d');
        }
    }
    
    public function users(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/users.php'; 
    }
    
    public function affiliates(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php'; 
    }
    
    public function advertising(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/advertising.php'; 
    }
    
    public function translations(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/translations.php'; 
    }
    
    public function database(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        include BHG_PLUGIN_DIR . 'admin/views/database.php'; 
    }
    
    public function settings(){ 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        if (class_exists('BHG_Settings')) BHG_Settings::render(); 
    }

    private function table($name){
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    public function save_hunt(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_save_hunt_nonce']) || !wp_verify_nonce($_POST['bhg_save_hunt_nonce'], 'bhg_save_hunt')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
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

        $format = ['%s', '%f', '%d', '%s', '%s', '%d'];
        $hunt_id = 0;
        
        if (!empty($_POST['id'])) {
            $hunt_id = intval($_POST['id']);
            $result = $wpdb->update($table, $data, ['id' => $hunt_id], $format, ['%d']);
            if (false === $result) {
                wp_die(__('Error updating bonus hunt', 'bonus-hunt-guesser'));
            }
        } else {
            $result = $wpdb->insert($table, $data, $format);
            if (false === $result) {
                wp_die(__('Error creating bonus hunt', 'bonus-hunt-guesser'));
            }
            $hunt_id = (int) $wpdb->insert_id;
        }

        try {
            $hunt = $wpdb->get_row($wpdb->prepare("SELECT id, status, winner_user_id, closed_at FROM $table WHERE id=%d", $hunt_id));
            if ($hunt && $hunt->status === 'closed' && !empty($hunt->winner_user_id)) {
                $this->update_tournament_results((int)$hunt->id, (int)$hunt->winner_user_id, $hunt->closed_at);
            }
        } catch (Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[BHG] Tournament update (save_hunt) error: ' . $e->getMessage());
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&updated=1'));
        exit;
    }

    public function close_hunt(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_close_hunt_nonce']) || !wp_verify_nonce($_POST['bhg_close_hunt_nonce'], 'bhg_close_hunt')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
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

        $guesses = $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->prefix."bhg_guesses` WHERE hunt_id=%d", (int)$id));
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

        $result = $wpdb->update($table, [
            'status' => 'closed',
            'final_balance' => $final,
            'winner_user_id' => $winner_user_id,
            'winner_diff' => $winner_diff,
            'closed_at' => $closed_at,
        ], ['id' => $id], ['%s', '%f', '%d', '%f', '%s'], ['%d']);

        if (false === $result) {
            wp_die(__('Error closing bonus hunt', 'bonus-hunt-guesser'));
        }

        $headers = [];
        $from = get_bloginfo('admin_email');
        $settings = get_option('bhg_settings', []);
        if (is_array($settings) && !empty($settings['email_from'])) $from = sanitize_email($settings['email_from']);
        if ($from) $headers[] = "From: " . $from;

        $user_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_id FROM $g_table WHERE hunt_id=%d", $id));
        $hunt = $wpdb->get_row($wpdb->prepare("SELECT * FROM `".$wpdb->prefix."bhg_bonus_hunts` WHERE id=%d", (int)$id));

        $subject_all = sprintf(__('Results: %s closed', 'bonus-hunt-guesser'), $hunt ? esc_html($hunt->title) : __('Bonus Hunt', 'bonus-hunt-guesser'));
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
                wp_mail($u->user_email, $subject_all, $body_all, $headers);
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
                    $hunt ? esc_html($hunt->title) : __('Bonus Hunt','bonus-hunt-guesser'),
                    function_exists('bhg_t') ? bhg_t('email_final_balance', 'Final Balance') : 'Final Balance',
                    number_format_i18n($final, 2)
                );
                wp_mail($u->user_email, $subject_w, $body_w, $headers);
            }

            try {
                $this->update_tournament_results((int)$id, (int)$winner_user_id, $closed_at);
            } catch (Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[BHG] Tournament update (close_hunt) error: ' . $e->getMessage());
                }
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&closed=1'));
        exit;
    }

    public function save_translation(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_save_translation_nonce']) || !wp_verify_nonce($_POST['bhg_save_translation_nonce'], 'bhg_save_translation')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
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
            $result = $wpdb->update($table, ['t_value' => $val], ['id' => $exists], ['%s'], ['%d']);
            if (false === $result) {
                wp_die(__('Error updating translation', 'bonus-hunt-guesser'));
            }
        } else {
            $result = $wpdb->insert($table, ['t_key' => $key, 't_value' => $val], ['%s', '%s']);
            if (false === $result) {
                wp_die(__('Error creating translation', 'bonus-hunt-guesser'));
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-translations&updated=1'));
        exit;
    }

    public function save_affiliate(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_save_affiliate_nonce']) || !wp_verify_nonce($_POST['bhg_save_affiliate_nonce'], 'bhg_save_affiliate')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
        }
        
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $slug = function_exists('bhg_slugify') ? bhg_slugify($_POST['slug'] ?? $name) : sanitize_title($_POST['slug'] ?? $name);
        $url  = esc_url_raw($_POST['url'] ?? '');
        
        if (!empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $result = $wpdb->update($table, ['name'=>$name,'slug'=>$slug,'url'=>$url], ['id'=>$id], ['%s','%s','%s'], ['%d']);
            if (false === $result) {
                wp_die(__('Error updating affiliate', 'bonus-hunt-guesser'));
            }
        } else {
            $result = $wpdb->insert($table, ['name'=>$name,'slug'=>$slug,'url'=>$url], ['%s','%s','%s']);
            if (false === $result) {
                wp_die(__('Error creating affiliate', 'bonus-hunt-guesser'));
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&updated=1'));
        exit;
    }

    public function delete_affiliate(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_delete_affiliate_nonce']) || !wp_verify_nonce($_POST['bhg_delete_affiliate_nonce'], 'bhg_delete_affiliate')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
        }
        
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $result = $wpdb->delete($table, ['id'=>$id], ['%d']);
            if (false === $result) {
                wp_die(__('Error deleting affiliate', 'bonus-hunt-guesser'));
            }
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&deleted=1'));
        exit;
    }

    public function save_ad(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
        if (!isset($_POST['bhg_save_ad_nonce']) || !wp_verify_nonce($_POST['bhg_save_ad_nonce'], 'bhg_save_ad')) {
            wp_die(__('Security check failed', 'bonus-hunt-guesser'));
        }
        
        global $wpdb;
        $table = $this->table('bhg_ads');
        $data = [
            'message' => wp_kses_post($_POST['message'] ?? ''),
            'link' => esc_url_raw($_POST['link'] ?? ''),
            'placement' => sanitize_text_field($_POST['placement'] ?? 'footer'),
            'visibility' => sanitize_text_field($_POST['visibility'] ?? 'all'),
            'target_pages' => sanitize_text_field($_POST['target_pages'] ?? ''),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        $format = ['%s', '%s', '%s', '%s', '%s', '%d'];
        
        if (!empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $result = $wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);
            if (false === $result) {
                wp_die(__('Error updating ad', 'bonus-hunt-guesser'));
            }
        } else {
            $result = $wpdb->insert($table, $data, $format);
            if (false === $result) {
                wp_die(__('Error creating ad', 'bonus-hunt-guesser'));
            }
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

        // Use direct queries for TRUNCATE and DELETE operations
        $wpdb->query("TRUNCATE TABLE $r_table");
        $wpdb->query("DELETE FROM $t_table");

        $closed = $wpdb->get_results("SELECT id, winner_user_id, closed_at FROM $h_table WHERE status='closed' AND winner_user_id IS NOT NULL AND closed_at IS NOT NULL");

        $ensure_tournament = function($period, $period_key, $title = null) use ($wpdb, $t_table){
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t_table WHERE type=%s AND period=%s", $period, $period_key));
            if ($id) return (int)$id;
            if ($title === null) $title = ucfirst($period) . ' ' . $period_key;
            $result = $wpdb->insert($t_table, [
                'type' => $period,
                'period' => $period_key,
                'start_date' => current_time('mysql'),
                'end_date' => current_time('mysql'),
                'status' => 'active',
                'created_at' => current_time('mysql'),
            ], ['%s', '%s', '%s', '%s', '%s', '%s']);
            if (false === $result) {
                return 0;
            }
            return (int)$wpdb->insert_id;
        };

        foreach ($closed as $row){
            $ts = strtotime($row->closed_at);
            if (!$ts) continue;
            $user_id = (int)$row->winner_user_id;
            if (!$user_id) continue;

            $isoYear = date('o', $ts);
            $week = str_pad(date('W', $ts), 2, '0', STR_PAD_LEFT);
            $weekKey = $isoYear . '-W' . $week;
            $monthKey = date('Y-m', $ts);
            $yearKey = date('Y', $ts);

            $weekly_id  = $ensure_tournament('weekly',  $weekKey);
            $monthly_id = $ensure_tournament('monthly', $monthKey, date('F Y', $ts) . ' Tournament');
            $yearly_id  = $ensure_tournament('yearly',  $yearKey,  $yearKey . ' Tournament');

            foreach ([$weekly_id, $monthly_id, $yearly_id] as $tid){
                if ($tid > 0) {
                    $wpdb->query($wpdb->prepare(
                        "INSERT INTO $r_table (tournament_id, user_id, wins) VALUES (%d, %d, 1)
                         ON DUPLICATE KEY UPDATE wins = wins + 1",
                        $tid, $user_id
                    ));
                }
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
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $tournaments_table WHERE type=%s AND period=%s", $period, $period_key));
            if ($id) return (int)$id;
            if ($title === null) $title = ucfirst($period) . ' ' . $period_key;
            $result = $wpdb->insert($tournaments_table, [
                'type' => $period,
                'period' => $period_key,
                'start_date' => current_time('mysql'),
                'end_date' => current_time('mysql'),
                'status' => 'active',
                'created_at' => current_time('mysql')
            ], ['%s', '%s', '%s', '%s', '%s', '%s']);
            if (false === $result) {
                return 0;
            }
            return (int)$wpdb->insert_id;
        };

        $weekly_id  = $ensure_tournament('weekly',  $weekKey);
        $monthly_id = $ensure_tournament('monthly', $monthKey, date('F Y', $ts) . ' Tournament');
        $yearly_id  = $ensure_tournament('yearly',  $yearKey,  $yearKey . ' Tournament');

        foreach ([$weekly_id, $monthly_id, $yearly_id] as $tid){
            if ($tid > 0) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO $results_table (tournament_id, user_id, wins) VALUES (%d, %d, 1)
                     ON DUPLICATE KEY UPDATE wins = wins + 1",
                    $tid, (int)$winner_user_id
                ));
            }
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
            $result = $wpdb->update($table, ['guess_value'=>$guess, 'updated_at'=>current_time('mysql', 1)], ['id'=>$exists], ['%f','%s'], ['%d']);
            if (false === $result) {
                wp_die(__('Error updating guess', 'bonus-hunt-guesser'));
            }
        } else {
            $result = $wpdb->insert($table, ['user_id'=>$uid, 'hunt_id'=>$hunt_id, 'guess_value'=>$guess, 'created_at'=>current_time('mysql', 1)], ['%d','%d','%f','%s']);
            if (false === $result) {
                wp_die(__('Error creating guess', 'bonus-hunt-guesser'));
            }
        }
        
        $redirect_url = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(esc_url_raw($redirect_url));
        exit;
    }

    public function bhg_tools_page(){
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BHG Tools', 'bonus-hunt-guesser'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bhg_reset_demo">
                <?php wp_nonce_field('bhg_reset_demo','bhg_nonce'); ?>
                <p><?php esc_html_e('Reset and reinsert demo data. This will wipe current demo rows in hunts, guesses, tournaments, winners and ads tables.','bonus-hunt-guesser'); ?></p>
                <p><button type="submit" class="button button-primary"><?php esc_html_e('Reset Demo Data', 'bonus-hunt-guesser'); ?></button></p>
            </form>
        </div>
        <?php
    }

    public function handle_reset_demo(){
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'bonus-hunt-guesser'));
        }
        
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
}