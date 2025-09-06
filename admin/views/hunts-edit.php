<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) { wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser')); }

$hunt_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$hunt_id) { wp_die(esc_html__('Missing hunt id','bonus-hunt-guesser')); }

check_admin_referer('bhg_edit_hunt_' . $hunt_id, 'bhg_nonce');

// Handle delete guess action
if (isset($_POST['bhg_remove_guess']) && isset($_POST['bhg_remove_guess_nonce']) && wp_verify_nonce($_POST['bhg_remove_guess_nonce'], 'bhg_remove_guess_action')) {
	$guess_id = (int) ($_POST['guess_id'] ?? 0);
	if ($guess_id > 0 && function_exists('bhg_remove_guess')) {
		bhg_remove_guess($guess_id);
		echo '<div class="notice notice-success is-dismissible"><p>'.esc_html__('Guess removed.','bonus-hunt-guesser').'</p></div>';
	}
}

if (!function_exists('bhg_get_hunt') || !function_exists('bhg_get_hunt_participants')) {
	wp_die(esc_html__('Missing helper functions. Please include class-bhg-bonus-hunts-helpers.php.', 'bonus-hunt-guesser'));
}

$hunt = bhg_get_hunt($hunt_id);
if (!$hunt) { wp_die(esc_html__('Hunt not found','bonus-hunt-guesser')); }

$paged = max(1, isset($_GET['ppaged']) ? (int) $_GET['ppaged'] : 1);
$per_page = 30;
$data = bhg_get_hunt_participants($hunt_id, $paged, $per_page);
$rows = $data['rows'];
$total = (int) $data['total'];
$pages = max(1, (int) ceil($total / $per_page));
?>
<div class="wrap">
  <h1><?php echo esc_html(sprintf(__('Edit Hunt — %s','bonus-hunt-guesser'), $hunt->title)); ?></h1>

  <!-- Your existing edit form for the hunt would be above this line -->

  <h2 style="margin-top:2em;"><?php esc_html_e('Participants','bonus-hunt-guesser'); ?></h2>
  <p><?php echo esc_html(sprintf(_n('%s participant', '%s participants', $total, 'bonus-hunt-guesser'), number_format_i18n($total))); ?></p>

  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php esc_html_e('User','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Guess','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Date','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Actions','bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if ($rows): foreach ($rows as $r): 
		$u = get_userdata((int)$r->user_id);
		$name = $u ? $u->user_login : ('#'.$r->user_id);
	  ?>
		<tr>
		  <td><a href="<?php echo esc_url( admin_url('user-edit.php?user_id='.(int)$r->user_id) ); ?>"><?php echo esc_html($name); ?></a></td>
		  <td><?php echo esc_html(number_format_i18n((float)$r->guess, 2)); ?></td>
                  <td><?php echo $r->created_at ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->created_at ) ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
		  <td>
			<form method="post" style="display:inline">
			  <?php wp_nonce_field('bhg_remove_guess_action','bhg_remove_guess_nonce'); ?>
			  <input type="hidden" name="guess_id" value="<?php echo (int)$r->id; ?>">
			  <button type="submit" name="bhg_remove_guess" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(__('Remove this guess?','bonus-hunt-guesser')); ?>');">
				<?php esc_html_e('Remove','bonus-hunt-guesser'); ?>
			  </button>
			</form>
		  </td>
		</tr>
	  <?php endforeach; else: ?>
		<tr><td colspan="4"><?php esc_html_e('No participants yet.','bonus-hunt-guesser'); ?></td></tr>
	  <?php endif; ?>
	</tbody>
  </table>

  <?php if ($pages > 1): ?>
	<div class="tablenav">
	  <div class="tablenav-pages">
		<?php
		  $base = remove_query_arg('ppaged');
		  for ($i=1; $i<=$pages; $i++) {
			$url = esc_url( add_query_arg('ppaged', $i, $base) );
			$class = $i === $paged ? ' class="page-numbers current"' : ' class="page-numbers"';
			echo '<a'.$class.' href="'.$url.'">'.$i.'</a> ';
		  }
		?>
	  </div>
	</div>
  <?php endif; ?>
</div>
