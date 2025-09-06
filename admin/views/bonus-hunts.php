<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Admin view for managing bonus hunts.
 */

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

global $wpdb;
$hunts_table    = $wpdb->prefix . 'bhg_bonus_hunts';
$guesses_table  = $wpdb->prefix . 'bhg_guesses';
$allowed_tables = array(
	$wpdb->prefix . 'bhg_bonus_hunts',
	$wpdb->prefix . 'bhg_guesses',
	$wpdb->prefix . 'bhg_affiliates',
	$wpdb->users,
);
if ( ! in_array( $hunts_table, $allowed_tables, true ) || ! in_array( $guesses_table, $allowed_tables, true ) ) {
        wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
}

$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'list';

if ( 'edit' === $view ) {
		require __DIR__ . '/bonus-hunts-edit.php';
		return;
}

/** LIST VIEW */
if ( 'list' === $view ) :
		$current_page = max( 1, isset( $_GET['paged'] ) ? (int) wp_unslash( $_GET['paged'] ) : 1 );
		$per_page     = 30;
		$offset       = ( $current_page - 1 ) * $per_page;

                $hunts = $wpdb->get_results(
                        $wpdb->prepare(
                                "SELECT id, title, starting_balance, final_balance, winners_count, status FROM {$hunts_table} ORDER BY id DESC LIMIT %d OFFSET %d",
                                $per_page,
                                $offset
                        )
                );

		$status_labels = array(
			'open'   => __( 'Open', 'bonus-hunt-guesser' ),
			'closed' => __( 'Closed', 'bonus-hunt-guesser' ),
		);

		$total    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hunts_table}" );
		$base_url = remove_query_arg( array( 'paged' ) );
		?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Bonus Hunts', 'bonus-hunt-guesser' ); ?></h1>
	<a href="<?php echo esc_url( add_query_arg( array( 'view' => 'add' ) ) ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'bonus-hunt-guesser' ); ?></a>

	<?php if ( isset( $_GET['closed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['closed'] ) ) ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Hunt closed successfully.', 'bonus-hunt-guesser' ); ?></p></div>
	<?php endif; ?>

	<table class="widefat striped bhg-margin-top-small">
	<thead>
		<tr>
		<th><?php echo esc_html__( 'ID', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Title', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Start Balance', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Final Balance', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Winners', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Status', 'bonus-hunt-guesser' ); ?></th>
		<th><?php echo esc_html__( 'Actions', 'bonus-hunt-guesser' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php if ( empty( $hunts ) ) : ?>
		<tr><td colspan="7"><?php echo esc_html__( 'No hunts found.', 'bonus-hunt-guesser' ); ?></td></tr>
			<?php
		else :
			foreach ( $hunts as $h ) :
				?>
		<tr>
			<td><?php echo (int) $h->id; ?></td>
			<td><a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'view' => 'edit',
							'id'   => (int) $h->id,
						)
					)
				);
				?>
							"><?php echo esc_html( $h->title ); ?></a></td>
			<td><?php echo esc_html( number_format_i18n( (float) $h->starting_balance, 2 ) ); ?></td>
					<td><?php echo null !== $h->final_balance ? esc_html( number_format_i18n( (float) $h->final_balance, 2 ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
			<td><?php echo (int) ( $h->winners_count ?? 3 ); ?></td>
					<td><?php echo esc_html( $status_labels[ $h->status ] ?? $h->status ); ?></td>
			<td>
			<a class="button" href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'view' => 'edit',
							'id'   => (int) $h->id,
						)
					)
				);
				?>
									"><?php echo esc_html__( 'Edit', 'bonus-hunt-guesser' ); ?></a>
						<?php if ( 'open' === $h->status ) : ?>
							<a class="button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'view' => 'close',
										'id'   => (int) $h->id,
									)
								)
							);
							?>
													"><?php echo esc_html__( 'Close Hunt', 'bonus-hunt-guesser' ); ?></a>
						<?php elseif ( 'closed' === $h->status && null !== $h->final_balance ) : ?>
							<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=bhg-bonus-hunts-results&id=' . (int) $h->id ) ); ?>"><?php echo esc_html__( 'Results', 'bonus-hunt-guesser' ); ?></a>
						<?php endif; ?>
			</td>
		</tr>
				<?php
			endforeach;
endif;
		?>
		</tbody>
	</table>

	<?php
		$total_pages = (int) ceil( $total / $per_page );
	if ( $total_pages > 1 ) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo paginate_links(
				array(
					'base'      => add_query_arg( 'paged', '%#%', $base_url ),
					'format'    => '',
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
					'total'     => $total_pages,
					'current'   => $current_page,
				)
			);
			echo '</div></div>';
	}
	?>
</div>
<?php endif; ?>

<?php
/** CLOSE VIEW */
if ( 'close' === $view ) :
		$id = isset( $_GET['id'] ) ? (int) wp_unslash( $_GET['id'] ) : 0;
        $hunt   = $wpdb->get_row(
                $wpdb->prepare( "SELECT id, title, status FROM {$hunts_table} WHERE id = %d", $id )
        );
	if ( ! $hunt || 'open' !== $hunt->status ) :
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid hunt.', 'bonus-hunt-guesser' ) . '</p></div>';
	else :
		?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Close Bonus Hunt', 'bonus-hunt-guesser' ); ?> <?php echo esc_html__( '—', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( $hunt->title ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bhg-max-width-400 bhg-margin-top-small">
		<?php wp_nonce_field( 'bhg_close_hunt' ); ?>
	<input type="hidden" name="action" value="bhg_close_hunt" />
	<input type="hidden" name="hunt_id" value="<?php echo (int) $hunt->id; ?>" />
	<table class="form-table" role="presentation">
		<tbody>
		<tr>
			<th scope="row"><label for="bhg_final_balance"><?php echo esc_html__( 'Final Balance', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input type="number" step="0.01" min="0" id="bhg_final_balance" name="final_balance" required></td>
		</tr>
		</tbody>
	</table>
		<?php submit_button( __( 'Close Hunt', 'bonus-hunt-guesser' ) ); ?>
	</form>
</div>
		<?php
	endif;
endif;
?>

<?php
/** ADD VIEW */
if ( $view === 'add' ) :
	?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Add New Bonus Hunt', 'bonus-hunt-guesser' ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bhg-max-width-900 bhg-margin-top-small">
	<?php wp_nonce_field( 'bhg_save_hunt' ); ?>
	<input type="hidden" name="action" value="bhg_save_hunt" />

	<table class="form-table" role="presentation">
		<tbody>
		<tr>
			<th scope="row"><label for="bhg_title"><?php echo esc_html__( 'Title', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input required class="regular-text" id="bhg_title" name="title" value=""></td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_starting"><?php echo esc_html__( 'Starting Balance', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input type="number" step="0.01" min="0" id="bhg_starting" name="starting_balance" value=""></td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_num"><?php echo esc_html__( 'Number of Bonuses', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input type="number" min="0" id="bhg_num" name="num_bonuses" value=""></td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_prizes"><?php echo esc_html__( 'Prizes', 'bonus-hunt-guesser' ); ?></label></th>
			<td><textarea class="large-text" rows="3" id="bhg_prizes" name="prizes"></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_affiliate"><?php echo esc_html__( 'Affiliate Site', 'bonus-hunt-guesser' ); ?></label></th>
			<td>
			<?php
			$aff_table = $wpdb->prefix . 'bhg_affiliates';
			if ( ! in_array( $aff_table, $allowed_tables, true ) ) {
				wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
			}
			$aff_table = esc_sql( $aff_table );
			$affs      = $wpdb->get_results(
				"SELECT id, name FROM {$aff_table} ORDER BY name ASC"
			);
			$sel       = isset( $hunt->affiliate_site_id ) ? (int) $hunt->affiliate_site_id : 0;
			?>
			<select id="bhg_affiliate" name="affiliate_site_id">
				<option value="0"><?php echo esc_html__( 'None', 'bonus-hunt-guesser' ); ?></option>
				<?php foreach ( $affs as $a ) : ?>
				<option value="<?php echo (int) $a->id; ?>" 
					<?php
					if ( $sel === (int) $a->id ) {
						echo 'selected';}
					?>
				><?php echo esc_html( $a->name ); ?></option>
				<?php endforeach; ?>
			</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_winners"><?php echo esc_html__( 'Number of Winners', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input type="number" min="1" max="25" id="bhg_winners" name="winners_count" value="3"></td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_status"><?php echo esc_html__( 'Status', 'bonus-hunt-guesser' ); ?></label></th>
			<td>
			<select id="bhg_status" name="status">
				<option value="open"><?php echo esc_html__( 'Open', 'bonus-hunt-guesser' ); ?></option>
				<option value="closed"><?php echo esc_html__( 'Closed', 'bonus-hunt-guesser' ); ?></option>
			</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="bhg_final"><?php echo esc_html__( 'Final Balance (optional)', 'bonus-hunt-guesser' ); ?></label></th>
			<td><input type="number" step="0.01" min="0" id="bhg_final" name="final_balance" value=""></td>
		</tr>
		</tbody>
	</table>
	<?php submit_button( __( 'Create Bonus Hunt', 'bonus-hunt-guesser' ) ); ?>
	</form>
</div>
<?php endif; ?>
