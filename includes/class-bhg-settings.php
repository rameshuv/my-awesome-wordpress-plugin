<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BHG_Settings {
	public static function render(){
		BHG_Utils::require_cap();
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && BHG_Utils::verify_nonce('bhg_save_settings')) {
			$allow = isset($_POST['allow_guess_edit']) ? 1 : 0;
			$ads   = isset($_POST['ads_enabled']) ? 1 : 0;
			$email = sanitize_email($_POST['email_from'] ?? get_bloginfo('admin_email'));
			BHG_Utils::update_settings([
				'allow_guess_edit' => $allow,
				'ads_enabled' => $ads,
				'email_from' => $email,
			]);
			echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'bonus-hunt-guesser') . '</p></div>';
		}
		$s = BHG_Utils::get_settings();
		?>
		<div class="wrap bhg-wrap">
			<h1><?php echo esc_html__('Bonus Hunt - Settings', 'bonus-hunt-guesser'); ?></h1>
			<form method="post">
				<?php BHG_Utils::nonce_field('bhg_save_settings'); ?>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e('Allow Guess Editing', 'bonus-hunt-guesser'); ?></th>
						<td><label><input type="checkbox" name="allow_guess_edit" <?php checked($s['allow_guess_edit']); ?> /> <?php esc_html_e('Users can edit their guess while hunt is open.', 'bonus-hunt-guesser'); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Enable Ads', 'bonus-hunt-guesser'); ?></th>
						<td><label><input type="checkbox" name="ads_enabled" <?php checked($s['ads_enabled']); ?> /> <?php esc_html_e('Show ads block on selected pages.', 'bonus-hunt-guesser'); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e('Email From', 'bonus-hunt-guesser'); ?></th>
						<td><input type="email" name="email_from" value="<?php echo esc_attr($s['email_from']); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
