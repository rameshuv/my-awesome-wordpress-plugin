<?php
/**
 * Shortcodes for Bonus Hunt Guesser plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class BHG_Shortcodes {
    public function __construct() {
        // Register all shortcodes
        add_shortcode('bhg_leaderboard', array($this, 'leaderboard_shortcode'));
        add_shortcode('bhg_bonus_hunt', array($this, 'bonus_hunt_shortcode'));
        add_shortcode('bhg_guess_form', array($this, 'guess_form_shortcode'));
        add_shortcode('bhg_tournaments', array($this, 'tournaments_shortcode'));
        add_shortcode('bhg_user_profile', array($this, 'user_profile'));
        add_shortcode('bhg_user_guesses', array($this, 'user_guesses'));
        add_shortcode('bhg_winner_notifications', array($this, 'winner_notifications'));
        add_shortcode('bhg_active_hunt', array($this, 'active_hunt'));
        add_shortcode('bonus_hunt_display', array($this, 'bonus_hunt_display'));
        add_shortcode('bonus_hunt_leaderboard', array($this, 'bonus_hunt_leaderboard'));
        add_shortcode('bonus_hunt_guess_form', array($this, 'bonus_hunt_guess_form'));
        
        // AJAX handlers
        add_action('wp_ajax_bhg_submit_guess', array($this, 'ajax_submit_guess'));
        add_action('wp_ajax_nopriv_bhg_submit_guess', array($this, 'ajax_no_privileges'));
    }

    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'timeframe' => 'all',
            'limit' => 20
        ), $atts);

        ob_start();
        $this->render_leaderboard($atts['timeframe'], $atts['limit']);
        return ob_get_clean();
    }

    public function bonus_hunt_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'status' => 'active'
        ), $atts);

        ob_start();
        $this->render_bonus_hunt($atts['id'], $atts['status']);
        return ob_get_clean();
    }

    public function guess_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'hunt_id' => 0
        ), $atts);

        ob_start();
        $this->render_guess_form($atts['hunt_id']);
        return ob_get_clean();
    }

    public function tournaments_shortcode($atts) {
        $atts = shortcode_atts(array(
            'period' => 'monthly'
        ), $atts);

        ob_start();
        $this->render_tournaments($atts['period']);
        return ob_get_clean();
    }

    private function render_leaderboard($timeframe, $limit) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bhg_guesses';
        $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
        $users_table = $wpdb->prefix . 'users';
        
        // Get active bonus hunt
        $active_hunt = $wpdb->get_row(
            "SELECT * FROM $hunts_table WHERE status = 'active' ORDER BY created_at DESC LIMIT 1"
        );
        
        if (!$active_hunt) {
            return '<div class="bhg-leaderboard"><p>' . esc_html__('No active bonus hunt found.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        // Get guesses for active hunt
        $guesses = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login, u.display_name, 
                    um.meta_value as affiliate_status
             FROM $table_name g
             LEFT JOIN $users_table u ON g.user_id = u.ID
             LEFT JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key = 'bhg_affiliate_status'
             WHERE g.hunt_id = %d
             ORDER BY g.guess ASC
             LIMIT %d",
            $active_hunt->id, $limit
        ));
        
        if (!$guesses) {
            return '<div class="bhg-leaderboard"><p>' . esc_html__('No guesses yet for this bonus hunt.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        ob_start();
        echo '<div class="bhg-leaderboard">';
        echo '<h3>' . esc_html($active_hunt->title) . ' ' . esc_html__('Leaderboard', 'bonus-hunt-guesser') . '</h3>';
        echo '<table class="bhg-leaderboard-table">';
        echo '<thead><tr>
                <th>' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>
                <th>' . esc_html__('Username', 'bonus-hunt-guesser') . '</th>
                <th>' . esc_html__('Guess', 'bonus-hunt-guesser') . '</th>
                <th>' . esc_html__('Affiliate', 'bonus-hunt-guesser') . '</th>
              </tr></thead>';
        echo '<tbody>';
        
        $position = 1;
        foreach ($guesses as $guess) {
            $username = !empty($guess->display_name) ? $guess->display_name : $guess->user_login;
            $is_affiliate = !empty($guess->affiliate_status) && $guess->affiliate_status == 1;
            
            echo '<tr>
                    <td>' . (int)$position . '</td>
                    <td>' . esc_html($username) . '</td>
                    <td>' . esc_html(number_format($guess->guess, 2)) . '</td>
                    <td><span class="affiliate-status ' . ($is_affiliate ? 'affiliate-yes' : 'affiliate-no') . '"></span></td>
                  </tr>';
            $position++;
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        return ob_get_clean();
    }

    private function render_bonus_hunt($id, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bhg_bonus_hunts';
        
        if ($id > 0) {
            $hunt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ));
        } else {
            $hunt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT 1",
                $status
            ));
        }
        
        if (!$hunt) {
            return '<div class="bhg-bonus-hunt"><p>' . esc_html__('No bonus hunt found.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        ob_start();
        echo '<div class="bhg-bonus-hunt">';
        echo '<h3>' . esc_html($hunt->title) . '</h3>';
        echo '<div class="bhg-hunt-details">
                <p><strong>' . esc_html__('Starting Balance:', 'bonus-hunt-guesser') . '</strong> ' . esc_html(number_format($hunt->starting_balance, 2)) . '</p>
                <p><strong>' . esc_html__('Number of Bonuses:', 'bonus-hunt-guesser') . '</strong> ' . (int)$hunt->num_bonuses . '</p>
                <p><strong>' . esc_html__('Status:', 'bonus-hunt-guesser') . '</strong> ' . esc_html(ucfirst($hunt->status)) . '</p>';
        
        if ($hunt->final_balance) {
            echo '<p><strong>' . esc_html__('Final Balance:', 'bonus-hunt-guesser') . '</strong> ' . esc_html(number_format($hunt->final_balance, 2)) . '</p>';
        }
        
        if ($hunt->prizes) {
            echo '<p><strong>' . esc_html__('Prizes:', 'bonus-hunt-guesser') . '</strong> ' . nl2br(esc_html($hunt->prizes)) . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
        
        return ob_get_clean();
    }

    private function render_guess_form($hunt_id) {
        global $wpdb;
        
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(get_permalink());
            return '<div class="bhg-guess-form"><p>' . 
                sprintf(
                    esc_html__('You must be <a href="%s">logged in</a> to submit a guess.', 'bonus-hunt-guesser'),
                    esc_url($login_url)
                ) . '</p></div>';
        }
        
        $table_name = $wpdb->prefix . 'bhg_bonus_hunts';
        
        if ($hunt_id > 0) {
            $hunt = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $hunt_id
            ));
        } else {
            $hunt = $wpdb->get_row(
                "SELECT * FROM $table_name WHERE status = 'active' ORDER BY created_at DESC LIMIT 1"
            );
        }
        
        if (!$hunt || $hunt->status !== 'active') {
            return '<div class="bhg-guess-form"><p>' . esc_html__('No active bonus hunt available for guessing.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        $user_id = get_current_user_id();
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        
        $existing_guess = $wpdb->get_var($wpdb->prepare(
            "SELECT guess FROM $guesses_table WHERE user_id = %d AND hunt_id = %d",
            $user_id, $hunt->id
        ));
        
        $settings = get_option('bhg_plugin_settings', []);
        $min_guess = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0;
        $max_guess = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000;
        
        ob_start();
        echo '<div class="bhg-guess-form">';
        echo '<h3>' . esc_html__('Submit Your Guess', 'bonus-hunt-guesser') . '</h3>';
        echo '<p>' . sprintf(esc_html__('Guess the final balance for %s. Current starting balance: %s', 'bonus-hunt-guesser'), 
                esc_html($hunt->title), 
                esc_html(number_format($hunt->starting_balance, 2))) . '</p>';
        
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bhg_submit_guess">';
        echo '<input type="hidden" name="hunt_id" value="' . (int)$hunt->id . '">';
        wp_nonce_field('bhg_guess_nonce', '_wpnonce');
        
        echo '<div class="form-group">
                <label for="bhg_guess">' . esc_html__('Your Guess:', 'bonus-hunt-guesser') . '</label>
                <input type="number" id="bhg_guess" name="guess" value="' . ($existing_guess ? esc_attr($existing_guess) : '') . '" 
                       min="' . esc_attr($min_guess) . '" max="' . esc_attr($max_guess) . '" step="0.01" required>
              </div>';
        
        echo '<button type="submit" class="bhg-submit-guess">' . 
             ($existing_guess ? esc_html__('Update Guess', 'bonus-hunt-guesser') : esc_html__('Submit Guess', 'bonus-hunt-guesser')) . 
             '</button>';
        echo '</form>';
        echo '</div>';
        
        return ob_get_clean();
    }

    private function render_tournaments($period) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'bhg_tournaments';
        $tournaments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE period = %s ORDER BY created_at DESC LIMIT 10",
            $period
        ));
        
        if (!$tournaments) {
            return '<div class="bhg-tournaments"><p>' . esc_html__('No tournaments found.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        ob_start();
        echo '<div class="bhg-tournaments">';
        echo '<h3>' . esc_html(ucfirst($period)) . ' ' . esc_html__('Tournaments', 'bonus-hunt-guesser') . '</h3>';
        echo '<ul class="bhg-tournament-list">';
        
        foreach ($tournaments as $tournament) {
            echo '<li>
                    <h4>' . esc_html($tournament->title) . '</h4>
                    <p>' . esc_html__('Period Key:', 'bonus-hunt-guesser') . ' ' . esc_html($tournament->period_key) . '</p>
                    <p>' . esc_html__('Status:', 'bonus-hunt-guesser') . ' ' . esc_html($tournament->status) . '</p>
                  </li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        return ob_get_clean();
    }

    public function active_hunt() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $hunt = $wpdb->get_row("SELECT * FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        
        if (!$hunt) {
            return '<div class="bhg-active-hunt"><p>' . esc_html__('No active bonus hunt at the moment.', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        $title = esc_html($hunt->title);
        $start = number_format_i18n((float)$hunt->starting_balance, 2);
        $bonuses = (int)$hunt->num_bonuses;
        $prizes = $hunt->prizes ? wp_kses_post($hunt->prizes) : '';

        ob_start(); 
        ?>
        <div class="bhg-active-hunt">
            <h3><?php echo esc_html( isset( $title ) ? $title : '' ); ?></h3>
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
                    <div><?php echo wp_kses_post( isset( $prizes ) ? $prizes : '' ); ?></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function user_guesses() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $hunt = $wpdb->get_row("SELECT id FROM {$prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        
        if (!$hunt) {
            return '<p>' . esc_html__('No active hunt.', 'bonus-hunt-guesser') . '</p>';
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT g.guess_amount, g.created_at, u.user_login, 
                    (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'bhg_affiliate_status' LIMIT 1) as is_affiliate
             FROM {$prefix}bhg_guesses g 
             JOIN {$wpdb->users} u ON g.user_id=u.ID 
             WHERE g.hunt_id=%d 
             ORDER BY g.created_at DESC
             LIMIT 100",
             (int)$hunt->id
        ));

        if (!$rows) {
            return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';
        }

        ob_start(); 
        ?>
        <div class="bhg-user-guesses">
            <table class="bhg-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Affiliate', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Guess', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Submitted', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): 
                    $is_affiliate = !empty($r->is_affiliate) && $r->is_affiliate === '1';
                ?>
                    <tr>
                        <td><?php echo esc_html($r->user_login); ?></td>
                        <td>
                            <span class="bhg-affiliate-status <?php echo esc_attr( $is_affiliate ? 'affiliate-yes' : 'affiliate-no' ); ?>">
                                ●
                            </span>
                        </td>
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

    public function tournaments() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $rows = $wpdb->get_results("SELECT * FROM {$prefix}bhg_tournaments ORDER BY created_at DESC LIMIT 20");
        
        if (!$rows) {
            return '<p>' . esc_html__('No tournaments yet.', 'bonus-hunt-guesser') . '</p>';
        }
        
        ob_start(); 
        ?>
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

    public function winner_notifications() {
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
        
        if (!$rows) {
            return '<p>' . esc_html__('Winners will be shown here once hunts are closed.', 'bonus-hunt-guesser') . '</p>';
        }
        
        ob_start(); 
        ?>
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

    public function user_profile() {
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

    public function leaderboard() {
        global $wpdb;
        $prefix = $wpdb->prefix;

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

        ob_start(); 
        ?>
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

    public function bonus_hunt_display($atts) {
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
                    <span class="bhg-value"><?php echo (int)$hunt->num_bonuses; ?></span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function bonus_hunt_leaderboard($atts) {
        global $wpdb;
        $hunt = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status = 'open' ORDER BY created_at DESC LIMIT 1");
        
        if (!$hunt) {
            return '<div class="bhg-container">' . esc_html__('No active bonus hunt found.', 'bonus-hunt-guesser') . '</div>';
        }
        
        $guesses = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login, u.display_name, 
             (SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'bhg_affiliate_status' LIMIT 1) as is_affiliate
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
                    <?php foreach ($guesses as $index => $guess): 
                    $is_affiliate = !empty($guess->is_affiliate) && $guess->is_affiliate === '1';
                    ?>
                    <tr>
                        <td><?php echo (int)($index + 1); ?></td>
                        <td><?php echo esc_html($guess->display_name ?: $guess->user_login); ?></td>
                        <td>€<?php echo esc_html(number_format($guess->guess_amount, 2)); ?></td>
                        <td>
                            <span class="bhg-affiliate-status <?php echo esc_attr( $is_affiliate ? 'affiliate-yes' : 'affiliate-no' ); ?>">
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
            wp_send_json_error(esc_html__('Security check failed', 'bonus-hunt-guesser'));
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