<?php
if (!defined('ABSPATH')) exit;

class BHG_DB {

    public function __construct() {}

    /** Create or update all required tables (idempotent via dbDelta). */
    public function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        // --- Bonus hunts (admin expects winner fields) ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_bonus_hunts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            starting_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
            num_bonuses INT UNSIGNED NOT NULL DEFAULT 0,
            prizes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            affiliate_site_id BIGINT UNSIGNED NULL,
            final_balance DECIMAL(12,2) NULL,
            winner_user_id BIGINT UNSIGNED NULL,
            winner_diff DECIMAL(12,2) NULL,
            closed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY winner_user_idx (winner_user_id),
            KEY status_idx (status),
            KEY affiliate_site_idx (affiliate_site_id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Guesses ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_guesses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            guess_value DECIMAL(12,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY hunt_id_idx (hunt_id),
            KEY user_id_idx (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Ads ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_ads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message TEXT NOT NULL,
            link VARCHAR(255) NULL,
            placement VARCHAR(50) NOT NULL DEFAULT 'footer',
            visibility VARCHAR(50) NOT NULL DEFAULT 'all',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Tournaments ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_tournaments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(20) NOT NULL,
            period VARCHAR(20) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY type_period (type, period)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Tournament wins (used by tournaments admin UI) ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_tournament_wins (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tournament_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            wins INT UNSIGNED NOT NULL DEFAULT 0,
            last_win_date DATETIME NULL,
            PRIMARY KEY (id),
            KEY tournament_idx (tournament_id),
            KEY user_idx (user_id),
            KEY wins_idx (wins)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Translations ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_translations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(191) NOT NULL,
            `value` TEXT NULL,
            -- legacy aliases kept for back-compat (not required by current code)
            t_key VARCHAR(191) NULL,
            t_value TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY tkey (`key`)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Affiliate websites ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_affiliate_websites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            url VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            UNIQUE KEY slug_unique (slug)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Hunt winners (history) ---
        $sql = "CREATE TABLE {$wpdb->prefix}bhg_hunt_winners (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            diff_value DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY hunt_idx (hunt_id),
            KEY user_idx (user_id)
        ) $charset_collate;";
        dbDelta($sql);

        // Back-compat call, if other code expects it.
        if (method_exists(__CLASS__, 'add_missing_tournament_columns')) {
            self::add_missing_tournament_columns();
        }
    }

    /** One-time migrations / backfills. Safe to call on every load. */
    public static function migrate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Helpers
        $table_exists = function($table) use ($wpdb) {
            $full = $wpdb->prefix . $table;
            return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $full)) === $full;
        };
        $col_exists = function($table, $column) use ($wpdb) {
            $full = $wpdb->prefix . $table;
            $col  = esc_sql($column);
            return (bool) $wpdb->get_var("SHOW COLUMNS FROM `{$full}` LIKE '{$col}'");
        };
        $index_exists = function($table, $index_name) use ($wpdb) {
            $full = $wpdb->prefix . $table;
            $idx  = esc_sql($index_name);
            return (bool) $wpdb->get_var("SHOW INDEX FROM `{$full}` WHERE Key_name = '{$idx}'");
        };

        // Ensure guesses indexes
        if ($table_exists('bhg_guesses')) {
            $tbl = $wpdb->prefix . 'bhg_guesses';
            if (!$index_exists('bhg_guesses', 'hunt_id_idx')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD INDEX `hunt_id_idx` (`hunt_id`)");
            }
            if (!$index_exists('bhg_guesses', 'user_id_idx')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD INDEX `user_id_idx` (`user_id`)");
            }
        }

        // Tournaments migration
        if ($table_exists('bhg_tournaments')) {
            $tbl = $wpdb->prefix . 'bhg_tournaments';
            if (!$col_exists('bhg_tournaments', 'start_date')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `start_date` DATETIME NOT NULL AFTER `period`");
            }
            if (!$col_exists('bhg_tournaments', 'end_date')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `end_date` DATETIME NOT NULL AFTER `start_date`");
            }
            // Drop any legacy columns if they exist
            if ($col_exists('bhg_tournaments', 'from_date')) {
                $wpdb->query("ALTER TABLE `{$tbl}` DROP COLUMN `from_date`");
            }
            if ($col_exists('bhg_tournaments', 'to_date')) {
                $wpdb->query("ALTER TABLE `{$tbl}` DROP COLUMN `to_date`");
            }
            if ($col_exists('bhg_tournaments', 'title')) {
                $wpdb->query("ALTER TABLE `{$tbl}` DROP COLUMN `title`");
            }
            if ($col_exists('bhg_tournaments', 'period_key')) {
                $wpdb->query("ALTER TABLE `{$tbl}` DROP COLUMN `period_key`");
            }
            if (!$index_exists('bhg_tournaments', 'type_period')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD UNIQUE INDEX `type_period` (`type`, `period`)");
            }
        }

        // Backfill hunts winner columns + indexes
        if ($table_exists('bhg_bonus_hunts')) {
            $tbl = $wpdb->prefix . 'bhg_bonus_hunts';
            if (!$col_exists('bhg_bonus_hunts', 'final_balance')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `final_balance` DECIMAL(12,2) NULL AFTER `status`");
            }
            if (!$col_exists('bhg_bonus_hunts', 'winner_user_id')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `winner_user_id` BIGINT UNSIGNED NULL AFTER `final_balance`");
            }
            if (!$col_exists('bhg_bonus_hunts', 'winner_diff')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `winner_diff` DECIMAL(12,2) NULL AFTER `winner_user_id`");
            }
            if (!$col_exists('bhg_bonus_hunts', 'closed_at')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `closed_at` DATETIME NULL AFTER `winner_diff`");
            }
            if (!$index_exists('bhg_bonus_hunts', 'winner_user_idx')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD INDEX `winner_user_idx` (`winner_user_id`)");
            }
            if (!$index_exists('bhg_bonus_hunts', 'status_idx')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD INDEX `status_idx` (`status`)");
            }
if (!$col_exists('bhg_bonus_hunts', 'affiliate_site_id')) {
    $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `affiliate_site_id` BIGINT UNSIGNED NULL AFTER `status`");
}
if (!$index_exists('bhg_bonus_hunts', 'affiliate_site_idx')) {
    $wpdb->query("ALTER TABLE `{$tbl}` ADD INDEX `affiliate_site_idx` (`affiliate_site_id`)");
}
        }

        
        // Translations: ensure expected column names
        if ($table_exists('bhg_translations')) {
            $tbl = $wpdb->prefix . 'bhg_translations';
            if (!$col_exists('bhg_translations', 'key')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `key` VARCHAR(191) NOT NULL AFTER `id`");
            }
            if (!$col_exists('bhg_translations', 'value')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD COLUMN `value` TEXT NULL AFTER `key`");
            }
            if (!$index_exists('bhg_translations', 'tkey')) {
                $wpdb->query("ALTER TABLE `{$tbl}` ADD UNIQUE INDEX `tkey` (`key`)");
            }
        }
// Ensure tournament wins table exists
        if (!$table_exists('bhg_tournament_wins')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $sql = "CREATE TABLE {$wpdb->prefix}bhg_tournament_wins (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                tournament_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                wins INT UNSIGNED NOT NULL DEFAULT 0,
                last_win_date DATETIME NULL,
                PRIMARY KEY (id),
                KEY tournament_idx (tournament_id),
                KEY user_idx (user_id),
                KEY wins_idx (wins)
            ) $charset_collate;";
            dbDelta($sql);
        }
    }

    /** Back-compat shim: old code may call this during activation. */
    public static function add_missing_tournament_columns() {
        self::migrate();
    }
}
