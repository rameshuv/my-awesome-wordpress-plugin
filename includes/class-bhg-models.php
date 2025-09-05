<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Models {
    public function __construct() {
        add_action('admin_post_bhg_submit_guess', array($this, 'submit_guess'));
        add_action('admin_post_nopriv_bhg_submit_guess', array($this, 'reject_guest'));
    }

    public function reject_guest() {
        wp_safe_redirect( wp_login_url() );
        exit;
    }

    public function submit_guess() {
        if (!is_user_logged_in()) { $this->reject_guest(); }
        check_admin_referer('bhg_submit_guess', 'bhg_nonce');

        $user_id = get_current_user_id();
        $hunt_id = isset($_POST['hunt_id']) ? (int) $_POST['hunt_id'] : 0;
        $guess   = isset($_POST['guess']) ? (float) $_POST['guess'] : 0;

        if ($hunt_id <= 0) {
            wp_die( esc_html__('Invalid hunt.', 'bonus-hunt-guesser') );
        }

        global $wpdb;
        $hunts_tbl   = $wpdb->prefix . 'bhg_bonus_hunts';
        $guesses_tbl = $wpdb->prefix . 'bhg_guesses';

        // Ensure hunt is open
        $status = $wpdb->get_var( $wpdb->prepare("SELECT status FROM {$hunts_tbl} WHERE id=%d", $hunt_id) );
        if ($status !== 'open') {
            wp_die( esc_html__('This hunt is closed.', 'bonus-hunt-guesser') );
        }

        // Bounds from Settings with safe defaults
        $settings = get_option('bhg_plugin_settings', array());
        $min = isset($settings['min_guess_amount']) ? (float) $settings['min_guess_amount'] : 0;
        $max = isset($settings['max_guess_amount']) ? (float) $settings['max_guess_amount'] : 100000;
        if ($min < 0) $min = 0;
        if ($max < $min) $max = $min;

        // Enforce server-side
        if (!is_numeric($guess)) $guess = 0;
        if ($guess < $min) $guess = $min;
        if ($guess > $max) $guess = $max;

        // Upsert (alter guess if already exists)
        $existing_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$guesses_tbl} WHERE user_id=%d AND hunt_id=%d", $user_id, $hunt_id
        ));

        $now = current_time('mysql');
        if ($existing_id) {
            $wpdb->update(
                $guesses_tbl,
                array('guess' => $guess, 'updated_at' => $now),
                array('id' => $existing_id)
            );
        } else {
            $wpdb->insert(
                $guesses_tbl,
                array(
                    'hunt_id'    => $hunt_id,
                    'user_id'    => $user_id,
                    'guess'      => $guess,
                    'created_at' => $now,
                    'updated_at' => $now,
                )
            );
        }

        // Redirect back
        $redirect = isset($_POST['_wp_http_referer']) ? esc_url_raw(wp_unslash($_POST['_wp_http_referer'])) : home_url('/');
        wp_safe_redirect($redirect);
        exit;
    }

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

new BHG_Models();
