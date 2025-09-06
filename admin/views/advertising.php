<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$table = $wpdb->prefix . 'bhg_ads';
$allowed_tables = [ $wpdb->prefix . 'bhg_ads' ];
if ( ! in_array( $table, $allowed_tables, true ) ) {
	wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
}
$table  = esc_sql( $table );

$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$ad_id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$edit_id  = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;

// Delete action
if ( 'delete' === $action && $ad_id && isset( $_GET['_wpnonce'] ) ) {
	$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
	if ( wp_verify_nonce( $nonce, 'bhg_delete_ad' ) && current_user_can( 'manage_options' ) ) {
		$wpdb->delete( $table, [ 'id' => $ad_id ], [ '%d' ] );
		wp_safe_redirect( remove_query_arg( [ 'action', 'id', '_wpnonce' ] ) );
		exit;
	}
}

// Fetch ads
$ads = $wpdb->get_results(
	"SELECT * FROM {$table} ORDER BY id DESC"
);
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php echo esc_html__('Advertising', 'bonus-hunt-guesser'); ?></h1>

  <h2 style="margin-top:1em"><?php echo esc_html__('Existing Ads', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php echo esc_html__('ID', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Title/Content', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Placement', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Visible To', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Active', 'bonus-hunt-guesser'); ?></th>
		<th><?php echo esc_html__('Actions', 'bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if (empty($ads)) : ?>
		<tr><td colspan="6"><?php echo esc_html__('No ads yet.', 'bonus-hunt-guesser'); ?></td></tr>
	  <?php else : foreach ($ads as $ad) : ?>
		<tr>
		  <td><?php echo (int)$ad->id; ?></td>
		  <td><?php echo isset($ad->title) && $ad->title !== '' ? esc_html($ad->title) : wp_kses_post(wp_trim_words($ad->content, 12)); ?></td>
		  <td><?php echo esc_html(isset($ad->placement)? $ad->placement : 'none'); ?></td>
		  <td><?php echo esc_html(isset($ad->visible_to)? $ad->visible_to : 'all'); ?></td>
		  <td><?php echo (int)$ad->active === 1 ? esc_html__('Yes','bonus-hunt-guesser') : esc_html__('No','bonus-hunt-guesser'); ?></td>
		  <td>
			<a class="button" href="<?php echo esc_url(add_query_arg(['edit'=> (int)$ad->id])); ?>"><?php echo esc_html__('Edit', 'bonus-hunt-guesser'); ?></a>
			<a class="button-link-delete" href="<?php echo esc_url(wp_nonce_url(add_query_arg(['action'=>'delete','id'=>(int)$ad->id]), 'bhg_delete_ad')); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this ad?', 'bonus-hunt-guesser' ) ); ?>');"><?php echo esc_html__('Remove', 'bonus-hunt-guesser'); ?></a>
		  </td>
		</tr>
	  <?php endforeach; endif; ?>
	</tbody>
  </table>

  <h2 style="margin-top:2em"><?php echo $edit_id ? esc_html__('Edit Ad', 'bonus-hunt-guesser') : esc_html__('Add Ad', 'bonus-hunt-guesser'); ?></h2>
  <?php
        $ad = null;
        if ( $edit_id ) {
                $ad = $wpdb->get_row(
                        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $edit_id )
                );
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
		  <th scope="row"><label for="bhg_ad_content"><?php echo esc_html__('Content', 'bonus-hunt-guesser'); ?></label></th>
		  <td><textarea class="large-text" rows="3" id="bhg_ad_content" name="content"><?php echo esc_textarea($ad ? ($ad->content ?? '') : ''); ?></textarea></td>
		</tr>
		<tr>
		  <th scope="row"><label for="bhg_ad_link"><?php echo esc_html__('Link URL (optional)', 'bonus-hunt-guesser'); ?></label></th>
		  <td><input class="regular-text" id="bhg_ad_link" name="link_url" value="<?php echo esc_attr($ad ? ($ad->link_url ?? '') : ''); ?>"></td>
		</tr>
		<tr>
		  <th scope="row"><label for="bhg_ad_place"><?php echo esc_html__('Placement', 'bonus-hunt-guesser'); ?></label></th>
		  <td>
			<select id="bhg_ad_place" name="placement">
			  <?php
				$placement_opts   = ['none', 'footer', 'bottom', 'sidebar', 'shortcode'];
				$placement_labels = [
					'none'      => __('None', 'bonus-hunt-guesser'),
					'footer'    => __('Footer', 'bonus-hunt-guesser'),
					'bottom'    => __('Bottom', 'bonus-hunt-guesser'),
					'sidebar'   => __('Sidebar', 'bonus-hunt-guesser'),
					'shortcode' => __('Shortcode', 'bonus-hunt-guesser'),
				];
				$sel = $ad ? ($ad->placement ?? 'none') : 'none';
				foreach ( $placement_opts as $o ) {
					$label = $placement_labels[ $o ] ?? $o;
					echo '<option value="' . esc_attr( $o ) . '" ' . selected( $sel, $o, false ) . '>' . esc_html( $label ) . '</option>';
				}
			  ?>
			</select>
		  </td>
		</tr>
		<tr>
		  <th scope="row"><label for="bhg_ad_vis"><?php echo esc_html__('Visible To', 'bonus-hunt-guesser'); ?></label></th>
		  <td>
			<select id="bhg_ad_vis" name="visible_to">
			  <?php
				$visible_opts   = ['all', 'guests', 'logged_in', 'affiliates', 'non_affiliates'];
				$visible_labels = [
					'all'            => __('All', 'bonus-hunt-guesser'),
					'guests'         => __('Guests', 'bonus-hunt-guesser'),
					'logged_in'      => __('Logged In', 'bonus-hunt-guesser'),
					'affiliates'     => __('Affiliates', 'bonus-hunt-guesser'),
					'non_affiliates' => __('Non Affiliates', 'bonus-hunt-guesser'),
				];
				$sel = $ad ? ($ad->visible_to ?? 'all') : 'all';
				foreach ( $visible_opts as $o ) {
					$label = $visible_labels[ $o ] ?? $o;
					echo '<option value="' . esc_attr( $o ) . '" ' . selected( $sel, $o, false ) . '>' . esc_html( $label ) . '</option>';
				}
			  ?>
			</select>
		  </td>
		</tr>
		<tr>
		  <th scope="row"><label for="bhg_ad_targets"><?php echo esc_html__('Target Page Slugs', 'bonus-hunt-guesser'); ?></label></th>
		  <td><input class="regular-text" id="bhg_ad_targets" name="target_pages" value="<?php echo esc_attr($ad ? ($ad->target_pages ?? '') : ''); ?>" placeholder="page-slug-1,page-slug-2"></td>
		</tr>
		<tr>
		  <th scope="row"><label for="bhg_ad_active"><?php echo esc_html__('Active', 'bonus-hunt-guesser'); ?></label></th>
		  <td><input type="checkbox" id="bhg_ad_active" name="active" value="1" <?php checked($ad ? ($ad->active ?? 1) : 1, 1); ?>></td>
		</tr>
	  </tbody>
	</table>
	<?php submit_button($ad ? __('Update Ad', 'bonus-hunt-guesser') : __('Create Ad', 'bonus-hunt-guesser')); ?>
  </form>
</div>
