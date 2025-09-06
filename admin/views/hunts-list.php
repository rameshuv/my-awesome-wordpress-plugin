<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) { wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser')); }

global $wpdb;
$t = $wpdb->prefix . 'bhg_hunts';

$paged = max(1, isset($_GET['paged']) ? (int) $_GET['paged'] : 1);
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$rows = $wpdb->get_results(
  $wpdb->prepare(
	"SELECT id, title, start_balance, final_balance, status, winners_limit, closed_at
	 FROM $t
	 ORDER BY id DESC
	 LIMIT %d OFFSET %d",
	$per_page,
	$offset
  )
);
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t" );
$pages = max(1, (int) ceil($total / $per_page));

?>
<div class="wrap">
  <h1><?php esc_html_e('Bonus Hunts','bonus-hunt-guesser'); ?></h1>
  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php esc_html_e('ID','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Title','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Start Balance','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Final Balance','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Winners','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Closed At','bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Actions','bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if ($rows): foreach ($rows as $r): ?>
		<tr>
		  <td><?php echo (int)$r->id; ?></td>
		  <td><strong><a href="<?php echo esc_url( admin_url('admin.php?page=bhg-hunts-edit&id='.(int)$r->id) ); ?>"><?php echo esc_html($r->title); ?></a></strong></td>
		  <td><?php echo esc_html(number_format_i18n((float)$r->start_balance, 2)); ?></td>
                  <td><?php echo ($r->final_balance !== null) ? esc_html( number_format_i18n( (float) $r->final_balance, 2 ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
		  <td><?php echo esc_html($r->status); ?></td>
		  <td><?php echo (int)$r->winners_limit; ?></td>
                  <td><?php echo $r->closed_at ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->closed_at ) ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
		  <td>
			<?php
			  $results_url = wp_nonce_url( admin_url('admin.php?page=bhg-hunt-results&id='.(int)$r->id), 'bhg_view_results_'.(int)$r->id, 'bhg_nonce' );
			  $edit_url    = wp_nonce_url( admin_url('admin.php?page=bhg-hunts-edit&id='.(int)$r->id), 'bhg_edit_hunt_'.(int)$r->id, 'bhg_nonce' );
			?>
			<a class="button" href="<?php echo esc_url($results_url); ?>"><?php esc_html_e('Results','bonus-hunt-guesser'); ?></a>
			<a class="button" href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit','bonus-hunt-guesser'); ?></a>
		  </td>
		</tr>
	  <?php endforeach; else: ?>
		<tr><td colspan="8"><?php esc_html_e('No hunts found.','bonus-hunt-guesser'); ?></td></tr>
	  <?php endif; ?>
	</tbody>
  </table>

  <?php if ($pages > 1): ?>
	<div class="tablenav">
	  <div class="tablenav-pages">
		<?php
		  $base = remove_query_arg('paged');
		  for ($i=1; $i<=$pages; $i++) {
			$url = esc_url( add_query_arg('paged', $i, $base) );
			$class = $i === $paged ? ' class="page-numbers current"' : ' class="page-numbers"';
			echo '<a'.$class.' href="'.$url.'">'.$i.'</a> ';
		  }
		?>
	  </div>
	</div>
  <?php endif; ?>
</div>
