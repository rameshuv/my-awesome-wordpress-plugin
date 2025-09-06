<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_DB {

	// Static wrapper to support legacy static calls.
	public static function migrate() {
		$db = new self();
		$db->create_tables();

		global $wpdb;
		$tours_table = $wpdb->prefix . 'bhg_tournaments';

		// Drop legacy "period" column and related index if they exist.
		if ( $db->column_exists( $tours_table, 'period' ) ) {
			// Remove unique index first if present.
			if ( $db->index_exists( $tours_table, 'type_period' ) ) {
				$wpdb->query( "ALTER TABLE `{$tours_table}` DROP INDEX type_period" );
			}

			$wpdb->query( "ALTER TABLE `{$tours_table}` DROP COLUMN period" );
		}
	}

	public function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$hunts_table   = $wpdb->prefix . 'bhg_bonus_hunts';
				$guesses_table = $wpdb->prefix . 'bhg_guesses';
				$tours_table   = $wpdb->prefix . 'bhg_tournaments';
				$tres_table    = $wpdb->prefix . 'bhg_tournament_results';
				$ads_table     = $wpdb->prefix . 'bhg_ads';
				$trans_table   = $wpdb->prefix . 'bhg_translations';
				$aff_table     = $wpdb->prefix . 'bhg_affiliates';

		$sql = [];

		// Bonus Hunts
		$sql[] = "CREATE TABLE {$hunts_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(190) NOT NULL,
			starting_balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			num_bonuses INT UNSIGNED NOT NULL DEFAULT 0,
			prizes TEXT NULL,
			affiliate_site_id BIGINT UNSIGNED NULL,
			winners_count INT UNSIGNED NOT NULL DEFAULT 3,
			final_balance DECIMAL(12,2) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'open',
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			closed_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) {$charset_collate};";

		// Guesses
		$sql[] = "CREATE TABLE {$guesses_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			hunt_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			guess DECIMAL(12,2) NOT NULL,
			created_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY hunt_id (hunt_id),
			KEY user_id (user_id)
		) {$charset_collate};";

				// Tournaments
				$sql[] = "CREATE TABLE {$tours_table} (
						id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
						title VARCHAR(190) NOT NULL,
						description TEXT NULL,
						type VARCHAR(20) NOT NULL,
						start_date DATE NULL,
						end_date DATE NULL,
						status VARCHAR(20) NOT NULL DEFAULT 'active',
						created_at DATETIME NULL,
						updated_at DATETIME NULL,
						PRIMARY KEY  (id),
						KEY type (type),
						KEY status (status)
				) {$charset_collate};";

				// Tournament Results
				$sql[] = "CREATE TABLE {$tres_table} (
						id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
						tournament_id BIGINT UNSIGNED NOT NULL,
						user_id BIGINT UNSIGNED NOT NULL,
						wins INT UNSIGNED NOT NULL DEFAULT 0,
						last_win_date DATETIME NULL,
						PRIMARY KEY  (id),
						KEY tournament_id (tournament_id),
						KEY user_id (user_id)
				) {$charset_collate};";

				// Ads
				$sql[] = "CREATE TABLE {$ads_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(190) NOT NULL,
			content TEXT NULL,
			link_url VARCHAR(255) NULL,
			placement VARCHAR(50) NOT NULL DEFAULT 'none',
			visible_to VARCHAR(30) NOT NULL DEFAULT 'all',
			target_pages TEXT NULL,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			KEY placement (placement),
			KEY visible_to (visible_to)
		) {$charset_collate};";

		// Translations
		$sql[] = "CREATE TABLE {$trans_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			tkey VARCHAR(190) NOT NULL,
			tvalue LONGTEXT NULL,
			locale VARCHAR(20) NOT NULL DEFAULT 'en_US',
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY tkey_locale (tkey, locale)
		) {$charset_collate};";

		// Affiliates
		$sql[] = "CREATE TABLE {$aff_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			url VARCHAR(255) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name_unique (name)
		) {$charset_collate};";

		foreach ($sql as $statement) {
			dbDelta($statement);
		}

		// Idempotent ensure for columns/indexes
		try {
			// Hunts: winners_count, affiliate_site_id
			$need = [
				'winners_count'    => "ALTER TABLE `{$hunts_table}` ADD COLUMN winners_count INT UNSIGNED NOT NULL DEFAULT 3",
				'affiliate_site_id'=> "ALTER TABLE `{$hunts_table}` ADD COLUMN affiliate_site_id BIGINT UNSIGNED NULL",
				'final_balance'    => "ALTER TABLE `{$hunts_table}` ADD COLUMN final_balance DECIMAL(12,2) NULL",
				'status'           => "ALTER TABLE `{$hunts_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'",
			];
			foreach ( $need as $c => $alter ) {
				if ( ! $this->column_exists( $hunts_table, $c ) ) {
					$wpdb->query( $alter );
				}
			}

			// Tournaments: make sure common columns exist
			$tneed = [
				'title'       => "ALTER TABLE `{$tours_table}` ADD COLUMN title VARCHAR(190) NOT NULL",
				'description' => "ALTER TABLE `{$tours_table}` ADD COLUMN description TEXT NULL",
				'type'        => "ALTER TABLE `{$tours_table}` ADD COLUMN type VARCHAR(20) NOT NULL",
				'start_date'  => "ALTER TABLE `{$tours_table}` ADD COLUMN start_date DATE NULL",
				'end_date'    => "ALTER TABLE `{$tours_table}` ADD COLUMN end_date DATE NULL",
				'status'      => "ALTER TABLE `{$tours_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
			];
						foreach ( $tneed as $c => $alter ) {
								if ( ! $this->column_exists( $tours_table, $c ) ) {
										$wpdb->query( $alter );
								}
						}

						// Tournament results columns
						$trrneed = [
								'tournament_id' => "ALTER TABLE `{$tres_table}` ADD COLUMN tournament_id BIGINT UNSIGNED NOT NULL",
								'user_id'       => "ALTER TABLE `{$tres_table}` ADD COLUMN user_id BIGINT UNSIGNED NOT NULL",
								'wins'          => "ALTER TABLE `{$tres_table}` ADD COLUMN wins INT UNSIGNED NOT NULL DEFAULT 0",
								'last_win_date' => "ALTER TABLE `{$tres_table}` ADD COLUMN last_win_date DATETIME NULL",
						];
						foreach ( $trrneed as $c => $alter ) {
								if ( ! $this->column_exists( $tres_table, $c ) ) {
										$wpdb->query( $alter );
								}
						}
						if ( ! $this->index_exists( $tres_table, 'tournament_id' ) ) {
								$wpdb->query( "ALTER TABLE `{$tres_table}` ADD KEY tournament_id (tournament_id)" );
						}
						if ( ! $this->index_exists( $tres_table, 'user_id' ) ) {
								$wpdb->query( "ALTER TABLE `{$tres_table}` ADD KEY user_id (user_id)" );
						}

						// Ads columns
						$aneed = [
								'title'        => "ALTER TABLE `{$ads_table}` ADD COLUMN title VARCHAR(190) NOT NULL",
								'content'      => "ALTER TABLE `{$ads_table}` ADD COLUMN content TEXT NULL",
				'link_url'     => "ALTER TABLE `{$ads_table}` ADD COLUMN link_url VARCHAR(255) NULL",
				'placement'    => "ALTER TABLE `{$ads_table}` ADD COLUMN placement VARCHAR(50) NOT NULL DEFAULT 'none'",
				'visible_to'   => "ALTER TABLE `{$ads_table}` ADD COLUMN visible_to VARCHAR(30) NOT NULL DEFAULT 'all'",
				'target_pages' => "ALTER TABLE `{$ads_table}` ADD COLUMN target_pages TEXT NULL",
				'active'       => "ALTER TABLE `{$ads_table}` ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1",
				'created_at'   => "ALTER TABLE `{$ads_table}` ADD COLUMN created_at DATETIME NULL",
				'updated_at'   => "ALTER TABLE `{$ads_table}` ADD COLUMN updated_at DATETIME NULL",
			];
			foreach ( $aneed as $c => $alter ) {
				if ( ! $this->column_exists( $ads_table, $c ) ) {
					$wpdb->query( $alter );
				}
			}

			// Translations columns
			$trneed = [
				'tkey'       => "ALTER TABLE `{$trans_table}` ADD COLUMN tkey VARCHAR(190) NOT NULL",
				'tvalue'     => "ALTER TABLE `{$trans_table}` ADD COLUMN tvalue LONGTEXT NULL",
				'locale'     => "ALTER TABLE `{$trans_table}` ADD COLUMN locale VARCHAR(20) NOT NULL DEFAULT 'en_US'",
				'created_at' => "ALTER TABLE `{$trans_table}` ADD COLUMN created_at DATETIME NULL",
				'updated_at' => "ALTER TABLE `{$trans_table}` ADD COLUMN updated_at DATETIME NULL",
			];
			foreach ( $trneed as $c => $alter ) {
				if ( ! $this->column_exists( $trans_table, $c ) ) {
					$wpdb->query( $alter );
				}
			}
			// Ensure unique index
			if ( ! $this->index_exists( $trans_table, 'tkey_locale' ) ) {
				$wpdb->query( "ALTER TABLE `{$trans_table}` ADD UNIQUE KEY tkey_locale (tkey, locale)" );
			}

			// Affiliates columns / unique index
			$afneed = [
				'name'       => "ALTER TABLE `{$aff_table}` ADD COLUMN name VARCHAR(190) NOT NULL",
				'url'        => "ALTER TABLE `{$aff_table}` ADD COLUMN url VARCHAR(255) NULL",
				'status'     => "ALTER TABLE `{$aff_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
				'created_at' => "ALTER TABLE `{$aff_table}` ADD COLUMN created_at DATETIME NULL",
				'updated_at' => "ALTER TABLE `{$aff_table}` ADD COLUMN updated_at DATETIME NULL",
			];
			foreach ( $afneed as $c => $alter ) {
				if ( ! $this->column_exists( $aff_table, $c ) ) {
					$wpdb->query( $alter );
				}
			}
			if ( ! $this->index_exists( $aff_table, 'name_unique' ) ) {
				$wpdb->query( "ALTER TABLE `{$aff_table}` ADD UNIQUE KEY name_unique (name)" );
			}

		} catch (Throwable $e) {
			if (function_exists('error_log')) error_log('[BHG] Schema ensure error: ' . $e->getMessage());
		}
	}

	/**
	 * Check if a column exists, falling back when information_schema is not accessible.
	 *
	 * @param string $table  Table name.
	 * @param string $column Column to check.
	 * @return bool
	 */
	private function column_exists( $table, $column ) {
		global $wpdb;

		$table  = esc_sql( $table );
		$column = esc_sql( $column );

		$wpdb->last_error = '';
		$sql    = $wpdb->prepare(
			"SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME=%s",
			DB_NAME,
			$table,
			$column
		);
		$exists = $wpdb->get_var( $sql );

		if ( $wpdb->last_error ) {
			$wpdb->last_error = '';
			$exists           = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` LIKE %s", $column ) );
		}

		return ! empty( $exists );
	}

	/**
	 * Check if an index exists, falling back when information_schema is not accessible.
	 *
	 * @param string $table Table name.
	 * @param string $index Index to check.
	 * @return bool
	 */
	private function index_exists( $table, $index ) {
		global $wpdb;

		$table = esc_sql( $table );
		$index = esc_sql( $index );

		$wpdb->last_error = '';
		$sql    = $wpdb->prepare(
			"SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND INDEX_NAME=%s",
			DB_NAME,
			$table,
			$index
		);
		$exists = $wpdb->get_var( $sql );

		if ( $wpdb->last_error ) {
			$wpdb->last_error = '';
			$exists           = $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM `{$table}` WHERE Key_name=%s", $index ) );
		}

		return ! empty( $exists );
	}
}
