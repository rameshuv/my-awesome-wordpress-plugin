<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>

    <?php if (isset($_GET['bhg_rebuilt']) && sanitize_text_field($_GET['bhg_rebuilt']) === '1'): ?>
        <div class="notice notice-success"><p><?php esc_html_e('Tournament standings rebuilt successfully.', 'bonus-hunt-guesser'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:16px 0;">
        <?php wp_nonce_field('bhg_rebuild_tournaments_action', 'bhg_rebuild_tournaments_nonce'); ?>
        <input type="hidden" name="action" value="bhg_rebuild_tournaments" />
        <button class="button button-primary"><?php esc_html_e('Rebuild Tournament Standings', 'bonus-hunt-guesser'); ?></button>
    </form>

    <?php
    global $wpdb;
    $t_table = $wpdb->prefix . 'bhg_tournaments';
    $r_table = $wpdb->prefix . 'bhg_tournament_results';

    $periods = ['weekly', 'monthly', 'yearly'];
    foreach ($periods as $p):
        $tournaments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_table WHERE period = %s ORDER BY period_key DESC, id DESC LIMIT 3", $p));
        if (!$tournaments) continue;
        echo '<h2>' . esc_html(ucfirst($p)) . '</h2>';
        foreach ($tournaments as $t):
            echo '<h3>' . esc_html($t->title) . '</h3>';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT r.wins, r.user_id, u.user_login
                 FROM $r_table r 
                 JOIN {$wpdb->users} u ON u.ID = r.user_id
                 WHERE r.tournament_id = %d
                 ORDER BY r.wins DESC, u.user_login ASC
                 LIMIT 20",
                $t->id
            ));
            
            if (!$rows) { 
                echo '<p>' . esc_html__('No results yet.', 'bonus-hunt-guesser') . '</p>'; 
                continue; 
            }
            
            echo '<table class="widefat striped"><thead><tr><th>#</th><th>' . esc_html__('User', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th></tr></thead><tbody>';
            $i = 1; 
            foreach ($rows as $r) {
                echo '<tr><td>' . intval($i++) . '</td><td>' . esc_html($r->user_login) . '</td><td>' . intval($r->wins) . '</td></tr>';
            }
            echo '</tbody></table>';
        endforeach;
    endforeach;
    ?>
</div>