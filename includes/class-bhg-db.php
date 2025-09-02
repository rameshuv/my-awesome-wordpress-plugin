<?php
if (!defined('ABSPATH')) exit;

class BHG_DB {

    public function install(){
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $tables = [];

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_bonus_hunts (
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
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_guesses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            guess_value DECIMAL(12,2) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL DEFAULT NULL,
            UNIQUE KEY hunt_user (hunt_id, user_id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id),
            PRIMARY KEY (id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_tournaments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            period VARCHAR(20) NOT NULL,
            period_key VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY period_unique (period, period_key)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_tournament_results (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY tournament_user (tournament_id, user_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_translations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            t_key VARCHAR(191) NOT NULL,
            t_value TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tkey (t_key)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_affiliate_websites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            url VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug_unique (slug)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_hunt_winners (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            position INT UNSIGNED NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $tables[] = "CREATE TABLE {$wpdb->prefix}bhg_ads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            placement VARCHAR(50) NOT NULL DEFAULT 'footer',
            visibility VARCHAR(50) NOT NULL DEFAULT 'all',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        foreach ($tables as $sql){
            dbDelta($sql);
            if (function_exists('bhg_log')) {
                bhg_log('Table ensured: ' . preg_replace('/\s+/', ' ', $sql));
            }
        }
    }

    public static function migrate(){
        global $wpdb;
        // Add 'active' column to ads if missing
        $ads_table = $wpdb->prefix . 'bhg_ads';
        $col = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME='active'",
                DB_NAME,
                $ads_table
            )
        );
        if (!$col){
            $wpdb->query("ALTER TABLE {$ads_table} ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
        }

        // Add 'status' column to tournaments if missing
        $tour_table = $wpdb->prefix . 'bhg_tournaments';
        $col2 = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME=%s',
                DB_NAME,
                $tour_table,
                'status'
            )
        );
        if (!$col2){
            $wpdb->query("ALTER TABLE {$tour_table} ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");
        }
    } // ✅ properly close migrate()

    public function maybe_upgrade(){
        global $wpdb;
        $hunts = $wpdb->prefix . 'bhg_bonus_hunts';
        $results = $wpdb->prefix . 'bhg_results';
        $ua = $wpdb->prefix . 'bhg_user_affiliates';
        // create results table if missing
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$results} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            guess_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            difference DECIMAL(12,2) NOT NULL DEFAULT 0,
            position INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // create user affiliates mapping
        $ua_sql = "CREATE TABLE IF NOT EXISTS {$ua} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            affiliate_site_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_aff_site (user_id, affiliate_site_id)
        ) {$charset};";
        dbDelta($ua_sql);
    }

    public function add_user_affiliate($user_id, $affiliate_site_id){
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_user_affiliates';
        if (!$user_id || !$affiliate_site_id) return false;
        $exists = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . $table . ' WHERE user_id=%d AND affiliate_site_id=%d',
            (int)$user_id, (int)$affiliate_site_id
        ));
        if ($exists) return true;
        $wpdb->insert($table, ['user_id'=>(int)$user_id,'affiliate_site_id'=>(int)$affiliate_site_id]);
        return (bool)$wpdb->insert_id;
    }

    public function remove_user_affiliate($user_id, $affiliate_site_id){
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_user_affiliates';
        return (bool)$wpdb->delete(
            $table,
            ['user_id'=>(int)$user_id, 'affiliate_site_id'=>(int)$affiliate_site_id],
            ['%d','%d']
        );
    }

    public function get_user_affiliates($user_id){
        global $wpdb;
        $table = $wpdb->prefix . 'bhg_user_affiliates';
        return $wpdb->get_col($wpdb->prepare(
            'SELECT affiliate_site_id FROM ' . $table . ' WHERE user_id=%d',
            (int)$user_id
        ));
    }
}
?>
