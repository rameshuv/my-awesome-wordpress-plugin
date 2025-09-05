<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BHG_Ads {

	/** Initialize front-end hooks for ads */
	public static function init() {
		add_action( 'wp_footer', [ 'BHG_Ads', 'render_footer' ] );
		add_shortcode( 'bhg_ad', [ 'BHG_Ads', 'shortcode' ] );
		add_shortcode( 'bhg_advertising', [ 'BHG_Ads', 'shortcode' ] );
	}


	/** Checks if front-end ads are enabled in plugin settings. */
	protected static function ads_enabled() {
		$settings = get_option('bhg_plugin_settings', []);
		$enabled = isset($settings['ads_enabled']) ? (int)$settings['ads_enabled'] : 0;
		return $enabled === 1;
	}

	/** Determine current user's affiliate status (global toggle). */
	protected static function user_is_affiliate() {
		if (!is_user_logged_in()) return false;
		$uid = get_current_user_id();
		return (bool) get_user_meta($uid, 'bhg_is_affiliate', true);
	}

	/** Whether current visitor matches the ad's visibility setting. */
	protected static function visibility_ok($visibility) {
		$visibility = is_string($visibility) ? strtolower($visibility) : 'all';
		switch ($visibility) {
			case 'logged_in':
		return is_user_logged_in();
			case 'guests':
		return !is_user_logged_in();
			case 'affiliates':
		return self::user_is_affiliate();
			case 'non_affiliates':
		return is_user_logged_in() ? ! self::user_is_affiliate() : false;
			case 'all':
			default:
		return true;
		}
	}

	/** Whether the current page is one of the targeted pages (by slug), if any are set. */
	protected static function page_target_ok($target_pages) {
		$target_pages = is_string($target_pages) ? trim($target_pages) : '';
		if ($target_pages === '') return true; // no restriction

		// Normalize list of slugs
		$slugs = array_filter(array_map(function($s){
			return sanitize_title(wp_unslash(trim($s)));
		}, explode(',', $target_pages)));

		if (empty($slugs)) return true;

		// On singular pages, check post_name; otherwise, do not show
		if (is_singular()) {
			$post = get_post();
			if (!$post) return false;
			$slug = $post->post_name;
			return in_array($slug, $slugs, true);
		}
		return false;
	}

	/** Render a single ad row to HTML */
	protected static function render_ad_row($row) {
		$msg = isset($row->content) ? $row->content : '';
		$msg = wp_kses_post($msg);
		$link = isset($row->link_url) ? esc_url($row->link_url) : '';

		if ($link) {
			$msg = '<a href="' . $link . '">' . $msg . '</a>';
		}
		return '<div class="bhg-ad bhg-ad-' . esc_attr($row->placement) . '">' . $msg . '</div>';
	}

	/** Fetch active ads for a placement */
	protected static function get_ads_for_placement($placement = 'footer') {
		global $wpdb;
		$table = $wpdb->prefix . 'bhg_ads';
		$placement = sanitize_text_field($placement);
		// Active ads ordered newest first
		$sql = "SELECT id, content, link_url, placement, visible_to, target_pages FROM {$table} WHERE active=1 AND placement=%s ORDER BY id DESC";
		return $wpdb->get_results($wpdb->prepare($sql, $placement));
	}

	/** Render footer-placed ads */
	public static function render_footer() {
		if (is_admin()) return;
		if (!self::ads_enabled()) return;

		$ads = self::get_ads_for_placement('footer');
		if (empty($ads)) return;

		$out = [];
		foreach ($ads as $row) {
			if (!self::visibility_ok($row->visible_to)) continue;
			if (!self::page_target_ok($row->target_pages)) continue;
			$out[] = self::render_ad_row($row);
		}

		if (!empty($out)) {
			echo '<div class="bhg-ads bhg-ads-footer" style="margin:16px 0;text-align:center;">';
			echo implode("\n", $out);
			echo '</div>';
		}
	}

	/**
	 * Shortcode handler for rendering a single ad row regardless of placement.
	 *
	 * Usage examples:
	 * [bhg_ad id="123"]
	 * [bhg_advertising ad="123" status="inactive"]
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Content enclosed by shortcode (unused).
	 * @param string $tag     Shortcode tag.
	 *
	 * @return string
	 */
	public static function shortcode( $atts = [], $content = '', $tag = '' ) {
		$a = shortcode_atts(
		[
		'id'     => 0,
		'ad'     => 0,
		'status' => 'active',
		],
		$atts,
		$tag
		);
		
		$id = $a['id'] ? (int) $a['id'] : (int) $a['ad'];
		if ( $id <= 0 ) {
		return '';
		}
		
		$status = strtolower( trim( $a['status'] ) );
		
		global $wpdb;
		$table = $wpdb->prefix . 'bhg_ads';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, content, placement, visible_to, target_pages, active, link_url FROM `$table` WHERE id=%d", $id ) );
		if ( ! $row ) {
		return '';
		}
		
		if ( 'all' !== $status ) {
		$expected = ( 'inactive' === $status ) ? 0 : 1;
		if ( (int) $row->active !== $expected ) {
		return '';
		}
		}
		
		if ( ! self::visibility_ok( $row->visible_to ) ) {
		return '';
		}
		if ( ! self::page_target_ok( $row->target_pages ) ) {
		return '';
		}
		
		return self::render_ad_row( $row );
	}

}

