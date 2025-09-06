<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Get sanitized table name from whitelist.
 *
 * @param string $slug Table slug.
 * @return string Sanitized table name or empty string.
 */
function bhg_demo_get_table_name( $slug ) {
        global $wpdb;
        $allowed = array(
                'bhg_bonus_hunts',
                'bhg_guesses',
                'bhg_tournaments',
                'bhg_affiliate_websites',
                'bhg_ads',
                'bhg_translations',
        );

        if ( in_array( $slug, $allowed, true ) ) {
                return esc_sql( $wpdb->prefix . $slug );
        }

        return '';
}


function bhg_seed_demo_on_activation() {
	// Only seed once
	if ( get_option( 'bhg_demo_seeded' ) ) {
		return;
	}

	// Create demo users (if not exist)
	$users    = array(
		array(
			'user_login' => 'alice_demo',
			'user_email' => 'alice_demo@example.com',
		),
		array(
			'user_login' => 'bob_demo',
			'user_email' => 'bob_demo@example.com',
		),
		array(
			'user_login' => 'charlie_demo',
			'user_email' => 'charlie_demo@example.com',
		),
	);
	$user_ids = array();
	foreach ( $users as $u ) {
		$uid = username_exists( $u['user_login'] );
		if ( ! $uid ) {
			$pass = wp_generate_password( 12, false );
			$uid  = wp_create_user( $u['user_login'], $pass, $u['user_email'] );
			if ( is_wp_error( $uid ) ) {
				$uid = 0; }
		}
		$user_ids[ $u['user_login'] ] = $uid ? intval( $uid ) : 0;
	}

        global $wpdb;
        $hunts       = bhg_demo_get_table_name( 'bhg_bonus_hunts' );
        $guesses     = bhg_demo_get_table_name( 'bhg_guesses' );
        $tournaments = bhg_demo_get_table_name( 'bhg_tournaments' );
        $aff         = bhg_demo_get_table_name( 'bhg_affiliate_websites' );
        $ads         = bhg_demo_get_table_name( 'bhg_ads' );
        $trans       = bhg_demo_get_table_name( 'bhg_translations' );

        if ( ! $hunts || ! $guesses || ! $tournaments || ! $aff || ! $ads || ! $trans ) {
                return;
        }

	// Insert affiliate sites
	$wpdb->insert(
		$aff,
		array(
			'name' => 'Demo Affiliate 1',
			'slug' => 'demo-aff-1',
			'url'  => 'https://demo-affiliate1.test',
		)
	);
	$wpdb->insert(
		$aff,
		array(
			'name' => 'Demo Affiliate 2',
			'slug' => 'demo-aff-2',
			'url'  => 'https://demo-affiliate2.test',
		)
	);

	// Insert a demo hunt (open) and a demo closed hunt
	$wpdb->insert(
		$hunts,
		array(
			'title'            => 'Bonus Hunt – Demo (Open)',
			'starting_balance' => 2000.00,
			'num_bonuses'      => 10,
			'prizes'           => 'Gift Card €50, Merch',
			'status'           => 'open',
			'created_at'       => current_time( 'mysql' ),
		)
	);
	$open_id = intval( $wpdb->insert_id );

	$wpdb->insert(
		$hunts,
		array(
			'title'            => 'Bonus Hunt – Demo (Closed)',
			'starting_balance' => 1500.00,
			'num_bonuses'      => 8,
			'prizes'           => 'Gift Card €25',
			'status'           => 'closed',
			'final_balance'    => 2420.00,
			'winner_user_id'   => 0,
			'winner_diff'      => 0,
			'closed_at'        => current_time( 'mysql', true ),
			'created_at'       => current_time( 'mysql' ),
		)
	);
	$closed_id = intval( $wpdb->insert_id );

	// Add demo guesses for closed hunt
	$grows = array(
		array(
			'hunt_id'      => $closed_id,
			'user_id'      => $user_ids['alice_demo'],
			'guess_amount' => 2450.00,
		),
		array(
			'hunt_id'      => $closed_id,
			'user_id'      => $user_ids['bob_demo'],
			'guess_amount' => 2400.00,
		),
		array(
			'hunt_id'      => $closed_id,
			'user_id'      => $user_ids['charlie_demo'],
			'guess_amount' => 1800.00,
		),
	);
	foreach ( $grows as $r ) {
		if ( $r['user_id'] ) {
			$wpdb->insert(
				$guesses,
				array(
					'hunt_id'      => $r['hunt_id'],
					'user_id'      => $r['user_id'],
					'guess_amount' => $r['guess_amount'],
					'created_at'   => current_time( 'mysql' ),
				)
			);
		}
	}

				// Compute winner for the closed hunt (closest)
				$rows = $wpdb->get_results(
                                $wpdb->prepare(
                                        "SELECT user_id, guess_amount FROM `{$guesses}` WHERE hunt_id = %d",
                                        $closed_id
                                )
                );
		$final        = 2420.00;
		$winner_id    = 0;
		$winner_diff  = null;
	foreach ( $rows as $row ) {
		$diff = abs( floatval( $row->guess_amount ) - $final );
		if ( $winner_diff === null || $diff < $winner_diff ) {
			$winner_diff = $diff;
			$winner_id   = intval( $row->user_id );
		}
	}
	$wpdb->update(
		$hunts,
		array(
			'winner_user_id' => $winner_id,
			'winner_diff'    => $winner_diff,
		),
		array( 'id' => $closed_id )
	);

	// Insert a demo tournament (monthly)
	$wpdb->insert(
		$tournaments,
		array(
			'title'      => 'Monthly Tournament (Demo)',
			'status'     => 'active',
			'created_at' => current_time( 'mysql' ),
		)
	);

	// Translations & Ads samples
	$wpdb->insert(
		$trans,
		array(
			'tkey'   => 'email_results_title',
			'tvalue' => 'The Bonus Hunt has been closed!',
		)
	);
	$wpdb->insert(
		$trans,
		array(
			'tkey'   => 'email_final_balance',
			'tvalue' => 'Final Balance',
		)
	);
	$wpdb->insert(
		$trans,
		array(
			'tkey'   => 'email_winner',
			'tvalue' => 'Winner',
		)
	);
	$wpdb->insert(
		$ads,
		array(
			'title'        => '',
			'content'      => 'Sponsored by Demo Casino – Play Now',
			'link_url'     => 'https://example.com',
			'placement'    => 'footer',
			'visible_to'   => 'guests',
			'target_pages' => '',
			'active'       => 1,
		)
	);

	// Create demo pages with shortcodes (with (Demo) suffix)
	$pages = array(
		array(
			'title'   => 'Bonus Hunt (Demo)',
			'content' => '[bhg_bonus_hunt]',
		),
		array(
			'title'   => 'Leaderboard (Demo)',
			'content' => '[bhg_leaderboard]',
		),
		array(
			'title'   => 'Tournaments (Demo)',
			'content' => '[bhg_tournament_leaderboard]',
		),
	);
	foreach ( $pages as $p ) {
		if ( ! get_page_by_title( $p['title'] ) ) {
			wp_insert_post(
				array(
					'post_title'   => $p['title'],
					'post_content' => $p['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}
	}

	update_option( 'bhg_demo_seeded', 1 );
}
