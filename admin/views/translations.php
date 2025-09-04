<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
  <h1><?php echo esc_html__('Translations', 'bonus-hunt-guesser'); ?></h1>

  <?php
  global $wpdb;
  $table = $wpdb->prefix . 'bhg_translations';
  $rows = $wpdb->get_results("SELECT id, `key`, `value` FROM `$table` ORDER BY `key` ASC");
  $total = is_array($rows) ? count($rows) : 0;
  ?>

  <div class="card" style="max-width:900px;padding:12px;margin:12px 0;">
    <p>
      <?php
        if ($total > 0) {
            printf(esc_html__('%d translation keys loaded.', 'bonus-hunt-guesser'), (int)$total);
        } else {
            echo esc_html__('No translations yet. Add one below.', 'bonus-hunt-guesser');
        }
      ?>
    </p>

    <?php if ($total > 0): ?>
    <table class="widefat fixed striped">
      <thead>
        <tr>
          <th><?php echo esc_html__('Key', 'bonus-hunt-guesser'); ?></th>
          <th><?php echo esc_html__('Value', 'bonus-hunt-guesser'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><code><?php echo esc_html($r->key); ?></code></td>
            <td><?php echo esc_html(wp_html_excerpt(wp_strip_all_tags($r->value), 160, '…')); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('bhg_save_translation', 'bhg_save_translation_nonce'); ?>
    <input type="hidden" name="action" value="bhg_save_translation">

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="bhg_t_key"><?php echo esc_html__('Key', 'bonus-hunt-guesser'); ?></label></th>
          <td><input class="regular-text" name="t_key" id="bhg_t_key" required></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_t_value"><?php echo esc_html__('Value', 'bonus-hunt-guesser'); ?></label></th>
          <td><textarea class="large-text" name="t_value" id="bhg_t_value" rows="3"></textarea></td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(__('Save', 'bonus-hunt-guesser')); ?>
  </form>
</div>
