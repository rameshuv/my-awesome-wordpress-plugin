<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'BHG_Login_Redirect' ) ) {
	class BHG_Login_Redirect {

		public function __construct() {
			add_filter( 'login_redirect', array( $this, 'core_login_redirect' ), 10, 3 );

			// Nextend Social Login compatibility if plugin active.
			if ( function_exists( 'NextendSocialLogin' ) ) {
				add_filter( 'nsl_login_redirect', array( $this, 'nextend_redirect' ), 10, 3 );
			}
		}

		public function core_login_redirect( $redirect_to, $requested, $user ) {
						$requested_redirect = '';
			if ( isset( $_POST['redirect_to'] ) ) {
					$requested_redirect = sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) );
			} elseif ( isset( $_GET['redirect_to'] ) ) {
					$requested_redirect = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
			}
			if ( $requested_redirect ) {
					$validated_redirect = wp_validate_redirect( $requested_redirect, home_url( '/' ) );
					return esc_url_raw( $validated_redirect );
			}

			// Fall back to referer if safe.
			$ref = wp_get_referer();
			if ( $ref ) {
				$validated_ref = wp_validate_redirect( $ref, home_url( '/' ) );
				return esc_url_raw( $validated_ref );
			}

			$validated_default = wp_validate_redirect( $redirect_to, home_url( '/' ) );
			return esc_url_raw( $validated_default );
		}

		public function nextend_redirect( $redirect_to, $user, $provider ) {
						$requested_redirect = '';
			if ( isset( $_POST['redirect_to'] ) ) {
					$requested_redirect = sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) );
			} elseif ( isset( $_GET['redirect_to'] ) ) {
					$requested_redirect = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
			}
			if ( $requested_redirect ) {
					$validated_redirect = wp_validate_redirect( $requested_redirect, home_url( '/' ) );
					return esc_url_raw( $validated_redirect );
			}

			$ref = wp_get_referer();
			if ( $ref ) {
				$validated_ref = wp_validate_redirect( $ref, home_url( '/' ) );
				return esc_url_raw( $validated_ref );
			}

			$validated_default = wp_validate_redirect( $redirect_to, home_url( '/' ) );
			return esc_url_raw( $validated_default );
		}
	}
}
