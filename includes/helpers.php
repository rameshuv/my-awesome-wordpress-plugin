<?php
/**
 * Helper utilities for the Bonus Hunt Guesser plugin.
 *
 * @package Bonus_Hunt_Guesser
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log debug messages when WP_DEBUG is enabled.
 *
 * @param mixed $message Message to log.
 * @return void
 */
function bhg_log( $message ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}
	if ( is_array( $message ) || is_object( $message ) ) {
		$message = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}
	if ( function_exists( 'error_log' ) ) {
		error_log( '[BHG] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

/**
 * Get the current user ID or 0 if not logged in.
 *
 * @return int
 */
function bhg_current_user_id() {
	$uid = get_current_user_id();
	return $uid ? (int) $uid : 0;
}

/**
 * Create a URL-friendly slug.
 *
 * @param string $text Text to slugify.
 * @return string
 */
function bhg_slugify( $text ) {
	$text = sanitize_title( $text );
	if ( ! $text ) {
		$text = uniqid( 'bhg' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_uniqid
	}
	return $text;
}

/**
 * Get admin capability for BHG plugin.
 *
 * @return string
 */
function bhg_admin_cap() {
	return apply_filters( 'bhg_admin_capability', 'manage_options' );
}

// Smart login redirect back to referring page.
add_filter(
	'login_redirect',
	/**
		 * Redirect users back to a validated URL when a custom redirect is provided.
		 *
		 * @param string           $redirect_to           Default redirect.
		 * @param string           $_requested_redirect_to Requested redirect. Unused.
		 * @param WP_User|WP_Error $_user                  User or error. Unused.
		 * @return string
		 */
		function ( $redirect_to, $_requested_redirect_to, $_user ) {
				unset( $_requested_redirect_to, $_user );
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$r = isset( $_GET['bhg_redirect'] ) ? esc_url_raw( wp_unslash( $_GET['bhg_redirect'] ) ) : '';
			if ( ! empty( $r ) ) {
					$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
					$r_host    = wp_parse_url( $r, PHP_URL_HOST );
				if ( ! $r_host || $r_host === $home_host ) {
						return $r;
				}
			}
				return $redirect_to;
		},
	10,
	3
);

/**
 * Determine if code runs on the frontend.
 *
 * @return bool
 */
function bhg_is_frontend() {
	return ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() );
}

if ( ! function_exists( 'bhg_t' ) ) {
		/**
		 * Retrieve a translation value from the database.
		 *
		 * The returned string is unsanitized and may contain HTML. Escape the
		 * value on output using {@see bhg_t_esc_html()} or {@see bhg_t_esc_attr()}.
		 *
		 * @param string $key            Translation key.
		 * @param string $default_value  Default text if not found.
		 * @return string Unsanitized translation value.
		 */
        function bhg_t( $key, $default_value = '' ) {
                global $wpdb;
                $p = $wpdb->prefix;
                static $cache = array();

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

                $table = esc_sql( "{$p}bhg_translations" );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT tvalue FROM {$table} WHERE tkey = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$key
			)
		);

		if ( $row && isset( $row->tvalue ) ) {
			$cache[ $key ] = $row->tvalue;
			return $row->tvalue;
		}

			return $default_value;
	}
}

if ( ! function_exists( 'bhg_t_esc_html' ) ) {
		/**
		 * Retrieve a translation and escape it for safe HTML output.
		 *
		 * @param string $key           Translation key.
		 * @param string $default_value Default text if not found.
		 * @return string Escaped translation for HTML context.
		 */
	function bhg_t_esc_html( $key, $default_value = '' ) {
			return esc_html( bhg_t( $key, $default_value ) );
	}
}

if ( ! function_exists( 'bhg_t_esc_attr' ) ) {
		/**
		 * Retrieve a translation and escape it for an HTML attribute.
		 *
		 * @param string $key           Translation key.
		 * @param string $default_value Default text if not found.
		 * @return string Escaped translation for attribute context.
		 */
	function bhg_t_esc_attr( $key, $default_value = '' ) {
			return esc_attr( bhg_t( $key, $default_value ) );
	}
}

if ( ! function_exists( 'bhg_get_default_translations' ) ) {
	/**
	 * Retrieve default translation key/value pairs.
	 *
	 * @return array
	 */
	function bhg_get_default_translations() {
		return array(
			'welcome_message'             => 'Welcome!',
			'goodbye_message'             => 'Goodbye!',
			'menu_dashboard'              => 'Dashboard',
			'menu_bonus_hunts'            => 'Bonus Hunts',
			'menu_results'                => 'Results',
			'menu_tournaments'            => 'Tournaments',
			'menu_users'                  => 'Users',
			'menu_affiliates'             => 'Affiliates',
			'menu_advertising'            => 'Advertising',
			'menu_translations'           => 'Translations',
			'menu_tools'                  => 'Tools',
			'menu_ads'                    => 'Ads',
			'label_start_balance'         => 'Starting Balance',
			'label_number_bonuses'        => 'Number of Bonuses',
			'label_prizes'                => 'Prizes',
			'label_submit_guess'          => 'Submit Guess',
			'label_guess'                 => 'Guess',
			'label_username'              => 'Username',
			'label_position'              => 'Position',
			'label_final_balance'         => 'Final Balance',
			'label_leaderboard'           => 'Leaderboard',
			'label_affiliate'             => 'Affiliate',
			'label_non_affiliate'         => 'Non-affiliate',
			'label_affiliate_status'      => 'Affiliate Status',
			'label_actions'               => 'Actions',
			'label_placements'            => 'Placements',
			'label_visible_to'            => 'Visible To',
			'label_start_date'            => 'Start Date',
			'label_end_date'              => 'End Date',
			'label_email'                 => 'Email',
			'label_real_name'             => 'Real Name',
			'label_search'                => 'Search',
			'notice_login_required'       => 'You must be logged in to guess.',
			'notice_guess_saved'          => 'Your guess has been saved.',
			'notice_guess_updated'        => 'Your guess has been updated.',
			'notice_hunt_closed'          => 'This bonus hunt is closed.',
			'notice_invalid_guess'        => 'Please enter a valid guess.',
			'notice_not_authorized'       => 'You are not authorized to perform this action.',
			'notice_translations_saved'   => 'Translations saved.',
			'notice_translations_reset'   => 'Translations reset.',
			'notice_no_active_hunt'       => 'No active bonus hunt found.',
			'notice_no_results'           => 'No results available.',
			'notice_user_removed'         => 'User removed.',
			'notice_ad_saved'             => 'Advertisement saved.',
			'notice_ad_deleted'           => 'Advertisement deleted.',
			'notice_settings_saved'       => 'Settings saved.',
			'notice_profile_updated'      => 'Profile updated.',
			'notice_security_failed'      => 'Security check failed.',
			'notice_invalid_hunt'         => 'Invalid hunt.',
			'notice_hunt_not_found'       => 'Hunt not found.',
			'notice_hunt_closed_change'   => 'This hunt is closed. You cannot submit or change a guess.',
			'notice_invalid_guess_amount' => 'Invalid guess amount.',
			'notice_max_guesses'          => 'You have reached the maximum number of guesses.',
			'notice_invalid_timeframe'    => 'Invalid timeframe.',
			'notice_no_data_available'    => 'No data available.',
			'notice_no_permission'        => 'You do not have sufficient permissions to perform this action.',
			'button_save'                 => 'Save',
			'button_cancel'               => 'Cancel',
			'button_delete'               => 'Delete',
			'button_edit'                 => 'Edit',
			'button_view'                 => 'View',
			'button_back'                 => 'Back',
			'msg_no_guesses'              => 'No guesses yet.',
			'msg_thank_you'               => 'Thank you!',
			'msg_error'                   => 'An error occurred.',
			'placeholder_enter_guess'     => 'Enter your guess',
			'notice_login_to_continue'    => 'Please log in to continue.',
			'button_log_in'               => 'Log in',
			'notice_no_active_hunts'      => 'No active bonus hunts at the moment.',
			'notice_login_to_guess'       => 'Please log in to submit your guess.',
			'notice_no_open_hunt'         => 'No open hunt found to guess.',
			'label_choose_hunt'           => 'Choose a hunt:',
			'label_select_hunt'           => 'Select a hunt',
			'label_guess_final_balance'   => 'Your guess (final balance):',
			'notice_no_hunts_found'       => 'No hunts found.',
			'label_user'                  => 'User',
			'notice_tournament_not_found' => 'Tournament not found.',
			'label_back_to_tournaments'   => 'Back to tournaments',
			'label_start'                 => 'Start',
			'label_end'                   => 'End',
			'label_status'                => 'Status',
			'notice_no_results_yet'       => 'No results yet.',
			'label_wins'                  => 'Wins',
			'label_last_win'              => 'Last win',
			'label_period'                => 'Period',
			'label_timeline'              => 'Timeline',
			'label_all'                   => 'All',
			'label_weekly'                => 'Weekly',
			'label_monthly'               => 'Monthly',
			'label_yearly'                => 'Yearly',
			'label_active'                => 'Active',
			'label_closed'                => 'Closed',
			'button_filter'               => 'Filter',
			'label_type'                  => 'Type',
			'label_details'               => 'Details',
			'label_show_details'          => 'Show details',
			'notice_no_tournaments'       => 'No tournaments found.',
			'label_bonus_hunts'           => 'Bonus Hunts',
			'notice_no_data_yet'          => 'No data yet.',
			'label_overall'               => 'Overall',
			'label_all_time'              => 'All-Time',
			'notice_no_closed_hunts'      => 'No closed hunts yet.',
			'label_final'                 => 'Final',
			'label_user_number'           => 'User #%d',
			'label_diff'                  => 'diff',
			'notice_login_view_content'   => 'Please log in to view this content.',
			'label_latest_hunts'          => 'Latest Hunts',
			'label_bonushunt'             => 'Bonushunt',
			'label_all_winners'           => 'All Winners',
			'label_closed_at'             => 'Closed At',
			'label_hunt'                  => 'Hunt',
			'label_title'                 => 'Title',
			'notice_no_user_specified'    => 'No user specified.',
			'notice_no_guesses_found'     => 'No guesses found.',
			'msg_no_ads_yet'              => 'No ads yet.',
			'notice_no_winners_yet'       => 'No winners yet.',
			// Shortcode labels for public views.
			'sc_hunt'                     => 'Hunt',
			'sc_guess'                    => 'Guess',
			'sc_final'                    => 'Final',
			'sc_title'                    => 'Title',
			'sc_start_balance'            => 'Start Balance',
			'sc_final_balance'            => 'Final Balance',
			'sc_status'                   => 'Status',
			'sc_affiliate'                => 'Affiliate',
			'sc_position'                 => 'Position',
			'sc_user'                     => 'User',
		);
	}
}

if ( ! function_exists( 'bhg_seed_default_translations_if_empty' ) ) {
	/**
	 * Seed default translations, avoiding duplicate entries.
	 *
	 * @return void
	 */
        function bhg_seed_default_translations_if_empty() {
                global $wpdb;
                $p = $wpdb->prefix;

                $table = $p . 'bhg_translations';

		foreach ( bhg_get_default_translations() as $tkey => $tvalue ) {
			$tkey = trim( (string) $tkey );
			if ( '' === $tkey ) {
				continue; // Skip invalid keys.
			}

			$wpdb->replace(
				$table,
				array(
					'tkey'   => $tkey,
					'tvalue' => $tvalue,
					'locale' => get_locale(),
				),
				array( '%s', '%s', '%s' )
			);
		}
	}
}

/**
 * Format an amount as currency.
 *
 * Allows the currency symbol to be customized via the `bhg_currency_symbol` filter.
 *
 * @param float $amount Amount to format.
 * @return string
 */
function bhg_format_currency( $amount ) {
	$symbol = apply_filters( 'bhg_currency_symbol', '€' );

	return sprintf( '%s%s', $symbol, number_format_i18n( (float) $amount, 2 ) );
}

/**
 * Validate a guess amount against settings.
 *
 * @param mixed $guess Guess value.
 * @return bool
 */
function bhg_validate_guess( $guess ) {
	$settings  = get_option( 'bhg_plugin_settings', array() );
	$min_guess = isset( $settings['min_guess_amount'] ) ? (float) $settings['min_guess_amount'] : 0;
	$max_guess = isset( $settings['max_guess_amount'] ) ? (float) $settings['max_guess_amount'] : 100000;

	if ( ! is_numeric( $guess ) ) {
		return false;
	}

	$guess = (float) $guess;
	return ( $guess >= $min_guess && $guess <= $max_guess );
}

/**
 * Get a user's display name with affiliate indicator.
 *
 * Uses the `bhg_is_affiliate` user meta to determine affiliate status.
 *
 * @param int $user_id User ID.
 * @return string Display name with optional affiliate indicator.
 */
function bhg_get_user_display_name( $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( ! $user ) {
		return __( 'Unknown User', 'bonus-hunt-guesser' );
	}

	$display_name = $user->display_name ? $user->display_name : $user->user_login;
	$is_affiliate = bhg_is_user_affiliate( (int) $user_id );

	if ( $is_affiliate ) {
		$display_name .= ' <span class="bhg-affiliate-indicator" title="' . esc_attr__( 'Affiliate User', 'bonus-hunt-guesser' ) . '">★</span>';
	}

	return $display_name;
}

if ( ! function_exists( 'bhg_is_user_affiliate' ) ) {
	/**
	 * Check if a user is an affiliate.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	function bhg_is_user_affiliate( $user_id ) {
		$val = get_user_meta( (int) $user_id, 'bhg_is_affiliate', true );
		return ( '1' === $val || 1 === $val || true === $val || 'yes' === $val );
	}
}

if ( ! function_exists( 'bhg_get_user_affiliate_sites' ) ) {
	/**
	 * Get affiliate site IDs for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	function bhg_get_user_affiliate_sites( $user_id ) {
		$ids = get_user_meta( (int) $user_id, 'bhg_affiliate_sites', true );
		if ( is_array( $ids ) ) {
			return array_map( 'absint', $ids );
		}
		if ( is_string( $ids ) && strlen( $ids ) ) {
			return array_map( 'absint', array_filter( array_map( 'trim', explode( ',', $ids ) ) ) );
		}
		return array();
	}
}

if ( ! function_exists( 'bhg_set_user_affiliate_sites' ) ) {
	/**
	 * Store affiliate site IDs for a user.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $site_ids Site IDs.
	 * @return void
	 */
	function bhg_set_user_affiliate_sites( $user_id, $site_ids ) {
		$clean = array();
		if ( is_array( $site_ids ) ) {
			foreach ( $site_ids as $sid ) {
				$sid = absint( $sid );
				if ( $sid ) {
					$clean[] = $sid;
				}
			}
		}
		update_user_meta( (int) $user_id, 'bhg_affiliate_sites', $clean );
	}
}

if ( ! function_exists( 'bhg_is_user_affiliate_for_site' ) ) {
	/**
	 * Determine if a user is an affiliate for a specific site.
	 *
	 * @param int $user_id User ID.
	 * @param int $site_id Site ID.
	 * @return bool
	 */
	function bhg_is_user_affiliate_for_site( $user_id, $site_id ) {
		if ( ! $site_id ) {
			return bhg_is_user_affiliate( (int) $user_id );
		}
		$sites = bhg_get_user_affiliate_sites( (int) $user_id );
		return in_array( absint( $site_id ), array_map( 'absint', (array) $sites ), true );
	}
}

if ( ! function_exists( 'bhg_render_affiliate_dot' ) ) {
	/**
	 * Render affiliate status dot.
	 *
	 * @param int $user_id                User ID.
	 * @param int $hunt_affiliate_site_id Hunt affiliate site ID.
	 * @return string
	 */
	function bhg_render_affiliate_dot( $user_id, $hunt_affiliate_site_id = 0 ) {
		$is_aff = bhg_is_user_affiliate_for_site( (int) $user_id, (int) $hunt_affiliate_site_id );
		$cls    = $is_aff ? 'bhg-aff-green' : 'bhg-aff-red';
		$label  = $is_aff ? esc_attr__( 'Affiliate', 'bonus-hunt-guesser' ) : esc_attr__( 'Non-affiliate', 'bonus-hunt-guesser' );
		return '<span class="bhg-aff-dot ' . esc_attr( $cls ) . '" aria-label="' . $label . '"></span>';
	}
}

/**
 * Render advertising blocks based on placement and user state.
 *
 * @param string $placement Placement location.
 * @param int    $hunt_id   Hunt ID.
 * @return string
 */
function bhg_render_ads( $placement = 'footer', $hunt_id = 0 ) {
        global $wpdb;
        $p = $wpdb->prefix;

        $tbl       = esc_sql( "{$p}bhg_ads" );
        $placement = sanitize_text_field( $placement );

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT content, link_url, visible_to FROM {$tbl} WHERE active=1 AND placement=%s ORDER BY id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$placement
		)
	);

	$hunt_site_id = 0;
        if ( $hunt_id ) {
                $hunts_tbl    = esc_sql( "{$p}bhg_bonus_hunts" );
                $hunt_site_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT affiliate_site_id FROM {$hunts_tbl} WHERE id=%d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $hunt_id
			)
		);
	}

	if ( ! $rows ) {
		return '';
	}

	$out = '<div class="bhg-ads bhg-ads-' . esc_attr( $placement ) . '">';
	foreach ( $rows as $r ) {
		$vis  = $r->visible_to ? $r->visible_to : 'all';
		$show = false;

		if ( 'all' === $vis ) {
			$show = true;
		} elseif ( 'guests' === $vis && ! is_user_logged_in() ) {
			$show = true;
		} elseif ( 'logged_in' === $vis && is_user_logged_in() ) {
			$show = true;
		} elseif ( 'affiliates' === $vis && is_user_logged_in() ) {
			$uid  = get_current_user_id();
			$show = $hunt_site_id > 0
				? bhg_is_user_affiliate_for_site( $uid, $hunt_site_id )
				: (bool) get_user_meta( $uid, 'bhg_is_affiliate', true );
		}

		if ( ! $show ) {
			continue;
		}

		$msg  = wp_kses_post( $r->content );
		$link = $r->link_url ? esc_url( $r->link_url ) : '';

		$out .= '<div class="bhg-ad" style="margin:10px 0;padding:10px;border:1px solid #e2e8f0;border-radius:6px;">';
		if ( $link ) {
			$out .= '<a href="' . $link . '">';
		}
		$out .= $msg;
		if ( $link ) {
			$out .= '</a>';
		}
		$out .= '</div>';
	}
	$out .= '</div>';

	return $out;
}

if ( ! function_exists( 'bhg_reset_demo_and_seed' ) ) {
	/**
	 * Reset demo tables and seed sample data.
	 *
	 * Administrative utility for creating sample plugin data. Callers must
	 * verify capabilities and nonces (e.g., via current_user_can() and
	 * check_admin_referer()) before invoking.
	 *
	 * @return bool
	 */
	function bhg_reset_demo_and_seed() {
		global $wpdb;

		$p = $wpdb->prefix;

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		check_admin_referer( 'bhg_reset_demo_and_seed' );

		// Ensure tables exist before touching.
		$tables = array(
			esc_sql( "{$p}bhg_guesses" ),
			esc_sql( "{$p}bhg_bonus_hunts" ),
			esc_sql( "{$p}bhg_tournaments" ),
			esc_sql( "{$p}bhg_tournament_results" ),
			esc_sql( "{$p}bhg_hunt_winners" ),
			esc_sql( "{$p}bhg_ads" ),
			esc_sql( "{$p}bhg_translations" ),
			esc_sql( "{$p}bhg_affiliate_websites" ),
		);

		// Delete rows (safer than TRUNCATE on some hosts).
		foreach ( $tables as $tbl ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tbl ) );
			if ( $exists !== $tbl ) {
				continue;
			}

			// Keep translations & affiliates content; they will be upserted below.
			if ( false !== strpos( $tbl, 'bhg_translations' ) || false !== strpos( $tbl, 'bhg_affiliate_websites' ) ) {
				continue;
			}

                        $wpdb->query(
                                $wpdb->prepare(
                                        "DELETE FROM {$tbl} WHERE 1 = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                        1
                                )
                        );
		}

		// Seed affiliate websites (idempotent upsert by slug).
		$aff_tbl = esc_sql( "{$p}bhg_affiliate_websites" );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aff_tbl ) ) === $aff_tbl ) {
			$affs = array(
				array(
					'name' => 'Main Site',
					'slug' => 'main-site',
					'url'  => home_url( '/' ),
				),
				array(
					'name' => 'Casino Hub',
					'slug' => 'casino-hub',
					'url'  => home_url( '/casino' ),
				),
			);
			foreach ( $affs as $a ) {
				$id = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM `{$aff_tbl}` WHERE slug=%s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$a['slug']
					)
				);
				if ( $id ) {
					$wpdb->update( $aff_tbl, $a, array( 'id' => (int) $id ), array( '%s', '%s', '%s' ), array( '%d' ) );
				} else {
					$wpdb->insert( $aff_tbl, $a, array( '%s', '%s', '%s' ) );
				}
			}
		}

		// Seed hunts.
		$hunts_tbl = esc_sql( "{$p}bhg_bonus_hunts" );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $hunts_tbl ) ) === $hunts_tbl ) {
			$now = current_time( 'mysql', 1 );

			$wpdb->insert(
				$hunts_tbl,
				array(
					'title'             => __( 'Bonus Hunt – Demo Open', 'bonus-hunt-guesser' ),
					'starting_balance'  => 2000.00,
					'num_bonuses'       => 10,
					'prizes'            => __( 'Gift card + swag', 'bonus-hunt-guesser' ),
					'status'            => 'open',
					'affiliate_site_id' => (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$p}bhg_affiliate_websites ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							1
						)
					),
					'created_at'        => $now,
					'updated_at'        => $now,
				),
				array( '%s', '%f', '%d', '%s', '%s', '%d', '%s', '%s' )
			);
			$open_id = (int) $wpdb->insert_id;

			$wpdb->insert(
				$hunts_tbl,
				array(
					'title'            => __( 'Bonus Hunt – Demo Closed', 'bonus-hunt-guesser' ),
					'starting_balance' => 1500.00,
					'num_bonuses'      => 8,
					'prizes'           => __( 'T-shirt', 'bonus-hunt-guesser' ),
					'status'           => 'closed',
					'final_balance'    => 1875.50,
					'winner_user_id'   => 1,
					'winner_diff'      => 12.50,
					'closed_at'        => gmdate( 'Y-m-d H:i:s', time() - 86400 ),
					'created_at'       => $now,
					'updated_at'       => $now,
				),
				array( '%s', '%f', '%d', '%s', '%s', '%f', '%d', '%f', '%s', '%s', '%s' )
			);

			// Seed guesses for open hunt.
                        $g_tbl = esc_sql( "{$p}bhg_guesses" );
                        $users = $wpdb->get_col(
                                $wpdb->prepare(
                                        "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                        5
                                )
                        );
			if ( empty( $users ) ) {
				$users = array( 1 );
			}
			$val = 2100.00;
			foreach ( $users as $uid ) {
				$wpdb->insert(
					$g_tbl,
					array(
						'hunt_id'    => $open_id,
						'user_id'    => (int) $uid,
						'guess'      => (float) $val,
						'created_at' => $now,
						'updated_at' => $now,
					),
					array( '%d', '%d', '%f', '%s', '%s' )
				);
				$val += 23.45;
			}
		}

		// Tournaments + results based on closed hunts.
		$t_tbl = esc_sql( "{$p}bhg_tournaments" );
		$r_tbl = esc_sql( "{$p}bhg_tournament_results" );

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_tbl ) ) === $t_tbl ) {
			// Wipe results only.
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $r_tbl ) ) === $r_tbl ) {
                                $wpdb->query(
                                        $wpdb->prepare(
                                                "DELETE FROM {$r_tbl} WHERE 1 = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                                                1
                                        )
                                );
			}

			$closed = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT winner_user_id, closed_at FROM {$hunts_tbl} WHERE status=%s AND winner_user_id IS NOT NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'closed'
				)
			);

			foreach ( (array) $closed as $row ) {
				$ts                        = $row->closed_at ? strtotime( $row->closed_at ) : time();
								$iso_year  = gmdate( 'o', $ts );
								$week      = str_pad( gmdate( 'W', $ts ), 2, '0', STR_PAD_LEFT );
								$week_key  = $iso_year . '-W' . $week;
								$month_key = gmdate( 'Y-m', $ts );
								$year_key  = gmdate( 'Y', $ts );

				$ensure = function ( $type, $period ) use ( $wpdb, $t_tbl ) {
					$now   = current_time( 'mysql', 1 );
					$start = $now;
					$end   = $now;

					if ( 'weekly' === $type ) {
						// Approximate ISO week range.
												$start = gmdate( 'Y-m-d', strtotime( $period . '-1' ) );
												$end   = gmdate( 'Y-m-d', strtotime( $period . '-7' ) );
					} elseif ( 'monthly' === $type ) {
						$start                       = $period . '-01';
												$end = gmdate( 'Y-m-t', strtotime( $start ) );
					} elseif ( 'yearly' === $type ) {
						$start = $period . '-01-01';
						$end   = $period . '-12-31';
					}

					$id = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$t_tbl} WHERE type=%s AND start_date=%s AND end_date=%s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$type,
							$start,
							$end
						)
					);

					if ( $id ) {
						return (int) $id;
					}

					$wpdb->insert(
						$t_tbl,
						array(
							'type'       => $type,
							'start_date' => $start,
							'end_date'   => $end,
							'status'     => 'active',
							'created_at' => $now,
							'updated_at' => $now,
						),
						array( '%s', '%s', '%s', '%s', '%s', '%s' )
					);

					return (int) $wpdb->insert_id;
				};

				$winner_user_id = (int) $row->winner_user_id;

				$target_tournaments = array(
					$ensure( 'weekly', $week_key ),
					$ensure( 'monthly', $month_key ),
					$ensure( 'yearly', $year_key ),
				);

				foreach ( $target_tournaments as $tid ) {
					if ( $tid && $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $r_tbl ) ) === $r_tbl ) {
						$wpdb->query(
							$wpdb->prepare(
								"INSERT INTO {$r_tbl} (tournament_id, user_id, wins) VALUES (%d, %d, 1) ON DUPLICATE KEY UPDATE wins = wins + 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								(int) $tid,
								$winner_user_id
							)
						);
					}
				}
			}
		}

		// Seed translations (upsert).
		$tr_tbl = esc_sql( "{$p}bhg_translations" );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tr_tbl ) ) === $tr_tbl ) {
			$pairs = array(
				'email_results_title'    => 'The Bonus Hunt has been closed!',
				'email_final_balance'    => 'Final Balance',
				'email_winner'           => 'Winner',
				'email_congrats_subject' => 'Congratulations! You won the Bonus Hunt',
				'email_congrats_body'    => 'You had the closest guess. Great job!',
				'email_hunt'             => 'Hunt',
			);
			foreach ( $pairs as $k => $v ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$tr_tbl} WHERE tkey=%s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$k
					)
				);
				if ( $exists ) {
					$wpdb->update( $tr_tbl, array( 'tvalue' => $v ), array( 'id' => (int) $exists ), array( '%s' ), array( '%d' ) );
				} else {
					$wpdb->insert(
						$tr_tbl,
						array(
							'tkey'   => $k,
							'tvalue' => $v,
						),
						array( '%s', '%s' )
					);
				}
			}
		}

		// Seed ads.
		$ads_tbl = esc_sql( "{$p}bhg_ads" );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ads_tbl ) ) === $ads_tbl ) {
			$now = current_time( 'mysql', 1 );
			$wpdb->insert(
				$ads_tbl,
				array(
					'title'        => '',
					'content'      => '<strong>Play responsibly.</strong> <a href="' . esc_url( home_url( '/promo' ) ) . '">See promo</a>',
					'link_url'     => '',
					'placement'    => 'footer',
					'visible_to'   => 'all',
					'target_pages' => '',
					'active'       => 1,
					'created_at'   => $now,
					'updated_at'   => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}

		return true;
	}
}
