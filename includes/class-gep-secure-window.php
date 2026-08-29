<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Secure Exam Window: Handles violation tracking and lockdown logic.
 */
class GEP_Secure_Window {

	public function log_violation( $attempt_id, $user_id, $type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_violations';
		$violation_type = sanitize_text_field( $type );

		$existing = $wpdb->get_row( $wpdb->prepare( 
			"SELECT id, count FROM $table WHERE attempt_id = %d AND violation_type = %s", 
			$attempt_id, $violation_type 
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 
					'count'     => $existing->count + 1,
					'timestamp' => current_time( 'mysql' )
				),
				array( 'id' => $existing->id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'attempt_id'     => $attempt_id,
					'user_id'        => $user_id,
					'violation_type' => $violation_type,
					'timestamp'      => current_time( 'mysql' ),
					'count'          => 1
				),
				array( '%d', '%d', '%s', '%s', '%d' )
			);
		}

		// Check if we need to auto-submit
		$violation_action = get_option( 'gep_violation_action', 'warn' );
		if ( $violation_action === 'submit' ) {
			$limit = get_option( 'gep_violation_limit', 3 );
			$total_count = $wpdb->get_var( $wpdb->prepare( 
				"SELECT SUM(count) FROM $table WHERE attempt_id = %d", 
				$attempt_id 
			) );

			if ( $total_count >= $limit ) {
				$engine = new GEP_Exam_Engine();
				$engine->submit_exam( $attempt_id );
				return 'auto_submitted';
			}
		}

		return 'logged';
	}

	public function get_violations( $attempt_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_violations';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE attempt_id = %d", $attempt_id ) );
	}
}
