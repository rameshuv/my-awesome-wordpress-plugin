<?php
if (!defined('ABSPATH')) exit;

/** @var wpdb $wpdb */
global $wpdb;

$hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
$aff_table   = $wpdb->prefix . 'bhg_affiliate_websites';

// Load affiliate sites for the dropdown
$affiliates = $wpdb->get_results("SELECT id, name FROM `{$aff_table}` ORDER BY name ASC");

// If editing, load the hunt
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editing = null;
if ($edit_id > 0) {
    $editing = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$hunts_table}` WHERE id=%d", $edit_id));
}

// List of hunts (simple)
$hunts = $wpdb->get_results("SELECT * FROM `{$hunts_table}` ORDER BY id DESC");
?>
<div class="wrap">
    <h1><?php echo esc_html__('Bonus Hunts', 'bonus-hunt-guesser'); ?></h1>

    <?php if (isset($_GET['updated'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Saved.', 'bonus-hunt-guesser'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['closed'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html__('Hunt closed and results processed.', 'bonus-hunt-guesser'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'final_balance_required'): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html__('Final balance is required to close a hunt.', 'bonus-hunt-guesser'); ?></p></div>
    <?php endif; ?>

    <h2 style="margin-top:1.5rem;"><?php echo $editing ? esc_html__('Edit Bonus Hunt', 'bonus-hunt-guesser') : esc_html__('Create Bonus Hunt', 'bonus-hunt-guesser'); ?></h2>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:900px;background:#fff;border:1px solid #e5e7eb;padding:20px;border-radius:8px;">
        <input type="hidden" name="action" value="bhg_save_hunt">
        <?php wp_nonce_field('bhg_save_hunt', 'bhg_save_hunt_nonce'); ?>
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing->id; ?>">
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="bhg-title"><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="text" id="bhg-title" name="title" class="regular-text" required
                               value="<?php echo esc_attr($editing ? $editing->title : ''); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bhg-starting"><?php echo esc_html__('Starting Balance (€)', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="number" id="bhg-starting" name="starting_balance" step="0.01" min="0" class="regular-text"
                               value="<?php echo esc_attr($editing ? (float)$editing->starting_balance : '0'); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bhg-bonuses"><?php echo esc_html__('Number of Bonuses', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="number" id="bhg-bonuses" name="num_bonuses" min="0" class="regular-text"
                               value="<?php echo esc_attr($editing ? (int)$editing->num_bonuses : '0'); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bhg-prizes"><?php echo esc_html__('Prizes (HTML allowed)', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <textarea id="bhg-prizes" name="prizes" rows="4" class="large-text" style="border:1px solid #cbd5e1;border-radius:6px;"><?php echo $editing ? wp_kses_post($editing->prizes) : ''; ?></textarea>
                        <p class="description"><?php echo esc_html__('You can include links, bold text, etc.', 'bonus-hunt-guesser'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bhg-status"><?php echo esc_html__('Status', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <select id="bhg-status" name="status">
                            <?php
                            $cur = $editing ? sanitize_text_field($editing->status) : 'open';
                            ?>
                            <option value="open"  <?php selected($cur, 'open');  ?>><?php echo esc_html__('Open', 'bonus-hunt-guesser'); ?></option>
                            <option value="closed"<?php selected($cur, 'closed'); ?>><?php echo esc_html__('Closed', 'bonus-hunt-guesser'); ?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="bhg-affiliate-site"><?php echo esc_html__('Affiliate Site (per-hunt)', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <select id="bhg-affiliate-site" name="affiliate_site_id">
                            <?php
                            $sel = $editing && isset($editing->affiliate_site_id) ? (int)$editing->affiliate_site_id : 0;
                            ?>
                            <option value=""><?php echo esc_html__('— None —', 'bonus-hunt-guesser'); ?></option>
                            <?php if ($affiliates): foreach ($affiliates as $a): ?>
                                <option value="<?php echo (int)$a->id; ?>" <?php selected($sel, (int)$a->id); ?>>
                                    <?php echo esc_html($a->name); ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Used for per-site affiliate context. Leaderboard affiliate dot will reflect the site selected here.', 'bonus-hunt-guesser'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p style="margin-top:20px;">
            <button type="submit" class="button button-primary" style="padding:.6rem 1.1rem;">
                <?php echo esc_html($editing ? __('Update Bonus Hunt', 'bonus-hunt-guesser') : __('Create Bonus Hunt', 'bonus-hunt-guesser')); ?>
            </button>
            <?php if ($editing): ?>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=bhg-bonus-hunts')); ?>" style="margin-left:8px;">
                    <?php echo esc_html__('Cancel', 'bonus-hunt-guesser'); ?>
                </a>
            <?php endif; ?>
        </p>
    </form>

    <hr style="margin:2rem 0;">

    <h2><?php echo esc_html__('Existing Hunts', 'bonus-hunt-guesser'); ?></h2>
    <?php if (!$hunts): ?>
        <p><?php echo esc_html__('No bonus hunts found.', 'bonus-hunt-guesser'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('#', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('Start Balance', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('# Bonuses', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('Affiliate Site', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('Status', 'bonus-hunt-guesser'); ?></th>
                    <th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hunts as $h): ?>
                    <tr>
                        <td><?php echo (int)$h->id; ?></td>
                        <td><?php echo esc_html($h->title); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float)$h->starting_balance, 2)); ?></td>
                        <td><?php echo (int)$h->num_bonuses; ?></td>
                        <td>
                            <?php
                            if (!empty($h->affiliate_site_id)) {
                                $name = $wpdb->get_var($wpdb->prepare("SELECT name FROM `{$aff_table}` WHERE id=%d", (int)$h->affiliate_site_id));
                                echo $name ? esc_html($name) : esc_html__('(unknown)', 'bonus-hunt-guesser');
                            } else {
                                echo esc_html__('—', 'bonus-hunt-guesser');
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($h->status); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page'=>'bhg-bonus-hunts','id'=>(int)$h->id], admin_url('admin.php'))); ?>">
                                <?php echo esc_html__('Edit', 'bonus-hunt-guesser'); ?>
                            </a>
                            <?php if ($h->status !== 'closed'): ?>
                                <!-- Close Hunt -->
                                <button class="button button-small" type="button" onclick="document.getElementById('bhg-close-<?php echo (int)$h->id; ?>').style.display='block';">
                                    <?php echo esc_html__('Close', 'bonus-hunt-guesser'); ?>
                                </button>
                                <div id="bhg-close-<?php echo (int)$h->id; ?>" style="display:none;margin-top:8px;">
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="bhg_close_hunt">
                                        <?php wp_nonce_field('bhg_close_hunt','bhg_close_hunt_nonce'); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$h->id; ?>">
                                        <label>
                                            <?php echo esc_html__('Final Balance (€):', 'bonus-hunt-guesser'); ?>
                                            <input type="number" step="0.01" min="0" name="final_balance" required>
                                        </label>
                                        <button type="submit" class="button button-primary" style="margin-left:8px;">
                                            <?php echo esc_html__('Confirm Close', 'bonus-hunt-guesser'); ?>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="dashicons dashicons-yes" title="<?php echo esc_attr__('Closed', 'bonus-hunt-guesser'); ?>"></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="description" style="margin-top:1rem;">
        <?php
        // Explain tournaments behavior for admins
        echo esc_html__(
            'Note: You do not select a tournament here. Weekly/Monthly/Yearly tournaments are created automatically and wins are tallied when a hunt is closed.',
            'bonus-hunt-guesser'
        );
        ?>
    </p>
</div>
