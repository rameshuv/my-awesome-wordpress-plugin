<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap">
  <h1><?php echo esc_html__('BHG Tools', 'bonus-hunt-guesser'); ?></h1>

  <?php
  global $wpdb;
  $hunts = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bhg_bonus_hunts" );
  $guesses = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bhg_guesses" );
  $users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
  $ads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bhg_ads" );
  $tournaments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bhg_tournaments" );
  ?>

  <div class="card" style="max-width:900px;padding:16px;margin-top:12px;">
	<h2><?php echo esc_html__('Diagnostics', 'bonus-hunt-guesser'); ?></h2>
	<?php if (($hunts + $guesses + $users + $ads + $tournaments) > 0): ?>
	  <ul>
		<li><?php echo esc_html__('Hunts:', 'bonus-hunt-guesser'); ?> <?php echo number_format_i18n($hunts); ?></li>
		<li><?php echo esc_html__('Guesses:', 'bonus-hunt-guesser'); ?> <?php echo number_format_i18n($guesses); ?></li>
		<li><?php echo esc_html__('Users:', 'bonus-hunt-guesser'); ?> <?php echo number_format_i18n($users); ?></li>
		<li><?php echo esc_html__('Ads:', 'bonus-hunt-guesser'); ?> <?php echo number_format_i18n($ads); ?></li>
		<li><?php echo esc_html__('Tournaments:', 'bonus-hunt-guesser'); ?> <?php echo number_format_i18n($tournaments); ?></li>
	  </ul>
	<?php else: ?>
	  <p><?php echo esc_html__('Nothing to show yet. Start by creating a hunt or a test user.', 'bonus-hunt-guesser'); ?></p>
	<?php endif; ?>
  </div>
</div>
