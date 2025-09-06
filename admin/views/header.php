<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$bhg_db = new BHG_DB();
$message = '';

// Process form submissions
if (isset($_POST['bhg_action'])) {
    $action = sanitize_text_field($_POST['bhg_action']);
    
    // Verify nonce based on action
    $nonce_action = 'bhg_' . $action;
    if (!isset($_POST['bhg_nonce']) || !wp_verify_nonce($_POST['bhg_nonce'], $nonce_action)) {
        wp_die('Security check failed');
    }
    
    switch ($action) {
        case 'create_bonus_hunt':
            // Handle bonus hunt creation with proper sanitization
            $title = sanitize_text_field($_POST['title']);
            $starting_balance = floatval($_POST['starting_balance']);
            $num_bonuses = intval($_POST['num_bonuses']);
            $prizes = sanitize_textarea_field($_POST['prizes']);
            $status = sanitize_text_field($_POST['status']);
            $affiliate_site_id = isset($_POST['affiliate_site_id']) ? intval($_POST['affiliate_site_id']) : 0;
            
            // Save to database
            $result = $bhg_db->create_bonus_hunt(array(
                'title' => $title,
                'starting_balance' => $starting_balance,
                'num_bonuses' => $num_bonuses,
                'prizes' => $prizes,
                'status' => $status,
                'affiliate_site_id' => $affiliate_site_id,
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ));
            
            if ($result) {
                $message = 'success';
            } else {
                $message = 'error';
            }
            break;
            
        case 'update_bonus_hunt':
            // Handle bonus hunt update
            $id = intval($_POST['id']);
            $title = sanitize_text_field($_POST['title']);
            $starting_balance = floatval($_POST['starting_balance']);
            $num_bonuses = intval($_POST['num_bonuses']);
            $prizes = sanitize_textarea_field($_POST['prizes']);
            $status = sanitize_text_field($_POST['status']);
            $final_balance = isset($_POST['final_balance']) ? floatval($_POST['final_balance']) : null;
            $affiliate_site_id = isset($_POST['affiliate_site_id']) ? intval($_POST['affiliate_site_id']) : 0;
            
            $result = $bhg_db->update_bonus_hunt($id, array(
                'title' => $title,
                'starting_balance' => $starting_balance,
                'num_bonuses' => $num_bonuses,
                'prizes' => $prizes,
                'status' => $status,
                'final_balance' => $final_balance,
                'affiliate_site_id' => $affiliate_site_id
            ));
            
            if ($result) {
                $message = 'updated';
            } else {
                $message = 'error';
            }
            break;
            
        case 'delete_bonus_hunt':
            // Handle bonus hunt deletion
            $id = intval($_POST['id']);
            $result = $bhg_db->delete_bonus_hunt($id);
            
            if ($result) {
                $message = 'deleted';
            } else {
                $message = 'error';
            }
            break;
            
        default:
            // Unknown action
            break;
    }
    
    // Redirect to avoid form resubmission
    wp_redirect(add_query_arg('message', $message, $_SERVER['REQUEST_URI']));
    exit;
}

// Get all bonus hunts
$bonus_hunts = $bhg_db->get_all_bonus_hunts();
$affiliate_sites = $bhg_db->get_affiliate_websites();

// Display status messages
if (isset($_GET['message'])) {
    $message_type = sanitize_text_field($_GET['message']);
    switch ($message_type) {
        case 'success':
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Bonus hunt created successfully!', 'bonus-hunt-guesser') . 
                 '</p></div>';
            break;
        case 'updated':
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Bonus hunt updated successfully!', 'bonus-hunt-guesser') . 
                 '</p></div>';
            break;
        case 'deleted':
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Bonus hunt deleted successfully!', 'bonus-hunt-guesser') . 
                 '</p></div>';
            break;
        case 'error':
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 __('An error occurred. Please try again.', 'bonus-hunt-guesser') . 
                 '</p></div>';
            break;
    }
}
?>

<div class="wrap bhg-admin">
    <h1><?php _e('Bonus Hunt Guesser', 'bonus-hunt-guesser'); ?></h1>
    <hr/>
    
    <div class="bhg-admin-content">
        <h2><?php _e('Create New Bonus Hunt', 'bonus-hunt-guesser'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('bhg_create_bonus_hunt', 'bhg_nonce'); ?>
            <input type="hidden" name="bhg_action" value="create_bonus_hunt">
            
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="title"><?php _e('Bonus Hunt Title', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="text" name="title" id="title" class="regular-text" required 
                               value="<?php echo isset($_POST['title']) ? esc_attr($_POST['title']) : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="starting_balance"><?php _e('Starting Balance (€)', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="number" name="starting_balance" id="starting_balance" step="0.01" min="0" 
                               value="<?php echo isset($_POST['starting_balance']) ? floatval($_POST['starting_balance']) : '0'; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="num_bonuses"><?php _e('Number of Bonuses', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <input type="number" name="num_bonuses" id="num_bonuses" min="1" 
                               value="<?php echo isset($_POST['num_bonuses']) ? intval($_POST['num_bonuses']) : '10'; ?>" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="prizes"><?php _e('Prizes Description', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <textarea name="prizes" id="prizes" rows="5" class="large-text"><?php 
                            echo isset($_POST['prizes']) ? esc_textarea($_POST['prizes']) : ''; 
                        ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="status"><?php _e('Status', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <select name="status" id="status" required>
                            <option value="active"><?php _e('Active', 'bonus-hunt-guesser'); ?></option>
                            <option value="upcoming"><?php _e('Upcoming', 'bonus-hunt-guesser'); ?></option>
                            <option value="completed"><?php _e('Completed', 'bonus-hunt-guesser'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="affiliate_site_id"><?php _e('Affiliate Website', 'bonus-hunt-guesser'); ?></label></th>
                    <td>
                        <select name="affiliate_site_id" id="affiliate_site_id">
                            <option value="0"><?php _e('None', 'bonus-hunt-guesser'); ?></option>
                            <?php foreach ($affiliate_sites as $site) : ?>
                                <option value="<?php echo esc_attr($site->id); ?>">
                                    <?php echo esc_html($site->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Create Bonus Hunt', 'bonus-hunt-guesser')); ?>
        </form>
        
        <hr>
        
        <h2><?php _e('Existing Bonus Hunts', 'bonus-hunt-guesser'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Title', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Starting Balance', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Final Balance', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Number of Bonuses', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Status', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Created', 'bonus-hunt-guesser'); ?></th>
                    <th><?php _e('Actions', 'bonus-hunt-guesser'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bonus_hunts)) : ?>
                    <?php foreach ($bonus_hunts as $hunt) : ?>
                        <tr>
                            <td><?php echo esc_html($hunt->id); ?></td>
                            <td><?php echo esc_html($hunt->title); ?></td>
                            <td>€<?php echo number_format($hunt->starting_balance, 2); ?></td>
                            <td>
                                <?php if ($hunt->final_balance !== null) : ?>
                                    €<?php echo number_format($hunt->final_balance, 2); ?>
                                <?php else : ?>
                                    <em><?php _e('Not set', 'bonus-hunt-guesser'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($hunt->num_bonuses); ?></td>
                            <td>
                                <span class="bhg-status bhg-status-<?php echo esc_attr($hunt->status); ?>">
                                    <?php echo esc_html(ucfirst($hunt->status)); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($hunt->created_at)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=bhg_bonus_hunts&action=edit&id=' . $hunt->id); ?>" class="button button-small">
                                    <?php _e('Edit', 'bonus-hunt-guesser'); ?>
                                </a>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('bhg_delete_bonus_hunt', 'bhg_nonce'); ?>
                                    <input type="hidden" name="bhg_action" value="delete_bonus_hunt">
                                    <input type="hidden" name="id" value="<?php echo esc_attr($hunt->id); ?>">
                                    <button type="submit" class="button button-small button-danger"
                                            onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this bonus hunt?', 'bonus-hunt-guesser' ) ); ?>');">
                                        <?php _e('Delete', 'bonus-hunt-guesser'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8"><?php _e('No bonus hunts found.', 'bonus-hunt-guesser'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.bhg-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.bhg-status-active {
    background-color: #46b450;
    color: white;
}

.bhg-status-upcoming {
    background-color: #ffb900;
    color: white;
}

.bhg-status-completed {
    background-color: #0073aa;
    color: white;
}

.button-danger {
    background: #d63638;
    border-color: #d63638;
    color: white;
}

.button-danger:hover {
    background: #b32d2e;
    border-color: #b32d2e;
    color: white;
}
</style>