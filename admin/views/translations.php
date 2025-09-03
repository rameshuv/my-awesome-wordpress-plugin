<?php
if (!defined('ABSPATH')) exit;

// Cap check
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_translations';

$message = '';
$error   = '';

// Handle form submission (create/update by key)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_save_translation'])) {
    // Nonce
    if (!isset($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], 'bhg_save_translation_action')) {
        wp_die(esc_html__('Security check failed.', 'bonus-hunt-guesser'));
    }

    // Sanitize
    $t_key   = isset($_POST['t_key'])   ? sanitize_text_field(wp_unslash($_POST['t_key']))   : '';
    $t_value = isset($_POST['t_value']) ? sanitize_textarea_field(wp_unslash($_POST['t_value'])) : '';

    if ($t_key === '') {
        $error = esc_html__('Key field is required.', 'bonus-hunt-guesser');
    } else {
        // Save/replace by key (MySQL REPLACE INTO)
        $result = $wpdb->replace(
            $table,
            array(
                't_key'   => $t_key,
                't_value' => $t_value,
            ),
            array('%s','%s')
        );

        if ($result !== false) {
            $message = esc_html__('Translation saved successfully.', 'bonus-hunt-guesser');
        } else {
            $error = esc_html__('Error saving translation.', 'bonus-hunt-guesser');
        }
    }
}

// Fetch rows
$rows = $wpdb->get_results("SELECT * FROM `{$table}` ORDER BY t_key ASC");
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Translations', 'bonus-hunt-guesser'); ?></h1>

    <p class="description">
        <em>
            <?php esc_html_e('Use this page to override any text string shown by the Bonus Hunt Guesser plugin. Changes are stored in the database and applied site-wide.', 'bonus-hunt-guesser'); ?>
        </em>
    </p>
    <ul class="ul-disc">
        <li><?php esc_html_e('Enter a key and value, then click Save / Update. Leaving the value empty will effectively clear the override for that key.', 'bonus-hunt-guesser'); ?></li>
        <li><?php esc_html_e('Examples of keys: email_winner_subject, email_winner_message, leaderboard_title.', 'bonus-hunt-guesser'); ?></li>
    </ul>

    <?php if (!empty($message)) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <?php if (!empty($error)) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>

    <form method="post" action="">
        <input type="hidden" name="bhg_save_translation" value="1" />
        <?php wp_nonce_field('bhg_save_translation_action', 'bhg_nonce'); ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="t_key"><?php esc_html_e('Key', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="text"
                               name="t_key"
                               id="t_key"
                               class="regular-text"
                               required
                               value="<?php echo isset($_POST['t_key']) ? esc_attr(wp_unslash($_POST['t_key'])) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="t_value"><?php esc_html_e('Value', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <textarea name="t_value"
                                  id="t_value"
                                  rows="3"
                                  class="large-text"><?php
                            echo isset($_POST['t_value']) ? esc_textarea(wp_unslash($_POST['t_value'])) : '';
                        ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Leave blank to remove the override and fall back to the plugin’s default text.', 'bonus-hunt-guesser'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
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
            <?php if (!empty($rows)) : ?>
                <?php foreach ($rows as $row) : 
                    $key   = isset($row->t_key)   ? $row->t_key   : '';
                    $value = isset($row->t_value) ? $row->t_value : '';
                ?>
                    <tr>
                        <td><code><?php echo esc_html($key); ?></code></td>
                        <td><?php echo esc_html($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="2"><?php esc_html_e('No translations found.', 'bonus-hunt-guesser'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
