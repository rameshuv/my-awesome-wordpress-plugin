<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BHG_Users_Table extends WP_List_Table {

	private $items_data = [];
	private $total_items = 0;
	private $per_page = 30;

	public function __construct() {
		parent::__construct([
			'singular' => 'bhg_user',
			'plural'   => 'bhg_users',
			'ajax'     => false
		]);
	}

	public function get_columns() {
		return [
			'id'       => __('ID', 'bonus-hunt-guesser'),
			'username' => __('Username', 'bonus-hunt-guesser'),
			'email'    => __('Email', 'bonus-hunt-guesser'),
			'role'     => __('Role', 'bonus-hunt-guesser'),
			'guesses'  => __('Guesses', 'bonus-hunt-guesser'),
			'wins'     => __('Wins', 'bonus-hunt-guesser'),
			'profile'  => __('Profile', 'bonus-hunt-guesser'),
		];
	}

	public function get_sortable_columns() {
		return [
			'username' => ['username', true],
			'email'    => ['email', false],
			'role'     => ['role', false],
			'guesses'  => ['guesses', false],
			'wins'     => ['wins', false],
		];
	}

	public function column_default($item, $column_name) {
		switch ($column_name) {
			case 'id':
				return (int)$item['id'];
			case 'username':
				return esc_html($item['username']);
			case 'email':
				return esc_html($item['email']);
			case 'role':
				return esc_html($item['role']);
			case 'guesses':
				return (int)$item['guesses'];
			case 'wins':
				return (int)$item['wins'];
			case 'profile':
				$url = esc_url(admin_url('user-edit.php?user_id='.(int)$item['id']));
				return '<a class="button" href="'.$url.'">'.esc_html__('Edit', 'bonus-hunt-guesser').'</a>';
		}
		return '';
	}

	public function prepare_items() {
		$paged   = isset($_REQUEST['paged']) ? max(1, (int)$_REQUEST['paged']) : 1;
		$orderby = isset($_REQUEST['orderby']) ? sanitize_key($_REQUEST['orderby']) : 'username';
		$order   = isset($_REQUEST['order']) && in_array(strtolower($_REQUEST['order']), ['asc','desc'], true) ? strtoupper($_REQUEST['order']) : 'ASC';
		$search  = isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '';

		// Whitelist orderby
		$allowed = ['username','email','role','guesses','wins'];
		if (!in_array($orderby, $allowed, true)) $orderby = 'username';

		// Fetch users via WP_User_Query
		$args = [
			'number' => $this->per_page,
			'offset' => ($paged - 1) * $this->per_page,
			'fields' => ['ID','user_login','user_email','roles'],
			'orderby'=> in_array($orderby, ['username','email']) ? ($orderby === 'username' ? 'login' : 'email') : 'login',
			'order'  => $order,
		];
		if ($search) {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = ['user_login','user_email'];
		}

		$query = new WP_User_Query($args);
		$users = (array)$query->get_results();
		$this->total_items = (int)$query->get_total();

		// Build base items
		$items = [];
		$ids = [];
		foreach ($users as $u) {
			if ($u instanceof WP_User) {
				$roles = (array) $u->roles;
				$role = $roles ? reset($roles) : '';
				$uid = (int)$u->ID;
				$ids[] = $uid;
				$items[$uid] = [
					'id'       => $uid,
					'username' => $u->user_login,
					'email'    => $u->user_email,
					'role'     => $role,
					'guesses'  => 0,
					'wins'     => 0,
				];
			}
		}

		global $wpdb;
		if (!empty($ids)) {
			$in = implode(',', array_map('intval', $ids));
			$g_table = $wpdb->prefix . 'bhg_guesses';
			$w_table = $wpdb->prefix . 'bhg_tournament_results';

			// Guesses per user
			$placeholders = implode(',', array_fill(0, count($ids), '%d'));
			$sql_g = "SELECT user_id, COUNT(*) c FROM `$g_table` WHERE user_id IN ($placeholders) GROUP BY user_id";
			$g_counts = $wpdb->get_results( $wpdb->prepare( $sql_g, $ids ) );
			foreach ( (array) $g_counts as $row ) {
				$uid = (int)$row->user_id;
				if (isset($items[ $uid ])) $items[$uid]['guesses'] = (int)$row->c;
			}

			// Wins per user (if table exists)
			$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $w_table));
			if ($exists) {
				$placeholders = implode(',', array_fill(0, count($ids), '%d'));
				$sql_w = "SELECT user_id, SUM(wins) c FROM `$w_table` WHERE user_id IN ($placeholders) GROUP BY user_id";
				$w_counts = $wpdb->get_results( $wpdb->prepare( $sql_w, $ids ) );
				foreach ( (array) $w_counts as $row ) {
					$uid = (int)$row->user_id;
					if (isset($items[ $uid ])) $items[$uid]['wins'] = (int)$row->c;
				}
			}
		}

		// Server-side sort for role/guesses/wins
		if (in_array($orderby, ['role','guesses','wins'], true)) {
			$items = array_values($items);
			usort($items, function($a,$b) use ($orderby,$order) {
				$av = $a[$orderby]; $bv = $b[$orderby];
				if ($av == $bv) return 0;
				if ($order === 'ASC') return ($av < $bv) ? -1 : 1;
				return ($av > $bv) ? -1 : 1;
			});
		} else {
			$items = array_values($items);
		}

		$this->items = $items;
		$this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'username'];

		$this->set_pagination_args([
			'total_items' => $this->total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil($this->total_items / $this->per_page),
		]);
	}

	public function extra_tablenav($which) {
		if ($which === 'top') {
			echo '<div class="alignleft actions">';
			$this->search_box( __('Search users','bonus-hunt-guesser'), 'bhg-users' );
			echo '</div>';
		}
	}
}
