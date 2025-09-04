<?php
if (!defined('ABSPATH')) exit;

class BHG_Admin {

    public function __construct() {
        // Admin pages & assets
        add_action('admin_menu', [$this, 'menu']);

        // Form handlers
        add_action('admin_post_bhg_save_hunt',       [$this, 'save_hunt']);
        add_action('admin_post_bhg_close_hunt',      [$this, 'close_hunt']);
        add_action('admin_post_bhg_save_translation',[$this, 'save_translation']);
        add_action('admin_post_bhg_save_affiliate',  [$this, 'save_affiliate']);
        add_action('admin_post_bhg_delete_affiliate',[$this, 'delete_affiliate']);
        add_action('admin_post_bhg_save_ad',         [$this, 'save_ad']);
        add_action('admin_post_bhg_reset_demo',      [$this, 'handle_reset_demo']);
        add_action('admin_post_bhg_submit_guess',    [$this, 'submit_guess']);
        add_action('admin_post_bhg_save_tournament', [$this, 'handle_save_tournament']);
        add_action('admin_post_bhg_delete_tournament', [$this, 'handle_delete_tournament']);

        // Tournaments bootstrap
        add_action('init', [$this, 'ensure_current_tournaments']);
    }

    /* =======================
     * Admin Menu
     * ======================= */
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

        add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts', [$this, 'bonus_hunts']);
        add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments', [$this, 'tournaments']);
        add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'), __('Users', 'bonus-hunt-guesser'), $cap, 'bhg-users', [$this, 'users']);
        add_submenu_page($slug, __('Affiliates', 'bonus-hunt-guesser'), __('Affiliates', 'bonus-hunt-guesser'), $cap, 'bhg-affiliates', [$this, 'affiliates']);
        add_submenu_page($slug, __('Advertising', 'bonus-hunt-guesser'), __('Advertising', 'bonus-hunt-guesser'), $cap, 'bhg-ads', [$this, 'advertising']);
        add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'), __('Translations', 'bonus-hunt-guesser'), $cap, 'bhg-translations', [$this, 'translations']);
        add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'), __('Database', 'bonus-hunt-guesser'), $cap, 'bhg-database', [$this, 'database']);
        add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'), __('Settings', 'bonus-hunt-guesser'), $cap, 'bhg-settings', [$this, 'settings']);
        add_submenu_page($slug, __('BHG Tools', 'bonus-hunt-guesser'), __('BHG Tools', 'bonus-hunt-guesser'), $cap, 'bhg-tools', [$this, 'bhg_tools_page']);
    }

    /* =======================
     * Views
     * ======================= */
    private function require_caps() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
        }
    }

    public function dashboard()     { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/dashboard.php'; }
    public function bonus_hunts()   { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php'; }
    public function tournaments()   { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/tournaments.php'; }
    public function users()         { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/users.php'; }
    public function affiliates()    { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php'; }
    public function advertising()   { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/advertising.php'; }
    public function translations()  { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/translations.php'; }
    public function database()      { $this->require_caps(); include BHG_PLUGIN_DIR . 'admin/views/database.php'; }
    public function settings()      { $this->require_caps(); if (class_exists('BHG_Settings')) BHG_Settings::render(); }

    private function table($name) { global $wpdb; return $wpdb->prefix . $name; }

    /* =======================
     * Hunts (create/update/close)
     * ======================= */
    public function save_hunt() {
        $this->require_caps();
        if (!isset($_POST['bhg_save_hunt_nonce']) || !wp_verify_nonce($_POST['bhg_save_hunt_nonce'], 'bhg_save_hunt')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }
        global $wpdb;
        $table = $this->table('bhg_bonus_hunts');

        $data = [
            'title'             => sanitize_text_field($_POST['title'] ?? ''),
            'starting_balance'  => (float)($_POST['starting_balance'] ?? 0),
            'num_bonuses'       => (int)($_POST['num_bonuses'] ?? 0),
            'prizes'            => wp_kses_post($_POST['prizes'] ?? ''),
            'status'            => sanitize_text_field($_POST['status'] ?? 'open'),
            'affiliate_site_id' => ($_POST['affiliate_site_id'] ?? '') !== '' ? (int)$_POST['affiliate_site_id'] : null,
        ];
        $format = ['%s','%f','%d','%s','%s','%d'];

        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $ok = $wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
            if ($ok === false) wp_die(esc_html__('Error updating bonus hunt', 'bonus-hunt-guesser'));
        } else {
            $ok = $wpdb->insert($table, $data, $format);
            if ($ok === false) wp_die(esc_html__('Error creating bonus hunt', 'bonus-hunt-guesser'));
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&updated=1'));
        exit;
    }

    public function close_hunt() {
        $this->require_caps();
        if (!isset($_POST['bhg_close_hunt_nonce']) || !wp_verify_nonce($_POST['bhg_close_hunt_nonce'], 'bhg_close_hunt')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }

        global $wpdb;
        $table   = $this->table('bhg_bonus_hunts');
        $g_table = $this->table('bhg_guesses');

        $id    = (int)($_POST['id'] ?? 0);
        $final = isset($_POST['final_balance']) ? (float)$_POST['final_balance'] : null;

        if (!$id || $final === null) {
            wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&error=final_balance_required'));
            exit;
        }

        $guesses = $wpdb->get_results($wpdb->prepare("SELECT user_id, guess_value FROM `$g_table` WHERE hunt_id=%d", $id));
        $winner_user_id = 0;
        $winner_diff    = null;

        foreach ($guesses as $g) {
            $val  = (float)$g->guess_value;
            $diff = abs($val - $final);
            if ($winner_diff === null || $diff < $winner_diff) {
                $winner_diff    = $diff;
                $winner_user_id = (int)$g->user_id;
            }
        }

        $closed_at = current_time('mysql', 1);
        $ok = $wpdb->update($table, [
            'status'         => 'closed',
            'final_balance'  => $final,
            'winner_user_id' => $winner_user_id ?: null,
            'winner_diff'    => $winner_diff,
            'closed_at'      => $closed_at,
        ], ['id' => $id], ['%s','%f','%d','%f','%s'], ['%d']);

        if ($ok === false) wp_die(esc_html__('Error closing bonus hunt', 'bonus-hunt-guesser'));

        // Email notifications (basic)
        $this->email_results_to_participants($id, $final, $winner_user_id);

        // Update tournament tallies
        if ($winner_user_id) {
            try { $this->update_tournament_results($id, $winner_user_id, $closed_at); }
            catch (\Throwable $e) { if (defined('WP_DEBUG') && WP_DEBUG) error_log('[BHG] tournament update error: ' . $e->getMessage()); }
        }

        wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts&closed=1'));
        exit;
    }

    private function email_results_to_participants($hunt_id, $final_balance, $winner_user_id) {
        global $wpdb;
        $hunts = $this->table('bhg_bonus_hunts');
        $guesses = $this->table('bhg_guesses');

        $hunt   = $wpdb->get_row($wpdb->prepare("SELECT title FROM `$hunts` WHERE id=%d", $hunt_id));
        $title  = $hunt ? $hunt->title : __('Bonus Hunt', 'bonus-hunt-guesser');

        $subject_all = sprintf(__('Results: %s closed', 'bonus-hunt-guesser'), $title);
        $body_all = sprintf(
            "%s\n\n%s: %s\n%s: %s\n",
            __('The Bonus Hunt has been closed!', 'bonus-hunt-guesser'),
            __('Final Balance', 'bonus-hunt-guesser'),
            number_format_i18n($final_balance, 2),
            __('Winner', 'bonus-hunt-guesser'),
            $winner_user_id ? get_the_author_meta('user_login', $winner_user_id) : __('N/A', 'bonus-hunt-guesser')
        );

        $user_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT user_id FROM `$guesses` WHERE hunt_id=%d", $hunt_id));
        foreach ($user_ids as $uid) {
            $u = get_userdata((int)$uid);
            if ($u && $u->user_email) {
                wp_mail($u->user_email, $subject_all, $body_all);
            }
        }

        if ($winner_user_id) {
            $u = get_userdata((int)$winner_user_id);
            if ($u && $u->user_email) {
                $subject_w = __('Congratulations! You won the Bonus Hunt', 'bonus-hunt-guesser');
                $body_w = sprintf(
                    "%s\n\n%s: %s\n%s: %s\n",
                    __('You had the closest guess. Great job!', 'bonus-hunt-guesser'),
                    __('Hunt', 'bonus-hunt-guesser'),
                    $title,
                    __('Final Balance', 'bonus-hunt-guesser'),
                    number_format_i18n($final_balance, 2)
                );
                wp_mail($u->user_email, $subject_w, $body_w);
            }
        }
    }

    /* =======================
     * Translations
     * ======================= */
    public function save_translation() {
        $this->require_caps();
        if (!isset($_POST['bhg_save_translation_nonce']) || !wp_verify_nonce($_POST['bhg_save_translation_nonce'], 'bhg_save_translation')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }
        global $wpdb;
        $table = $this->table('bhg_translations');

        $key = sanitize_key($_POST['t_key'] ?? '');
        $val = wp_kses_post($_POST['t_value'] ?? '');
        if (!$key) {
            wp_safe_redirect(admin_url('admin.php?page=bhg-translations&error=1'));
            exit;
        }
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE `key`=%s", $key));
        if ($exists) {
            $wpdb->update($table, ['value' => $val], ['id' => (int)$exists], ['%s'], ['%d']);
        } else {
            $wpdb->insert($table, ['key' => $key, 'value' => $val], ['%s','%s']);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-translations&updated=1'));
        exit;
    }

    /* =======================
     * Affiliates
     * ======================= */
    public function save_affiliate() {
        $this->require_caps();
        if (!isset($_POST['bhg_save_affiliate_nonce']) || !wp_verify_nonce($_POST['bhg_save_affiliate_nonce'], 'bhg_save_affiliate')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $name  = sanitize_text_field($_POST['name'] ?? '');
        $slug  = function_exists('bhg_slugify') ? bhg_slugify($_POST['slug'] ?? $name) : sanitize_title($_POST['slug'] ?? $name);
        $url   = esc_url_raw($_POST['url'] ?? '');

        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $wpdb->update($table, ['name'=>$name,'slug'=>$slug,'url'=>$url], ['id'=>$id], ['%s','%s','%s'], ['%d']);
        } else {
            $wpdb->insert($table, ['name'=>$name,'slug'=>$slug,'url'=>$url], ['%s','%s','%s']);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&updated=1'));
        exit;
    }

    public function delete_affiliate() {
        $this->require_caps();
        if (!isset($_POST['bhg_delete_affiliate_nonce']) || !wp_verify_nonce($_POST['bhg_delete_affiliate_nonce'], 'bhg_delete_affiliate')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }
        global $wpdb;
        $table = $this->table('bhg_affiliate_websites');
        $id    = (int)($_POST['id'] ?? 0);
        if ($id) $wpdb->delete($table, ['id' => $id], ['%d']);
        wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates&deleted=1'));
        exit;
    }

    /* =======================
     * Advertising
     * ======================= */
    public function save_ad() {
        $this->require_caps();
        if (!isset($_POST['bhg_save_ad_nonce']) || !wp_verify_nonce($_POST['bhg_save_ad_nonce'], 'bhg_save_ad')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }

        global $wpdb;
        $table = $this->table('bhg_ads');

        // As requested: message is HTML, no separate link field.
        $data = [
            'message'      => wp_kses_post($_POST['message'] ?? ''),
            'placement'    => sanitize_text_field($_POST['placement'] ?? 'footer'),
            'visibility'   => sanitize_text_field($_POST['visibility'] ?? 'all'),
            'target_pages' => sanitize_text_field($_POST['target_pages'] ?? ''),
            'active'       => isset($_POST['active']) ? 1 : 0,
        ];
        $format = ['%s','%s','%s','%s','%d'];

        if (!empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            $wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);
        } else {
            $wpdb->insert($table, $data, $format);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-ads&updated=1'));
        exit;
    }

    /* =======================
     * Tools (Demo reset)
     * ======================= */
    public function bhg_tools_page() {
        $this->require_caps();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('BHG Tools', 'bonus-hunt-guesser'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bhg_reset_demo">
                <?php wp_nonce_field('bhg_reset_demo','bhg_nonce'); ?>
                <p><?php echo esc_html__('Reset and reinsert demo data. This will wipe current demo rows in hunts, guesses, tournaments, winners and ads tables.','bonus-hunt-guesser'); ?></p>
                <p><button type="submit" class="button button-primary"><?php echo esc_html__('Reset Demo Data', 'bonus-hunt-guesser'); ?></button></p>
            </form>
        </div>
        <?php
    }

    public function handle_reset_demo() {
        $this->require_caps();
        if (empty($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_reset_demo')) {
            wp_die(esc_html__('Invalid request', 'bonus-hunt-guesser'));
        }
        if (function_exists('bhg_reset_demo_and_seed')) {
            bhg_reset_demo_and_seed();
            update_option('bhg_demo_notice', 1);
        }
        wp_safe_redirect(admin_url('admin.php?page=bhg-tools&reset=1'));
        exit;
    }

    /* =======================
     * Tournaments – ensure & tally
     * ======================= */
    public function ensure_current_tournaments() {
        global $wpdb;
        $table = $this->table('bhg_tournaments');

        $compute = function($type) {
            $now = current_time('timestamp');
            if ($type === 'weekly') {
                $y = (int) date('o', $now);
                $W = (int) date('W', $now);
                $dto = new DateTime();
                $dto->setISODate($y, $W);
                $start = $dto->format('Y-m-d');
                $dto->modify('+6 days');
                $end   = $dto->format('Y-m-d');
                return ['period'=> sprintf('%04d-%02d', $y, $W), 'start'=>$start, 'end'=>$end];
            } elseif ($type === 'monthly') {
                $y = (int) date('Y', $now);
                $m = (int) date('m', $now);
                $start = sprintf('%04d-%02d-01', $y, $m);
                $end   = date('Y-m-t', $now);
                return ['period'=> sprintf('%04d-%02d', $y, $m), 'start'=>$start, 'end'=>$end];
            }
            $y = (int) date('Y', $now);
            return ['period'=> (string)$y, 'start'=> sprintf('%04d-01-01', $y), 'end'=> sprintf('%04d-12-31', $y)];
        };

        foreach (['weekly','monthly','yearly'] as $t) {
            $d = $compute($t);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE type=%s AND period=%s", $t, $d['period']));
            if (!$exists) {
                $wpdb->insert($table, [
                    'type'       => $t,
                    'period'     => $d['period'],
                    'start_date' => $d['start'],
                    'end_date'   => $d['end'],
                    'status'     => 'active',
                    'created_at' => current_time('mysql', 1),
                    'updated_at' => current_time('mysql', 1),
                ], ['%s','%s','%s','%s','%s','%s','%s']);
            } else {
                $wpdb->update($table, [
                    'start_date' => $d['start'],
                    'end_date'   => $d['end'],
                    'updated_at' => current_time('mysql', 1),
                ], ['id' => (int)$exists], ['%s','%s','%s'], ['%d']);
            }
        }
    }

    private function update_tournament_results($hunt_id, $winner_user_id, $closed_at = null) {
        if (!$winner_user_id) return;

        global $wpdb;
        $hunts = $this->table('bhg_bonus_hunts');

        if ($closed_at) {
            $ts = strtotime($closed_at);
        } else {
            $closed_at = $wpdb->get_var($wpdb->prepare("SELECT closed_at FROM `$hunts` WHERE id=%d", $hunt_id));
            $ts = $closed_at ? strtotime($closed_at) : time();
        }

        $isoYear  = date('o', $ts);
        $week     = str_pad(date('W', $ts), 2, '0', STR_PAD_LEFT);
        $weekKey  = $isoYear . '-' . $week;
        $monthKey = date('Y-m', $ts);
        $yearKey  = date('Y',   $ts);

        $tournaments = $this->table('bhg_tournaments');
        $results     = $this->table('bhg_tournament_results');

        $ensure = function($type, $period, $title = null) use ($wpdb, $tournaments) {
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$tournaments` WHERE type=%s AND period=%s", $type, $period));
            if ($id) return (int)$id;
            $wpdb->insert($tournaments, [
                'type' => $type, 'period' => $period,
                'start_date' => current_time('mysql', 1),
                'end_date'   => current_time('mysql', 1),
                'status'     => 'active',
                'created_at' => current_time('mysql', 1),
                'updated_at' => current_time('mysql', 1),
            ], ['%s','%s','%s','%s','%s','%s','%s']);
            return (int)$wpdb->insert_id;
        };

        $weekly_id  = $ensure('weekly',  $weekKey);
        $monthly_id = $ensure('monthly', $monthKey);
        $yearly_id  = $ensure('yearly',  $yearKey);

        foreach ([$weekly_id, $monthly_id, $yearly_id] as $tid) {
            if ($tid > 0) {
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO `$results` (tournament_id, user_id, wins) VALUES (%d,%d,1)
                     ON DUPLICATE KEY UPDATE wins = wins + 1",
                    $tid, (int)$winner_user_id
                ));
            }
        }
    }

    /* =======================
     * Frontend guess submit (admin-post)
     * ======================= */
    public function submit_guess() {
        if (!is_user_logged_in()) wp_die(esc_html__('You must be logged in', 'bonus-hunt-guesser'));
        if (empty($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_submit_guess')) wp_die(esc_html__('Invalid nonce', 'bonus-hunt-guesser'));

        $hunt_id   = isset($_POST['hunt_id']) ? (int)$_POST['hunt_id'] : 0;
        $guess_raw = $_POST['guess_value'] ?? ($_POST['guess'] ?? '');
        $guess_num = is_numeric($guess_raw) ? (float)$guess_raw : null;

        // min/max from settings
        $settings = is_callable(['BHG_Utils','get_settings']) ? BHG_Utils::get_settings() : get_option('bhg_plugin_settings', []);
        $min = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0.0;
        $max = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000.0;

        if ($guess_num === null || $guess_num < $min || $guess_num > $max) wp_die(esc_html__('Invalid guess amount.', 'bonus-hunt-guesser'));

        global $wpdb;
        $hunt = $wpdb->get_row($wpdb->prepare("SELECT status FROM ".$this->table('bhg_bonus_hunts')." WHERE id=%d", $hunt_id));
        if (!$hunt || strtolower($hunt->status) !== 'open') wp_die(esc_html__('This bonus hunt is closed for guesses.', 'bonus-hunt-guesser'));

        $uid   = get_current_user_id();
        $table = $this->table('bhg_guesses');
        $id    = $wpdb->get_var($wpdb->prepare("SELECT id FROM `$table` WHERE user_id=%d AND hunt_id=%d", $uid, $hunt_id));

        if ($id) {
            $wpdb->update($table, ['guess_value'=>$guess_num, 'updated_at'=>current_time('mysql', 1)], ['id'=>$id], ['%f','%s'], ['%d']);
        } else {
            $wpdb->insert($table, ['user_id'=>$uid,'hunt_id'=>$hunt_id,'guess_value'=>$guess_num,'created_at'=>current_time('mysql', 1),'updated_at'=>current_time('mysql', 1)], ['%d','%d','%f','%s','%s']);
        }

        $redirect_url = wp_get_referer() ?: home_url('/');
        wp_safe_redirect($redirect_url);
        exit;
    }

    /* =======================
     * Tournaments CRUD handlers
     * ======================= */
    public function handle_save_tournament() {
        $this->require_caps();
        if (!isset($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_save_tournament')) wp_die(esc_html__('Bad nonce', 'bonus-hunt-guesser'));

        global $wpdb; $t = $this->table('bhg_tournaments');
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $data = [
            'type'       => sanitize_text_field($_POST['type'] ?? ''),
            'period'     => sanitize_text_field($_POST['period'] ?? ''),
            'start_date' => sanitize_text_field($_POST['start_date'] ?? ''),
            'end_date'   => sanitize_text_field($_POST['end_date'] ?? ''),
            'status'     => sanitize_text_field($_POST['status'] ?? 'active'),
        ];
        if ($id > 0) { $wpdb->update($t, $data, ['id'=>$id]); }
        else         { $wpdb->insert($t, $data); }

        wp_safe_redirect(admin_url('admin.php?page=bhg-tournaments&updated=1'));
        exit;
    }

    public function handle_delete_tournament() {
        $this->require_caps();
        if (!isset($_GET['bhg_nonce']) || !wp_verify_nonce($_GET['bhg_nonce'], 'bhg_delete_tournament')) wp_die(esc_html__('Bad nonce', 'bonus-hunt-guesser'));
        global $wpdb; $t = $this->table('bhg_tournaments');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) $wpdb->delete($t, ['id'=>$id], ['%d']);
        wp_safe_redirect(admin_url('admin.php?page=bhg-tournaments&deleted=1'));
        exit;
    }
}
