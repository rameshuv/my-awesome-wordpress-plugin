<?php
/**
 * Database tools admin view.
 *
 * @package Bonus_Hunt_Guesser
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
      exit;
}


if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser' ) );
}

// Handle form submissions
$post_action = '';
$bhg_nonce   = '';

if ( isset( $_POST['bhg_action'] ) ) {
        $post_action = sanitize_text_field( wp_unslash( $_POST['bhg_action'] ) );
}

if ( isset( $_POST['bhg_nonce'] ) ) {
        $bhg_nonce = sanitize_text_field( wp_unslash( $_POST['bhg_nonce'] ) );
}

if ( 'db_cleanup' === $post_action && isset( $_POST['bhg_db_cleanup'] ) ) {
        if ( ! $bhg_nonce || ! wp_verify_nonce( $bhg_nonce, 'bhg_db_cleanup_action' ) ) {
                wp_die( esc_html__( 'Security check failed', 'bonus-hunt-guesser' ) );
        }

        // Perform database cleanup
        bhg_database_cleanup();
        $cleanup_completed = true;
} elseif ( 'db_optimize' === $post_action && isset( $_POST['bhg_db_optimize'] ) ) {
        if ( ! $bhg_nonce || ! wp_verify_nonce( $bhg_nonce, 'bhg_db_optimize_action' ) ) {
                wp_die( esc_html__( 'Security check failed', 'bonus-hunt-guesser' ) );
        }

        // Perform database optimization
        bhg_database_optimize();
        $optimize_completed = true;
}

// Whitelisted table names.
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

// Database cleanup function
function bhg_database_cleanup() {
        global $wpdb;

        foreach ( bhg_get_allowed_tables() as $slug ) {
                $table = esc_sql( $wpdb->prefix . $slug );
                if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
                        $wpdb->query( "TRUNCATE TABLE `{$table}`" );
                }
        }

        // Reinsert default data if needed
        bhg_insert_demo_data();
}

// Database optimization function
function bhg_database_optimize() {
        global $wpdb;

        foreach ( bhg_get_allowed_tables() as $slug ) {
                $table = esc_sql( $wpdb->prefix . $slug );
                if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
                        $wpdb->query( "OPTIMIZE TABLE `{$table}`" );
                }
        }
}

// Demo data insertion function (simplified version)
function bhg_insert_demo_data() {
        // This would typically be in a separate file like includes/demo.php
        global $wpdb;

        $table_slug = 'bhg_bonus_hunts';
        if ( ! in_array( $table_slug, bhg_get_allowed_tables(), true ) ) {
                return;
        }
        $table = esc_sql( $wpdb->prefix . $table_slug );

        // Insert default bonus hunt
        $wpdb->insert(
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
?>
<div class="wrap bhg-wrap">
	<h1><?php esc_html_e( 'Database Tools', 'bonus-hunt-guesser' ); ?></h1>
	<p><?php esc_html_e( 'Tables are automatically created on activation. If you need to reinstall them, deactivate and activate the plugin again.', 'bonus-hunt-guesser' ); ?></p>
	
	<?php if ( isset( $cleanup_completed ) && $cleanup_completed ) : ?>
		<div class="notice notice-success">
			<p><?php esc_html_e( 'Database cleanup completed successfully.', 'bonus-hunt-guesser' ); ?></p>
		</div>
	<?php endif; ?>
	
	<?php if ( isset( $optimize_completed ) && $optimize_completed ) : ?>
		<div class="notice notice-success">
			<p><?php esc_html_e( 'Database optimization completed successfully.', 'bonus-hunt-guesser' ); ?></p>
		</div>
	<?php endif; ?>
	
	<form method="post" action="">
		<?php wp_nonce_field( 'bhg_db_cleanup_action', 'bhg_nonce' ); ?>
		<input type="hidden" name="bhg_action" value="db_cleanup">
		<p>
			<input type="submit" name="bhg_db_cleanup" class="button button-secondary" value="<?php esc_attr_e( 'Run Database Cleanup', 'bonus-hunt-guesser' ); ?>"
					onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to run database cleanup? This action cannot be undone.', 'bonus-hunt-guesser' ) ); ?>')">
		</p>
		<p class="description">
			<?php esc_html_e( 'Note: This will remove any demo data and reset tables to their initial state.', 'bonus-hunt-guesser' ); ?>
		</p>
	</form>
	
	<h2><?php esc_html_e( 'Current Database Status', 'bonus-hunt-guesser' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Table Name', 'bonus-hunt-guesser' ); ?></th>
				<th><?php esc_html_e( 'Status', 'bonus-hunt-guesser' ); ?></th>
				<th><?php esc_html_e( 'Rows', 'bonus-hunt-guesser' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
                        global $wpdb;
                        foreach ( bhg_get_allowed_tables() as $table ) {
                                $table_name = esc_sql( $wpdb->prefix . $table );
                                $exists     = $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
                                $row_count  = $exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name}`" ) : 0;

                                echo '<tr>';
                                echo '<td>' . esc_html( $table_name ) . '</td>';
                                echo '<td><span class="' . ( $exists ? 'dashicons dashicons-yes-alt" style="color: #46b450"' : 'dashicons dashicons-no" style="color: #dc3232"' ) . '"></span> ' . ( $exists ? esc_html__( 'Exists', 'bonus-hunt-guesser' ) : esc_html__( 'Missing', 'bonus-hunt-guesser' ) ) . '</td>';
                                echo '<td>' . esc_html( number_format_i18n( $row_count ) ) . '</td>';
                                echo '</tr>';
                        }
                        ?>
                </tbody>
        </table>
	
	<h2><?php esc_html_e( 'Database Maintenance', 'bonus-hunt-guesser' ); ?></h2>
	<form method="post" action="">
		<?php wp_nonce_field( 'bhg_db_optimize_action', 'bhg_nonce' ); ?>
		<input type="hidden" name="bhg_action" value="db_optimize">
		<p>
			<input type="submit" name="bhg_db_optimize" class="button button-primary" value="<?php esc_attr_e( 'Optimize Database Tables', 'bonus-hunt-guesser' ); ?>">
		</p>
	</form>
</div>
