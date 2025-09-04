<?php
if (!defined('ABSPATH')) exit;

class BHG_Bonus_Hunts {
    public static function get_latest_hunts_with_winners($limit = 3) {
        global $wpdb;
        $hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        $limit = max(1, (int)$limit);

        $hunts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, starting_balance, final_balance, winners_count, closed_at
                 FROM `$hunts_table`
                 WHERE status = %s AND final_balance IS NOT NULL AND closed_at IS NOT NULL
                 ORDER BY closed_at DESC
                 LIMIT %d",
                'closed', $limit
            )
        );
        $out = [];
        foreach ((array)$hunts as $h) {
            $winners_count = max(1, (int)$h->winners_count);
            $winners = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT g.user_id, u.display_name, g.guess,
                            ABS(g.guess - %f) AS diff
                     FROM `$guesses_table` g
                     LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id
                     WHERE g.hunt_id = %d
                     ORDER BY diff ASC, g.id ASC
                     LIMIT %d",
                    $h->final_balance, $h->id, $winners_count
                )
            );
            $out[] = [ 'hunt' => $h, 'winners' => $winners ];
        }
        return $out;
    }

    public static function get_hunt($hunt_id) {
        global $wpdb;
        $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM `$hunts_table` WHERE id=%d", (int)$hunt_id));
    }

    public static function get_hunt_guesses_ranked($hunt_id) {
        global $wpdb;
        $hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
        $guesses_table = $wpdb->prefix . 'bhg_guesses';
        $hunt = self::get_hunt($hunt_id);
        if (!$hunt) return [];

        if ($hunt->final_balance !== null) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT g.*, u.display_name, ABS(g.guess - %f) AS diff
                     FROM `$guesses_table` g
                     LEFT JOIN `$wpdb->users` u ON u.ID = g.user_id
                     WHERE g.hunt_id = %d
                     ORDER BY diff ASC, g.id ASC",
                    $hunt->final_balance, $hunt_id
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
