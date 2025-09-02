<?php
if (!defined('ABSPATH')) exit;

// Check if user has proper capabilities
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

// Initialize variables
$users = [];
$affiliate_websites = [];

// Get affiliate websites for multi-site support
global $wpdb;
$affiliate_websites = $wpdb->get_results(
    $wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}bhg_affiliate_websites")
);

// Process form submission with proper security checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_update_users'])) {
    // Verify nonce
    if (!isset($_POST['_wpnonce_bhg_update_users']) || 
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_bhg_update_users'])), 'bhg_update_users_nonce')) {
        wp_die(esc_html__('Security check failed.', 'bonus-hunt-guesser'));
    }
    
    // Check capabilities again
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'bonus-hunt-guesser'));
    }
    
    // Process user updates
    if (isset($_POST['aff']) && is_array($_POST['aff'])) {
        foreach ($_POST['aff'] as $user_id => $affiliate_data) {
            $user_id = intval($user_id);
            
            // Update affiliate status for each website
            foreach ($affiliate_websites as $website) {
                $website_id = intval($website->id);
                $is_affiliate = isset($affiliate_data[$website_id]) ? 1 : 0;
                update_user_meta($user_id, "bhg_affiliate_website_{$website_id}", $is_affiliate);
            }
        }
    }
    
    echo '<div class="updated"><p>' . esc_html__('Users updated successfully.', 'bonus-hunt-guesser') . '</p></div>';
}

// Get users with only necessary fields
$users = get_users([
    'fields' => ['ID', 'user_login', 'user_email', 'display_name']
]);
?>

<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Users & Affiliate Status', 'bonus-hunt-guesser'); ?></h1>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="bhg_update_users">
        <?php wp_nonce_field('bhg_update_users_nonce', '_wpnonce_bhg_update_users'); ?>
        <input type="hidden" name="bhg_update_users" value="1" />
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Username', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Display Name', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Email', 'bonus-hunt-guesser'); ?></th>
                    <?php foreach ($affiliate_websites as $website): ?>
                    <th>
                        <?php echo esc_html($website->name); ?>
                        <br>
                        <small><?php esc_html_e('Affiliate', 'bonus-hunt-guesser'); ?></small>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo intval($u->ID); ?></td>
                    <td><?php echo esc_html($u->user_login); ?></td>
                    <td><?php echo esc_html($u->display_name); ?></td>
                    <td><?php echo esc_html($u->user_email); ?></td>
                    <?php foreach ($affiliate_websites as $website): 
                        $is_affiliate = get_user_meta($u->ID, "bhg_affiliate_website_{$website->id}", true);
                    ?>
                    <td>
                        <input type="checkbox" name="aff[<?php echo intval($u->ID); ?>][<?php echo intval($website->id); ?>]" 
                               <?php checked((bool) $is_affiliate); ?> />
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Changes', 'bonus-hunt-guesser')); ?>
    </form>
</div>