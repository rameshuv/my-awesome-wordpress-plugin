<?php
if (!defined('ABSPATH')) exit;

class BHG_Shortcodes {

    public function __construct() {
        add_shortcode('bhg_user_profile', [$this, 'user_profile']);
        add_shortcode('bhg_tournaments', [$this, 'tournaments']);
        add_shortcode('bhg_user_guesses', [$this, 'user_guesses']);
        add_shortcode('bhg_winner_notifications', [$this, 'winner_notifications']);
        add_shortcode('bhg_active_hunt', [$this, 'active_hunt']);
        add_shortcode('bhg_guess_form', [$this, 'guess_form']);
        add_shortcode('bhg_leaderboard', [$this, 'leaderboard']);
        add_shortcode('bonus_hunt_display', array($this, 'bonus_hunt_display'));
        add_shortcode('bonus_hunt_leaderboard', array($this, 'bonus_hunt_leaderboard'));
        add_shortcode('bonus_hunt_guess_form', array($this, 'bonus_hunt_guess_form'));
        
        // AJAX handlers
        add_action('wp_ajax_bhg_submit_guess', array($this, 'ajax_submit_guess'));
        add_action('wp_ajax_nopriv_bhg_submit_guess', array($this, 'ajax_no_privileges'));
    }

    /** Setup: create tables if missing and seed demo if tables empty */
    private function maybe_setup_demo(){
        global $wpdb;

        static $done = false;
        if ($done) return;
        $done = true;

        $prefix = $wpdb->prefix;
        $charset = $wpdb->get_charset_collate();

        // Create tables (MySQL 5.5.5 compatible)
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$prefix}bhg_bonus_hunts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            starting_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            num_bonuses INT UNSIGNED NOT NULL DEFAULT 0,
            prizes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            affiliate_site_id BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            final_balance DECIMAL(12,2) NULL,
            winner_user_id BIGINT UNSIGNED NULL,
            winner_diff DECIMAL(12,2) NULL,
            closed_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset");

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$prefix}bhg_guesses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            guess_amount DECIMAL(12,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            UNIQUE KEY hunt_user (hunt_id, user_id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id),
            PRIMARY KEY (id)
        ) $charset");

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$prefix}bhg_tournaments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            period VARCHAR(20) NOT NULL,
            period_key VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY period_unique (period, period_key)
        ) $charset");

        // Seed demo data only if tables are empty
        $hunts_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}bhg_bonus_hunts");
        if ($hunts_count === 0){
            $wpdb->insert("{$prefix}bhg_bonus_hunts", array(
                'title' => 'Demo Hunt 1',
                'starting_balance' => 1000.00,
                'num_bonuses' => 10,
                'prizes' => 'Demo Prizes: Gift Card, Merch',
                'status' => 'open',
                'created_at' => current_time('mysql')
            ));
        }

        $t_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}bhg_tournaments");
        if ($t_count === 0){
            $wpdb->insert("{$prefix}bhg_tournaments", array(
                'title' => 'Demo Tournament',
                'period' => 'monthly',
                'period_key' => date('Y-m'),
                'status' => 'active',
                'created_at' => current_time('mysql')
            ));
        }

        // Seed one demo guess for the active hunt if none
        $hunt = $wpdb->get_row("SELECT * FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        if ($hunt){
            $gcount = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$prefix}bhg_guesses WHERE hunt_id=%d", $hunt->id));
            if ($gcount === 0){
                $user_id = get_current_user_id();
                if (!$user_id){
                    $admin = get_user_by('id', 1);
                    if ($admin) $user_id = 1;
                }
                if ($user_id){
                    $wpdb->insert("{$prefix}bhg_guesses", array(
                        'hunt_id' => (int)$hunt->id,
                        'user_id' => (int)$user_id,
                        'guess_amount' => 5000.00,
                        'created_at' => current_time('mysql')
                    ), array('%d','%d','%f','%s'));
                }
            }
        }
    }

    public function active_hunt(){
        $this->maybe_setup_demo();
        global $wpdb;
        $prefix = $wpdb->prefix;
        $hunt = $wpdb->get_row("SELECT * FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        if (!$hunt){
            return '<div class="bhg-active-hunt"><p>' . esc_html__('No active bonus hunt at the moment.', 'bonus-hunt-guesser') . '</p></div>';
        }
        $title = esc_html($hunt->title);
        $start = number_format_i18n((float)$hunt->starting_balance, 2);
        $bonuses = (int)$hunt->num_bonuses;
        $prizes = $hunt->prizes ? wp_kses_post($hunt->prizes) : '';

        ob_start(); ?>
        <div class="bhg-active-hunt">
            <h3><?php echo $title; ?></h3>
            <p><?php 
                printf(
                    esc_html__('Starting Balance: €%s | Bonuses: %d', 'bonus-hunt-guesser'), 
                    esc_html($start), 
                    esc_html($bonuses)
                ); 
            ?></p>
            <?php if ($prizes): ?>
                <div class="bhg-prizes">
                    <strong><?php esc_html_e('Prizes:', 'bonus-hunt-guesser'); ?></strong>
                    <div><?php echo $prizes; ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function guess_form(){
        $this->maybe_setup_demo();
        global $wpdb;
        $prefix = $wpdb->prefix;
        $hunt = $wpdb->get_row("SELECT * FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        if (!$hunt){
            return '<div class="bhg-guess-form"><p>' . esc_html__('No active hunt to guess on right now.', 'bonus-hunt-guesser') . '</p></div>';
        }

        // Handle submit inline
        $notices = '';
        if (!empty($_POST['bhg_sc_action']) && $_POST['bhg_sc_action']==='submit_guess'){
            if (isset($_POST['bhg_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bhg_nonce'])), 'bhg_sc_guess')){
                if (is_user_logged_in()){
                    $uid = get_current_user_id();
                    $val = isset($_POST['bhg_guess_value']) ? floatval($_POST['bhg_guess_value']) : 0;
                    if ($val > 0){
                        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$prefix}bhg_guesses WHERE hunt_id=%d AND user_id=%d", (int)$hunt->id, (int)$uid));
                        if ($exists){
                            $wpdb->update("{$prefix}bhg_guesses",
                                array('guess_amount'=>$val, 'updated_at'=>current_time('mysql')),
                                array('id'=>(int)$exists),
                                array('%f','%s'),
                                array('%d')
                            );
                        } else {
                            $wpdb->insert("{$prefix}bhg_guesses",
                                array('hunt_id'=>(int)$hunt->id, 'user_id'=>(int)$uid, 'guess_amount'=>$val, 'created_at'=>current_time('mysql')),
                                array('%d','%d','%f','%s')
                            );
                        }
                        $notices = '<div class="bhg-notice">' . esc_html__('Guess saved!', 'bonus-hunt-guesser') . '</div>';
                    } else {
                        $notices = '<div class="bhg-error">' . esc_html__('Please enter a valid number.', 'bonus-hunt-guesser') . '</div>';
                    }
                } else {
                    $notices = '<div class="bhg-error">' . esc_html__('You must be logged in to submit a guess.', 'bonus-hunt-guesser') . '</div>';
                }
            } else {
                $notices = '<div class="bhg-error">' . esc_html__('Security check failed. Please reload and try again.', 'bonus-hunt-guesser') . '</div>';
            }
        }

        if (!is_user_logged_in()){
            $login_url = wp_login_url( get_permalink() );
            return '<div class="bhg-guess-form"><p>' . 
                sprintf(
                    esc_html__('Please %s to submit a guess.', 'bonus-hunt-guesser'), 
                    '<a href="' . esc_url($login_url) . '">' . esc_html__('log in','bonus-hunt-guesser') . '</a>'
                ) . 
            '</p></div>';
        }

        ob_start(); 
        echo wp_kses_post($notices);
        ?>
        <div class="bhg-guess-form">
            <form method="post">
                <input type="hidden" name="bhg_sc_action" value="submit_guess">
                <?php wp_nonce_field('bhg_sc_guess','bhg_nonce'); ?>
                <label for="bhg_guess_value"><?php esc_html_e('Your Guess (0-100,000)', 'bonus-hunt-guesser'); ?></label>
                <input type="number" id="bhg_guess_value" name="bhg_guess_value" min="0" max="100000" step="0.01" required>
                <button type="submit"><?php esc_html_e('Submit Guess', 'bonus-hunt-guesser'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function user_guesses(){
        $this->maybe_setup_demo();
        global $wpdb;
        $prefix = $wpdb->prefix;
        $hunt = $wpdb->get_row("SELECT id FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        if (!$hunt) return '<p>' . esc_html__('No active hunt.', 'bonus-hunt-guesser') . '</p>';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT g.guess_amount, g.created_at, u.user_login 
             FROM {$prefix}bhg_guesses g 
             JOIN {$wpdb->users} u ON g.user_id=u.ID 
             WHERE g.hunt_id=%d 
             ORDER BY g.created_at DESC
             LIMIT 100",
             (int)$hunt->id
        ));

        if (!$rows) return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';

        ob_start(); ?>
        <div class="bhg-user-guesses">
            <table class="bhg-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Guess', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Submitted', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo esc_html($r->user_login); ?></td>
                        <td>€<?php echo esc_html(number_format_i18n((float)$r->guess_amount, 2)); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format').' '.get_option('time_format'), $r->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public function tournaments(){
        $this->maybe_setup_demo();
        global $wpdb;
        $prefix = $wpdb->prefix;
        $rows = $wpdb->get_results("SELECT * FROM {$prefix}bhg_tournaments ORDER BY created_at DESC LIMIT 20");
        if (!$rows) return '<p>' . esc_html__('No tournaments yet.', 'bonus-hunt-guesser') . '</p>';
        ob_start(); ?>
        <div class="bhg-tournaments">
            <ul class="bhg-list">
                <?php foreach ($rows as $t): ?>
                    <li>
                        <strong><?php echo esc_html($t->title); ?></strong>
                        — <?php echo esc_html($t->period . ' ' . $t->period_key); ?>
                        (<?php echo esc_html($t->status); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    public function winner_notifications(){
        global $wpdb;
        $prefix = $wpdb->prefix;
        $rows = $wpdb->get_results("
            SELECT h.title, h.final_balance, h.closed_at, u.user_login AS winner_name
            FROM {$prefix}bhg_bonus_hunts h
            LEFT JOIN {$wpdb->users} u ON u.ID = h.winner_user_id
            WHERE h.status='closed'
            ORDER BY h.closed_at DESC
            LIMIT 3
        ");
        if (!$rows){
            return '<p>' . esc_html__('Winners will be shown here once hunts are closed.', 'bonus-hunt-guesser') . '</p>';
        }
        ob_start(); ?>
        <div class="bhg-winner-notifications">
            <h3><?php esc_html_e('Recent Winners', 'bonus-hunt-guesser'); ?></h3>
            <ul class="bhg-list">
                <?php foreach ($rows as $r): ?>
                    <li>
                        <strong><?php echo esc_html($r->title); ?></strong> — 
                        <?php esc_html_e('Final Balance', 'bonus-hunt-guesser'); ?>: €<?php echo esc_html(number_format_i18n((float)$r->final_balance, 2)); ?> — 
                        <?php esc_html_e('Winner', 'bonus-hunt-guesser'); ?>: <?php echo $r->winner_name ? esc_html($r->winner_name) : esc_html__('TBD','bonus-hunt-guesser'); ?> 
                        (<?php echo esc_html(mysql2date(get_option('date_format'), $r->closed_at)); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    public function user_profile(){
        $this->maybe_setup_demo();
        ob_start();
        echo '<div class="bhg-user-profile">';
        echo '<h3>' . esc_html__("User Profile", "bonus-hunt-guesser") . '</h3>';
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            echo '<p><strong>' . esc_html($user->display_name) . '</strong></p>';
            echo '<p>' . esc_html($user->user_email) . '</p>';
        } else {
            echo '<p>' . esc_html__("You need to log in to view your profile.", "bonus-hunt-guesser") . '</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function leaderboard(){
        $this->maybe_setup_demo();
        global $wpdb;
        $prefix = $wpdb->prefix;

        // Ensure jQuery is available (WP's bundled)
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('jquery');
        }

        // Fetch closed hunts with a final balance
        $closed = $wpdb->get_results("SELECT id, final_balance FROM {$prefix}bhg_bonus_hunts WHERE status='closed' AND final_balance IS NOT NULL ORDER BY closed_at DESC LIMIT 200");
        $user_stats = []; // user_id => ['login'=>..., 'wins'=>0, 'total_rank'=>0, 'participations'=>0]

        foreach ((array)$closed as $h){
            $final = (float)$h->final_balance;
            // Guesses for this hunt
            $guesses = $wpdb->get_results($wpdb->prepare("
                SELECT g.user_id, g.guess_amount, u.user_login
                FROM {$prefix}bhg_guesses g
                JOIN {$wpdb->users} u ON u.ID = g.user_id
                WHERE g.hunt_id = %d
            ", (int)$h->id));
            if (!$guesses) continue;

            // Rank by absolute difference
            $rows = [];
            foreach ($guesses as $g){
                $rows[] = [
                    'user_id' => (int)$g->user_id,
                    'login' => $g->user_login,
                    'diff' => abs((float)$g->guess_amount - $final),
                ];
            }
            usort($rows, function($a,$b){
                if ($a['diff'] == $b['diff']) return 0;
                return ($a['diff'] < $b['diff']) ? -1 : 1;
            });

            // assign ranks and accumulate
            $rank = 1;
            foreach ($rows as $r){
                $uid = $r['user_id'];
                if (!isset($user_stats[$uid])){
                    $user_stats[$uid] = ['login'=>$r['login'],'wins'=>0,'total_rank'=>0,'participations'=>0];
                }
                $user_stats[$uid]['participations'] += 1;
                $user_stats[$uid]['total_rank'] += $rank;
                if ($rank === 1) $user_stats[$uid]['wins'] += 1;
                $rank++;
            }
        }

        if (empty($user_stats)){
            return '<div class="bhg-leaderboard"><p>' . esc_html__('No finished hunts yet. Leaderboard will appear after the first hunt is closed.', 'bonus-hunt-guesser') . '</p></div>';
        }

        $final = [];
        foreach ($user_stats as $uid => $s){
            $avg = $s['participations'] > 0 ? ($s['total_rank'] / $s['participations']) : 9999;
            $final[] = [
                'login' => $s['login'],
                'avg' => $avg,
                'wins' => $s['wins'],
            ];
        }
        usort($final, function($a,$b){
            if ($a['wins'] == $b['wins']){
                if ($a['avg'] == $b['avg']) return 0;
                return ($a['avg'] < $b['avg']) ? -1 : 1;
            }
            return ($a['wins'] > $b['wins']) ? -1 : 1;
        });

        ob_start(); ?>
        <div class="bhg-leaderboard">
            <h3><?php esc_html_e('Leaderboard', 'bonus-hunt-guesser'); ?></h3>

            <!-- DataTables assets -->
            <link rel="stylesheet" href="<?php echo esc_url('https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css'); ?>" />
            <script src="<?php echo esc_url('https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js'); ?>"></script>

            <table id="bhg-leaderboard-table" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Pos', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('User', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Average Ranking', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Times Won', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pos = 1; foreach ($final as $row): ?>
                        <tr>
                            <td><?php echo (int)$pos++; ?></td>
                            <td><?php echo esc_html($row['login']); ?></td>
                            <td><?php echo esc_html(number_format_i18n($row['avg'], 2)); ?></td>
                            <td><?php echo (int)$row['wins']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <script>
            (function($){
                $(function(){
                    if ($.fn.DataTable) {
                        $('#bhg-leaderboard-table').DataTable({
                            pageLength: 10
                        });
                    }
                });
            })(jQuery);
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    // New shortcode methods from user request
    public function bonus_hunt_display($atts) {
        $this->maybe_setup_demo();
        global $wpdb;
        $hunt = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status = 'open' ORDER BY created_at DESC LIMIT 1");
        
        if (!$hunt) {
            return '<div class="bhg-container">' . esc_html__('No active bonus hunt found.', 'bonus-hunt-guesser') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="bhg-container">
            <div class="bhg-hunt-details">
                <h2><?php echo esc_html($hunt->title); ?></h2>
                <div class="bhg-detail-row">
                    <span class="bhg-label"><?php esc_html_e('Starting Balance:', 'bonus-hunt-guesser'); ?></span>
                    <span class="bhg-value">€<?php echo esc_html(number_format($hunt->starting_balance, 2)); ?></span>
                </div>
                <div class="bhg-detail-row">
                    <span class="bhg-label"><?php esc_html_e('Number of Bonuses:', 'bonus-hunt-guesser'); ?></span>
                    <span class="bhg-value"><?php echo esc_html($hunt->num_bonuses); ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function bonus_hunt_leaderboard($atts) {
        $this->maybe_setup_demo();
        global $wpdb;
        $hunt = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status = 'open' ORDER BY created_at DESC LIMIT 1");
        
        if (!$hunt) {
            return '<div class="bhg-container">' . esc_html__('No active bonus hunt found.', 'bonus-hunt-guesser') . '</div>';
        }
        
        $guesses = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login, u.display_name, 
             (SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'bhg_affiliate_status' AND meta_value = '1') as is_affiliate
             FROM {$wpdb->prefix}bhg_guesses g
             JOIN {$wpdb->users} u ON g.user_id = u.ID
             WHERE g.hunt_id = %d
             ORDER BY g.guess_amount ASC",
             $hunt->id
        ));
        
        ob_start();
        ?>
        <div class="bhg-container">
            <h2><?php esc_html_e('Leaderboard', 'bonus-hunt-guesser'); ?></h2>
            <table class="bhg-leaderboard-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Position', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Username', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Guess', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Affiliate', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guesses as $index => $guess): ?>
                    <tr>
                        <td><?php echo (int)($index + 1); ?></td>
                        <td><?php echo esc_html($guess->display_name ?: $guess->user_login); ?></td>
                        <td>€<?php echo esc_html(number_format($guess->guess_amount, 2)); ?></td>
                        <td>
                            <span class="bhg-affiliate-status <?php echo $guess->is_affiliate ? 'affiliate-yes' : 'affiliate-no'; ?>">
                                ●
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function bonus_hunt_guess_form($atts) {
        if (!is_user_logged_in()) {
            return '<div class="bhg-container">' . 
                   esc_html__('Please log in to submit your guess.', 'bonus-hunt-guesser') . 
                   '</div>';
        }
        
        $this->maybe_setup_demo();
        global $wpdb;
        $hunt = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status = 'open' ORDER BY created_at DESC LIMIT 1");
        $user_id = get_current_user_id();
        
        if (!$hunt) {
            return '<div class="bhg-container">' . esc_html__('No active bonus hunt found.', 'bonus-hunt-guesser') . '</div>';
        }
        
        $existing_guess = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_guesses WHERE hunt_id = %d AND user_id = %d",
            $hunt->id, $user_id
        ));
        
        // Enqueue necessary scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('bhg-public-js', BHG_PLUGIN_URL . 'assets/js/public.js', array('jquery'), BHG_VERSION, true);
        wp_localize_script('bhg-public-js', 'bhg_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bhg_nonce'),
            'update_text' => esc_html__('Update Guess', 'bonus-hunt-guesser')
        ));
        
        ob_start();
        ?>
        <div class="bhg-container">
            <h2><?php esc_html_e('Submit Your Guess', 'bonus-hunt-guesser'); ?></h2>
            <form id="bhg-guess-form" class="bhg-form">
                <input type="hidden" name="hunt_id" value="<?php echo (int)$hunt->id; ?>">
                <?php wp_nonce_field('bhg_guess_nonce', 'bhg_nonce'); ?>
                
                <div class="bhg-form-group">
                    <label for="bhg-guess-value"><?php esc_html_e('Your Guess for Final Balance (€):', 'bonus-hunt-guesser'); ?></label>
                    <input type="number" id="bhg-guess-value" name="guess_value" 
                           min="0" max="100000" step="0.01" required
                           value="<?php echo $existing_guess ? esc_attr(number_format($existing_guess->guess_amount, 2)) : ''; ?>">
                </div>
                
                <button type="submit" class="bhg-submit-button">
                    <?php echo $existing_guess ? esc_html__('Update Guess', 'bonus-hunt-guesser') : esc_html__('Submit Guess', 'bonus-hunt-guesser'); ?>
                </button>
                
                <div id="bhg-message" class="bhg-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // AJAX handlers for guess submission
    public function ajax_submit_guess() {
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bhg_nonce')) {
            wp_die(esc_html__('Security check failed', 'bonus-hunt-guesser'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(esc_html__('You must be logged in to submit a guess', 'bonus-hunt-guesser'));
        }
        
        // Validate inputs
        $hunt_id = isset($_POST['hunt_id']) ? intval($_POST['hunt_id']) : 0;
        $guess_value = isset($_POST['guess_value']) ? floatval($_POST['guess_value']) : 0;
        
        if ($guess_value < 0 || $guess_value > 100000) {
            wp_send_json_error(esc_html__('Guess must be between €0 and €100,000', 'bonus-hunt-guesser'));
        }
        
        // Save the guess
        global $wpdb;
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bhg_guesses WHERE hunt_id = %d AND user_id = %d",
            $hunt_id, get_current_user_id()
        ));
        
        if ($existing) {
            $result = $wpdb->update(
                "{$wpdb->prefix}bhg_guesses",
                array('guess_amount' => $guess_value, 'updated_at' => current_time('mysql')),
                array('id' => $existing->id),
                array('%f', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                "{$wpdb->prefix}bhg_guesses",
                array(
                    'hunt_id' => $hunt_id,
                    'user_id' => get_current_user_id(),
                    'guess_amount' => $guess_value,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%f', '%s')
            );
        }
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => esc_html__('Your guess has been saved successfully!', 'bonus-hunt-guesser')
            ));
        } else {
            wp_send_json_error(esc_html__('Failed to save your guess. Please try again.', 'bonus-hunt-guesser'));
        }
    }
    
    public function ajax_no_privileges() {
        wp_send_json_error(esc_html__('You must be logged in to perform this action', 'bonus-hunt-guesser'));
    }
}

new BHG_Shortcodes();