<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}
global $wpdb;
$table = $wpdb->prefix . 'bhg_affiliates';

// Load for edit
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$row = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id=%d", $edit_id)) : null;

// List
$rows = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY id DESC" );
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Affiliates', 'bonus-hunt-guesser'); ?></h1>

  <h2 style="margin-top:1em"><?php echo esc_html__('All Affiliate Websites', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php esc_html_e('ID','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Name','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('URL','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Actions','bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if (empty($rows)) : ?>
		<tr><td colspan="5"><em><?php esc_html_e('No affiliates yet.','bonus-hunt-guesser'); ?></em></td></tr>
	  <?php else : foreach ($rows as $r) : ?>
		<tr>
		  <td><?php echo (int)$r->id; ?></td>
		  <td><?php echo esc_html($r->name); ?></td>
		  <td><?php echo esc_html($r->url); ?></td>
		  <td><?php echo esc_html($r->status); ?></td>
		  <td>
			<a class="button" href="<?php echo esc_url(add_query_arg(['edit'=>(int)$r->id])); ?>"><?php esc_html_e('Edit','bonus-hunt-guesser'); ?></a>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('<?php echo esc_js(__('Delete this affiliate?', 'bonus-hunt-guesser')); ?>');">
			  <?php wp_nonce_field('bhg_delete_affiliate'); ?>
			  <input type="hidden" name="action" value="bhg_delete_affiliate">
			  <input type="hidden" name="id" value="<?php echo (int)$r->id; ?>">
			  <button class="button-link-delete" type="submit"><?php esc_html_e('Remove','bonus-hunt-guesser'); ?></button>
			</form>
		  </td>
		</tr>
	  <?php endforeach; endif; ?>
	</tbody>
  </table>

  <h2 style="margin-top:2em"><?php echo $row ? esc_html__('Edit Affiliate','bonus-hunt-guesser') : esc_html__('Add Affiliate','bonus-hunt-guesser'); ?></h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:800px">
	<?php wp_nonce_field('bhg_save_affiliate'); ?>
	<input type="hidden" name="action" value="bhg_save_affiliate">
	<?php if ($row): ?><input type="hidden" name="id" value="<?php echo (int)$row->id; ?>"><?php endif; ?>
	<table class="form-table">
	  <tr>
		<th><label for="aff_name"><?php esc_html_e('Name','bonus-hunt-guesser'); ?></label></th>
		<td><input class="regular-text" id="aff_name" name="name" value="<?php echo esc_attr($row->name ?? ''); ?>" required></td>
	  </tr>
	  <tr>
		<th><label for="aff_url"><?php esc_html_e('URL','bonus-hunt-guesser'); ?></label></th>
		<td><input class="regular-text" id="aff_url" name="url" value="<?php echo esc_attr($row->url ?? ''); ?>" placeholder="https://example.com"></td>
	  </tr>
	  <tr>
		<th><label for="aff_status"><?php esc_html_e('Status','bonus-hunt-guesser'); ?></label></th>
		<td>
		  <select id="aff_status" name="status">
			<?php $opts=['active','inactive']; $cur = $row->status ?? 'active'; foreach ($opts as $o): ?>
			  <option value="<?php echo esc_attr($o); ?>" <?php selected($cur, $o); ?>><?php echo esc_html(ucfirst($o)); ?></option>
			<?php endforeach; ?>
		  </select>
		</td>
	  </tr>
	</table>
	<?php submit_button($row ? __('Update Affiliate','bonus-hunt-guesser') : __('Create Affiliate','bonus-hunt-guesser')); ?>
  </form>
</div>
