<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment System: Razorpay integration and Order handling.
 */
class GEP_Payment {

	private $key_id;
	private $key_secret;

	public function __construct() {
		$this->key_id = get_option( 'gep_razorpay_key_id' );
		$this->key_secret = $this->decrypt( get_option( 'gep_razorpay_key_secret' ) );
	}

	/** Keep history even when a purchased item has since been removed. */
	public function get_user_orders( $user_id ) {
		global $wpdb;
		$orders = $wpdb->get_results( $wpdb->prepare(
			"SELECT o.*, CASE WHEN o.item_type = 'course' THEN c.title ELSE t.title END AS item_title
			 FROM {$wpdb->prefix}gep_orders o
			 LEFT JOIN {$wpdb->prefix}gep_tests t ON o.item_type = 'test' AND o.item_id = t.id
			 LEFT JOIN {$wpdb->prefix}gep_courses c ON o.item_type = 'course' AND o.item_id = c.id
			 WHERE o.user_id = %d ORDER BY o.created_at DESC, o.id DESC", $user_id
		) );
		$pass_names = array(1 => '1-Month Mock Test Pass', 2 => 'Yearly Mock Test Pass Pro', 3 => 'Lifetime Mock Test Pass Ultimate');
		foreach ( (array) $orders as $order ) {
			if ( $order->item_type === 'pass' ) {
				$order->item_title = isset($pass_names[$order->item_id]) ? $pass_names[$order->item_id] : 'Mock Test Pass';
			}
			if ( empty($order->item_title) ) $order->item_title = 'Unavailable item #' . (int) $order->item_id;
		}
		return (array) $orders;
	}

	/**
	 * Create a real Razorpay order.
	 */
	public function create_order( $item_id, $item_type = 'test', $coupon_code = '', $attempts = 0 ) {
		global $wpdb;
        if (!in_array($item_type, array('test','course','pass'), true) || $item_id < 1) return new WP_Error('invalid_item', 'Choose a valid item.');
		
		if ( $item_type === 'pass' ) {
			$pass_plans = array(
				1 => (object) array(
					'id'          => 1,
					'title'       => '1-Month Mock Test Pass',
					'description' => 'Get full access to all test series, exam formats, and PYQs for 30 days.',
					'price'       => 99,
				),
				2 => (object) array(
					'id'          => 2,
					'title'       => 'Yearly Mock Test Pass Pro',
					'description' => 'Get full access to all test series, exam formats, and PYQs for 365 days.',
					'price'       => 299,
				),
				3 => (object) array(
					'id'          => 3,
					'title'       => 'Lifetime Mock Test Pass Ultimate',
					'description' => 'Unlock all present and future exams and test series forever.',
					'price'       => 599,
				),
			);
			$item = isset( $pass_plans[$item_id] ) ? $pass_plans[$item_id] : null;
		} else {
			$table = ( $item_type === 'course' ) ? "{$wpdb->prefix}gep_courses" : "{$wpdb->prefix}gep_tests";
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $item_id ) );
		}
		
		if ( ! $item ) return new WP_Error( 'invalid_item', 'Item not found' );

        if (isset($item->status) && $item->status !== 'publish') return new WP_Error('unavailable', 'This item is not currently available.');
        if (!is_numeric($item->price) || !is_finite((float)$item->price) || $item->price < 0) return new WP_Error('invalid_price', 'This item needs a valid price before checkout.');
		$amount = !empty($item->is_free) ? 0 : $item->price;
		$discount = 0;

        if ($item_type === 'test' && isset($item->type) && $item->type === 'random' && empty($item->is_free) && $attempts <= 0) return new WP_Error('invalid_tier', 'Select an attempts package before paying.');
		if ( $item_type === 'test' && isset($item->type) && $item->type === 'random' && empty($item->is_free) && $attempts > 0 ) {
			$trans = !empty($item->translated_data) ? gep_safe_json_decode($item->translated_data, true) : array();
			$attempt_pricing = isset($trans['attempt_pricing']) ? $trans['attempt_pricing'] : array();
			$found_price = false;
			foreach ( $attempt_pricing as $tier ) {
				if ( intval($tier['attempts']) === intval($attempts) ) {
					$amount = floatval($tier['price']);
					$found_price = true;
					break;
				}
			}
			if ( ! $found_price ) {
				return new WP_Error( 'invalid_tier', 'Selected attempts package pricing not found.' );
			}
		}

		if ( ! empty( $coupon_code ) ) {
			$coupon_res = $this->validate_coupon( $coupon_code, $item_id, $item_type, $attempts );
			if ( is_wp_error( $coupon_res ) ) return $coupon_res;
			if ( ! is_wp_error( $coupon_res ) ) {
				$discount = $coupon_res['discount'];
				$amount -= $discount;
			}
		}

		if ( $amount < 0 ) $amount = 0;

        if ($amount > 0 && (empty($this->key_id) || empty($this->key_secret))) return new WP_Error('gateway_unavailable', 'Payments are temporarily unavailable. Please contact support.');
		// Create record in our orders table first as pending
		$inserted = $wpdb->insert(
			"{$wpdb->prefix}gep_orders",
			array(
				'user_id'     => get_current_user_id(),
				'item_id'     => $item_id,
				'item_type'   => $item_type,
				'amount'      => $amount,
				'discount'    => $discount,
				'coupon_code' => $coupon_code,
				'status'      => 'pending',
				'attempts'    => $attempts,
				'created_at'  => current_time( 'mysql' )
			)
		);
		$order_db_id = $wpdb->insert_id;
		if ( $inserted === false || !$order_db_id ) return new WP_Error('order_failed', 'Could not create the order. Please retry.');

		// If free, grant access immediately and redirect to My Purchases view
		if ( $amount <= 0 ) {
			if ( $wpdb->query('START TRANSACTION') === false ) return new WP_Error('order_failed', 'Could not complete enrollment. Please retry.');
			$granted = $this->grant_access( get_current_user_id(), $item_id, $item_type, $order_db_id );
			$recorded = $wpdb->update("{$wpdb->prefix}gep_orders", array('status' => 'success'), array('id' => $order_db_id));
			if ( ! $granted || $recorded === false ) { $wpdb->query('ROLLBACK'); return new WP_Error('order_failed', 'Could not complete enrollment. Please retry.'); }
			if (!empty($coupon_code) && $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gep_coupons SET used_count = used_count + 1 WHERE code = %s", $coupon_code)) === false) { $wpdb->query('ROLLBACK'); return new WP_Error('order_failed', 'Could not confirm the coupon. Please retry.'); }
			if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); return new WP_Error('order_failed', 'Enrollment is not confirmed. Please retry.'); }
			// BUG FIX: Redirect to ?view=purchases so user sees their newly enrolled item
			return array( 'status' => 'free', 'redirect' => add_query_arg( 'view', 'purchases', (string) gep_get_url( 'dashboard' ) ) );
		}

		// Call Razorpay API
		$url = 'https://api.razorpay.com/v1/orders';
		$data = array(
			'amount'   => round( $amount * 100 ), // In paise
			'currency' => 'INR',
			'receipt'  => 'order_rcptid_' . $order_db_id
		);

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->key_id . ':' . $this->key_secret ),
				'Content-Type'  => 'application/json'
			),
			'body'    => json_encode( $data ),
			'timeout' => 15, // seconds
		) );

		if ( is_wp_error( $response ) ) {
			error_log( '[GEP Payment] Razorpay API WP_Error: ' . $response->get_error_message() );
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$res_body  = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( $http_code !== 200 || ! isset( $res_body['id'] ) ) {
			$err_desc = isset( $res_body['error']['description'] ) ? $res_body['error']['description'] : 'Unknown Razorpay error';
			error_log( '[GEP Payment] Razorpay API HTTP ' . $http_code . ': ' . $err_desc . ' | key_id starts: ' . substr( $this->key_id, 0, 8 ) );
			return new WP_Error( 'razorpay_error', 'Payment gateway error: ' . $err_desc );
		}

		$recorded = $wpdb->update(
			"{$wpdb->prefix}gep_orders",
			array( 'razorpay_order_id' => $res_body['id'] ),
			array( 'id' => $order_db_id )
		);
        if ($recorded === false) return new WP_Error('order_failed', 'Could not prepare a recoverable payment. Please retry before paying.');
		return $res_body;
	}

	/**
	 * Verify payment signature.
	 */
	public function verify_payment( $razorpay_order_id, $razorpay_payment_id, $razorpay_signature, $item_id = 0, $item_type = 'test' ) {
        if (empty($this->key_secret) || !$razorpay_order_id || !$razorpay_payment_id) return false;
		$expected_signature = hash_hmac( 'sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $this->key_secret );
		
		if ( ! hash_equals( $expected_signature, $razorpay_signature ) ) {
			return false;
		}

		global $wpdb;
		// Serialize callbacks for the exact gateway order. Granting attempts and
		// recording success belong to one transaction, so a retry cannot double-grant.
		if ( $wpdb->query('START TRANSACTION') === false ) return false;
		$rollback = static function() use ($wpdb) {
			$wpdb->query('ROLLBACK');
			wp_cache_delete(get_current_user_id(), 'user_meta');
			return false;
		};
		$order = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}gep_orders WHERE razorpay_order_id = %s FOR UPDATE",
			$razorpay_order_id
		) );
		// Never attach payment to a different 'latest pending' order after a failed lookup.
		if ( ! $order || (int) $order->user_id !== get_current_user_id() ) return $rollback();
		if ( $item_id && ((int) $order->item_id !== (int) $item_id || $order->item_type !== $item_type) ) return $rollback();
		if ( $order->status === 'success' ) {
			return $wpdb->query('COMMIT') !== false;
		}
		if ( ! $this->grant_access( $order->user_id, $order->item_id, $order->item_type, $order->id ) ) return $rollback();
		$updated = $wpdb->update(
			"{$wpdb->prefix}gep_orders",
			array('razorpay_payment_id' => $razorpay_payment_id, 'status' => 'success'),
			array('id' => $order->id)
		);
		if ( $updated === false ) return $rollback();
		if ( ! empty($order->coupon_code) ) {
			$used = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gep_coupons SET used_count = used_count + 1 WHERE code = %s", $order->coupon_code));
			if ( $used === false ) return $rollback();
		}
		if ( $wpdb->query('COMMIT') === false ) return $rollback();
		return true;
	}

	private function grant_access( $user_id, $item_id, $item_type, $order_id ) {
		global $wpdb;

		if ( $item_type === 'pass' ) {
            // Serialize renewals for this account, including different paid orders.
            if (!$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d FOR UPDATE", $user_id))) return false;
            wp_cache_delete($user_id, 'user_meta');
            $current_expiry = strtotime((string)get_user_meta($user_id, 'gep_pass_expiry', true));
            $starts_at = max(current_time('timestamp'), $current_expiry ?: 0);
			$duration = '+30 days';
			if ( $item_id == 2 ) {
				$duration = '+365 days';
			} elseif ( $item_id == 3 ) {
				$duration = '+100 years';
			}
			$expiry = date( 'Y-m-d H:i:s', strtotime( $duration, $starts_at ) );
			return update_user_meta( $user_id, 'gep_pass_expiry', $expiry ) !== false || get_user_meta($user_id, 'gep_pass_expiry', true) === $expiry;
		}

		if ( $item_type === 'course' ) {
			$table  = "{$wpdb->prefix}gep_user_course_access";
			$column = 'course_id';
		} else {
			$table  = "{$wpdb->prefix}gep_user_test_access";
			$column = 'test_id';
		}

		$attempts = 0;
		if ( $order_id && $item_type !== 'course' ) {
			$order = $wpdb->get_row( $wpdb->prepare( "SELECT attempts FROM {$wpdb->prefix}gep_orders WHERE id = %d", $order_id ) );
			$attempts = $order ? intval($order->attempts) : 0;
		}

		// Check if it's a random test to increment attempts
		$test = $wpdb->get_row( $wpdb->prepare( "SELECT type FROM {$wpdb->prefix}gep_tests WHERE id = %d", $item_id ) );
		if ( $item_type === 'test' && $test && $test->type === 'random' ) {
			$already_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $table WHERE user_id = %d AND test_id = %d",
				$user_id, $item_id
			) );
			if ( $already_exists ) {
				$granted = $wpdb->query( $wpdb->prepare(
					"UPDATE $table SET extra_attempts = extra_attempts + %d WHERE id = %d",
					$attempts, $already_exists
				) );
			} else {
				$granted = $wpdb->insert(
					$table,
					array(
						'user_id'        => $user_id,
						'test_id'        => $item_id,
						'extra_attempts' => $attempts,
						'assigned_at'    => current_time( 'mysql' )
					)
				);
			}
			return $granted !== false;
		}

		// Duplicate guard: do not insert if access already exists (legacy tests/courses)
		$already_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table WHERE user_id = %d AND $column = %d",
			$user_id, $item_id
		) );

		if ( $already_exists ) return true;

		return $wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				$column       => $item_id,
				'assigned_at' => current_time( 'mysql' )
			)
		) !== false;
	}

	public function validate_coupon( $code, $item_id, $item_type = 'test', $attempts = 0 ) {
		global $wpdb;
		$coupon = $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM {$wpdb->prefix}gep_coupons WHERE code = %s AND status = 'active'", 
			$code 
		) );

		if ( ! $coupon ) return new WP_Error( 'invalid_coupon', 'Invalid coupon code' );

		if ( $coupon->expiry_date && strtotime( $coupon->expiry_date ) < current_time('timestamp') ) {
			return new WP_Error( 'expired_coupon', 'Coupon has expired' );
		}

		if ( $coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit ) {
			return new WP_Error( 'limit_reached', 'Coupon usage limit reached' );
		}

		if ( $item_type === 'pass' ) {
			$pass_plans = array(
				1 => (object) array( 'price' => 99 ),
				2 => (object) array( 'price' => 299 ),
				3 => (object) array( 'price' => 599 ),
			);
			$item = isset( $pass_plans[$item_id] ) ? $pass_plans[$item_id] : null;
		} else {
			$table = ( $item_type === 'course' ) ? "{$wpdb->prefix}gep_courses" : "{$wpdb->prefix}gep_tests";
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $item_id ) );
		}
		if ( ! $item ) return new WP_Error( 'error', 'Invalid item' );

		$base_price = !empty($item->is_free) ? 0 : $item->price;
		if ( $item_type === 'test' && isset($item->type) && $item->type === 'random' && empty($item->is_free) && $attempts > 0 ) {
			$trans = !empty($item->translated_data) ? gep_safe_json_decode($item->translated_data, true) : array();
			$attempt_pricing = isset($trans['attempt_pricing']) ? $trans['attempt_pricing'] : array();
			foreach ( $attempt_pricing as $tier ) {
				if ( intval($tier['attempts']) === intval($attempts) ) {
					$base_price = floatval($tier['price']);
					break;
				}
			}
		}

		$discount = ( $coupon->type === 'percent' ) ? ( $base_price * $coupon->value / 100 ) : $coupon->value;
        $discount = min($base_price, max(0, $discount));

		return array(
			'code' => $code,
			'discount' => $discount,
			'new_total' => max( 0, $base_price - $discount )
		);
	}

	private function decrypt( $value ) {
		if ( empty( $value ) ) return '';
		$decoded = base64_decode( $value, true );
		if ( $decoded !== false && preg_match( '/^[[:print:]\r\n\t]*$/', $decoded ) ) {
			return $decoded;
		}
		return $value;
	}
}
