<?php
/**
 * Uninstall script for Bonus Hunt Guesser.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
delete_option( 'bhg_version' );
delete_option( 'bhg_plugin_settings' );

delete_site_option( 'bhg_version' );
delete_site_option( 'bhg_plugin_settings' );

global $wpdb;

$tables = array(
	'bhg_bonus_hunts',
	'bhg_guesses',
	'bhg_tournaments',
	'bhg_tournament_results',
	'bhg_ads',
	'bhg_translations',
	'bhg_affiliates',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
}
