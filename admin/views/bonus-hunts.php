<?php
if (!defined('ABSPATH')) exit;

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_bonus_hunts';
$aff_table = $wpdb->prefix . 'bhg_affiliate_websites';

// Get affiliate sites with prepared statement
$affs = $wpdb->get_results("SELECT id, name FROM {$aff_table} ORDER BY name ASC");

// Get bonus hunts with prepared statement
$rows = $wpdb->get_results("SELECT * FROM `" . $table . "`");

$edit = null;
if (!empty($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit = $wpdb->get_results("SELECT * FROM `" . $table . "`");
}
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Bonus Hunts', 'bonus-hunt-guesser'); ?></h1>
    <div class="bhg-grid">
        <div class="bhg-col">
            <h2><?php echo $edit ? esc_html__('Edit Hunt', 'bonus-hunt-guesser') : esc_html__('Add New Hunt', 'bonus-hunt-guesser'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="bhg_save_hunt" />
                <?php wp_nonce_field('bhg_save_hunt', 'bhg_save_hunt_nonce'); ?>
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?php echo intval($edit->id); ?>" />
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Title', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <input type="text" name="title" value="<?php echo esc_attr($edit->title ?? ''); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Starting Balance (€)', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <input type="number" step="0.01" min="0" name="starting_balance" value="<?php echo esc_attr($edit->starting_balance ?? '0'); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Number of Bonuses', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <input type="number" min="0" name="num_bonuses" value="<?php echo esc_attr($edit->num_bonuses ?? '0'); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Prizes', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <textarea name="prizes" rows="3" class="large-text"><?php echo esc_textarea($edit->prizes ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Affiliate Site', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <select name="affiliate_site_id">
                                <option value=""><?php esc_html_e('None', 'bonus-hunt-guesser'); ?></option>
                                <?php foreach ($affs as $a): ?>
                                    <option value="<?php echo intval($a->id); ?>" <?php selected(($edit->affiliate_site_id ?? '') == $a->id); ?>>
                                        <?php echo esc_html($a->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                        <td>
                            <select name="status">
                                <option value="open" <?php selected(($edit->status ?? '') === 'open'); ?>>
                                    <?php esc_html_e('Open', 'bonus-hunt-guesser'); ?>
                                </option>
                                <option value="closed" <?php selected(($edit->status ?? '') === 'closed'); ?>>
                                    <?php esc_html_e('Closed', 'bonus-hunt-guesser'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button($edit ? __('Update Hunt', 'bonus-hunt-guesser') : __('Create Hunt', 'bonus-hunt-guesser')); ?>
            </form>
        </div>
        <div class="bhg-col">
            <h2><?php esc_html_e('Existing Hunts', 'bonus-hunt-guesser'); ?></h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th><?php esc_html_e('Title', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo intval($r->id); ?></td>
                            <td><?php echo esc_html($r->title); ?></td>
                            <td><?php echo esc_html($r->status); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bhg-bonus-hunts&edit=' . intval($r->id))); ?>" class="button">
                                    <?php esc_html_e('Edit', 'bonus-hunt-guesser'); ?>
                                </a>
                                <?php if ($r->status !== 'closed'): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-right:8px;">
                                        <input type="hidden" name="action" value="bhg_close_hunt" />
                                        <?php wp_nonce_field('bhg_close_hunt', 'bhg_close_hunt_nonce'); ?>
                                        <input type="hidden" name="id" value="<?php echo intval($r->id); ?>" />
                                        <input type="number" step="0.01" min="0" max="100000000" name="final_balance" 
                                               placeholder="<?php esc_attr_e('Final Balance €', 'bonus-hunt-guesser'); ?>" required style="width:140px;" />
                                        <button class="button button-primary" onclick="return confirm('<?php echo esc_attr__('Close this hunt and send results?', 'bonus-hunt-guesser'); ?>')">
                                            <?php esc_html_e('Close & Announce', 'bonus-hunt-guesser'); ?>
                                        </button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="bhg_close_hunt" />
                                        <?php wp_nonce_field('bhg_close_hunt', 'bhg_close_hunt_nonce'); ?>
                                        <input type="hidden" name="id" value="<?php echo intval($r->id); ?>" />
                                        <button class="button button-secondary" onclick="return confirm('<?php echo esc_attr__('Close this hunt?', 'bonus-hunt-guesser'); ?>')">
                                            <?php esc_html_e('Close', 'bonus-hunt-guesser'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>