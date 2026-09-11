<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require WordPress media includes unconditionally (NOT guarded by is_admin())
// because media_handle_upload() is called from front-end AJAX where is_admin() = false
if ( ! function_exists( 'media_handle_upload' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
}

/**
 * Central AJAX handler dispatcher.
 * Method names must match the AJAX action names exactly as registered in GEP_Loader.
 */
class GEP_AJAX {

	public function gep_save_answer() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_exam_nonce', 'nonce' );

		$attempt_id  = absint( $_POST['attempt_id'] );
		$question_id = absint( $_POST['question_id'] );
		$answer      = sanitize_textarea_field( wp_unslash( $_POST['answer'] ) );
		$flagged     = filter_var( isset($_POST['flagged']) ? $_POST['flagged'] : false, FILTER_VALIDATE_BOOLEAN );
		$time_ms     = isset( $_POST['time_ms'] ) ? absint( $_POST['time_ms'] ) : 0;

		global $wpdb;
		$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT user_id, status FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id ) );

		if ( ! $attempt || $attempt->user_id != get_current_user_id() || $attempt->status !== 'in_progress' ) {
			wp_send_json_error( array( 'message' => 'Invalid attempt or session expired' ) );
			return;
		}

		$engine = new GEP_Exam_Engine();
		$success = $engine->save_answer( $attempt_id, $question_id, $answer, $flagged );

		$remaining_seconds = 0;
		$attempt_row = $wpdb->get_row( $wpdb->prepare( "SELECT a.start_time, t.duration_minutes FROM {$wpdb->prefix}gep_attempts a JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id WHERE a.id = %d", $attempt_id ) );
		if ( $attempt_row ) {
			$start_time = strtotime( $attempt_row->start_time );
			$duration_seconds = $attempt_row->duration_minutes * 60;
			$elapsed_seconds = time() - $start_time;
			$remaining_seconds = max( 0, $duration_seconds - $elapsed_seconds );
		}

		if ( $success ) {
			wp_send_json_success( array( 'message' => 'Answer saved', 'remaining_seconds' => $remaining_seconds ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to save answer' ) );
		}
	}

	public function gep_exam_heartbeat() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_exam_nonce', 'nonce' );

		$attempt_id = absint( $_POST['attempt_id'] );
		global $wpdb;
		$attempt_row = $wpdb->get_row( $wpdb->prepare( "SELECT a.start_time, t.duration_minutes, a.user_id, a.status FROM {$wpdb->prefix}gep_attempts a JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id WHERE a.id = %d", $attempt_id ) );

		if ( ! $attempt_row || $attempt_row->user_id != get_current_user_id() || $attempt_row->status !== 'in_progress' ) {
			wp_send_json_error( array( 'message' => 'Invalid session' ) );
			return;
		}

		$start_time = strtotime( $attempt_row->start_time );
		$duration_seconds = $attempt_row->duration_minutes * 60;
		$elapsed_seconds = time() - $start_time;
		$remaining_seconds = max( 0, $duration_seconds - $elapsed_seconds );

		wp_send_json_success( array( 'remaining_seconds' => $remaining_seconds ) );
	}

	public function gep_submit_exam() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_exam_nonce', 'nonce' );

		$attempt_id = absint( $_POST['attempt_id'] );
		
		// The language the paper was read in, remembered so the solution page opens
		// the same way. It is NOT the portal language — writing it to gep_lang here
		// is what switched the student's dashboard to whatever they last read a
		// question in.
		if ( isset( $_POST['lang'] ) ) {
			gep_set_exam_lang( sanitize_text_field( $_POST['lang'] ) );
		}
		
		global $wpdb;
		$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id ) );

		if ( ! $attempt || $attempt->user_id != get_current_user_id() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access' ) );
			return;
		}

		$engine = new GEP_Exam_Engine();
		$success = $engine->submit_exam( $attempt_id );

		if ( $success ) {
			wp_send_json_success( array( 
				'redirect_url' => gep_get_url( 'result' ) . '?id=' . $attempt_id 
			) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to submit exam' ) );
		}
	}

	public function gep_log_violation() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_exam_nonce', 'nonce' );

		$attempt_id = absint( $_POST['attempt_id'] );
		$type       = sanitize_text_field( $_POST['violation_type'] );
		$user_id    = get_current_user_id();

		$secure = new GEP_Secure_Window();
		$status = $secure->log_violation( $attempt_id, $user_id, $type );

		wp_send_json_success( array( 'status' => $status ) );
	}

	public function gep_get_translation() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_exam_nonce', 'nonce' );

		$question_id = absint( $_POST['question_id'] );
		$translator = new GEP_Translator();
		$content = $translator->get_translated_content( $question_id );

		if ( $content ) {
			wp_send_json_success( $content );
		} else {
			wp_send_json_error( array( 'message' => 'Translation not available' ) );
		}
	}

	public function gep_apply_coupon() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Login required' ) );
		check_ajax_referer( 'gep_checkout_nonce', 'nonce' );
		$code = sanitize_text_field( $_POST['coupon_code'] );
		$item_id   = absint( $_POST['item_id'] );
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : 'test';
		$attempts  = isset( $_POST['attempts'] ) ? absint( $_POST['attempts'] ) : 0;
		
		$coupon_logic = new GEP_Payment(); 
		$result = $coupon_logic->validate_coupon( $code, $item_id, $item_type, $attempts );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} else {
			wp_send_json_success( $result );
		}
	}

	public function gep_create_payment_order() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Login required' ) );
		check_ajax_referer( 'gep_checkout_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$item_id   = absint( $_POST['item_id'] );
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : 'test';
		$coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
		$attempts  = isset( $_POST['attempts'] ) ? absint( $_POST['attempts'] ) : 0;

		// SECURITY: Whitelist item_type to prevent SQL injection
		if ( ! in_array( $item_type, array( 'test', 'series', 'course' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid item type' ) );
			return;
		}

		if ( ! $item_id ) {
			wp_send_json_error( array( 'message' => 'Invalid item' ) );
			return;
		}

		// DUPLICATE PREVENTION: If user already has access, don't create another order
		$dashboard = new GEP_Dashboard();
		$access_type = ( $item_type === 'course' ) ? 'course' : 'test';
		
		$is_random = false;
		if ( $item_type === 'test' ) {
			$test_logic = new GEP_Test();
			$test = $test_logic->get_test( $item_id );
			$is_random = ( $test && $test->type === 'random' );
		}

		if ( ! $is_random && $dashboard->has_access( $user_id, $item_id, $access_type ) ) {
			// BUG FIX: Use ?view=purchases — NOT #purchases hash (hash is ignored by server-side router)
			$purchases_url = add_query_arg( 'view', 'purchases', (string) gep_get_url( 'dashboard' ) );
			wp_send_json_success( array(
				'status'   => 'free',
				'redirect' => $purchases_url,
				'message'  => 'You already have access to this item.'
			) );
			return;
		}

		// BUG FIX: Guard against missing Razorpay key — prevents silent payment gateway failure
		$razorpay_key = get_option( 'gep_razorpay_key_id' );
		if ( empty( $razorpay_key ) ) {
			wp_send_json_error( array(
				'message' => 'Payment gateway is not configured. Please contact support.'
			) );
			return;
		}

		$payment = new GEP_Payment();
		$order = $payment->create_order( $item_id, $item_type, $coupon_code, $attempts );

		if ( is_wp_error( $order ) ) {
			// Log the error for debugging
			error_log( '[GEP Payment] create_order error: ' . $order->get_error_message() . ' | item_id=' . $item_id . ' item_type=' . $item_type );
			wp_send_json_error( array( 'message' => $order->get_error_message() ) );
		} else {
			wp_send_json_success( $order );
		}
	}

	public function gep_verify_payment() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Login required' ) );
		check_ajax_referer( 'gep_checkout_nonce', 'nonce' );

		$razorpay_payment_id = sanitize_text_field( $_POST['razorpay_payment_id'] );
		$razorpay_order_id   = sanitize_text_field( $_POST['razorpay_order_id'] );
		$razorpay_signature  = sanitize_text_field( $_POST['razorpay_signature'] );
		// Also accept item_id/item_type from inline modal for access granting
		$item_id   = isset( $_POST['item_id'] )   ? absint( $_POST['item_id'] )                        : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( $_POST['item_type'] ) : 'test';

		$payment = new GEP_Payment();
		$result = $payment->verify_payment( $razorpay_order_id, $razorpay_payment_id, $razorpay_signature, $item_id, $item_type );

		if ( is_wp_error( $result ) || ! $result ) {
			error_log( '[GEP Payment] verify_payment failed: ' . $razorpay_payment_id . ' order:' . $razorpay_order_id );
			wp_send_json_error( array( 'message' => 'Payment verification failed. Please contact support with Payment ID: ' . $razorpay_payment_id ) );
		} else {
			// BUG FIX: Use ?view=purchases query param — NOT #purchases hash
			$redirect_url = add_query_arg( 'view', 'purchases', (string) gep_get_url( 'dashboard' ) );
			wp_send_json_success( array(
				'message'      => 'Payment verified successfully',
				'redirect_url' => $redirect_url
			) );
		}
	}

	public function gep_verify_otp() {
		check_ajax_referer( 'gep_otp_nonce', 'nonce' );

		$otp     = sanitize_text_field( $_POST['otp'] );
		$user_id = absint( $_POST['user_id'] );

		// BUG-6 FIX: Validate user_id before setting auth cookie to prevent account takeover
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ) );
			return;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			wp_send_json_error( array( 'message' => 'Invalid user' ) );
			return;
		}
		// Ensure a pending login session actually exists for this user before validating OTP
		if ( false === get_transient( 'gep_pending_login_' . $user_id ) ) {
			wp_send_json_error( array( 'message' => 'OTP session expired. Please log in again.' ) );
			return;
		}

		$auth = new GEP_Auth();
		if ( $auth->verify_otp( $user_id, $otp ) ) {
			$remember = get_transient( 'gep_pending_login_' . $user_id );
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, $remember );
			delete_transient( 'gep_pending_login_' . $user_id );
			do_action( 'wp_login', $user->user_login, $user );
			wp_send_json_success( array( 'redirect' => gep_get_url( 'dashboard' ) ) );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid or expired OTP' ) );
		}
	}

	public function gep_start_exam() {
		try {
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
				return;
			}
			
			check_ajax_referer( 'gep_exam_nonce', 'nonce' );

			$test_id = isset( $_POST['test_id'] ) ? absint( $_POST['test_id'] ) : 0;
			if ( ! $test_id ) {
				wp_send_json_error( array( 'message' => 'Invalid Test ID' ) );
				return;
			}

			$user_id = get_current_user_id();
			
			$test_logic = new GEP_Test();
			$test = $test_logic->get_test( $test_id );
			
			$all_q_ids = array();
			global $wpdb;

			if ( $test && $test->type === 'random' ) {
				$selected_topics = isset( $_POST['selected_topics'] ) ? $_POST['selected_topics'] : array();
				if ( is_string( $selected_topics ) ) {
					$selected_topics = json_decode( stripslashes($selected_topics), true );
				}
				if ( empty($selected_topics) || ! is_array($selected_topics) ) {
					wp_send_json_error( array( 'message' => 'Please select at least one topic and specify the number of questions.' ) );
					return;
				}
				
				foreach ( $selected_topics as $topic ) {
					$topic_id = absint( isset($topic['topic_id']) ? $topic['topic_id'] : 0 );
					$count    = absint( isset($topic['count']) ? $topic['count'] : 0 );
					if ( $topic_id <= 0 || $count <= 0 ) continue;
					
					// Fetch random question IDs matching this subcategory
					$q_ids = $wpdb->get_col( $wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}gep_questions WHERE subcategory_id = %d AND status = 'publish' ORDER BY RAND() LIMIT %d",
						$topic_id, $count
					) );
					if ( ! empty($q_ids) ) {
						$all_q_ids = array_merge( $all_q_ids, $q_ids );
					}
				}
				
				if ( empty($all_q_ids) ) {
					wp_send_json_error( array( 'message' => 'No questions found in the bank for the selected topics.' ) );
					return;
				}
				
				shuffle( $all_q_ids );
			}

			$engine = new GEP_Exam_Engine();
			$attempt_id = $engine->start_attempt( $test_id, $user_id );

			if ( is_wp_error( $attempt_id ) ) {
				wp_send_json_error( array( 'message' => $attempt_id->get_error_message() ) );
				return;
			} elseif ( ! $attempt_id ) {
				wp_send_json_error( array( 'message' => 'Database failure: Could not initialize attempt.' ) );
				return;
			}

			// If random, link generated question IDs to this attempt
			if ( ! empty($all_q_ids) ) {
				$wpdb->update(
					"{$wpdb->prefix}gep_attempts",
					array( 'question_ids' => implode( ',', $all_q_ids ) ),
					array( 'id' => $attempt_id )
				);
			}

			wp_send_json_success( array( 'attempt_id' => $attempt_id ) );
		} catch ( Exception $e ) {
			error_log( 'GEP_AJAX_ERROR (gep_start_exam): ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Critical System Error: ' . $e->getMessage() ) );
		}
	}

	public function gep_start_pyq_practice_test() {
		try {
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
				return;
			}

			$type   = sanitize_text_field( isset($_POST['type']) ? $_POST['type'] : '' );
			$target = sanitize_text_field( isset($_POST['target']) ? $_POST['target'] : '' );

			if ( empty($type) || empty($target) ) {
				wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
				return;
			}

			$user_id = get_current_user_id();
			global $wpdb;

			$question_ids = array();
			$practice_title = 'Practice Test';
			$duration = 15;

			if ( $type === 'topic' ) {
				$topic_id = absint( $target );
				$topic_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $topic_id ) );
				$practice_title = ($topic_name ? $topic_name : 'Topic') . ' Practice Test';
				
				// Fetch 10 random questions from this topic (subcategory_id)
				$question_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}gep_questions WHERE subcategory_id = %d AND status = 'publish' ORDER BY RAND() LIMIT 10",
					$topic_id
				) );
				$duration = 15; // 15 mins for 10 questions
			} elseif ( $type === 'year' ) {
				// target is like "2023 Shift-1" or "2022"
				$practice_title = 'PYQ Paper - ' . $target;
				
				// Fetch all questions from this year/paper
				$question_ids = $wpdb->get_col( $wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}gep_questions WHERE year = %s AND status = 'publish' ORDER BY id ASC LIMIT 50",
					$target
				) );
				
				$q_count = count($question_ids);
				// Set duration dynamically based on question count: 1.2 minutes per question, min 15 mins
				$duration = max(15, ceil($q_count * 1.2));
			}

			if ( empty($question_ids) ) {
				wp_send_json_error( array( 'message' => 'No questions found matching your selection.' ) );
				return;
			}

			// Clean and shuffle questions
			$question_ids = array_filter( array_map( 'absint', $question_ids ) );
			if ( $type === 'topic' ) {
				shuffle( $question_ids );
			}

			// Initialize attempt using Exam Engine
			$engine = new GEP_Exam_Engine();
			$attempt_id = $engine->start_attempt( 999999, $user_id );

			if ( is_wp_error( $attempt_id ) ) {
				wp_send_json_error( array( 'message' => $attempt_id->get_error_message() ) );
				return;
			}

			// Save custom practice info and generated questions list into this attempt
			$analytics_data = array(
				'practice_title' => $practice_title,
				'type' => $type,
				'target' => $target,
				'duration' => $duration
			);

			$wpdb->update(
				"{$wpdb->prefix}gep_attempts",
				array( 
					'question_ids' => implode( ',', $question_ids ),
					'analytics_data' => json_encode( $analytics_data )
				),
				array( 'id' => $attempt_id )
			);

			wp_send_json_success( array( 'redirect' => gep_get_url( 'exam' ) . '?id=999999' ) );

		} catch ( Exception $e ) {
			error_log( 'GEP_AJAX_ERROR (gep_start_pyq_practice_test): ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Critical System Error: ' . $e->getMessage() ) );
		}
	}

	public function gep_update_profile() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_profile_update', 'gep_profile_nonce' );

		$user_id = get_current_user_id();
		
		// Perfect Context Bridge: If admin is viewing/editing a student
		if ( current_user_can('manage_options') && isset($_POST['uid']) ) {
			if ( ! current_user_can( 'edit_users' ) ) wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
			$user_id = absint($_POST['uid']);
		}

		$user_data = array( 'ID' => $user_id );

		// Names
		if ( isset( $_POST['first_name'] ) ) $user_data['first_name'] = sanitize_text_field( $_POST['first_name'] );
		if ( isset( $_POST['last_name'] ) ) $user_data['last_name'] = sanitize_text_field( $_POST['last_name'] );
		if ( isset( $_POST['first_name'] ) && isset( $_POST['last_name'] ) ) {
			$user_data['display_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
		}

		// Password — BUG-8 FIX: validate length and sanitize
		if ( ! empty( $_POST['new_password'] ) ) {
			$new_pass = wp_unslash( $_POST['new_password'] );
			if ( strlen( $new_pass ) < 8 ) {
				wp_send_json_error( array( 'message' => 'Password must be at least 8 characters.' ) );
				return;
			}
			$user_data['user_pass'] = $new_pass; // wp_update_user() hashes it; do NOT sanitize_text_field as it strips special chars
		}

		// Update Core User
		$updated = wp_update_user( $user_data );
		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( array( 'message' => $updated->get_error_message() ) );
		}

		// Update Meta
		if ( isset( $_POST['phone'] ) ) update_user_meta( $user_id, 'gep_phone', sanitize_text_field( $_POST['phone'] ) );
		if ( isset( $_POST['qualification'] ) ) update_user_meta( $user_id, 'gep_qualification', sanitize_text_field( $_POST['qualification'] ) );
		if ( isset( $_POST['target_exam'] ) ) update_user_meta( $user_id, 'gep_target_exam', sanitize_text_field( $_POST['target_exam'] ) );
		if ( isset( $_POST['bio'] ) ) update_user_meta( $user_id, 'description', sanitize_textarea_field( $_POST['bio'] ) );
		
		if ( isset( $_POST['student_goals'] ) && is_array( $_POST['student_goals'] ) ) {
			$goals = array_map( 'absint', $_POST['student_goals'] );
			update_user_meta( $user_id, 'gep_student_goals', $goals );
		} else {
			// If none checked, clear the meta
			update_user_meta( $user_id, 'gep_student_goals', array() );
		}

		if ( isset( $_POST['preferred_lang'] ) ) {
			$lang = sanitize_text_field( $_POST['preferred_lang'] );
			update_user_meta( $user_id, 'gep_preferred_lang', $lang );
			// Sync with session immediately
			if ( ! session_id() ) session_start();
			$_SESSION['gep_lang'] = $lang;
			// This is the one place the student states a language preference for the
			// portal as a whole, so let the next paper they open follow it rather
			// than whatever the last one happened to be read in.
			unset( $_SESSION['gep_exam_lang'] );
		}

		wp_send_json_success( array( 'message' => 'Profile updated successfully' ) );
	}

	public function gep_update_avatar() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_profile_update', 'gep_profile_nonce' );

		if ( ! isset( $_FILES['avatar'] ) ) {
			wp_send_json_error( array( 'message' => 'No file uploaded' ) );
		}

		$file = $_FILES['avatar'];

		// BUG-7 FIX: Use server-side MIME detection — $_FILES['type'] is client-supplied and can be forged
		$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );
		if ( ! in_array( $real_mime, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.' ) );
			return;
		}

		// Also validate via WordPress helper for double safety
		$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( ! $wp_filetype['type'] ) {
			wp_send_json_error( array( 'message' => 'File type not permitted by WordPress security policy.' ) );
			return;
		}

		if ( $file['size'] > 1024 * 1024 * 2 ) { // 2MB limit
			wp_send_json_error( array( 'message' => 'File too large. Max 2MB allowed.' ) );
			return;
		}

		$user_id = get_current_user_id();
		if ( current_user_can('manage_options') && isset($_POST['uid']) ) {
			if ( ! current_user_can( 'edit_users' ) ) wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
			$user_id = absint($_POST['uid']);
		}
		
		// Perfect Cleanup: Delete old avatar attachment
		$old_avatar_id = get_user_meta( $user_id, 'gep_avatar_id', true );
		if ( $old_avatar_id ) {
			wp_delete_attachment( $old_avatar_id, true );
		}

		$attachment_id = media_handle_upload( 'avatar', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		update_user_meta( $user_id, 'gep_avatar', $image_url );
		update_user_meta( $user_id, 'gep_avatar_id', $attachment_id );

		wp_send_json_success( array( 
			'message'   => 'Avatar updated successfully',
			'image_url' => $image_url
		) );
	}

	public function gep_fetch_question_ids() {
		if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
		if ( ! get_option( 'gep_ai_unlocked', true ) ) {
			wp_send_json_error( 'License key required to unlock AI premium features. Please contact help@gopath.in.' );
			return;
		}
		check_ajax_referer( 'gep_test_save', 'nonce' );
		
		$cat_id = isset($_POST['cat_id']) ? absint( $_POST['cat_id'] ) : 0;
		$limit  = isset($_POST['count']) ? absint( $_POST['count'] ) : 10;

		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $table WHERE category_id = %d AND status = 'publish' ORDER BY RAND() LIMIT %d", $cat_id, $limit ) );

		if ( ! empty( $ids ) ) {
			wp_send_json_success( array( 'ids' => $ids ) );
		} else {
			wp_send_json_error( 'No questions found for this subject.' );
		}
	}

	public function gep_download_import_sample() {
		if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Unauthorized' );

		$filename = 'gep-question-import-template.csv';
		$headers  = array(
			'title', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e',
			'correct_answer', 'explanation', 'marks', 'negative_marks',
			'question_type', 'subject', 'topic', 'year', 'pyqs', 'tags', 'source',
			'question_hi', 'option_a_hi', 'option_b_hi', 'option_c_hi', 'option_d_hi', 'option_e_hi',
			'explanation_hi', 'translation_enabled',
			'passage', 'passage_hi',
		);

		// Row 1: English MCQ
		$row1 = array(
			'What is the chemical formula of water?',
			'H2O', 'CO2', 'NaCl', 'O2', '',
			'A', 'Water is H2O — two hydrogen atoms and one oxygen.', '1', '0.25',
			'mcq', 'Chemistry', 'Basic Chemistry', '2023', 'NEET', 'water,chemistry', 'Chemistry 101',
			'', '', '', '', '', '',
			'', '0',
			'', ''
		);

		// Row 2: Bilingual Hindi+English MCQ
		$row2 = array(
			'Who is known as the Father of the Indian Constitution?',
			'B.R. Ambedkar', 'Mahatma Gandhi', 'Jawaharlal Nehru', 'Sardar Patel', '',
			'A', 'Dr. B.R. Ambedkar is the principal architect of the Indian Constitution.',
			'2', '0.5',
			'mcq', 'Indian Polity', 'Constitution', '2022', 'UPSC', 'polity', 'Constitution book',
			"\xe0\xa4\xad\xe0\xa4\xbe\xe0\xa4\xb0\xe0\xa4\xa4\xe0\xa5\x80\xe0\xa4\xaf \xe0\xa4\xb8\xe0\xa4\x82\xe0\xa4\xb5\xe0\xa4\xbf\xe0\xa4\xa7\xe0\xa4\xbe\xe0\xa4\xa8 \xe0\xa4\x95\xe0\xa5\x87 \xe0\xa4\x9c\xe0\xa4\xa8\xe0\xa4\x95?",
			"\xe0\xa4\xac\xe0\xa5\x80.\xe0\xa4\x86\xe0\xa4\xb0. \xe0\xa4\x85\xe0\xa4\x82\xe0\xa4\xac\xe0\xa5\x87\xe0\xa4\xa1\xe0\xa4\x95\xe0\xa4\xb0", "\xe0\xa4\xae\xe0\xa4\xb9\xe0\xa4\xbe\xe0\xa4\xa4\xe0\xa5\x8d\xe0\xa4\xae\xe0\xa4\xbe \xe0\xa4\x97\xe0\xa4\xbe\xe0\xa4\x82\xe0\xa4\xa7\xe0\xa5\x80", "\xe0\xa4\x9c\xe0\xa4\xb5\xe0\xa4\xbe\xe0\xa4\xb9\xe0\xa4\xb0\xe0\xa4\xb2\xe0\xa4\xbe\xe0\xa4\xb2 \xe0\xa4\xa8\xe0\xa5\x87\xe0\xa4\xb9\xe0\xa4\xb0\xe0\xa5\x82", "\xe0\xa4\xb8\xe0\xa4\xb0\xe0\xa4\xa6\xe0\xa4\xbe\xe0\xa4\xb0 \xe0\xa4\xaa\xe0\xa4\x9f\xe0\xa5\x87\xe0\xa4\xb2", "",
			"\xe0\xa4\xa1\xe0\xa5\x89. \xe0\xa4\xac\xe0\xa5\x80.\xe0\xa4\x86\xe0\xa4\xb0. \xe0\xa4\x85\xe0\xa4\x82\xe0\xa4\xac\xe0\xa5\x87\xe0\xa4\xa1\xe0\xa4\x95\xe0\xa4\xb0 \xe0\xa4\xad\xe0\xa4\xbe\xe0\xa4\xb0\xe0\xa4\xa4\xe0\xa5\x80\xe0\xa4\xaf \xe0\xa4\xb8\xe0\xa4\x82\xe0\xa4\xb5\xe0\xa4\xbf\xe0\xa4\xa7\xe0\xa4\xbe\xe0\xa4\xa8 \xe0\xa4\x95\xe0\xa5\x87 \xe0\xa4\xae\xe0\xa5\x81\xe0\xa4\x96\xe0\xa5\x8d\xe0\xa4\xaf \xe0\xa4\xa8\xe0\xa4\xbf\xe0\xa4\xb0\xe0\xa5\x8d\xe0\xa4\xae\xe0\xa4\xbe\xe0\xa4\xa4\xe0\xa4\xbe \xe0\xa4\xb9\xe0\xa5\x88\xe0\xa4\x82\xe0\xa5\xa4",
			'1',
			'', ''
		);

		// Row 3: True/False
		$row3 = array(
			'The Earth revolves around the Sun.',
			'True', 'False', '', '', '',
			'A', 'Earth completes one revolution in approximately 365.25 days.',
			'1', '0',
			'true_false', 'Science', 'Astronomy', '', '', 'earth,solar', 'Astronomy Guide',
			'', '', '', '', '', '', '', '0',
			'', ''
		);

		// Row 4: Bilingual Passage MCQ
		$row4 = array(
			'Which celestial body is at the center of the solar system?',
			'Sun', 'Earth', 'Mars', 'Venus', '',
			'A', 'The Sun is at the center of the solar system.',
			'1', '0.25',
			'mcq', 'Science', 'Astronomy', '2024', '', 'solar,passage', 'General Knowledge 2024',
			'सौर मंडल के केंद्र में कौन सा खगोलीय पिंड है?',
			'सूर्य', 'पृथ्वी', 'मंगल', 'शुक्र', '',
			'सूर्य सौर मंडल के केंद्र में है।',
			'1',
			'The solar system consists of the Sun and the objects that orbit it.',
			'सौर मंडल में सूर्य और उसकी परिक्रमा करने वाले पिंड शामिल हैं।'
		);

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel to display Hindi correctly

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, $headers );
		fputcsv( $output, $row1 );
		fputcsv( $output, $row2 );
		fputcsv( $output, $row3 );
		fputcsv( $output, $row4 );
		fclose( $output );
		exit;
	}

	public function gep_validate_import_file() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		if ( empty( $_FILES['import_file'] ) || empty( $_FILES['import_file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => 'No file uploaded.' ) );
		}

		$tmp_name = $_FILES['import_file']['tmp_name'];
		$filename = $_FILES['import_file']['name'];
		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		$headers = array();
		$row_count = 0;

		if ( $extension === 'csv' ) {
			// Auto-convert non-UTF-8 CSV contents to UTF-8
			$file_content = @file_get_contents( $tmp_name );
			$handle = false;
			if ( $file_content ) {
				$encoding = 'UTF-8';
				if ( function_exists( 'mb_detect_encoding' ) ) {
					$detected = mb_detect_encoding( $file_content, array( 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ASCII', 'Windows-1252', 'ISO-8859-1' ), true );
					if ( $detected && $detected !== 'UTF-8' ) {
						$encoding = $detected;
					}
				}
				if ( $encoding !== 'UTF-8' ) {
					if ( function_exists( 'mb_convert_encoding' ) ) {
						$file_content = mb_convert_encoding( $file_content, 'UTF-8', $encoding );
					} elseif ( function_exists( 'iconv' ) ) {
						$file_content = iconv( $encoding, 'UTF-8//IGNORE', $file_content );
					}
					$temp_handle = fopen( 'php://temp', 'r+' );
					if ( $temp_handle ) {
						fwrite( $temp_handle, $file_content );
						rewind( $temp_handle );
						$handle = $temp_handle;
					}
				}
			}

			if ( ! $handle ) {
				$handle = fopen( $tmp_name, 'r' );
			}

			if ( $handle ) {
				// Detect delimiter
				$first_line = fgets( $handle );
				$delimiter = ',';
				if ( $first_line ) {
					$delimiters = array( ',', ';', "\t" );
					$max_count = 0;
					foreach ( $delimiters as $delim ) {
						$count = substr_count( $first_line, $delim );
						if ( $count > $max_count ) {
							$max_count = $count;
							$delimiter = $delim;
						}
					}
				}
				rewind( $handle );

				$headers = fgetcsv( $handle, 0, $delimiter );
				// Strip UTF-8 BOM
				$bom = pack( 'H*', 'EFBBBF' );
				if ( isset( $headers[0] ) && 0 === strpos( $headers[0], $bom ) ) {
					$headers[0] = substr( $headers[0], strlen( $bom ) );
				}

				while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== FALSE ) {
					if ( ! empty( array_filter( $row ) ) ) {
						$row_count++;
					}
				}
				fclose( $handle );
			}
		} elseif ( in_array( $extension, array( 'xlsx', 'xls' ) ) ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				wp_send_json_error( array( 'message' => 'ZipArchive PHP extension is required to parse Excel spreadsheets.' ) );
			}
			require_once GEP_PLUGIN_DIR . 'includes/class-gep-xlsx-parser.php';
			$rows = GEP_XLSX_Parser::parse( $tmp_name );
			if ( $rows && ! empty( $rows ) ) {
				$headers = array_shift( $rows );
				foreach ( $rows as $row ) {
					if ( ! empty( array_filter( $row ) ) ) {
						$row_count++;
					}
				}
			}
		} else {
			wp_send_json_error( array( 'message' => 'Unsupported file format. Please upload a .csv, .xlsx, or .xls file.' ) );
		}

		if ( empty( $headers ) ) {
			wp_send_json_error( array( 'message' => 'Could not detect any headers or questions in the file.' ) );
		}

		// Map columns
		$fields = array(
			'title'               => array( 'label' => 'Question Text', 'required' => true, 'alts' => array( 'question', 'questiontext', 'qustion', 'प्रशन', 'प्रश्न', 'प्रश्नशीर्षक' ) ),
			'option_a'            => array( 'label' => 'Option A', 'required' => true, 'alts' => array( 'option1', 'choice1', 'a', 'विकल्पक', 'क', 'विकल्पअ', 'विकल्प1', 'विकल्पइ', 'विकल्प१' ) ),
			'option_b'            => array( 'label' => 'Option B', 'required' => true, 'alts' => array( 'option2', 'choice2', 'b', 'विकल्पख', 'ख', 'विकल्पब', 'विकल्प2', 'विकल्पउ', 'विकल्प२' ) ),
			'option_c'            => array( 'label' => 'Option C', 'required' => false, 'alts' => array( 'option3', 'choice3', 'c', 'विकल्पग', 'ग', 'विकल्पस', 'विकल्प3', 'विकल्पए', 'विकल्प३' ) ),
			'option_d'            => array( 'label' => 'Option D', 'required' => false, 'alts' => array( 'option4', 'choice4', 'd', 'विकल्पघ', 'घ', 'विकल्पद', 'विकल्प4', 'विकल्पओ', 'विकल्प४' ) ),
			'option_e'            => array( 'label' => 'Option E', 'required' => false, 'alts' => array( 'optione', 'option5', 'choice5', 'e', 'विकल्पङ', 'ङ', 'विकल्पइ', 'विकल्प5', 'विकल्प५' ) ),
			'correct_answer'      => array( 'label' => 'Correct Answer', 'required' => true, 'alts' => array( 'correctanswer', 'answer', 'correctoption', 'key', 'ans', 'उत्तर', 'सहीउत्तर', 'सहीविकल्प' ) ),
			'explanation'         => array( 'label' => 'Explanation', 'required' => false, 'alts' => array( 'explation', 'solution', 'reason', 'hint', 'व्याख्या', 'स्पष्टीकरण', 'हल' ) ),
			'marks'               => array( 'label' => 'Marks', 'required' => false, 'alts' => array( 'mark' ) ),
			'negative_marks'      => array( 'label' => 'Negative Penalty', 'required' => false, 'alts' => array( 'negativemark' ) ),
			'subject'             => array( 'label' => 'Subject', 'required' => false, 'alts' => array( 'category', 'subjectname', 'विषय' ) ),
			'topic'               => array( 'label' => 'Topic', 'required' => false, 'alts' => array( 'subcategory', 'chapter', 'अध्याय', 'शीर्षक' ) ),
			'pyqs'                => array( 'label' => 'PYQ Tags', 'required' => false, 'alts' => array( 'pyq', 'previousyear', 'गतवर्ष' ) ),
			'year'                => array( 'label' => 'Year', 'required' => false, 'alts' => array( 'session' ) ),
			'tags'                => array( 'label' => 'Tags', 'required' => false, 'alts' => array() ),
			'source'              => array( 'label' => 'Source', 'required' => false, 'alts' => array( 'from', 'book', 'questionsource', 'origin', 'स्रोत', 'कहाँसे' ) ),
			'question_type'       => array( 'label' => 'Question Type', 'required' => false, 'alts' => array( 'questiontype', 'type', 'qtype', 'प्रश्नप्रकार', 'प्रकार' ) ),
			'numerical_tolerance' => array( 'label' => 'Numerical Tolerance', 'required' => false, 'alts' => array( 'numericaltolerance', 'tolerance', 'tolerancevalue', 'acceptedrange' ) ),
			'question_hi'         => array( 'label' => 'Hindi Question Text', 'required' => false, 'alts' => array( 'questionhi', 'titlehi', 'questionhindi', 'titlehindi', 'prashna', 'प्रश्नहिंदी', 'प्रश्नहिन्दी', 'प्रश्नहिं' ) ),
			'option_a_hi'         => array( 'label' => 'Hindi Option A', 'required' => false, 'alts' => array( 'optionahi', 'optionahindi', 'option1hi', 'option1hindi', 'choice1hi', 'choice1hindi', 'ahi', 'ahindi', 'विकल्पकहिंदी', 'विकल्पकहिन्दी', 'विकल्पअहिंदी', 'विकल्पअहिन्दी' ) ),
			'option_b_hi'         => array( 'label' => 'Hindi Option B', 'required' => false, 'alts' => array( 'optionbhi', 'optionbhindi', 'option2hi', 'option2hindi', 'choice2hi', 'choice2hindi', 'bhi', 'bhindi', 'विकल्पखहिंदी', 'विकल्पखहिन्दी', 'विकल्पबहिंदी', 'विकल्पबहिन्दी' ) ),
			'option_c_hi'         => array( 'label' => 'Hindi Option C', 'required' => false, 'alts' => array( 'optionchi', 'optionchindi', 'option3hi', 'option3hindi', 'choice3hi', 'choice3hindi', 'chi', 'chindi', 'विकल्पगहिंदी', 'विकल्पगहिन्दी', 'विकल्पसहिंदी', 'विकल्पसहिन्दी' ) ),
			'option_d_hi'         => array( 'label' => 'Hindi Option D', 'required' => false, 'alts' => array( 'optiondhi', 'optiondhindi', 'option4hi', 'option4hindi', 'choice4hi', 'choice4hindi', 'dhi', 'dhindi', 'विकल्पघहिंदी', 'विकल्पघहिन्दी', 'विकल्पदहिंदी', 'विकल्पदहिन्दी' ) ),
			'option_e_hi'         => array( 'label' => 'Hindi Option E', 'required' => false, 'alts' => array( 'optionehi', 'optionehindi', 'option5hi', 'option5hindi', 'choice5hi', 'choice5hindi', 'ehi', 'ehindi', 'विकल्पङहिंदी', 'विकल्पङहिन्दी', 'विकल्पइहिंदी', 'विकल्पइहिन्दी' ) ),
			'explanation_hi'      => array( 'label' => 'Hindi Explanation', 'required' => false, 'alts' => array( 'explanationhi', 'explanationhindi', 'solutionhi', 'solutionhindi' ) ),
			'translation_enabled' => array( 'label' => 'Translation Toggle', 'required' => false, 'alts' => array( 'translationenabled', 'bilingual', 'hindi', 'translation' ) ),
			'passage'             => array( 'label' => 'Comprehension Passage', 'required' => false, 'alts' => array( 'passagetext', 'comprehension', 'गद्यांश', 'पैराग्राफ' ) ),
			'passage_hi'          => array( 'label' => 'Hindi Passage', 'required' => false, 'alts' => array( 'passagehi', 'passagehindi', 'comprehensionhi', 'comprehensionhindi', 'गद्यांशहिंदी', 'गद्यांशहिन्दी' ) )
		);

		$normalized_headers = array_map( function( $header ) {
			return preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( trim( (string) $header ) ) );
		}, $headers );

		$mapped = array();
		$missing_required = array();

		foreach ( $fields as $key => $meta ) {
			$norm_key = preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( $key ) );
			$index = array_search( $norm_key, $normalized_headers );

			if ( $index === false ) {
				// Search alternates
				foreach ( $meta['alts'] as $alt ) {
					$norm_alt = preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( $alt ) );
					$idx = array_search( $norm_alt, $normalized_headers );
					if ( $idx !== false ) {
						$index = $idx;
						break;
					}
				}
			}

			if ( $index !== false ) {
				$mapped[$key] = array(
					'label' => $meta['label'],
					'column' => $headers[$index]
				);
			} else {
				if ( $meta['required'] ) {
					$missing_required[] = $meta['label'];
				}
			}
		}

		wp_send_json_success( array(
			'filename'         => $filename,
			'row_count'        => $row_count,
			'mapped'           => $mapped,
			'missing_required' => $missing_required
		) );
	}

	public function gep_mark_notif_read() {
		if ( ! is_user_logged_in() ) wp_send_json_error();
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );
		$id = absint( $_POST['id'] );
		$success = GEP_Notifications::mark_as_read( $id );
		wp_send_json_success( array( 'success' => $success ) );
	}

	public function gep_mark_all_notifs_read() {
		if ( ! is_user_logged_in() ) wp_send_json_error();
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );
		$success = GEP_Notifications::mark_all_read( get_current_user_id() );
		wp_send_json_success( array( 'success' => $success ) );
	}

	public function gep_update_lang() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		// Validate lang against whitelist
		$allowed_langs = array( 'en', 'hi' );
		$lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en';
		if ( ! in_array( $lang, $allowed_langs, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid language' ) );
			return; // BUG-lang FIX: missing return caused duplicate $lang reassignment below
		}
		// Instructions screen: this chooses the language of the paper about to be
		// taken, not the language of the portal.
		gep_set_exam_lang( $lang ); // reuse already-validated $lang, no need to re-read POST
		wp_send_json_success( array( 'lang' => $lang ) );
	}

	public function gep_toggle_lesson_completion() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( ! $lesson_id || ! $course_id ) {
			wp_send_json_error( array( 'message' => 'Invalid lesson or course ID' ) );
		}

		$user_id = get_current_user_id();
		
		$dashboard = new GEP_Dashboard();
		if ( ! $dashboard->has_access( $user_id, $course_id, 'course' ) ) {
			wp_send_json_error( array('message' => 'Access Denied: You do not own this course.') );
			return;
		}

		// Remove duplicate $user_id assignment: already set above
		$completed_map = get_user_meta( $user_id, 'gep_completed_lessons', true );
		if ( ! is_array( $completed_map ) ) {
			$completed_map = array();
		}

		if ( ! isset( $completed_map[ $course_id ] ) || ! is_array( $completed_map[ $course_id ] ) ) {
			$completed_map[ $course_id ] = array();
		}


		$index = array_search( $lesson_id, $completed_map[ $course_id ] );
		if ( $index !== false ) {
			// Already completed, so unmark it
			unset( $completed_map[ $course_id ][ $index ] );
			$completed_map[ $course_id ] = array_values( $completed_map[ $course_id ] );
			$status = 'unmarked';
		} else {
			// Not completed, so mark it
			$completed_map[ $course_id ][] = $lesson_id;
			$status = 'completed';
		}

		update_user_meta( $user_id, 'gep_completed_lessons', $completed_map );

		// Recalculate progress for response
		global $wpdb;
		$lessons = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gep_lessons WHERE course_id = %d", $course_id ) );
		$total_lessons = count( $lessons );
		$completed_lesson_ids = $completed_map[ $course_id ];
		$completed_lessons = count( array_intersect( $completed_lesson_ids, wp_list_pluck( $lessons, 'id' ) ) );
		$progress_percent = $total_lessons > 0 ? round( ( $completed_lessons / $total_lessons ) * 100 ) : 0;

		wp_send_json_success( array(
			'status'           => $status,
			'completed_count'  => $completed_lessons,
			'progress_percent' => $progress_percent
		) );
	}

	public function gep_post_doubt() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array('message' => 'Unauthorized') );
		// Fix: use gep_dashboard_nonce (which IS localized) instead of gep_ajax_nonce (never localized)
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );

		global $wpdb;
		$user_id = get_current_user_id();
		$course_id = absint( $_POST['course_id'] );
		$lesson_id = absint( $_POST['lesson_id'] );
		$question = sanitize_textarea_field( wp_unslash( $_POST['question'] ) );

		if ( empty( $question ) ) wp_send_json_error( array('message' => 'Question is empty') );

		$dashboard = new GEP_Dashboard();
		if ( ! $dashboard->has_access( $user_id, $course_id, 'course' ) ) {
			wp_send_json_error( array('message' => 'Access Denied: You do not own this course.') );
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'gep_doubts',
			array(
				'user_id' => $user_id,
				'course_id' => $course_id,
				'lesson_id' => $lesson_id,
				'question' => $question,
				'created_at' => current_time( 'mysql' )
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		wp_send_json_success();
	}

	public function gep_get_doubts() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array('message' => 'Unauthorized') );
		// Fix: use gep_dashboard_nonce (which IS localized) instead of gep_ajax_nonce (never localized)
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );

		global $wpdb;
		$user_id = get_current_user_id();
		$lesson_id = absint( $_POST['lesson_id'] );
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( ! $course_id ) {
			wp_send_json_error( array('message' => 'Invalid Course ID') );
			return;
		}

		$dashboard = new GEP_Dashboard();
		if ( ! $dashboard->has_access( $user_id, $course_id, 'course' ) ) {
			wp_send_json_error( array('message' => 'Access Denied: You do not own this course.') );
			return;
		}

		// BUG-G FIX: Scope query to course_id to prevent cross-course doubt leakage
		$doubts = $wpdb->get_results( $wpdb->prepare(
			"SELECT d.question, d.answer, d.status, u.display_name 
			 FROM {$wpdb->prefix}gep_doubts d 
			 LEFT JOIN {$wpdb->users} u ON d.user_id = u.ID 
			 WHERE d.lesson_id = %d AND d.course_id = %d
			 ORDER BY d.created_at DESC LIMIT 50",
			$lesson_id, $course_id
		) );

		wp_send_json_success( $doubts );
	}

	public function gep_bulk_save_parsed_questions() {
		if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( 'Unauthorized' );
		if ( ! get_option( 'gep_ai_unlocked', true ) ) {
			wp_send_json_error( 'License key required to unlock AI premium features. Please contact help@gopath.in.' );
			return;
		}
		check_ajax_referer( 'gep_bulk_import', 'nonce' );

		$raw_questions = isset( $_POST['questions'] ) ? $_POST['questions'] : '';
		if ( empty( $raw_questions ) ) {
			wp_send_json_error( 'No questions data received.' );
		}

		$questions = json_decode( wp_unslash( $raw_questions ), true );
		if ( ! is_array( $questions ) ) {
			wp_send_json_error( 'Invalid questions format.' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$inserted_count = 0;
		$skipped_count  = 0;
		$errors         = array(); // FIX: track errors per question

		// Valid question types whitelist
		$valid_types = array( 'mcq', 'short_answer', 'true_false', 'multi_select', 'msq', 'numerical', 'assertion_reason', 'matching', 'passage' );

		foreach ( $questions as $q ) {
			$question_hi = isset( $q['question_hi'] ) ? wp_kses_post( $q['question_hi'] ) : '';
			$option_a_hi = isset( $q['option_a_hi'] ) ? wp_kses_post( $q['option_a_hi'] ) : '';
			$option_b_hi = isset( $q['option_b_hi'] ) ? wp_kses_post( $q['option_b_hi'] ) : '';
			$option_c_hi = isset( $q['option_c_hi'] ) ? wp_kses_post( $q['option_c_hi'] ) : '';
			$option_d_hi = isset( $q['option_d_hi'] ) ? wp_kses_post( $q['option_d_hi'] ) : '';
			$option_e_hi = isset( $q['option_e_hi'] ) ? wp_kses_post( $q['option_e_hi'] ) : '';
			$explanation_hi = isset( $q['explanation_hi'] ) ? wp_kses_post( $q['explanation_hi'] ) : '';

			$has_translation_content = ( ! empty( $question_hi ) || ! empty( $option_a_hi ) || ! empty( $option_b_hi ) || ! empty( $option_c_hi ) || ! empty( $option_d_hi ) || ! empty( $option_e_hi ) || ! empty( $explanation_hi ) );
			$translation_enabled = ( ! empty( $q['translation_enabled'] ) || $has_translation_content ) ? 1 : 0;
			
			$translated_data = array();
			if ( $translation_enabled || $has_translation_content ) {
				$translated_data = array(
					'title'       => $question_hi,
					'option_a'    => $option_a_hi,
					'option_b'    => $option_b_hi,
					'option_c'    => $option_c_hi,
					'option_d'    => $option_d_hi,
					'option_e'    => $option_e_hi,
					'explanation' => $explanation_hi
				);
			}

			// FIX: Validate required title
			$title = isset( $q['title'] ) ? trim( wp_kses_post( $q['title'] ) ) : '';
			if ( empty( $title ) ) {
				$errors[] = 'Skipped: Question title is empty.';
				$skipped_count++;
				continue;
			}

			// FIX: Validate question_type
			$q_type = isset( $q['question_type'] ) ? strtolower( trim( $q['question_type'] ) ) : 'mcq';
			if ( ! in_array( $q_type, $valid_types, true ) ) $q_type = 'mcq';

			$data = array(
				'title'               => $title,
				'question_type'       => $q_type,
				'option_a'            => isset( $q['option_a'] )       ? wp_kses_post( $q['option_a'] )              : '',
				'option_b'            => isset( $q['option_b'] )       ? wp_kses_post( $q['option_b'] )              : '',
				'option_c'            => isset( $q['option_c'] )       ? wp_kses_post( $q['option_c'] )              : '',
				'option_d'            => isset( $q['option_d'] )       ? wp_kses_post( $q['option_d'] )              : '',
				'option_e'            => isset( $q['option_e'] )       ? wp_kses_post( $q['option_e'] )              : '',
				'correct_answer'      => isset( $q['correct_answer'] ) ? strtoupper( sanitize_text_field( $q['correct_answer'] ) ) : '',
				'explanation'         => isset( $q['explanation'] )    ? wp_kses_post( $q['explanation'] )           : '',
				'marks'               => isset( $q['marks'] )          ? floatval( $q['marks'] )                     : 1.0,
				'negative_marks'      => isset( $q['negative_marks'] ) ? floatval( $q['negative_marks'] )            : 0.0,
				'category_id'         => isset( $q['category_id'] )    ? absint( $q['category_id'] )                 : 0,
				'subcategory_id'      => isset( $q['subcategory_id'] ) ? absint( $q['subcategory_id'] )              : 0,
				'tags'                => isset( $q['tags'] )           ? sanitize_text_field( $q['tags'] )           : '',
				'pyqs'                => isset( $q['pyqs'] )           ? sanitize_text_field( $q['pyqs'] )           : '',
				'year'                => isset( $q['year'] )           ? sanitize_text_field( $q['year'] )           : '',
				'translation_enabled' => $translation_enabled,
				'translated_data'     => wp_json_encode( $translated_data, JSON_UNESCAPED_UNICODE ),
				'status'              => 'publish',
			);

			// Resilient duplicate check to prevent double injection and support translation updates
			$exists = 0;
			$hash = md5( trim( $data['title'] ) );
			$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE MD5(title) = %s AND category_id = %d LIMIT 1", $hash, $data['category_id'] ) );
			if ( $existing_id ) {
				$exists = (int) $existing_id;
			} else {
				// Normalized fallback check
				$cat_qs = $wpdb->get_results( $wpdb->prepare( "SELECT id, title FROM $table WHERE category_id = %d", $data['category_id'] ) );
				if ( ! empty( $cat_qs ) ) {
					$norm_target = preg_replace( '/\s+/u', '', mb_strtolower( html_entity_decode( strip_tags( $data['title'] ), ENT_QUOTES, 'UTF-8' ), 'UTF-8' ) );
					$target_hash = md5( $norm_target );
					foreach ( $cat_qs as $cq ) {
						$norm_cq = preg_replace( '/\s+/u', '', mb_strtolower( html_entity_decode( strip_tags( $cq->title ), ENT_QUOTES, 'UTF-8' ), 'UTF-8' ) );
						if ( md5( $norm_cq ) === $target_hash ) {
							$exists = (int) $cq->id;
							break;
						}
					}
				}
			}

			if ( $exists ) {
				if ( $translation_enabled && ! empty( $data['translated_data'] ) ) {
					$wpdb->update( $table, array(
						'translation_enabled' => 1,
						'translated_data'     => $data['translated_data']
					), array( 'id' => $exists ) );
					$inserted_count++;
				} else {
					$skipped_count++;
				}
				continue;
			}

			$inserted = $wpdb->insert( $table, $data );
			if ( $inserted ) {
				$inserted_count++;
			} else {
				$errors[] = 'DB error: ' . substr( wp_strip_all_tags( $data['title'] ), 0, 60 );
				$skipped_count++;
			}
		}

		wp_cache_flush();
		wp_send_json_success( array(
			'inserted' => $inserted_count,
			'skipped'  => $skipped_count,
			'errors'   => $errors,
		) );
	}

	public function gep_get_analytics() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array('message'=>'Unauthorized') );
		$attempt_id = absint( isset($_POST['attempt_id']) ? $_POST['attempt_id'] : 0 );
		if ( ! $attempt_id ) wp_send_json_error( array('message'=>'Missing attempt ID') );

		// Ownership check
		global $wpdb;
		$attempt = $wpdb->get_row( $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id
		) );
		if ( ! $attempt || ( $attempt->user_id != get_current_user_id() && ! current_user_can('manage_options') ) ) {
			wp_send_json_error( array('message'=>'Access denied') );
		}

		$analytics = new GEP_Analytics();
		$data = $analytics->get_attempt_analytics( $attempt_id );
		if ( ! $data ) wp_send_json_error( array('message'=>'No data') );
		wp_send_json_success( $data );
	}

	public function gep_get_progress_chart() {
		if ( ! is_user_logged_in() ) wp_send_json_error( array('message'=>'Unauthorized') );
		$user_id = get_current_user_id();
		$test_id = isset($_POST['test_id']) ? absint($_POST['test_id']) : null;
		$analytics = new GEP_Analytics();
		$progress = $analytics->get_user_progress( $user_id, $test_id );
		wp_send_json_success( $progress );
	}

	public function gep_download_analytics_csv() {
		if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
		global $wpdb;
		$test_id = isset($_GET['test_id']) ? absint($_GET['test_id']) : 0;
		$where = "WHERE a.status = 'submitted'";
		if ( $test_id ) {
			$where .= $wpdb->prepare( ' AND a.test_id = %d', $test_id );
		}
		$rows = $wpdb->get_results(
			"SELECT a.id, u.display_name, u.user_email, a.score, a.percentage, a.is_pass, a.attempt_number,
			        a.start_time, a.end_time, TIMESTAMPDIFF(SECOND, a.start_time, a.end_time) as duration_sec
			 FROM {$wpdb->prefix}gep_attempts a
			 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
			 $where
			 ORDER BY a.score DESC"
		);
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="attempts-export.csv"');
		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		$out = fopen('php://output','w');
		fputcsv($out, ['Attempt ID','Student','Email','Score','Percentage','Pass','Attempt #','Started','Submitted','Duration (sec)']);
		foreach ( $rows as $r ) {
			fputcsv($out, [
				$r->id, $r->display_name, $r->user_email,
				$r->score, round($r->percentage,1), $r->is_pass ? 'Yes':'No',
				$r->attempt_number, $r->start_time, $r->end_time, $r->duration_sec
			]);
		}
		fclose($out);
		exit;
	}

	// ─── CSV Export: Attempts Report ─────────────────────────────────────────
	public function gep_export_attempts_csv() {
		if ( ! current_user_can('manage_options') ) wp_die('Unauthorized', 403);
		if ( ! isset($_GET['_wpnonce']) || ! wp_verify_nonce( sanitize_key($_GET['_wpnonce']), 'gep_export_csv' ) ) wp_die('Invalid nonce', 403);
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT a.id, t.title as test_title, u.display_name, u.user_email,
			        a.score, ROUND(a.percentage,2) as percentage, a.is_pass,
			        a.attempt_number, a.status, a.start_time, a.end_time,
			        TIMESTAMPDIFF(SECOND, a.start_time, a.end_time) as duration_sec,
			        a.violations
			 FROM {$wpdb->prefix}gep_attempts a
			 LEFT JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id
			 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
			 WHERE a.status = 'submitted'
			 ORDER BY a.id DESC"
		);
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="gep-attempts-' . date('Y-m-d') . '.csv"');
		echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
		$out = fopen('php://output', 'w');
		fputcsv($out, ['Attempt ID', 'Exam Title', 'Student Name', 'Email', 'Score', 'Percentage (%)', 'Passed', 'Attempt #', 'Status', 'Started', 'Submitted', 'Duration (sec)', 'Violations']);
		foreach ( $rows as $r ) {
			fputcsv($out, [
				$r->id, $r->test_title, $r->display_name, $r->user_email,
				$r->score, $r->percentage, $r->is_pass ? 'Yes' : 'No',
				$r->attempt_number, $r->status, $r->start_time, $r->end_time,
				$r->duration_sec, isset($r->violations) ? $r->violations : 0
			]);
		}
		fclose($out);
		exit;
	}

	// ─── CSV Export: Students Performance ────────────────────────────────────
	public function gep_export_students_csv() {
		if ( ! current_user_can('manage_options') ) wp_die('Unauthorized', 403);
		if ( ! isset($_GET['_wpnonce']) || ! wp_verify_nonce( sanitize_key($_GET['_wpnonce']), 'gep_export_csv' ) ) wp_die('Invalid nonce', 403);
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT u.ID as user_id, u.display_name, u.user_email,
			        COUNT(a.id) as total_attempts,
			        COUNT(CASE WHEN a.status = 'submitted' THEN 1 END) as completed,
			        SUM(CASE WHEN a.is_pass = 1 THEN 1 ELSE 0 END) as total_passes,
			        ROUND(AVG(CASE WHEN a.status='submitted' THEN a.percentage END), 2) as avg_percentage,
			        ROUND(MAX(CASE WHEN a.status='submitted' THEN a.score END), 2) as best_score,
			        MIN(a.start_time) as first_attempt,
			        MAX(a.start_time) as last_attempt
			 FROM {$wpdb->users} u
			 INNER JOIN {$wpdb->prefix}gep_attempts a ON a.user_id = u.ID
			 GROUP BY u.ID, u.display_name, u.user_email
			 ORDER BY avg_percentage DESC"
		);
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="gep-students-' . date('Y-m-d') . '.csv"');
		echo "\xEF\xBB\xBF";
		$out = fopen('php://output', 'w');
		fputcsv($out, ['User ID', 'Student Name', 'Email', 'Total Attempts', 'Completed', 'Total Passes', 'Avg Percentage (%)', 'Best Score', 'First Attempt', 'Last Attempt']);
		foreach ( $rows as $r ) {
			fputcsv($out, [
				$r->user_id, $r->display_name, $r->user_email,
				$r->total_attempts, $r->completed, $r->total_passes,
				$r->avg_percentage, $r->best_score,
				$r->first_attempt, $r->last_attempt
			]);
		}
		fclose($out);
		exit;
	}

	// ─── CSV Export: Question Bank ────────────────────────────────────────────
	public function gep_export_questions_csv() {
		if ( ! current_user_can( 'edit_posts' ) ) wp_die('Unauthorized', 403);
		if ( ! isset($_GET['_wpnonce']) || ! wp_verify_nonce( sanitize_key($_GET['_wpnonce']), 'gep_export_csv' ) ) wp_die('Invalid nonce', 403);
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT q.id, q.title, q.question_type, q.option_a, q.option_b, q.option_c, q.option_d, q.option_e,
			        q.correct_answer, q.numerical_tolerance, q.marks, q.negative_marks,
			        q.explanation, q.pyqs, q.year, q.tags,
			        c.name as subject, s.name as topic, q.status, q.created_at
			 FROM {$wpdb->prefix}gep_questions q
			 LEFT JOIN {$wpdb->prefix}gep_categories c ON q.category_id = c.id
			 LEFT JOIN {$wpdb->prefix}gep_categories s ON q.subcategory_id = s.id
			 WHERE q.status = 'publish'
			 ORDER BY q.id ASC"
		);
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="gep-questions-' . date('Y-m-d') . '.csv"');
		echo "\xEF\xBB\xBF";
		$out = fopen('php://output', 'w');
		fputcsv($out, ['ID', 'Question', 'Type', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Correct Answer', 'Numerical Tolerance', 'Marks', 'Negative Marks', 'Explanation', 'PYQ Exam', 'Year', 'Tags', 'Subject', 'Topic', 'Status', 'Created At']);
		foreach ( $rows as $r ) {
			fputcsv($out, [
				$r->id, strip_tags($r->title), $r->question_type,
				strip_tags($r->option_a), strip_tags($r->option_b),
				strip_tags($r->option_c), strip_tags($r->option_d), strip_tags(isset($r->option_e) ? $r->option_e : ''),
				$r->correct_answer, isset($r->numerical_tolerance) ? $r->numerical_tolerance : '',
				$r->marks, $r->negative_marks,
				strip_tags($r->explanation), $r->pyqs, $r->year, $r->tags,
				$r->subject, $r->topic, $r->status, $r->created_at
			]);
		}
		fclose($out);
		exit;
	}

	public function gep_get_subcategories() {
		check_ajax_referer( 'gep_admin_nonce', 'nonce' );

		if ( ! current_user_can('edit_posts') ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		if ( ! $category_id ) {
			wp_send_json_error( 'Invalid Category' );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		$subcategories = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, name FROM $table WHERE parent_id = %d ORDER BY name ASC",
			$category_id
		) );

		wp_send_json_success( $subcategories );
	}

	public function gep_submit_support_ticket() {
		check_ajax_referer( 'gep_dashboard_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Please login to submit a support request.' ) );
		}

		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

		if ( empty( $subject ) || empty( $message ) ) {
			wp_send_json_error( array( 'message' => 'Please fill all fields.' ) );
		}

		$user = wp_get_current_user();
		$to = 'help@gopath.in';
		$headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $user->display_name . ' <' . $user->user_email . '>');
		$body = "<p><strong>User:</strong> {$user->display_name} ({$user->user_email})</p><p><strong>Subject:</strong> {$subject}</p><p><strong>Message:</strong><br/>" . nl2br($message) . "</p>";

		$sent = wp_mail( $to, 'Support Request: ' . $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => 'Your message has been sent successfully. Our team will get back to you soon.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to send message. Please try again later.' ) );
		}
	}

	public function gep_get_random_test_config() {
		try {
			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => 'Unauthorized' ) );
				return;
			}
			
			global $wpdb;
			
			// Get all parent categories (Subjects)
			$subjects = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE parent_id = 0 ORDER BY name ASC" );
			
			$config = array();
			foreach ( $subjects as $sub ) {
				// Get child categories (Topics) and count questions in the bank for each
				$topics = $wpdb->get_results( $wpdb->prepare(
					"SELECT c.id, c.name, COUNT(q.id) as question_count 
					 FROM {$wpdb->prefix}gep_categories c
					 LEFT JOIN {$wpdb->prefix}gep_questions q ON c.id = q.subcategory_id AND q.status = 'publish'
					 WHERE c.parent_id = %d 
					 GROUP BY c.id 
					 ORDER BY c.name ASC",
					$sub->id
				) );
				
				$has_questions = false;
				$topics_data = array();
				foreach ( $topics as $t ) {
					$q_count = intval($t->question_count);
					if ( $q_count > 0 ) {
						$has_questions = true;
					}
					$topics_data[] = array(
						'id'             => intval($t->id),
						'name'           => $t->name,
						'question_count' => $q_count
					);
				}
				
				if ( $has_questions ) {
					$config[] = array(
						'id'     => intval($sub->id),
						'name'   => $sub->name,
						'topics' => $topics_data
					);
				}
			}
			
			wp_send_json_success( $config );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function gep_get_student_access_data() {
		check_ajax_referer( 'gep_student_access', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
		if ( ! $student_id ) {
			wp_send_json_error( 'Invalid student ID' );
		}

		global $wpdb;

		// 1. Get all published courses
		$courses = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gep_courses WHERE status = 'publish' ORDER BY title ASC" );

		// 2. Get all published tests
		$tests = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gep_tests ORDER BY title ASC" );

		// 3. Get student's current manual course access
		$current_courses = $wpdb->get_col( $wpdb->prepare(
			"SELECT course_id FROM {$wpdb->prefix}gep_user_course_access WHERE user_id = %d",
			$student_id
		) );

		// 4. Get student's current manual test access
		$current_tests = $wpdb->get_col( $wpdb->prepare(
			"SELECT test_id FROM {$wpdb->prefix}gep_user_test_access WHERE user_id = %d",
			$student_id
		) );

		wp_send_json_success( array(
			'courses'         => $courses ?: array(),
			'tests'           => $tests ?: array(),
			'current_courses' => $current_courses ?: array(),
			'current_tests'   => $current_tests ?: array(),
		) );
	}

	public function gep_save_student_access_data() {
		check_ajax_referer( 'gep_student_access', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
		if ( ! $student_id ) {
			wp_send_json_error( 'Invalid student ID' );
		}

		global $wpdb;

		// 1. Clear existing manual access records for this student
		$wpdb->delete( "{$wpdb->prefix}gep_user_course_access", array( 'user_id' => $student_id ) );
		$wpdb->delete( "{$wpdb->prefix}gep_user_test_access", array( 'user_id' => $student_id ) );

		// 2. Insert course access records
		$course_ids = isset( $_POST['course_ids'] ) && is_array( $_POST['course_ids'] ) ? array_map( 'absint', $_POST['course_ids'] ) : array();
		foreach ( $course_ids as $course_id ) {
			if ( $course_id > 0 ) {
				$wpdb->insert( "{$wpdb->prefix}gep_user_course_access", array(
					'user_id'     => $student_id,
					'course_id'   => $course_id,
					'assigned_at' => current_time( 'mysql' )
				) );
			}
		}

		// 3. Insert test access records
		$test_ids = isset( $_POST['test_ids'] ) && is_array( $_POST['test_ids'] ) ? array_map( 'absint', $_POST['test_ids'] ) : array();
		foreach ( $test_ids as $test_id ) {
			if ( $test_id > 0 ) {
				$wpdb->insert( "{$wpdb->prefix}gep_user_test_access", array(
					'user_id'     => $student_id,
					'test_id'     => $test_id,
					'granted_by'  => get_current_user_id(),
					'assigned_at' => current_time( 'mysql' )
				) );
			}
		}

		wp_cache_flush();
		wp_send_json_success( 'Access settings updated successfully' );
	}

	public function gep_save_typing_attempt() {
		check_ajax_referer( 'gep_student_access', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( 'Login required' );
		}

		$user_id  = get_current_user_id();
		$wpm      = isset( $_POST['wpm'] ) ? floatval( $_POST['wpm'] ) : 0;
		$accuracy = isset( $_POST['accuracy'] ) ? floatval( $_POST['accuracy'] ) : 0;
		$errors   = isset( $_POST['errors'] ) ? absint( $_POST['errors'] ) : 0;
		$duration = isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 0;

		global $wpdb;
		$table = $wpdb->prefix . 'gep_typing_attempts';

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'    => $user_id,
				'wpm'        => $wpm,
				'accuracy'   => $accuracy,
				'errors'     => $errors,
				'duration'   => $duration,
				'created_at' => current_time( 'mysql' )
			)
		);

		if ( $inserted ) {
			wp_send_json_success( 'Attempt logged successfully' );
		} else {
			wp_send_json_error( 'Failed to save attempt' );
		}
	}
}


