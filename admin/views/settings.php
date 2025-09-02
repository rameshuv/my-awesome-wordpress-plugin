<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle form submission
if (isset($_POST['bhg_settings_submit'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['bhg_settings_nonce'], 'bhg_save_settings')) {
        wp_die(__('Security check failed.'));
    }
    
    // Sanitize and save settings
    $settings = array();
    
    if (isset($_POST['bhg_default_tournament_period'])) {
        $settings['default_tournament_period'] = sanitize_text_field($_POST['bhg_default_tournament_period']);
    }
    
    if (isset($_POST['bhg_max_guess_amount'])) {
        $settings['max_guess_amount'] = floatval($_POST['bhg_max_guess_amount']);
    }
    
    if (isset($_POST['bhg_min_guess_amount'])) {
        $settings['min_guess_amount'] = floatval($_POST['bhg_min_guess_amount']);
    }
    
    if (isset($_POST['bhg_allow_guess_changes'])) {
        $settings['allow_guess_changes'] = sanitize_text_field($_POST['bhg_allow_guess_changes']);
    }
    
    update_option('bhg_plugin_settings', $settings);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . 
         __('Settings saved successfully.', 'bonus-hunt-guesser') . 
         '</p></div>';
}

// Get current settings
$current_settings = get_option('bhg_plugin_settings', array(
    'default_tournament_period' => 'monthly',
    'max_guess_amount' => 100000,
    'min_guess_amount' => 0,
    'allow_guess_changes' => 'yes'
));
?>

<div class="wrap">
    <h1><?php _e('Bonus Hunt Guesser Settings', 'bonus-hunt-guesser'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bhg_save_settings', 'bhg_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="bhg_default_tournament_period">
                        <?php _e('Default Tournament Period', 'bonus-hunt-guesser'); ?>
                    </label>
                </th>
                <td>
                    <select name="bhg_default_tournament_period" id="bhg_default_tournament_period" class="regular-text">
                        <option value="weekly" <?php selected($current_settings['default_tournament_period'], 'weekly'); ?>>
                            <?php _e('Weekly', 'bonus-hunt-guesser'); ?>
                        </option>
                        <option value="monthly" <?php selected($current_settings['default_tournament_period'], 'monthly'); ?>>
                            <?php _e('Monthly', 'bonus-hunt-guesser'); ?>
                        </option>
                        <option value="yearly" <?php selected($current_settings['default_tournament_period'], 'yearly'); ?>>
                            <?php _e('Yearly', 'bonus-hunt-guesser'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Default period for tournament calculations.', 'bonus-hunt-guesser'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bhg_min_guess_amount">
                        <?php _e('Minimum Guess Amount', 'bonus-hunt-guesser'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="bhg_min_guess_amount" id="bhg_min_guess_amount" 
                           value="<?php echo esc_attr($current_settings['min_guess_amount']); ?>" 
                           class="regular-text" step="0.01" min="0">
                    <p class="description">
                        <?php _e('Minimum amount users can guess for a bonus hunt.', 'bonus-hunt-guesser'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bhg_max_guess_amount">
                        <?php _e('Maximum Guess Amount', 'bonus-hunt-guesser'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="bhg_max_guess_amount" id="bhg_max_guess_amount" 
                           value="<?php echo esc_attr($current_settings['max_guess_amount']); ?>" 
                           class="regular-text" step="0.01" min="0">
                    <p class="description">
                        <?php _e('Maximum amount users can guess for a bonus hunt.', 'bonus-hunt-guesser'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="bhg_allow_guess_changes">
                        <?php _e('Allow Guess Changes', 'bonus-hunt-guesser'); ?>
                    </label>
                </th>
                <td>
                    <select name="bhg_allow_guess_changes" id="bhg_allow_guess_changes" class="regular-text">
                        <option value="yes" <?php selected($current_settings['allow_guess_changes'], 'yes'); ?>>
                            <?php _e('Yes', 'bonus-hunt-guesser'); ?>
                        </option>
                        <option value="no" <?php selected($current_settings['allow_guess_changes'], 'no'); ?>>
                            <?php _e('No', 'bonus-hunt-guesser'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Allow users to change their guesses before a bonus hunt closes.', 'bonus-hunt-guesser'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="bhg_settings_submit" id="submit" 
                   class="button button-primary" value="<?php _e('Save Changes', 'bonus-hunt-guesser'); ?>">
        </p>
    </form>
</div>