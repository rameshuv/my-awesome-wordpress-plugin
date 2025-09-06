<?php
// phpcs:ignoreFile -- Legacy view requires refactoring for WordPress coding standards.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

global $wpdb;

$id   = absint( wp_unslash( $_GET['id'] ?? '' ) );
$hunt = bhg_get_hunt( $id );
if ( ! $hunt ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid hunt', 'bonus-hunt-guesser' ) . '</p></div>';
		return;
}

$aff_table = $wpdb->prefix . 'bhg_affiliates';
if ( isset( $allowed_tables ) && ! in_array( $aff_table, $allowed_tables, true ) ) {
		wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
}
$aff_table = esc_sql( $aff_table );
$affs      = $wpdb->get_results( "SELECT id, name FROM {$aff_table} ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized and query uses no dynamic values.
$sel       = isset( $hunt->affiliate_site_id ) ? (int) $hunt->affiliate_site_id : 0;

$paged    = max( 1, absint( wp_unslash( $_GET['ppaged'] ?? '' ) ) );
$per_page = 30;
$data     = bhg_get_hunt_participants( $id, $paged, $per_page );
$rows     = $data['rows'];
$total    = (int) $data['total'];
$pages    = max( 1, (int) ceil( $total / $per_page ) );
$base     = remove_query_arg( 'ppaged' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Edit Bonus Hunt', 'bonus-hunt-guesser' ); ?> <?php echo esc_html__( '—', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( $hunt->title ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bhg-max-width-900 bhg-margin-top-small">
		<?php wp_nonce_field( 'bhg_save_hunt' ); ?>
		<input type="hidden" name="action" value="bhg_save_hunt" />
		<input type="hidden" name="id" value="<?php echo (int) $hunt->id; ?>" />

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="bhg_title"><?php echo esc_html__( 'Title', 'bonus-hunt-guesser' ); ?></label></th>
					<td><input required class="regular-text" id="bhg_title" name="title" value="<?php echo esc_attr( $hunt->title ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_starting"><?php echo esc_html__( 'Starting Balance', 'bonus-hunt-guesser' ); ?></label></th>
					<td><input type="number" step="0.01" min="0" id="bhg_starting" name="starting_balance" value="<?php echo esc_attr( $hunt->starting_balance ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_num"><?php echo esc_html__( 'Number of Bonuses', 'bonus-hunt-guesser' ); ?></label></th>
					<td><input type="number" min="0" id="bhg_num" name="num_bonuses" value="<?php echo esc_attr( $hunt->num_bonuses ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_prizes"><?php echo esc_html__( 'Prizes', 'bonus-hunt-guesser' ); ?></label></th>
					<td><textarea class="large-text" rows="3" id="bhg_prizes" name="prizes"><?php echo esc_textarea( $hunt->prizes ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_affiliate"><?php echo esc_html__( 'Affiliate Site', 'bonus-hunt-guesser' ); ?></label></th>
					<td>
						<select id="bhg_affiliate" name="affiliate_site_id">
							<option value="0"><?php echo esc_html__( 'None', 'bonus-hunt-guesser' ); ?></option>
							<?php foreach ( $affs as $a ) : ?>
								<option value="<?php echo (int) $a->id; ?>" <?php selected( $sel, (int) $a->id ); ?>><?php echo esc_html( $a->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_winners"><?php echo esc_html__( 'Number of Winners', 'bonus-hunt-guesser' ); ?></label></th>
									<td><input type="number" min="1" max="25" id="bhg_winners" name="winners_count" value="<?php echo esc_attr( $hunt->winners_count ? $hunt->winners_count : 3 ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_final"><?php echo esc_html__( 'Final Balance', 'bonus-hunt-guesser' ); ?></label></th>
                                       <td><input type="number" step="0.01" min="0" id="bhg_final" name="final_balance" value="<?php echo esc_attr( $hunt->final_balance ); ?>" placeholder="<?php echo esc_attr( esc_html__( '-', 'bonus-hunt-guesser' ) ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bhg_status"><?php echo esc_html__( 'Status', 'bonus-hunt-guesser' ); ?></label></th>
					<td>
						<select id="bhg_status" name="status">
							<option value="open" <?php selected( $hunt->status, 'open' ); ?>><?php echo esc_html__( 'Open', 'bonus-hunt-guesser' ); ?></option>
							<option value="closed" <?php selected( $hunt->status, 'closed' ); ?>><?php echo esc_html__( 'Closed', 'bonus-hunt-guesser' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button( __( 'Save Hunt', 'bonus-hunt-guesser' ) ); ?>
	</form>

	<h2 class="bhg-margin-top-large"><?php esc_html_e( 'Participants', 'bonus-hunt-guesser' ); ?></h2>
	<p><?php echo esc_html( sprintf( _n( '%s participant', '%s participants', $total, 'bonus-hunt-guesser' ), number_format_i18n( $total ) ) ); ?></p>

	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'User', 'bonus-hunt-guesser' ); ?></th>
				<th><?php esc_html_e( 'Guess', 'bonus-hunt-guesser' ); ?></th>
				<th><?php esc_html_e( 'Date', 'bonus-hunt-guesser' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'bonus-hunt-guesser' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $rows ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No participants yet.', 'bonus-hunt-guesser' ); ?></td></tr>
				<?php
			else :
				foreach ( $rows as $r ) :
					$u    = get_userdata( (int) $r->user_id );
					$name = $u ? $u->display_name : sprintf( __( 'user#%d', 'bonus-hunt-guesser' ), (int) $r->user_id );
					?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $r->user_id ) ); ?>"><?php echo esc_html( $name ); ?></a></td>
					<td><?php echo esc_html( number_format_i18n( (float) $r->guess, 2 ) ); ?></td>
                                       <td><?php echo $r->created_at ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->created_at ) ) ) : esc_html__( '-', 'bonus-hunt-guesser' ); ?></td>
                                       <td>
                                               <?php
                                               $delete_url = wp_nonce_url(
                                                       add_query_arg(
                                                               array(
                                                                       'action'   => 'bhg_delete_guess',
                                                                       'guess_id' => (int) $r->id,
                                                               ),
                                                               admin_url( 'admin-post.php' )
                                                       ),
                                                       'bhg_delete_guess'
                                               );
                                               ?>
                                               <a href="<?php echo esc_url( $delete_url ); ?>" class="button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this guess?', 'bonus-hunt-guesser' ) ); ?>');"><?php esc_html_e( 'Remove', 'bonus-hunt-guesser' ); ?></a>
                                       </td>
                                </tr>
							<?php
			endforeach;
endif;
			?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<?php
				echo paginate_links(
					array(
						'base'      => add_query_arg( 'ppaged', '%#%', $base ),
						'format'    => '',
						'prev_text' => '&laquo;',
						'next_text' => '&raquo;',
						'total'     => $pages,
						'current'   => $paged,
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
