<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin attempts management logic.
 */
class GEP_Admin_Attempts {

	/**
	 * Handle admin actions for attempts.
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-attempts' ) return;
		if ( ! current_user_can( 'edit_posts' ) ) return;

		// 1. Handle Quick Grant
		if ( isset( $_POST['gep_grant_submit'] ) ) {
			if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_grant_attempt' ) ) {
				wp_redirect( admin_url( 'admin.php?page=gep-attempts&error=nonce' ) );
				exit;
			}

			$user_id = absint( $_POST['user_id'] );
			$test_id = absint( $_POST['test_id'] );
			$count   = absint( $_POST['count'] );

			if ( $user_id && $test_id && $count ) {
				$this->grant_extra_attempts( $user_id, $test_id, $count );
				wp_redirect( admin_url( 'admin.php?page=gep-attempts&message=granted' ) );
				exit;
			}
		}

		// 2. Handle Deletion
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_delete_attempt' ) ) {
				wp_redirect( admin_url( 'admin.php?page=gep-attempts&error=nonce' ) );
				exit;
			}
			$this->delete_attempt( absint( $_GET['id'] ) );
			wp_redirect( admin_url( 'admin.php?page=gep-attempts&message=deleted' ) );
			exit;
		}
	}

	/**
	 * Grant extra attempts to a student.
	 */
	public function grant_extra_attempts( $user_id, $test_id, $count ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_user_test_access';
		
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, extra_attempts FROM $table WHERE user_id = %d AND test_id = %d", $user_id, $test_id ) );

		if ( $existing ) {
			return $wpdb->update( 
				$table, 
				array( 'extra_attempts' => $existing->extra_attempts + $count ),
				array( 'id' => $existing->id )
			);
		} else {
			return $wpdb->insert( $table, array(
				'user_id'    => $user_id,
				'test_id'    => $test_id,
				'extra_attempts' => $count,
				'assigned_at' => current_time( 'mysql' ),
				'granted_by' => get_current_user_id()
			) );
		}
	}

	/**
	 * Get all attempts with user and test info.
	 */
	public function get_attempts( $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table_attempts = $wpdb->prefix . 'gep_attempts';
		$table_users = $wpdb->users;
		$table_tests = $wpdb->prefix . 'gep_tests';

		$sql = $wpdb->prepare(
			"SELECT a.*, u.display_name, t.title as test_name 
			 FROM $table_attempts a
			 JOIN $table_users u ON a.user_id = u.ID
			 JOIN $table_tests t ON a.test_id = t.id
			 ORDER BY a.id DESC
			 LIMIT %d OFFSET %d",
			$limit, $offset
		);

		return $wpdb->get_results( $sql );
	}

	/**
	 * Delete an attempt.
	 */
	public function delete_attempt( $attempt_id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'gep_attempts', array( 'id' => $attempt_id ) );
	}
}
