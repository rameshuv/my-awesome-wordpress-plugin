<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class BHG_Logger {
	public static function info($msg){ bhg_log($msg); }
	public static function error($msg){ bhg_log('ERROR: ' . $msg); }
}
