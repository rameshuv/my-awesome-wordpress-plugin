<?php
// phpcs:ignoreFile -- Legacy view requires refactoring for WordPress coding standards.
/**
 * Users admin page.
 *
 * @package Bonus_Hunt_Guesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

if ( ! class_exists( 'BHG_Users_Table' ) ) {
	require_once BHG_PLUGIN_DIR . 'admin/class-bhg-users-table.php';
}

$table = new BHG_Users_Table();
$table->prepare_items();
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Users', 'bonus-hunt-guesser' ); ?></h1>
	<form method="get">
		<input type="hidden" name="page" value="bhg-users" />
		<?php $table->display(); ?>
	</form>
</div>
