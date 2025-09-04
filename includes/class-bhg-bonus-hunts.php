<?php
if (!defined('ABSPATH')) exit;

class BHG_Bonus_Hunts {

    /**
     * Return latest closed hunts with their top N winners.
     * @param int $limit number of hunts (default 3)
     * @return array of arrays: [ 'hunt' => object, 'winners' => array ]
     */
    public static function get_latest_hunts_with_winners($limit = 3) {
        global $wpdb;
        $limit = max(1, (int)$limit);

        $hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        $users_table   = $wpdb->users;

        // Fetch latest closed hunts (prefer closed_at; fallback to updated_at for ordering)
        $hunts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, starting_balance, final_balance, winners_count, closed_at
                 FROM `$hunts_table`
                 WHERE final_balance IS NOT NULL AND closed_at IS NOT NULL AND status = %s
                 ORDER BY closed_at DESC
                 LIMIT %d",
                'closed', $limit
            )
        );

        $out = [];
        foreach ((array)$hunts as $h) {
            $winners_count = (int)$h->winners_count;
            if ($winners_count < 1) $winners_count = 3;
            if ($winners_count > 25) $winners_count = 25;

            // Compute top N winners from guesses by absolute difference to final_balance; earlier guess wins on tie
            $winners = [];
            if ($h->final_balance !== null) {
                $winners = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT g.user_id, u.display_name, g.guess_value,
                                ABS(g.guess_value - %f) AS diff, g.created_at
                         FROM `$guesses_table` g
                         INNER JOIN `$users_table` u ON u.ID = g.user_id
                         WHERE g.hunt_id = %d
                         ORDER BY diff ASC, g.created_at ASC, g.id ASC
                         LIMIT %d",
                        $h->final_balance, $h->id, $winners_count
                    )
                );
            }

            $out[] = [
                'hunt'    => $h,
                'winners' => $winners,
            ];
        }
        return $out;
    }
}
