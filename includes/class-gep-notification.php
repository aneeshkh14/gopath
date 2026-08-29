<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Notification system logic.
 */
class GEP_Notification {

	public function send_notification( $user_id, $title, $message ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'gep_notifications', array(
			'user_id'    => $user_id,
			'title'      => $title,
			'message'    => $message,
			'created_at' => current_time( 'mysql' )
		) );
	}

	public function get_user_notifications( $user_id, $limit = 10 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM {$wpdb->prefix}gep_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT %d", 
			$user_id, $limit 
		) );
	}
}
