<?php
if (!defined('ABSPATH')) exit;

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$t_table = $wpdb->prefix . 'bhg_tournaments';
$r_table = $wpdb->prefix . 'bhg_tournament_results';

$periods = array('weekly' => __('Weekly', 'bonus-hunt-guesser'),
                 'monthly' => __('Monthly', 'bonus-hunt-guesser'),
                 'yearly' => __('Yearly', 'bonus-hunt-guesser'));
?>
<div class="wrap">
    <h1><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>

    <p><?php esc_html_e('View current standings by period.', 'bonus-hunt-guesser'); ?></p>

    <?php foreach ($periods as $key => $label): ?>
        <h2><?php echo esc_html($label); ?></h2>
        <?php
        // Fetch top 50 standings for this period, if table exists
        $has_table = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $r_table)) === $r_table);
        if ($has_table) {
            // Join with WP users table to retrieve display name / username
            $users_table = $wpdb->users;
            $rows = $wpdb->get_results($wpdb->prepare("SELECT u.display_name AS username, r.wins FROM {$r_table} r JOIN {$users_table} u ON r.user_id = u.ID WHERE r.period = %s ORDER BY r.wins DESC, u.display_name ASC LIMIT 50", $key));
        } else {
            $rows = array();
        }
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Position', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Username', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Wins', 'bonus-hunt-guesser'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="3"><?php esc_html_e('No data available.', 'bonus-hunt-guesser'); ?></td></tr>
                <?php else: ?>
                    <?php $rank = 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo intval($rank); ?></td>
                            <td><?php echo esc_html($row->username); ?></td>
                            <td><?php echo intval($row->wins); ?></td>
                        </tr>
                        <?php $rank++; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>
