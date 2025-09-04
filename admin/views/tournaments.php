
<?php if (!defined('ABSPATH')) exit; global $wpdb; $t = $wpdb->prefix.'bhg_tournaments'; ?>
<div class="wrap">
<h1><?php echo esc_html__('Tournaments', 'bonus-hunt-guesser'); ?></h1>

<div class="card" style="max-width:900px;padding:16px;margin:12px 0;">
  <h2><?php echo esc_html__('Create / Edit Tournament', 'bonus-hunt-guesser'); ?></h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('bhg_save_tournament','bhg_nonce'); ?>
    <input type="hidden" name="action" value="bhg_save_tournament">
    <input type="hidden" name="id" value="<?php echo isset($_GET['edit'])? (int)$_GET['edit'] : 0; ?>">
    <table class="form-table">
      <tr><th><?php esc_html_e('Type','bonus-hunt-guesser'); ?></th>
        <td>
          <select name="type" required>
            <?php $cur = isset($_GET['type'])? sanitize_text_field($_GET['type']) : ''; ?>
            <?php foreach(['weekly','monthly','yearly'] as $opt): ?>
              <option value="<?php echo esc_attr($opt); ?>" <?php selected($cur,$opt); ?>><?php echo esc_html(ucfirst($opt)); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr><th><?php esc_html_e('Period','bonus-hunt-guesser'); ?></th>
        <td><input type="text" name="period" value="<?php echo esc_attr(isset($_GET['period'])? $_GET['period']:''); ?>" placeholder="e.g. 2025-09 (monthly) or 2025 (yearly)" required></td>
      </tr>
      <tr><th><?php esc_html_e('Start Date','bonus-hunt-guesser'); ?></th>
        <td><input type="datetime-local" name="start_date" required></td>
      </tr>
      <tr><th><?php esc_html_e('End Date','bonus-hunt-guesser'); ?></th>
        <td><input type="datetime-local" name="end_date" required></td>
      </tr>
      <tr><th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
        <td>
          <select name="status">
            <option value="active"><?php esc_html_e('Active','bonus-hunt-guesser'); ?></option>
            <option value="closed"><?php esc_html_e('Closed','bonus-hunt-guesser'); ?></option>
          </select>
        </td>
      </tr>
    </table>
    <p><button class="button button-primary"><?php esc_html_e('Save Tournament','bonus-hunt-guesser'); ?></button></p>
  </form>
</div>

<div class="card" style="max-width:1200px;padding:16px;">
  <h2><?php esc_html_e('Existing Tournaments','bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
    <thead><tr>
      <th><?php esc_html_e('ID','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('Type','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('Period','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('Start','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('End','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
      <th><?php esc_html_e('Actions','bonus-hunt-guesser'); ?></th>
    </tr></thead>
    <tbody>
      <?php $rows = $wpdb->get_results("SELECT * FROM {$t} ORDER BY start_date DESC"); 
      if(!$rows){ echo '<tr><td colspan="7">'.esc_html__('No tournaments found','bonus-hunt-guesser').'</td></tr>'; }
      foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r->id; ?></td>
          <td><?php echo esc_html($r->type); ?></td>
          <td><?php echo esc_html($r->period); ?></td>
          <td><?php echo esc_html($r->start_date); ?></td>
          <td><?php echo esc_html($r->end_date); ?></td>
          <td><?php echo esc_html($r->status); ?></td>
          <td>
            <a class="button" href="<?php echo esc_url( add_query_arg(['page'=>'bhg-tournaments','edit'=>$r->id], admin_url('admin.php')) ); ?>"><?php esc_html_e('Edit','bonus-hunt-guesser'); ?></a>
            <a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( add_query_arg(['action'=>'bhg_delete_tournament','id'=>$r->id], admin_url('admin-post.php')), 'bhg_delete_tournament','bhg_nonce') ); ?>"><?php esc_html_e('Delete','bonus-hunt-guesser'); ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</div>
