<?php
/**
 * Logging helper functions.
 *
 * @package Bonus_Hunt_Guesser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Logging helper class.
 */
class BHG_Logger {

	/**
	 * Write an informational log message.
	 *
	 * @param string $msg Message to log.
	 */
	public static function info( $msg ) {
		bhg_log( $msg );
	}

	/**
	 * Write an error log message.
	 *
	 * @param string $msg Message to log.
	 */
	public static function error( $msg ) {
		bhg_log( 'ERROR: ' . $msg );
	}
}
