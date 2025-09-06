<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die(
		__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' )
	);
}

if ( ! function_exists( 'bhg_get_latest_closed_hunts' ) ) {
	wp_die(
		__(
			'Helper function bhg_get_latest_closed_hunts() missing. Please include class-bhg-bonus-hunts.php helpers.',
			'bonus-hunt-guesser'
		)
	);
}

$hunts = bhg_get_latest_closed_hunts( 3 );
?>
<div class="wrap">
  <h1><?php esc_html_e( 'Latest Hunts', 'bonus-hunt-guesser' ); ?></h1>
  <table class="widefat striped">
	<thead>
	  <tr>
		<th><?php esc_html_e( 'Bonushunt', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'All Winners', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'Start Balance', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'Final Balance', 'bonus-hunt-guesser' ); ?></th>
		<th><?php esc_html_e( 'Closed At', 'bonus-hunt-guesser' ); ?></th>
	  </tr>
	</thead>
	<tbody>
	  <?php if ( $hunts ) : ?>
		<?php foreach ( $hunts as $h ) : ?>
		  <?php
		  $winners = function_exists( 'bhg_get_top_winners_for_hunt' )
			  ? bhg_get_top_winners_for_hunt( $h->id, (int) $h->winners_count )
			  : array();
		  ?>
		  <tr>
			<td><?php echo esc_html( $h->title ); ?></td>
			<td>
			  <?php
			  if ( $winners ) {
				  $out = array();
				  foreach ( $winners as $w ) {
					  $u  = get_userdata( (int) $w->user_id );
					  $nm = $u ? $u->user_login : sprintf( __( 'User #%d', 'bonus-hunt-guesser' ), (int) $w->user_id );
                                          $out[] = sprintf(
                                                  '%s %s %s (%s %s)',
                                                  esc_html( $nm ),
                                                  esc_html__( '—', 'bonus-hunt-guesser' ),
                                                  esc_html( number_format_i18n( (float) $w->guess, 2 ) ),
                                                  esc_html__( 'diff', 'bonus-hunt-guesser' ),
                                                  esc_html( number_format_i18n( (float) $w->diff, 2 ) )
                                          );
				  }
				  echo esc_html( implode( ' • ', $out ) );
			  } else {
				  esc_html_e( 'No winners yet', 'bonus-hunt-guesser' );
			  }
			  ?>
			</td>
			<td><?php echo esc_html( number_format_i18n( (float) $h->starting_balance, 2 ) ); ?></td>
                        <td><?php echo ( $h->final_balance !== null ) ? esc_html( number_format_i18n( (float) $h->final_balance, 2 ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
                        <td><?php echo $h->closed_at ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $h->closed_at ) ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?></td>
		  </tr>
		<?php endforeach; ?>
	  <?php else : ?>
		<tr><td colspan="5"><?php esc_html_e( 'No closed hunts yet.', 'bonus-hunt-guesser' ); ?></td></tr>
	  <?php endif; ?>
	</tbody>
  </table>
</div>
