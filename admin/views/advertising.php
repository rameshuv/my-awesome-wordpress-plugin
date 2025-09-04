<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
  <h1><?php echo esc_html__('Advertising', 'bonus-hunt-guesser'); ?></h1>

  <?php if (isset($_GET['updated'])): ?>
    <div class="notice notice-success is-dismissible">
      <p><?php echo esc_html__('Saved.', 'bonus-hunt-guesser'); ?></p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <input type="hidden" name="action" value="bhg_save_ad" />
    <?php wp_nonce_field('bhg_save_ad', 'bhg_save_ad_nonce'); ?>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row">
            <label for="bhg_ad_message"><?php echo esc_html__('Message (HTML allowed)', 'bonus-hunt-guesser'); ?></label>
          </th>
          <td>
            <textarea class="large-text" id="bhg_ad_message" name="message" rows="6" 
              style="border:1px solid #cbd5e1;border-radius:6px;"><?php echo isset($editing_ad) ? wp_kses_post($editing_ad->message) : ''; ?></textarea>
            <p class="description">
              <?php echo esc_html__('You can include links, bold text, etc.', 'bonus-hunt-guesser'); ?>
            </p>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="bhg_ad_placement"><?php echo esc_html__('Placement', 'bonus-hunt-guesser'); ?></label>
          </th>
          <td>
            <select id="bhg_ad_placement" name="placement">
              <?php
              $placement = isset($editing_ad) ? sanitize_text_field($editing_ad->placement) : 'footer';
              ?>
              <option value="footer" <?php selected($placement, 'footer'); ?>>
                <?php echo esc_html__('Footer', 'bonus-hunt-guesser'); ?>
              </option>
              <option value="below_content" <?php selected($placement, 'below_content'); ?>>
                <?php echo esc_html__('Below Content', 'bonus-hunt-guesser'); ?>
              </option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="bhg_ad_visibility"><?php echo esc_html__('Visibility', 'bonus-hunt-guesser'); ?></label>
          </th>
          <td>
            <?php $vis = isset($editing_ad) ? sanitize_text_field($editing_ad->visibility) : 'all'; ?>
            <select id="bhg_ad_visibility" name="visibility">
              <option value="all" <?php selected($vis, 'all'); ?>><?php echo esc_html__('All visitors', 'bonus-hunt-guesser'); ?></option>
              <option value="guest" <?php selected($vis, 'guest'); ?>><?php echo esc_html__('Guests only', 'bonus-hunt-guesser'); ?></option>
              <option value="logged_in" <?php selected($vis, 'logged_in'); ?>><?php echo esc_html__('Logged-in only', 'bonus-hunt-guesser'); ?></option>
              <option value="affiliate" <?php selected($vis, 'affiliate'); ?>><?php echo esc_html__('Affiliates only', 'bonus-hunt-guesser'); ?></option>
              <option value="non_affiliate" <?php selected($vis, 'non_affiliate'); ?>><?php echo esc_html__('Non-affiliates only', 'bonus-hunt-guesser'); ?></option>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row">
            <label for="bhg_ad_target_pages"><?php echo esc_html__('Target pages (comma-separated slugs)', 'bonus-hunt-guesser'); ?></label>
          </th>
          <td>
            <input type="text" id="bhg_ad_target_pages" name="target_pages" class="regular-text"
              value="<?php echo isset($editing_ad) ? esc_attr($editing_ad->target_pages) : ''; ?>">
            <p class="description"><?php echo esc_html__('Leave empty to show everywhere.', 'bonus-hunt-guesser'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><?php echo esc_html__('Active', 'bonus-hunt-guesser'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="active" value="1" 
                <?php checked(isset($editing_ad) ? (int)$editing_ad->active : 1, 1); ?>>
              <?php echo esc_html__('Enable this ad', 'bonus-hunt-guesser'); ?>
            </label>
          </td>
        </tr>
      </tbody>
    </table>

    <?php if (!empty($editing_ad->id)): ?>
      <input type="hidden" name="id" value="<?php echo (int)$editing_ad->id; ?>">
    <?php endif; ?>

    <p>
      <button type="submit" class="button button-primary" style="padding:.6rem 1.1rem;">
        <?php echo esc_html__('Save Ad', 'bonus-hunt-guesser'); ?>
      </button>
      <a class="button" style="margin-left:8px;" href="<?php echo esc_url(admin_url('admin.php?page=bhg-ads')); ?>">
        <?php echo esc_html__('Cancel', 'bonus-hunt-guesser'); ?>
      </a>
    </p>
  </form>

  <hr>

  <?php
  // List existing ads (simple)
  global $wpdb;
  $ads = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bhg_ads ORDER BY id DESC");
  if (!$ads) {
      echo '<p>' . esc_html__('No ads yet.', 'bonus-hunt-guesser') . '</p>';
  } else {
      echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
      echo '<th>#</th><th>' . esc_html__('Message', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Placement', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Visibility', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Active', 'bonus-hunt-guesser') . '</th>';
      echo '</tr></thead><tbody>';
      foreach ($ads as $ad) {
          echo '<tr>';
          echo '<td>' . (int)$ad->id . '</td>';
          echo '<td>' . wp_kses_post($ad->message) . '</td>';
          echo '<td>' . esc_html($ad->placement) . '</td>';
          echo '<td>' . esc_html($ad->visibility) . '</td>';
          echo '<td>' . ((int)$ad->active ? '✓' : '—') . '</td>';
          // Actions: Edit & Remove
          $edit_url = esc_url( admin_url('admin.php?page=bhg-ads&edit='.(int)$ad->id) );
          $remove_form = '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" style="display:inline;">'
                       . wp_nonce_field('bhg_delete_ad','bhg_delete_ad_nonce', true, false)
                       . '<input type="hidden" name="action" value="bhg_delete_ad" />'
                       . '<input type="hidden" name="id" value="'.(int)$ad->id.'" />'
                       . '<button type="submit" class="button-link delete-link" onclick="return confirm(\'' . esc_js(__('Remove this ad?', 'bonus-hunt-guesser')) . '\');">'.esc_html__('Remove','bonus-hunt-guesser').'</button>'
                       . '</form>';
          echo '<td><a class="button-link" href="'.$edit_url.'">'.esc_html__('Edit','bonus-hunt-guesser').'</a> | ' . $remove_form . '</td>';
          echo '</tr>';
      }
      echo '</tbody></table>';
  }
  ?>
</div>
