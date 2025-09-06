<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

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
		$hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
		$guesses_table = $wpdb->prefix . 'bhg_guesses';
		$limit         = max( 1, (int) $limit );

		$hunts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, starting_balance, final_balance, winners_count, closed_at
				 FROM `$hunts_table`
				 WHERE status = %s AND final_balance IS NOT NULL AND closed_at IS NOT NULL
				 ORDER BY closed_at DESC
				 LIMIT %d",
				'closed',
				$limit
			)
		);

		$out = array();

		foreach ( (array) $hunts as $h ) {
			$winners_count = max( 1, (int) $h->winners_count );
			$winners       = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT g.user_id, u.display_name, g.guess,
							ABS(g.guess - %f) AS diff
					 FROM `$guesses_table` g
					 LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id
					 WHERE g.hunt_id = %d
					 ORDER BY diff ASC, g.id ASC
					 LIMIT %d",
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
		$hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$hunts_table` WHERE id=%d", (int) $hunt_id ) );
	}

	/**
	 * Get ranked guesses for a hunt.
	 *
	 * @param int $hunt_id Hunt ID.
	 * @return array List of guesses.
	 */
	public static function get_hunt_guesses_ranked( $hunt_id ) {
		global $wpdb;
		$hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
		$guesses_table = $wpdb->prefix . 'bhg_guesses';
		$hunt          = self::get_hunt( $hunt_id );

		if ( ! $hunt ) { return array(); }

		if ( null !== $hunt->final_balance ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT g.*, u.display_name, ABS(g.guess - %f) AS diff
					 FROM `$guesses_table` g
					 LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id
					 WHERE g.hunt_id = %d
					 ORDER BY diff ASC, g.id ASC",
					$hunt->final_balance,
					$hunt_id
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.*, u.display_name, NULL AS diff
				 FROM `$guesses_table` g
				 LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id
				 WHERE g.hunt_id = %d
				 ORDER BY g.id ASC",
				$hunt_id
			)
		);
	}
}
