<?php
/**
 * Plugin Name: Bonus Hunt Guesser
 * Plugin URI: https://yourdomain.com/
 * Description: Comprehensive bonus hunt management system with tournaments, leaderboards, and user guessing functionality
 * Version: 8.0.07
 * Author: Bonus Hunt Guesser Development Team
 * Text Domain: bonus-hunt-guesser
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.3.5
 * License: GPLv2 or later
 */

// Helper: parse human-entered money-like strings into float
if ( ! function_exists( 'bhg_parse_amount' ) ) {
    /**
     * Parse a human-entered money-like string into a float.
     *
     * @param string $s Raw amount string.
     * @return float|null Parsed float value or null on failure.
     */
    function bhg_parse_amount( $s ) {
        if ( ! is_string( $s ) ) {
            return null;
        }
        // Normalize unicode spaces (NBSP / NNBSP) and trim
        $s = str_replace( array( "\xc2\xa0", "\xe2\x80\xaf" ), ' ', $s );
        $s = trim( wp_unslash( $s ) );
        if ( $s === '' ) {
            return null;
        }

        // Remove currency symbols/letters while keeping digits, separators, minus
        $s = preg_replace( '/[^\d,\.\-\s]/u', '', $s );
        $s = str_replace( ' ', '', $s );

        $has_comma = strpos( $s, ',' ) !== false;
        $has_dot   = strpos( $s, '.' ) !== false;

        if ( $has_comma && $has_dot ) {
            // Use the last occurring symbol as decimal separator
            $last_comma = strrpos( $s, ',' );
            $last_dot   = strrpos( $s, '.' );
            if ( $last_comma !== false && ( $last_dot === false || $last_comma > $last_dot ) ) {
                // Comma as decimal
                $s = str_replace( '.', '', $s );  // thousands
                $s = str_replace( ',', '.', $s ); // decimal
            } else {
                // Dot as decimal
                $s = str_replace( ',', '', $s );
            }
        } elseif ( $has_comma ) {
            // Only comma present
            $last = strrpos( $s, ',' );
            $frac = substr( $s, $last + 1 );
            if ( ctype_digit( $frac ) && strlen( $frac ) >= 1 && strlen( $frac ) <= 2 ) {
                // Treat as decimal
                $s = str_replace( ',', '.', $s );
            } else {
                // Treat as thousands (incl. Indian grouping)
                $s = str_replace( ',', '', $s );
            }
        } elseif ( $has_dot ) {
            // Only dot present
            $last = strrpos( $s, '.' );
            $frac = substr( $s, $last + 1 );
            if ( ctype_digit( $frac ) && strlen( $frac ) > 3 ) {
                // Likely thousands separators → remove all dots
                $s = str_replace( '.', '', $s );
            }
        }

        // Keep only digits, one leading minus, and dots
        $s = preg_replace( '/[^0-9\.\-]/', '', $s );
        if ( $s === '' || $s === '-' || $s === '.' || $s === '-.' || $s === '.-' ) {
            return null;
        }

        // Collapse multiple dots to a single decimal point (keep first as decimal)
        $parts = explode( '.', $s );
        if ( count( $parts ) > 2 ) {
            $s = $parts[0] . '.' . implode( '', array_slice( $parts, 1 ) );
        }

        if ( is_numeric( $s ) ) {
            return (float) $s;
        }

        // Permissive fallback: first number pattern
        if ( preg_match( '/\d+(?:\.\d+)?/', $s, $m2 ) ) {
            return (float) $m2[0];
        }

        return null;
    }
}
// Ensure canonical DB class is loaded
require_once __DIR__ . '/includes/class-bhg-db.php';


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BHG_VERSION','8.0.07');
define('BHG_MIN_WP', '6.3.5');
define('BHG_PLUGIN_FILE', __FILE__);
define('BHG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BHG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BHG_TABLE_PREFIX', 'bhg_');

// Simple BHG_DB class for activation
/**
 * Legacy database handler used for table creation during activation.
 */
class BHG_DB_OLD {
    /**
     * Table name prefix.
     *
     * @var string
     */
    private $table_prefix;

    /**
     * Set up the table prefix.
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . BHG_TABLE_PREFIX;
    }

    /**
     * Create required database tables.
     *
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Bonus hunts table
        $table_name = $this->table_prefix . 'bonus_hunts';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            starting_balance decimal(10,2) NOT NULL,
            num_bonuses int(11) NOT NULL,
            prizes text,
            status varchar(20) DEFAULT 'active',
            final_balance decimal(10,2) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Guesses table
        $table_name = $this->table_prefix . 'guesses';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            hunt_id int(11) NOT NULL,
            guess decimal(10,2) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY hunt_id (hunt_id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Ads table
        $table_name = $this->table_prefix . 'ads';
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NULL,
            link_url varchar(255) DEFAULT NULL,
            placement varchar(50) NOT NULL,
            visible_to varchar(20) DEFAULT 'all',
            target_pages text NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        // Tournaments table
        $table_name = $this->table_prefix . 'tournaments';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
        
        error_log('[BHG] All tables created successfully');
    }
    
    /**
     * Run database migrations for legacy schema.
     *
     * @return void
     */
    public static function migrate() {
        // Future database migration logic
        $current_version = get_option('bhg_db_version', '1.0');
        
        if (version_compare($current_version, '1.0', '=')) {
            // Initial version, nothing to migrate
            return;
        }
        
        // Add future migration logic here
        update_option('bhg_db_version', '1.0');
    }
}

// Table creation function
/**
 * Create plugin database tables using active DB class.
 *
 * @return void
 */
function bhg_create_tables() {
        if (class_exists('BHG_DB')) {
            (new BHG_DB())->create_tables();
            BHG_DB::migrate();
            return;
        }
}

// Check and create tables if needed
/**
 * Create tables on first run if they do not exist.
 *
 * @return void
 */
function bhg_check_tables() {
    if (!get_option('bhg_tables_created')) {
        bhg_create_tables();
        update_option('bhg_tables_created', true);
    }
}

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'BHG_') !== 0) {
        return;
    }
    
    $class_map = [
        'BHG_Admin' => 'admin/class-bhg-admin.php',
        'BHG_Shortcodes' => 'includes/class-bhg-shortcodes.php',
        'BHG_Logger' => 'includes/class-bhg-logger.php',
        'BHG_Settings' => 'includes/class-bhg-settings.php',
        'BHG_Utils' => 'includes/class-bhg-utils.php',
        'BHG_Menus' => 'includes/class-bhg-menus.php',
        'BHG_Models' => 'includes/class-bhg-models.php',
        'BHG_Front_Menus' => 'includes/class-bhg-front-menus.php',
        'BHG_Demo' => 'admin/class-bhg-demo.php',
    ];
    
    if (isset($class_map[$class])) {
        $file_path = BHG_PLUGIN_DIR . $class_map[$class];
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
});

// Include helper functions
require_once BHG_PLUGIN_DIR . 'includes/helpers.php';

// Activation hook: create tables and set default options
/**
 * Activation callback for setting up the plugin.
 *
 * @param bool $network_wide Whether plugin is network activated.
 * @return void
 */
function bhg_activate_plugin( $network_wide ) {
    if (!current_user_can('activate_plugins')) {
        return;
    }

    bhg_create_tables();
    
    // Set default options
    add_option('bhg_version', BHG_VERSION);
    add_option('bhg_plugin_settings', [
        'allow_guess_changes' => 'yes',
        'default_tournament_period' => 'monthly',
        'min_guess_amount' => 0,
        'max_guess_amount' => 100000,
        'ads_enabled' => 1,
        'email_from' => get_bloginfo('admin_email'),
    ]);
    
    // Seed demo data if empty
    if (function_exists('bhg_seed_demo_if_empty')) {
        bhg_seed_demo_if_empty();
    }
    update_option('bhg_demo_notice', 1);
    
    // Set tables created flag
    update_option('bhg_tables_created', true);
    
    // Flush rewrite rules after database changes
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'bhg_activate_plugin');

// Deactivation hook (no destructive actions)
register_deactivation_hook(__FILE__, function() {
    // Keep data intact by default
});

// Frontend asset loader
add_action( 'wp_enqueue_scripts', 'bhg_enqueue_public_assets' );

/**
 * Enqueue public-facing scripts and styles.
 *
 * @return void
 */
function bhg_enqueue_public_assets() {
    wp_register_style(
        'bhg-public',
        BHG_PLUGIN_URL . 'assets/css/public.css',
        array(),
        defined( 'BHG_VERSION' ) ? BHG_VERSION : null
    );

    wp_register_script(
        'bhg-public',
        BHG_PLUGIN_URL . 'assets/js/public.js',
        array( 'jquery' ),
        defined( 'BHG_VERSION' ) ? BHG_VERSION : null,
        true
    );

    wp_localize_script(
        'bhg-public',
        'bhg_public_ajax',
        array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'bhg_public_nonce' ),
            'is_logged_in' => is_user_logged_in(),
            'i18n'         => array(
                'guess_required'     => __( 'Please enter a guess.', 'bonus-hunt-guesser' ),
                'guess_numeric'      => __( 'Please enter a valid number.', 'bonus-hunt-guesser' ),
                'guess_range'        => __( 'Guess must be between €0 and €100,000.', 'bonus-hunt-guesser' ),
                'guess_submitted'    => __( 'Your guess has been submitted!', 'bonus-hunt-guesser' ),
                'ajax_error'         => __( 'An error occurred. Please try again.', 'bonus-hunt-guesser' ),
                'affiliate_user'     => __( 'Affiliate', 'bonus-hunt-guesser' ),
                'non_affiliate_user' => __( 'Non-affiliate', 'bonus-hunt-guesser' ),
            ),
        )
    );

    wp_enqueue_style( 'bhg-public' );
    wp_enqueue_script( 'bhg-public' );
}

// Initialize plugin
add_action('plugins_loaded', 'bhg_init_plugin');
/**
 * Initialize plugin components.
 *
 * @return void
 */
function bhg_init_plugin() {
    // Load text domain
    load_plugin_textdomain('bonus-hunt-guesser', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    
    // Initialize components
    if (is_admin()) {
        if (class_exists('BHG_Admin')) {
            new BHG_Admin();
        }
        if (class_exists('BHG_Demo')) {
            new BHG_Demo();
        }
    }
    
    if (class_exists('BHG_Shortcodes')) {
        new BHG_Shortcodes();
    }
    if (class_exists('BHG_Front_Menus')) {
        new BHG_Front_Menus();
    }
    
    if (class_exists('BHG_DB')) {
        BHG_DB::migrate();
    }
    
    if (class_exists('BHG_Utils')) {
        BHG_Utils::init_hooks();
    }
    
    // Register form handlers
    add_action('admin_post_bhg_save_bonus_hunt', 'bhg_handle_bonus_hunt_save');
    add_action('admin_post_nopriv_bhg_save_bonus_hunt', 'bhg_handle_bonus_hunt_save_unauth');
    add_action('admin_post_bhg_submit_guess', 'bhg_handle_submit_guess');
    add_action('admin_post_nopriv_bhg_submit_guess', function(){ $ref = wp_get_referer(); wp_safe_redirect( wp_login_url( $ref ? $ref : home_url() ) ); exit; });
    add_action('wp_ajax_submit_bhg_guess', 'bhg_handle_submit_guess');
    add_action('wp_ajax_nopriv_submit_bhg_guess', 'bhg_handle_submit_guess');
    add_action('admin_post_bhg_save_settings', 'bhg_handle_settings_save');
}

// Early table check on init
add_action('init', 'bhg_check_tables', 0);

// Form handler for settings save
/**
 * Handle saving of plugin settings.
 *
 * @return void
 */
function bhg_handle_settings_save() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'bonus-hunt-guesser'));
    }
    
    // Verify nonce
    if (!isset($_POST['bhg_settings_nonce']) || !wp_verify_nonce($_POST['bhg_settings_nonce'], 'bhg_save_settings_nonce')) {
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=bhg_settings&error=nonce_failed' ) ) );
        exit;
    }
    
    // Sanitize and validate data
    $settings = array();
    
    if (isset($_POST['bhg_default_tournament_period'])) {
        $period = sanitize_text_field($_POST['bhg_default_tournament_period']);
        if (in_array($period, array('weekly', 'monthly', 'yearly'))) {
            $settings['default_tournament_period'] = $period;
        }
    }
    
    if (isset($_POST['bhg_max_guess_amount'])) {
        $max = floatval($_POST['bhg_max_guess_amount']);
        if ($max >= 0) {
            $settings['max_guess_amount'] = $max;
        }
    }
    
    if (isset($_POST['bhg_min_guess_amount'])) {
        $min = floatval($_POST['bhg_min_guess_amount']);
        if ($min >= 0) {
            $settings['min_guess_amount'] = $min;
        }
    }
    
    // Validate that min is not greater than max
    if (isset($settings['min_guess_amount']) && isset($settings['max_guess_amount']) && 
        $settings['min_guess_amount'] > $settings['max_guess_amount']) {
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=bhg_settings&error=invalid_data' ) ) );
        exit;
    }
    
    if (isset($_POST['bhg_allow_guess_changes'])) {
        $allow = sanitize_text_field($_POST['bhg_allow_guess_changes']);
        if (in_array($allow, array('yes', 'no'))) {
            $settings['allow_guess_changes'] = $allow;
        }
    }
    
    if (isset($_POST['bhg_ads_enabled'])) {
        $ads_enabled = sanitize_text_field($_POST['bhg_ads_enabled']);
        $settings['ads_enabled'] = $ads_enabled === '1' ? 1 : 0;
    }
    
    if (isset($_POST['bhg_email_from'])) {
        $email_from = sanitize_email($_POST['bhg_email_from']);
        if ($email_from) {
            $settings['email_from'] = $email_from;
        }
    }
    
    // Save settings
    update_option('bhg_plugin_settings', $settings);
    
    // Redirect back to settings page
    wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=bhg_settings&message=saved' ) ) );
    exit;
}

// Form handler for bonus hunt save (admin)
/**
 * Handle admin bonus hunt form submissions.
 *
 * @return void
 */
function bhg_handle_bonus_hunt_save() {
    // Verify nonce
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'bhg_form_nonce' ) ) {
        wp_die( esc_html__( 'Security check failed', 'bonus-hunt-guesser' ) );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access denied', 'bonus-hunt-guesser' ) );
    }

    // Process form data
    global $wpdb;
    $table_name = $wpdb->prefix . 'bhg_bonus_hunts';

    $data = [
        'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ) ),
        'starting_balance' => floatval( wp_unslash( $_POST['starting_balance'] ) ),
        'num_bonuses'     => intval( wp_unslash( $_POST['num_bonuses'] ) ),
        'prizes'          => wp_kses_post( wp_unslash( $_POST['prizes'] ) ),
        'status'          => sanitize_text_field( wp_unslash( $_POST['status'] ) ),
        'updated_at'      => current_time( 'mysql', 1 ),
    ];

    if ( isset( $_POST['final_balance'] ) ) {
        $data['final_balance'] = floatval( wp_unslash( $_POST['final_balance'] ) );
    }

    if ( isset( $_POST['id'] ) && ! empty( $_POST['id'] ) ) {
        // Update existing hunt
        $wpdb->update(
            $table_name,
            $data,
            [ 'id' => intval( wp_unslash( $_POST['id'] ) ) ]
        );
    } else {
        // Insert new hunt
        $data['created_at'] = current_time( 'mysql', 1 );
        $wpdb->insert( $table_name, $data );
    }

    wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=bhg_bonus_hunts&message=saved' ) ) );
    exit;
}

/**
 * Handle unauthorized bonus hunt save attempts.
 *
 * @return void
 */
function bhg_handle_bonus_hunt_save_unauth() {
    wp_die( esc_html__( 'You must be logged in to submit this form', 'bonus-hunt-guesser' ) );
}

// Form handler for guess submission (frontend)
/**
 * Proxy handler for guess submissions.
 *
 * @return void
 */
function bhg_handle_guess_submission() { return bhg_handle_submit_guess(); }

// Canonical guess submit handler
/**
 * Process a guess submission from admin-post or AJAX.
 *
 * @return void
 */
function bhg_handle_submit_guess() {
    if ( wp_doing_ajax() ) {
        check_ajax_referer( 'bhg_public_nonce', 'nonce' );
    } else {
        if ( ! isset( $_POST['bhg_nonce'] ) ) {
            wp_die( esc_html__( 'Security check failed.', 'bonus-hunt-guesser' ) );
        }
        check_admin_referer( 'bhg_submit_guess', 'bhg_nonce' );
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'You must be logged in to submit a guess.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'You must be logged in to submit a guess.', 'bonus-hunt-guesser' ) );
    }

    $hunt_id = isset( $_POST['hunt_id'] ) ? (int) $_POST['hunt_id'] : 0;
    if ( $hunt_id <= 0 ) {
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid hunt.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'Invalid hunt.', 'bonus-hunt-guesser' ) );
    }

    // Parse guess robustly
    if ( wp_doing_ajax() ) {
        $raw_guess = isset( $_POST['guess_amount'] ) ? wp_unslash( $_POST['guess_amount'] ) : '';
    } else {
        $raw_guess = isset( $_POST['guess'] ) ? wp_unslash( $_POST['guess'] ) : ( isset( $_POST['balance_guess'] ) ? wp_unslash( $_POST['balance_guess'] ) : '' );
    }
    $guess = -1.0;
    if ( function_exists( 'bhg_parse_amount' ) ) {
        $parsed = bhg_parse_amount( $raw_guess );
        $guess  = ( null === $parsed ) ? -1.0 : (float) $parsed;
    } else {
        $guess = is_numeric( $raw_guess ) ? (float) $raw_guess : -1.0;
    }

    if ( $guess < 0 || $guess > 100000 ) {
        if ( function_exists( 'error_log' ) ) {
            error_log( '[BHG] invalid guess after parse: raw=' . print_r( $raw_guess, true ) . ' parsed=' . print_r( $guess, true ) );
        }
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'Invalid guess amount.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'Invalid guess amount.', 'bonus-hunt-guesser' ) );
    }

    global $wpdb;
    $hunts = $wpdb->prefix . 'bhg_bonus_hunts';
    $g_tbl = $wpdb->prefix . 'bhg_guesses';

    $hunt = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM `$hunts` WHERE id=%d", $hunt_id ) );
    if ( ! $hunt ) {
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'Hunt not found.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'Hunt not found.', 'bonus-hunt-guesser' ) );
    }
    if ( $hunt->status !== 'open' ) {
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'This hunt is closed. You cannot submit or change a guess.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'This hunt is closed. You cannot submit or change a guess.', 'bonus-hunt-guesser' ) );
    }

    // Insert or update last guess per settings
    $max        = (int) get_option( 'bhg_guesses_max', 1 );
    $allow_edit = get_option( 'bhg_allow_guess_edit_until_close', 'yes' ) === 'yes';

    $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$g_tbl` WHERE hunt_id=%d AND user_id=%d", $hunt_id, $user_id ) );
    if ( $count >= $max ) {
        if ( $allow_edit && $count > 0 ) {
            $gid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$g_tbl` WHERE hunt_id=%d AND user_id=%d ORDER BY id DESC LIMIT 1", $hunt_id, $user_id ) );
            if ( $gid ) {
                $wpdb->update( $g_tbl, [ 'guess' => $guess, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $gid ] );
                if ( wp_doing_ajax() ) {
                    wp_send_json_success();
                }
                wp_safe_redirect( wp_get_referer() ?: home_url() );
                exit;
            }
        }
        if ( wp_doing_ajax() ) {
            wp_send_json_error( __( 'You have reached the maximum number of guesses.', 'bonus-hunt-guesser' ) );
        }
        wp_die( esc_html__( 'You have reached the maximum number of guesses.', 'bonus-hunt-guesser' ) );
    }

    // Insert
    $wpdb->insert(
        $g_tbl,
        [
            'hunt_id'    => $hunt_id,
            'user_id'    => $user_id,
            'guess'      => $guess,
            'created_at' => current_time( 'mysql' ),
        ]
    );

    if ( wp_doing_ajax() ) {
        wp_send_json_success();
    }

    wp_safe_redirect( wp_get_referer() ?: home_url() );
    exit;
}

// Frontend ads rendering
/**
 * Determine if an advertisement should be displayed.
 *
 * @param string $visibility Visibility rule.
 * @return bool True if ad should be shown, false otherwise.
 */
function bhg_should_show_ad( $visibility ) {
    if ($visibility === 'all') {
        return true;
    }
    if ($visibility === 'logged_in') {
        return (function_exists('is_user_logged_in') && is_user_logged_in());
    }
    if ($visibility === 'guests') {
        return !(function_exists('is_user_logged_in') && is_user_logged_in());
    }
    if ($visibility === 'affiliates') {
        return (function_exists('is_user_logged_in') && is_user_logged_in()) && bhg_is_affiliate();
    }
    if ($visibility === 'non_affiliates') {
        return !(function_exists('is_user_logged_in') && is_user_logged_in()) || !bhg_is_affiliate();
    }
    return true;
}

/**
 * Safe and validated ads query builder.
 *
 * @param string $table     Table name without prefix.
 * @param string $placement Ad placement.
 * @return array List of ad rows.
 */
function bhg_build_ads_query( $table, $placement = 'footer' ) {
    global $wpdb;

    // Validate table variable (only allow alphanumeric and underscore)
    if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return array();
    }

    // Build safe table name with prefix if required
    $safe_table = esc_sql($table);
    if (strpos($safe_table, $wpdb->prefix) !== 0) {
        if (strpos($safe_table, BHG_TABLE_PREFIX) === 0 || strpos($safe_table, 'bhg_') === 0) {
            $safe_table = $safe_table;
        } else {
            $safe_table = $wpdb->prefix . $safe_table;
        }
    }

    // Ensure final safe table name still matches allowed characters
    if (!preg_match('/^[A-Za-z0-9_]+$/', str_replace($wpdb->prefix, '', $safe_table))) {
        return array();
    }

    // Use prepare for the placement value
    $query = $wpdb->prepare(
        "SELECT * FROM `{$safe_table}` WHERE placement = %s AND active = %d",
        $placement,
        1
    );

    $rows = $wpdb->get_results($query);
    if ( did_action('wp') && function_exists('get_queried_object_id') ) {
        $pid = (int)get_queried_object_id();
        if ($pid && is_array($rows)) {
            $rows = array_filter($rows, function($r) use ($pid) {
                if (empty($r->target_pages)) return true;
                $ids = array_filter(array_map('intval', array_map('trim', explode(',', $r->target_pages))));
                return in_array($pid, $ids, true);
            });
        }
    }
    return $rows;
}

// AJAX handler for loading leaderboard data
add_action('wp_ajax_bhg_load_leaderboard', 'bhg_load_leaderboard_ajax');
add_action('wp_ajax_nopriv_bhg_load_leaderboard', 'bhg_load_leaderboard_ajax');

/**
 * AJAX handler for loading leaderboard markup.
 *
 * @return void
 */
function bhg_load_leaderboard_ajax() {
    check_ajax_referer('bhg_public_nonce', 'nonce');
    
    if ( ! isset( $_POST['timeframe'] ) ) {
        wp_send_json_error( __( 'Invalid timeframe', 'bonus-hunt-guesser' ) );
    }

    $timeframe = sanitize_text_field( wp_unslash( $_POST['timeframe'] ) );
    $allowed_timeframes = array( 'overall', 'monthly', 'yearly', 'alltime' );
    if ( ! in_array( $timeframe, $allowed_timeframes, true ) ) {
        wp_send_json_error( __( 'Invalid timeframe', 'bonus-hunt-guesser' ) );
    }
    
    // Generate leaderboard HTML based on timeframe
    $html = bhg_generate_leaderboard_html($timeframe);
    
    wp_send_json_success($html);
}

// Helper function to generate leaderboard HTML
/**
 * Generate leaderboard HTML for a timeframe.
 *
 * @param string $timeframe Timeframe key.
 * @return string Generated HTML.
 */
function bhg_generate_leaderboard_html( $timeframe ) {
    global $wpdb;

    $per_page = 20;
    $paged    = isset( $_POST['paged'] ) ? max( 1, (int) $_POST['paged'] ) : 1;
    $offset   = ( $paged - 1 ) * $per_page;

    $start_date = '';
    $now        = current_time( 'timestamp' );
    switch ( strtolower( $timeframe ) ) {
        case 'monthly':
            $start_date = gmdate( 'Y-m-01 00:00:00', $now );
            break;
        case 'yearly':
            $start_date = gmdate( 'Y-01-01 00:00:00', $now );
            break;
        case 'overall':
            $start_date = gmdate( 'Y-m-d H:i:s', $now - 30 * DAY_IN_SECONDS );
            break;
        case 'all-time':
        case 'all_time':
        default:
            $start_date = '';
            break;
    }

    $g = $wpdb->prefix . 'bhg_guesses';
    $h = $wpdb->prefix . 'bhg_bonus_hunts';
    $u = $wpdb->users;

    $where = "h.status='closed' AND h.final_balance IS NOT NULL";
    $args  = array();
    if ( $start_date ) {
        $where .= " AND h.updated_at >= %s";
        $args[] = $start_date;
    }

    $sql_total = "
        SELECT COUNT(*) FROM (
            SELECT g.user_id
            FROM {$g} g
            INNER JOIN {$h} h ON h.id = g.hunt_id
            WHERE {$where} AND NOT EXISTS (
                SELECT 1 FROM {$g} g2
                WHERE g2.hunt_id = g.hunt_id
                  AND ABS(g2.guess - h.final_balance) < ABS(g.guess - h.final_balance)
            )
            GROUP BY g.user_id
        ) t";

    if ( $args ) {
        $total = (int) $wpdb->get_var( $wpdb->prepare( $sql_total, $args ) );
    } else {
        $total = (int) $wpdb->get_var( $sql_total );
    }

    $sql = "
        SELECT g.user_id, u.user_login, COUNT(*) AS wins
        FROM {$g} g
        INNER JOIN {$h} h ON h.id = g.hunt_id
        INNER JOIN {$u} u ON u.ID = g.user_id
        WHERE {$where} AND NOT EXISTS (
            SELECT 1 FROM {$g} g2
            WHERE g2.hunt_id = g.hunt_id
              AND ABS(g2.guess - h.final_balance) < ABS(g.guess - h.final_balance)
        )
        GROUP BY g.user_id, u.user_login
        ORDER BY wins DESC, u.user_login ASC
        LIMIT %d OFFSET %d";

    $args_query   = $args;
    $args_query[] = $per_page;
    $args_query[] = $offset;
    $rows         = $wpdb->get_results( $wpdb->prepare( $sql, $args_query ) );

    if ( ! $rows ) {
        return '<p>' . esc_html__( 'No data available.', 'bonus-hunt-guesser' ) . '</p>';
    }

    ob_start();
    echo '<table class="bhg-leaderboard bhg-table" data-timeframe="' . esc_attr( $timeframe ) . '">';
    echo '<thead><tr>';
    echo '<th class="sortable" data-sort="position">' . esc_html__( 'Position', 'bonus-hunt-guesser' ) . '</th>';
    echo '<th class="sortable" data-sort="username">' . esc_html__( 'User', 'bonus-hunt-guesser' ) . '</th>';
    echo '<th class="sortable" data-sort="wins">' . esc_html__( 'Wins', 'bonus-hunt-guesser' ) . '</th>';
    echo '</tr></thead><tbody>';

    $pos = $offset + 1;
    foreach ( $rows as $row ) {
        $user_label = $row->user_login ? $row->user_login : 'user#' . (int) $row->user_id;
        echo '<tr>';
        echo '<td>' . (int) $pos++ . '</td>';
        echo '<td>' . esc_html( $user_label ) . '</td>';
        echo '<td>' . (int) $row->wins . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    $pages = (int) ceil( $total / $per_page );
    if ( $pages > 1 ) {
        echo '<div class="bhg-pagination">';
        for ( $p = 1; $p <= $pages; $p++ ) {
            $current = $p === $paged ? ' class="current"' : '';
            echo '<a href="#" data-page="' . (int) $p . '"' . $current . '>' . (int) $p . '</a> ';
        }
        echo '</div>';
    }

    return ob_get_clean();
}

// Helper function to check if user is affiliate
/**
 * Check whether a user has affiliate status.
 *
 * @param int|null $user_id Optional. User ID to check. Defaults to current user.
 * @return bool True if user is an affiliate.
 */
function bhg_is_affiliate( $user_id = null ) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    return (bool) get_user_meta($user_id, 'bhg_affiliate_status', true);
}

// Add user profile fields for affiliate status
add_action('show_user_profile', 'bhg_extra_user_profile_fields');
add_action('edit_user_profile', 'bhg_extra_user_profile_fields');

/**
 * Display affiliate status field on user profile.
 *
 * @param WP_User $user User object.
 * @return void
 */
function bhg_extra_user_profile_fields( $user ) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $affiliate_status = get_user_meta($user->ID, 'bhg_affiliate_status', true);
    ?>
    <h3><?php esc_html_e('Bonus Hunt Guesser Information', 'bonus-hunt-guesser'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="bhg_affiliate_status"><?php esc_html_e('Affiliate Status', 'bonus-hunt-guesser'); ?></label></th>
            <td>
                <input type="checkbox" name="bhg_affiliate_status" id="bhg_affiliate_status" value="1" <?php checked($affiliate_status, 1); ?> />
                <span class="description"><?php esc_html_e('Check if this user is an affiliate.', 'bonus-hunt-guesser'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'bhg_save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'bhg_save_extra_user_profile_fields');

/**
 * Save affiliate status from user profile.
 *
 * @param int $user_id User ID.
 * @return void|false Returns false if the user cannot be edited.
 */
function bhg_save_extra_user_profile_fields( $user_id ) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $affiliate_status = isset($_POST['bhg_affiliate_status']) ? 1 : 0;
    update_user_meta($user_id, 'bhg_affiliate_status', $affiliate_status);
}

if (!function_exists('bhg_self_heal_db')) {
    /**
     * Attempt to repair missing database tables.
     *
     * @return void
     */
    function bhg_self_heal_db() {
        if (!class_exists('BHG_DB')) require_once __DIR__ . '/includes/class-bhg-db.php';
        try {
            $db = new BHG_DB();
            $db->create_tables();
        } catch (Throwable $e) {
            if (function_exists('error_log')) error_log('[BHG] DB self-heal failed: ' . $e->getMessage());
        }
    }
    add_action('admin_init', 'bhg_self_heal_db');
    register_activation_hook(__FILE__, 'bhg_self_heal_db');
}


// === BHG Guess Shortcode & Handler (clean block) ===
if (!function_exists('bhg_guess_form_shortcode')) {
    /**
     * Render the guess form shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Form HTML.
     */
    function bhg_guess_form_shortcode( $atts ) {
        $atts = shortcode_atts(['hunt_id' => 0], $atts, 'bhg_guess_form');
        $hunt_id = (int) $atts['hunt_id'];
        if (!(function_exists('is_user_logged_in') && is_user_logged_in())) {
            return '<p>' . esc_html__('Please log in to submit your guess.', 'bonus-hunt-guesser') . '</p>';
        }
        if (!$hunt_id) {
            return '<p>' . esc_html__('No active hunt selected.', 'bonus-hunt-guesser') . '</p>';
        }
        ob_start(); ?>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php', 'relative') ); ?>" class="bhg-guess-form">
            <input type="hidden" name="action" value="bhg_submit_guess" />
            <input type="hidden" name="hunt_id" value="<?php echo (int) $hunt_id; ?>" />
            <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>
            <label><?php esc_html_e('Your Guess (0 - 100,000)', 'bonus-hunt-guesser'); ?>
                <input type="text" name="guess" placeholder="e.g. 1,234.56" required />
            </label>
            <button type="submit"><?php esc_html_e('Submit Guess', 'bonus-hunt-guesser'); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }
    add_shortcode('bhg_guess_form', 'bhg_guess_form_shortcode');
}
