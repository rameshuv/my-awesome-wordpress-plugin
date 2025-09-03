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

    /** [bhg_active_hunt] */
    public function active_hunt_shortcode($atts) {
        global $wpdb;
        $hunt = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
        if (!$hunt) {
            return '<div class="bhg-active-hunt"><p>' . esc_html__('No active bonus hunt at the moment.', 'bonus-hunt-guesser') . '</p></div>';
        }

        $html  = '<div class="bhg-active-hunt">';
        $html .= '<h3>' . esc_html($hunt->title) . '</h3>';
        $html .= '<ul class="bhg-hunt-meta">';
        $html .= '<li><strong>' . esc_html__('Starting Balance', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(number_format_i18n((float)$hunt->starting_balance, 2)) . '</li>';
        $html .= '<li><strong>' . esc_html__('Number of Bonuses', 'bonus-hunt-guesser') . ':</strong> ' . (int)$hunt->num_bonuses . '</li>';
        if (!empty($hunt->prizes)) {
            $html .= '<li><strong>' . esc_html__('Prizes', 'bonus-hunt-guesser') . ':</strong> ' . wp_kses_post($hunt->prizes) . '</li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

    /** [bhg_guess_form hunt_id=""] */
    public function guess_form_shortcode($atts) {
        $atts = shortcode_atts(['hunt_id' => 0], $atts, 'bhg_guess_form');
        $hunt_id = (int)$atts['hunt_id'];

        if (!is_user_logged_in()) {
            $redirect = esc_url(add_query_arg([], home_url(add_query_arg([]))));
            return '<p>' . esc_html__('Please log in to submit your guess.', 'bonus-hunt-guesser') . '</p>'
                 . '<p><a class="button" href="' . esc_url(wp_login_url($redirect)) . '">' . esc_html__('Log in', 'bonus-hunt-guesser') . '</a></p>';
        }

        if ($hunt_id <= 0) {
            global $wpdb;
            $hunt_id = (int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY id DESC LIMIT 1");
            if ($hunt_id <= 0) {
                return '<p>' . esc_html__('No open hunt found to guess.', 'bonus-hunt-guesser') . '</p>';
            }
        }

        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_guesses';
        $existing_id = (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND hunt_id=%d", $user_id, $hunt_id));
        $existing_guess = $existing_id ? (float)$wpdb->get_var($wpdb->prepare("SELECT guess_value FROM {$table} WHERE id=%d", $existing_id)) : '';

        $settings = get_option('bhg_plugin_settings');
        $min = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0;
        $max = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000;

        ob_start();
        ?>
        <form class="bhg-guess-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="bhg_submit_guess">
            <input type="hidden" name="hunt_id" value="<?php echo esc_attr($hunt_id); ?>">
            <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>

            <label for="bhg-guess"><?php esc_html_e('Your guess (final balance):', 'bonus-hunt-guesser'); ?></label>
            <input type="number" step="0.01" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>"
                   id="bhg-guess" name="guess_value" value="<?php echo esc_attr($existing_guess); ?>" required>

            <button type="submit" class="button button-primary"><?php echo $existing_id ? esc_html__('Update Guess', 'bonus-hunt-guesser') : esc_html__('Submit Guess', 'bonus-hunt-guesser'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }

    /** [bhg_leaderboard hunt_id="" orderby="guess|user_login|position" order="ASC|DESC" page="1" per_page="20"] */
    public function leaderboard_shortcode($atts) {
        $a = shortcode_atts([
            'hunt_id' => 0,
            'orderby' => 'guess',
            'order'   => 'ASC',
            'page'    => 1,
            'per_page'=> 20,
        ], $atts, 'bhg_leaderboard');

        $hunt_id = (int)$a['hunt_id'];
        if ($hunt_id <= 0) {
            global $wpdb;
            $hunt_id = (int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}bhg_bonus_hunts ORDER BY id DESC LIMIT 1");
            if ($hunt_id <= 0) {
                return '<p>' . esc_html__('No hunts found.', 'bonus-hunt-guesser') . '</p>';
            }
        }

        global $wpdb;
        $g = $wpdb->prefix . 'bhg_guesses';
        $u = $wpdb->users;
        $allowed = ['guess','user_login','position'];
        $order   = (strtoupper($a['order']) === 'DESC') ? 'DESC' : 'ASC';
        // Map friendly names to actual columns
        $map = [
            'guess'      => 'g.guess_value',
            'user_login' => 'u.user_login',
            'position'   => 'g.id' // position is display-only; order by id as a stable proxy
        ];
        $orderby_key = in_array($a['orderby'], array_keys($map), true) ? $a['orderby'] : 'guess';
        $orderby = $map[$orderby_key];
        $page    = max(1, (int)$a['page']);
        $per     = max(1, (int)$a['per_page']);
        $offset  = ($page - 1) * $per;

        $total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$g} WHERE hunt_id=%d", $hunt_id));
        if ($total < 1) {
            return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login
             FROM {$g} g
             LEFT JOIN {$u} u ON u.ID = g.user_id
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
            $aff = get_user_meta((int)$r->user_id, 'bhg_affiliate_status', true) ? 'green' : 'red';
            echo '<tr>';
            echo '<td>' . (int)$pos++ . '</td>';
            echo '<td>' . esc_html($r->user_login or ('user#' . (int)$r->user_id)) . ' <span class="bhg-aff-dot bhg-aff-' . esc_attr($aff) . '" aria-hidden="true"></span></td>';
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

    /** [bhg_tournaments period="weekly|monthly|yearly|all"] */
    public function tournaments_shortcode($atts) {
        $a = shortcode_atts(['period' => 'monthly'], $atts, 'bhg_tournaments');
        global $wpdb;

        $t = $wpdb->prefix . 'bhg_tournaments';
        $sql = "SELECT * FROM {$t}";
        $args = [];

        if (in_array($a['period'], ['weekly','monthly','yearly'], true)) {
            $sql .= " WHERE type=%s";
            $args[] = $a['period'];
        }
        $sql .= " ORDER BY start_date DESC";

        $rows = $args ? $wpdb->get_results($wpdb->prepare($sql, ...$args)) : $wpdb->get_results($sql);
        if (!$rows) {
            return '<p>' . esc_html__('No tournaments found.', 'bonus-hunt-guesser') . '</p>';
        }

        ob_start();
        echo '<table class="bhg-tournaments">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Type', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('Period', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('Start', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('End', 'bonus-hunt-guesser') . '</th>';
        echo '<th>' . esc_html__('Status', 'bonus-hunt-guesser') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $r) {
            echo '<tr>';
            echo '<td>' . esc_html(ucfirst($r->type)) . '</td>';
            echo '<td>' . esc_html($r->period) . '</td>';
            echo '<td>' . esc_html(mysql2date(get_option('date_format'), $r->start_date)) . '</td>';
            echo '<td>' . esc_html(mysql2date(get_option('date_format'), $r->end_date)) . '</td>';
            echo '<td>' . esc_html($r->status) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        return ob_get_clean();
    }


/** [bhg_user_profile] - minimal */
public function user_profile_shortcode($atts) {
    if (!is_user_logged_in()) {
        $login = wp_login_url(get_permalink());
        return '<p>' . esc_html__('Please log in to view your profile.', 'bonus-hunt-guesser') . '</p>'
             . '<p><a class="button" href="' . esc_url($login) . '">' . esc_html__('Log in', 'bonus-hunt-guesser') . '</a></p>';
    }
    $user = wp_get_current_user();
    $is_aff = get_user_meta($user->ID, 'bhg_affiliate_status', true) ? esc_html__('Yes','bonus-hunt-guesser') : esc_html__('No','bonus-hunt-guesser');
    $html  = '<div class="bhg-user-profile">';
    $html .= '<p><strong>' . esc_html__('Display Name:', 'bonus-hunt-guesser') . '</strong> ' . esc_html($user->display_name) . '</p>';
    $html .= '<p><strong>' . esc_html__('Username:', 'bonus-hunt-guesser') . '</strong> ' . esc_html($user->user_login) . '</p>';
    $html .= '<p><strong>' . esc_html__('Email:', 'bonus-hunt-guesser') . '</strong> ' . esc_html($user->user_email) . '</p>';
    $html .= '<p><strong>' . esc_html__('Affiliate:', 'bonus-hunt-guesser') . '</strong> ' . $is_aff . '</p>';
    $html .= '</div>';
    return $html;
}


/** [bhg_user_guesses] - minimal */
public function user_guesses_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>' . esc_html__('Log in to see your guesses.', 'bonus-hunt-guesser') . '</p>';
    }
    $user_id = get_current_user_id();
    global $wpdb;
    $g = $wpdb->prefix . 'bhg_guesses';
    $h = $wpdb->prefix . 'bhg_bonus_hunts';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT g.guess_value, g.created_at, h.title, h.status
         FROM {$g} g
         LEFT JOIN {$h} h ON h.id = g.hunt_id
         WHERE g.user_id=%d
         ORDER BY g.created_at DESC
         LIMIT 20",
         $user_id
    ));
    if (empty($rows)) {
        return '<p>' . esc_html__('You have not submitted any guesses yet.', 'bonus-hunt-guesser') . '</p>';
    }
    ob_start();
    echo '<table class="bhg-user-guesses"><thead><tr>';
    echo '<th>' . esc_html__('Hunt', 'bonus-hunt-guesser') . '</th>';
    echo '<th>' . esc_html__('Guess', 'bonus-hunt-guesser') . '</th>';
    echo '<th>' . esc_html__('Status', 'bonus-hunt-guesser') . '</th>';
    echo '<th>' . esc_html__('Submitted', 'bonus-hunt-guesser') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        echo '<td>' . esc_html($r->title ?: __('(deleted)', 'bonus-hunt-guesser')) . '</td>';
        echo '<td>' . esc_html(number_format_i18n((float)$r->guess_value, 2)) . '</td>';
        echo '<td>' . esc_html($r->status ?: '') . '</td>';
        echo '<td>' . esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $r->created_at)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    return ob_get_clean();
}


/** [bhg_winner_notifications] - minimal */
public function winner_notifications_shortcode($atts) {
    global $wpdb;
    $h = $wpdb->prefix . 'bhg_bonus_hunts';
    $row = $wpdb->get_row("SELECT * FROM {$h} WHERE status='closed' AND winner_user_id IS NOT NULL ORDER BY closed_at DESC LIMIT 1");
    if (!$row) {
        return '<p>' . esc_html__('No winner announcements yet.', 'bonus-hunt-guesser') . '</p>';
    }
    $winner_name = $row->winner_user_id ? get_the_author_meta('display_name', (int)$row->winner_user_id) : '';
    $html  = '<div class="bhg-winner-notice">';
    $html .= '<p><strong>' . esc_html__('Latest Winner', 'bonus-hunt-guesser') . ':</strong> '
          . esc_html($row->title) . ' — ' . esc_html($winner_name) . '</p>';
    $html .= '<p><strong>' . esc_html__('Final Balance', 'bonus-hunt-guesser') . ':</strong> '
          . esc_html(number_format_i18n((float)$row->final_balance, 2)) . '</p>';
    if (!empty($row->winner_diff)) {
        $html .= '<p><strong>' . esc_html__('Difference', 'bonus-hunt-guesser') . ':</strong> '
              . esc_html(number_format_i18n((float)$row->winner_diff, 2)) . '</p>';
    }
    if (!empty($row->closed_at)) {
        $html .= '<p><strong>' . esc_html__('Closed', 'bonus-hunt-guesser') . ':</strong> '
              . esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->closed_at)) . '</p>';
    }
    $html .= '</div>';
    return $html;
}

}
