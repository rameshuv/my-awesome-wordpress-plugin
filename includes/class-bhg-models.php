<?php
if (!defined('ABSPATH')) exit;

class BHG_Models {
  public static function active_hunt(){
    global $wpdb;
    $t = BHG_DB::table('bonus_hunts');
    return $wpdb->get_results("SELECT * FROM `" . $id . "`");
  }
  
  public static function list_hunts($limit=50){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    return $wpdb->get_results("SELECT * FROM `" . $limit . "`");
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
  
  public static function upsert_guess($hunt_id, $user_id, $guess){
    global $wpdb; 
    $t = BHG_DB::table('guesses');
    
    // Validate inputs
    $hunt_id = intval($hunt_id);
    $user_id = intval($user_id);
    $guess = floatval($guess);
    
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM {$t} WHERE hunt_id = %d AND user_id = %d", 
      $hunt_id, 
      $user_id
    ));
    
    $row = [
      'hunt_id' => $hunt_id, 
      'user_id' => $user_id, 
      'guess' => $guess, 
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
  
  public static function guesses($hunt_id, $orderby='guess', $order='ASC', $paged=1, $per_page=20){
    global $wpdb; 
    $t = BHG_DB::table('guesses');
    
    // Validate and sanitize inputs
    $hunt_id = intval($hunt_id);
    $allowed = ['guess', 'user_id'];
    $orderby = in_array($orderby, $allowed, true) ? $orderby : 'guess';
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $paged = max(1, intval($paged));
    $per_page = max(1, intval($per_page));
    $offset = max(0, ($paged-1)*$per_page);
    
    $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM `" . $hunt_id . "`");
    
    // Note: We can't use placeholders for ORDER BY column names, so we validate above
    $rows = $wpdb->get_results("SELECT * FROM `" . $limit . "`");
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
      "SELECT user_id, guess FROM {$t} WHERE hunt_id = %d", 
      $hunt_id
    ));
    
    if (!$rows) $rows = [];
    $winner = null; 
    $best_diff = null;
    
    foreach ($rows as $r){
      $diff = abs($final_balance - floatval($r->guess));
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

    do_action('bhg_hunt_closed', $hunt_id, $final_balance, $winner);
    return $winner;
  }
  
  public static function wins_count($user_id, $period='all'){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    
    // Validate inputs
    $user_id = intval($user_id);
    
    if ($period === 'month'){
      $ym = current_time('Y-m');
      return (int)$wpdb->get_var("SELECT COUNT(*) FROM `" . $user_id . "`");
    } elseif ($period === 'year'){
      $y = current_time('Y');
      return (int)$wpdb->get_var("SELECT COUNT(*) FROM `" . $user_id . "`");
    }
    
    return (int)$wpdb->get_var("SELECT COUNT(*) FROM `" . $user_id . "`");
  }
  
  public static function previous_hunts($limit=20){
    global $wpdb; 
    $t = BHG_DB::table('bonus_hunts');
    
    // Validate input
    $limit = max(1, intval($limit));
    
    return $wpdb->get_results("SELECT * FROM `" . $limit . "`");
  }
}