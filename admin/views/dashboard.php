<?php
if (!defined('ABSPATH')) exit;
BHG_Utils::require_cap();
global $wpdb;
$hunts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_bonus_hunts");
$guesses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_guesses");
$affs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_affiliate_websites");
?>
<div class="wrap bhg-wrap">
    <h1><?php esc_html_e('Bonus Hunt Dashboard', 'bonus-hunt-guesser'); ?></h1>
    <div class="bhg-cards">
        <div class="bhg-card"><h3><?php echo intval($hunts); ?></h3><p><?php esc_html_e('Bonus Hunts', 'bonus-hunt-guesser'); ?></p></div>
        <div class="bhg-card"><h3><?php echo intval($guesses); ?></h3><p><?php esc_html_e('Guesses', 'bonus-hunt-guesser'); ?></p></div>
        <div class="bhg-card"><h3><?php echo intval($affs); ?></h3><p><?php esc_html_e('Affiliate Sites', 'bonus-hunt-guesser'); ?></p></div>
    </div>
    <p><?php esc_html_e('Use the menu to manage hunts, tournaments, translations, ads, and more.', 'bonus-hunt-guesser'); ?></p>
</div>

<div class="bhg-section">
  <div class="bhg-card">
    <h2>Recent Winners</h2>
    <?php if (!empty($winners)) : ?>
    <table class="bhg-table">
      <thead>
        <tr><th>Hunt</th><th>Winner</th><th>Final Balance</th><th>Difference</th><th>Closed At</th></tr>
      </thead>
      <tbody>
        <?php foreach ($winners as $w) : ?>
          <tr>
            <td><?php echo esc_html($w->title); ?></td>
            <td><?php echo esc_html($w->display_name); ?></td>
            <td><?php echo esc_html($w->final_balance); ?></td>
            <td><?php echo esc_html($w->winner_diff); ?></td>
            <td><?php echo esc_html($w->closed_at); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else : ?>
      <p>No winners yet.</p>
    <?php endif; ?>
  </div>
</div>
