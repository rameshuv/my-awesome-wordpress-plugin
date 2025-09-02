<?php if(!defined('ABSPATH')) exit;
/**
 * BHG Shortcodes: best guessers and tournament leaderboards
 */
class BHG_Shortcodes {
    public static function init(){
        add_shortcode('bhg_best_guessers', ['BHG_Shortcodes','render_best_guessers']);
        add_shortcode('bhg_tournament_leaderboard', ['BHG_Shortcodes','render_tournament_leaderboard']);
    }

    public static function render_best_guessers($atts){
        global $wpdb;
        $atts = shortcode_atts(['period'=>'overall','limit'=>10], $atts, 'bhg_best_guessers');
        $limit = intval($atts['limit']);
        $table = $wpdb->prefix . 'bhg_tournament_results';
        if ($atts['period'] === 'overall') {
            $rows = $wpdb->get_results('SELECT user_id, SUM(wins) as wins FROM '.$table.' GROUP BY user_id ORDER BY wins DESC LIMIT '. $limit, ARRAY_A);
        } else {
            // period filtering by tournament period_key handled elsewhere; fallback to overall
            $rows = $wpdb->get_results('SELECT user_id, SUM(wins) as wins FROM '.$table.' GROUP BY user_id ORDER BY wins DESC LIMIT '. $limit, ARRAY_A);
        }
        if (!$rows) return '<p>'.__('No data','bonus-hunt-guesser').'</p>';
        $out = '<div class="bhg-best-guessers"><ol>';
        foreach($rows as $r){
            $u = get_userdata($r['user_id']);
            $name = $u ? $u->display_name : ('#'.$r['user_id']);
            $out .= '<li>'.esc_html($name).' — '.intval($r['wins']).' '.__('wins','bonus-hunt-guesser').'</li>';
        }
        $out .= '</ol></div>';
        return $out;
    }

    public static function render_tournament_leaderboard($atts){
        global $wpdb;
        $atts = shortcode_atts(['period'=>'monthly','period_key'=>'','limit'=>10], $atts, 'bhg_tournament_leaderboard');
        $limit = intval($atts['limit']);
        $t_table = $wpdb->prefix . 'bhg_tournaments';
        $r_table = $wpdb->prefix . 'bhg_tournament_results';
        // find tournament(s)
        $where = $wpdb->prepare('period=%s AND period_key=%s', $atts['period'], $atts['period_key']);
        $t = $wpdb->get_row('SELECT id FROM '.$t_table.' WHERE '.$where);
        if (!$t) return '<p>'.__('No tournament found','bonus-hunt-guesser').'</p>';
        $rows = $wpdb->get_results($wpdb->prepare('SELECT user_id, wins FROM '.$r_table.' WHERE tournament_id=%d ORDER BY wins DESC LIMIT %d', (int)$t->id, $limit), ARRAY_A);
        if (!$rows) return '<p>'.__('No results yet','bonus-hunt-guesser').'</p>';
        $out = '<div class="bhg-tournament-leaderboard"><ol>';
        foreach($rows as $r){
            $u = get_userdata($r['user_id']);
            $name = $u ? $u->display_name : ('#'.$r['user_id']);
            $out .= '<li>'.esc_html($name).' — '.intval($r['wins']).' '.__('wins','bonus-hunt-guesser').'</li>';
        }
        $out .= '</ol></div>';
        return $out;
    }
}
BHG_Shortcodes::init();
