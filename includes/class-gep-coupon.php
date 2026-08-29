<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon System: Generation and validation.
 */
class GEP_Coupon {

	public function validate_coupon( $code, $test_id = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_coupons';

		$coupon = $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM $table WHERE code = %s AND status = 'active'", 
			sanitize_text_field( $code ) 
		) );

		if ( ! $coupon ) {
			return new WP_Error( 'invalid_coupon', 'Invalid or expired coupon code' );
		}

		// Check expiry
		if ( $coupon->expiry_date && strtotime( $coupon->expiry_date ) < time() ) {
			return new WP_Error( 'expired_coupon', 'This coupon has expired' );
		}

		// Check usage limit
		if ( $coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit ) {
			return new WP_Error( 'usage_limit', 'This coupon has reached its usage limit' );
		}

		return $coupon;
	}

	public function apply_discount( $amount, $coupon ) {
		if ( $coupon->type === 'percent' ) {
			$discount = ( $amount * $coupon->value ) / 100;
		} else {
			$discount = $coupon->value;
		}
		return min( $discount, $amount );
	}

	public function increment_usage( $coupon_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_coupons';
		return $wpdb->query( $wpdb->prepare( "UPDATE $table SET used_count = used_count + 1 WHERE id = %d", $coupon_id ) );
	}
}
