<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e('Demo Tools', 'bonus-hunt-guesser'); ?></h1>
	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="bhg_demo_reseed">
		<?php wp_nonce_field('bhg_demo_reseed_action', 'bhg_demo_reseed_nonce'); ?>
		<p><?php esc_html_e('This will delete all demo data and pages, then recreate fresh demo content.', 'bonus-hunt-guesser'); ?></p>
		<p><input type="submit" class="button button-primary" value="<?php esc_attr_e('Reset & Reseed Demo Data', 'bonus-hunt-guesser'); ?>"></p>
	</form>
</div>
