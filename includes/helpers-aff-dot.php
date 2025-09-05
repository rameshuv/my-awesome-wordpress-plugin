<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Renders green/red dot based on affiliate status for current hunt/site context
if (!function_exists('bhg_render_affiliate_dot')) {
	function bhg_render_affiliate_dot($user_id, $hunt_affiliate_site_id = 0){
		$is_aff = false;
		if (function_exists('bhg_is_user_affiliate_for_site')) {
			$is_aff = (bool) bhg_is_user_affiliate_for_site($user_id, $hunt_affiliate_site_id);
		} elseif (function_exists('bhg_is_user_affiliate')) {
			$is_aff = (bool) bhg_is_user_affiliate($user_id);
		}

		$cls   = $is_aff ? 'bhg-aff-green' : 'bhg-aff-red';
		$label = $is_aff ? esc_attr__('Affiliate', 'bonus-hunt-guesser')
						 : esc_attr__('Non-affiliate', 'bonus-hunt-guesser');

		return '<span class="bhg-aff-dot ' . esc_attr($cls) . '" aria-label="' . $label . '"></span>';
	}
}
