<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) wp_die(esc_html__('Insufficient permissions','bonus-hunt-guesser'));
global $wpdb;
$hunt_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hunts = $wpdb->prefix.'bhg_bonus_hunts';
$guesses = $wpdb->prefix.'bhg_guesses';
$hunt = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$hunts` WHERE id=%d",$hunt_id));
if(!$hunt) { echo '<div class="wrap"><h1>'.esc_html__('Hunt not found','bonus-hunt-guesser').'</h1></div>'; return; }
$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT g.*, u.display_name, ABS(g.guess - %f) as diff FROM `$guesses` g JOIN `$wpdb->users` u ON u.ID=g.user_id WHERE g.hunt_id=%d ORDER BY diff ASC, g.id ASC",
		(float) $hunt->final_balance,
		$hunt_id
	)
);
?>
<div class="wrap">
  <h1><?php echo esc_html__('Results for ','bonus-hunt-guesser').esc_html($hunt->title); ?></h1>
  <table class="widefat striped">
	<thead><tr>
	  <th><?php esc_html_e('Position','bonus-hunt-guesser'); ?></th>
	  <th><?php esc_html_e('User','bonus-hunt-guesser'); ?></th>
	  <th><?php esc_html_e('Guess','bonus-hunt-guesser'); ?></th>
	  <th><?php esc_html_e('Difference','bonus-hunt-guesser'); ?></th>
	</tr></thead>
	<tbody>
	<?php $pos=1; foreach($rows as $r): $wcount = (int)$hunt->winners_count; if ($wcount < 1) $wcount = 3; $isWinner = $pos <= $wcount; ?>
	  <tr <?php if($isWinner) echo 'class="bhg-winner-row"'; ?>>
		<td><?php echo (int)$pos; ?></td>
		<td><?php echo esc_html($r->display_name); ?></td>
		<td><?php echo esc_html(number_format_i18n((float)$r->guess,2)); ?></td>
		<td><?php echo esc_html(number_format_i18n((float)$r->diff,2)); ?></td>
	  </tr>
	<?php $pos++; endforeach; ?>
	</tbody>
  </table>
</div>
