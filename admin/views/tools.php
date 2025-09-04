
<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
  <h1><?php echo esc_html__('BHG Tools', 'bonus-hunt-guesser'); ?></h1>
  <div class="card" style="max-width:900px;padding:16px;margin-top:12px;">
    <h2><?php echo esc_html__('Dashboard Overview', 'bonus-hunt-guesser'); ?></h2>
    <ul>
      <li><?php echo esc_html__('Hunts:', 'bonus-hunt-guesser'); ?> <?php echo (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}bhg_bonus_hunts"); ?></li>
      <li><?php echo esc_html__('Guesses:', 'bonus-hunt-guesser'); ?> <?php echo (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}bhg_guesses"); ?></li>
      <li><?php echo esc_html__('Tournaments:', 'bonus-hunt-guesser'); ?> <?php echo (int) $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}bhg_tournaments"); ?></li>
    </ul>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('bhg_tools_actions', 'bhg_tools_nonce'); ?>
      <input type="hidden" name="action" value="bhg_tools_reset_demo">
      <button class="button button-secondary"><?php echo esc_html__('Reset demo data', 'bonus-hunt-guesser'); ?></button>
    </form>
  </div>
</div>
