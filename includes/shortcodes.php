<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BHG Shortcodes
 * Implements all required frontend shortcodes with full DB wiring.
 */

// [bhg_user_profile] — profile only (no forms inside)
add_shortcode(
	'bhg_user_profile',
	function ( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="bhg-box">' . esc_html__( 'Please log in to view your profile.', 'bonus-hunt-guesser' ) . '</div>';
		}

		$u   = wp_get_current_user();
		$uid = get_current_user_id();

		// Basic profile
		ob_start(); ?>
	<div class="bhg-box">
		<h3><?php echo esc_html( $u->display_name ?: $u->user_login ); ?></h3>
		<p><?php echo esc_html( $u->user_email ); ?></p>
		<?php
		if ( function_exists( 'bhg_get_affiliate_sites' ) ) :
			$sites = bhg_get_affiliate_sites( $uid );
			if ( ! empty( $sites ) ) :
				?>
				<h4><?php esc_html_e( 'Affiliate Sites', 'bonus-hunt-guesser' ); ?></h4>
				<ul class="bhg-list">
				<?php foreach ( $sites as $s ) : ?>
					<li><?php echo esc_html( is_array( $s ) ? ( $s['name'] ?? $s['url'] ?? '' ) : (string) $s ); ?></li>
				<?php endforeach; ?>
				</ul>
				<?php
			endif;
		endif;
		?>

		<?php
		// Hunts participated
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT h.id, h.title, h.status 
            FROM {$wpdb->prefix}bhg_bonus_hunts h 
            INNER JOIN {$wpdb->prefix}bhg_guesses g ON g.hunt_id = h.id 
            WHERE g.user_id = %d 
            ORDER BY h.id DESC",
				$uid
			)
		);

		if ( $rows ) :
			?>
			<h4><?php esc_html_e( 'Your Hunts', 'bonus-hunt-guesser' ); ?></h4>
			<ul class="bhg-list">
			<?php foreach ( $rows as $r ) : ?>
				<li><?php echo esc_html( $r->title ?: ( '#' . $r->id ) ); ?> — <?php echo esc_html( ucfirst( $r->status ) ); ?></li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
		<?php
		return ob_get_clean();
	}
);

// [bhg_active_hunt] — show all open hunts with description, prizes and user guesses
add_shortcode(
	'bhg_active_hunt',
	function ( $atts ) {
		global $wpdb;
		$hunts = $wpdb->get_results( 'SELECT * FROM `' . $status . '`' );

		ob_start();
		?>
	
	<div class="bhg-box">
		<form method="get" class="bhg-field">
			<?php
			foreach ( $_GET as $k => $v ) :
				if ( $k === 'status' ) {
					continue;
				}
				?>
				<input type="hidden" name="<?php echo esc_attr( $k ); ?>" value="<?php echo esc_attr( $v ); ?>">
			<?php endforeach; ?>
			
			<label><?php esc_html_e( 'Filter', 'bonus-hunt-guesser' ); ?></label>
			<select name="status" onchange="this.form.submit()">
				<option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Active', 'bonus-hunt-guesser' ); ?></option>
				<option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Closed', 'bonus-hunt-guesser' ); ?></option>
			</select>
		</form>

		<?php if ( $rows ) : ?>
			<ul class="bhg-list">
				<?php foreach ( $rows as $r ) : ?>
					<li><?php echo esc_html( $r->title ?: ( '#' . $r->id ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'No tournaments found.', 'bonus-hunt-guesser' ); ?></p>
		<?php endif; ?>
	</div>
	
		<?php
		return ob_get_clean();
	}
);

// [bhg_user_guesses] — list guesses by current user
add_shortcode(
	'bhg_user_guesses',
	function ( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<div class="bhg-box">' . esc_html__( 'Please log in.', 'bonus-hunt-guesser' ) . '</div>';
		}

		global $wpdb;
		$uid  = get_current_user_id();
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.*, h.title as hunt_title, h.status as hunt_status 
        FROM {$wpdb->prefix}bhg_guesses g 
        LEFT JOIN {$wpdb->prefix}bhg_bonus_hunts h ON h.id = g.hunt_id 
        WHERE g.user_id = %d 
        ORDER BY g.id DESC",
				$uid
			)
		);

		ob_start();
		?>
	
	<div class="bhg-box">
		<h3><?php esc_html_e( 'Your Guesses', 'bonus-hunt-guesser' ); ?></h3>
		
		<?php if ( $rows ) : ?>
			<table class="bhg-leaderboard">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Hunt', 'bonus-hunt-guesser' ); ?></th>
						<th><?php esc_html_e( 'Guess', 'bonus-hunt-guesser' ); ?></th>
						<th><?php esc_html_e( 'Status', 'bonus-hunt-guesser' ); ?></th>
						<th><?php esc_html_e( 'Date', 'bonus-hunt-guesser' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->hunt_title ?: ( '#' . $r->hunt_id ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $r->guess, 2 ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $r->hunt_status ?: 'open' ) ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $r->created_at ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No guesses yet.', 'bonus-hunt-guesser' ); ?></p>
		<?php endif; ?>
	</div>
	
		<?php
		return ob_get_clean();
	}
);

// [bhg_winner_notifications] — winners of closed hunts
add_shortcode(
	'bhg_winner_notifications',
	function ( $atts ) {
		global $wpdb;

		// If winners table exists, use it; otherwise show closed hunts only
		$winners_table = $wpdb->prefix . 'bhg_hunt_winners';
		$table_exists  = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
				DB_NAME,
				$winners_table
			)
		);

		if ( $table_exists ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT h.id as hunt_id, h.title, w.user_id, w.position 
            FROM {$wpdb->prefix}bhg_bonus_hunts h 
            INNER JOIN {$wpdb->prefix}bhg_hunt_winners w ON w.hunt_id = h.id 
            WHERE h.status = %s 
            ORDER BY h.id DESC",
					'closed'
				)
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id as hunt_id, title 
            FROM {$wpdb->prefix}bhg_bonus_hunts 
            WHERE status = %s 
            ORDER BY id DESC",
					'closed'
				)
			);
		}

		ob_start();
		?>
	
	<div class="bhg-box">
		<h3><?php esc_html_e( 'Winner Notifications', 'bonus-hunt-guesser' ); ?></h3>
		
		<?php if ( $rows ) : ?>
			<ul class="bhg-list">
			<?php
			foreach ( $rows as $r ) :
				$u = isset( $r->user_id ) && $r->user_id ? get_userdata( $r->user_id ) : null;
				?>
				<li>
					<strong><?php echo esc_html( $r->title ?: ( '#' . $r->hunt_id ) ); ?></strong>
					
					<?php if ( $u ) : ?>
						— <?php echo esc_html( $u->display_name ?: $u->user_login ); ?>
						<?php if ( ! empty( $r->position ) ) : ?>
							(<?php echo intval( $r->position ); ?>)
						<?php endif; ?>
					<?php else : ?>
						— <?php esc_html_e( 'Results pending', 'bonus-hunt-guesser' ); ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'No winner notifications yet.', 'bonus-hunt-guesser' ); ?></p>
		<?php endif; ?>
	</div>
	
		<?php
		return ob_get_clean();
	}
);