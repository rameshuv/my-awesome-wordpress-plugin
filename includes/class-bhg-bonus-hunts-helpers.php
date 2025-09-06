<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Helper functions for hunts and guesses used by admin dashboard, list and results.
 * DB tables assumed:
 *  - {$wpdb->prefix}bhg_bonus_hunts (id, title, starting_balance, final_balance, winners_count, status, closed_at)
 *  - {$wpdb->prefix}bhg_guesses (id, hunt_id, user_id, guess, created_at)
 */

if ( ! function_exists( 'bhg_get_hunt' ) ) {
       function bhg_get_hunt( $hunt_id ) {
               global $wpdb;
               $hunt_id   = (int) $hunt_id;
               $cache_key = "bhg_hunt_{$hunt_id}";

               $cached = wp_cache_get( $cache_key, 'bhg' );
               if ( false !== $cached ) {
                       return $cached;
               }

               $t    = $wpdb->prefix . 'bhg_bonus_hunts';
               $hunt = $wpdb->get_row( $wpdb->prepare( "SELECT id, title, starting_balance, final_balance, winners_count, status, closed_at FROM $t WHERE id=%d", $hunt_id ) );
               wp_cache_set( $cache_key, $hunt, 'bhg', HOUR_IN_SECONDS );

               return $hunt;
       }
}

if ( ! function_exists( 'bhg_get_latest_closed_hunts' ) ) {
    function bhg_get_latest_closed_hunts( $limit = 3 ) {
        global $wpdb;
        $limit     = (int) $limit;
        $cache_key = "bhg_latest_closed_hunts_{$limit}";

        $cached = wp_cache_get( $cache_key, 'bhg' );
        if ( false !== $cached ) {
            return $cached;
        }

        $t   = $wpdb->prefix . 'bhg_bonus_hunts';
        $sql = $wpdb->prepare(
            "SELECT id, title, starting_balance, final_balance, winners_count, closed_at"
            . " FROM $t"
            . " WHERE status = %s"
            . " ORDER BY closed_at DESC"
            . " LIMIT %d",
            'closed',
            $limit
        );
        $rows = $wpdb->get_results( $sql );
        wp_cache_set( $cache_key, $rows, 'bhg', HOUR_IN_SECONDS );

        return $rows;
    }
}

if ( ! function_exists( 'bhg_get_top_winners_for_hunt' ) ) {
    function bhg_get_top_winners_for_hunt( $hunt_id, $winners_limit = 3 ) {
        global $wpdb;
        $t_g    = $wpdb->prefix . 'bhg_guesses';
        $t_h    = $wpdb->prefix . 'bhg_bonus_hunts';
        $hunt_id = (int) $hunt_id;

        $hunt = $wpdb->get_row( $wpdb->prepare( "SELECT final_balance, winners_count FROM $t_h WHERE id=%d", $hunt_id ) );
        if ( ! $hunt || null === $hunt->final_balance ) {
            return array();
        }

        $limit     = $winners_limit ? $winners_limit : ( (int) $hunt->winners_count ? (int) $hunt->winners_count : 3 );
        $cache_key = "bhg_top_winners_{$hunt_id}_{$limit}";

        $cached = wp_cache_get( $cache_key, 'bhg' );
        if ( false !== $cached ) {
            return $cached;
        }

        $sql = $wpdb->prepare(
            "SELECT g.user_id, g.guess, ABS(g.guess - %f) AS diff"
            . " FROM $t_g g"
            . " WHERE g.hunt_id = %d"
            . " ORDER BY diff ASC"
            . " LIMIT %d",
            (float) $hunt->final_balance,
            $hunt_id,
            (int) $limit
        );
        $rows = $wpdb->get_results( $sql );
        wp_cache_set( $cache_key, $rows, 'bhg', HOUR_IN_SECONDS );

        return $rows;
    }
}

if ( ! function_exists( 'bhg_get_all_ranked_guesses' ) ) {
	function bhg_get_all_ranked_guesses( $hunt_id ) {
		global $wpdb;
		$t_g  = $wpdb->prefix . 'bhg_guesses';
		$t_h  = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunt = $wpdb->get_row( $wpdb->prepare( "SELECT final_balance FROM $t_h WHERE id=%d", (int) $hunt_id ) );
		if ( ! $hunt || $hunt->final_balance === null ) {
			return array();
		}

		$sql = $wpdb->prepare(
			"SELECT g.id, g.user_id, g.guess, ABS(g.guess - %f) AS diff
			 FROM $t_g g
			 WHERE g.hunt_id = %d
			 ORDER BY diff ASC",
			(float) $hunt->final_balance,
			(int) $hunt_id
		);
		return $wpdb->get_results( $sql );
	}
}

if ( ! function_exists( 'bhg_get_hunt_participants' ) ) {
	function bhg_get_hunt_participants( $hunt_id, $paged = 1, $per_page = 30 ) {
		global $wpdb;
		$t_g    = $wpdb->prefix . 'bhg_guesses';
		$offset = max( 0, ( (int) $paged - 1 ) * (int) $per_page );

			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, user_id, guess, created_at
					 FROM $t_g
					 WHERE hunt_id = %d
					 ORDER BY created_at DESC
					 LIMIT %d OFFSET %d",
					(int) $hunt_id,
					(int) $per_page,
					(int) $offset
				)
			);
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $t_g WHERE hunt_id = %d",
					(int) $hunt_id
				)
			);
			return array(
				'rows'  => $rows,
				'total' => $total,
			);
	}
}

if ( ! function_exists( 'bhg_remove_guess' ) ) {
    function bhg_remove_guess( $guess_id ) {
        global $wpdb;
        $t_g      = $wpdb->prefix . 'bhg_guesses';
        $guess_id = (int) $guess_id;

        $hunt_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT hunt_id FROM $t_g WHERE id = %d", $guess_id ) );
        $deleted = $wpdb->delete( $t_g, array( 'id' => $guess_id ), array( '%d' ) );

        if ( $hunt_id ) {
            bhg_flush_hunt_cache( $hunt_id );
        }

        return $deleted;
    }
}

if ( ! function_exists( 'bhg_flush_hunt_cache' ) ) {
    function bhg_flush_hunt_cache( $hunt_id ) {
        $hunt_id = (int) $hunt_id;
        wp_cache_delete( "bhg_hunt_{$hunt_id}", 'bhg' );
        for ( $i = 1; $i <= 25; $i++ ) {
            wp_cache_delete( "bhg_top_winners_{$hunt_id}_{$i}", 'bhg' );
        }
        // Latest closed hunts cache uses default limit 3.
        wp_cache_delete( 'bhg_latest_closed_hunts_3', 'bhg' );
    }
}
