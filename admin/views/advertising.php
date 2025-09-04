<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_ads';

// Delete action
if (isset($_GET['action'], $_GET['id']) && $_GET['action']==='delete' && isset($_GET['_wpnonce'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'bhg_delete_ad') && current_user_can('manage_options')) {
        $wpdb->delete($table, ['id'=>(int)$_GET['id']], ['%d']);
        wp_redirect(remove_query_arg(['action','id','_wpnonce']));
        exit;
    }
}

// Fetch ads
$ads = $wpdb->get_results("SELECT * FROM `$table` ORDER BY id DESC");
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Advertising', 'bonus-hunt-guesser'); ?></h1>

  <h2 style="margin-top:1em"><?php echo esc_html__('Existing Ads', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php echo esc_html__('ID', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Title/Message', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Placement', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Visibility', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($ads)) : ?>
        <tr><td colspan="5"><?php echo esc_html__('No ads yet.', 'bonus-hunt-guesser'); ?></td></tr>
      <?php else : foreach ($ads as $ad) : ?>
        <tr>
          <td><?php echo (int)$ad->id; ?></td>
          <td><?php echo isset($ad->title)? esc_html($ad->title) : wp_kses_post(wp_trim_words($ad->message, 12)); ?></td>
          <td><?php echo esc_html(isset($ad->placement)? $ad->placement : 'none'); ?></td>
          <td><?php echo esc_html(isset($ad->visibility)? $ad->visibility : 'all'); ?></td>
          <td>
            <a class="button" href="<?php echo esc_url(add_query_arg(['edit'=> (int)$ad->id])); ?>"><?php echo esc_html__('Edit', 'bonus-hunt-guesser'); ?></a>
            <a class="button-link-delete" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action'=>'delete','id'=>(int)$ad->id]), 'bhg_delete_ad')); ?>" onclick="return confirm('Delete this ad?');"><?php echo esc_html__('Remove', 'bonus-hunt-guesser'); ?></a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <h2 style="margin-top:2em"><?php echo isset($_GET['edit']) ? esc_html__('Edit Ad', 'bonus-hunt-guesser') : esc_html__('Add Ad', 'bonus-hunt-guesser'); ?></h2>
  <?php
    $ad = null;
    if (isset($_GET['edit'])) {
        $ad = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$table` WHERE id = %d", (int)$_GET['edit']));
    }
  ?>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:800px">
    <?php wp_nonce_field('bhg_save_ad'); ?>
    <input type="hidden" name="action" value="bhg_save_ad">
    <?php if ($ad) : ?>
      <input type="hidden" name="id" value="<?php echo (int)$ad->id; ?>">
    <?php endif; ?>

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="bhg_ad_title"><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></label></th>
          <td><input class="regular-text" id="bhg_ad_title" name="title" value="<?php echo esc_attr($ad ? ($ad->title ?? '') : ''); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_ad_message"><?php echo esc_html__('Message', 'bonus-hunt-guesser'); ?></label></th>
          <td><textarea class="large-text" rows="3" id="bhg_ad_message" name="message"><?php echo esc_textarea($ad ? ($ad->message ?? '') : ''); ?></textarea></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_ad_link"><?php echo esc_html__('Link URL (optional)', 'bonus-hunt-guesser'); ?></label></th>
          <td><input class="regular-text" id="bhg_ad_link" name="link" value="<?php echo esc_attr($ad ? ($ad->link ?? '') : ''); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_ad_place"><?php echo esc_html__('Placement', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <select id="bhg_ad_place" name="placement">
              <?php
                $opts = ['none','footer','bottom','sidebar','shortcode'];
                $sel  = $ad ? ($ad->placement ?? 'none') : 'none';
                foreach ($opts as $o) {
                    echo '<option value="'.esc_attr($o).'" '.selected($sel, $o, false).'>'.esc_html(ucfirst($o)).'</option>';
                }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_ad_vis"><?php echo esc_html__('Visible To', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <select id="bhg_ad_vis" name="visibility">
              <?php
                $opts = ['all','guests','logged','affiliates','non_affiliates'];
                $sel  = $ad ? ($ad->visibility ?? 'all') : 'all';
                foreach ($opts as $o) {
                    echo '<option value="'.esc_attr($o).'" '.selected($sel, $o, false).'>'.esc_html(ucfirst(str_replace('_',' ', $o))).'</option>';
                }
              ?>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
    <?php submit_button($ad ? __('Update Ad', 'bonus-hunt-guesser') : __('Create Ad', 'bonus-hunt-guesser')); ?>
  </form>
</div>
