<?php
/**
 * Shortcodes for Bonus Hunt Guesser
 */
if (!defined('ABSPATH')) exit;

class BHG_Shortcodes {

    public function __construct() {
        add_shortcode('bhg_active_hunt',   [$this, 'active_hunt_shortcode']);
        add_shortcode('bhg_guess_form',    [$this, 'guess_form_shortcode']);
        add_shortcode('bhg_leaderboard',   [$this, 'leaderboard_shortcode']);
        add_shortcode('bhg_tournaments',   [$this, 'tournaments_shortcode']);
        add_shortcode('bhg_winner_notifications',   [$this, 'winner_notifications_shortcode']);
        add_shortcode('bhg_user_guesses',   [$this, 'user_guesses_shortcode']);
        add_shortcode('bhg_user_profile',   [$this, 'user_profile_shortcode']);
    }

    /** [bhg_active_hunt] — show ALL open hunts (not just one) */
    public function active_hunt_shortcode($atts) {
        global $wpdb;
        $hunts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC");
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
        $atts = shortcode_atts(['hunt_id' => 0], $atts, 'bhg_guess_form');
        $hunt_id = (int)$atts['hunt_id'];

        if (!is_user_logged_in()) {
            $scheme = is_ssl() ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? parse_url(home_url('/'), PHP_URL_HOST);
            $uri  = $_SERVER['REQUEST_URI'] ?? '/';
            $current_url = $scheme . $host . $uri;
            $redirect = esc_url_raw($current_url);
            return '<p>' . esc_html__('Please log in to submit your guess.', 'bonus-hunt-guesser') . '</p>'
                 . '<p><a class="button button-primary" href="' . esc_url(wp_login_url($redirect)) . '">' . esc_html__('Log in', 'bonus-hunt-guesser') . '</a></p>';
        }

        global $wpdb;
        $open_hunts = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC");

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
        $existing_guess = $existing_id ? (float)$wpdb->get_var($wpdb->prepare("SELECT guess_value FROM {$table} WHERE id=%d", $existing_id)) : '';

        $settings = get_option('bhg_plugin_settings');
        $min = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0;
        $max = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000;

        ob_start();
        ?>
        <form class="bhg-guess-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;">
            <input type="hidden" name="action" value="bhg_submit_guess">
            <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>

            <?php if (count($open_hunts) > 1) : ?>
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
                   id="bhg-guess" name="guess_value" value="<?php echo esc_attr($existing_guess); ?>" required>

            <button type="submit" class="button button-primary" style="margin-top:20px;"><?php echo esc_html__('Submit Guess', 'bonus-hunt-guesser'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }

    /** [bhg_leaderboard] */
    public function leaderboard_shortcode($atts) {
        $a = shortcode_atts([
            'hunt_id' => 0,
            'orderby' => 'guess',
            'order'   => 'ASC',
            'page'    => 1,
            'per_page'=> 20,
        ], $atts, 'bhg_leaderboard');

        global $wpdb;
        $hunt_id = (int)$a['hunt_id'];
        if ($hunt_id <= 0) {
            $hunt_id = (int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}bhg_bonus_hunts ORDER BY id DESC LIMIT 1");
            if ($hunt_id <= 0) {
                return '<p>' . esc_html__('No hunts found.', 'bonus-hunt-guesser') . '</p>';
            }
        }

        $g = $wpdb->prefix . 'bhg_guesses';
        $u = $wpdb->users;

        $order = strtoupper($a['order']) === 'DESC' ? 'DESC' : 'ASC';
        $map = [
            'guess'      => 'g.guess_value',
            'user'       => 'u.user_login',
            'position'   => 'g.id', // stable proxy
        ];
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
             ORDER BY {$orderby} {$order}
             LIMIT %d OFFSET %d",
            $hunt_id, $per, $offset
        ));

        ob_start();
        echo '<table class="bhg-leaderboard">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('User', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('Guess', 'bonus-hunt-guesser') . '</th>';
        echo '</tr></thead><tbody>';

        $pos = $offset + 1;
        foreach ($rows as $r) {
            $site_id = isset($r->affiliate_site_id) ? (int)$r->affiliate_site_id : 0;
            $is_aff  = $site_id > 0
                ? (int)get_user_meta((int)$r->user_id, "bhg_affiliate_website_{$site_id}", true)
                : (int)get_user_meta((int)$r->user_id, 'bhg_affiliate_status', true);
            $aff = $is_aff ? 'green' : 'red';
            $user_label = $r->user_login ? $r->user_login : ('user#' . (int)$r->user_id);

            echo '<tr>';
            echo '<td>' . (int)$pos++ . '</td>';
            echo '<td>' . esc_html($user_label) . ' <span class="bhg-aff-' . esc_attr($aff) . '" aria-hidden="true"></span></td>';
            echo '<td>' . esc_html(number_format_i18n((float)$r->guess_value, 2)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        $pages = (int)ceil($total / $per);
        if ($pages > 1) {
            echo '<div class="bhg-pagination">';
            for ($p=1; $p <= $pages; $p++) {
                $is = $p == $page ? ' style="font-weight:bold;"' : '';
                echo '<a'.$is.' href="' . esc_url(add_query_arg(['page'=>$p])) . '">' . (int)$p . '</a> ';
            }
            echo '</div>';
        }

        return ob_get_clean();
    }

    /** [bhg_tournaments period="all|weekly|monthly|yearly" status="active|closed|all"] */
    public function tournaments_shortcode($atts) {
        $a = shortcode_atts(['period' => 'all', 'status' => 'active'], $atts, 'bhg_tournaments');
        global $wpdb;

        $t = $wpdb->prefix . 'bhg_tournaments';
        $where = [];
        $args = [];

        if (in_array($a['period'], ['weekly','monthly','yearly'], true)) {
            $where[] = "type=%s";
            $args[] = $a['period'];
        }
        if (in_array($a['status'], ['active','closed'], true)) {
            $where[] = "status=%s";
            $args[] = $a['status'];
        }

        $sql = "SELECT * FROM {$t}";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY start_date DESC";

        $rows = $args ? $wpdb->get_results($wpdb->prepare($sql, ...$args)) : $wpdb->get_results($sql);
        if (!$rows) {
            return '<p>' . esc_html__('No tournaments found.', 'bonus-hunt-guesser') . '</p>';
        }

        ob_start();

        // Filters UI
        echo '<form method="get" class="bhg-tournament-filters" style="margin-bottom:10px;">';
        foreach ($_GET as $k=>$v) {
            if ($k === 'bhg_period' || $k === 'bhg_status') continue;
            echo '<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr(is_array($v)? reset($v) : $v).'">';
        }
        echo '<label style="margin-right:8px;">' . esc_html__('Period:', 'bonus-hunt-guesser') . ' ';
        echo '<select name="bhg_period">';
        $periods = ['all'=>__('All','bonus-hunt-guesser'),'weekly'=>__('Weekly','bonus-hunt-guesser'),'monthly'=>__('Monthly','bonus-hunt-guesser'),'yearly'=>__('Yearly','bonus-hunt-guesser')];
        foreach ($periods as $key=>$label) {
            $sel = selected($a['period'], $key, false);
            echo "<option value=\"".esc_attr($key)."\" {$sel}>".esc_html($label)."</option>";
        }
        echo '</select></label>';

        echo '<label>' . esc_html__('Status:', 'bonus-hunt-guesser') . ' ';
        echo '<select name="bhg_status">';
        $statuses = ['active'=>__('Active','bonus-hunt-guesser'),'closed'=>__('Closed','bonus-hunt-guesser'),'all'=>__('All','bonus-hunt-guesser')];
        foreach ($statuses as $key=>$label) {
            $sel = selected($a['status'], $key, false);
            echo "<option value=\"".esc_attr($key)."\" {$sel}>".esc_html($label)."</option>";
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

        foreach ($rows as $r) {
            $detail_url = add_query_arg(['bhg_tournament_id' => (int)$r->id], home_url('/'));
            echo '<tr>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(ucfirst($r->type)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($r->period) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(mysql2date(get_option('date_format'), $r->start_date)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html(mysql2date(get_option('date_format'), $r->end_date)) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;">' . esc_html($r->status) . '</td>';
            echo '<td style="padding:6px;border-bottom:1px solid #f1f5f9;"><a href="'.esc_url($detail_url).'">' . esc_html__('Show details','bonus-hunt-guesser') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        return ob_get_clean();
    }

    /** Minimal winners widget: latest closed hunts */
    public function winner_notifications_shortcode($atts) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT title, final_balance, winner_diff, closed_at FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='closed' AND winner_user_id IS NOT NULL ORDER BY closed_at DESC LIMIT 5");
        if (!$rows) return '';
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

    /** Show current user's guesses */
    public function user_guesses_shortcode($atts) {
        if (!is_user_logged_in()) return '';
        $user_id = get_current_user_id();
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT g.*, h.title FROM {$wpdb->prefix}bhg_guesses g LEFT JOIN {$wpdb->prefix}bhg_bonus_hunts h ON h.id=g.hunt_id WHERE g.user_id=%d ORDER BY g.created_at DESC", $user_id));
        if (!$rows) return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';
        ob_start();
        echo '<ul class="bhg-user-guesses">';
        foreach ($rows as $r) {
            echo '<li>' . esc_html($r->title) . ': ' . esc_html(number_format_i18n((float)$r->guess_value, 2)) . '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }

    /** Minimal profile view: affiliate status badge */
    public function user_profile_shortcode($atts) {
        if (!is_user_logged_in()) return '';
        $user_id = get_current_user_id();
        $is_affiliate = (int)get_user_meta($user_id, 'bhg_affiliate_status', true);
        $badge = $is_affiliate ? '<span class="bhg-aff-green" aria-hidden="true"></span>' : '<span class="bhg-aff-red" aria-hidden="true"></span>';
        return '<div class="bhg-user-profile">' . $badge . ' ' . esc_html(wp_get_current_user()->display_name) . '</div>';
    }
}

