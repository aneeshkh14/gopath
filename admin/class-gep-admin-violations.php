<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin violations management logic.
 */
class GEP_Admin_Violations {

	/**
	 * Get all violations with user and attempt info.
	 */
	public function get_violations( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table_violations = $wpdb->prefix . 'gep_violations';
		$table_users = $wpdb->users;

		$sql = $wpdb->prepare(
			"SELECT v.*, u.display_name 
			 FROM $table_violations v
			 LEFT JOIN $table_users u ON v.user_id = u.ID
			 ORDER BY v.timestamp DESC
			 LIMIT %d OFFSET %d",
			$limit, $offset
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * Clear violations for a specific attempt.
	 */
	public function clear_attempt_violations( $attempt_id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'gep_violations', array( 'attempt_id' => $attempt_id ) );
	}
}
