<?php
if (!defined('ABSPATH')) exit;
BHG_Utils::require_cap();
global $wpdb;
$table = $wpdb->prefix . 'bhg_translations';
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY t_key ASC");
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Translations', 'bonus-hunt-guesser'); ?></h1>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="bhg_save_translation" />
        <?php BHG_Utils::nonce_field('bhg_save_translation'); ?>
        <table class="form-table">
            <tr><th><?php esc_html_e('Key','bonus-hunt-guesser'); ?></th><td><input type="text" name="t_key" class="regular-text" required></td></tr>
            <tr><th><?php esc_html_e('Value','bonus-hunt-guesser'); ?></th><td><textarea name="t_value" rows="2" class="large-text"></textarea></td></tr>
        </table>
        <?php submit_button(__('Save / Update','bonus-hunt-guesser')); ?>
    </form>
    <h2><?php esc_html_e('Existing Translations','bonus-hunt-guesser'); ?></h2>
    <table class="widefat fixed striped">
        <thead><tr><th><?php esc_html_e('Key','bonus-hunt-guesser'); ?></th><th><?php esc_html_e('Value','bonus-hunt-guesser'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?php echo esc_html($r->t_key); ?></td><td><?php echo esc_html(wp_trim_words($r->t_value, 18)); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
