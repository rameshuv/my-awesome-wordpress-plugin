<?php
if (!defined('ABSPATH')) exit;

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_translations';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_save_translation'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['bhg_nonce'], 'bhg_save_translation_action')) {
        wp_die(__('Security check failed.', 'bonus-hunt-guesser'));
    }
    
    // Sanitize input
    $t_key = sanitize_text_field($_POST['t_key']);
    $t_value = sanitize_textarea_field($_POST['t_value']);
    
    // Validate input
    if (empty($t_key)) {
        $error = __('Key field is required.', 'bonus-hunt-guesser');
    } else {
        // Save to database
        $result = $wpdb->replace(
            $table,
            array(
                't_key' => $t_key,
                't_value' => $t_value
            ),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            $message = __('Translation saved successfully.', 'bonus-hunt-guesser');
        } else {
            $error = __('Error saving translation.', 'bonus-hunt-guesser');
        }
    }
}

// Get translations data
$rows = $wpdb->get_results("SELECT * FROM `" . $table . "`");
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Translations', 'bonus-hunt-guesser'); ?></h1>
<p class="description"><em><?php esc_html_e('Use this page to override any text string shown by the Bonus Hunt Guesser plugin. Changes are stored in the database and applied site-wide.', 'bonus-hunt-guesser'); ?></em></p>
<ul class="ul-disc">
    <li><?php esc_html_e('Click a key to edit its value. Leave blank to fall back to the default text.', 'bonus-hunt-guesser'); ?></li>
    <li><?php esc_html_e('Export/Import your translations when moving between environments.', 'bonus-hunt-guesser'); ?></li>
    <li><?php esc_html_e('Examples of keys: email_winner_subject, email_winner_message, leaderboard_title.', 'bonus-hunt-guesser'); ?></li>
</ul>
<!-- STAGE-5 TRANSLATIONS HELP -->

    
    <?php if (isset($message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <input type="hidden" name="bhg_save_translation" value="<?php esc_attr_e('1','bonus-hunt-guesser'); ?>" />
        <?php wp_nonce_field('bhg_save_translation_action', 'bhg_nonce'); ?>
        <table class="form-table">
            <tr>
                <th><label for="t_key"><?php esc_html_e('Key', 'bonus-hunt-guesser'); ?></label></th>
                <td>
                    <input type="text" name="t_key" id="t_key" class="regular-text" required 
                           value="<?php esc_attr_e('<?php echo isset($_POST['t_key']) ? esc_attr($_POST['t_key']) : ''; ?>','bonus-hunt-guesser'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="t_value"><?php esc_html_e('Value', 'bonus-hunt-guesser'); ?></label></th>
                <td>
                    <textarea name="t_value" id="t_value" rows="2" class="large-text"><?php 
                        echo isset($_POST['t_value']) ? esc_textarea($_POST['t_value']) : ''; 
                    ?></textarea>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Save / Update', 'bonus-hunt-guesser')); ?>
    </form>
    
    <h2><?php esc_html_e('Existing Translations', 'bonus-hunt-guesser'); ?></h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Key', 'bonus-hunt-guesser'); ?></th>
                <th><?php esc_html_e('Value', 'bonus-hunt-guesser'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo esc_html($r->t_key); ?></td>
                    <td><?php echo esc_html(wp_trim_words($r->t_value, 18)); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2"><?php esc_html_e('No translations found.', 'bonus-hunt-guesser'); ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>