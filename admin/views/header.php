<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Process form submissions with nonce verification
if (isset($_POST['bhg_action']) && isset($_POST['bhg_nonce'])) {
    if (!wp_verify_nonce($_POST['bhg_nonce'], 'bhg_action')) {
        wp_die('Security check failed');
    }
    
    // Handle form actions here
    $action = sanitize_text_field($_POST['bhg_action']);
    
    switch ($action) {
        case 'create_bonus_hunt':
            // Handle bonus hunt creation with proper sanitization
            $title = sanitize_text_field($_POST['title']);
            $starting_balance = floatval($_POST['starting_balance']);
            $num_bonuses = intval($_POST['num_bonuses']);
            $prizes = sanitize_textarea_field($_POST['prizes']);
            
            // Save to database (implementation depends on your data layer)
            break;
            
        case 'update_settings':
            // Handle settings update
            break;
            
        default:
            // Unknown action
            break;
    }
}
?>

<div class="wrap bhg-admin">
  <h1>Bonus Hunt Guesser</h1>
  <hr/>
  
  <div class="bhg-admin-content">
    <h2>Create New Bonus Hunt</h2>
    <form method="post" action="">
        <?php wp_nonce_field('bhg_action', 'bhg_nonce'); ?>
        <input type="hidden" name="bhg_action" value="create_bonus_hunt">
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="title">Bonus Hunt Title</label></th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text" required 
                           value="<?php echo isset($_POST['title']) ? esc_attr($_POST['title']) : ''; ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="starting_balance">Starting Balance (€)</label></th>
                <td>
                    <input type="number" name="starting_balance" id="starting_balance" step="0.01" min="0" 
                           value="<?php echo isset($_POST['starting_balance']) ? floatval($_POST['starting_balance']) : '0'; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="num_bonuses">Number of Bonuses</label></th>
                <td>
                    <input type="number" name="num_bonuses" id="num_bonuses" min="1" 
                           value="<?php echo isset($_POST['num_bonuses']) ? intval($_POST['num_bonuses']) : '10'; ?>" required>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="prizes">Prizes Description</label></th>
                <td>
                    <textarea name="prizes" id="prizes" rows="5" class="large-text"><?php 
                        echo isset($_POST['prizes']) ? esc_textarea($_POST['prizes']) : ''; 
                    ?></textarea>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Create Bonus Hunt'); ?>
    </form>
  </div>
</div>