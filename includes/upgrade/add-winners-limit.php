<?php
if (!defined('ABSPATH')) exit;

function bhg_upgrade_add_winners_limit_column(){
    global $wpdb;
    $hunts = $wpdb->prefix . 'bhg_hunts';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Ensure table exists & has winners_limit
    $sql = "CREATE TABLE $hunts (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        start_balance decimal(18,2) NOT NULL DEFAULT 0,
        final_balance decimal(18,2) DEFAULT NULL,
        winners_limit int unsigned NOT NULL DEFAULT 3,
        status varchar(20) NOT NULL DEFAULT 'open',
        closed_at datetime DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
}
