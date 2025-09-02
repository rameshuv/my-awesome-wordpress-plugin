<?php
if (!defined('ABSPATH')) exit;

// Check capabilities and nonce for security
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_ads';
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$edit = null;

if (!empty($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit = $wpdb->get_results("SELECT * FROM `" . $edit_id . "`");
}

// Handle form submission with nonce verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_nonce'])) {
    if (!wp_verify_nonce($_POST['bhg_nonce'], 'bhg_action')) {
        wp_die('Security check failed');
    }
    
    // Process form data here (this would typically be in a separate handler)
}
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Advertising', 'bonus-hunt-guesser'); ?></h1>
    <div class="bhg-grid">
        <div class="bhg-col">
            <h2><?php echo $edit ? esc_html__('Edit Ad', 'bonus-hunt-guesser') : esc_html__('Create Ad', 'bonus-hunt-guesser'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bhg_save_ad" />
                <?php wp_nonce_field('bhg_save_ad', 'bhg_save_ad_nonce'); ?>
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?php echo intval($edit->id); ?>" />
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Message', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <textarea name="message" rows="3" class="large-text"><?php 
                                echo $edit ? esc_textarea($edit->message) : ''; 
                            ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Link (optional)', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <input type="url" name="link" class="regular-text" value="<?php 
                                echo $edit ? esc_attr($edit->link) : ''; 
                            ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Placement', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <select name="placement">
                                <option value="footer" <?php 
                                    selected(($edit->placement ?? '') === 'footer'); 
                                ?>><?php esc_html_e('Footer', 'bonus-hunt-guesser'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Visibility', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <select name="visibility">
                                <option value="all" <?php 
                                    selected(($edit->visibility ?? '') === 'all'); 
                                ?>><?php esc_html_e('All', 'bonus-hunt-guesser'); ?></option>
                                <option value="logged_in" <?php 
                                    selected(($edit->visibility ?? '') === 'logged_in'); 
                                ?>><?php esc_html_e('Logged-in', 'bonus-hunt-guesser'); ?></option>
                                <option value="guests" <?php 
                                    selected(($edit->visibility ?? '') === 'guests'); 
                                ?>><?php esc_html_e('Guests', 'bonus-hunt-guesser'); ?></option>
                                <option value="affiliates" <?php 
                                    selected(($edit->visibility ?? '') === 'affiliates'); 
                                ?>><?php esc_html_e('Affiliates', 'bonus-hunt-guesser'); ?></option>
                                <option value="non_affiliates" <?php 
                                    selected(($edit->visibility ?? '') === 'non_affiliates'); 
                                ?>><?php esc_html_e('Non-Affiliates', 'bonus-hunt-guesser'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Active', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="active" value="1" <?php 
                                    checked(($edit->active ?? 1) == 1); 
                                ?> /> 
                                <?php esc_html_e('Enabled', 'bonus-hunt-guesser'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button($edit ? __('Update Ad', 'bonus-hunt-guesser') : __('Create Ad', 'bonus-hunt-guesser')); ?>
            </form>
        </div>
        <div class="bhg-col">
            <h2><?php esc_html_e('Existing Ads', 'bonus-hunt-guesser'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php esc_html_e('Message', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Visibility', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Active', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo intval($r->id); ?></td>
                            <td><?php echo esc_html(wp_trim_words(strip_tags($r->message), 12)); ?></td>
                            <td><?php echo esc_html($r->visibility); ?></td>
                            <td><?php echo $r->active ? esc_html__('Yes', 'bonus-hunt-guesser') : esc_html__('No', 'bonus-hunt-guesser'); ?></td>
                            <td>
                                <a class="button" href="<?php 
                                    echo esc_url(admin_url('admin.php?page=bhg-ads&edit=' . intval($r->id))); 
                                ?>"><?php esc_html_e('Edit', 'bonus-hunt-guesser'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>