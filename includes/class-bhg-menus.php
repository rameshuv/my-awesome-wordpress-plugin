<?php
if (!defined('ABSPATH')) exit;

class BHG_Menus {
  public function __construct(){
    add_action('admin_menu', [$this,'admin_menu']);
    add_action('admin_enqueue_scripts', [$this,'assets']);
  }
  public function assets($hook){
    if (strpos($hook, 'bhg') !== false){
      wp_enqueue_style('bhg-admin', BHG_PLUGIN_URL.'assets/css/admin.css', [], BHG_VERSION);
      wp_enqueue_script('bhg-admin', BHG_PLUGIN_URL.'assets/js/admin.js', ['jquery'], BHG_VERSION, true);
    }
  }
  public function admin_menu(){
    $cap = bhg_admin_cap();
    $slug = 'bhg';
    add_menu_page(__('Bonus Hunt','bonus-hunt-guesser'), __('Bonus Hunt','bonus-hunt-guesser'), $cap, $slug, [$this,'render_dashboard'], 'dashicons-awards', 26);
    add_submenu_page($slug, __('Dashboard','bonus-hunt-guesser'), __('Dashboard','bonus-hunt-guesser'), $cap, $slug, [$this,'render_dashboard']);
    add_submenu_page($slug, __('Bonus Hunts','bonus-hunt-guesser'), __('Bonus Hunts','bonus-hunt-guesser'), $cap, 'bhg-bonus-hunts', [$this,'render_bonus_hunts']);
    add_submenu_page($slug, __('Users','bonus-hunt-guesser'), __('Users','bonus-hunt-guesser'), $cap, 'bhg-users', [$this,'render_users']);
    add_submenu_page($slug, __('Affiliate Websites','bonus-hunt-guesser'), __('Affiliate Websites','bonus-hunt-guesser'), $cap, 'bhg-affiliate-websites', [$this,'render_affiliates']);
    add_submenu_page($slug, __('Tournaments','bonus-hunt-guesser'), __('Tournaments','bonus-hunt-guesser'), $cap, 'bhg-tournaments', [$this,'render_tournaments']);
    add_submenu_page($slug, __('Translations','bonus-hunt-guesser'), __('Translations','bonus-hunt-guesser'), $cap, 'bhg-translations', [$this,'render_translations']);
    add_submenu_page($slug, __('Settings','bonus-hunt-guesser'), __('Settings','bonus-hunt-guesser'), $cap, 'bhg-settings', [$this,'render_settings']);
    add_submenu_page($slug, __('Database','bonus-hunt-guesser'), __('Database','bonus-hunt-guesser'), $cap, 'bhg-database', [$this,'render_database']);
  
    add_submenu_page('bhg', __('Tools','bonus-hunt-guesser'), __('Tools','bonus-hunt-guesser'), $cap, 'bhg-tools', [$this,'render_database']);
    
  }
  public function view($view, $vars=[]){
    extract($vars);
    include BHG_PLUGIN_DIR . 'admin/views/header.php';
    include BHG_PLUGIN_DIR . 'admin/views/'.$view.'.php';
  }
  public function render_dashboard(){ $this->view('dashboard'); }
  public function render_bonus_hunts(){ $this->view('bonus-hunts'); }
  public function render_users(){ $this->view('users'); }
  public function render_affiliates(){ $this->view('affiliate-websites'); }
  public function render_tournaments(){ $this->view('tournaments'); }
  public function render_translations(){ $this->view('translations'); }
  public function render_settings(){ $this->view('settings'); }
  public function render_database(){ $this->view('database'); }
}
