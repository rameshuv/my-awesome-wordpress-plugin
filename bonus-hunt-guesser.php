<?php
/**
 * Plugin Name: Bonus Hunt Guesser
 * Plugin URI: https://yourdomain.com/
 * Description: Comprehensive bonus hunt management system with tournaments, leaderboards, and user guessing functionality.
 * Author: Bonus Hunt Guesser Development Team
 * Text Domain: bonus-hunt-guesser
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Version: 8.0.10
 * Requires at least: 5.5.5
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) { 
    exit; 
}

define('BHG_VERSION', '8.0.10');
define('BHG_MIN_WP', '5.5.5');
define('BHG_PLUGIN_FILE', __FILE__);
define('BHG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BHG_PLUGIN_URL', plugin_dir_url(__FILE__));

// PSR-4-ish autoload within plugin
spl_autoload_register(function($class){
    if (strpos($class, 'BHG_') !== 0) return;
    $map = [
        'BHG_DB' => 'includes/class-bhg-db.php',
        'BHG_Admin' => 'admin/class-bhg-admin.php',
        'BHG_Shortcodes' => 'includes/class-bhg-shortcodes.php',
        'BHG_Logger' => 'includes/class-bhg-logger.php',
        'BHG_Settings' => 'includes/class-bhg-settings.php',
        'BHG_Utils' => 'includes/class-bhg-utils.php',
        'BHG_Models' => 'includes/class-bhg-models.php',
        'BHG_Menus' => 'includes/class-bhg-menus.php',
    ];
    if (isset($map[$class])) {
        $path = BHG_PLUGIN_DIR . $map[$class];
        if (file_exists($path)) require_once $path;
    }
});

require_once BHG_PLUGIN_DIR . 'includes/helpers.php';

// Activation: create tables and options
register_activation_hook(__FILE__, function(){
    if (!class_exists('BHG_DB')) require_once BHG_PLUGIN_DIR . 'includes/class-bhg-db.php';
    $db = new BHG_DB();
    $db->install();
    // default options
    add_option('bhg_version', BHG_VERSION);
    add_option('bhg_settings', [
        'allow_guess_edit' => 1,
        'ads_enabled' => 1,
        'email_from' => get_bloginfo('admin_email'),
    ]);
    
    // Seed demo data
    bhg_seed_demo_if_empty();
});

// Deactivation: nothing destructive
register_deactivation_hook(__FILE__, function(){
    // keep data by default
});

// Init
add_action('plugins_loaded', function(){
    if (is_admin()) {
        if (class_exists('BHG_Admin')) {
            new BHG_Admin();
        }
    }
    
    if (class_exists('BHG_Shortcodes')) {
        new BHG_Shortcodes();
    }
    
    if (class_exists('BHG_DB')) { 
        BHG_DB::migrate(); 
    }
    
    if (class_exists('BHG_Utils')) {
        BHG_Utils::init_hooks();
    }
    
    if (class_exists('BHG_Menus')) {
        BHG_Menus::init();
    }
});

// Enqueue assets
add_action('admin_enqueue_scripts', function($hook){
    if (strpos($hook, 'bhg') === false) return;
    
    wp_register_style('bhg-admin', BHG_PLUGIN_URL . 'assets/css/admin.css', [], BHG_VERSION);
    wp_register_script('bhg-admin', BHG_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], BHG_VERSION, true);
    wp_enqueue_style('bhg-admin');
    wp_enqueue_script('bhg-admin');
    
    // Enqueue WordPress editor for advertising page
    if ($hook === 'bonus-hunt-guesser_page_bhg-advertising') {
        wp_enqueue_editor();
    }
});

add_action('wp_enqueue_scripts', function(){
    wp_register_style('bhg-public', BHG_PLUGIN_URL . 'assets/css/public.css', [], BHG_VERSION);
    wp_register_script('bhg-public', BHG_PLUGIN_URL . 'assets/js/public.js', ['jquery'], BHG_VERSION, true);
    wp_enqueue_style('bhg-public');
    wp_enqueue_script('bhg-public');
});

// Frontend ads rendering
function bhg_should_show_ad($visibility){
    if ($visibility === 'all') return true;
    if ($visibility === 'logged_in') return is_user_logged_in();
    if ($visibility === 'guests') return !is_user_logged_in();
    if ($visibility === 'affiliates') return is_user_logged_in() && bhg_is_affiliate();
    if ($visibility === 'non_affiliates') return !is_user_logged_in() || !bhg_is_affiliate();
    return true;
}

function bhg_build_ads_query($table, $placement='footer'){
    global $wpdb;
    // Check if 'active' column exists
    $col = $wpdb->get_var( $wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s AND COLUMN_NAME='active'", DB_NAME, $table) );
    if ($col) {
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE active=%d AND placement=%s ORDER BY id DESC", 1, $placement);
    } else {
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE placement=%s ORDER BY id DESC", $placement);
    }
    return $sql;
}

function bhg_render_ads_footer(){
    if (is_admin()) return;
    global $wpdb;
    $table = $wpdb->prefix . 'bhg_ads';
    $ads = $wpdb->get_results(bhg_build_ads_query($table, 'footer'));
    if (!$ads) return;
    echo '<div class="bhg-ads bhg-ads-footer">';
    foreach ($ads as $ad){
        if (!bhg_should_show_ad($ad->visibility)) continue;
        $msg = wpautop(wp_kses_post($ad->message));
        echo '<div class="bhg-ad-item">'.$msg.'</div>';
    }
    echo '</div>';
}
add_action('wp_footer', 'bhg_render_ads_footer');

// === BHG DEMO & SCHEMA (minimal) ===
if (!function_exists('bhg_ensure_tables')) {
    function bhg_ensure_tables() {
        global $wpdb; 
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_bonus_hunts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            title VARCHAR(191) NOT NULL, 
            starting_balance DECIMAL(12,2) NOT NULL DEFAULT 0, 
            num_bonuses INT UNSIGNED NOT NULL DEFAULT 0, 
            prizes TEXT NULL, 
            status VARCHAR(20) NOT NULL DEFAULT 'open', 
            affiliate_site_id BIGINT UNSIGNED NULL, 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            final_balance DECIMAL(12,2) NULL, 
            winner_user_id BIGINT UNSIGNED NULL, 
            winner_diff DECIMAL(12,2) NULL, 
            closed_at DATETIME NULL, 
            PRIMARY KEY (id)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_guesses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            hunt_id BIGINT UNSIGNED NOT NULL, 
            user_id BIGINT UNSIGNED NOT NULL, 
            guess_amount DECIMAL(12,2) NOT NULL, 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            updated_at DATETIME NULL DEFAULT NULL, 
            UNIQUE KEY hunt_user (hunt_id, user_id), 
            KEY hunt_id (hunt_id), 
            KEY user_id (user_id), 
            PRIMARY KEY (id)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_tournaments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            title VARCHAR(191) NOT NULL, 
            period VARCHAR(20) NOT NULL, 
            period_key VARCHAR(20) NOT NULL, 
            status VARCHAR(20) NOT NULL DEFAULT 'active', 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            PRIMARY KEY (id), 
            UNIQUE KEY period_unique (period, period_key)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_translations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            t_key VARCHAR(191) NOT NULL, 
            t_value TEXT NULL, 
            PRIMARY KEY (id), 
            UNIQUE KEY tkey (t_key)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_affiliate_websites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            name VARCHAR(191) NOT NULL, 
            slug VARCHAR(191) NOT NULL, 
            url VARCHAR(255) NULL, 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            PRIMARY KEY (id), 
            UNIQUE KEY slug_unique (slug)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_ads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL, 
            placement VARCHAR(50) NOT NULL DEFAULT 'footer', 
            visibility VARCHAR(50) NOT NULL DEFAULT 'all', 
            active TINYINT(1) NOT NULL DEFAULT 1, 
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            PRIMARY KEY (id)
        ) $charset;");
        
        dbDelta("CREATE TABLE {$wpdb->prefix}bhg_hunt_winners (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            hunt_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hunt_id (hunt_id),
            KEY user_id (user_id)
        ) $charset;");
    }
}

if (!function_exists('bhg_seed_demo_data')) {
    function bhg_seed_demo_data() {
        global $wpdb;
        
        // Empty tables first
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_ads");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_translations");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_tournaments");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_guesses");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_bonus_hunts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_affiliate_websites");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}bhg_hunt_winners");
        
        // Ensure tables exist
        bhg_ensure_tables();
        
        // Create demo users if they don't exist
        foreach (['demo1','demo2','demo3'] as $u) { 
            if (!username_exists($u)) {
                wp_create_user($u, $u.'123', $u.'@example.com');
            }
        }
        
        $u1 = get_user_by('login','demo1'); 
        $u2 = get_user_by('login','demo2'); 
        $u3 = get_user_by('login','demo3');
        
        // Insert affiliate website
        $wpdb->insert($wpdb->prefix.'bhg_affiliate_websites', [
            'name' => 'Demo Site',
            'slug' => 'demo-site',
            'url' => 'https://example.com'
        ]);
        $aff = $wpdb->insert_id;
        
        // Insert bonus hunts
        $wpdb->insert($wpdb->prefix.'bhg_bonus_hunts', [
            'title' => 'Demo Hunt 1',
            'starting_balance' => 1000,
            'num_bonuses' => 10,
            'prizes' => 'Goodies',
            'status' => 'open',
            'affiliate_site_id' => $aff
        ]);
        $h1 = $wpdb->insert_id;
        
        $wpdb->insert($wpdb->prefix.'bhg_bonus_hunts', [
            'title' => 'Demo Hunt 2 (Closed)',
            'starting_balance' => 2000,
            'num_bonuses' => 12,
            'prizes' => 'Swag',
            'status' => 'closed',
            'affiliate_site_id' => $aff,
            'final_balance' => 1450.00,
            'closed_at' => current_time('mysql')
        ]);
        $h2 = $wpdb->insert_id;
        
        // Insert guesses
        if ($u1 && $u2 && $u3) {
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h1,
                'user_id' => $u1->ID,
                'guess_amount' => 1200
            ]);
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h1,
                'user_id' => $u2->ID,
                'guess_amount' => 900
            ]);
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h1,
                'user_id' => $u3->ID,
                'guess_amount' => 1600
            ]);
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h2,
                'user_id' => $u1->ID,
                'guess_amount' => 1500
            ]);
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h2,
                'user_id' => $u2->ID,
                'guess_amount' => 1400
            ]);
            $wpdb->insert($wpdb->prefix.'bhg_guesses', [
                'hunt_id' => $h2,
                'user_id' => $u3->ID,
                'guess_amount' => 1455
            ]);
        }
        
        // Determine winner for closed hunt
        $final = 1450.00; 
        $winner = null; 
        $best = null;
        
        foreach ($wpdb->get_results($wpdb->prepare("SELECT user_id, guess_amount FROM {$wpdb->prefix}bhg_guesses WHERE hunt_id=%d", $h2)) as $r) {
            $d = abs(floatval($r->guess_amount) - $final); 
            if ($best === null || $d < $best) {
                $best = $d;
                $winner = intval($r->user_id);
            } 
        }
        
        if ($winner) { 
            $wpdb->update(
                $wpdb->prefix.'bhg_bonus_hunts', 
                ['winner_user_id' => $winner, 'winner_diff' => $best], 
                ['id' => $h2]
            ); 
            
            // Add to winners table
            $wpdb->insert($wpdb->prefix.'bhg_hunt_winners', [
                'hunt_id' => $h2,
                'user_id' => $winner,
                'position' => 1
            ]);
        }
        
        // Insert tournament
        $wpdb->insert($wpdb->prefix . 'bhg_tournaments', [
            'title' => 'August 2025 Tournament',
            'period' => 'monthly',
            'period_key' => '2025-08',
            'status' => 'active'
        ]);
        
        // Insert demo ad
        $wpdb->insert($wpdb->prefix . 'bhg_ads', [
            'title' => 'Welcome Message',
            'message' => '<p>Welcome to our <strong>Bonus Hunt Guesser</strong> platform!</p><p>Check out our latest tournament and submit your guess for a chance to win amazing prizes.</p>',
            'placement' => 'footer',
            'visibility' => 'all',
            'active' => 1
        ]);
        
        // Insert some translations
        $translations = [
            'tournament_title' => 'Tournament',
            'bonus_hunt' => 'Bonus Hunt',
            'leaderboard' => 'Leaderboard',
            'affiliate' => 'Affiliate',
            'guess_submission' => 'Submit Your Guess'
        ];
        
        foreach ($translations as $key => $value) {
            $wpdb->insert($wpdb->prefix . 'bhg_translations', [
                't_key' => $key,
                't_value' => $value
            ]);
        }
    }
}

/**
 * Demo data installer (only if tables are empty)
 */
function bhg_seed_demo_if_empty(){
    global $wpdb;
    // Check hunts
    $hunts_table = $wpdb->prefix . 'bhg_bonus_hunts';
    $count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$hunts_table}"));
    if ($count > 0){ return; }

    // Seed demo data
    bhg_seed_demo_data();
}

/**
 * Reset demo data
 */
function bhg_reset_demo_and_seed(){
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'bonus-hunt-guesser'));
    }
    
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_guesses");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_hunt_winners");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_bonus_hunts");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_tournaments");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_ads");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_translations");
    $wpdb->query("DELETE FROM {$wpdb->prefix}bhg_affiliate_websites");
    
    bhg_seed_demo_data();
    
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG){
        $u = wp_get_current_user();
        error_log('[BHG DEMO] Demo data reset by admin (user: ' . ($u ? ($u->user_login) : 'unknown') . ')');
    }
}

// Admin notice after activation/reset
add_action('admin_notices', function(){
    if (get_option('bhg_demo_notice')){
        echo '<div class="notice notice-success is-dismissible"><p>Demo data inserted successfully. You can reset it anytime from <strong>BHG Tools</strong>.</p></div>';
        delete_option('bhg_demo_notice');
    }
});

add_action('init', function(){
    load_plugin_textdomain('bonus-hunt-guesser', false, dirname(plugin_basename(__FILE__)) . '/languages/');
});

// Handle demo reset via AJAX
add_action('wp_ajax_bhg_reset_demo', function(){
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    check_ajax_referer('bhg_admin_nonce', 'nonce');
    bhg_reset_demo_and_seed();
    wp_send_json_success(['message' => 'Demo data reset successfully.']);
});

// Add admin notices for BHG pages
add_action('admin_notices', function(){
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'bhg') !== false && current_user_can('manage_options')) {
        $url = admin_url('admin.php?page=bhg-tools');
        echo '<div class="notice notice-info"><p>Need demo data? Visit <a href="'.$url.'">Bonus Hunt → Tools</a> to reset.</p></div>';
    }
});