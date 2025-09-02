<?php
if (!defined('ABSPATH')) exit;

// Check capabilities using WordPress standard methods
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;

// Use direct queries for static queries without variables
$hunts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_bonus_hunts");

$guesses = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_guesses");

$affs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}bhg_affiliate_websites");

// Get recent winners with direct query
$winners = $wpdb->get_results(
    "SELECT h.title, u.display_name, h.final_balance, h.winner_diff, h.closed_at 
     FROM {$wpdb->prefix}bhg_bonus_hunts h
     LEFT JOIN {$wpdb->users} u ON h.winner_user_id = u.ID
     WHERE h.winner_user_id IS NOT NULL 
     ORDER BY h.closed_at DESC 
     LIMIT 5"
);
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
    <h2><?php esc_html_e('Recent Winners', 'bonus-hunt-guesser'); ?></h2>
    <?php if (!empty($winners)) : ?>
    <table class="bhg-table wp-list-table widefat fixed striped">
      <thead>
        <tr>
            <th><?php esc_html_e('Hunt', 'bonus-hunt-guesser'); ?></th>
            <th><?php esc_html_e('Winner', 'bonus-hunt-guesser'); ?></th>
            <th><?php esc_html_e('Final Balance', 'bonus-hunt-guesser'); ?></th>
            <th><?php esc_html_e('Difference', 'bonus-hunt-guesser'); ?></th>
            <th><?php esc_html_e('Closed At', 'bonus-hunt-guesser'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($winners as $w) : ?>
          <tr>
            <td><?php echo esc_html($w->title); ?></td>
            <td><?php echo esc_html($w->display_name); ?></td>
            <td><?php echo esc_html(number_format_i18n($w->final_balance, 2)); ?></td>
            <td><?php echo esc_html(number_format_i18n($w->winner_diff, 2)); ?></td>
            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($w->closed_at))); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else : ?>
      <p><?php esc_html_e('No winners yet.', 'bonus-hunt-guesser'); ?></p>
    <?php endif; ?>
  </div>
</div>