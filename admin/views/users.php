<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

$paged    = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
$per_page = 30;
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$allowed_orderby = ['user_login', 'display_name', 'user_email'];
$orderby         = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
	$orderby = 'user_login';
}
$order = ( isset( $_GET['order'] ) && 'desc' === strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) ) ? 'DESC' : 'ASC';

$args = [
	'number'   => $per_page,
	'offset'   => ($paged - 1) * $per_page,
	'orderby'  => $orderby,
	'order'    => $order,
	'search'   => $search ? '*' . $search . '*' : '',
	'search_columns' => ['user_login','user_email','display_name'],
];

$user_query = new WP_User_Query($args);
$users = $user_query->get_results();
$total = $user_query->get_total();

$base_url = remove_query_arg(['paged']);
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Users', 'bonus-hunt-guesser'); ?></h1>

  <form method="get" class="bhg-margin-top-small">
	<input type="hidden" name="page" value="bhg-users" />
	<p class="search-box">
	  <label class="screen-reader-text" for="user-search-input"><?php echo esc_html__('Search Users', 'bonus-hunt-guesser'); ?></label>
	  <input type="search" id="user-search-input" name="s" value="<?php echo esc_attr($search); ?>" />
	  <input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__('Search', 'bonus-hunt-guesser'); ?>" />
	</p>
  </form>

  <table class="widefat striped">
	<thead>
	  <tr>
		<th><a href="<?php echo esc_url(add_query_arg(['orderby'=>'user_login','order'=> $order==='ASC'?'desc':'asc'], $base_url)); ?>"><?php echo esc_html__('Username', 'bonus-hunt-guesser'); ?></a></th>
		<th><a href="<?php echo esc_url(add_query_arg(['orderby'=>'display_name','order'=> $order==='ASC'?'desc':'asc'], $base_url)); ?>"><?php echo esc_html__('Name', 'bonus-hunt-guesser'); ?></a></th>
		<th><?php echo esc_html__('Real Name', 'bonus-hunt-guesser'); ?></th>
		<th><a href="<?php echo esc_url(add_query_arg(['orderby'=>'user_email','order'=> $order==='ASC'?'desc':'asc'], $base_url)); ?>"><?php echo esc_html__('Email', 'bonus-hunt-guesser'); ?></a></th>
		<th><?php echo esc_html__('Affiliate', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if (empty($users)) : ?>
		<tr><td colspan="6"><?php echo esc_html__('No users found.', 'bonus-hunt-guesser'); ?></td></tr>
	  <?php else : foreach ($users as $u) :
		  $form_id    = 'bhg-user-' . (int) $u->ID;
		  $real_name  = get_user_meta($u->ID, 'bhg_real_name', true);
		  $is_aff     = get_user_meta($u->ID, 'bhg_is_affiliate', true);
	  ?>
		<tr>
		  <td><?php echo esc_html($u->user_login); ?></td>
		  <td><?php echo esc_html($u->display_name); ?></td>
		  <td><input type="text" name="bhg_real_name" form="<?php echo esc_attr($form_id); ?>" value="<?php echo esc_attr($real_name); ?>" /></td>
		  <td><?php echo esc_html($u->user_email); ?></td>
		  <td class="bhg-text-center"><input type="checkbox" name="bhg_is_affiliate" value="1" form="<?php echo esc_attr($form_id); ?>" <?php checked( $is_aff, 1 ); ?> /></td>
		  <td>
			<form id="<?php echo esc_attr($form_id); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			  <input type="hidden" name="action" value="bhg_save_user_meta" />
			  <input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>" />
			  <?php wp_nonce_field('bhg_save_user_meta'); ?>
			  <button type="submit" class="button button-primary"><?php echo esc_html__('Save', 'bonus-hunt-guesser'); ?></button>
			</form>
			<a class="button" href="<?php echo esc_url(admin_url('user-edit.php?user_id='.(int)$u->ID)); ?>"><?php echo esc_html__('View / Edit', 'bonus-hunt-guesser'); ?></a>
		  </td>
		</tr>
	  <?php endforeach; endif; ?>
	</tbody>
  </table>

  <?php
	$total_pages = ceil($total / $per_page);
	if ($total_pages > 1) {
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo paginate_links([
			'base'      => add_query_arg('paged', '%#%', $base_url),
			'format'    => '',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total'     => $total_pages,
			'current'   => $paged,
		]);
		echo '</div></div>';
	}
  ?>
</div>
