<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('admin_post_bhg_save_hunt', function(){
  if (!current_user_can(bhg_admin_cap())) wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  check_admin_referer('bhg_save_hunt');
  $id = BHG_Models::save_hunt($_POST);
  wp_safe_redirect(add_query_arg(['page'=>'bhg-bonus-hunts','updated'=>'1','id'=>$id], admin_url('admin.php')));
  exit;
});

add_action('admin_post_bhg_close_hunt', function(){
  if (!current_user_can(bhg_admin_cap())) wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  check_admin_referer('bhg_close_hunt');
  $hunt_id = intval($_POST['hunt_id'] ?? 0);
  $final_balance = floatval($_POST['final_balance'] ?? 0);
  $winner = BHG_Models::close_hunt($hunt_id, $final_balance);

  // Notify participants
  $emails_enabled = get_option('bhg_email_enabled', 1);
  if ($emails_enabled){
    global $wpdb; $t = BHG_DB::table('guesses');
    $rows = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT user_id FROM {$t} WHERE hunt_id=%d", $hunt_id));
    $template = get_option('bhg_email_template', 'Hi {{username}},\nThe Bonus Hunt "{{hunt}}" is closed. Final balance: €{{final}}. Winner: {{winner}}. Thanks for playing!');
    $hunt = BHG_Models::get_hunt($hunt_id);
    $winner_user = $winner ? get_userdata($winner) : null;
    foreach ($rows as $r){
      $u = get_userdata($r->user_id); if (!$u) continue;
      $body = strtr($template, [
        '{{username}}' => $u->user_login,
        '{{hunt}}' => $hunt ? $hunt->title : '',
        '{{final}}' => number_format($final_balance,2),
        '{{winner}}' => $winner_user ? $winner_user->user_login : '—',
      ]);
      wp_mail($u->user_email, sprintf(__('Results for %s','bonus-hunt-guesser'), $hunt ? $hunt->title : 'Bonus Hunt'), $body);
    }
  }
  wp_safe_redirect(add_query_arg(['page'=>'bhg-bonus-hunts','closed'=>'1','id'=>$hunt_id], admin_url('admin.php')));
  exit;
});

add_action('admin_post_bhg_add_affiliate', function(){
  if (!current_user_can(bhg_admin_cap())) wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  check_admin_referer('bhg_add_affiliate');
  global $wpdb; $t = BHG_DB::table('affiliate_websites');
  $wpdb->insert($t, ['name'=>sanitize_text_field($_POST['name']??''), 'slug'=>sanitize_title($_POST['slug']??'')]);
  wp_safe_redirect(add_query_arg(['page'=>'bhg-affiliate-websites','added'=>'1'], admin_url('admin.php')));
  exit;
});


add_action('admin_post_bhg_save_translations', function(){
  if (!current_user_can(bhg_admin_cap())) wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  check_admin_referer('bhg_save_translations');
  $strings = array_map('sanitize_text_field', $_POST['strings'] ?? []);
  update_option('bhg_translations', $strings);
  wp_safe_redirect(add_query_arg(['page'=>'bhg-translations','saved'=>'1'], admin_url('admin.php')));
  exit;
});

add_action('admin_post_bhg_save_ads', function(){
  if (!current_user_can(bhg_admin_cap())) wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  check_admin_referer('bhg_save_ads');
  $ads = $_POST['ads'] ?? [];
  $clean = [];
  foreach ($ads as $ad){
    $clean[] = [
      'text' => sanitize_text_field($ad['text'] ?? ''),
      'link' => esc_url_raw($ad['link'] ?? ''),
      'placement' => sanitize_text_field($ad['placement'] ?? ''),
      'visibility' => sanitize_text_field($ad['visibility'] ?? ''),
    ];
  }
  update_option('bhg_ads', $clean);
  update_option('bhg_email_enabled', isset($_POST['email_enabled']) ? 1 : 0);
  update_option('bhg_email_template', sanitize_textarea_field($_POST['email_template'] ?? ''));
  wp_safe_redirect(add_query_arg(['page'=>'bhg-advertising','saved'=>'1'], admin_url('admin.php')));
  exit;
});
/**
 * Save affiliate flags and site assignments for users.
 */
add_action( 'admin_post_bhg_save_user_affiliates', function () {
  // Verify capability and nonce.
  if ( ! current_user_can( bhg_admin_cap() ) ) {
    wp_die( esc_html__( 'Forbidden', 'bonus-hunt-guesser' ), 403 );
  }
  check_admin_referer( 'bhg_save_user_affiliates' );

  global $wpdb;
  $table = BHG_DB::table( 'user_affiliate_sites' );

  // Sanitize incoming arrays.
  $is_aff_raw   = isset( $_POST['is_affiliate'] ) ? (array) $_POST['is_affiliate'] : [];
  $is_affiliate = array_fill_keys( array_map( 'intval', array_keys( $is_aff_raw ) ), true );

  $sites_raw = isset( $_POST['site'] ) ? (array) $_POST['site'] : [];
  $sites     = array_map(
    static function ( $site_ids ) {
      return array_map( 'intval', (array) $site_ids );
    },
    $sites_raw
  );

  $users = get_users( [ 'fields' => [ 'ID' ], 'number' => 2000 ] );
  if ( empty( $users ) ) {
    wp_safe_redirect( add_query_arg( [ 'page' => 'bhg-users', 'saved' => '0' ], admin_url( 'admin.php' ) ) );
    return;
  }

  foreach ( $users as $u ) {
    $uid = (int) $u->ID;

    // Flag user as affiliate.
    if ( isset( $is_affiliate[ $uid ] ) ) {
      update_user_meta( $uid, 'bhg_is_affiliate', 1 );
    } else {
      delete_user_meta( $uid, 'bhg_is_affiliate' );
    }

    // Remove existing site assignments for this user.
    $wpdb->delete( $table, [ 'user_id' => $uid ], [ '%d' ] );

    // Insert selected sites.
    if ( ! empty( $sites[ $uid ] ) ) {
      foreach ( $sites[ $uid ] as $sid ) {
        $wpdb->insert(
          $table,
          [
            'user_id'    => $uid,
            'site_id'    => $sid,
            'active'     => 1,
            'created_at' => current_time( 'mysql' ),
          ],
          [ '%d', '%d', '%d', '%s' ]
        );
      }
    }
  }

  wp_safe_redirect( add_query_arg( [ 'page' => 'bhg-users', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
  exit;
} );
