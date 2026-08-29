<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Result System: Handles result retrieval and display logic.
 */
class GEP_Result {

	public function get_attempt_result( $attempt_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $attempt_id ) );
	}

	public function get_attempt_details( $attempt_id ) {
		$attempt = $this->get_attempt_result( $attempt_id );
		if ( ! $attempt ) return false;

		$test_logic = new GEP_Test();
		$test = $test_logic->get_test( $attempt->test_id );
		
		$question_ids = $test_logic->get_test_questions( $test->id, $attempt->id );
		$question_logic = new GEP_Question();
		$questions = $question_logic->get_questions_by_ids( $question_ids );

		$answers = json_decode( $attempt->answers, true );

		return array(
			'attempt'   => $attempt,
			'test'      => $test,
			'questions' => $questions,
			'answers'   => $answers
		);
	}

	public function get_user_attempts( $user_id, $test_id = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		$sql = "SELECT * FROM $table WHERE user_id = %d";
		$params = array( $user_id );

		if ( $test_id ) {
			$sql .= " AND test_id = %d";
			$params[] = $test_id;
		}

		$sql .= " ORDER BY start_time DESC";
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public function get_user_results( $user_id ) {
		global $wpdb;
		$attempts_table = $wpdb->prefix . 'gep_attempts';
		$tests_table = $wpdb->prefix . 'gep_tests';
		
		$results = $wpdb->get_results( $wpdb->prepare( 
			"SELECT a.*, COALESCE(t.title, 'PYQ Practice Test') as test_name 
			 FROM $attempts_table a 
			 LEFT JOIN $tests_table t ON a.test_id = t.id 
			 WHERE a.user_id = %d AND a.status = 'submitted' 
			 ORDER BY a.end_time DESC", 
			$user_id 
		) );

		foreach ( $results as $r ) {
			if ( $r->test_id == 999999 && ! empty( $r->analytics_data ) ) {
				$analytics = json_decode( $r->analytics_data, true );
				if ( isset( $analytics['practice_title'] ) ) {
					$r->test_name = $analytics['practice_title'];
				}
			}
		}

		return $results;
	}
}
