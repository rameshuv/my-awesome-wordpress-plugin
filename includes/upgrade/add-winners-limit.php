<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bhg_upgrade_add_winners_limit_column(){
	global $wpdb;
	$hunts = $wpdb->prefix . 'bhg_bonus_hunts';
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Ensure table exists & has winners_count column
	$sql = "CREATE TABLE {$hunts} (
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

	dbDelta( $sql );
}
