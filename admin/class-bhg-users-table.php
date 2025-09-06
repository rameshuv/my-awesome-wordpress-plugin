<?php
/**
 * Users list table.
 *
 * @package Bonus_Hunt_Guesser
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table to display plugin users.
 */
class BHG_Users_Table extends WP_List_Table {

	/**
	 * Total items count.
	 *
	 * @var int
	 */
	private $total_items = 0;
	/**
	 * Items per page.
	 *
	 * @var int
	 */
	private $per_page = 30;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'bhg_user',
				'plural'   => 'bhg_users',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Retrieve table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'id'       => __( 'ID', 'bonus-hunt-guesser' ),
			'username' => __( 'Username', 'bonus-hunt-guesser' ),
			'email'    => __( 'Email', 'bonus-hunt-guesser' ),
			'role'     => __( 'Role', 'bonus-hunt-guesser' ),
			'guesses'  => __( 'Guesses', 'bonus-hunt-guesser' ),
			'wins'     => __( 'Wins', 'bonus-hunt-guesser' ),
			'profile'  => __( 'Profile', 'bonus-hunt-guesser' ),
		);
	}

	/**
	 * Retrieve sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'username' => array( 'username', true ),
			'email'    => array( 'email', false ),
			'role'     => array( 'role', false ),
			'guesses'  => array( 'guesses', false ),
			'wins'     => array( 'wins', false ),
		);
	}

	/**
	 * Render default column output.
	 *
	 * @param array  $item        Item data.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return esc_html( (string) (int) $item['id'] );
			case 'username':
				return esc_html( $item['username'] );
			case 'email':
				return esc_html( $item['email'] );
			case 'role':
				return esc_html( $item['role'] );
			case 'guesses':
				return esc_html( (string) (int) $item['guesses'] );
			case 'wins':
				return esc_html( (string) (int) $item['wins'] );
			case 'profile':
				$url = esc_url( admin_url( 'user-edit.php?user_id=' . (int) $item['id'] ) );
				return '<a class="button" href="' . $url . '">' . esc_html__( 'Edit', 'bonus-hunt-guesser' ) . '</a>';
		}
		return '';
	}

	/**
	 * Prepare table items.
	 */
	public function prepare_items() {
		$paged     = max( 1, absint( wp_unslash( $_GET['paged'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby   = sanitize_key( wp_unslash( $_GET['orderby'] ?? 'username' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_raw = sanitize_key( wp_unslash( $_GET['order'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order     = in_array( $order_raw, array( 'asc', 'desc' ), true ) ? strtoupper( $order_raw ) : 'ASC';
		$search    = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Whitelist orderby.
		$allowed = array( 'username', 'email', 'role', 'guesses', 'wins' );
		if ( ! in_array( $orderby, $allowed, true ) ) {
			$orderby = 'username';
		}

		// Fetch users via WP_User_Query.
		$args = array(
			'number'  => $this->per_page,
			'offset'  => ( $paged - 1 ) * $this->per_page,
			'fields'  => array( 'ID', 'user_login', 'user_email', 'roles' ),
			'orderby' => in_array( $orderby, array( 'username', 'email' ), true ) ? ( 'username' === $orderby ? 'login' : 'email' ) : 'login',
			'order'   => $order,
		);
		if ( $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email' );
		}

		$query             = new WP_User_Query( $args );
		$users             = (array) $query->get_results();
		$this->total_items = (int) $query->get_total();

		// Build base items.
		$items = array();
		$ids   = array();
		foreach ( $users as $u ) {
			if ( $u instanceof WP_User ) {
				$roles         = (array) $u->roles;
				$role          = $roles ? reset( $roles ) : '';
				$uid           = (int) $u->ID;
				$ids[]         = $uid;
				$items[ $uid ] = array(
					'id'       => $uid,
					'username' => $u->user_login,
					'email'    => $u->user_email,
					'role'     => $role,
					'guesses'  => 0,
					'wins'     => 0,
				);
			}
		}

		global $wpdb;
		if ( ! empty( $ids ) ) {
			$in      = implode( ',', array_map( 'intval', $ids ) );
			$g_table = $wpdb->prefix . 'bhg_guesses';
			$w_table = $wpdb->prefix . 'bhg_tournament_results';

			// Guesses per user.
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$sql_g        = "SELECT user_id, COUNT(*) c FROM `$g_table` WHERE user_id IN ($placeholders) GROUP BY user_id";
			$prepared     = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql_g ), $ids ) );
			$g_counts     = $wpdb->get_results( $prepared ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $g_counts as $row ) {
				$uid = (int) $row->user_id;
				if ( isset( $items[ $uid ] ) ) {
					$items[ $uid ]['guesses'] = (int) $row->c;
				}
			}

			// Wins per user (if table exists).
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $w_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( $exists ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$sql_w        = "SELECT user_id, SUM(wins) c FROM `$w_table` WHERE user_id IN ($placeholders) GROUP BY user_id";
				$prepared_w   = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql_w ), $ids ) );
				$w_counts     = $wpdb->get_results( $prepared_w ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				foreach ( (array) $w_counts as $row ) {
					$uid = (int) $row->user_id;
					if ( isset( $items[ $uid ] ) ) {
						$items[ $uid ]['wins'] = (int) $row->c;
					}
				}
			}
		}

		// Server-side sort for role/guesses/wins.
		if ( in_array( $orderby, array( 'role', 'guesses', 'wins' ), true ) ) {
			$items = array_values( $items );
			usort(
				$items,
				function ( $a, $b ) use ( $orderby, $order ) {
					$av = $a[ $orderby ];
					$bv = $b[ $orderby ];
					if ( $av === $bv ) {
						return 0;
					}
					if ( 'ASC' === $order ) {
						return ( $av < $bv ) ? -1 : 1;
					}
					return ( $av > $bv ) ? -1 : 1;
				}
			);
		} else {
			$items = array_values( $items );
		}

		$this->items           = $items;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'username' );

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil( $this->total_items / $this->per_page ),
			)
		);
	}

	/**
	 * Output extra controls in the table nav.
	 *
	 * @param string $which Top or bottom position.
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="alignleft actions">';
			$this->search_box( __( 'Search users', 'bonus-hunt-guesser' ), 'bhg-users' );
			echo '</div>';
		}
	}
}
