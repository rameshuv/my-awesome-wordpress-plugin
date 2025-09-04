<?php if (!defined('ABSPATH')) exit; global $wpdb; $t = $wpdb->prefix.'bhg_tournaments';

// Self-processing: save tournament
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['bhg_tournament_save'])) {
    if (!current_user_can('manage_options')) wp_die(esc_html__('Insufficient permissions','bonus-hunt-guesser'));
    check_admin_referer('bhg_tournament_save_action');

    $id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $data  = [
      'title'       => sanitize_text_field($_POST['title'] ?? ''),
      'description' => wp_kses_post($_POST['description'] ?? ''),
      'type'        => sanitize_text_field($_POST['type'] ?? 'weekly'),
      'start_date'  => sanitize_text_field($_POST['start_date'] ?? ''),
      'end_date'    => sanitize_text_field($_POST['end_date'] ?? ''),
      'status'      => sanitize_text_field($_POST['status'] ?? 'active'),
    ];

    if ($id > 0) {
        $wpdb->update($t, $data, ['id'=>$id]);
        wp_redirect(add_query_arg(['updated'=>1]));
        exit;
    } else {
        $wpdb->insert($t, $data);
        wp_redirect(add_query_arg(['created'=>1]));
        exit;
    }
}

// Fetch for edit
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$row = $edit_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM `$t` WHERE id=%d", $edit_id)) : null;

// List all
$rows = $wpdb->get_results("SELECT * FROM `$t` ORDER BY id DESC");
?>
<div class="wrap">
<h1><?php echo esc_html__('Tournaments', 'bonus-hunt-guesser'); ?></h1>

<div class="card" style="max-width:900px;padding:16px;margin:12px 0;">
  <h2><?php echo esc_html($row ? __('Edit Tournament','bonus-hunt-guesser') : __('Create Tournament','bonus-hunt-guesser')); ?></h2>
  <form method="post">
    <?php wp_nonce_field('bhg_tournament_save_action'); ?>
    <input type="hidden" name="bhg_tournament_save" value="1">
    <input type="hidden" name="id" value="<?php echo (int)($row->id ?? 0); ?>">
    <table class="form-table">
      <tr><th><?php esc_html_e('Title','bonus-hunt-guesser'); ?></th>
        <td><input type="text" class="regular-text" name="title" value="<?php echo esc_attr($row->title ?? ''); ?>" required></td>
      </tr>
      <tr><th><?php esc_html_e('Description','bonus-hunt-guesser'); ?></th>
        <td><textarea class="large-text" rows="3" name="description"><?php echo esc_textarea($row->description ?? ''); ?></textarea></td>
      </tr>
      <tr><th><?php esc_html_e('Type','bonus-hunt-guesser'); ?></th>
        <td>
          <select name="type" required>
            <?php $opts = ['weekly','monthly','quarterly','yearly','alltime']; $cur = $row->type ?? 'weekly'; foreach($opts as $opt): ?>
              <option value="<?php echo esc_attr($opt); ?>" <?php selected($cur,$opt); ?>><?php echo esc_html(ucfirst($opt)); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr><th><?php esc_html_e('Start Date','bonus-hunt-guesser'); ?></th>
        <td><input type="date" name="start_date" value="<?php echo esc_attr(isset($row->start_date)? substr($row->start_date,0,10) : ''); ?>"></td>
      </tr>
      <tr><th><?php esc_html_e('End Date','bonus-hunt-guesser'); ?></th>
        <td><input type="date" name="end_date" value="<?php echo esc_attr(isset($row->end_date)? substr($row->end_date,0,10) : ''); ?>"></td>
      </tr>
      <tr><th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
        <td>
          <select name="status">
            <?php $sopts=['active','archived']; $cur=$row->status ?? 'active'; foreach($sopts as $s): ?>
              <option value="<?php echo esc_attr($s); ?>" <?php selected($cur,$s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
            <?php endforeach; ?>
          </select>
        </td>
      </tr>
    </table>
    <?php submit_button($row ? __('Update Tournament','bonus-hunt-guesser') : __('Create Tournament','bonus-hunt-guesser')); ?>
  </form>
</div>

<h2 style="margin-top:1em;"><?php esc_html_e('All Tournaments','bonus-hunt-guesser'); ?></h2>
<table class="widefat striped">
  <thead><tr>
    <th><?php esc_html_e('ID','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('Title','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('Type','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('Start','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('End','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('Status','bonus-hunt-guesser'); ?></th>
    <th><?php esc_html_e('Actions','bonus-hunt-guesser'); ?></th>
  </tr></thead>
  <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="7"><em><?php esc_html_e('No tournaments yet.','bonus-hunt-guesser'); ?></em></td></tr>
    <?php else: foreach($rows as $r): ?>
      <tr>
        <td><?php echo (int)$r->id; ?></td>
        <td><?php echo esc_html($r->title); ?></td>
        <td><?php echo esc_html($r->type); ?></td>
        <td><?php echo esc_html($r->start_date); ?></td>
        <td><?php echo esc_html($r->end_date); ?></td>
        <td><?php echo esc_html($r->status); ?></td>
        <td><a class="button" href="<?php echo esc_url(add_query_arg(['edit'=>(int)$r->id])); ?>"><?php esc_html_e('Edit','bonus-hunt-guesser'); ?></a></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
</div>
