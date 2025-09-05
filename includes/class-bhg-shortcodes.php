<?php
/**
 * Shortcodes for Bonus Hunt Guesser
 *
 * This file is self-contained and safe on PHP 7.4.
 * It registers the required shortcodes on `init` and avoids "public function outside class" parse errors.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (!class_exists('BHG_Shortcodes')) {

class BHG_Shortcodes {

    public function __construct() {
        // Register shortcodes once
        add_shortcode('bhg_active_hunt',        array($this, 'active_hunt_shortcode'));
        add_shortcode('bhg_guess_form',         array($this, 'guess_form_shortcode'));
        add_shortcode('bhg_leaderboard',        array($this, 'leaderboard_shortcode'));
        add_shortcode('bhg_tournaments',        array($this, 'tournaments_shortcode'));
        add_shortcode('bhg_winner_notifications', array($this, 'winner_notifications_shortcode'));
        add_shortcode('bhg_user_profile',       array($this, 'user_profile_shortcode'));
        add_shortcode('bhg_best_guessers',      array($this, 'best_guessers_shortcode'));

        // Legacy/alias tags if your site used alternatives
        add_shortcode('bonus_hunt_leaderboard', array($this, 'leaderboard_shortcode'));
        add_shortcode('bonus_hunt_login',       array($this, 'login_hint_shortcode'));
        add_shortcode('bhg_active',             array($this, 'active_hunt_shortcode'));
    }

    /** Minimal login hint used by some themes */
    public function login_hint_shortcode($atts = array()) {
        if (is_user_logged_in()) {
            return '';
        }
        $redirect = (is_ssl() ? 'https://' : 'http://') . (isset($_SERVER['HTTP_HOST']) ? esc_attr(wp_unslash($_SERVER['HTTP_HOST'])) : parse_url(home_url('/'), PHP_URL_HOST)) . (isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/');
        return '<p>' . esc_html__('Please log in to continue.', 'bonus-hunt-guesser') . '</p>'
             . '<p><a class="button button-primary" href="' . esc_url(wp_login_url($redirect)) . '">' . esc_html__('Log in', 'bonus-hunt-guesser') . '</a></p>';
    }

    /** [bhg_active_hunt] — list all open hunts */
    public function active_hunt_shortcode($atts) {
        global $wpdb;
        $hunts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY created_at DESC" );
        if (!$hunts) {
            return '<div class="bhg-active-hunt"><p>' . esc_html__('No active bonus hunts at the moment.', 'bonus-hunt-guesser') . '</p></div>';
        }

        ob_start();
        echo '<div class="bhg-active-hunts">';
        foreach ($hunts as $hunt) {
            echo '<div class="bhg-hunt-card" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin:12px 0;">';
            echo '<h3 style="margin:0 0 8px;">' . esc_html($hunt->title) . '</h3>';
            echo '<ul class="bhg-hunt-meta" style="list-style:none;margin:0;padding:0">';
            echo '<li><strong>' . esc_html__('Starting Balance', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(number_format_i18n((float)$hunt->starting_balance, 2)) . '</li>';
            echo '<li><strong>' . esc_html__('Number of Bonuses', 'bonus-hunt-guesser') . ':</strong> ' . (int)$hunt->num_bonuses . '</li>';
            if (!empty($hunt->prizes)) {
                echo '<li><strong>' . esc_html__('Prizes', 'bonus-hunt-guesser') . ':</strong> ' . wp_kses_post($hunt->prizes) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    /** [bhg_guess_form hunt_id=""] */
    public function guess_form_shortcode($atts) {
        $atts = shortcode_atts(array('hunt_id' => 0), $atts, 'bhg_guess_form');
        $hunt_id = (int) $atts['hunt_id'];

        if (!is_user_logged_in()) {
            $scheme = is_ssl() ? 'https://' : 'http://';
            $host   = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : parse_url(home_url('/'), PHP_URL_HOST);
            $uri    = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
            $redirect = esc_url_raw($scheme . $host . $uri);
            return '<p>' . esc_html__('Please log in to submit your guess.', 'bonus-hunt-guesser') . '</p>'
                 . '<p><a class="button button-primary" href="' . esc_url(wp_login_url($redirect)) . '">' . esc_html__('Log in', 'bonus-hunt-guesser') . '</a></p>';
        }

        global $wpdb;
        $open_hunts = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY created_at DESC" );

        if ($hunt_id <= 0) {
            if (!$open_hunts) {
                return '<p>' . esc_html__('No open hunt found to guess.', 'bonus-hunt-guesser') . '</p>';
            }
            if (count($open_hunts) === 1) {
                $hunt_id = (int)$open_hunts[0]->id;
            }
        }

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'bhg_guesses';
        $existing_id = $hunt_id > 0 ? (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND hunt_id=%d", $user_id, $hunt_id)) : 0;
        $existing_guess = $existing_id ? (float) $wpdb->get_var($wpdb->prepare("SELECT guess FROM {$table} WHERE id=%d", $existing_id)) : '';

        $settings = get_option('bhg_plugin_settings');
        $min = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0;
        $max = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000;

        ob_start(); ?>
        <form class="bhg-guess-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;">
            <input type="hidden" name="action" value="bhg_submit_guess">
            <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>

            <?php if ($open_hunts && count($open_hunts) > 1) : ?>
                <label for="bhg-hunt-select"><?php esc_html_e('Choose a hunt:', 'bonus-hunt-guesser'); ?></label>
                <select id="bhg-hunt-select" name="hunt_id" required>
                    <option value=""><?php esc_html_e('Select a hunt', 'bonus-hunt-guesser'); ?></option>
                    <?php foreach ($open_hunts as $oh) : ?>
                        <option value="<?php echo (int)$oh->id; ?>" <?php if ($hunt_id === (int)$oh->id) echo 'selected'; ?>>
                            <?php echo esc_html($oh->title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input type="hidden" name="hunt_id" value="<?php echo esc_attr($hunt_id); ?>">
            <?php endif; ?>

            <label for="bhg-guess" style="display:block;margin-top:10px;"><?php esc_html_e('Your guess (final balance):', 'bonus-hunt-guesser'); ?></label>
            <input type="number" step="0.01" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>"
                   id="bhg-guess" name="guess" value="<?php echo esc_attr($existing_guess); ?>" required>

            <button type="submit" class="button button-primary" style="margin-top:20px;"><?php echo esc_html__('Submit Guess', 'bonus-hunt-guesser'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }

    /** [bhg_leaderboard] */
    public function leaderboard_shortcode($atts) {
        $a = shortcode_atts(array(
            'hunt_id' => 0,
            'orderby' => 'guess',
            'order'   => 'ASC',
            'page'    => 1,
            'per_page'=> 20,
        ), $atts, 'bhg_leaderboard');

        global $wpdb;
        $hunt_id = (int)$a['hunt_id'];
        if ($hunt_id <= 0) {
            $hunt_id = (int)$wpdb->get_var( "SELECT id FROM {$wpdb->prefix}bhg_bonus_hunts ORDER BY created_at DESC LIMIT 1" );
            if ($hunt_id <= 0) {
                return '<p>' . esc_html__('No hunts found.', 'bonus-hunt-guesser') . '</p>';
            }
        }

        $g = $wpdb->prefix . 'bhg_guesses';
        $u = $wpdb->users;

        $order = strtoupper($a['order']) === 'DESC' ? 'DESC' : 'ASC';
        $map = array(
            'guess'      => 'g.guess',
            'user'       => 'u.user_login',
            'position'   => 'g.id', // stable proxy
        );
        $orderby_key = array_key_exists($a['orderby'], $map) ? $a['orderby'] : 'guess';
        $orderby = $map[$orderby_key];
        $page    = max(1, (int)$a['page']);
        $per     = max(1, (int)$a['per_page']);
        $offset  = ($page - 1) * $per;

        $total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$g} WHERE hunt_id=%d", $hunt_id));
        if ($total < 1) {
            return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login, h.affiliate_site_id
             FROM {$g} g
             LEFT JOIN {$u} u ON u.ID = g.user_id
             LEFT JOIN {$wpdb->prefix}bhg_bonus_hunts h ON h.id = g.hunt_id
             WHERE g.hunt_id=%d
             ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            $hunt_id, $per, $offset
        ));

        ob_start();
        echo '<table class="bhg-leaderboard">';
        echo '<thead><tr>';
        echo '<th class="sortable" data-column="position">' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>';
        echo '<th class="sortable" data-column="user">' . esc_html__('User', 'bonus-hunt-guesser') . '</th>';
        echo '<th class="sortable" data-column="guess">' . esc_html__('Guess', 'bonus-hunt-guesser') . '</th>';
        echo '</tr></thead><tbody>';

        $pos = $offset + 1;
        foreach ($rows as $r) {
            $site_id = isset($r->affiliate_site_id) ? (int)$r->affiliate_site_id : 0;
            $is_aff  = $site_id > 0
                ? (int)get_user_meta((int)$r->user_id, 'bhg_affiliate_website_' . $site_id, true)
                : (int)get_user_meta((int)$r->user_id, 'bhg_affiliate_status', true);
            $aff = $is_aff ? 'green' : 'red';
            $user_label = $r->user_login ? $r->user_login : ('user#' . (int)$r->user_id);

            echo '<tr>';
            echo '<td data-column="position">' . (int)$pos++ . '</td>';
            echo '<td data-column="user">' . esc_html($user_label) . ' <span class="bhg-aff-dot bhg-aff-' . esc_attr($aff) . '" aria-hidden="true"></span></td>';
            echo '<td data-column="guess">' . esc_html(number_format_i18n((float) $r->guess, 2)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<style>.bhg-aff-dot{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:middle;margin-left:6px}.bhg-aff-green{background:#1f9d55}.bhg-aff-red{background:#e3342f}</style>';

        $pages = (int)ceil($total / $per);
        if ($pages > 1) {
            echo '<div class="bhg-pagination">';
            for ($p=1; $p <= $pages; $p++) {
                $is = $p == $page ? ' style="font-weight:bold;"' : '';
                echo '<a'.$is.' href="' . esc_url(add_query_arg(array('page'=>$p))) . '">' . (int)$p . '</a> ';
            }
            echo '</div>';
        }

        return ob_get_clean();
    }

    /** [bhg_tournaments] list + details (via ?bhg_tournament_id=ID) */
    public function tournaments_shortcode($atts) {
        global $wpdb;

        // If a specific tournament ID is requested, render details
        $details_id = isset($_GET['bhg_tournament_id']) ? absint($_GET['bhg_tournament_id']) : 0;
        if ($details_id > 0) {
            $t = $wpdb->prefix . 'bhg_tournaments';
            $r = $wpdb->prefix . 'bhg_tournament_results';
            $u = $wpdb->users;

            $tournament = $wpdb->get_row($wpdb->prepare(
                "SELECT id, type, period, start_date, end_date, status FROM {$t} WHERE id=%d",
                $details_id
            ));
            if (!$tournament) {
                return '<p>' . esc_html__('Tournament not found.', 'bonus-hunt-guesser') . '</p>';
            }

            // Sortable results (whitelisted)
            $orderby = isset($_GET['orderby']) ? strtolower(sanitize_key($_GET['orderby'])) : 'wins';
            $order   = isset($_GET['order'])   ? strtolower(sanitize_key($_GET['order']))   : 'desc';

            $allowed = array(
                'wins'        => 'r.wins',
                'username'    => 'u.user_login',
                'last_win_at' => 'r.last_win_date',
            );
            if (!isset($allowed[$orderby])) { $orderby = 'wins'; }
            if ($order !== 'asc' && $order !== 'desc') { $order = 'desc'; }
            $order_by_sql = $allowed[$orderby] . ' ' . strtoupper($order);

            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT r.user_id, r.wins, r.last_win_date, u.user_login
                 FROM {$r} r
                 INNER JOIN {$u} u ON u.ID = r.user_id
                 WHERE r.tournament_id=%d
                 ORDER BY {$order_by_sql}, r.user_id ASC",
                $tournament->id
            ));

            $base = remove_query_arg(array('orderby','order'));
            $toggle = function($key) use ($orderby, $order, $base) {
                $next = ($orderby === $key && strtolower($order) === 'asc') ? 'desc' : 'asc';
                return esc_url(add_query_arg(array('orderby'=>$key,'order'=>$next), $base));
            };

            ob_start();
            echo '<div class="bhg-tournament-details" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;">';
            echo '<p><a href="' . esc_url(remove_query_arg('bhg_tournament_id')) . '">&larr; ' . esc_html__('Back to tournaments', 'bonus-hunt-guesser') . '</a></p>';
            echo '<h3 style="margin-top:0;">' . esc_html(ucfirst($tournament->type)) . ' — ' . esc_html($tournament->period) . '</h3>';
            echo '<p><strong>' . esc_html__('Start', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(mysql2date(get_option('date_format'), $tournament->start_date)) . ' &nbsp; ';
            echo '<strong>' . esc_html__('End', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(mysql2date(get_option('date_format'), $tournament->end_date)) . ' &nbsp; ';
            echo '<strong>' . esc_html__('Status', 'bonus-hunt-guesser') . ':</strong> ' . esc_html($tournament->status) . '</p>';

            if (!$rows) {
                echo '<p>' . esc_html__('No results yet.', 'bonus-hunt-guesser') . '</p>';
                echo '</div>';
                return ob_get_clean();
            }

            echo '<table class="bhg-leaderboard" style="width:100%;border-collapse:collapse">';
            echo '<thead><tr>';
            echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">#</th>';
            echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;"><a href="' . $toggle('username') . '">' . esc_html__('Username', 'bonus-hunt-guesser') . '</a></th>';
            echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;"><a href="' . $toggle('wins') . '">' . esc_html__('Wins', 'bonus-hunt-guesser') . '</a></th>';
            echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;"><a href="' . $toggle('last_win_at') . '">' . esc_html__('Last win', 'bonus-hunt-guesser') . '</a></th>';
            echo '</tr></thead><tbody>';

            $pos = 1;
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . (int)$pos++ . '</td>';
                echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($row->user_login ?: ('user#' . (int)$row->user_id)) . '</td>';
                echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . (int)$row->wins . '</td>';
                echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($row->last_win_date ? mysql2date(get_option('date_format'), $row->last_win_date) : '—') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';

            return ob_get_clean();
        }

        // Otherwise list tournaments with filters
        $a = shortcode_atts(array('period' => 'all', 'status' => 'active'), $atts, 'bhg_tournaments');
        $t = $wpdb->prefix . 'bhg_tournaments';
        $where = array();
        $args  = array();

        if (in_array($a['period'], array('weekly','monthly','yearly'), true)) {
            $where[] = "type=%s";
            $args[]  = $a['period'];
        }
        if (in_array($a['status'], array('active','closed'), true)) {
            $where[] = "status=%s";
            $args[]  = $a['status'];
        }

        $sql = "SELECT * FROM {$t}";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY start_date DESC, id DESC";

        $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) ) : $wpdb->get_results( $sql );
        if (!$rows) {
            return '<p>' . esc_html__('No tournaments found.', 'bonus-hunt-guesser') . '</p>';
        }

        $current_url = (isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : home_url('/'));

        ob_start();
        echo '<form method="get" class="bhg-tournament-filters" style="margin-bottom:10px;">';
        foreach ($_GET as $k=>$v) {
            if ($k === 'bhg_period' || $k === 'bhg_status' || $k === 'bhg_tournament_id') continue;
            echo '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr(is_array($v)? reset($v) : $v).'">';
        }
        echo '<label style="margin-right:8px;">' . esc_html__('Period:', 'bonus-hunt-guesser') . ' ';
        echo '<select name="bhg_period">';
        $periods = array('all'=>__('All','bonus-hunt-guesser'),'weekly'=>__('Weekly','bonus-hunt-guesser'),'monthly'=>__('Monthly','bonus-hunt-guesser'),'yearly'=>__('Yearly','bonus-hunt-guesser'));
        $period_key = isset($_GET['bhg_period']) ? sanitize_key($_GET['bhg_period']) : $a['period'];
        foreach ($periods as $key=>$label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($period_key, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';

        echo '<label>' . esc_html__('Status:', 'bonus-hunt-guesser') . ' ';
        echo '<select name="bhg_status">';
        $statuses = array('active'=>__('Active','bonus-hunt-guesser'),'closed'=>__('Closed','bonus-hunt-guesser'),'all'=>__('All','bonus-hunt-guesser'));
        $status_key = isset($_GET['bhg_status']) ? sanitize_key($_GET['bhg_status']) : $a['status'];
        foreach ($statuses as $key=>$label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($status_key, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label> ';

        echo '<button class="button" type="submit" style="margin-left:8px;">'.esc_html__('Filter','bonus-hunt-guesser').'</button>';
        echo '</form>';

        echo '<table class="bhg-tournaments" style="width:100%;border-collapse:collapse">';
        echo '<thead><tr>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('Type', 'bonus-hunt-guesser') . '</th>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('Period', 'bonus-hunt-guesser') . '</th>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('Start', 'bonus-hunt-guesser') . '</th>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('End', 'bonus-hunt-guesser') . '</th>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('Status', 'bonus-hunt-guesser') . '</th>';
        echo '<th style="text-align:left;border-bottom:1px solid #e2e8f0;padding:6px;">' . esc_html__('Details', 'bonus-hunt-guesser') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $detail_url = esc_url(add_query_arg('bhg_tournament_id', (int)$row->id, remove_query_arg(array('orderby','order'), $current_url)));
            echo '<tr>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(ucfirst($row->type)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($row->period) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(mysql2date(get_option('date_format'), $row->start_date)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(mysql2date(get_option('date_format'), $row->end_date)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($row->status) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;"><a href="' . $detail_url . '">' . esc_html__('Show details','bonus-hunt-guesser') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        return ob_get_clean();
    }

    /** Minimal winners widget: latest closed hunts */
    public function winner_notifications_shortcode($atts) {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT title, final_balance, winner_diff, closed_at FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='closed' AND winner_user_id IS NOT NULL ORDER BY id DESC LIMIT 5" );
        if (!$rows) return '<p>' . esc_html__('No closed hunts yet.', 'bonus-hunt-guesser') . '</p>';
        ob_start();
        echo '<div class="bhg-winner-notifications">';
        foreach ($rows as $row) {
            echo '<div class="bhg-winner">';
            echo '<p><strong>' . esc_html($row->title) . '</strong></p>';
            echo '<p><em>' . esc_html__('Final', 'bonus-hunt-guesser') . ':</em> ' . esc_html(number_format_i18n((float)$row->final_balance, 2)) . '</p>';
            if (!empty($row->winner_diff)) {
                echo '<p><em>' . esc_html__('Diff', 'bonus-hunt-guesser') . ':</em> ' . esc_html(number_format_i18n((float)$row->winner_diff, 2)) . '</p>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    /** Minimal profile view: affiliate status badge */
    public function user_profile_shortcode($atts) {
        if (!is_user_logged_in()) return '<p>' . esc_html__('Please log in to view this content.', 'bonus-hunt-guesser') . '</p>';
        $user_id = get_current_user_id();
        $is_affiliate = (int)get_user_meta($user_id, 'bhg_affiliate_status', true);
        $badge = $is_affiliate ? '<span class="bhg-aff-green" aria-hidden="true"></span>' : '<span class="bhg-aff-red" aria-hidden="true"></span>';
        return '<div class="bhg-user-profile">' . $badge . ' ' . esc_html(wp_get_current_user()->display_name) . '</div>';
    }

    /** [bhg_best_guessers] — simple wins leaderboard */
    public function best_guessers_shortcode($atts) {
        global $wpdb;

        $wins_tbl = $wpdb->prefix . 'bhg_tournament_results';
        $tours_tbl = $wpdb->prefix . 'bhg_tournaments';
        $users_tbl = $wpdb->users;

        $now_ts = current_time('timestamp');
        $current_month = gmdate('Y-m', $now_ts);
        $current_year  = gmdate('Y', $now_ts);

        $periods = array(
            'overall' => array(
                'label' => esc_html__('Overall', 'bonus-hunt-guesser'),
                'type'  => '',
                'period'=> '',
            ),
            'monthly' => array(
                'label' => esc_html__('Monthly', 'bonus-hunt-guesser'),
                'type'  => 'monthly',
                'period'=> $current_month,
            ),
            'yearly' => array(
                'label' => esc_html__('Yearly', 'bonus-hunt-guesser'),
                'type'  => 'yearly',
                'period'=> $current_year,
            ),
            'alltime' => array(
                'label' => esc_html__('All-Time', 'bonus-hunt-guesser'),
                'type'  => 'alltime',
                'period'=> '',
            ),
        );

        $results = array();
        foreach ($periods as $key => $info) {
            if ($info['type']) {
                $where = 't.type = %s';
                $params = array($info['type']);
                if ($info['period']) {
                    $where .= ' AND t.period = %s';
                    $params[] = $info['period'];
                }
                $sql = "SELECT u.ID as user_id, u.user_login, SUM(r.wins) as total_wins
                        FROM {$wins_tbl} r
                        INNER JOIN {$users_tbl} u ON u.ID = r.user_id
                        INNER JOIN {$tours_tbl} t ON t.id = r.tournament_id
                        WHERE {$where}
                        GROUP BY u.ID, u.user_login
                        ORDER BY total_wins DESC, u.user_login ASC
                        LIMIT 50";
                array_unshift($params, $sql);
                $prepared = call_user_func_array(array($wpdb, 'prepare'), $params);
                $results[$key] = $wpdb->get_results($prepared);
            } else {
                $sql = "SELECT u.ID as user_id, u.user_login, SUM(r.wins) as total_wins
                        FROM {$wins_tbl} r
                        INNER JOIN {$users_tbl} u ON u.ID = r.user_id
                        GROUP BY u.ID, u.user_login
                        ORDER BY total_wins DESC, u.user_login ASC
                        LIMIT 50";
                $results[$key] = $wpdb->get_results( $sql );
            }
        }

        $hunts_tbl = $wpdb->prefix . 'bhg_bonus_hunts';
        $hunts = $wpdb->get_results( "SELECT id, title FROM {$hunts_tbl} WHERE status='closed' ORDER BY created_at DESC LIMIT 50" );

        ob_start();
        echo '<ul class="bhg-tabs">';
        $first = true;
        foreach ($periods as $key => $info) {
            $active = $first ? ' class="active"' : '';
            echo '<li' . $active . '><a href="#bhg-tab-' . esc_attr($key) . '">' . esc_html($info['label']) . '</a></li>';
            $first = false;
        }
        if ($hunts) {
            echo '<li><a href="#bhg-tab-hunts">' . esc_html__('Bonus Hunts', 'bonus-hunt-guesser') . '</a></li>';
        }
        echo '</ul>';

        $first = true;
        foreach ($periods as $key => $info) {
            $active = $first ? ' active' : '';
            echo '<div id="bhg-tab-' . esc_attr($key) . '" class="bhg-tab-pane' . $active . '">';
            $rows = isset($results[$key]) ? $results[$key] : array();
            if (!$rows) {
                echo '<p>' . esc_html__('No data yet.', 'bonus-hunt-guesser') . '</p>';
            } else {
                echo '<table class="bhg-leaderboard"><thead><tr><th>#</th><th>' . esc_html__('User', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th></tr></thead><tbody>';
                $pos = 1;
                foreach ($rows as $r) {
                    $user_label = $r->user_login ? $r->user_login : 'user#' . (int) $r->user_id;
                    echo '<tr><td>' . (int) $pos++ . '</td><td>' . esc_html($user_label) . '</td><td>' . (int) $r->total_wins . '</td></tr>';
                }
                echo '</tbody></table>';
            }
            echo '</div>';
            $first = false;
        }

        if ($hunts) {
            echo '<div id="bhg-tab-hunts" class="bhg-tab-pane">';
            echo '<ul class="bhg-hunt-history">';
            foreach ($hunts as $hunt) {
                $url = esc_url(add_query_arg('hunt_id', (int) $hunt->id));
                echo '<li><a href="' . $url . '">' . esc_html($hunt->title) . '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }

        echo '<style>.bhg-tabs{list-style:none;margin:0;padding:0;display:flex;border-bottom:1px solid #e2e8f0}.bhg-tabs li{margin:0;padding:0}.bhg-tabs a{display:block;padding:8px 12px;text-decoration:none;border:1px solid #e2e8f0;border-bottom:none;margin-right:4px;background:#f7fafc;border-top-left-radius:4px;border-top-right-radius:4px}.bhg-tabs li.active a{background:#fff;font-weight:700}.bhg-tab-pane{display:none;border:1px solid #e2e8f0;padding:12px;border-top:none}.bhg-tab-pane.active{display:block}</style>';
        echo '<script>document.addEventListener("DOMContentLoaded",function(){var t=document.querySelectorAll(".bhg-tabs a");t.forEach(function(e){e.addEventListener("click",function(t){t.preventDefault();var n=this.getAttribute("href").substring(1);document.querySelectorAll(".bhg-tabs li").forEach(function(e){e.classList.remove("active")});document.querySelectorAll(".bhg-tab-pane").forEach(function(e){e.classList.remove("active")});this.parentElement.classList.add("active");var e=document.getElementById(n);e&&e.classList.add("active")})})});</script>';

        return ob_get_clean();
    }
}

} // end if class not exists

// Register once on init even if no other bootstrap instantiates the class
if (!function_exists('bhg_register_shortcodes_once')) {
    function bhg_register_shortcodes_once() {
        static $done = false;
        if ($done) return;
        $done = true;
        if (class_exists('BHG_Shortcodes')) {
            // Instantiate to attach the hooks
            new BHG_Shortcodes();
        }
    }
    add_action('init', 'bhg_register_shortcodes_once', 20);
}
