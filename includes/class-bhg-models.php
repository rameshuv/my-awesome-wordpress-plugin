<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Data layer utilities for Bonus Hunt Guesser.
 *
 * This class previously handled guess submissions directly. Guess handling is
 * now centralized through {@see bhg_handle_submit_guess()} in
 * `bonus-hunt-guesser.php`. The methods related to form handling and request
 * routing were removed to avoid duplication and ensure a single canonical
 * implementation.
 */
class BHG_Models {

	/**
	 * Close a bonus hunt and determine winners.
	 *
	 * @param int   $hunt_id       Hunt identifier.
	 * @param float $final_balance Final balance for the hunt.
	 *
	 * @return int[] Array of winning user IDs.
	 */
	public static function close_hunt( $hunt_id, $final_balance ) {
		global $wpdb;

		$hunt_id       = (int) $hunt_id;
		$final_balance = (float) $final_balance;

		if ( $hunt_id <= 0 ) {
			return array();
		}

		$hunts_tbl   = $wpdb->prefix . 'bhg_bonus_hunts';
		$guesses_tbl = $wpdb->prefix . 'bhg_guesses';

		// Determine number of winners for this hunt.
		$winners_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT winners_count FROM {$hunts_tbl} WHERE id = %d",
				$hunt_id
			)
		);
		if ( $winners_count <= 0 ) {
			$winners_count = 1;
		}

		// Update hunt status and final details.
		$now = current_time( 'mysql' );
		$wpdb->update(
			$hunts_tbl,
			array(
				'status'        => 'closed',
				'final_balance' => $final_balance,
				'closed_at'     => $now,
				'updated_at'    => $now,
			),
			array( 'id' => $hunt_id ),
			array( '%s', '%f', '%s', '%s' ),
			array( '%d' )
		);

		// Fetch winners based on proximity to final balance.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id FROM {$guesses_tbl} WHERE hunt_id = %d ORDER BY ABS(guess - %f) ASC, id ASC LIMIT %d",
				$hunt_id,
				$final_balance,
				$winners_count
			)
		);

		if ( empty( $rows ) ) {
			return array();
		}

		return array_map( 'intval', wp_list_pluck( $rows, 'user_id' ) );
	}
}
