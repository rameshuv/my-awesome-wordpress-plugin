<?php
if (!defined('ABSPATH')) exit;

// Check capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

require_once BHG_PLUGIN_DIR . 'includes/class-bhg-bonus-hunts.php';
$rows = BHG_Bonus_Hunts::get_latest_hunts_with_winners(3);
?>
<div class="wrap">
  <h1><?php echo esc_html__('Dashboard', 'bonus-hunt-guesser'); ?></h1>

  <h2 style="margin-top:1em"><?php echo esc_html__('Latest Hunts', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php echo esc_html__('Bonushunt', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('All Winners (name • guess • diff)', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Start Balance', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Final Balance', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Closed At', 'bonus-hunt-guesser'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr><td colspan="5"><?php echo esc_html__('No closed hunts yet.', 'bonus-hunt-guesser'); ?></td></tr>
      <?php else : foreach ($rows as $row) :
        $h = $row['hunt']; $winners = $row['winners'];
        $wcount = (int)($h->winners_count ?: 3);
        ?>
        <tr>
          <td><?php echo esc_html($h->title); ?></td>
          <td>
            <?php
            if (empty($winners)) {
                echo '<em>' . esc_html__('No winners found', 'bonus-hunt-guesser') . '</em>';
            } else {
                $parts = [];
                $pos = 1;
                foreach ($winners as $w) {
                    $parts[] = sprintf(
                        '#%d %s • %s • %s',
                        $pos++,
                        esc_html($w->display_name ?: ('user#' . (int)$w->user_id)),
                        esc_html(number_format_i18n($w->guess, 2)),
                        esc_html(number_format_i18n($w->diff, 2))
                    );
                    if ($pos > $wcount) break;
                }
                echo wp_kses_post(implode('<br>', $parts));
            }
            ?>
          </td>
          <td><?php echo esc_html(number_format_i18n($h->starting_balance, 2)); ?></td>
          <td><?php echo $h->final_balance !== null ? esc_html(number_format_i18n($h->final_balance, 2)) : '—'; ?></td>
          <td><?php echo $h->closed_at ? esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $h->closed_at)) : '—'; ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
