<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get tournaments
$tournaments = BHG_Models::get_tournaments();
?>
<div class="wrap">
    <h1><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>
    
    <?php if (isset($_GET['bhg_rebuilt'])): ?>
        <div class="notice notice-success"><p><?php esc_html_e('Tournament standings rebuilt successfully.', 'bonus-hunt-guesser'); ?></p></div>
    <?php endif; ?>

    <div class="bhg-admin-header">
        <a href="<?php echo admin_url('admin.php?page=bhg-tournaments&action=create'); ?>" class="page-title-action">
            <?php esc_html_e('Add New Tournament', 'bonus-hunt-guesser'); ?>
        </a>
        
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 10px;">
            <?php wp_nonce_field('bhg_rebuild_tournaments'); ?>
            <input type="hidden" name="action" value="bhg_rebuild_tournaments" />
            <button class="button button-primary"><?php esc_html_e('Rebuild Tournament Standings', 'bonus-hunt-guesser'); ?></button>
        </form>
    </div>
    
    <?php if (!empty($tournaments)) : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Period', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Period Key', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Participants', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tournaments as $tournament) : ?>
            <tr>
                <td><?php echo esc_html($tournament->title); ?></td>
                <td><?php echo esc_html($tournament->period); ?></td>
                <td><?php echo esc_html($tournament->period_key); ?></td>
                <td>
                    <span class="bhg-status-badge bhg-status-<?php echo esc_attr($tournament->status); ?>">
                        <?php echo esc_html(ucfirst($tournament->status)); ?>
                    </span>
                </td>
                <td><?php echo BHG_Models::get_tournament_participants_count($tournament->id); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=bhg-tournaments&action=edit&id=' . $tournament->id); ?>" class="button">
                        <?php esc_html_e('Edit', 'bonus-hunt-guesser'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=bhg-tournaments&action=delete&id=' . $tournament->id); ?>" class="button button-danger" onclick="return confirm('<?php esc_attr_e('Are you sure?', 'bonus-hunt-guesser'); ?>')">
                        <?php esc_html_e('Delete', 'bonus-hunt-guesser'); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else : ?>
    <p><?php esc_html_e('No tournaments found.', 'bonus-hunt-guesser'); ?></p>
    <?php endif; ?>

    <h2><?php esc_html_e('Recent Tournament Results', 'bonus-hunt-guesser'); ?></h2>
    <?php
    global $wpdb;
    $t_table = $wpdb->prefix . 'bhg_tournaments';
    $r_table = $wpdb->prefix . 'bhg_tournament_results';

    $periods = ['weekly','monthly','yearly'];
    foreach ($periods as $p):
        $tournaments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_table WHERE period=%s ORDER BY period_key DESC, id DESC LIMIT 3", $p));
        if (!$tournaments) continue;
        echo '<h3>' . esc_html(ucfirst($p)) . '</h3>';
        foreach ($tournaments as $t):
            echo '<h4>' . esc_html($t->title) . '</h4>';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT r.wins, r.user_id, u.user_login
                 FROM $r_table r JOIN {$wpdb->users} u ON u.ID=r.user_id
                 WHERE r.tournament_id=%d
                 ORDER BY r.wins DESC, u.user_login ASC
                 LIMIT 20", $t->id));
            if (!$rows){ echo '<p>' . esc_html__('No results yet.', 'bonus-hunt-guesser') . '</p>'; continue; }
            echo '<table class="widefat striped"><thead><tr><th>#</th><th>' . esc_html__('User','bonus-hunt-guesser') . '</th><th>' . esc_html__('Wins','bonus-hunt-guesser') . '</th></tr></thead><tbody>';
            $i=1; foreach ($rows as $r){
                echo '<tr><td>' . intval($i++) . '</td><td>' . esc_html($r->user_login) . '</td><td>' . intval($r->wins) . '</td></tr>';
            }
            echo '</tbody></table>';
        endforeach;
    endforeach;
    ?>
</div>