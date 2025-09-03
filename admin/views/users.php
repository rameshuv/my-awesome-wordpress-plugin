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
$affiliate_websites = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}bhg_affiliate_websites");

// Process form submission with proper security checks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_update_users'])) {
    // Verify nonce
    if (!isset($_POST['bhg_users_nonce']) || 
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bhg_users_nonce'])), 'bhg_save_users')) {
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
            
            // Update general affiliate status (for backward compatibility)
            $any_affiliate = false;
            foreach ($affiliate_websites as $website) {
                if (isset($affiliate_data[$website->id])) {
                    $any_affiliate = true;
                    break;
                }
            }
            update_user_meta($user_id, "bhg_affiliate_status", $any_affiliate ? 1 : 0);
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
    
    <form method="post" action="">
        <?php wp_nonce_field('bhg_save_users', 'bhg_users_nonce'); ?>
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
                    <th><?php esc_html_e('Global Affiliate Status', 'bonus-hunt-guesser'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): 
                $global_affiliate_status = get_user_meta($u->ID, "bhg_affiliate_status", true);
            ?>
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
                    <td>
                        <span class="affiliate-status-indicator <?php echo $global_affiliate_status ? 'affiliate-yes' : 'affiliate-no'; ?>">
                            <?php echo $global_affiliate_status ? '✓' : '✗'; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Changes', 'bonus-hunt-guesser')); ?>
    </form>
</div>

<style>
.affiliate-status-indicator {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    text-align: center;
    line-height: 20px;
    font-weight: bold;
}
.affiliate-yes {
    background-color: #46b450;
    color: white;
}
.affiliate-no {
    background-color: #dc3232;
    color: white;
}
</style>
        <!-- STAGE-3 AFFILIATE SITES -->
        <h3><?php esc_html_e('Affiliate Websites','bonus-hunt-guesser'); ?></h3>
        <?php
        global $wpdb;
        $sites = $wpdb->get_results("SELECT id,name FROM {$wpdb->prefix}bhg_affiliate_websites ORDER BY name ASC");
        foreach ($sites as $s) {
            $key = 'bhg_affiliate_website_' . $s->id;
            $val = get_user_meta($user->ID,$key,true);
            echo '<p>'.esc_html($s->name).': '.($val?'<span style="color:green;">●</span>':'<span style="color:red;">●</span>').'</p>';
        }
        ?>
        

<?php
if (!defined('ABSPATH')) exit;
global $wpdb;

$table_sites = $wpdb->prefix . 'bhg_affiliate_websites';
$sites = $wpdb->get_results("SELECT id, name FROM {$table_sites} ORDER BY name ASC");

$current_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
if ($current_user_id) {
    $is_aff = bhg_is_user_affiliate($current_user_id);
    $assigned = bhg_get_user_affiliate_sites($current_user_id);
?>
<div class="wrap">
  <h2><?php echo esc_html__('Affiliate Settings', 'bonus-hunt-guesser'); ?></h2>
  <form method="post">
    <?php wp_nonce_field('bhg_save_user_affiliate', 'bhg_nonce'); ?>
    <input type="hidden" name="bhg_user_aff_form" value="1">
    <input type="hidden" name="user_id" value="<?php echo (int)$current_user_id; ?>">
    <table class="form-table">
      <tr>
        <th scope="row"><?php echo esc_html__('Is Affiliate', 'bonus-hunt-guesser'); ?></th>
        <td>
          <label>
            <input type="checkbox" name="bhg_is_affiliate" value="1" <?php checked($is_aff); ?> />
            <?php echo esc_html__('Mark this user as affiliate', 'bonus-hunt-guesser'); ?>
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php echo esc_html__('Affiliate Websites', 'bonus-hunt-guesser'); ?></th>
        <td>
          <select name="bhg_affiliate_sites[]" multiple style="min-width:280px; height:140px;">
            <?php if ($sites) foreach ($sites as $s): ?>
            <option value="<?php echo (int)$s->id; ?>" <?php selected(in_array((int)$s->id, $assigned, true)); ?>>
              <?php echo esc_html($s->name); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php echo esc_html__('Hold Ctrl/Cmd to select multiple sites.', 'bonus-hunt-guesser'); ?></p>
        </td>
      </tr>
    </table>
    <?php submit_button(__('Save Affiliate Settings', 'bonus-hunt-guesser')); ?>
  </form>
</div>
<?php } ?>
