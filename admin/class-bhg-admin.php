<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Admin {

	/**
	 * Initialize admin hooks and actions.
	 */
	public function __construct() {
		// Menus.
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );

		// Handlers.
		add_action( 'admin_post_bhg_delete_guess', [ $this, 'handle_delete_guess' ] );
		add_action( 'admin_post_bhg_save_hunt', [ $this, 'handle_save_hunt' ] );
		add_action( 'admin_post_bhg_close_hunt', [ $this, 'handle_close_hunt' ] );
		add_action( 'admin_post_bhg_save_ad', [ $this, 'handle_save_ad' ] );
		add_action( 'admin_post_bhg_tournament_save', [ $this, 'handle_save_tournament' ] );
		add_action( 'admin_post_bhg_save_affiliate', [ $this, 'handle_save_affiliate' ] );
		add_action( 'admin_post_bhg_delete_affiliate', [ $this, 'handle_delete_affiliate' ] );
		add_action( 'admin_post_bhg_save_settings', [ $this, 'handle_save_settings' ] );
		add_action( 'admin_post_bhg_save_user_meta', [ $this, 'handle_save_user_meta' ] );
	}

	/** Register admin menus and pages */
	public function menu() {
		$cap  = 'manage_options';
		$slug = 'bhg';

		add_menu_page(
			__('Bonus Hunt', 'bonus-hunt-guesser'),
			__('Bonus Hunt', 'bonus-hunt-guesser'),
			$cap,
			$slug,
			[$this, 'dashboard'],
			'dashicons-awards',
			55
		);

		add_submenu_page($slug, __('Dashboard', 'bonus-hunt-guesser'),   __('Dashboard', 'bonus-hunt-guesser'),   $cap, $slug,                         [$this, 'dashboard']);
		add_submenu_page($slug, __('Bonus Hunts', 'bonus-hunt-guesser'), __('Bonus Hunts', 'bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts',             [$this, 'bonus_hunts']);
		add_submenu_page($slug, __('Results', 'bonus-hunt-guesser'),     __('Results', 'bonus-hunt-guesser'),     $cap, 'bhg-bonus-hunts-results',     [$this, 'bonus_hunts_results']);
		add_submenu_page($slug, __('Tournaments', 'bonus-hunt-guesser'), __('Tournaments', 'bonus-hunt-guesser'), $cap, 'bhg-tournaments',             [$this, 'tournaments']);
		add_submenu_page($slug, __('Users', 'bonus-hunt-guesser'),       __('Users', 'bonus-hunt-guesser'),       $cap, 'bhg-users',                   [$this, 'users']);
		add_submenu_page($slug, __('Affiliates', 'bonus-hunt-guesser'),  __('Affiliates', 'bonus-hunt-guesser'),  $cap, 'bhg-affiliates',              [$this, 'affiliates']);
		add_submenu_page($slug, __('Advertising', 'bonus-hunt-guesser'), __('Advertising', 'bonus-hunt-guesser'), $cap, 'bhg-ads',                     [$this, 'advertising']);
		add_submenu_page($slug, __('Translations', 'bonus-hunt-guesser'),__('Translations', 'bonus-hunt-guesser'),$cap, 'bhg-translations',            [$this, 'translations']);
		add_submenu_page($slug, __('Database', 'bonus-hunt-guesser'),    __('Database', 'bonus-hunt-guesser'),    $cap, 'bhg-database',                [$this, 'database']);
		add_submenu_page($slug, __('Settings', 'bonus-hunt-guesser'),    __('Settings', 'bonus-hunt-guesser'),    $cap, 'bhg-settings',                [$this, 'settings']);
		add_submenu_page(
			$slug,
			__('BHG Tools', 'bonus-hunt-guesser'),
			__('BHG Tools', 'bonus-hunt-guesser'),
			$cap,
			'bhg-tools',
			[$this, 'bhg_tools_page']
		);

		// NOTE: By default, WordPress adds a submenu item that duplicates the
		// top-level “Bonus Hunt” menu. The previous `remove_submenu_page()`
		// call removed this submenu, but it also inadvertently removed our
		// custom “Dashboard” submenu. Removing the call ensures the Dashboard
		// item remains visible under the "Bonus Hunt" menu.
	}

	/** Enqueue admin assets on BHG screens. */
	public function assets( $hook ) {
		if ( strpos( $hook, 'bhg' ) !== false ) {
			wp_enqueue_style(
				'bhg-admin',
				BHG_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				defined( 'BHG_VERSION' ) ? BHG_VERSION : null
			);
			$script_path = BHG_PLUGIN_DIR . 'assets/js/admin.js';
			if ( file_exists( $script_path ) && filesize( $script_path ) > 0 ) {
				wp_enqueue_script(
					'bhg-admin',
					BHG_PLUGIN_URL . 'assets/js/admin.js',
					array( 'jquery' ),
					defined( 'BHG_VERSION' ) ? BHG_VERSION : null,
					true
				);
			}
		}
	}

	// -------------------- Views --------------------
	/**
	 * Render the dashboard page.
	 */
	public function dashboard() {
		require BHG_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render the bonus hunts page.
	 */
	public function bonus_hunts() {
		require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts.php';
	}

	/**
	 * Render the bonus hunts results page.
	 */
	public function bonus_hunts_results() {
		require BHG_PLUGIN_DIR . 'admin/views/bonus-hunts-results.php';
	}

	/**
	 * Render the tournaments page.
	 */
	public function tournaments() {
		require BHG_PLUGIN_DIR . 'admin/views/tournaments.php';
	}

	/**
	 * Render the users page.
	 */
	public function users() {
		require BHG_PLUGIN_DIR . 'admin/views/users.php';
	}

	/**
	 * Render the affiliates management page.
	 */
	public function affiliates() {
		$view = BHG_PLUGIN_DIR . 'admin/views/affiliate-websites.php';
		if (file_exists($view)) { require $view; }
		else { echo '<div class="wrap"><h1>' . esc_html__('Affiliates', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('Affiliate management UI not provided yet.', 'bonus-hunt-guesser') . '</p></div>'; }
	}
	/**
	 * Render the advertising page.
	 */
	public function advertising() {
		require BHG_PLUGIN_DIR . 'admin/views/advertising.php';
	}

	/**
	 * Render the translations page.
	 */
	public function translations() {
		$view = BHG_PLUGIN_DIR . 'admin/views/translations.php';
		if (file_exists($view)) { require $view; }
		else { echo '<div class="wrap"><h1>' . esc_html__('Translations', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No translations UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
	}
	/**
	 * Render the database maintenance page.
	 */
	public function database() {
		$view = BHG_PLUGIN_DIR . 'admin/views/database.php';
		if (file_exists($view)) { require $view; }
		else { echo '<div class="wrap"><h1>' . esc_html__('Database', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No database UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
	}
	/**
	 * Render the settings page.
	 */
	public function settings() {
		$view = BHG_PLUGIN_DIR . 'admin/views/settings.php';
		if (file_exists($view)) { require $view; }
		else { echo '<div class="wrap"><h1>' . esc_html__('Settings', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No settings UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
	}
	/**
	 * Render the tools demo page.
	 */
	public function bhg_tools_page() {
		$view = BHG_PLUGIN_DIR . 'admin/views/demo-tools.php';
		if (file_exists($view)) { require $view; }
		else { echo '<div class="wrap"><h1>' . esc_html__('BHG Tools', 'bonus-hunt-guesser') . '</h1><p>' . esc_html__('No tools UI found.', 'bonus-hunt-guesser') . '</p></div>'; }
	}

	// -------------------- Handlers --------------------

	/**
	 * Handle deletion of a guess from the admin screen.
	 */
	public function handle_delete_guess() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_delete_guess' );
		global $wpdb;
		$guesses_table = $wpdb->prefix . 'bhg_guesses';
		$guess_id      = isset( $_POST['guess_id'] ) ? absint( wp_unslash( $_POST['guess_id'] ) ) : 0;
		if ( $guess_id ) {
			$wpdb->delete( $guesses_table, [ 'id' => $guess_id ], [ '%d' ] );
		}
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=bhg-bonus-hunts' ) );
		exit;
	}

	/**
	 * Handle creation and updating of a bonus hunt.
	 */
	public function handle_save_hunt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_save_hunt' );
		global $wpdb;
		$hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';

		$id             = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$title          = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$starting       = isset( $_POST['starting_balance'] ) ? floatval( wp_unslash( $_POST['starting_balance'] ) ) : 0;
		$num_bonuses    = isset( $_POST['num_bonuses'] ) ? absint( wp_unslash( $_POST['num_bonuses'] ) ) : 0;
		$prizes         = isset( $_POST['prizes'] ) ? wp_kses_post( wp_unslash( $_POST['prizes'] ) ) : '';
		$winners_count  = isset( $_POST['winners_count'] ) ? max( 1, absint( wp_unslash( $_POST['winners_count'] ) ) ) : 3;
		$affiliate_site = isset( $_POST['affiliate_site_id'] ) ? absint( wp_unslash( $_POST['affiliate_site_id'] ) ) : 0;
		$final_balance  = ( isset( $_POST['final_balance'] ) && $_POST['final_balance'] !== '' ) ? floatval( wp_unslash( $_POST['final_balance'] ) ) : null;
		$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'open';

		$data = [
			'title'            => $title,
			'starting_balance' => $starting,
			'num_bonuses'      => $num_bonuses,
			'prizes'           => $prizes,
			'winners_count'    => $winners_count,
			'affiliate_site_id'=> $affiliate_site,
			'final_balance'    => $final_balance,
			'status'           => $status,
			'updated_at'       => current_time('mysql'),
		];

		$format = ['%s','%f','%d','%s','%d','%d','%f','%s','%s'];
		if ($id) {
			$wpdb->update($hunts_table, $data, ['id' => $id], $format, ['%d']);
		} else {
			$data['created_at'] = current_time('mysql');
			$format[] = '%s';
			$wpdb->insert($hunts_table, $data, $format);
			$id = (int) $wpdb->insert_id;
		}

		if ($status === 'closed' && $final_balance !== null) {
			$winners = BHG_Models::close_hunt($id, $final_balance);

			$emails_enabled = (int) get_option('bhg_email_enabled', 1);
			if ($emails_enabled) {
				$guesses_table = $wpdb->prefix . 'bhg_guesses';
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM {$guesses_table} WHERE hunt_id=%d",
						$id
					)
				);

				$template = get_option(
					'bhg_email_template',
					'Hi {{username}},\nThe Bonus Hunt "{{hunt}}" is closed. Final balance: €{{final}}. Winners: {{winners}}. Thanks for playing!'
				);

				$hunt_title = (string) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT title FROM {$hunts_table} WHERE id=%d",
						$id
					)
				);

				$winner_names = array();
				foreach ((array) $winners as $winner_id) {
					$wu = get_userdata((int) $winner_id);
					if ($wu) {
						$winner_names[] = $wu->user_login;
					}
				}
                                $winner_first = $winner_names ? $winner_names[0] : esc_html__( '—', 'bonus-hunt-guesser' );
                                $winner_list  = $winner_names ? implode( ', ', $winner_names ) : esc_html__( '—', 'bonus-hunt-guesser' );

				foreach ($rows as $r) {
					$u = get_userdata((int) $r->user_id);
					if (! $u) {
						continue;
					}
					$body = strtr(
						$template,
						array(
							'{{username}}' => $u->user_login,
							'{{hunt}}'     => $hunt_title,
							'{{final}}'    => number_format($final_balance, 2),
							'{{winner}}'   => $winner_first,
							'{{winners}}'  => $winner_list,
						)
					);
					wp_mail(
						$u->user_email,
						sprintf(__('Results for %s', 'bonus-hunt-guesser'), $hunt_title ?: 'Bonus Hunt'),
						$body
					);
				}
			}
		}

		wp_safe_redirect(admin_url('admin.php?page=bhg-bonus-hunts'));
		exit;
	}

	/**
	 * Close an active bonus hunt.
	 */
	public function handle_close_hunt() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_close_hunt' );

		$hunt_id            = isset( $_POST['hunt_id'] ) ? absint( wp_unslash( $_POST['hunt_id'] ) ) : 0;
		$final_balance_raw  = isset( $_POST['final_balance'] ) ? sanitize_text_field( wp_unslash( $_POST['final_balance'] ) ) : '';

		if ( '' === $final_balance_raw || ! is_numeric( $final_balance_raw ) || (float) $final_balance_raw < 0 ) {
			wp_safe_redirect( add_query_arg( 'bhg_msg', 'invalid_final_balance', admin_url( 'admin.php?page=bhg-bonus-hunts' ) ) );
			exit;
		}

		$final_balance = (float) $final_balance_raw;

		if ( $hunt_id ) {
			BHG_Models::close_hunt( $hunt_id, $final_balance );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bhg-bonus-hunts' ) );
		exit;
	}

	/**
	 * Save or update an advertising entry.
	 */
	public function handle_save_ad() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_save_ad' );
		global $wpdb;
		$table = $wpdb->prefix . 'bhg_ads';

		$id       = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$content  = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$link     = isset( $_POST['link_url'] ) ? esc_url_raw( wp_unslash( $_POST['link_url'] ) ) : '';
		$place    = isset( $_POST['placement'] ) ? sanitize_text_field( wp_unslash( $_POST['placement'] ) ) : 'none';
		$visible  = isset( $_POST['visible_to'] ) ? sanitize_text_field( wp_unslash( $_POST['visible_to'] ) ) : 'all';
		$targets  = isset( $_POST['target_pages'] ) ? sanitize_text_field( wp_unslash( $_POST['target_pages'] ) ) : '';
		$active   = isset($_POST['active']) ? 1 : 0;

		$data = [
			'title'        => $title,
			'content'      => $content,
			'link_url'     => $link,
			'placement'    => $place,
			'visible_to'   => $visible,
			'target_pages' => $targets,
			'active'       => $active,
			'updated_at'   => current_time('mysql'),
		];

		$format = ['%s','%s','%s','%s','%s','%s','%d','%s'];
		if ($id) {
			$wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
		} else {
			$data['created_at'] = current_time('mysql');
			$format[] = '%s';
			$wpdb->insert($table, $data, $format);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=bhg-ads' ) );
		exit;
	}

	/**
	 * Save a tournament record.
	 */
	public function handle_save_tournament() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( add_query_arg( 'bhg_msg', 'noaccess', admin_url( 'admin.php?page=bhg-tournaments' ) ) );
			exit;
		}
		if ( ! check_admin_referer( 'bhg_tournament_save_action' ) ) {
			wp_safe_redirect( add_query_arg( 'bhg_msg', 'nonce', admin_url( 'admin.php?page=bhg-tournaments' ) ) );
			exit;
		}
		global $wpdb;
		$t  = $wpdb->prefix . 'bhg_tournaments';
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$data = [
			'title'       => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '',
			'description' => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'type'        => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'weekly',
			'start_date'  => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : null,
			'end_date'    => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : null,
			'status'      => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active',
			'updated_at'  => current_time( 'mysql' ),
		];
		try {
			$format = [ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ];
			if ( $id > 0 ) {
				$wpdb->update( $t, $data, [ 'id' => $id ], $format, [ '%d' ] );
			} else {
				$data['created_at'] = current_time( 'mysql' );
				$format[]           = '%s';
				$wpdb->insert( $t, $data, $format );
			}
			wp_safe_redirect( add_query_arg( 'bhg_msg', 't_saved', admin_url( 'admin.php?page=bhg-tournaments' ) ) );
			exit;
		} catch ( Throwable $e ) {
			if ( function_exists( 'error_log' ) ) {
				error_log( '[BHG] tournament save error: ' . $e->getMessage() );
			}
			wp_safe_redirect( add_query_arg( 'bhg_msg', 't_error', admin_url( 'admin.php?page=bhg-tournaments' ) ) );
			exit;
		}
	}

	/**
	 * Save or update an affiliate record.
	 */
	public function handle_save_affiliate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_save_affiliate' );
		global $wpdb;
		$table = $wpdb->prefix . 'bhg_affiliates';
		$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$url   = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		$status= isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';

		$data = ['name'=>$name, 'url'=>$url, 'status'=>$status, 'updated_at'=> current_time('mysql')];
		$format = ['%s','%s','%s','%s'];
		if ($id) {
			$wpdb->update($table, $data, ['id'=>$id], $format, ['%d']);
		} else {
			$data['created_at'] = current_time('mysql');
			$format[] = '%s';
			$wpdb->insert($table, $data, $format);
		}
		wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates'));
		exit;
	}

	/**
	 * Delete an affiliate.
	 */
	public function handle_delete_affiliate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_delete_affiliate' );
		global $wpdb;
		$table = $wpdb->prefix . 'bhg_affiliates';
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		if ( $id ) {
			$wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
		}
		wp_safe_redirect(admin_url('admin.php?page=bhg-affiliates'));
		exit;
	}

	/**
	 * Save plugin settings.
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_save_settings' );
		$opts = [
			'allow_guess_edit_until_close' => isset( $_POST['allow_guess_edit_until_close'] ) ? 'yes' : 'no',
			'guesses_max' => isset( $_POST['guesses_max'] ) ? max( 1, absint( wp_unslash( $_POST['guesses_max'] ) ) ) : 1,
		];
		foreach ($opts as $k => $v) {
			update_option('bhg_' . $k, $v, false);
		}
		wp_safe_redirect(admin_url('admin.php?page=bhg-settings&updated=1'));
		exit;
	}

	/**
	 * Save custom user metadata from the admin screen.
	 */
	public function handle_save_user_meta() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission', 'bonus-hunt-guesser' ) );
		}
		check_admin_referer( 'bhg_save_user_meta' );
		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		if ( $user_id ) {
			$real_name    = isset( $_POST['bhg_real_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bhg_real_name'] ) ) : '';
			$is_affiliate = isset( $_POST['bhg_is_affiliate'] ) ? 1 : 0;
			update_user_meta( $user_id, 'bhg_real_name', $real_name );
			update_user_meta( $user_id, 'bhg_is_affiliate', $is_affiliate );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=bhg-users' ) );
		exit;
	}

	/**
	 * Display admin notices for tournament actions.
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_GET['bhg_msg'] ) ) {
			return;
		}
		$msg = sanitize_text_field( wp_unslash( $_GET['bhg_msg'] ) );
		$map = [
			't_saved'  => __( 'Tournament saved.', 'bonus-hunt-guesser' ),
			't_error'  => __( 'Could not save tournament. Check logs.', 'bonus-hunt-guesser' ),
			'nonce'    => __( 'Security check failed. Please retry.', 'bonus-hunt-guesser' ),
			'noaccess' => __( 'You do not have permission to do that.', 'bonus-hunt-guesser' ),
			'invalid_final_balance' => __( 'Invalid final balance. Please enter a non-negative number.', 'bonus-hunt-guesser' ),
		];
		$class = ( strpos( $msg, 'error' ) !== false || 'nonce' === $msg || 'noaccess' === $msg ) ? 'notice notice-error' : 'notice notice-success';
		$text  = isset( $map[ $msg ] ) ? $map[ $msg ] : esc_html( $msg );
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $text ) . '</p></div>';
	}
}
