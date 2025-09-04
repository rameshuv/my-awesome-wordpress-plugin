<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php echo esc_html__('Translations', 'bonus-hunt-guesser'); ?></h1>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('bhg_save_translation', 'bhg_nonce'); ?>
        <input type="hidden" name="action" value="bhg_save_translation">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="bhg_tkey"><?php echo esc_html__('Key', 'bonus-hunt-guesser'); ?></label></th>
                <td><input class="regular-text" name="tkey" id="bhg_tkey" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="bhg_tval"><?php echo esc_html__('Value', 'bonus-hunt-guesser'); ?></label></th>
                <td><textarea class="large-text" name="tval" id="bhg_tval" rows="3"></textarea></td>
            </tr>
        </table>
        <?php submit_button(__('Save', 'bonus-hunt-guesser')); ?>
    </form>
    <hr>
    <h2><?php echo esc_html__('Existing Strings', 'bonus-hunt-guesser'); ?></h2>
    <table class="widefat striped">
        <thead><tr><th><?php echo esc_html__('Key','bonus-hunt-guesser'); ?></th><th><?php echo esc_html__('Value','bonus-hunt-guesser'); ?></th><th><?php echo esc_html__('Actions','bonus-hunt-guesser'); ?></th></tr></thead>
        <tbody>
            <?php
            global $wpdb;
            $rows = $wpdb->get_results("SELECT id, `key`, `value` FROM {$wpdb->prefix}bhg_translations ORDER BY `key` ASC");
            if ($rows):
                foreach ($rows as $r): ?>
                <tr>
                    <td><?php echo esc_html($r->key); ?></td>
                    <td><?php echo esc_html($r->value); ?></td>
                    <td>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                            <?php wp_nonce_field('bhg_delete_translation', 'bhg_nonce'); ?>
                            <input type="hidden" name="action" value="bhg_delete_translation">
                            <input type="hidden" name="id" value="<?php echo (int)$r->id; ?>">
                            <button class="button-link-delete" onclick="return confirm('<?php echo esc_attr__('Delete this translation?', 'bonus-hunt-guesser'); ?>')"><?php echo esc_html__('Delete','bonus-hunt-guesser'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="3"><?php echo esc_html__('No translations yet. Add some above.', 'bonus-hunt-guesser'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
