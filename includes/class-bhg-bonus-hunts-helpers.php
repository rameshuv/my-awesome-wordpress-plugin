<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Helper functions for hunts and guesses used by admin dashboard, list and results.
 * DB tables assumed:
 *  - {$wpdb->prefix}bhg_bonus_hunts (id, title, starting_balance, final_balance, winners_count, status, closed_at)
 *  - {$wpdb->prefix}bhg_guesses (id, hunt_id, user_id, guess, created_at)
 */

if (!function_exists('bhg_get_hunt')) {
	function bhg_get_hunt($hunt_id){
		global $wpdb;
		$t = $wpdb->prefix . 'bhg_bonus_hunts';
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d", (int)$hunt_id));
	}
}

if (!function_exists('bhg_get_latest_closed_hunts')) {
	function bhg_get_latest_closed_hunts($limit = 3){
		global $wpdb;
		$t = $wpdb->prefix . 'bhg_bonus_hunts';
		$sql = $wpdb->prepare("SELECT id, title, starting_balance, final_balance, winners_count, closed_at
							   FROM $t
							   WHERE status = %s
							   ORDER BY closed_at DESC
							   LIMIT %d", 'closed', (int)$limit);
		return $wpdb->get_results($sql);
	}
}

if (!function_exists('bhg_get_top_winners_for_hunt')) {
	function bhg_get_top_winners_for_hunt($hunt_id, $winners_limit = 3){
		global $wpdb;
		$t_g = $wpdb->prefix . 'bhg_guesses';
		$t_h = $wpdb->prefix . 'bhg_bonus_hunts';

		$hunt = $wpdb->get_row($wpdb->prepare("SELECT final_balance, winners_count FROM $t_h WHERE id=%d", (int)$hunt_id));
		if (!$hunt || $hunt->final_balance === null) return array();
		$limit = $winners_limit ?: (int) $hunt->winners_count ?: 3;

		$sql = $wpdb->prepare(
			"SELECT g.user_id, g.guess, ABS(g.guess - %f) AS diff
			 FROM $t_g g
			 WHERE g.hunt_id = %d
			 ORDER BY diff ASC
			 LIMIT %d",
			(float)$hunt->final_balance, (int)$hunt_id, (int)$limit
		);
		return $wpdb->get_results($sql);
	}
}

if (!function_exists('bhg_get_all_ranked_guesses')) {
	function bhg_get_all_ranked_guesses($hunt_id){
		global $wpdb;
		$t_g = $wpdb->prefix . 'bhg_guesses';
		$t_h = $wpdb->prefix . 'bhg_bonus_hunts';
		$hunt = $wpdb->get_row($wpdb->prepare("SELECT final_balance FROM $t_h WHERE id=%d", (int)$hunt_id));
		if (!$hunt || $hunt->final_balance === null) return array();

		$sql = $wpdb->prepare(
			"SELECT g.id, g.user_id, g.guess, ABS(g.guess - %f) AS diff
			 FROM $t_g g
			 WHERE g.hunt_id = %d
			 ORDER BY diff ASC",
			(float)$hunt->final_balance, (int)$hunt_id
		);
		return $wpdb->get_results($sql);
	}
}

if (!function_exists('bhg_get_hunt_participants')) {
	function bhg_get_hunt_participants($hunt_id, $paged = 1, $per_page = 30){
		global $wpdb;
		$t_g = $wpdb->prefix . 'bhg_guesses';
		$offset = max(0, ((int)$paged - 1) * (int)$per_page);

			$rows = $wpdb->get_results(
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
			return array( 'rows' => $rows, 'total' => $total );
	}
}

if (!function_exists('bhg_remove_guess')) {
	function bhg_remove_guess($guess_id){
		global $wpdb;
		$t_g = $wpdb->prefix . 'bhg_guesses';
		return $wpdb->delete($t_g, array('id' => (int)$guess_id), array('%d'));
	}
}
