<?php
/**
 * Bonus Hunt helper functions.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bonus hunt data helpers.
 */
class BHG_Bonus_Hunts {

	/**
	 * Retrieve latest hunts with winners.
	 *
	 * @param int $limit Number of hunts to fetch. Default 3.
	 * @return array List of hunts and winners.
	 */
	public static function get_latest_hunts_with_winners( $limit = 3 ) {
		global $wpdb;
		$hunts_table   = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
		$guesses_table = esc_sql( $wpdb->prefix . 'bhg_guesses' );
		$users_table   = esc_sql( $wpdb->users );
		$limit         = max( 1, (int) $limit );

		$cache_key = 'bhg_latest_hunts_' . $limit;
		$cached    = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $cached ) {
			return $cached;
		}

				$hunts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT id, title, starting_balance, final_balance, winners_count, closed_at FROM `{$hunts_table}` WHERE status = %s AND final_balance IS NOT NULL AND closed_at IS NOT NULL ORDER BY closed_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is dynamic but sanitized.
						'closed',
						$limit
					)
				);

		$out = array();

		foreach ( (array) $hunts as $h ) {
			$winners_count       = max( 1, (int) $h->winners_count );
						$winners = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT g.user_id, u.display_name, g.guess, ABS(g.guess - %f) AS diff FROM `{$guesses_table}` g LEFT JOIN `{$users_table}` u ON u.ID = g.user_id WHERE g.hunt_id = %d ORDER BY diff ASC, g.id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are dynamic but sanitized.
								$h->final_balance,
								$h->id,
								$winners_count
							)
						);

			$out[] = array(
				'hunt'    => $h,
				'winners' => $winners,
			);
		}

		wp_cache_set( $cache_key, $out, 'bhg', HOUR_IN_SECONDS );

		return $out;
	}

	/**
	 * Retrieve a hunt by ID.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return object|null Hunt data or null if not found.
	 */
	public static function get_hunt( $hunt_id ) {
		global $wpdb;
		$hunts_table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );

		$cache_key = 'bhg_hunt_' . (int) $hunt_id;
		$hunt      = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $hunt ) {
			return $hunt;
		}

				$hunt = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT id, title, starting_balance, num_bonuses, prizes, affiliate_site_id, winners_count, final_balance, status, created_at, updated_at, closed_at FROM `{$hunts_table}` WHERE id=%d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is dynamic but sanitized.
						(int) $hunt_id
					)
				);

		wp_cache_set( $cache_key, $hunt, 'bhg', HOUR_IN_SECONDS );

		return $hunt;
	}

	/**
	 * Get ranked guesses for a hunt.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return array List of guesses.
	 */
	public static function get_hunt_guesses_ranked( $hunt_id ) {
		global $wpdb;
		$guesses_table = esc_sql( $wpdb->prefix . 'bhg_guesses' );
		$users_table   = esc_sql( $wpdb->users );
		$hunt          = self::get_hunt( $hunt_id );

		if ( ! $hunt ) {
			return array();
		}

		$cache_key_suffix = null === $hunt->final_balance ? 'open' : $hunt->final_balance;
		$cache_key        = 'bhg_hunt_guesses_ranked_' . $hunt_id . '_' . $cache_key_suffix;
		$cached           = wp_cache_get( $cache_key, 'bhg' );
		if ( false !== $cached ) {
			return $cached;
		}

		if ( null !== $hunt->final_balance ) {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT g.id, g.user_id, g.guess, g.created_at, u.display_name, ABS(g.guess - %f) AS diff FROM `{$guesses_table}` g LEFT JOIN `{$users_table}` u ON u.ID = g.user_id WHERE g.hunt_id = %d ORDER BY diff ASC, g.id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are dynamic but sanitized.
								$hunt->final_balance,
								$hunt_id
							)
						);
		} else {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT g.id, g.user_id, g.guess, g.created_at, u.display_name, NULL AS diff FROM `{$guesses_table}` g LEFT JOIN `{$users_table}` u ON u.ID = g.user_id WHERE g.hunt_id = %d ORDER BY g.id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names are dynamic but sanitized.
								$hunt_id
							)
						);
		}

		wp_cache_set( $cache_key, $results, 'bhg', HOUR_IN_SECONDS );

		return $results;
	}
}
