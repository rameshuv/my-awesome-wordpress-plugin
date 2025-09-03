<?php
if (!defined('ABSPATH')) exit;

class BHG_Models {
  public static function active_hunt(){
    global $wpdb;
    $t = BHG_DB::table('bonus_hunts');
    return $wpdb->get_row("SELECT * FROM $t WHERE status = 'open' ORDER BY created_at DESC LIMIT 1");
  }
  
  public static function list_hunts($limit=50){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    $limit = intval($limit);
    return $wpdb->get_results("SELECT * FROM $t ORDER BY created_at DESC LIMIT $limit");
  }
  
  public static function get_hunt($id) {
    global $wpdb;
    $t = BHG_DB::table('bonus_hunts');
    $id = intval($id);
    return $wpdb->get_row("SELECT * FROM $t WHERE id = $id");
  }
  
  public static function save_hunt($data){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    
    // Validate and sanitize all inputs
    $row = [
      'title' => sanitize_text_field($data['title'] ?? 'Untitled'),
      'starting_balance' => floatval($data['starting_balance'] ?? 0),
      'bonus_count' => intval($data['bonus_count'] ?? 0),
      'prizes' => sanitize_textarea_field($data['prizes'] ?? ''),
      'affiliate_site_id' => isset($data['affiliate_site_id']) ? intval($data['affiliate_site_id']) : null,
      'status' => sanitize_text_field($data['status'] ?? 'open'),
    ];
    
    if (!empty($data['id'])){
      $id = intval($data['id']);
      $wpdb->update($t, $row, ['id' => $id]);
      return $id;
    } else {
      $wpdb->insert($t, $row);
      return intval($wpdb->insert_id);
    }
  }
  
  public static function upsert_guess_value($hunt_id, $user_id, $guess_value){
    global $wpdb; 
    $t = BHG_DB::table('guesses');
    
    // Validate inputs
    $hunt_id = intval($hunt_id);
    $user_id = intval($user_id);
    $guess_value = floatval($guess_value);
    
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM $t WHERE hunt_id = %d AND user_id = %d", 
      $hunt_id, 
      $user_id
    ));
    
    $row = [
      'hunt_id' => $hunt_id, 
      'user_id' => $user_id, 
      'guess_value' => $guess_value, 
      'updated_at' => current_time('mysql')
    ];
    
    if ($exists){
      $wpdb->update($t, $row, ['id' => intval($exists)]);
      return intval($exists);
    } else {
      $row['created_at'] = current_time('mysql');
      $wpdb->insert($t, $row);
      return intval($wpdb->insert_id);
    }
  }
  
  public static function guesses($hunt_id, $orderby='guess_value', $order='ASC', $paged=1, $per_page=20){
    global $wpdb; 
    $t = BHG_DB::table('guesses');
    $users_table = $wpdb->prefix . 'users';
    
    // Validate and sanitize inputs
    $hunt_id = intval($hunt_id);
    $allowed = ['guess_value', 'user_id', 'user_login'];
    $orderby = in_array($orderby, $allowed, true) ? $orderby : 'guess_value';
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $paged = max(1, intval($paged));
    $per_page = max(1, intval($per_page));
    $offset = max(0, ($paged-1)*$per_page);
    
    $total = (int)$wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM $t WHERE hunt_id = %d",
      $hunt_id
    ));
    
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT g.*, u.user_login 
       FROM $t g 
       LEFT JOIN $users_table u ON g.user_id = u.ID 
       WHERE g.hunt_id = %d 
       ORDER BY $orderby $order 
       LIMIT %d OFFSET %d",
      $hunt_id, $per_page, $offset
    ));
    
    return [
      'items' => $rows,
      'total' => $total,
      'pages' => ceil($total / $per_page),
      'page' => $paged
    ];
  }
  
  public static function close_hunt($hunt_id, $final_balance){
    global $wpdb; 
    
    // Validate inputs
    $hunt_id = intval($hunt_id);
    $final_balance = floatval($final_balance);
    
    $hunt = self::get_hunt($hunt_id); 
    if (!$hunt) return false;
    
    $t = BHG_DB::table('guesses');
    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT user_id, guess_value FROM $t WHERE hunt_id = %d", 
      $hunt_id
    ));
    
    if (!$rows) $rows = [];
    $winner = null; 
    $best_diff = null;
    
    foreach ($rows as $r){
      $diff = abs($final_balance - floatval($r->guess_value));
      if ($best_diff === null || $diff < $best_diff){
        $best_diff = $diff; 
        $winner = intval($r->user_id);
      }
    }
    
    $bt = BHG_DB::table('bonus_hunts');
    $wpdb->update($bt, [
      'final_balance' => $final_balance,
      'status' => 'closed',
      'winner_user_id' => $winner,
      'closed_at' => current_time('mysql'),
    ], ['id' => $hunt_id]);

    // Record tournament win for the winner
    if ($winner) {
      self::record_tournament_win($winner, $hunt_id);
    }

    do_action('bhg_hunt_closed', $hunt_id, $final_balance, $winner);
    return $winner;
  }
  
  public static function wins_count($user_id, $period='all'){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    
    // Validate inputs
    $user_id = intval($user_id);
    
    $query = "SELECT COUNT(*) FROM $t WHERE winner_user_id = $user_id AND status = 'closed'";
    
    if ($period === 'month'){
      $ym = current_time('Y-m');
      $query .= " AND DATE_FORMAT(closed_at, '%Y-%m') = '$ym'";
    } elseif ($period === 'year'){
      $y = current_time('Y');
      $query .= " AND DATE_FORMAT(closed_at, '%Y') = '$y'";
    }
    
    return (int)$wpdb->get_var($query);
  }
  
  public static function previous_hunts($limit=20){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    
    // Validate input
    $limit = max(1, intval($limit));
    
    return $wpdb->get_results("SELECT * FROM $t WHERE status = 'closed' ORDER BY closed_at DESC LIMIT $limit");
  }
  
  // Tournament functionality methods
  public static function record_tournament_win($user_id, $bonus_hunt_id) {
    global $wpdb;
    
    $types = ['weekly', 'monthly', 'yearly'];
    $periods = [
      'weekly' => date('Y-W'),
      'monthly' => date('Y-m'),
      'yearly' => date('Y')
    ];
    
    $tournament_table = $wpdb->prefix . 'bhg_tournaments';
    $wins_table = $wpdb->prefix . 'bhg_tournament_wins';
    
    foreach ($types as $type) {
      $period = $periods[$type];
      
      // Get or create tournament
      $tournament = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tournament_table WHERE type = %s AND period = %s",
        $type, $period
      ));
      
      if (!$tournament) {
        $start_date = self::get_period_start_date($type, $period);
        $end_date = self::get_period_end_date($type, $period);
        
        $wpdb->insert($tournament_table, [
          'type' => $type,
          'period' => $period,
          'start_date' => $start_date,
          'end_date' => $end_date,
          'status' => 'active'
        ]);
        
        $tournament_id = $wpdb->insert_id;
      } else {
        $tournament_id = $tournament->id;
      }
      
      // Update user wins
      $existing_win = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $wins_table WHERE tournament_id = %d AND user_id = %d",
        $tournament_id, $user_id
      ));
      
      if ($existing_win) {
        $wpdb->update($wins_table, 
          ['wins' => $existing_win->wins + 1, 'last_win_date' => current_time('mysql')],
          ['id' => $existing_win->id]
        );
      } else {
        $wpdb->insert($wins_table, [
          'tournament_id' => $tournament_id,
          'user_id' => $user_id,
          'wins' => 1,
          'last_win_date' => current_time('mysql')
        ]);
      }
    }
  }
  
  private static function get_period_start_date($type, $period) {
    switch ($type) {
      case 'weekly':
        // For weekly: period format is Y-W (e.g., 2023-48)
        list($year, $week) = explode('-', $period);
        return date('Y-m-d', strtotime($year . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT)));
      case 'monthly':
        // For monthly: period format is Y-m (e.g., 2023-11)
        return $period . '-01';
      case 'yearly':
        // For yearly: period format is Y (e.g., 2023)
        return $period . '-01-01';
      default:
        return current_time('mysql');
    }
  }
  
  private static function get_period_end_date($type, $period) {
    switch ($type) {
      case 'weekly':
        list($year, $week) = explode('-', $period);
        $start = date('Y-m-d', strtotime($year . 'W' . str_pad($week, 2, '0', STR_PAD_LEFT)));
        return date('Y-m-d', strtotime($start . ' +6 days'));
      case 'monthly':
        return date('Y-m-t', strtotime($period . '-01'));
      case 'yearly':
        return $period . '-12-31';
      default:
        return current_time('mysql');
    }
  }
  
  public static function get_tournament_standings($type, $limit = 100) {
    global $wpdb;
    
    $current_period = self::get_current_period($type);
    $tournament_table = $wpdb->prefix . 'bhg_tournaments';
    $wins_table = $wpdb->prefix . 'bhg_tournament_wins';
    $users_table = $wpdb->prefix . 'users';
    
    // Get current tournament
    $tournament = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $tournament_table WHERE type = %s AND period = %s",
      $type, $current_period
    ));
    
    if (!$tournament) {
      return [];
    }
    
    // Get tournament wins
    return $wpdb->get_results($wpdb->prepare(
      "SELECT w.user_id, u.user_login, w.wins 
       FROM $wins_table w 
       JOIN $users_table u ON w.user_id = u.ID 
       WHERE w.tournament_id = %d 
       ORDER BY w.wins DESC, w.last_win_date ASC 
       LIMIT %d",
      $tournament->id, $limit
    ));
  }
  
  private static function get_current_period($type) {
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
}