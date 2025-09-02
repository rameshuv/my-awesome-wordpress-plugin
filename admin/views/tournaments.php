<?php
if (!defined('ABSPATH')) exit;

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'bonus-hunt-guesser'));
}

// Process any success messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>

<div class="wrap">
    <h1><?php esc_html_e('Tournaments', 'bonus-hunt-guesser'); ?></h1>

    <?php if ($message === 'rebuild_success'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Tournament standings rebuilt successfully.', 'bonus-hunt-guesser'); ?></p>
        </div>
    <?php elseif ($message === 'rebuild_error'): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('Error rebuilding tournament standings. Please try again.', 'bonus-hunt-guesser'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:16px 0;">
        <input type="hidden" name="action" value="bhg_rebuild_tournaments">
        <?php wp_nonce_field('bhg_rebuild_tournaments', 'bhg_rebuild_nonce'); ?>
        <button class="button button-primary"><?php esc_html_e('Rebuild Tournament Standings', 'bonus-hunt-guesser'); ?></button>
    </form>

    <?php
    global $wpdb;
    $t_table = $wpdb->prefix . 'bhg_tournaments';
    $r_table = $wpdb->prefix . 'bhg_tournament_results';

    $periods = ['weekly', 'monthly', 'yearly'];
    
    foreach ($periods as $period):
        // Sanitize period value
        $sanitized_period = sanitize_text_field($period);
        
        $tournaments = $wpdb->get_results("SELECT * FROM `" . $tournament_id . "`");
            
            if (!$results) { 
                echo '<p>' . esc_html__('No results yet.', 'bonus-hunt-guesser') . '</p>'; 
                continue; 
            }
            
            echo '<table class="widefat striped"><thead><tr>
                <th>#</th>
                <th>' . esc_html__('User', 'bonus-hunt-guesser') . '</th>
                <th>' . esc_html__('Wins', 'bonus-hunt-guesser') . '</th>
            </tr></thead><tbody>';
            
            $rank = 1;
            foreach ($results as $result) {
                $username = esc_html($result->user_login);
                $wins = intval($result->wins);
                
                echo '<tr>
                    <td>' . $rank . '</td>
                    <td>' . $username . '</td>
                    <td>' . $wins . '</td>
                </tr>';
                
                $rank++;
            }
            
            echo '</tbody></table>';
        endforeach;
    endforeach;
    ?>
</div>