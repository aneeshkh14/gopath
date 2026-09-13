<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Authentication logic for the plugin.
 */
class GEP_Auth {

	public function handle_register() {
		if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_register' ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'nonce', (string) wp_get_referer() ) );
			exit;
		}

		$username = sanitize_user( $_POST['user_login'] );
		$email    = sanitize_email( $_POST['user_email'] );
		$password = $_POST['user_pass'];
		$first_name = sanitize_text_field( $_POST['first_name'] );
		$last_name  = sanitize_text_field( $_POST['last_name'] );

		// Validation
		if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
			$redirect = add_query_arg( 'error', 'missing_fields', (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( ! is_email( $email ) ) {
			$redirect = add_query_arg( 'error', 'invalid_email', (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( strlen( $password ) < 8 ) {
			$redirect = add_query_arg( 'error', 'password_too_short', (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( username_exists( $username ) || email_exists( $email ) ) {
			$redirect = add_query_arg( 'error', 'existing_user_login', (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		$user_id = wp_create_user( $username, $password, $email );

		if ( is_wp_error( $user_id ) ) {
			$redirect = add_query_arg( 'error', $user_id->get_error_code(), (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Update profile info
		wp_update_user( array(
			'ID'         => $user_id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'role'       => 'subscriber'
		) );

		// Auto login after registration
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		
		$user = get_user_by( 'id', $user_id );
		do_action( 'wp_login', $user->user_login, $user );
		
		wp_safe_redirect( gep_get_url('dashboard') );
		exit;
	}

	public function handle_login() {
		if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_login' ) ) {
			wp_safe_redirect( add_query_arg( 'login_error', 'nonce', (string) wp_get_referer() ) );
			exit;
		}

		$creds = array(
			'user_login'    => sanitize_text_field( $_POST['log'] ),
			'user_password' => $_POST['pwd'],
			'remember'      => isset( $_POST['rememberme'] )
		);

		$user = wp_authenticate( $creds['user_login'], $creds['user_password'] );

		if ( is_wp_error( $user ) ) {
			$redirect = add_query_arg( 'login_error', $user->get_error_code(), (string) wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		// OTP CHECK
		if ( get_option( 'gep_enable_otp' ) === 'yes' ) {
			$otp = $this->generate_otp( $user->ID );
			if ( is_wp_error( $otp ) ) {
				wp_safe_redirect( add_query_arg( 'login_error', 'otp_mail_failed', gep_get_url('login') ) );
				exit;
			}
			// A false remember-me value must not look like an expired transient.
			set_transient( 'gep_pending_login_' . $user->ID, array( 'remember' => ! empty($creds['remember']) ), 5 * MINUTE_IN_SECONDS );

			$otp_url = add_query_arg( 'view', 'otp', (string) gep_get_url('login') );
			$final_url = add_query_arg( 'uid', $user->ID, $otp_url );
			wp_safe_redirect( $final_url );
			exit;
		}

		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $creds['remember'] );
		do_action( 'wp_login', $user->user_login, $user );
		
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';
		if ( empty( $redirect_to ) && isset( $_REQUEST['_wp_http_referer'] ) ) {
			$redirect_to = esc_url_raw( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
		}
		
		if ( empty( $redirect_to ) || strpos( $redirect_to, 'wp-admin' ) !== false || strpos( $redirect_to, 'wp-login.php' ) !== false ) {
			$redirect_to = gep_get_url('dashboard');
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}

	public function handle_forgot_password() {
		if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_forgot' ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'nonce', wp_get_referer() ) );
			exit;
		}

		$user_input = sanitize_text_field( $_POST['user_login'] );
		$user_data  = get_user_by( 'email', $user_input );
		
		if ( ! $user_data ) {
			$user_data = get_user_by( 'login', $user_input );
		}

		if ( ! $user_data ) {
			$redirect = add_query_arg( 'error', 'invalid_user', wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		$key = get_password_reset_key( $user_data );
		if ( is_wp_error( $key ) ) {
			$redirect = add_query_arg( 'error', $key->get_error_code(), wp_get_referer() );
			wp_safe_redirect( $redirect );
			exit;
		}

		$message = "Someone has requested a password reset for the following account:\r\n\r\n";
		$message .= network_home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf( 'Username: %s', $user_data->user_login ) . "\r\n\r\n";
		$message .= "To reset your password, visit the following address:\r\n\r\n";
		$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_data->user_login ), 'login' ) . "\r\n";

		if ( ! wp_mail( $user_data->user_email, 'Password Reset Request', $message ) ) {
			$redirect = add_query_arg( 'error', 'mail_failed', wp_get_referer() );
			wp_safe_redirect( $redirect );
		} else {
			$redirect = add_query_arg( 'success', 'reset_sent', wp_get_referer() );
			wp_safe_redirect( $redirect );
		}
		exit;
	}

	public function generate_otp( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) return new WP_Error( 'invalid_user', 'Account unavailable.' );
		$otp = wp_rand( 100000, 999999 );
		set_transient( 'gep_otp_' . $user_id, (string) $otp, 5 * MINUTE_IN_SECONDS );
		delete_transient( 'gep_otp_failures_' . $user_id );
		if ( ! wp_mail( $user->user_email, 'Your GoPath verification code', 'Your verification code is ' . $otp . '. It expires in 5 minutes. If you did not request this code, ignore this email.' ) ) {
			delete_transient( 'gep_otp_' . $user_id );
			delete_transient( 'gep_pending_login_' . $user_id );
			return new WP_Error( 'otp_mail_failed', 'Could not send the verification code.' );
		}
		return $otp;
	}

	public function verify_otp( $user_id, $otp ) {
		$stored_otp = get_transient( 'gep_otp_' . $user_id );
		$failures = (int) get_transient( 'gep_otp_failures_' . $user_id );
		if ( ! $stored_otp || $failures >= 5 ) return false;
		if ( preg_match('/^[0-9]{6}$/', (string) $otp) && hash_equals( (string) $stored_otp, (string) $otp ) ) {
			delete_transient( 'gep_otp_' . $user_id );
			delete_transient( 'gep_otp_failures_' . $user_id );
			return true;
		}
		set_transient( 'gep_otp_failures_' . $user_id, $failures + 1, 5 * MINUTE_IN_SECONDS );
		if ( $failures + 1 >= 5 ) {
			delete_transient( 'gep_otp_' . $user_id );
			delete_transient( 'gep_pending_login_' . $user_id );
		}
		return false;
	}

	/**
	 * Enforce strict 2-device maximum by destroying oldest sessions.
	 */
	public function limit_concurrent_sessions( $user_login, $user ) {
		$sessions = WP_Session_Tokens::get_instance( $user->ID );
		$all_sessions = $sessions->get_all();

		if ( count( $all_sessions ) > 2 ) {
			// Sort sessions by login time (oldest first)
			uasort( $all_sessions, function ( $a, $b ) {
				return $a['login'] - $b['login'];
			});

			$sessions_to_destroy = count( $all_sessions ) - 2;
			$destroyed = 0;

			foreach ( $all_sessions as $token => $session_data ) {
				if ( $destroyed >= $sessions_to_destroy ) {
					break;
				}
				// Destroying a token forces that device to log out.
				$sessions->destroy( $token );
				$destroyed++;
			}
		}
	}
}
