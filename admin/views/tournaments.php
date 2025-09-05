<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}
global $wpdb;
$table = $wpdb->prefix . 'bhg_tournaments';
$allowed_tables = [ $wpdb->prefix . 'bhg_tournaments' ];
if ( ! in_array( $table, $allowed_tables, true ) ) {
	wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
}
$table   = esc_sql( $table );

$edit_id = isset( $_GET['edit'] ) ? (int) wp_unslash( $_GET['edit'] ) : 0;
$row     = $edit_id
	? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $edit_id ) )
	: null;

$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );

$labels = [
	'weekly'    => __( 'Weekly', 'bonus-hunt-guesser' ),
	'monthly'   => __( 'Monthly', 'bonus-hunt-guesser' ),
	'quarterly' => __( 'Quarterly', 'bonus-hunt-guesser' ),
	'yearly'    => __( 'Yearly', 'bonus-hunt-guesser' ),
	'alltime'   => __( 'Alltime', 'bonus-hunt-guesser' ),
];
?>
<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>

  <h2 class="bhg-margin-top-small"><?php esc_html_e('All Tournaments', 'bonus-hunt-guesser'); ?></h2>
  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php esc_html_e('ID', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Title', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Type', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Start', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('End', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
		<th><?php esc_html_e('Actions', 'bonus-hunt-guesser'); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if (empty($rows)) : ?>
		<tr><td colspan="7"><em><?php esc_html_e('No tournaments yet.', 'bonus-hunt-guesser'); ?></em></td></tr>
	  <?php else : foreach ($rows as $r) : ?>
		<tr>
		  <td><?php echo (int)$r->id; ?></td>
		  <td><?php echo esc_html($r->title); ?></td>
		  <td><?php echo esc_html( $labels[ $r->type ] ?? $r->type ); ?></td>
		  <td><?php echo esc_html($r->start_date); ?></td>
		  <td><?php echo esc_html($r->end_date); ?></td>
		  <td><?php echo esc_html($r->status); ?></td>
		  <td>
			<a class="button" href="<?php echo esc_url(add_query_arg(['edit' => (int)$r->id])); ?>"><?php esc_html_e('Edit','bonus-hunt-guesser'); ?></a>
		  </td>
		</tr>
	  <?php endforeach; endif; ?>
	</tbody>
  </table>

  <h2 class="bhg-margin-top-large"><?php echo $row ? esc_html__('Edit Tournament', 'bonus-hunt-guesser') : esc_html__('Add Tournament', 'bonus-hunt-guesser'); ?></h2>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bhg-max-width-900">
	<?php wp_nonce_field('bhg_tournament_save_action'); ?>
	<input type="hidden" name="action" value="bhg_tournament_save" />
	<?php if ($row): ?><input type="hidden" name="id" value="<?php echo (int)$row->id; ?>" /><?php endif; ?>
	<table class="form-table">
	  <tr>
		<th><label for="bhg_t_title"><?php esc_html_e('Title','bonus-hunt-guesser'); ?></label></th>
		<td><input id="bhg_t_title" class="regular-text" name="title" value="<?php echo esc_attr($row->title ?? ''); ?>" required /></td>
	  </tr>
	  <tr>
		<th><label for="bhg_t_desc"><?php esc_html_e('Description','bonus-hunt-guesser'); ?></label></th>
		<td><textarea id="bhg_t_desc" class="large-text" rows="4" name="description"><?php echo esc_textarea($row->description ?? ''); ?></textarea></td>
	  </tr>
	  <tr>
		<th><label for="bhg_t_type"><?php esc_html_e('Type','bonus-hunt-guesser'); ?></label></th>
		<td>
		  <?php
		  $types = [ 'weekly', 'monthly', 'quarterly', 'yearly', 'alltime' ];
		  $cur   = $row->type ?? 'weekly';
		  ?>
		  <select id="bhg_t_type" name="type">
			<?php foreach ( $types as $t ) : ?>
			  <option value="<?php echo esc_attr( $t ); ?>" <?php selected( $cur, $t ); ?>><?php echo esc_html( $labels[ $t ] ); ?></option>
			<?php endforeach; ?>
		  </select>
		</td>
	  </tr>
	  <tr>
		<th><label for="bhg_t_start"><?php esc_html_e('Start Date','bonus-hunt-guesser'); ?></label></th>
		<td><input id="bhg_t_start" type="date" name="start_date" value="<?php echo esc_attr($row->start_date ?? ''); ?>" /></td>
	  </tr>
	  <tr>
		<th><label for="bhg_t_end"><?php esc_html_e('End Date','bonus-hunt-guesser'); ?></label></th>
		<td><input id="bhg_t_end" type="date" name="end_date" value="<?php echo esc_attr($row->end_date ?? ''); ?>" /></td>
	  </tr>
	  <tr>
		<th><label for="bhg_t_status"><?php esc_html_e('Status','bonus-hunt-guesser'); ?></label></th>
		<td>
		  <?php $st = ['active','archived']; $cur = $row->status ?? 'active'; ?>
		  <select id="bhg_t_status" name="status">
			<?php foreach ($st as $v): ?>
			  <option value="<?php echo esc_attr($v); ?>" <?php selected($cur, $v); ?>><?php echo esc_html(ucfirst($v)); ?></option>
			<?php endforeach; ?>
		  </select>
		</td>
	  </tr>
	</table>
	<?php submit_button($row ? __('Update Tournament','bonus-hunt-guesser') : __('Create Tournament','bonus-hunt-guesser')); ?>
  </form>
</div>
