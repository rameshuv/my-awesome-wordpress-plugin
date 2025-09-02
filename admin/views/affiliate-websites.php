<?php
if (!defined('ABSPATH')) exit;

// Check capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_affiliate_websites';
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
$edit = null;

if (!empty($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
}
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Affiliate Websites', 'bonus-hunt-guesser'); ?></h1>
    <div class="bhg-grid">
        <div class="bhg-col">
            <h2><?php echo $edit ? esc_html__('Edit Site', 'bonus-hunt-guesser') : esc_html__('Add New Site', 'bonus-hunt-guesser'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bhg_save_affiliate">
                <?php wp_nonce_field('bhg_save_affiliate_nonce', 'bhg_nonce'); ?>
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?php echo intval($edit->id); ?>" />
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php esc_html_e('Name', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <input type="text" name="name" id="name" value="<?php echo $edit ? esc_attr($edit->name) : ''; ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php esc_html_e('Slug', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <input type="text" name="slug" id="slug" value="<?php echo $edit ? esc_attr($edit->slug) : ''; ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url"><?php esc_html_e('URL', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <input type="url" name="url" id="url" value="<?php echo $edit ? esc_url($edit->url) : ''; ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button($edit ? __('Update Site', 'bonus-hunt-guesser') : __('Create Site', 'bonus-hunt-guesser')); ?>
            </form>
        </div>
        <div class="bhg-col">
            <h2><?php esc_html_e('Existing Sites', 'bonus-hunt-guesser'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Name', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Slug', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('URL', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)) : ?>
                        <?php foreach ($rows as $r) : ?>
                            <tr>
                                <td><?php echo intval($r->id); ?></td>
                                <td><?php echo esc_html($r->name); ?></td>
                                <td><?php echo esc_html($r->slug); ?></td>
                                <td><?php echo esc_url($r->url); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=bhg-affiliates&edit=' . intval($r->id))); ?>" class="button">
                                        <?php esc_html_e('Edit', 'bonus-hunt-guesser'); ?>
                                    </a>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="bhg_delete_affiliate" />
                                        <?php wp_nonce_field('bhg_delete_affiliate_nonce', 'bhg_nonce'); ?>
                                        <input type="hidden" name="id" value="<?php echo intval($r->id); ?>" />
                                        <button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(__('Delete this site?', 'bonus-hunt-guesser')); ?>')">
                                            <?php esc_html_e('Delete', 'bonus-hunt-guesser'); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No affiliate websites found.', 'bonus-hunt-guesser'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>