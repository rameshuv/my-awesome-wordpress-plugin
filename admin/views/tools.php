<?php
// phpcs:ignoreFile -- Legacy view requires refactoring for WordPress coding standards.
/**
 * Tools admin view.
 *
 * @package BonusHuntGuesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

if ( ! function_exists( 'bhg_get_allowed_tables' ) ) {
	/**
	 * Return the list of plugin-owned tables (slugs only).
	 *
	 * @return string[] Allowed table slugs.
	 */
	function bhg_get_allowed_tables() {
		return array(
			'bhg_bonus_hunts',
			'bhg_guesses',
			'bhg_tournaments',
			'bhg_tournament_results',
			'bhg_translations',
			'bhg_affiliate_websites',
			'bhg_hunt_winners',
			'bhg_ads',
		);
	}
}

if ( ! function_exists( 'bhg_insert_demo_data' ) ) {
	/**
	 * Insert minimal demo data.
	 *
	 * This would typically live in a dedicated demo/seed file.
	 *
	 * @return void
	 */
	function bhg_insert_demo_data() {
		global $wpdb;

		$table_slug = 'bhg_bonus_hunts';

		if ( ! in_array( $table_slug, bhg_get_allowed_tables(), true ) ) {
			return;
		}

		$table = $wpdb->prefix . $table_slug;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array(
				'title'             => __( 'Demo Bonus Hunt', 'bonus-hunt-guesser' ),
				'starting_balance'  => 2000,
				'number_of_bonuses' => 10,
				'status'            => 'active',
				'created_at'        => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%d', '%s', '%s' )
		);
	}
}

if ( ! function_exists( 'bhg_database_cleanup' ) ) {
	/**
	 * Truncate all plugin tables and re-insert demo data.
	 *
	 * @return void
	 */
	function bhg_database_cleanup() {
		global $wpdb;

		foreach ( bhg_get_allowed_tables() as $slug ) {
			$table = $wpdb->prefix . $slug;

			if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name comes from a predefined whitelist and maintenance queries require direct execution.
				$wpdb->query( "TRUNCATE TABLE `{$table}`" );
			}
		}

		bhg_insert_demo_data();
	}
}

if ( ! function_exists( 'bhg_database_optimize' ) ) {
	/**
	 * Optimize all plugin tables.
	 *
	 * @return void
	 */
	function bhg_database_optimize() {
		global $wpdb;

		foreach ( bhg_get_allowed_tables() as $slug ) {
			$table = $wpdb->prefix . $slug;

			if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name comes from a predefined whitelist and maintenance queries require direct execution.
				$wpdb->query( "OPTIMIZE TABLE `{$table}`" );
			}
		}
	}
}

$notice = '';

if ( isset( $_POST['bhg_action'] ) ) {
	$post_action = sanitize_text_field( wp_unslash( $_POST['bhg_action'] ) );
	$bhg_nonce   = isset( $_POST['bhg_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bhg_nonce'] ) ) : '';

	if ( 'demo_reseed' === $post_action && isset( $_POST['bhg_demo_reseed'] ) ) {
		if ( ! $bhg_nonce || ! wp_verify_nonce( $bhg_nonce, 'bhg_demo_reseed_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'bonus-hunt-guesser' ) );
		}
		if ( function_exists( 'bhg_reset_demo_and_seed' ) ) {
			bhg_reset_demo_and_seed();
			$notice = __( 'Demo data reseeded.', 'bonus-hunt-guesser' );
		}
	} elseif ( 'db_cleanup' === $post_action && isset( $_POST['bhg_db_cleanup'] ) ) {
		if ( ! $bhg_nonce || ! wp_verify_nonce( $bhg_nonce, 'bhg_db_cleanup_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'bonus-hunt-guesser' ) );
		}
		bhg_database_cleanup();
		$notice = __( 'Database cleanup completed.', 'bonus-hunt-guesser' );
	} elseif ( 'db_optimize' === $post_action && isset( $_POST['bhg_db_optimize'] ) ) {
		if ( ! $bhg_nonce || ! wp_verify_nonce( $bhg_nonce, 'bhg_db_optimize_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'bonus-hunt-guesser' ) );
		}
		bhg_database_optimize();
		$notice = __( 'Database optimization completed.', 'bonus-hunt-guesser' );
	}
}

global $wpdb;
$hunts_table   = esc_sql( $wpdb->prefix . 'bhg_bonus_hunts' );
$guesses_table = esc_sql( $wpdb->prefix . 'bhg_guesses' );
$ads_table     = esc_sql( $wpdb->prefix . 'bhg_ads' );
$tours_table   = esc_sql( $wpdb->prefix . 'bhg_tournaments' );
$hunts         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hunts_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are hardcoded with prefix and require no placeholders.
$guesses       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$guesses_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are hardcoded with prefix and require no placeholders.
$users         = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WordPress users table is known and query has no dynamic parts.
$ads           = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ads_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are hardcoded with prefix and require no placeholders.
$tournaments   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tours_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table names are hardcoded with prefix and require no placeholders.
?>
<div class="wrap">
<h1><?php echo esc_html__( 'BHG Tools', 'bonus-hunt-guesser' ); ?></h1>

<?php if ( $notice ) : ?>
<div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div>
<?php endif; ?>

<div class="card" style="max-width:900px;padding:16px;margin-top:12px;">
<h2><?php echo esc_html__( 'Maintenance Actions', 'bonus-hunt-guesser' ); ?></h2>
<form method="post">
<?php wp_nonce_field( 'bhg_demo_reseed_action', 'bhg_nonce' ); ?>
<input type="hidden" name="bhg_action" value="demo_reseed" />
<p><input type="submit" name="bhg_demo_reseed" class="button button-secondary" value="<?php esc_attr_e( 'Reset & Reseed Demo Data', 'bonus-hunt-guesser' ); ?>" /></p>
</form>
<form method="post">
<?php wp_nonce_field( 'bhg_db_cleanup_action', 'bhg_nonce' ); ?>
<input type="hidden" name="bhg_action" value="db_cleanup" />
<p><input type="submit" name="bhg_db_cleanup" class="button button-secondary" value="<?php esc_attr_e( 'Run Database Cleanup', 'bonus-hunt-guesser' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to run database cleanup? This action cannot be undone.', 'bonus-hunt-guesser' ) ); ?>');" /></p>
</form>
<form method="post">
<?php wp_nonce_field( 'bhg_db_optimize_action', 'bhg_nonce' ); ?>
<input type="hidden" name="bhg_action" value="db_optimize" />
<p><input type="submit" name="bhg_db_optimize" class="button button-secondary" value="<?php esc_attr_e( 'Optimize Database Tables', 'bonus-hunt-guesser' ); ?>" /></p>
</form>
</div>

<div class="card" style="max-width:900px;padding:16px;margin-top:12px;">
<h2><?php echo esc_html__( 'Diagnostics', 'bonus-hunt-guesser' ); ?></h2>
<?php if ( ( $hunts + $guesses + $users + $ads + $tournaments ) > 0 ) : ?>
<ul>
<li><?php echo esc_html__( 'Hunts:', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( number_format_i18n( $hunts ) ); ?></li>
<li><?php echo esc_html__( 'Guesses:', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( number_format_i18n( $guesses ) ); ?></li>
<li><?php echo esc_html__( 'Users:', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( number_format_i18n( $users ) ); ?></li>
<li><?php echo esc_html__( 'Ads:', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( number_format_i18n( $ads ) ); ?></li>
<li><?php echo esc_html__( 'Tournaments:', 'bonus-hunt-guesser' ); ?> <?php echo esc_html( number_format_i18n( $tournaments ) ); ?></li>
</ul>
<?php else : ?>
<p><?php echo esc_html__( 'Nothing to show yet. Start by creating a hunt or a test user.', 'bonus-hunt-guesser' ); ?></p>
<?php endif; ?>
</div>
</div>

