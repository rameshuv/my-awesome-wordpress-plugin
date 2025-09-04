
<?php if (!defined('ABSPATH')) exit; global $wpdb; ?>
<div class="wrap">
<h1><?php echo esc_html__('Users', 'bonus-hunt-guesser'); ?></h1>
<table class="widefat striped">
<thead><tr>
  <th><?php esc_html_e('ID','bonus-hunt-guesser'); ?></th>
  <th><?php esc_html_e('Username','bonus-hunt-guesser'); ?></th>
  <th><?php esc_html_e('Email','bonus-hunt-guesser'); ?></th>
  <th><?php esc_html_e('Affiliate','bonus-hunt-guesser'); ?></th>
  <th><?php esc_html_e('Profile','bonus-hunt-guesser'); ?></th>
</tr></thead>
<tbody>
<?php
$rows = get_users(array('fields'=>array('ID','user_login','user_email')));
if (!$rows) echo '<tr><td colspan="5">'.esc_html__('No users found','bonus-hunt-guesser').'</td></tr>';
foreach($rows as $u){
  $aff = get_user_meta($u->ID, 'bhg_affiliate_status', true) ? esc_html__('Yes','bonus-hunt-guesser') : esc_html__('No','bonus-hunt-guesser');
  $edit = esc_url( admin_url('user-edit.php?user_id='.$u->ID) );
  echo '<tr>';
  echo '<td>'.(int)$u->ID.'</td>';
  echo '<td>'.esc_html($u->user_login).'</td>';
  echo '<td>'.esc_html($u->user_email).'</td>';
  echo '<td>'.esc_html($aff).'</td>';
  echo '<td><a class="button" href="'.$edit.'">'.esc_html__('Edit Profile','bonus-hunt-guesser').'</a></td>';
  echo '</tr>';
}
?>
</tbody>
</table>
</div>
