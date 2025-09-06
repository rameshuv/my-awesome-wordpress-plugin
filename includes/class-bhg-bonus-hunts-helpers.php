<?php
/**
 * Helper functions for hunts and guesses used by admin dashboard, list and results.
 *
 * DB tables assumed:
 * - {$wpdb->prefix}bhg_bonus_hunts (id, title, starting_balance, final_balance, winners_count, status, closed_at)
 * - {$wpdb->prefix}bhg_guesses (id, hunt_id, user_id, guess, created_at)
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bhg_get_hunt' ) ) {
	/**
	 * Retrieve a bonus hunt by its ID.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return object|null Hunt object on success, null on failure.
	 */
	function bhg_get_hunt( $hunt_id ) {
		global $wpdb;
		$hunt_id   = (int) $hunt_id;
		$cache_key = "bhg_hunt_{$hunt_id}";

		$cached = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $cached ) {
			return $cached;
		}

		$t    = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunt = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, title, starting_balance, final_balance, winners_count, status, closed_at FROM {$t} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$hunt_id
			)
		);
		wp_cache_set( $cache_key, $hunt, 'bhg', HOUR_IN_SECONDS );

		return $hunt;
	}
}

if ( ! function_exists( 'bhg_get_latest_closed_hunts' ) ) {
	/**
	 * Retrieve the most recently closed hunts.
	 *
	 * @param int $limit Number of hunts to fetch. Default 3.
	 * @return array List of hunt objects.
	 */
	function bhg_get_latest_closed_hunts( $limit = 3 ) {
		global $wpdb;
		$limit     = (int) $limit;
		$cache_key = "bhg_latest_closed_hunts_{$limit}";

		$cached = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $cached ) {
			return $cached;
		}

		$t    = $wpdb->prefix . 'bhg_bonus_hunts';
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, title, starting_balance, final_balance, winners_count, closed_at FROM {$t} WHERE status = %s ORDER BY closed_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'closed',
				$limit
			)
		);
		wp_cache_set( $cache_key, $rows, 'bhg', HOUR_IN_SECONDS );

		return $rows;
	}
}

if ( ! function_exists( 'bhg_get_top_winners_for_hunt' ) ) {
	/**
	 * Get top winners for a specific hunt ordered by closest guesses.
	 *
	 * @param int $hunt_id       Hunt ID.
	 * @param int $winners_limit Optional. Number of winners to retrieve. Default 3.
	 * @return array List of winner rows.
	 */
	function bhg_get_top_winners_for_hunt( $hunt_id, $winners_limit = 3 ) {
		global $wpdb;
		$t_g     = $wpdb->prefix . 'bhg_guesses';
		$t_h     = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunt_id = (int) $hunt_id;

		$hunt = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT final_balance, winners_count FROM {$t_h} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$hunt_id
			)
		);
		if ( ! $hunt || null === $hunt->final_balance ) {
			return array();
		}

		$limit     = $winners_limit ? $winners_limit : ( (int) $hunt->winners_count ? (int) $hunt->winners_count : 3 );
		$cache_key = "bhg_top_winners_{$hunt_id}_{$limit}";

		$cached = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT g.user_id, g.guess, ABS( g.guess - %f ) AS diff FROM {$t_g} g WHERE g.hunt_id = %d ORDER BY diff ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(float) $hunt->final_balance,
				$hunt_id,
				(int) $limit
			)
		);
		wp_cache_set( $cache_key, $rows, 'bhg', HOUR_IN_SECONDS );

		return $rows;
	}
}

if ( ! function_exists( 'bhg_get_all_ranked_guesses' ) ) {
	/**
	 * Get all guesses for a hunt ranked by their difference from the final balance.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return array List of guesses with difference values.
	 */
	function bhg_get_all_ranked_guesses( $hunt_id ) {
		global $wpdb;
		$t_g  = $wpdb->prefix . 'bhg_guesses';
		$t_h  = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunt = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT final_balance FROM {$t_h} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $hunt_id
			)
		);
		if ( ! $hunt || null === $hunt->final_balance ) {
			return array();
		}

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT g.id, g.user_id, g.guess, ABS( g.guess - %f ) AS diff FROM {$t_g} g WHERE g.hunt_id = %d ORDER BY diff ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(float) $hunt->final_balance,
				(int) $hunt_id
			)
		);
	}
}

if ( ! function_exists( 'bhg_get_hunt_participants' ) ) {
	/**
	 * Retrieve participants for a hunt with pagination support.
	 *
	 * @param int $hunt_id  Hunt ID.
	 * @param int $paged    Optional. Page number. Default 1.
	 * @param int $per_page Optional. Results per page. Default 30.
	 * @return array {
	 *     @type array $rows  Participant rows.
	 *     @type int   $total Total number of guesses.
	 * }
	 */
	function bhg_get_hunt_participants( $hunt_id, $paged = 1, $per_page = 30 ) {
		global $wpdb;
		$t_g    = $wpdb->prefix . 'bhg_guesses';
		$offset = max( 0, ( (int) $paged - 1 ) * (int) $per_page );

		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, user_id, guess, created_at FROM {$t_g} WHERE hunt_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				(int) $hunt_id,
				(int) $per_page,
				(int) $offset
			)
		);
		$total = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$t_g} WHERE hunt_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	/**
	 * Delete a guess and flush its related cache.
	 *
	 * @param int $guess_id Guess ID.
	 * @return int|false Number of rows deleted or false on failure.
	 */
	function bhg_remove_guess( $guess_id ) {
		global $wpdb;
		$t_g      = $wpdb->prefix . 'bhg_guesses';
		$guess_id = (int) $guess_id;

		$hunt_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT hunt_id FROM {$t_g} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$guess_id
			)
		);
		$deleted = $wpdb->delete( $t_g, array( 'id' => $guess_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		if ( $hunt_id ) {
			bhg_flush_hunt_cache( $hunt_id );
		}

		return $deleted;
	}
}

if ( ! function_exists( 'bhg_flush_hunt_cache' ) ) {
	/**
	 * Clear cached data for a specific hunt.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return void
	 */
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
