<?php
if (!defined('ABSPATH')) exit;

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

global $wpdb;
$t_table = $wpdb->prefix . 'bhg_tournaments';
$w_table = $wpdb->prefix . 'bhg_tournament_wins';
$users_table = $wpdb->prefix . 'users';

// Function to get current period identifier
function get_current_period($type) {
    switch ($type) {
        case 'weekly':
            return date('Y-W');
        case 'monthly':
            return date('Y-m');
        case 'yearly':
            return date('Y');
        default:
            return '';
    }
}

// Function to display tournament table
function display_tournament_table($type) {
    global $wpdb;
    
    $current_period = get_current_period($type);
    $table_name = $wpdb->prefix . 'bhg_tournaments';
    $wins_table = $wpdb->prefix . 'bhg_tournament_wins';
    $users_table = $wpdb->prefix . 'users';
    
    // Check if tournament tables exist
    $tournament_table_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name)) === $table_name);
    $wins_table_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wins_table)) === $wins_table);
    
    if (!$tournament_table_exists || !$wins_table_exists) {
        echo '<p>' . esc_html__('Tournament system not initialized. Please activate the plugin again.', 'bonus-hunt-guesser') . '</p>';
        return;
    }
    
    // Get current tournament
    $tournament = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE type = %s AND period = %s",
        $type, $current_period
    ));
    
    if (!$tournament) {
        echo '<p>' . esc_html__('No tournament data available.', 'bonus-hunt-guesser') . '</p>';
        return;
    }
    
    // Get tournament wins
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT w.user_id, u.user_login, w.wins 
         FROM $wins_table w 
         JOIN $users_table u ON w.user_id = u.ID 
         WHERE w.tournament_id = %d 
         ORDER BY w.wins DESC, w.last_win_date ASC 
         LIMIT 50",
        $tournament->id
    ));
    
    if (empty($results)) {
        echo '<p>' . esc_html__('No data available.', 'bonus-hunt-guesser') . '</p>';
        return;
    }
    
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>
        <th>' . esc_html__('Position', 'bonus-hunt-guesser') . '</th>
        <th>' . esc_html__('Username', 'bonus-hunt-guesser') . '</th>
        <th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th>
    </tr></thead>';
    echo '<tbody>';
    
    $position = 1;
    foreach ($results as $row) {
        echo '<tr>
            <td>' . $position . '</td>
            <td>' . esc_html($row->user_login) . '</td>
            <td>' . $row->wins . '</td>
        </tr>';
        $position++;
    }
    
    echo '</tbody></table>';
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>
    
    <p><?php esc_html_e('View current standings by period.', 'bonus-hunt-guesser'); ?></p>
    
    <div class="tab-wrapper">
        <h2 class="nav-tab-wrapper">
            <a href="#weekly" class="nav-tab nav-tab-active"><?php esc_html_e('Weekly', 'bonus-hunt-guesser'); ?></a>
            <a href="#monthly" class="nav-tab"><?php esc_html_e('Monthly', 'bonus-hunt-guesser'); ?></a>
            <a href="#yearly" class="nav-tab"><?php esc_html_e('Yearly', 'bonus-hunt-guesser'); ?></a>
        </h2>
        
        <div id="weekly" class="tab-content">
            <h3><?php esc_html_e('Weekly Tournament Standings', 'bonus-hunt-guesser'); ?></h3>
            <?php display_tournament_table('weekly'); ?>
        </div>
        
        <div id="monthly" class="tab-content" style="display:none;">
            <h3><?php esc_html_e('Monthly Tournament Standings', 'bonus-hunt-guesser'); ?></h3>
            <?php display_tournament_table('monthly'); ?>
        </div>
        
        <div id="yearly" class="tab-content" style="display:none;">
            <h3><?php esc_html_e('Yearly Tournament Standings', 'bonus-hunt-guesser'); ?></h3>
            <?php display_tournament_table('yearly'); ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.nav-tab-wrapper a').click(function(e) {
        e.preventDefault();
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });
});
</script>