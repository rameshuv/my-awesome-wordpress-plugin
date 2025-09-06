<?php
if (!defined('ABSPATH')) {
	exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

// Get current settings
$current_settings = get_option('bhg_plugin_settings', array(
	'default_tournament_period' => 'monthly',
	'max_guess_amount' => 100000,
	'min_guess_amount' => 0,
	'allow_guess_changes' => 'yes'
));

// Handle settings save via admin-post.php
$message = isset($_GET['message']) ? sanitize_text_field(wp_unslash($_GET['message'])) : '';
if ('saved' === $message) {
	echo '<div class="notice notice-success is-dismissible"><p>' .
		 esc_html__('Settings saved successfully.', 'bonus-hunt-guesser') .
		 '</p></div>';
}

// Handle error messages
$error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : '';
if (!empty($error)) {
	$error_message = '';
	switch ($error) {
		case 'nonce_failed':
			$error_message = __('Security check failed. Please try again.', 'bonus-hunt-guesser');
			break;
		case 'invalid_data':
			$error_message = __('Invalid data submitted. Please check your inputs.', 'bonus-hunt-guesser');
			break;
		default:
			$error_message = __('An error occurred while saving settings.', 'bonus-hunt-guesser');
	}

	if (!empty($error_message)) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
	}
}
?>

<div class="wrap">
	<h1><?php _e('Bonus Hunt Guesser Settings', 'bonus-hunt-guesser'); ?></h1>
	
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="bhg_save_settings">
		<?php wp_nonce_field('bhg_save_settings_nonce', 'bhg_settings_nonce'); ?>

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
						<option value="quarterly" <?php selected($current_settings['default_tournament_period'], 'quarterly'); ?>>
							<?php _e('Quarterly', 'bonus-hunt-guesser'); ?>
						</option>
						<option value="yearly" <?php selected($current_settings['default_tournament_period'], 'yearly'); ?>>
							<?php _e('Yearly', 'bonus-hunt-guesser'); ?>
						</option>
						<option value="alltime" <?php selected($current_settings['default_tournament_period'], 'alltime'); ?>>
							<?php _e('All-Time', 'bonus-hunt-guesser'); ?>
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
						   class="regular-text" step="0.01" min="0" required>
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
						   class="regular-text" step="0.01" min="0" required>
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