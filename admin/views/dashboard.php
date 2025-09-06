<?php
/**
 * Admin dashboard view.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die(
		esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' )
	);
}

if ( ! function_exists( 'bhg_get_latest_closed_hunts' ) ) {
	wp_die(
		esc_html__(
			'Helper function bhg_get_latest_closed_hunts() missing. Please include class-bhg-bonus-hunts.php helpers.',
			'bonus-hunt-guesser'
		)
	);
}

$hunts = bhg_get_latest_closed_hunts( 3 );
?>

<div class="wrap bhg-wrap bhg-admin">
	<h1><?php esc_html_e( 'Latest Hunts', 'bonus-hunt-guesser' ); ?></h1>
	<div class="bhg-dashboard-grid">
		<?php if ( $hunts ) : ?>
			<?php foreach ( $hunts as $h ) : ?>
				<?php
				$winners = function_exists( 'bhg_get_top_winners_for_hunt' )
					? bhg_get_top_winners_for_hunt( $h->id, (int) $h->winners_count )
					: array();
				?>
				<div class="bhg-hunt-card">
					<h2 class="bhg-hunt-title">
						<span class="dashicons dashicons-calendar-alt bhg-icon" aria-hidden="true"></span>
						<?php echo esc_html( $h->title ); ?>
					</h2>
					<ul class="bhg-winners">
						<?php if ( $winners ) : ?>
							<?php foreach ( $winners as $i => $w ) : ?>
								<?php
								$u = get_userdata( (int) $w->user_id );
								/* translators: %d: user ID. */
								$nm = $u ? $u->user_login : sprintf( __( 'User #%d', 'bonus-hunt-guesser' ), (int) $w->user_id );
								?>
								<li class="bhg-winner winner-<?php echo (int) $i + 1; ?>">
									<span class="dashicons dashicons-awards bhg-icon" aria-hidden="true"></span>
									<span class="bhg-winner-name"><?php echo esc_html( $nm ); ?></span>
									<span class="bhg-winner-sep"><?php esc_html_e( '—', 'bonus-hunt-guesser' ); ?></span>
									<span class="bhg-winner-guess"><?php echo esc_html( number_format_i18n( (float) $w->guess, 2 ) ); ?></span>
									<span class="bhg-diff">
										(<?php esc_html_e( 'diff', 'bonus-hunt-guesser' ); ?>
										<?php echo esc_html( number_format_i18n( (float) $w->diff, 2 ) ); ?>)
									</span>
								</li>
							<?php endforeach; ?>
						<?php else : ?>
							<li class="bhg-winner none">
								<span class="dashicons dashicons-dismiss bhg-icon" aria-hidden="true"></span>
								<?php esc_html_e( 'No winners yet', 'bonus-hunt-guesser' ); ?>
							</li>
						<?php endif; ?>
					</ul>
					<div class="bhg-balance bhg-start">
						<span class="dashicons dashicons-money-alt bhg-icon" aria-hidden="true"></span>
						<?php esc_html_e( 'Start Balance', 'bonus-hunt-guesser' ); ?>:
						<?php echo esc_html( number_format_i18n( (float) $h->starting_balance, 2 ) ); ?>
					</div>
					<div class="bhg-balance bhg-final">
						<span class="dashicons dashicons-chart-bar bhg-icon" aria-hidden="true"></span>
						<?php esc_html_e( 'Final Balance', 'bonus-hunt-guesser' ); ?>:
						<?php echo ( null !== $h->final_balance ) ? esc_html( number_format_i18n( (float) $h->final_balance, 2 ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?>
					</div>
					<div class="bhg-closed">
						<span class="dashicons dashicons-clock bhg-icon" aria-hidden="true"></span>
						<?php esc_html_e( 'Closed At', 'bonus-hunt-guesser' ); ?>:
						<?php echo $h->closed_at ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $h->closed_at ) ) ) : esc_html__( '—', 'bonus-hunt-guesser' ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No closed hunts yet.', 'bonus-hunt-guesser' ); ?></p>
		<?php endif; ?>
	</div>
</div>

