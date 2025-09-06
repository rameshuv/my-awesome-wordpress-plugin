<?php
// phpcs:ignoreFile -- Legacy file requires refactoring for WordPress coding standards.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class BHG_DB {

	// Static wrapper to support legacy static calls.
	public static function migrate() {
		$db = new self();
		$db->create_tables();

		global $wpdb;
		$tours_table    = $wpdb->prefix . 'bhg_tournaments';
		$allowed_tables = array( $wpdb->prefix . 'bhg_tournaments' );
		if ( ! in_array( $tours_table, $allowed_tables, true ) ) {
			wp_die( esc_html__( 'Invalid table.', 'bonus-hunt-guesser' ) );
		}

		// Drop legacy "period" column and related index if they exist.
		if ( $db->column_exists( $tours_table, 'period' ) ) {
			// Remove unique index first if present.
			if ( $db->index_exists( $tours_table, 'type_period' ) ) {
				dbDelta( sprintf( 'ALTER TABLE `%s` DROP INDEX type_period', $tours_table ) );
			}

			dbDelta( sprintf( 'ALTER TABLE `%s` DROP COLUMN period', $tours_table ) );
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

		$sql = array();

		// Bonus Hunts
		$sql[] = "CREATE TABLE `{$hunts_table}` (
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
		$sql[] = "CREATE TABLE `{$guesses_table}` (
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
				$sql[] = "CREATE TABLE `{$tours_table}` (
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
				$sql[] = "CREATE TABLE `{$tres_table}` (
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
				$sql[] = "CREATE TABLE `{$ads_table}` (
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
		$sql[] = "CREATE TABLE `{$trans_table}` (
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
		$sql[] = "CREATE TABLE `{$aff_table}` (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			url VARCHAR(255) NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NULL,
			updated_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY name_unique (name)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		// Idempotent ensure for columns/indexes
		try {
			// Hunts: winners_count, affiliate_site_id
						$need = array(
							'winners_count'     => "ALTER TABLE `{$hunts_table}` ADD COLUMN winners_count INT UNSIGNED NOT NULL DEFAULT 3",
							'affiliate_site_id' => "ALTER TABLE `{$hunts_table}` ADD COLUMN affiliate_site_id BIGINT UNSIGNED NULL",
							'final_balance'     => "ALTER TABLE `{$hunts_table}` ADD COLUMN final_balance DECIMAL(12,2) NULL",
							'status'            => "ALTER TABLE `{$hunts_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'",
						);
						foreach ( $need as $c => $alter ) {
							if ( ! $this->column_exists( $hunts_table, $c ) ) {
																			dbDelta( $alter );
							}
						}

						// Tournaments: make sure common columns exist
						$tneed = array(
							'title'       => "ALTER TABLE `{$tours_table}` ADD COLUMN title VARCHAR(190) NOT NULL",
							'description' => "ALTER TABLE `{$tours_table}` ADD COLUMN description TEXT NULL",
							'type'        => "ALTER TABLE `{$tours_table}` ADD COLUMN type VARCHAR(20) NOT NULL",
							'start_date'  => "ALTER TABLE `{$tours_table}` ADD COLUMN start_date DATE NULL",
							'end_date'    => "ALTER TABLE `{$tours_table}` ADD COLUMN end_date DATE NULL",
							'status'      => "ALTER TABLE `{$tours_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
						);
						foreach ( $tneed as $c => $alter ) {
							if ( ! $this->column_exists( $tours_table, $c ) ) {
									dbDelta( $alter );
							}
						}

						// Tournament results columns
												$trrneed = array(
													'tournament_id' => "ALTER TABLE `{$tres_table}` ADD COLUMN tournament_id BIGINT UNSIGNED NOT NULL",
													'user_id' => "ALTER TABLE `{$tres_table}` ADD COLUMN user_id BIGINT UNSIGNED NOT NULL",
													'wins' => "ALTER TABLE `{$tres_table}` ADD COLUMN wins INT UNSIGNED NOT NULL DEFAULT 0",
													'last_win_date' => "ALTER TABLE `{$tres_table}` ADD COLUMN last_win_date DATETIME NULL",
												);
												foreach ( $trrneed as $c => $alter ) {
													if ( ! $this->column_exists( $tres_table, $c ) ) {
															dbDelta( $alter );
													}
												}
												if ( ! $this->index_exists( $tres_table, 'tournament_id' ) ) {
														dbDelta( sprintf( 'ALTER TABLE `%s` ADD KEY tournament_id (tournament_id)', $tres_table ) );
												}
												if ( ! $this->index_exists( $tres_table, 'user_id' ) ) {
														dbDelta( sprintf( 'ALTER TABLE `%s` ADD KEY user_id (user_id)', $tres_table ) );
												}

												// Ads columns
												$aneed = array(
													'title'    => "ALTER TABLE `{$ads_table}` ADD COLUMN title VARCHAR(190) NOT NULL",
													'content'  => "ALTER TABLE `{$ads_table}` ADD COLUMN content TEXT NULL",
													'link_url' => "ALTER TABLE `{$ads_table}` ADD COLUMN link_url VARCHAR(255) NULL",
													'placement' => "ALTER TABLE `{$ads_table}` ADD COLUMN placement VARCHAR(50) NOT NULL DEFAULT 'none'",
													'visible_to' => "ALTER TABLE `{$ads_table}` ADD COLUMN visible_to VARCHAR(30) NOT NULL DEFAULT 'all'",
													'target_pages' => "ALTER TABLE `{$ads_table}` ADD COLUMN target_pages TEXT NULL",
													'active'   => "ALTER TABLE `{$ads_table}` ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1",
													'created_at' => "ALTER TABLE `{$ads_table}` ADD COLUMN created_at DATETIME NULL",
													'updated_at' => "ALTER TABLE `{$ads_table}` ADD COLUMN updated_at DATETIME NULL",
												);
												foreach ( $aneed as $c => $alter ) {
													if ( ! $this->column_exists( $ads_table, $c ) ) {
																			dbDelta( $alter );
													}
												}

												// Translations columns
												$trneed = array(
													'tkey' => "ALTER TABLE `{$trans_table}` ADD COLUMN tkey VARCHAR(190) NOT NULL",
													'tvalue' => "ALTER TABLE `{$trans_table}` ADD COLUMN tvalue LONGTEXT NULL",
													'locale' => "ALTER TABLE `{$trans_table}` ADD COLUMN locale VARCHAR(20) NOT NULL DEFAULT 'en_US'",
													'created_at' => "ALTER TABLE `{$trans_table}` ADD COLUMN created_at DATETIME NULL",
													'updated_at' => "ALTER TABLE `{$trans_table}` ADD COLUMN updated_at DATETIME NULL",
												);
												foreach ( $trneed as $c => $alter ) {
													if ( ! $this->column_exists( $trans_table, $c ) ) {
																			dbDelta( $alter );
													}
												}
												// Ensure unique index
												if ( ! $this->index_exists( $trans_table, 'tkey_locale' ) ) {
																	dbDelta( sprintf( 'ALTER TABLE `%s` ADD UNIQUE KEY tkey_locale (tkey, locale)', $trans_table ) );
												}

												// Affiliates columns / unique index
												$afneed = array(
													'name' => "ALTER TABLE `{$aff_table}` ADD COLUMN name VARCHAR(190) NOT NULL",
													'url'  => "ALTER TABLE `{$aff_table}` ADD COLUMN url VARCHAR(255) NULL",
													'status' => "ALTER TABLE `{$aff_table}` ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
													'created_at' => "ALTER TABLE `{$aff_table}` ADD COLUMN created_at DATETIME NULL",
													'updated_at' => "ALTER TABLE `{$aff_table}` ADD COLUMN updated_at DATETIME NULL",
												);
												foreach ( $afneed as $c => $alter ) {
													if ( ! $this->column_exists( $aff_table, $c ) ) {
																			dbDelta( $alter );
													}
												}
												if ( ! $this->index_exists( $aff_table, 'name_unique' ) ) {
																	dbDelta( sprintf( 'ALTER TABLE `%s` ADD UNIQUE KEY name_unique (name)', $aff_table ) );
												}
		} catch ( Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				bhg_log( 'Schema ensure error: ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Get list of allowed plugin tables.
	 *
	 * @return array
	 */
        private function get_allowed_tables() {
                global $wpdb;

		return array(
			$wpdb->prefix . 'bhg_bonus_hunts',
			$wpdb->prefix . 'bhg_guesses',
			$wpdb->prefix . 'bhg_tournaments',
			$wpdb->prefix . 'bhg_tournament_results',
			$wpdb->prefix . 'bhg_ads',
			$wpdb->prefix . 'bhg_translations',
                        $wpdb->prefix . 'bhg_affiliates',
                );
        }

       /**
        * Retrieve all bonus hunts.
        *
        * @return array
        */
       public function get_all_bonus_hunts() {
               global $wpdb;

               $table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
               if ( ! in_array( $table, $this->get_allowed_tables(), true ) ) {
                       return array();
               }

               $sql = "SELECT * FROM `{$table}` WHERE 1 = %d ORDER BY created_at DESC";

               return $wpdb->get_results( $wpdb->prepare( $sql, 1 ), ARRAY_A );
       }

       /**
        * Create a new bonus hunt.
        *
        * @param array $data Bonus hunt data.
        * @return int|false
        */
       public function create_bonus_hunt( $data ) {
               global $wpdb;

               $table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
               if ( ! in_array( $table, $this->get_allowed_tables(), true ) ) {
                       return false;
               }

               $defaults = array(
                       'title'             => '',
                       'starting_balance'  => 0,
                       'num_bonuses'       => 0,
                       'prizes'            => '',
                       'status'            => 'open',
                       'affiliate_site_id' => 0,
                       'created_by'        => 0,
                       'created_at'        => current_time( 'mysql' ),
               );

               $data = wp_parse_args( $data, $defaults );

               $inserted = $wpdb->insert(
                       $table,
                       $data,
                       array(
                               '%s',
                               '%f',
                               '%d',
                               '%s',
                               '%s',
                               '%d',
                               '%d',
                               '%s',
                       )
               );

               if ( ! $inserted ) {
                       return false;
               }

               return $wpdb->insert_id;
       }

       /**
        * Update an existing bonus hunt.
        *
        * @param int   $id   Hunt ID.
        * @param array $data Fields to update.
        * @return int|false
        */
       public function update_bonus_hunt( $id, $data ) {
               global $wpdb;

               $table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
               if ( ! in_array( $table, $this->get_allowed_tables(), true ) ) {
                       return false;
               }

               $formats = array(
                       'title'             => '%s',
                       'starting_balance'  => '%f',
                       'num_bonuses'       => '%d',
                       'prizes'            => '%s',
                       'status'            => '%s',
                       'final_balance'     => '%f',
                       'affiliate_site_id' => '%d',
                       'updated_at'        => '%s',
               );

               $data['updated_at'] = current_time( 'mysql' );
               $data               = array_intersect_key( $data, $formats );

               if ( empty( $data ) ) {
                       return false;
               }

               $format = array();
               foreach ( $data as $key => $value ) {
                       $format[] = $formats[ $key ];
               }

               return $wpdb->update(
                       $table,
                       $data,
                       array( 'id' => $id ),
                       $format,
                       array( '%d' )
               );
       }

       /**
        * Delete a bonus hunt.
        *
        * @param int $id Hunt ID.
        * @return int|false
        */
       public function delete_bonus_hunt( $id ) {
               global $wpdb;

               $table = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
               if ( ! in_array( $table, $this->get_allowed_tables(), true ) ) {
                       return false;
               }

               return $wpdb->delete(
                       $table,
                       array( 'id' => $id ),
                       array( '%d' )
               );
       }

       /**
        * Get affiliate websites.
        *
        * @return array
        */
       public function get_affiliate_websites() {
               global $wpdb;

               $table = esc_sql( $wpdb->prefix . 'bhg_affiliates' );
               if ( ! in_array( $table, $this->get_allowed_tables(), true ) ) {
                       return array();
               }

               $sql = "SELECT id, name FROM `{$table}` WHERE status = %s ORDER BY name";

               return $wpdb->get_results( $wpdb->prepare( $sql, 'active' ) );
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

		$allowed_tables = $this->get_allowed_tables();
		if ( ! in_array( $table, $allowed_tables, true ) ) {
			return false;
		}
		$column = esc_sql( $column );

		$wpdb->last_error = '';
		$exists           = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME=%s',
				DB_NAME,
				$table,
				$column
			)
		);

		if ( $wpdb->last_error ) {
			$wpdb->last_error = '';
			$sql              = sprintf( 'SHOW COLUMNS FROM `%s` LIKE %%s', $table );
			$exists           = $wpdb->get_var( $wpdb->prepare( $sql, $column ) );
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

		$allowed_tables = $this->get_allowed_tables();
		if ( ! in_array( $table, $allowed_tables, true ) ) {
			return false;
		}
		$index = esc_sql( $index );

		$wpdb->last_error = '';
		$exists           = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND INDEX_NAME=%s',
				DB_NAME,
				$table,
				$index
			)
		);

		if ( $wpdb->last_error ) {
			$wpdb->last_error = '';
			$sql              = sprintf( 'SHOW INDEX FROM `%s` WHERE Key_name=%%s', $table );
			$exists           = $wpdb->get_var( $wpdb->prepare( $sql, $index ) );
		}

		return ! empty( $exists );
	}
}
