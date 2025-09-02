<?php
if (!defined('ABSPATH')) exit;

BHG_Utils::require_cap();
global $wpdb;

$table = $wpdb->prefix . 'bhg_ads';
$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
$edit = null;

if (!empty($_GET['edit'])) {
    $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", intval($_GET['edit'])));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_ad_nonce'])) {
    if (wp_verify_nonce($_POST['bhg_ad_nonce'], 'bhg_save_ad')) {
        $ad_data = array(
            'title' => sanitize_text_field($_POST['title']),
            'message' => wp_kses_post($_POST['message']),
            'placement' => sanitize_text_field($_POST['placement']),
            'visibility' => sanitize_text_field($_POST['visibility']),
            'active' => isset($_POST['active']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update existing ad
            $wpdb->update($table, $ad_data, array('id' => intval($_POST['id'])));
            echo '<div class="notice notice-success"><p>' . __('Ad updated successfully!', 'bonus-hunt-guesser') . '</p></div>';
        } else {
            // Create new ad
            $ad_data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $ad_data);
            echo '<div class="notice notice-success"><p>' . __('Ad created successfully!', 'bonus-hunt-guesser') . '</p></div>';
        }
        
        // Refresh data
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
    }
}
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Advertising Management', 'bonus-hunt-guesser'); ?></h1>
    
    <div class="bhg-admin-header">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bhg-ads')); ?>" class="page-title-action">
            <?php esc_html_e('Add New Advertisement', 'bonus-hunt-guesser'); ?>
        </a>
    </div>
    
    <div class="bhg-grid">
        <div class="bhg-col">
            <h2><?php echo $edit ? esc_html__('Edit Advertisement', 'bonus-hunt-guesser') : esc_html__('Create New Advertisement', 'bonus-hunt-guesser'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('bhg_save_ad', 'bhg_ad_nonce'); ?>
                
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?php echo intval($edit->id); ?>" />
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="ad_title"><?php esc_html_e('Title', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <input type="text" name="title" id="ad_title" class="regular-text" 
                                   value="<?php echo $edit ? esc_attr($edit->title) : ''; ?>" required />
                            <p class="description"><?php esc_html_e('A descriptive name for this advertisement', 'bonus-hunt-guesser'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ad_message"><?php esc_html_e('Message Content', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <?php
                            $message_content = $edit ? $edit->message : '';
                            wp_editor($message_content, 'ad_message', array(
                                'textarea_name' => 'message',
                                'media_buttons' => true,
                                'textarea_rows' => 10,
                                'teeny' => false,
                                'quicktags' => true
                            ));
                            ?>
                            <p class="description"><?php esc_html_e('Use the editor to create rich content with formatting, links, and images', 'bonus-hunt-guesser'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ad_placement"><?php esc_html_e('Placement', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <select name="placement" id="ad_placement">
                                <option value="header" <?php selected(($edit->placement ?? '') === 'header'); ?>>
                                    <?php esc_html_e('Header', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="sidebar" <?php selected(($edit->placement ?? '') === 'sidebar'); ?>>
                                    <?php esc_html_e('Sidebar', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="footer" <?php selected(($edit->placement ?? '') === 'footer'); ?>>
                                    <?php esc_html_e('Footer', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="after_content" <?php selected(($edit->placement ?? '') === 'after_content'); ?>>
                                    <?php esc_html_e('After Content', 'bonus-hunt-guesser'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="ad_visibility"><?php esc_html_e('Visibility', 'bonus-hunt-guesser'); ?></label></th>
                        <td>
                            <select name="visibility" id="ad_visibility">
                                <option value="all" <?php selected(($edit->visibility ?? '') === 'all'); ?>>
                                    <?php esc_html_e('All Users', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="logged_in" <?php selected(($edit->visibility ?? '') === 'logged_in'); ?>>
                                    <?php esc_html_e('Logged-in Users Only', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="guests" <?php selected(($edit->visibility ?? '') === 'guests'); ?>>
                                    <?php esc_html_e('Guests Only', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="affiliates" <?php selected(($edit->visibility ?? '') === 'affiliates'); ?>>
                                    <?php esc_html_e('Affiliates Only', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="non_affiliates" <?php selected(($edit->visibility ?? '') === 'non_affiliates'); ?>>
                                    <?php esc_html_e('Non-Affiliates Only', 'bonus-hunt-guesser'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="active" value="1" 
                                    <?php checked(($edit->active ?? 1) == 1); ?> />
                                <?php esc_html_e('Active', 'bonus-hunt-guesser'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Uncheck to disable this advertisement', 'bonus-hunt-guesser'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <?php submit_button($edit ? __('Update Advertisement', 'bonus-hunt-guesser') : __('Create Advertisement', 'bonus-hunt-guesser'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bhg-ads')); ?>" class="button">
                        <?php esc_html_e('Cancel', 'bonus-hunt-guesser'); ?>
                    </a>
                </p>
            </form>
        </div>
        
        <div class="bhg-col">
            <h2><?php esc_html_e('Existing Advertisements', 'bonus-hunt-guesser'); ?></h2>
            
            <?php if (empty($rows)) : ?>
                <p><?php esc_html_e('No advertisements found.', 'bonus-hunt-guesser'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Title', 'bonus-hunt-guesser'); ?></th>
                            <th><?php esc_html_e('Placement', 'bonus-hunt-guesser'); ?></th>
                            <th><?php esc_html_e('Visibility', 'bonus-hunt-guesser'); ?></th>
                            <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                            <th><?php esc_html_e('Shortcode', 'bonus-hunt-guesser'); ?></th>
                            <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $ad): ?>
                            <tr>
                                <td><?php echo esc_html($ad->title); ?></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $ad->placement))); ?></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $ad->visibility))); ?></td>
                                <td>
                                    <span class="bhg-status-badge bhg-status-<?php echo $ad->active ? 'active' : 'inactive'; ?>">
                                        <?php echo $ad->active ? esc_html__('Active', 'bonus-hunt-guesser') : esc_html__('Inactive', 'bonus-hunt-guesser'); ?>
                                    </span>
                                </td>
                                <td><code>[bhg_ad id="<?php echo intval($ad->id); ?>"]</code></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=bhg-ads&edit=' . intval($ad->id))); ?>" class="button">
                                        <?php esc_html_e('Edit', 'bonus-hunt-guesser'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=bhg_delete_ad&id=' . intval($ad->id) . '&_wpnonce=' . wp_create_nonce('bhg_delete_ad'))); ?>" class="button button-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this advertisement?', 'bonus-hunt-guesser'); ?>')">
                                        <?php esc_html_e('Delete', 'bonus-hunt-guesser'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.bhg-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.bhg-col {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.bhg-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.bhg-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.bhg-status-active {
    background: #00a32a;
    color: white;
}

.bhg-status-inactive {
    background: #d63638;
    color: white;
}

.button-danger {
    background: #d63638;
    border-color: #d63638;
    color: white;
}

.button-danger:hover {
    background: #e65054;
    border-color: #e65054;
    color: white;
}

@media (max-width: 1200px) {
    .bhg-grid {
        grid-template-columns: 1fr;
    }
}
</style>