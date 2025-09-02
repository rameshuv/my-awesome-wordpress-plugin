<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle affiliate editing mode
if (isset($_GET['edit_affiliates'])) {
    $uid = (int)$_GET['edit_affiliates'];
    $aff_sites = $wpdb->get_results('SELECT id,name FROM ' . bhg_table('affiliate_websites') . ' WHERE 1', ARRAY_A);
    $user_affs = array();
    if (class_exists('BHG_DB')) { 
        $db = new BHG_DB(); 
        $user_affs = $db->get_user_affiliates($uid); 
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Edit Affiliate Sites for User', 'bonus-hunt-guesser'); ?></h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bhg_save_user_affiliates'); ?>
            <input type="hidden" name="action" value="bhg_save_user_affiliates" />
            <input type="hidden" name="user_id" value="<?php echo (int)$uid; ?>" />
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Affiliate Sites', 'bonus-hunt-guesser'); ?></th>
                    <td>
                        <?php foreach($aff_sites as $s): 
                            $checked = in_array((int)$s['id'], $user_affs) ? 'checked' : ''; 
                        ?>
                            <label style="display:block;">
                                <input type="checkbox" name="affiliate_site_id[]" value="<?php echo (int)$s['id']; ?>" <?php echo $checked; ?>> 
                                <?php echo esc_html($s['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Affiliates', 'bonus-hunt-guesser')); ?>
        </form>
        <p>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=bhg-users')); ?>">
                <?php esc_html_e('Back to Users', 'bonus-hunt-guesser'); ?>
            </a>
        </p>
    </div>
    <?php 
    return; 
}

// Handle user updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bhg_update_users']) && BHG_Utils::verify_nonce('bhg_update_users')) {
    $users = get_users(['fields' => ['ID']]);
    foreach ($users as $u) {
        $is_aff = isset($_POST['aff'][$u->ID]) ? 1 : 0;
        update_user_meta($u->ID, 'bhg_is_affiliate', $is_aff);
    }
    echo '<div class="updated"><p>' . esc_html__('Users updated', 'bonus-hunt-guesser') . '</p></div>';
}

// Get users with enhanced data
$users = BHG_Models::get_bhg_users();
?>

<div class="wrap">
    <h1><?php esc_html_e('Users & Affiliate Status', 'bonus-hunt-guesser'); ?></h1>
    
    <div class="bhg-admin-header">
        <input type="text" id="bhg-user-search" placeholder="<?php esc_attr_e('Search users...', 'bonus-hunt-guesser'); ?>" class="regular-text">
        <button type="button" id="bhg-search-btn" class="button"><?php esc_html_e('Search', 'bonus-hunt-guesser'); ?></button>
    </div>
    
    <form method="post" class="bhg-wrap">
        <?php BHG_Utils::nonce_field('bhg_update_users'); ?>
        <input type="hidden" name="bhg_update_users" value="1" />
        
        <?php if (!empty($users)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Username', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Email', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Role', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Affiliate', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Joined', 'bonus-hunt-guesser'); ?></th>
                    <th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : 
                    $is_affiliate = get_user_meta($user->ID, 'bhg_is_affiliate', true);
                ?>
                <tr class="bhg-user-row" data-userid="<?php echo esc_attr($user->ID); ?>">
                    <td><?php echo intval($user->ID); ?></td>
                    <td><?php echo esc_html($user->user_login); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo implode(', ', $user->roles); ?></td>
                    <td>
                        <input type="checkbox" name="aff[<?php echo intval($user->ID); ?>]" <?php checked((bool) $is_affiliate); ?> />
                        <span class="bhg-status-badge bhg-status-<?php echo $is_affiliate ? 'active' : 'inactive'; ?>">
                            <?php echo $is_affiliate ? __('Yes', 'bonus-hunt-guesser') : __('No', 'bonus-hunt-guesser'); ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($user->user_registered)); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=bhg-users&action=edit&id=' . $user->ID); ?>" class="button">
                            <?php _e('Edit', 'bonus-hunt-guesser'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=bhg-users&edit_affiliates=' . $user->ID); ?>" class="button">
                            <?php _e('Edit Affiliates', 'bonus-hunt-guesser'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=bhg-users&action=delete&id=' . $user->ID); ?>" class="button button-danger" onclick="return confirm('<?php esc_attr_e('Are you sure?', 'bonus-hunt-guesser'); ?>')">
                            <?php _e('Delete', 'bonus-hunt-guesser'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php submit_button(__('Save Changes', 'bonus-hunt-guesser')); ?>
        <?php else : ?>
        <p><?php esc_html_e('No users found.', 'bonus-hunt-guesser'); ?></p>
        <?php endif; ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Make user rows clickable
    $('.bhg-user-row').on('click', function(e) {
        // Don't trigger if clicking on actions, checkboxes, or links
        if ($(e.target).is('a') || $(e.target).is('button') || $(e.target).is('input[type="checkbox"]')) {
            return;
        }
        
        var userId = $(this).data('userid');
        window.location.href = '<?php echo admin_url('admin.php?page=bhg-users&action=edit&id='); ?>' + userId;
    });
    
    // User search functionality
    $('#bhg-search-btn').on('click', function() {
        var searchTerm = $('#bhg-user-search').val().toLowerCase();
        
        $('.bhg-user-row').each(function() {
            var username = $(this).find('td:eq(1)').text().toLowerCase();
            var email = $(this).find('td:eq(2)').text().toLowerCase();
            
            if (username.indexOf(searchTerm) > -1 || email.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Allow pressing Enter to search
    $('#bhg-user-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('#bhg-search-btn').click();
            return false;
        }
    });
});
</script>