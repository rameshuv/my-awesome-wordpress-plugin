<?php
/**
 * Shortcodes for Bonus Hunt Guesser
 *
 * This file is self-contained and safe on PHP 7.4.
 * It registers the required shortcodes on `init` and avoids "public function outside class" parse errors.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (!class_exists('BHG_Shortcodes')) {

class BHG_Shortcodes {

	/**
	 * Register plugin shortcodes.
	 */
	public function __construct() {
		// Register shortcodes once.
		add_shortcode( 'bhg_active_hunt', array( $this, 'active_hunt_shortcode' ) );
		add_shortcode( 'bhg_guess_form', array( $this, 'guess_form_shortcode' ) );
		add_shortcode( 'bhg_leaderboard', array( $this, 'leaderboard_shortcode' ) );
		add_shortcode( 'bhg_tournaments', array( $this, 'tournaments_shortcode' ) );
				add_shortcode( 'bhg_winner_notifications', array( $this, 'winner_notifications_shortcode' ) );
				add_shortcode( 'bhg_user_profile', array( $this, 'user_profile_shortcode' ) );
				add_shortcode( 'bhg_best_guessers', array( $this, 'best_guessers_shortcode' ) );
			   add_shortcode( 'bhg_user_guesses', array( $this, 'user_guesses_shortcode' ) );
			   add_shortcode( 'bhg_hunts', array( $this, 'hunts_shortcode' ) );
			   add_shortcode( 'bhg_leaderboards', array( $this, 'leaderboards_shortcode' ) );

				// Legacy/alias tags if your site used alternatives.
				add_shortcode( 'bonus_hunt_leaderboard', array( $this, 'leaderboard_shortcode' ) );
				add_shortcode( 'bonus_hunt_login', array( $this, 'login_hint_shortcode' ) );
				add_shortcode( 'bhg_active', array( $this, 'active_hunt_shortcode' ) );
	}

	/** Minimal login hint used by some themes */
	public function login_hint_shortcode($atts = array()) {
		if ( is_user_logged_in() ) {
			return '';
		}

                $base     = wp_validate_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ), home_url( '/' ) );
                $redirect = esc_url_raw( add_query_arg( array(), $base ) );

		return '<p>' . esc_html__( 'Please log in to continue.', 'bonus-hunt-guesser' ) . '</p>'
			 . '<p><a class="button button-primary" href="' . esc_url( wp_login_url( $redirect ) ) . '">' . esc_html__( 'Log in', 'bonus-hunt-guesser' ) . '</a></p>';
	}

	/** [bhg_active_hunt] — list all open hunts */
        public function active_hunt_shortcode($atts) {
                global $wpdb;
                $hunts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY created_at DESC" );
                if (!$hunts) {
                        return '<div class="bhg-active-hunt"><p>' . esc_html__('No active bonus hunts at the moment.', 'bonus-hunt-guesser') . '</p></div>';
                }

               wp_enqueue_style(
                       'bhg-shortcodes',
                       BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
                       array(),
                       defined( 'BHG_VERSION' ) ? BHG_VERSION : null
               );

               ob_start();
               echo '<div class="bhg-active-hunts">';
               foreach ($hunts as $hunt) {
                       echo '<div class="bhg-hunt-card">';
                       echo '<h3>' . esc_html($hunt->title) . '</h3>';
                       echo '<ul class="bhg-hunt-meta">';
                       echo '<li><strong>' . esc_html__('Starting Balance', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(number_format_i18n((float)$hunt->starting_balance, 2)) . '</li>';
                       echo '<li><strong>' . esc_html__('Number of Bonuses', 'bonus-hunt-guesser') . ':</strong> ' . (int)$hunt->num_bonuses . '</li>';
                       if (!empty($hunt->prizes)) {
                               echo '<li><strong>' . esc_html__('Prizes', 'bonus-hunt-guesser') . ':</strong> ' . wp_kses_post($hunt->prizes) . '</li>';
                       }
			echo '</ul>';
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/** [bhg_guess_form hunt_id=""] */
	public function guess_form_shortcode($atts) {
		$atts = shortcode_atts(array('hunt_id' => 0), $atts, 'bhg_guess_form');
		$hunt_id = (int) $atts['hunt_id'];

		if ( ! is_user_logged_in() ) {
                        $base     = wp_validate_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ), home_url( '/' ) );
                        $redirect = esc_url_raw( add_query_arg( array(), $base ) );

			return '<p>' . esc_html__( 'Please log in to submit your guess.', 'bonus-hunt-guesser' ) . '</p>'
				 . '<p><a class="button button-primary" href="' . esc_url( wp_login_url( $redirect ) ) . '">' . esc_html__( 'Log in', 'bonus-hunt-guesser' ) . '</a></p>';
		}

		global $wpdb;
		$open_hunts = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='open' ORDER BY created_at DESC" );

		if ($hunt_id <= 0) {
			if (!$open_hunts) {
				return '<p>' . esc_html__('No open hunt found to guess.', 'bonus-hunt-guesser') . '</p>';
			}
			if (count($open_hunts) === 1) {
				$hunt_id = (int)$open_hunts[0]->id;
			}
		}

		$user_id = get_current_user_id();
		$table = $wpdb->prefix . 'bhg_guesses';
		$existing_id = $hunt_id > 0 ? (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE user_id=%d AND hunt_id=%d", $user_id, $hunt_id)) : 0;
		$existing_guess = $existing_id ? (float) $wpdb->get_var($wpdb->prepare("SELECT guess FROM {$table} WHERE id=%d", $existing_id)) : '';

               $settings = get_option('bhg_plugin_settings');
               $min = isset($settings['min_guess_amount']) ? (float)$settings['min_guess_amount'] : 0;
               $max = isset($settings['max_guess_amount']) ? (float)$settings['max_guess_amount'] : 100000;

               wp_enqueue_style(
                       'bhg-shortcodes',
                       BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
                       array(),
                       defined( 'BHG_VERSION' ) ? BHG_VERSION : null
               );

               ob_start(); ?>
               <form class="bhg-guess-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="bhg_submit_guess">
			<?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>

			<?php if ($open_hunts && count($open_hunts) > 1) : ?>
				<label for="bhg-hunt-select"><?php esc_html_e('Choose a hunt:', 'bonus-hunt-guesser'); ?></label>
				<select id="bhg-hunt-select" name="hunt_id" required>
					<option value=""><?php esc_html_e('Select a hunt', 'bonus-hunt-guesser'); ?></option>
					<?php foreach ($open_hunts as $oh) : ?>
						<option value="<?php echo (int)$oh->id; ?>" <?php if ($hunt_id === (int)$oh->id) echo 'selected'; ?>>
							<?php echo esc_html($oh->title); ?>
						</option>
					<?php endforeach; ?>
				</select>
			<?php else : ?>
				<input type="hidden" name="hunt_id" value="<?php echo esc_attr($hunt_id); ?>">
			<?php endif; ?>

			<label for="bhg-guess" class="bhg-guess-label"><?php esc_html_e('Your guess (final balance):', 'bonus-hunt-guesser'); ?></label>
			<input type="number" step="0.01" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>"
				   id="bhg-guess" name="guess" value="<?php echo esc_attr($existing_guess); ?>" required>
			<div class="bhg-error-message"></div>
			<button type="submit" class="bhg-submit-btn button button-primary">
				<?php echo esc_html__('Submit Guess', 'bonus-hunt-guesser'); ?></button>
		</form>
		<?php
		return ob_get_clean();
	}

	/** [bhg_leaderboard] */
	public function leaderboard_shortcode($atts) {
		$a = shortcode_atts(array(
			'hunt_id' => 0,
			'orderby' => 'guess',
			'order'   => 'ASC',
			'page'    => 1,
			'per_page'=> 20,
		), $atts, 'bhg_leaderboard');

		global $wpdb;
		$hunt_id = (int)$a['hunt_id'];
		if ($hunt_id <= 0) {
			$hunt_id = (int)$wpdb->get_var( "SELECT id FROM {$wpdb->prefix}bhg_bonus_hunts ORDER BY created_at DESC LIMIT 1" );
			if ($hunt_id <= 0) {
				return '<p>' . esc_html__('No hunts found.', 'bonus-hunt-guesser') . '</p>';
			}
		}

		$g = $wpdb->prefix . 'bhg_guesses';
		$u = $wpdb->users;

		$order = strtoupper($a['order']) === 'DESC' ? 'DESC' : 'ASC';
		$map = array(
			'guess'      => 'g.guess',
			'user'       => 'u.user_login',
			'position'   => 'g.id', // stable proxy
		);
		$orderby_key = array_key_exists($a['orderby'], $map) ? $a['orderby'] : 'guess';
		$orderby = $map[$orderby_key];
		$page    = max(1, (int)$a['page']);
		$per     = max(1, (int)$a['per_page']);
		$offset  = ($page - 1) * $per;

		$total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$g} WHERE hunt_id=%d", $hunt_id));
		if ($total < 1) {
			return '<p>' . esc_html__('No guesses yet.', 'bonus-hunt-guesser') . '</p>';
		}

		$rows = $wpdb->get_results($wpdb->prepare(
			"SELECT g.*, u.user_login, h.affiliate_site_id
			 FROM {$g} g
			 LEFT JOIN {$u} u ON u.ID = g.user_id
			 LEFT JOIN {$wpdb->prefix}bhg_bonus_hunts h ON h.id = g.hunt_id
			 WHERE g.hunt_id=%d
			 ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
			$hunt_id, $per, $offset
		));

		wp_enqueue_style(
			'bhg-shortcodes',
			BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
			array(),
			defined( 'BHG_VERSION' ) ? BHG_VERSION : null
		);

		ob_start();
		echo '<table class="bhg-leaderboard">';
		echo '<thead><tr>';
		echo '<th class="sortable" data-column="position">' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>';
		echo '<th class="sortable" data-column="user">' . esc_html__('User', 'bonus-hunt-guesser') . '</th>';
		echo '<th class="sortable" data-column="guess">' . esc_html__('Guess', 'bonus-hunt-guesser') . '</th>';
		echo '</tr></thead><tbody>';

		$pos = $offset + 1;
		foreach ($rows as $r) {
			$site_id = isset($r->affiliate_site_id) ? (int)$r->affiliate_site_id : 0;
			$is_aff  = $site_id > 0
				? (int)get_user_meta((int)$r->user_id, 'bhg_affiliate_website_' . $site_id, true)
				: (int)get_user_meta((int)$r->user_id, 'bhg_is_affiliate', true);
			$aff = $is_aff ? 'green' : 'red';
                       /* translators: %d: user ID. */
                       $user_label = $r->user_login ? $r->user_login : sprintf( __( 'user#%d', 'bonus-hunt-guesser' ), (int) $r->user_id );

			echo '<tr>';
			echo '<td data-column="position">' . (int)$pos++ . '</td>';
			echo '<td data-column="user">' . esc_html($user_label) . ' <span class="bhg-aff-dot bhg-aff-' . esc_attr($aff) . '" aria-hidden="true"></span></td>';
			echo '<td data-column="guess">' . esc_html(number_format_i18n((float) $r->guess, 2)) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		$pages = (int) ceil( $total / $per );
		if ( $pages > 1 ) {
                        $base = wp_validate_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ), home_url( '/' ) );
                        $base = esc_url_raw( remove_query_arg( 'page', $base ) );
			echo '<div class="bhg-pagination">';
			for ( $p = 1; $p <= $pages; $p++ ) {
$class = $p == $page ? ' class="bhg-current-page"' : '';
echo '<a' . $class . ' href="' . esc_url( add_query_arg( array( 'page' => $p ), $base ) ) . '">' . (int) $p . '</a> ';
			}
			echo '</div>';
		}

			   return ob_get_clean();
	   }

	   /**
		* Display guesses submitted by a user.
		*
		* @param array $atts Shortcode attributes.
		* @return string HTML output.
		*/
	   public function user_guesses_shortcode( $atts ) {
			   $a = shortcode_atts(
					   array(
							   'id'       => 0,
							   'aff'      => 'yes',
							   'website'  => 0,
							   'status'   => '',
							   'timeline' => '',
							   'orderby'  => 'hunt',
							   'order'    => 'DESC',
					   ),
					   $atts,
					   'bhg_user_guesses'
			   );

			   global $wpdb;

			   $user_id = (int) $a['id'];
			   if ( $user_id <= 0 ) {
					   $user_id = get_current_user_id();
			   }
			   if ( $user_id <= 0 ) {
					   return '<p>' . esc_html__( 'No user specified.', 'bonus-hunt-guesser' ) . '</p>';
			   }

			   $g = $wpdb->prefix . 'bhg_guesses';
			   $h = $wpdb->prefix . 'bhg_bonus_hunts';

			   $where  = array( 'g.user_id = %d' );
			   $params = array( $user_id );

			   if ( in_array( $a['status'], array( 'open', 'closed' ), true ) ) {
					   $where[]  = 'h.status = %s';
					   $params[] = $a['status'];
			   }

			   $website = (int) $a['website'];
			   if ( $website > 0 ) {
					   $where[]  = 'h.affiliate_site_id = %d';
					   $params[] = $website;
			   }

			   $order = strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC';
			   $orderby_map = array(
					   'guess' => 'g.guess',
					   'hunt'  => 'h.created_at',
			   );
			   $orderby_key = isset( $orderby_map[ $a['orderby'] ] ) ? $a['orderby'] : 'hunt';
			   $orderby     = $orderby_map[ $orderby_key ];

			   $limit_sql = '';
			   if ( 'recent' === strtolower( $a['timeline'] ) ) {
					   $limit_sql = ' LIMIT 10';
			   }

			   $sql = "SELECT g.guess, h.title, h.final_balance, h.affiliate_site_id
					   FROM {$g} g INNER JOIN {$h} h ON h.id = g.hunt_id
					   WHERE " . implode( ' AND ', $where ) . "
					   ORDER BY {$orderby} {$order}{$limit_sql}";

			   $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
			   if ( ! $rows ) {
					   return '<p>' . esc_html__( 'No guesses found.', 'bonus-hunt-guesser' ) . '</p>';
			   }

			   $show_aff = filter_var( $a['aff'], FILTER_VALIDATE_BOOLEAN );

			   ob_start();
			   echo '<table class="bhg-user-guesses"><thead><tr>';
			   echo '<th>' . esc_html__( 'Hunt', 'bonus-hunt-guesser' ) . '</th>';
			   echo '<th>' . esc_html__( 'Guess', 'bonus-hunt-guesser' ) . '</th>';
			   echo '<th>' . esc_html__( 'Final', 'bonus-hunt-guesser' ) . '</th>';
			   echo '</tr></thead><tbody>';

			   foreach ( $rows as $row ) {
					   echo '<tr>';
					   echo '<td>' . esc_html( $row->title ) . '</td>';
					   $guess_cell = esc_html( number_format_i18n( (float) $row->guess, 2 ) );
					   if ( $show_aff ) {
							   $guess_cell = bhg_render_affiliate_dot( $user_id, (int) $row->affiliate_site_id ) . $guess_cell;
					   }
					   echo '<td>' . $guess_cell . '</td>';
					   echo '<td>';
                                           echo isset( $row->final_balance ) ? esc_html( number_format_i18n( (float) $row->final_balance, 2 ) ) : esc_html__( '&mdash;', 'bonus-hunt-guesser' );
					   echo '</td>';
					   echo '</tr>';
			   }
			   echo '</tbody></table>';
			   return ob_get_clean();
	   }

	   /**
		* Display a list of bonus hunts.
		*
		* @param array $atts Shortcode attributes.
		* @return string HTML output.
		*/
	   public function hunts_shortcode( $atts ) {
			   $a = shortcode_atts(
					   array(
							   'id'       => 0,
							   'aff'      => 'no',
							   'website'  => 0,
							   'status'   => '',
							   'timeline' => '',
					   ),
					   $atts,
					   'bhg_hunts'
			   );

			   global $wpdb;
			   $h = $wpdb->prefix . 'bhg_bonus_hunts';
			   $aff_table = $wpdb->prefix . 'bhg_affiliates';

			   $where  = array();
			   $params = array();

			   $id = (int) $a['id'];
			   if ( $id > 0 ) {
					   $where[]  = 'h.id = %d';
					   $params[] = $id;
			   }

			   if ( in_array( $a['status'], array( 'open', 'closed' ), true ) ) {
					   $where[]  = 'h.status = %s';
					   $params[] = $a['status'];
			   }

			   $website = (int) $a['website'];
			   if ( $website > 0 ) {
					   $where[]  = 'h.affiliate_site_id = %d';
					   $params[] = $website;
			   }

			   $sql = "SELECT h.id, h.title, h.starting_balance, h.final_balance, h.status, h.created_at, h.closed_at, a.name AS aff_name
					   FROM {$h} h
					   LEFT JOIN {$aff_table} a ON a.id = h.affiliate_site_id";
			   if ( $where ) {
					   $sql .= ' WHERE ' . implode( ' AND ', $where );
			   }
			   $sql .= ' ORDER BY h.created_at DESC';

			   if ( 'recent' === strtolower( $a['timeline'] ) ) {
					   $sql .= ' LIMIT 10';
			   }

			   $rows = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );
			   if ( ! $rows ) {
					   return '<p>' . esc_html__( 'No hunts found.', 'bonus-hunt-guesser' ) . '</p>';
			   }

			   $show_aff = filter_var( $a['aff'], FILTER_VALIDATE_BOOLEAN );

			   ob_start();
			   echo '<table class="bhg-hunts"><thead><tr>';
			   echo '<th>' . esc_html__( 'Title', 'bonus-hunt-guesser' ) . '</th>';
			   echo '<th>' . esc_html__( 'Start Balance', 'bonus-hunt-guesser' ) . '</th>';
			   echo '<th>' . esc_html__( 'Final Balance', 'bonus-hunt-guesser' ) . '</th>';
			   echo '<th>' . esc_html__( 'Status', 'bonus-hunt-guesser' ) . '</th>';
			   if ( $show_aff ) {
					   echo '<th>' . esc_html__( 'Affiliate', 'bonus-hunt-guesser' ) . '</th>';
			   }
			   echo '</tr></thead><tbody>';

			   foreach ( $rows as $row ) {
					   echo '<tr>';
					   echo '<td>' . esc_html( $row->title ) . '</td>';
					   echo '<td>' . esc_html( number_format_i18n( (float) $row->starting_balance, 2 ) ) . '</td>';
					   echo '<td>';
                                           echo isset( $row->final_balance ) ? esc_html( number_format_i18n( (float) $row->final_balance, 2 ) ) : esc_html__( '&mdash;', 'bonus-hunt-guesser' );
                                           echo '</td>';
                                           echo '<td>' . esc_html( $row->status ) . '</td>';
                                           if ( $show_aff ) {
                                                           echo '<td>' . ( $row->aff_name ? esc_html( $row->aff_name ) : esc_html__( '—', 'bonus-hunt-guesser' ) ) . '</td>';
                                           }
					   echo '</tr>';
			   }
			   echo '</tbody></table>';
			   return ob_get_clean();
	   }

	   /**
		* Display leaderboards for multiple hunts.
		*
		* @param array $atts Shortcode attributes.
		* @return string HTML output.
		*/
	   public function leaderboards_shortcode( $atts ) {
			   $a = shortcode_atts(
					   array(
							   'id'       => 0,
							   'aff'      => 'yes',
							   'website'  => 0,
							   'status'   => '',
							   'timeline' => '',
					   ),
					   $atts,
					   'bhg_leaderboards'
			   );

			   global $wpdb;
			   $h = $wpdb->prefix . 'bhg_bonus_hunts';

			   $where  = array();
			   $params = array();

			   $id = (int) $a['id'];
			   if ( $id > 0 ) {
					   $where[]  = 'id = %d';
					   $params[] = $id;
			   }

			   if ( in_array( $a['status'], array( 'open', 'closed' ), true ) ) {
					   $where[]  = 'status = %s';
					   $params[] = $a['status'];
			   }

			   $website = (int) $a['website'];
			   if ( $website > 0 ) {
					   $where[]  = 'affiliate_site_id = %d';
					   $params[] = $website;
			   }

			   $sql = "SELECT id, title FROM {$h}";
			   if ( $where ) {
					   $sql .= ' WHERE ' . implode( ' AND ', $where );
			   }
			   $sql .= ' ORDER BY created_at DESC';

			   if ( 'recent' === strtolower( $a['timeline'] ) ) {
					   $sql .= ' LIMIT 5';
			   }

			   $hunts = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );
			   if ( ! $hunts ) {
					   return '<p>' . esc_html__( 'No hunts found.', 'bonus-hunt-guesser' ) . '</p>';
			   }

			   $show_aff = filter_var( $a['aff'], FILTER_VALIDATE_BOOLEAN );

			   ob_start();
			   foreach ( $hunts as $hunt ) {
					   echo '<div class="bhg-leaderboard-wrap">';
					   echo '<h3>' . esc_html( $hunt->title ) . '</h3>';
					   $html = $this->leaderboard_shortcode( array( 'hunt_id' => (int) $hunt->id ) );
					   if ( ! $show_aff ) {
							   $html = preg_replace( '/<span class="bhg-aff-dot[^>]*><\/span>/', '', $html );
					   }
					   echo $html;
					   echo '</div>';
			   }
			   return ob_get_clean();
	   }

	   /**
		* [bhg_tournaments] List tournaments or show details.
		*
		* Attributes:
		* - status    (string) Filter by tournament status (active|closed).
		* - tournament(int)    Specific tournament ID.
		* - website   (int)    Affiliate website ID.
		* - timeline  (string) Tournament type (weekly|monthly|yearly|quarterly|alltime).
		*
		* Details view is available via ?bhg_tournament_id=ID.
		*
		* @param array $atts Shortcode attributes.
		* @return string HTML output.
		*/
       public function tournaments_shortcode($atts) {
                          global $wpdb;

               wp_enqueue_style(
                       'bhg-shortcodes',
                       BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
                       array(),
                       defined( 'BHG_VERSION' ) ? BHG_VERSION : null
               );

		// If a specific tournament ID is requested, render details
		$details_id = isset($_GET['bhg_tournament_id']) ? absint($_GET['bhg_tournament_id']) : 0;
		if ($details_id > 0) {
			$t = $wpdb->prefix . 'bhg_tournaments';
			$r = $wpdb->prefix . 'bhg_tournament_results';
			$u = $wpdb->users;

			$tournament = $wpdb->get_row($wpdb->prepare(
				"SELECT id, type, start_date, end_date, status FROM {$t} WHERE id=%d",
				$details_id
			));
			if (!$tournament) {
				return '<p>' . esc_html__('Tournament not found.', 'bonus-hunt-guesser') . '</p>';
			}

			// Sortable results (whitelisted)
			$orderby = isset($_GET['orderby']) ? strtolower(sanitize_key($_GET['orderby'])) : 'wins';
			$order   = isset($_GET['order'])   ? strtolower(sanitize_key($_GET['order']))   : 'desc';

			$allowed = array(
				'wins'        => 'r.wins',
				'username'    => 'u.user_login',
				'last_win_at' => 'r.last_win_date',
			);
			if (!isset($allowed[$orderby])) { $orderby = 'wins'; }
			if ($order !== 'asc' && $order !== 'desc') { $order = 'desc'; }
			$order_by_sql = $allowed[$orderby] . ' ' . strtoupper($order);

			$rows = $wpdb->get_results($wpdb->prepare(
				"SELECT r.user_id, r.wins, r.last_win_date, u.user_login
				 FROM {$r} r
				 INNER JOIN {$u} u ON u.ID = r.user_id
				 WHERE r.tournament_id=%d
				 ORDER BY {$order_by_sql}, r.user_id ASC",
				$tournament->id
			));

			$base = remove_query_arg(array('orderby','order'));
			$toggle = function($key) use ($orderby, $order, $base) {
				$next = ($orderby === $key && strtolower($order) === 'asc') ? 'desc' : 'asc';
				return esc_url(add_query_arg(array('orderby'=>$key,'order'=>$next), $base));
			};

ob_start();
echo '<div class="bhg-tournament-details">';
			echo '<p><a href="' . esc_url(remove_query_arg('bhg_tournament_id')) . '">&larr; ' . esc_html__('Back to tournaments', 'bonus-hunt-guesser') . '</a></p>';
echo '<h3>' . esc_html(ucfirst($tournament->type)) . '</h3>';
			echo '<p><strong>' . esc_html__('Start', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(mysql2date(get_option('date_format'), $tournament->start_date)) . ' &nbsp; ';
			echo '<strong>' . esc_html__('End', 'bonus-hunt-guesser') . ':</strong> ' . esc_html(mysql2date(get_option('date_format'), $tournament->end_date)) . ' &nbsp; ';
			echo '<strong>' . esc_html__('Status', 'bonus-hunt-guesser') . ':</strong> ' . esc_html($tournament->status) . '</p>';

			if (!$rows) {
				echo '<p>' . esc_html__('No results yet.', 'bonus-hunt-guesser') . '</p>';
				echo '</div>';
				return ob_get_clean();
			}

echo '<table class="bhg-leaderboard">';
echo '<thead><tr>';
echo '<th>#</th>';
echo '<th><a href="' . $toggle('username') . '">' . esc_html__('Username', 'bonus-hunt-guesser') . '</a></th>';
echo '<th><a href="' . $toggle('wins') . '">' . esc_html__('Wins', 'bonus-hunt-guesser') . '</a></th>';
echo '<th><a href="' . $toggle('last_win_at') . '">' . esc_html__('Last win', 'bonus-hunt-guesser') . '</a></th>';
echo '</tr></thead><tbody>';

			$pos = 1;
foreach ($rows as $row) {
echo '<tr>';
echo '<td>' . (int)$pos++ . '</td>';
echo '<td>' . esc_html( $row->user_login ?: sprintf(
                                       /* translators: %d: user ID. */
                                       __( 'user#%d', 'bonus-hunt-guesser' ),
                                       (int) $row->user_id
                               ) ) . '</td>';
echo '<td>' . (int)$row->wins . '</td>';
echo '<td>' . ( $row->last_win_date ? esc_html( mysql2date( get_option( 'date_format' ), $row->last_win_date ) ) : esc_html__( '—', 'bonus-hunt-guesser' ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			echo '</div>';

			return ob_get_clean();
		}

			   // Otherwise list tournaments with filters
			   $a = shortcode_atts(
					   array(
							   'status'    => 'active',
							   'tournament'=> 0,
							   'website'   => 0,
							   'timeline'  => '',
					   ),
					   $atts,
					   'bhg_tournaments'
			   );

			   $t          = $wpdb->prefix . 'bhg_tournaments';
			   $where      = array();
			   $args       = array();
			   $status     = isset( $_GET['bhg_status'] ) ? sanitize_key( $_GET['bhg_status'] ) : sanitize_key( $a['status'] );
			   $timeline   = isset( $_GET['bhg_timeline'] ) ? sanitize_key( $_GET['bhg_timeline'] ) : sanitize_key( $a['timeline'] );
			   $tournament = absint( $a['tournament'] );
			   $website    = absint( $a['website'] );

			   if ( $tournament > 0 ) {
					   $where[] = 'id = %d';
					   $args[]  = $tournament;
			   }
			   if ( in_array( $status, array( 'active', 'closed' ), true ) ) {
					   $where[] = 'status = %s';
					   $args[]  = $status;
			   }
			   if ( in_array( $timeline, array( 'weekly', 'monthly', 'yearly', 'quarterly', 'alltime' ), true ) ) {
					   $where[] = 'type = %s';
					   $args[]  = $timeline;
			   }
			   if ( $website > 0 ) {
					   $where[] = 'affiliate_site_id = %d';
					   $args[]  = $website;
			   }

			   $sql = "SELECT * FROM {$t}";
			   if ( $where ) {
					   $sql .= ' WHERE ' . implode( ' AND ', $where );
			   }
			   $sql .= ' ORDER BY start_date DESC, id DESC';

			   $rows = $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql );
			   if (!$rows) {
					   return '<p>' . esc_html__('No tournaments found.', 'bonus-hunt-guesser') . '</p>';
			   }
                               $current_url = isset( $_SERVER['REQUEST_URI'] )
                                       ? esc_url_raw( wp_validate_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ), home_url( '/' ) ) )
                                       : home_url( '/' );

				ob_start();
echo '<form method="get" class="bhg-tournament-filters">';
				foreach ( $_GET as $raw_key => $v ) {
						$key = sanitize_key( $raw_key );
						if ( $key === 'bhg_timeline' || $key === 'bhg_status' || $key === 'bhg_tournament_id' ) {
								continue;
						}
						echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( is_array( $v ) ? reset( $v ) : $v ) . '">';
				}
echo '<label class="bhg-tournament-label">' . esc_html__( 'Timeline:', 'bonus-hunt-guesser' ) . ' ';
				echo '<select name="bhg_timeline">';
				$timelines = array( 'all' => __( 'All', 'bonus-hunt-guesser' ), 'weekly' => __( 'Weekly', 'bonus-hunt-guesser' ), 'monthly' => __( 'Monthly', 'bonus-hunt-guesser' ), 'yearly' => __( 'Yearly', 'bonus-hunt-guesser' ), 'quarterly' => __( 'Quarterly', 'bonus-hunt-guesser' ), 'alltime' => __( 'All-Time', 'bonus-hunt-guesser' ) );
				$timeline_key = isset( $_GET['bhg_timeline'] ) ? sanitize_key( $_GET['bhg_timeline'] ) : $timeline;
				foreach ( $timelines as $key => $label ) {
						echo '<option value="' . esc_attr( $key ) . '"' . selected( $timeline_key, $key, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select></label>';

				echo '<label>' . esc_html__( 'Status:', 'bonus-hunt-guesser' ) . ' ';
				echo '<select name="bhg_status">';
				$statuses = array( 'active' => __( 'Active', 'bonus-hunt-guesser' ), 'closed' => __( 'Closed', 'bonus-hunt-guesser' ), 'all' => __( 'All', 'bonus-hunt-guesser' ) );
				$status_key = isset( $_GET['bhg_status'] ) ? sanitize_key( $_GET['bhg_status'] ) : $status;
				foreach ( $statuses as $key => $label ) {
						echo '<option value="' . esc_attr( $key ) . '"' . selected( $status_key, $key, false ) . '>' . esc_html( $label ) . '</option>';
				}
				echo '</select></label> ';

echo '<button class="button bhg-filter-button" type="submit">'.esc_html__('Filter','bonus-hunt-guesser').'</button>';
echo '</form>';

echo '<table class="bhg-tournaments">';
		echo '<thead><tr>';
echo '<th>' . esc_html__('Type', 'bonus-hunt-guesser') . '</th>';
echo '<th>' . esc_html__('Start', 'bonus-hunt-guesser') . '</th>';
echo '<th>' . esc_html__('End', 'bonus-hunt-guesser') . '</th>';
echo '<th>' . esc_html__('Status', 'bonus-hunt-guesser') . '</th>';
echo '<th>' . esc_html__('Details', 'bonus-hunt-guesser') . '</th>';
		echo '</tr></thead><tbody>';

		foreach ($rows as $row) {
			$detail_url = esc_url(add_query_arg('bhg_tournament_id', (int)$row->id, remove_query_arg(array('orderby','order'), $current_url)));
			echo '<tr>';
echo '<td>' . esc_html(ucfirst($row->type)) . '</td>';
echo '<td>' . esc_html(mysql2date(get_option('date_format'), $row->start_date)) . '</td>';
echo '<td>' . esc_html(mysql2date(get_option('date_format'), $row->end_date)) . '</td>';
echo '<td>' . esc_html($row->status) . '</td>';
echo '<td><a href="' . $detail_url . '">' . esc_html__('Show details','bonus-hunt-guesser') . '</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		return ob_get_clean();
	}

	/** Minimal winners widget: latest closed hunts */
	public function winner_notifications_shortcode($atts) {
		global $wpdb;

		$a = shortcode_atts(
			array('limit' => 5),
			$atts,
			'bhg_winner_notifications'
		);

		$hunts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, final_balance, winners_count, closed_at FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status='closed' ORDER BY closed_at DESC LIMIT %d",
				(int) $a['limit']
			)
		);

               if ( ! $hunts ) {
                       return '<p>' . esc_html__( 'No closed hunts yet.', 'bonus-hunt-guesser' ) . '</p>';
               }

               wp_enqueue_style(
                       'bhg-shortcodes',
                       BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
                       array(),
                       defined( 'BHG_VERSION' ) ? BHG_VERSION : null
               );

               ob_start();
               echo '<div class="bhg-winner-notifications">';
               foreach ( $hunts as $hunt ) {
                        $winners = function_exists( 'bhg_get_top_winners_for_hunt' )
                                ? bhg_get_top_winners_for_hunt( $hunt->id, (int) $hunt->winners_count )
                                : array();

                       echo '<div class="bhg-winner">';
			echo '<p><strong>' . esc_html( $hunt->title ) . '</strong></p>';
			if ( $hunt->final_balance !== null ) {
				echo '<p><em>' . esc_html__( 'Final', 'bonus-hunt-guesser' ) . ':</em> ' . esc_html( number_format_i18n( (float) $hunt->final_balance, 2 ) ) . '</p>';
			}

			if ( $winners ) {
				echo '<ul class="bhg-winner-list">';
				foreach ( $winners as $w ) {
					$u  = get_userdata( (int) $w->user_id );
					$nm = $u ? $u->user_login : sprintf( __( 'User #%d', 'bonus-hunt-guesser' ), (int) $w->user_id );
                                        echo '<li>' . esc_html( $nm ) . ' ' . esc_html__( '—', 'bonus-hunt-guesser' ) . ' ' . esc_html( number_format_i18n( (float) $w->guess, 2 ) ) . ' (' . esc_html( number_format_i18n( (float) $w->diff, 2 ) ) . ')</li>';
				}
				echo '</ul>';
			}

			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	/** Minimal profile view: affiliate status badge */
	public function user_profile_shortcode($atts) {
		if (!is_user_logged_in()) return '<p>' . esc_html__('Please log in to view this content.', 'bonus-hunt-guesser') . '</p>';
		wp_enqueue_style(
			'bhg-shortcodes',
			BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
			array(),
			defined( 'BHG_VERSION' ) ? BHG_VERSION : null
		);
		$user_id = get_current_user_id();
		$is_affiliate = (int)get_user_meta($user_id, 'bhg_is_affiliate', true);
		$badge = $is_affiliate ? '<span class="bhg-aff-green" aria-hidden="true"></span>' : '<span class="bhg-aff-red" aria-hidden="true"></span>';
		return '<div class="bhg-user-profile">' . $badge . ' ' . esc_html(wp_get_current_user()->display_name) . '</div>';
	}

	/** [bhg_best_guessers] — simple wins leaderboard */
	public function best_guessers_shortcode($atts) {
		global $wpdb;

		$wins_tbl = $wpdb->prefix . 'bhg_tournament_results';
		$tours_tbl = $wpdb->prefix . 'bhg_tournaments';
		$users_tbl = $wpdb->users;

		$now_ts = current_time('timestamp');
		$current_month = wp_date('Y-m', $now_ts);
		$current_year  = wp_date('Y', $now_ts);

		$periods = array(
			'overall' => array(
				'label' => esc_html__('Overall', 'bonus-hunt-guesser'),
				'type'  => '',
				'start' => '',
				'end'   => '',
			),
			'monthly' => array(
				'label' => esc_html__('Monthly', 'bonus-hunt-guesser'),
				'type'  => 'monthly',
				'start' => $current_month . '-01',
				'end'   => wp_date('Y-m-t', strtotime($current_month . '-01', $now_ts)),
			),
			'yearly' => array(
				'label' => esc_html__('Yearly', 'bonus-hunt-guesser'),
				'type'  => 'yearly',
				'start' => $current_year . '-01-01',
				'end'   => $current_year . '-12-31',
			),
			'alltime' => array(
				'label' => esc_html__('All-Time', 'bonus-hunt-guesser'),
				'type'  => 'alltime',
				'start' => '',
				'end'   => '',
			),
		);

		$results = array();
		foreach ($periods as $key => $info) {
			if ($info['type']) {
				$where = 't.type = %s';
				$params = array($info['type']);
				if (!empty($info['start']) && !empty($info['end'])) {
					$where .= ' AND t.start_date >= %s AND t.end_date <= %s';
					$params[] = $info['start'];
					$params[] = $info['end'];
				}
				$sql = "SELECT u.ID as user_id, u.user_login, SUM(r.wins) as total_wins
						FROM {$wins_tbl} r
						INNER JOIN {$users_tbl} u ON u.ID = r.user_id
						INNER JOIN {$tours_tbl} t ON t.id = r.tournament_id
						WHERE {$where}
						GROUP BY u.ID, u.user_login
						ORDER BY total_wins DESC, u.user_login ASC
						LIMIT 50";
				array_unshift($params, $sql);
				$prepared = call_user_func_array(array($wpdb, 'prepare'), $params);
				$results[$key] = $wpdb->get_results($prepared);
			} else {
				$sql = "SELECT u.ID as user_id, u.user_login, SUM(r.wins) as total_wins
						FROM {$wins_tbl} r
						INNER JOIN {$users_tbl} u ON u.ID = r.user_id
						GROUP BY u.ID, u.user_login
						ORDER BY total_wins DESC, u.user_login ASC
						LIMIT 50";
				$results[$key] = $wpdb->get_results( $sql );
			}
		}

		$hunts_tbl = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunts = $wpdb->get_results( "SELECT id, title FROM {$hunts_tbl} WHERE status='closed' ORDER BY created_at DESC LIMIT 50" );

		wp_enqueue_style(
			'bhg-shortcodes',
			BHG_PLUGIN_URL . 'assets/css/bhg-shortcodes.css',
			array(),
			defined( 'BHG_VERSION' ) ? BHG_VERSION : null
		);
		wp_enqueue_script(
			'bhg-shortcodes-js',
			BHG_PLUGIN_URL . 'assets/js/bhg-shortcodes.js',
			array(),
			defined( 'BHG_VERSION' ) ? BHG_VERSION : null,
			true
		);

		ob_start();
		echo '<ul class="bhg-tabs">';
		$first = true;
		foreach ($periods as $key => $info) {
			$active = $first ? ' class="active"' : '';
			echo '<li' . $active . '><a href="#bhg-tab-' . esc_attr($key) . '">' . esc_html($info['label']) . '</a></li>';
			$first = false;
		}
		if ($hunts) {
			echo '<li><a href="#bhg-tab-hunts">' . esc_html__('Bonus Hunts', 'bonus-hunt-guesser') . '</a></li>';
		}
		echo '</ul>';

		$first = true;
		foreach ($periods as $key => $info) {
			$active = $first ? ' active' : '';
			echo '<div id="bhg-tab-' . esc_attr($key) . '" class="bhg-tab-pane' . $active . '">';
			$rows = isset($results[$key]) ? $results[$key] : array();
			if (!$rows) {
				echo '<p>' . esc_html__('No data yet.', 'bonus-hunt-guesser') . '</p>';
			} else {
				echo '<table class="bhg-leaderboard"><thead><tr><th>#</th><th>' . esc_html__('User', 'bonus-hunt-guesser') . '</th><th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th></tr></thead><tbody>';
				$pos = 1;
				foreach ($rows as $r) {
                                       /* translators: %d: user ID. */
                                       $user_label = $r->user_login ? $r->user_login : sprintf( __( 'user#%d', 'bonus-hunt-guesser' ), (int) $r->user_id );
					echo '<tr><td>' . (int) $pos++ . '</td><td>' . esc_html($user_label) . '</td><td>' . (int) $r->total_wins . '</td></tr>';
				}
				echo '</tbody></table>';
			}
			echo '</div>';
			$first = false;
		}

		if ( $hunts ) {
                       $base = wp_validate_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ), home_url( '/' ) );
                       $base = esc_url_raw( remove_query_arg( 'hunt_id', $base ) );
			echo '<div id="bhg-tab-hunts" class="bhg-tab-pane">';
			echo '<ul class="bhg-hunt-history">';
			foreach ( $hunts as $hunt ) {
				$url = esc_url( add_query_arg( 'hunt_id', (int) $hunt->id, $base ) );
				echo '<li><a href="' . $url . '">' . esc_html( $hunt->title ) . '</a></li>';
			}
			echo '</ul>';
			echo '</div>';
		}

		return ob_get_clean();
	}
}

} // end if class not exists

// Register once on init even if no other bootstrap instantiates the class
if (!function_exists('bhg_register_shortcodes_once')) {
	function bhg_register_shortcodes_once() {
		static $done = false;
		if ($done) return;
		$done = true;
		if (class_exists('BHG_Shortcodes')) {
			// Instantiate to attach the hooks
			new BHG_Shortcodes();
		}
	}
	add_action('init', 'bhg_register_shortcodes_once', 20);
}
