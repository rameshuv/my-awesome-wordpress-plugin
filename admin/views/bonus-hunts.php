<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Admin view for managing bonus hunts.
 */

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

global $wpdb;
$hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
$guesses_table = $wpdb->prefix . 'bhg_guesses';

$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'list';

/** LIST VIEW */
if ( 'list' === $view ) :
    $hunts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$hunts_table` ORDER BY id DESC" ) );
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Bonus Hunts', 'bonus-hunt-guesser'); ?></h1>
  <a href="<?php echo esc_url(add_query_arg(['view'=>'add'])); ?>" class="page-title-action"><?php echo esc_html__('Add New', 'bonus-hunt-guesser'); ?></a>

  <table class="widefat striped" style="margin-top:1em">
    <thead>
      <tr>
        <th><?php echo esc_html__('ID', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Start Balance', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Final Balance', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Winners', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Status', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if ( empty( $hunts ) ) : ?>
        <tr><td colspan="7"><?php echo esc_html__('No hunts found.', 'bonus-hunt-guesser'); ?></td></tr>
      <?php else : foreach ( $hunts as $h ) : ?>
        <tr>
          <td><?php echo (int)$h->id; ?></td>
          <td><a href="<?php echo esc_url(add_query_arg(['view'=>'edit','id'=>(int)$h->id])); ?>"><?php echo esc_html($h->title); ?></a></td>
          <td><?php echo esc_html(number_format_i18n((float)$h->starting_balance, 2)); ?></td>
          <td><?php echo $h->final_balance !== null ? esc_html(number_format_i18n((float)$h->final_balance, 2)) : '—'; ?></td>
          <td><?php echo (int)($h->winners_count ?? 3); ?></td>
          <td><?php echo esc_html($h->status); ?></td>
          <td>
            <a class="button" href="<?php echo esc_url(add_query_arg(['view'=>'edit','id'=>(int)$h->id])); ?>"><?php echo esc_html__('Edit', 'bonus-hunt-guesser'); ?></a>
            <?php if ($h->status === 'closed' && $h->final_balance !== null) : ?>
              <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=bhg-bonus-hunts-results&id='.(int)$h->id)); ?>"><?php echo esc_html__('Results', 'bonus-hunt-guesser'); ?></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php
/** ADD VIEW */
if ($view === 'add') : ?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Add New Bonus Hunt', 'bonus-hunt-guesser'); ?></h1>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:900px;margin-top:1em">
    <?php wp_nonce_field('bhg_save_hunt'); ?>
    <input type="hidden" name="action" value="bhg_save_hunt" />

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="bhg_title"><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></label></th>
          <td><input required class="regular-text" id="bhg_title" name="title" value=""></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_starting"><?php echo esc_html__('Starting Balance', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" step="0.01" min="0" id="bhg_starting" name="starting_balance" value=""></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_num"><?php echo esc_html__('Number of Bonuses', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" min="0" id="bhg_num" name="num_bonuses" value=""></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_prizes"><?php echo esc_html__('Prizes', 'bonus-hunt-guesser'); ?></label></th>
          <td><textarea class="large-text" rows="3" id="bhg_prizes" name="prizes"></textarea></td>
        
        <tr>
          <th scope="row"><label for="bhg_affiliate"><?php echo esc_html__('Affiliate Site', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <?php $affs = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM `{$wpdb->prefix}bhg_affiliates` ORDER BY name ASC" ) ); $sel = isset($hunt->affiliate_site_id)? (int)$hunt->affiliate_site_id : 0; ?>
            <select id="bhg_affiliate" name="affiliate_site_id">
              <option value="0"><?php echo esc_html__('None', 'bonus-hunt-guesser'); ?></option>
              <?php foreach ($affs as $a): ?>
                <option value="<?php echo (int)$a->id; ?>" <?php if ($sel === (int)$a->id) echo 'selected'; ?>><?php echo esc_html($a->name); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_winners"><?php echo esc_html__('Number of Winners', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" min="1" max="25" id="bhg_winners" name="winners_count" value="3"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_status"><?php echo esc_html__('Status', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <select id="bhg_status" name="status">
              <option value="open"><?php echo esc_html__('Open', 'bonus-hunt-guesser'); ?></option>
              <option value="closed"><?php echo esc_html__('Closed', 'bonus-hunt-guesser'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_final"><?php echo esc_html__('Final Balance (optional)', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" step="0.01" min="0" id="bhg_final" name="final_balance" value=""></td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(__('Create Bonus Hunt', 'bonus-hunt-guesser')); ?>
  </form>
</div>
<?php endif; ?>

<?php
/** EDIT VIEW */
if ($view === 'edit') :
    $id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $hunt  = $wpdb->get_row($wpdb->prepare("SELECT * FROM `$hunts_table` WHERE id = %d", $id));
    if (!$hunt) {
        echo '<div class="notice notice-error"><p>'.esc_html__('Invalid hunt', 'bonus-hunt-guesser').'</p></div>';
        return;
    }
    $guesses = $wpdb->get_results($wpdb->prepare("SELECT g.*, u.display_name FROM `$guesses_table` g LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id WHERE g.hunt_id = %d ORDER BY g.id ASC", $id));
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Edit Bonus Hunt', 'bonus-hunt-guesser'); ?> — <?php echo esc_html($hunt->title); ?></h1>

  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:900px;margin-top:1em">
    <?php wp_nonce_field('bhg_save_hunt'); ?>
    <input type="hidden" name="action" value="bhg_save_hunt" />
    <input type="hidden" name="id" value="<?php echo (int)$hunt->id; ?>" />

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="bhg_title"><?php echo esc_html__('Title', 'bonus-hunt-guesser'); ?></label></th>
          <td><input required class="regular-text" id="bhg_title" name="title" value="<?php echo esc_attr($hunt->title); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_starting"><?php echo esc_html__('Starting Balance', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" step="0.01" min="0" id="bhg_starting" name="starting_balance" value="<?php echo esc_attr($hunt->starting_balance); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_num"><?php echo esc_html__('Number of Bonuses', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" min="0" id="bhg_num" name="num_bonuses" value="<?php echo esc_attr($hunt->num_bonuses); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_prizes"><?php echo esc_html__('Prizes', 'bonus-hunt-guesser'); ?></label></th>
          <td><textarea class="large-text" rows="3" id="bhg_prizes" name="prizes"><?php echo esc_textarea($hunt->prizes); ?></textarea></td>
        
        <tr>
          <th scope="row"><label for="bhg_affiliate"><?php echo esc_html__('Affiliate Site', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <?php $affs = $wpdb->get_results( $wpdb->prepare( "SELECT id, name FROM `{$wpdb->prefix}bhg_affiliates` ORDER BY name ASC" ) ); $sel = isset($hunt->affiliate_site_id)? (int)$hunt->affiliate_site_id : 0; ?>
            <select id="bhg_affiliate" name="affiliate_site_id">
              <option value="0"><?php echo esc_html__('None', 'bonus-hunt-guesser'); ?></option>
              <?php foreach ($affs as $a): ?>
                <option value="<?php echo (int)$a->id; ?>" <?php if ($sel === (int)$a->id) echo 'selected'; ?>><?php echo esc_html($a->name); ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_winners"><?php echo esc_html__('Number of Winners', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" min="1" max="25" id="bhg_winners" name="winners_count" value="<?php echo esc_attr($hunt->winners_count ?: 3); ?>"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_final"><?php echo esc_html__('Final Balance', 'bonus-hunt-guesser'); ?></label></th>
          <td><input type="number" step="0.01" min="0" id="bhg_final" name="final_balance" value="<?php echo esc_attr($hunt->final_balance); ?>" placeholder="—"></td>
        </tr>
        <tr>
          <th scope="row"><label for="bhg_status"><?php echo esc_html__('Status', 'bonus-hunt-guesser'); ?></label></th>
          <td>
            <select id="bhg_status" name="status">
              <option value="open" <?php selected($hunt->status, 'open'); ?>><?php echo esc_html__('Open', 'bonus-hunt-guesser'); ?></option>
              <option value="closed" <?php selected($hunt->status, 'closed'); ?>><?php echo esc_html__('Closed', 'bonus-hunt-guesser'); ?></option>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
    <?php submit_button(__('Save Hunt', 'bonus-hunt-guesser')); ?>
  </form>

  <h2 style="margin-top:2em"><?php echo esc_html__('Participants', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
    <thead>
      <tr>
        <th><?php echo esc_html__('User', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Guess', 'bonus-hunt-guesser'); ?></th>
        <th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($guesses)) : ?>
        <tr><td colspan="3"><?php echo esc_html__('No participants yet.', 'bonus-hunt-guesser'); ?></td></tr>
      <?php else : foreach ($guesses as $g) : ?>
        <tr>
          <td>
            <?php
              $name = $g->display_name ? $g->display_name : ('user#' . (int)$g->user_id);
              $url  = admin_url('user-edit.php?user_id=' . (int)$g->user_id);
              echo '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
            ?>
          </td>
          <td><?php echo esc_html(number_format_i18n((float)($g->guess ?? 0), 2)); ?></td>
          <td>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Delete this guess?', 'bonus-hunt-guesser')); ?>');" style="display:inline">
              <?php wp_nonce_field('bhg_delete_guess'); ?>
              <input type="hidden" name="action" value="bhg_delete_guess">
              <input type="hidden" name="guess_id" value="<?php echo (int)$g->id; ?>">
              <button type="submit" class="button-link-delete"><?php echo esc_html__('Remove', 'bonus-hunt-guesser'); ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
