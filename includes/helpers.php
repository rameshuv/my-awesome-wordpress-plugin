<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function bhg_log($message) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) return;
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    error_log('[BHG] ' . $message);
}

function bhg_current_user_id() {
    $uid = get_current_user_id();
    return $uid ? intval($uid) : 0;
}

function bhg_slugify($text) {
    $text = sanitize_title($text);
    if (!$text) $text = uniqid('bhg');
    return $text;
}

// Get admin capability for BHG plugin
function bhg_admin_cap() {
    return apply_filters('bhg_admin_capability', 'manage_options');
}

// Smart login redirect back to referring page
add_filter('login_redirect', function($redirect_to, $requested_redirect_to, $user){
    $r = isset($_REQUEST['bhg_redirect']) ? $_REQUEST['bhg_redirect'] : '';
    if (!empty($r)) {
        $safe = esc_url_raw($r);
        $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $r_host = wp_parse_url($safe, PHP_URL_HOST);
        if (!$r_host || $r_host === $home_host) {
            return $safe;
        }
    }
    return $redirect_to;
}, 10, 3);

// Safe checks for query conditionals (avoid early usage)
function bhg_is_frontend() {
    return !is_admin() && !wp_doing_ajax() && !wp_doing_cron();
}

if (!function_exists('bhg_t')) {
    function bhg_t($key, $default = '') {
        global $wpdb;
        static $cache = [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $table = $wpdb->prefix . 'bhg_translations';
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT tvalue FROM $table WHERE tkey = %s", $key)
        );

        if ($row && isset($row->tvalue)) {
            $cache[$key] = $row->tvalue;
            return $row->tvalue;
        }

        return $default;
    }
}

// Helper function to format currency
function bhg_format_currency($amount) {
    return '€' . number_format($amount, 2);
}

// Helper function to validate guess value
function bhg_validate_guess($guess) {
    $settings = get_option('bhg_settings', []);
    $min_guess = $settings['min_guess'] ?? 0;
    $max_guess = $settings['max_guess'] ?? 100000;

    if (!is_numeric($guess)) {
        return false;
    }

    $guess = floatval($guess);
    return ($guess >= $min_guess && $guess <= $max_guess);
}

// Helper function to get user display name with affiliate indicator
function bhg_get_user_display_name($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return __('Unknown User', 'bonus-hunt-guesser');
    }

    $display_name = $user->display_name ?: $user->user_login;
    $is_affiliate = get_user_meta($user_id, 'bhg_affiliate_status', true);

    if ($is_affiliate) {
        $display_name .= ' <span class="bhg-affiliate-indicator" title="' . esc_attr__('Affiliate User', 'bonus-hunt-guesser') . '">★</span>';
    }

    return $display_name;
}


if (!function_exists('bhg_is_user_affiliate')) {
    function bhg_is_user_affiliate($user_id) {
        $val = get_user_meta($user_id, 'bhg_is_affiliate', true);
        return $val === '1' || $val === 1 || $val === true || $val === 'yes';
    }
}
if (!function_exists('bhg_get_user_affiliate_sites')) {
    function bhg_get_user_affiliate_sites($user_id) {
        $ids = get_user_meta($user_id, 'bhg_affiliate_sites', true);
        if (is_array($ids)) return array_map('absint', $ids);
        if (is_string($ids) && strlen($ids)) {
            return array_map('absint', array_filter(array_map('trim', explode(',', $ids))));
        }
        return array();
    }
}
if (!function_exists('bhg_set_user_affiliate_sites')) {
    function bhg_set_user_affiliate_sites($user_id, $site_ids) {
        $clean = array();
        if (is_array($site_ids)) {
            foreach ($site_ids as $sid) { $sid = absint($sid); if ($sid) $clean[] = $sid; }
        }
        update_user_meta($user_id, 'bhg_affiliate_sites', $clean);
    }
}


if (!function_exists('bhg_is_user_affiliate_for_site')) {
    function bhg_is_user_affiliate_for_site($user_id, $site_id) {
        if (!$site_id) return bhg_is_user_affiliate($user_id);
        $sites = bhg_get_user_affiliate_sites($user_id);
        return in_array(absint($site_id), array_map('absint', (array)$sites), true);
    }
}

if (!function_exists('bhg_render_affiliate_dot')) {
    function bhg_render_affiliate_dot($user_id, $hunt_affiliate_site_id = 0){
        $is_aff = bhg_is_user_affiliate_for_site($user_id, $hunt_affiliate_site_id);
        $cls   = $is_aff ? 'bhg-aff-green' : 'bhg-aff-red';
        $label = $is_aff ? esc_attr__('Affiliate', 'bonus-hunt-guesser') : esc_attr__('Non-affiliate', 'bonus-hunt-guesser');
        return '<span class="bhg-aff-dot ' . esc_attr($cls) . '" aria-label="' . $label . '"></span>';
    }
}


function bhg_render_ads($placement = 'footer', $hunt_id = 0) {
    global $wpdb;
    $tbl = $wpdb->prefix . 'bhg_ads';
    $placement = sanitize_text_field($placement);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT content, link_url, visible_to FROM {$tbl} WHERE active=1 AND placement=%s ORDER BY id DESC", $placement));
    $hunt_site_id = 0;
    if ($hunt_id) {
        $hunt_site_id = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT affiliate_site_id FROM {$wpdb->prefix}bhg_bonus_hunts WHERE id=%d", $hunt_id)
        );
    }
    if (!$rows) return '';

    $out = '<div class="bhg-ads bhg-ads-' . esc_attr($placement) . '">';
    foreach ($rows as $r) {
        $vis = $r->visible_to ?: 'all';
        $show = false;
        if ($vis === 'all') $show = true;
        elseif ($vis === 'guests' && !is_user_logged_in()) $show = true;
        elseif ($vis === 'logged_in' && is_user_logged_in()) $show = true;
        elseif ($vis === 'affiliate' && is_user_logged_in()) {
            $uid = get_current_user_id();
            $show = $hunt_site_id > 0
                ? bhg_is_user_affiliate_for_site($uid, $hunt_site_id)
                : (bool) get_user_meta($uid, 'bhg_affiliate_status', true);
        }
        if (!$show) continue;
        $msg  = wp_kses_post($r->content);
        $link = $r->link_url ? esc_url($r->link_url) : '';
        $out .= '<div class="bhg-ad" style="margin:10px 0;padding:10px;border:1px solid #e2e8f0;border-radius:6px;">';
        if ($link) { $out .= '<a href="' . $link . '">'; }
        $out .= $msg;
        if ($link) { $out .= '</a>'; }
        $out .= '</div>';
    }
    $out .= '</div>';
    return $out;
}

// Demo reset and seed data
if (!function_exists('bhg_reset_demo_and_seed')) {
    function bhg_reset_demo_and_seed() {
        global $wpdb;

        $p = $wpdb->prefix;

        // Ensure tables exist before touching
        $tables = array(
            "{$p}bhg_guesses",
            "{$p}bhg_bonus_hunts",
            "{$p}bhg_tournaments",
            "{$p}bhg_tournament_results",
            "{$p}bhg_hunt_winners",
            "{$p}bhg_ads",
            "{$p}bhg_translations",
            "{$p}bhg_affiliate_websites",
        );

        // Soft delete (DELETE) to preserve schema even if user lacks TRIGGER/TRUNCATE
        foreach ($tables as $tbl) {
            // Skip translations/affiliates if table missing
            $exists = $wpdb->get_var( $wpdb->prepare("SHOW TABLES LIKE %s", $tbl) );
            if ($exists !== $tbl) continue;
            if (strpos($tbl, 'bhg_translations') !== false || strpos($tbl, 'bhg_affiliate_websites') !== false) {
                // keep existing; we'll upsert below
                continue;
            }
            $wpdb->query( "DELETE FROM `{$tbl}`" );
        }

        // Seed affiliate websites (idempotent upsert by slug)
        $aff_tbl = "{$p}bhg_affiliate_websites";
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $aff_tbl)) === $aff_tbl) {
            $affs = array(
                array('name'=>'Main Site','slug'=>'main-site','url'=>home_url('/')),
                array('name'=>'Casino Hub','slug'=>'casino-hub','url'=>home_url('/casino')),
            );
            foreach ($affs as $a) {
                $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM `{$aff_tbl}` WHERE slug=%s", $a['slug']));
                if ($id) {
                    $wpdb->update($aff_tbl, $a, array('id'=>(int)$id), array('%s','%s','%s'), array('%d'));
                } else {
                    $wpdb->insert($aff_tbl, $a, array('%s','%s','%s'));
                }
            }
        }

        // Seed hunts
        $hunts_tbl = "{$p}bhg_bonus_hunts";
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hunts_tbl)) === $hunts_tbl) {
            $now = current_time('mysql', 1);
            // Open hunt
            $wpdb->insert($hunts_tbl, array(
                'title' => __('Bonus Hunt – Demo Open', 'bonus-hunt-guesser'),
                'starting_balance' => 2000.00,
                'num_bonuses' => 10,
                'prizes' => __('Gift card + swag', 'bonus-hunt-guesser'),
                'status' => 'open',
                'affiliate_site_id' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$p}bhg_affiliate_websites ORDER BY id ASC LIMIT 1" ) ),
                'created_at' => $now,
                'updated_at' => $now,
            ), array('%s','%f','%d','%s','%s','%d','%s','%s'));
            $open_id = (int) $wpdb->insert_id;

            // Closed hunt with winner
            $wpdb->insert($hunts_tbl, array(
                'title' => __('Bonus Hunt – Demo Closed', 'bonus-hunt-guesser'),
                'starting_balance' => 1500.00,
                'num_bonuses' => 8,
                'prizes' => __('T-shirt', 'bonus-hunt-guesser'),
                'status' => 'closed',
                'final_balance' => 1875.50,
                'winner_user_id' => 1,
                'winner_diff' => 12.50,
                'closed_at' => gmdate('Y-m-d H:i:s', time() - 86400),
                'created_at' => $now,
                'updated_at' => $now,
            ), array('%s','%f','%d','%s','%s','%f','%d','%f','%s','%s','%s'));
            $closed_id = (int) $wpdb->insert_id;

            // Seed guesses for open hunt
            $g_tbl = "{$p}bhg_guesses";
            $users = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT 5" ) );
            if (empty($users)) { $users = array(1); }
            $val = 2100.00;
            foreach ($users as $uid) {
                $wpdb->insert($g_tbl, array(
                    'hunt_id' => $open_id,
                    'user_id' => (int)$uid,
                    'guess' => $val,
                    'created_at' => $now,
                    'updated_at' => $now,
                ), array('%d','%d','%f','%s','%s'));
                $val += 23.45;
            }
        }

        // Tournaments + results based on closed hunts
        $t_tbl = "{$p}bhg_tournaments";
        $r_tbl = "{$p}bhg_tournament_results";
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_tbl)) === $t_tbl) {
            // Wipe results only
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $r_tbl)) === $r_tbl) {
                $wpdb->query( "DELETE FROM `{$r_tbl}`" );
            }
            $closed = $wpdb->get_results( $wpdb->prepare( "SELECT winner_user_id, closed_at FROM {$hunts_tbl} WHERE status='closed' AND winner_user_id IS NOT NULL" ) );
            foreach ($closed as $row) {
                $ts = $row->closed_at ? strtotime($row->closed_at) : time();
                $isoYear = date('o', $ts);
                $week = str_pad(date('W', $ts), 2, '0', STR_PAD_LEFT);
                $weekKey = $isoYear . '-W' . $week;
                $monthKey = date('Y-m', $ts);
                $yearKey = date('Y', $ts);

                $ensure = function($type, $period) use ($wpdb, $t_tbl) {
                    $now = current_time('mysql', 1);
                    $start = $now; $end = $now;
                    if ($type === 'weekly') {
                        $start = date('Y-m-d', strtotime($period . '-1'));
                        $end   = date('Y-m-d', strtotime($period . '-7'));
                    } elseif ($type === 'monthly') {
                        $start = $period . '-01';
                        $end   = date('Y-m-t', strtotime($start));
                    } elseif ($type === 'yearly') {
                        $start = $period . '-01-01';
                        $end   = $period . '-12-31';
                    }
                    $id = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$t_tbl} WHERE type=%s AND start_date=%s AND end_date=%s",
                        $type,
                        $start,
                        $end
                    ));
                    if ($id) return (int) $id;
                    $wpdb->insert($t_tbl, array(
                        'type'=>$type,'start_date'=>$start,'end_date'=>$end,'status'=>'active',
                        'created_at'=>$now,'updated_at'=>$now
                    ), array('%s','%s','%s','%s','%s','%s'));
                    return (int)$wpdb->insert_id;
                };

                $uids = (int)$row->winner_user_id;
                foreach (array(
                    $ensure('weekly', $weekKey),
                    $ensure('monthly', $monthKey),
                    $ensure('yearly', $yearKey)
                ) as $tid) {
                    if ($tid && $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $r_tbl)) === $r_tbl) {
                        $wpdb->query($wpdb->prepare(
                            "INSERT INTO {$r_tbl} (tournament_id, user_id, wins) VALUES (%d, %d, 1)
                             ON DUPLICATE KEY UPDATE wins = wins + 1",
                            $tid, $uids
                        ));
                    }
                }
            }
        }

        // Seed translations (upsert)
        $tr_tbl = "{$p}bhg_translations";
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tr_tbl)) === $tr_tbl) {
            $pairs = array(
                'email_results_title' => 'The Bonus Hunt has been closed!',
                'email_final_balance' => 'Final Balance',
                'email_winner' => 'Winner',
                'email_congrats_subject' => 'Congratulations! You won the Bonus Hunt',
                'email_congrats_body' => 'You had the closest guess. Great job!',
                'email_hunt' => 'Hunt',
            );
            foreach ($pairs as $k=>$v) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tr_tbl} WHERE tkey=%s", $k));
                if ($exists) {
                    $wpdb->update($tr_tbl, array('tvalue'=>$v), array('id'=>$exists), array('%s'), array('%d'));
                } else {
                    $wpdb->insert($tr_tbl, array('tkey'=>$k, 'tvalue'=>$v), array('%s','%s'));
                }
            }
        }

        // Seed ads
        $ads_tbl = "{$p}bhg_ads";
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $ads_tbl)) === $ads_tbl) {
            $now = current_time('mysql', 1);
            $wpdb->insert($ads_tbl, array(
                'title'        => '',
                'content'      => '<strong>Play responsibly.</strong> <a href="'.esc_url(home_url('/promo')).'">See promo</a>',
                'link_url'     => '',
                'placement'    => 'footer',
                'visible_to'   => 'all',
                'target_pages' => '',
                'active'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ), array('%s','%s','%s','%s','%s','%s','%d','%s','%s'));
        }

        return true;
    }
}
