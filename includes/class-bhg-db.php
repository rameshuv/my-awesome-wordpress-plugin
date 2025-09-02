<?php
if (!defined('ABSPATH')) exit;

class BHG_DB {

    public function __construct() {
        // Database initialization if needed
    }
    
    public function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        
        // Bonus hunts table
        $table_name = $wpdb->prefix . 'bhg_bonus_hunts';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            starting_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            num_bonuses INT UNSIGNED NOT NULL DEFAULT 0,
            prizes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            affiliate_site_id BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            final_balance DECIMAL(12,2) NULL,
            winner_user_id BIGINT UNSIGNED NULL,
            winner_diff DECIMAL(12,2) NULL,
            closed_at DATETIME NULL,
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        // Guesses table
        $table_name = $wpdb->prefix . 'bhg_guesses';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            guess_amount DECIMAL(12,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            UNIQUE KEY hunt_user (hunt_id, user_id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id),
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        // Tournaments table
        $table_name = $wpdb->prefix . 'bhg_tournaments';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            period VARCHAR(20) NOT NULL,
            period_key VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY period_unique (period, period_key)
        ) $charset_collate";
        dbDelta($sql);

        // Tournament results table
        $table_name = $wpdb->prefix . 'bhg_tournament_results';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY tournament_user (tournament_id, user_id)
        ) $charset_collate";
        dbDelta($sql);

        // Translations table
        $table_name = $wpdb->prefix . 'bhg_translations';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            t_key VARCHAR(191) NOT NULL,
            t_value TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tkey (t_key)
        ) $charset_collate";
        dbDelta($sql);

        // Affiliate websites table
        $table_name = $wpdb->prefix . 'bhg_affiliate_websites';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            url VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug_unique (slug)
        ) $charset_collate";
        dbDelta($sql);

        // Hunt winners table
        $table_name = $wpdb->prefix . 'bhg_hunt_winners';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            position INT UNSIGNED NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id)
        ) $charset_collate";
        dbDelta($sql);

        // Ads table
        $table_name = $wpdb->prefix . 'bhg_ads';
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            placement VARCHAR(50) NOT NULL DEFAULT 'footer',
            visibility VARCHAR(50) NOT NULL DEFAULT 'all',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate";
        dbDelta($sql);

        if (function_exists('bhg_log')) {
            bhg_log('All tables created successfully');
        }
    }

    public static function migrate() {
        global $wpdb;
        
        // Add 'active' column to ads if missing
        $ads_table = $wpdb->prefix . 'bhg_ads';
        $col = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $ads_table,
            'active'
        ));
        
        if (!$col) {
            $wpdb->query("ALTER TABLE $ads_table ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
        }

        // Add 'status' column to tournaments if missing
        $tour_table = $wpdb->prefix . 'bhg_tournaments';
        $col2 = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $tour_table,
            'status'
        ));
        
        if (!$col2) {
            $wpdb->query("ALTER TABLE $tour_table ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }

        // Migrate guess_value to guess_amount if needed
        $guess_table = $wpdb->prefix . 'bhg_guesses';
        $col3 = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $guess_table,
            'guess_value'
        ));
        
        if ($col3) {
            $wpdb->query("ALTER TABLE $guess_table CHANGE guess_value guess_amount DECIMAL(12,2) NOT NULL");
        }
    }
    
    public function get_active_bonus_hunt() {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE status = %s ORDER BY created_at DESC LIMIT 1",
            'open'
        ));
    }
    
    public function get_bonus_hunt($hunt_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts WHERE id = %d",
            $hunt_id
        ));
    }
    
    public function get_all_bonus_hunts($status = null, $limit = null, $offset = null) {
        global $wpdb;
        
        $query = "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts";
        $params = array();
        
        if ($status) {
            $query .= " WHERE status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $query .= " LIMIT %d";
            $params[] = $limit;
            if ($offset) {
                $query .= " OFFSET %d";
                $params[] = $offset;
            }
        }
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
    }
    
    public function create_bonus_hunt($data) {
        global $wpdb;
        
        $defaults = array(
            'title' => '',
            'starting_balance' => 0,
            'num_bonuses' => 0,
            'prizes' => '',
            'status' => 'open',
            'affiliate_site_id' => null,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            "{$wpdb->prefix}bhg_bonus_hunts",
            $data,
            array('%s', '%f', '%d', '%s', '%s', '%d', '%s')
        );
    }
    
    public function update_bonus_hunt($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            "{$wpdb->prefix}bhg_bonus_hunts",
            $data,
            array('id' => $id),
            array('%s', '%f', '%d', '%s', '%s', '%d', '%f', '%d', '%f', '%s'),
            array('%d')
        );
    }
    
    public function close_bonus_hunt($hunt_id, $final_balance, $winner_user_id = null, $winner_diff = null) {
        global $wpdb;
        
        return $wpdb->update(
            "{$wpdb->prefix}bhg_bonus_hunts",
            array(
                'status' => 'closed',
                'final_balance' => $final_balance,
                'winner_user_id' => $winner_user_id,
                'winner_diff' => $winner_diff,
                'closed_at' => current_time('mysql')
            ),
            array('id' => $hunt_id),
            array('%s', '%f', '%d', '%f', '%s'),
            array('%d')
        );
    }
    
    public function get_user_guess($hunt_id, $user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_guesses WHERE hunt_id = %d AND user_id = %d",
            $hunt_id, $user_id
        ));
    }
    
    public function save_guess($hunt_id, $user_id, $guess_amount) {
        global $wpdb;
        
        $existing = $this->get_user_guess($hunt_id, $user_id);
        
        if ($existing) {
            return $wpdb->update(
                "{$wpdb->prefix}bhg_guesses",
                array('guess_amount' => $guess_amount, 'updated_at' => current_time('mysql')),
                array('id' => $existing->id),
                array('%f', '%s'),
                array('%d')
            );
        } else {
            return $wpdb->insert(
                "{$wpdb->prefix}bhg_guesses",
                array(
                    'hunt_id' => $hunt_id,
                    'user_id' => $user_id,
                    'guess_amount' => $guess_amount,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%f', '%s')
            );
        }
    }
    
    public function get_guesses($hunt_id, $limit = null, $offset = null) {
        global $wpdb;
        
        $query = "SELECT g.*, u.user_login, u.display_name, 
                 (SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = u.ID AND meta_key = 'bhg_affiliate_status' AND meta_value = '1') as is_affiliate
                 FROM {$wpdb->prefix}bhg_guesses g
                 JOIN {$wpdb->users} u ON g.user_id = u.ID
                 WHERE g.hunt_id = %d
                 ORDER BY g.guess_amount ASC";
        
        $params = array($hunt_id);
        
        if ($limit) {
            $query .= " LIMIT %d";
            $params[] = $limit;
            if ($offset) {
                $query .= " OFFSET %d";
                $params[] = $offset;
            }
        }
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    public function get_guess_count($hunt_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bhg_guesses WHERE hunt_id = %d",
            $hunt_id
        ));
    }
    
    public function get_closest_guesses($hunt_id, $final_balance, $limit = 3) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT g.*, u.user_login, u.display_name, ABS(g.guess_amount - %f) as difference
             FROM {$wpdb->prefix}bhg_guesses g
             JOIN {$wpdb->users} u ON g.user_id = u.ID
             WHERE g.hunt_id = %d
             ORDER BY difference ASC
             LIMIT %d",
            $final_balance, $hunt_id, $limit
        ));
    }
    
    public function add_hunt_winner($hunt_id, $user_id, $position) {
        global $wpdb;
        
        return $wpdb->insert(
            "{$wpdb->prefix}bhg_hunt_winners",
            array(
                'hunt_id' => $hunt_id,
                'user_id' => $user_id,
                'position' => $position,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s')
        );
    }
    
    public function get_hunt_winners($hunt_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT w.*, u.user_login, u.display_name
             FROM {$wpdb->prefix}bhg_hunt_winners w
             JOIN {$wpdb->users} u ON w.user_id = u.ID
             WHERE w.hunt_id = %d
             ORDER BY w.position ASC",
            $hunt_id
        ));
    }
    
    public function get_affiliate_websites() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bhg_affiliate_websites ORDER BY name ASC"
        );
    }
    
    public function get_affiliate_website($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_affiliate_websites WHERE id = %d",
            $id
        ));
    }
    
    public function add_affiliate_website($data) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'slug' => '',
            'url' => '',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            "{$wpdb->prefix}bhg_affiliate_websites",
            $data,
            array('%s', '%s', '%s', '%s')
        );
    }
    
    public function update_affiliate_website($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            "{$wpdb->prefix}bhg_affiliate_websites",
            $data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }
    
    public function delete_affiliate_website($id) {
        global $wpdb;
        
        return $wpdb->delete(
            "{$wpdb->prefix}bhg_affiliate_websites",
            array('id' => $id),
            array('%d')
        );
    }
    
    public function get_active_tournaments() {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdp->prefix}bhg_tournaments WHERE status = %s ORDER BY created_at DESC",
            'active'
        ));
    }
    
    public function get_tournament($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_tournaments WHERE id = %d",
            $id
        ));
    }
    
    public function get_tournament_by_period($period, $period_key) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_tournaments WHERE period = %s AND period_key = %s",
            $period, $period_key
        ));
    }
    
    public function create_tournament($data) {
        global $wpdb;
        
        $defaults = array(
            'title' => '',
            'period' => 'monthly',
            'period_key' => '',
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            "{$wpdb->prefix}bhg_tournaments",
            $data,
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    public function update_tournament_result($tournament_id, $user_id, $wins) {
        global $wpdb;
        
        $existing = $this->get_tournament_result($tournament_id, $user_id);
        
        if ($existing) {
            return $wpdb->update(
                "{$wpdb->prefix}bhg_tournament_results",
                array('wins' => $wins),
                array('id' => $existing->id),
                array('%d'),
                array('%d')
            );
        } else {
            return $wpdb->insert(
                "{$wpdb->prefix}bhg_tournament_results",
                array(
                    'tournament_id' => $tournament_id,
                    'user_id' => $user_id,
                    'wins' => $wins
                ),
                array('%d', '%d', '%d')
            );
        }
    }
    
    public function get_tournament_result($tournament_id, $user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}bhg_tournament_results WHERE tournament_id = %d AND user_id = %d",
            $tournament_id, $user_id
        ));
    }
    
    public function get_tournament_leaderboard($tournament_id, $limit = null) {
        global $wpdb;
        
        $query = "SELECT tr.*, u.user_login, u.display_name
                 FROM {$wpdb->prefix}bhg_tournament_results tr
                 JOIN {$wpdb->users} u ON tr.user_id = u.ID
                 WHERE tr.tournament_id = %d
                 ORDER BY tr.wins DESC";
        
        $params = array($tournament_id);
        
        if ($limit) {
            $query .= " LIMIT %d";
            $params[] = $limit;
        }
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    public function get_translation($key) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT t_value FROM {$wpdb->prefix}bhg_translations WHERE t_key = %s",
            $key
        ));
    }
    
    public function set_translation($key, $value) {
        global $wpdb;
        
        $existing = $this->get_translation($key);
        
        if ($existing !== null) {
            return $wpdb->update(
                "{$wpdb->prefix}bhg_translations",
                array('t_value' => $value),
                array('t_key' => $key),
                array('%s'),
                array('%s')
            );
        } else {
            return $wpdb->insert(
                "{$wpdb->prefix}bhg_translations",
                array(
                    't_key' => $key,
                    't_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }
    
    public function get_all_translations() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}bhg_translations ORDER BY t_key ASC"
        );
    }
    
    public function get_active_ads($placement = null, $visibility = 'all', $user_affiliate_status = false) {
        global $wpdb;
        
        $query = "SELECT * FROM {$wpdb->prefix}bhg_ads WHERE active = 1";
        $params = array();
        
        if ($placement) {
            $query .= " AND placement = %s";
            $params[] = $placement;
        }
        
        if ($visibility !== 'all') {
            if ($visibility === 'affiliate' && $user_affiliate_status) {
                $query .= " AND visibility IN ('all', 'affiliate')";
            } elseif ($visibility === 'non_affiliate' && !$user_affiliate_status) {
                $query .= " AND visibility IN ('all', 'non_affiliate')";
            } else {
                $query .= " AND visibility = %s";
                $params[] = $visibility;
            }
        }
        
        $query .= " ORDER BY created_at DESC";
        
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        
        return $wpdb->get_results($query);
    }
    
    public function add_ad($data) {
        global $wpdb;
        
        $defaults = array(
            'message' => '',
            'link' => '',
            'placement' => 'footer',
            'visibility' => 'all',
            'active' => 1,
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        return $wpdb->insert(
            "{$wpdb->prefix}bhg_ads",
            $data,
            array('%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    public function update_ad($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            "{$wpdb->prefix}bhg_ads",
            $data,
            array('id' => $id),
            array('%s', '%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );
    }
    
    public function delete_ad($id) {
        global $wpdb;
        
        return $wpdb->delete(
            "{$wpdb->prefix}bhg_ads",
            array('id' => $id),
            array('%d')
        );
    }
}