<?php
// Deprecated: Do not include. Replaced by includes/class-bhg-db.php
<?php
if (!defined('ABSPATH')) { exit; }

function bhg_install_tables() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : 'DEFAULT CHARSET=utf8';

    $hunts = $wpdb->prefix . 'bhg_bonus_hunts';
    $guesses = $wpdb->prefix . 'bhg_guesses';
    $aff = $wpdb->prefix . 'bhg_affiliate_websites';
    $tournaments = $wpdb->prefix . 'bhg_tournaments';
    $ads = $wpdb->prefix . 'bhg_advertisements';

    $sql = array();

    $sql[] = "CREATE TABLE {$hunts} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        starting_balance decimal(12,2) NOT NULL DEFAULT 0.00,
        num_bonuses int(11) NOT NULL DEFAULT 0,
        prizes text NULL,
        affiliate_site_id bigint(20) unsigned NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        final_balance decimal(12,2) NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) ENGINE=InnoDB {$charset_collate};";

    $sql[] = "CREATE TABLE {$guesses} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        hunt_id bigint(20) unsigned NOT NULL,
        guess_value decimal(12,2) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NULL,
        UNIQUE KEY unique_user_hunt (user_id, hunt_id),
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$charset_collate};";

    $sql[] = "CREATE TABLE {$aff} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(190) NOT NULL,
        slug varchar(190) NOT NULL,
        url varchar(255) NULL,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) ENGINE=InnoDB {$charset_collate};";

    $sql[] = "CREATE TABLE {$tournaments} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(190) NOT NULL,
        type varchar(20) NOT NULL,
        from_date date NOT NULL,
        to_date date NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$charset_collate};";

    $sql[] = "CREATE TABLE {$ads} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        message text NOT NULL,
        link varchar(255) NULL,
        placement varchar(50) NOT NULL DEFAULT 'footer',
        visibility varchar(50) NOT NULL DEFAULT 'all',
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$charset_collate};";

    foreach ($sql as $q) { dbDelta($q); }
}

// Advertisement renderer
add_action('wp_footer', function(){
    if (is_admin()) return;
    global $wpdb;
    $table = $wpdb->prefix . 'bhg_advertisements';
    if ($wpdb->get_results("SELECT * FROM `" . $hunt_id . "`");
    if(!$hunt || !$hunt->is_active){
        wp_safe_redirect( add_query_arg('bhg_msg','closed', wp_get_referer() ?: home_url('/')) );
        exit;
    }
    $allow_edit = get_option('bhg_allow_guess_alteration','1') === '1';
    $exists = $wpdb->get_results("SELECT * FROM `" . $user_id . "`");
    if($exists){
        if(!$allow_edit){
            wp_safe_redirect( add_query_arg('bhg_msg','noedit', wp_get_referer() ?: home_url('/')) );
            exit;
        }
        $wpdb->update($guesses, array(
            'guess_value' => $guess,
            'updated_at' => current_time('mysql')
        ), array('id' => $exists->id));
    } else {
        $wpdb->insert($guesses, array(
            'user_id' => $user_id,
            'hunt_id' => $hunt_id,
            'guess_value' => $guess,
            'created_at' => current_time('mysql')
        ));
    }
    wp_safe_redirect( add_query_arg('bhg_msg','saved', wp_get_referer() ?: home_url('/')) );
    exit;
}

// When closing a hunt, compute winners and notify
function bhg_compute_and_notify_winners($hunt_id){
    global $wpdb;
    $hunts = $wpdb->prefix.'bhg_bonus_hunts';
    $guesses = $wpdb->prefix.'bhg_guesses';

    $hunt = $wpdb->get_results("SELECT * FROM `" . $hunt_id . "`");
    if(!$hunt || is_null($hunt->final_balance)) return;

    $rows = $wpdb->get_results("SELECT * FROM `" . $hunt_id . "`");
    if(!$rows) return;
    // closest by absolute difference
    usort($rows, function($a,$b) use ($hunt){
        $da = abs(floatval($a->guess_value) - floatval($hunt->final_balance));
        $db = abs(floatval($b->guess_value) - floatval($hunt->final_balance));
        if ($da == $db) return 0;
        return ($da < $db) ? -1 : 1;
    });
    // Take top 3 for emailing (customize as needed)
    $winners = array_slice($rows, 0, 3);
    foreach($winners as $pos => $row){
        $user = get_userdata($row->user_id);
        if(!$user) continue;
        $subject = sprintf(__('You placed #%d in %s','bonus-hunt-guesser'), $pos+1, $hunt->title);
        $body = sprintf("Hello %s,\n\nGood news! You placed #%d in the bonus hunt '%s'.\nFinal balance: €%s\nYour guess: €%s\n\nWe will contact you with prize details.\n\nThanks,\nBonus Hunt Team",
            $user->display_name, $pos+1, $hunt->title, number_format_i18n($hunt->final_balance,2), number_format_i18n($row->guess_value,2));
        wp_mail($user->user_email, $subject, $body);
    }
}
