<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

// Verify nonce for any form submissions
if (isset($_POST['bhg_action']) && !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_action')) {
    wp_die(__('Security check failed', 'bonus-hunt-guesser'));
}
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Database Tools', 'bonus-hunt-guesser'); ?></h1>
    <p><?php esc_html_e('Tables are automatically created on activation. If you need to reinstall them, deactivate and activate the plugin again.', 'bonus-hunt-guesser'); ?></p>
    
    <?php if (isset($_POST['bhg_db_cleanup']) && wp_verify_nonce($_POST['bhg_nonce'], 'bhg_db_cleanup_action')) : ?>
        <div class="notice notice-success">
            <p><?php esc_html_e('Database cleanup completed successfully.', 'bonus-hunt-guesser'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('bhg_db_cleanup_action', 'bhg_nonce'); ?>
        <input type="hidden" name="bhg_action" value="db_cleanup">
        <p>
            <input type="submit" name="bhg_db_cleanup" class="button button-secondary" value="<?php esc_attr_e('Run Database Cleanup', 'bonus-hunt-guesser'); ?>" 
                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to run database cleanup? This action cannot be undone.', 'bonus-hunt-guesser'); ?>')">
        </p>
        <p class="description">
            <?php esc_html_e('Note: This will remove any demo data and reset tables to their initial state.', 'bonus-hunt-guesser'); ?>
        </p>
    </form>
    
    <h2><?php esc_html_e('Current Database Status', 'bonus-hunt-guesser'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Table Name', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Rows', 'bonus-hunt-guesser'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $tables = array(
                'wp_bhg_bonus_hunts',
                'wp_bhg_guesses',
                'wp_bhg_tournaments',
                'wp_bhg_tournament_results',
                'wp_bhg_translations',
                'wp_bhg_affiliate_websites',
                'wp_bhg_hunt_winners',
                'wp_bhg_ads'
            );
            
            foreach ($tables as $table) {
                $table_name = $wpdb->prefix . str_replace('wp_', '', $table);
                $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
                $row_count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM $table_name") : 0;
                
                echo '<tr>';
                echo '<td>' . esc_html($table_name) . '</td>';
                echo '<td><span class="' . ($exists ? 'dashicons dashicons-yes-alt" style="color: #46b450"' : 'dashicons dashicons-no" style="color: #dc3232"') . '"></span> ' . ($exists ? esc_html__('Exists', 'bonus-hunt-guesser') : esc_html__('Missing', 'bonus-hunt-guesser')) . '</td>';
                echo '<td>' . esc_html(number_format_i18n($row_count)) . '</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
    
    <h2><?php esc_html_e('Database Maintenance', 'bonus-hunt-guesser'); ?></h2>
    <form method="post" action="">
        <?php wp_nonce_field('bhg_db_optimize_action', 'bhg_nonce'); ?>
        <input type="hidden" name="bhg_action" value="db_optimize">
        <p>
            <input type="submit" name="bhg_db_optimize" class="button button-primary" value="<?php esc_attr_e('Optimize Database Tables', 'bonus-hunt-guesser'); ?>">
        </p>
    </form>
</div>