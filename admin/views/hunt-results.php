<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) ); }

$hunt_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
if ( ! $hunt_id ) {
	wp_die( __( 'Missing hunt id', 'bonus-hunt-guesser' ) ); }

check_admin_referer( 'bhg_view_results_' . $hunt_id, 'bhg_nonce' );

if ( ! function_exists( 'bhg_get_hunt' ) || ! function_exists( 'bhg_get_all_ranked_guesses' ) ) {
	wp_die( __( 'Required helper functions are missing. Please include class-bhg-bonus-hunts.php helpers.', 'bonus-hunt-guesser' ) );
}

$hunt = bhg_get_hunt( $hunt_id );
if ( ! $hunt ) {
	wp_die( __( 'Hunt not found', 'bonus-hunt-guesser' ) ); }

$rows          = bhg_get_all_ranked_guesses( $hunt_id );
$winners_limit = (int) ( $hunt->winners_limit ?? 3 );
?>
<div class="wrap">
	<h1><?php echo esc_html( sprintf( __( 'Results — %s', 'bonus-hunt-guesser' ), $hunt->title ) ); ?></h1>
	<table class="widefat striped">
	<thead><tr>
		<th>#</th>
		<th><?php esc_html_e( 'User', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'Guess', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'Diff', 'bonus-hunt-guesser' ); ?></th>
	</tr></thead>
	<tbody>
		<?php
		if ( $rows ) :
			$i = 1; foreach ( $rows as $r ) :
				$highlight = ( $i <= $winners_limit ) ? ' style="background:#e6ffed;"' : '';
				$u         = get_userdata( (int) $r->user_id );
				$name      = $u ? $u->user_login : ( '#' . $r->user_id );
				?>
		<tr<?php echo $highlight; ?>>
		<td><?php echo (int) $i; ?></td>
		<td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . (int) $r->user_id ) ); ?>"><?php echo esc_html( $name ); ?></a></td>
		<td><?php echo esc_html( number_format_i18n( (float) $r->guess, 2 ) ); ?></td>
		<td><?php echo esc_html( number_format_i18n( (float) $r->diff, 2 ) ); ?></td>
		</tr>
					<?php
					++$i;
endforeach; else :
	?>
		<tr><td colspan="4"><?php esc_html_e( 'No guesses yet.', 'bonus-hunt-guesser' ); ?></td></tr>
		<?php endif; ?>
	</tbody>
	</table>
</div>
