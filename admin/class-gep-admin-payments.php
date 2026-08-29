<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin payments/orders management logic.
 */
class GEP_Admin_Payments {

	/**
	 * Get all orders with user and test info.
	 */
	public function get_orders( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table_orders = $wpdb->prefix . 'gep_orders';
		$table_users = $wpdb->users;
		$table_tests = $wpdb->prefix . 'gep_tests';

		$sql = $wpdb->prepare(
			"SELECT o.*, u.display_name, t.title as test_title 
			 FROM $table_orders o
			 LEFT JOIN $table_users u ON o.user_id = u.ID
			 LEFT JOIN $table_tests t ON o.test_id = t.id
			 ORDER BY o.created_at DESC
			 LIMIT %d OFFSET %d",
			$limit, $offset
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * Manual success update (e.g. for offline payments).
	 */
	public function mark_as_success( $order_id ) {
		global $wpdb;
		$order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gep_orders WHERE id = %d", $order_id ) );
		
		if ( ! $order ) return false;

		$payment = new GEP_Payment();
		return $wpdb->update(
			"{$wpdb->prefix}gep_orders",
			array( 'status' => 'success' ),
			array( 'id' => $order_id )
		);
		// Note: This should ideally call grant_access as well
	}
}
