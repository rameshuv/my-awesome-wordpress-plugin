<?php
if (!defined('ABSPATH')) exit;

/**
 * BHG Shortcodes
 * Implements all required frontend shortcodes with full DB wiring.
 */

// [bhg_user_profile] — profile only (no forms inside)
add_shortcode('bhg_user_profile', function($atts) {
    if (!is_user_logged_in()) {
        return '<div class="bhg-box">' . esc_html__('Please log in to view your profile.', 'bonus-hunt-guesser') . '</div>';
    }
    
    $u = wp_get_current_user();
    $uid = get_current_user_id();

    // Basic profile
    ob_start(); ?>
    <div class="bhg-box">
        <h3><?php echo esc_html($u->display_name ?: $u->user_login); ?></h3>
        <p><?php echo esc_html($u->user_email); ?></p>
        <?php if (function_exists('bhg_get_affiliate_sites')): 
            $sites = bhg_get_affiliate_sites($uid);
            if (!empty($sites)): ?>
                <h4><?php esc_html_e('Affiliate Sites', 'bonus-hunt-guesser'); ?></h4>
                <ul class="bhg-list">
                <?php foreach ($sites as $s): ?>
                    <li><?php echo esc_html(is_array($s) ? ($s['name'] ?? $s['url'] ?? '') : (string)$s); ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; 
        endif; ?>

        <?php
        // Hunts participated
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT h.id, h.title, h.status 
            FROM {$wpdb->prefix}bhg_bonus_hunts h 
            INNER JOIN {$wpdb->prefix}bhg_guesses g ON g.hunt_id = h.id 
            WHERE g.user_id = %d 
            ORDER BY h.id DESC", 
            $uid
        ));
        
        if ($rows): ?>
            <h4><?php esc_html_e('Your Hunts', 'bonus-hunt-guesser'); ?></h4>
            <ul class="bhg-list">
            <?php foreach ($rows as $r): ?>
                <li><?php echo esc_html($r->title ?: ('#' . $r->id)); ?> — <?php echo esc_html(ucfirst($r->status)); ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

// [bhg_active_hunt] — show all open hunts with description, prizes and user guesses
add_shortcode('bhg_active_hunt', function($atts) {
    global $wpdb;
    $hunts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts 
        WHERE status = %s 
        ORDER BY id DESC", 
        'open'
    ));
    
    if (!$hunts) {
        return '<div class="bhg-empty">' . esc_html__('No active bonus hunts right now.', 'bonus-hunt-guesser') . '</div>';
    }

    $uid = get_current_user_id();
    ob_start();
    
    foreach ($hunts as $hunt):
        $desc = isset($hunt->description) ? $hunt->description : '';
        $prizes = isset($hunt->prizes) ? $hunt->prizes : ''; ?>
        <div class="bhg-box">
            <h3><?php echo esc_html($hunt->title ?: ('#' . $hunt->id)); ?></h3>
            <p><em><?php esc_html_e('Starting Balance', 'bonus-hunt-guesser'); ?>:</em> 
            <?php echo esc_html(function_exists('wc_price') ? wc_price($hunt->starting_balance) : number_format_i18n($hunt->starting_balance, 2)); ?></p>
            
            <?php if ($desc): ?>
                <p><?php echo esc_html($desc); ?></p>
            <?php endif; ?>
            
            <?php if ($prizes): ?>
                <p><strong><?php esc_html_e('Prizes', 'bonus-hunt-guesser'); ?>:</strong> <?php echo esc_html($prizes); ?></p>
            <?php endif; ?>

            <?php if ($uid): 
                $user_guess = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bhg_guesses 
                    WHERE user_id = %d AND hunt_id = %d", 
                    $uid, 
                    $hunt->id
                ));
                
                if ($user_guess): ?>
                    <p><strong><?php esc_html_e('Your guess', 'bonus-hunt-guesser'); ?>:</strong> 
                    <?php echo esc_html(number_format_i18n($user_guess->guess_value, 2)); ?></p>
                <?php else: ?>
                    <form class="bhg-guess-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <input type="hidden" name="action" value="bhg_submit_guess">
                        <input type="hidden" name="hunt_id" value="<?php echo intval($hunt->id); ?>">
                        <div class="bhg-field">
                            <label><?php esc_html_e('Your Guess (0-100,000)', 'bonus-hunt-guesser'); ?></label>
                            <input type="number" name="guess_value" step="0.01" min="0" max="100000" required>
                        </div>
                        <div class="bhg-actions">
                            <button type="submit" class="bhg-btn-submit"><?php esc_html_e('Submit Guess', 'bonus-hunt-guesser'); ?></button>
                        </div>
                        <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>
                    </form>
                <?php endif; ?>

                <?php
                // Overview of user's submitted guesses for this hunt
                $guesses = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bhg_guesses 
                    WHERE user_id = %d AND hunt_id = %d 
                    ORDER BY id DESC", 
                    $uid, 
                    $hunt->id
                ));
                
                if ($guesses): ?>
                    <h4><?php esc_html_e('Your guesses', 'bonus-hunt-guesser'); ?></h4>
                    <table class="bhg-leaderboard">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Guess', 'bonus-hunt-guesser'); ?></th>
                                <th><?php esc_html_e('Date', 'bonus-hunt-guesser'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guesses as $g): ?>
                                <tr>
                                    <td><?php echo esc_html(number_format_i18n($g->guess_value, 2)); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($g->created_at))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach;
    
    return ob_get_clean();
});

// [bhg_guess_form] — dedicated form (handles multiple active hunts with dropdown)
add_shortcode('bhg_guess_form', function($atts) {
    if (!is_user_logged_in()) {
        return '<div class="bhg-box">' . esc_html__('Please log in to submit a guess.', 'bonus-hunt-guesser') . '</div>';
    }
    
    global $wpdb;
    $hunts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bhg_bonus_hunts 
        WHERE status = %s 
        ORDER BY id DESC", 
        'open'
    ));
    
    if (!$hunts) {
        return '<div class="bhg-empty">' . esc_html__('No active hunts to guess for.', 'bonus-hunt-guesser') . '</div>';
    }

    $selected = isset($_GET['hunt_id']) ? intval($_GET['hunt_id']) : (count($hunts) === 1 ? intval($hunts[0]->id) : 0);
    ob_start(); ?>
    
    <form class="bhg-guess-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="bhg_submit_guess">
        
        <?php if (count($hunts) > 1): ?>
            <div class="bhg-field">
                <label><?php esc_html_e('Choose Hunt', 'bonus-hunt-guesser'); ?></label>
                <select name="hunt_id" required>
                    <option value=""><?php esc_html_e('Select a hunt', 'bonus-hunt-guesser'); ?></option>
                    <?php foreach ($hunts as $h): ?>
                        <option value="<?php echo intval($h->id); ?>" <?php selected($selected, intval($h->id)); ?>>
                            <?php echo esc_html($h->title ?: ('#' . $h->id)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else: ?>
            <input type="hidden" name="hunt_id" value="<?php echo intval($hunts[0]->id); ?>">
        <?php endif; ?>
        
        <div class="bhg-field">
            <label><?php esc_html_e('Your Guess (0-100,000)', 'bonus-hunt-guesser'); ?></label>
            <input type="number" name="guess_value" step="0.01" min="0" max="100000" required>
        </div>
        
        <div class="bhg-actions">
            <button type="submit" class="bhg-btn-submit"><?php esc_html_e('Submit Guess', 'bonus-hunt-guesser'); ?></button>
        </div>
        
        <?php wp_nonce_field('bhg_submit_guess', 'bhg_nonce'); ?>
    </form>
    
    <?php
    return ob_get_clean();
});

// [bhg_tournaments] — list active (default) and closed with history
add_shortcode('bhg_tournaments', function($atts) {
    global $wpdb;
    $status = (isset($_GET['status']) && $_GET['status'] === 'closed') ? 'closed' : 'active';
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}bhg_tournaments 
        WHERE status = %s 
        ORDER BY id DESC", 
        $status
    ));

    ob_start(); ?>
    
    <div class="bhg-box">
        <form method="get" class="bhg-field">
            <?php foreach ($_GET as $k => $v): 
                if ($k === 'status') continue; ?>
                <input type="hidden" name="<?php echo esc_attr($k); ?>" value="<?php echo esc_attr($v); ?>">
            <?php endforeach; ?>
            
            <label><?php esc_html_e('Filter', 'bonus-hunt-guesser'); ?></label>
            <select name="status" onchange="this.form.submit()">
                <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Active', 'bonus-hunt-guesser'); ?></option>
                <option value="closed" <?php selected($status, 'closed'); ?>><?php esc_html_e('Closed', 'bonus-hunt-guesser'); ?></option>
            </select>
        </form>

        <?php if ($rows): ?>
            <ul class="bhg-list">
                <?php foreach ($rows as $r): ?>
                    <li><?php echo esc_html($r->title ?: ('#' . $r->id)); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php esc_html_e('No tournaments found.', 'bonus-hunt-guesser'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
});

// [bhg_user_guesses] — list guesses by current user
add_shortcode('bhg_user_guesses', function($atts) {
    if (!is_user_logged_in()) {
        return '<div class="bhg-box">' . esc_html__('Please log in.', 'bonus-hunt-guesser') . '</div>';
    }
    
    global $wpdb;
    $uid = get_current_user_id();
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT g.*, h.title as hunt_title, h.status as hunt_status 
        FROM {$wpdb->prefix}bhg_guesses g 
        LEFT JOIN {$wpdb->prefix}bhg_bonus_hunts h ON h.id = g.hunt_id 
        WHERE g.user_id = %d 
        ORDER BY g.id DESC", 
        $uid
    ));
    
    ob_start(); ?>
    
    <div class="bhg-box">
        <h3><?php esc_html_e('Your Guesses', 'bonus-hunt-guesser'); ?></h3>
        
        <?php if ($rows): ?>
            <table class="bhg-leaderboard">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Hunt', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Guess', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Status', 'bonus-hunt-guesser'); ?></th>
                        <th><?php esc_html_e('Date', 'bonus-hunt-guesser'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?php echo esc_html($r->hunt_title ?: ('#' . $r->hunt_id)); ?></td>
                            <td><?php echo esc_html(number_format_i18n($r->guess_value, 2)); ?></td>
                            <td><?php echo esc_html(ucfirst($r->hunt_status ?: 'open')); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($r->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php esc_html_e('No guesses yet.', 'bonus-hunt-guesser'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
});

// [bhg_winner_notifications] — winners of closed hunts
add_shortcode('bhg_winner_notifications', function($atts) {
    global $wpdb;
    
    // If winners table exists, use it; otherwise show closed hunts only
    $winners_table = $wpdb->prefix . 'bhg_hunt_winners';
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s", 
        DB_NAME, 
        $winners_table
    ));
    
    if ($table_exists) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT h.id as hunt_id, h.title, w.user_id, w.position 
            FROM {$wpdb->prefix}bhg_bonus_hunts h 
            INNER JOIN {$wpdb->prefix}bhg_hunt_winners w ON w.hunt_id = h.id 
            WHERE h.status = %s 
            ORDER BY h.id DESC", 
            'closed'
        ));
    } else {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id as hunt_id, title 
            FROM {$wpdb->prefix}bhg_bonus_hunts 
            WHERE status = %s 
            ORDER BY id DESC", 
            'closed'
        ));
    }

    ob_start(); ?>
    
    <div class="bhg-box">
        <h3><?php esc_html_e('Winner Notifications', 'bonus-hunt-guesser'); ?></h3>
        
        <?php if ($rows): ?>
            <ul class="bhg-list">
            <?php foreach ($rows as $r): 
                $u = isset($r->user_id) && $r->user_id ? get_userdata($r->user_id) : null; ?>
                <li>
                    <strong><?php echo esc_html($r->title ?: ('#' . $r->hunt_id)); ?></strong>
                    
                    <?php if ($u): ?>
                        — <?php echo esc_html($u->display_name ?: $u->user_login); ?>
                        <?php if (!empty($r->position)): ?>
                            (<?php echo intval($r->position); ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        — <?php esc_html_e('Results pending', 'bonus-hunt-guesser'); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p><?php esc_html_e('No winner notifications yet.', 'bonus-hunt-guesser'); ?></p>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
});