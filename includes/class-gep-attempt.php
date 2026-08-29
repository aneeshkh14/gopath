<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attempt Control logic.
 */
class GEP_Attempt {

	public function get_attempt( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public function get_in_progress_attempt( $user_id, $test_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		return $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM $table WHERE user_id = %d AND test_id = %d AND status = 'in_progress'", 
			$user_id, $test_id 
		) );
	}

	public function grant_extra_attempts( $user_id, $test_id, $count, $admin_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_user_test_access';

		$exists = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $table WHERE user_id = %d AND test_id = %d", 
			$user_id, $test_id 
		) );

		if ( $exists ) {
			return $wpdb->query( $wpdb->prepare( 
				"UPDATE $table SET extra_attempts = extra_attempts + %d, granted_by = %d WHERE id = %d", 
				$count, $admin_id, $exists 
			) );
		} else {
			return $wpdb->insert(
				$table,
				array(
					'user_id'        => $user_id,
					'test_id'        => $test_id,
					'granted_by'     => $admin_id,
					'extra_attempts' => $count,
					'assigned_at'    => current_time( 'mysql' )
				),
				array( '%d', '%d', '%d', '%d', '%s' )
			);
		}
	}
}
