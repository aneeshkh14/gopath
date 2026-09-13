<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Result System: Handles result retrieval and display logic.
 */
class GEP_Result {

	/** Latest answered response per question, using the exam's grading rules. */
	public function get_subject_stats( $attempts ) {
		global $wpdb;
		$latest = array();
		foreach ( (array) $attempts as $attempt ) {
			$answers = json_decode( $attempt->answers, true );
			if ( ! is_array($answers) ) continue;
			foreach ( $answers as $id => $data ) {
				$id = absint($id);
				if ( !$id || isset($latest[$id]) || !is_array($data) || !isset($data['answer']) || !is_scalar($data['answer']) || trim((string)$data['answer']) === '' ) continue;
				$latest[$id] = (string) $data['answer'];
			}
		}
		if ( !$latest ) return array();
		$ids = array_keys($latest);
		$placeholders = implode(',', array_fill(0, count($ids), '%d'));
		$questions = $wpdb->get_results($wpdb->prepare(
			"SELECT q.*, c.name AS category_name FROM {$wpdb->prefix}gep_questions q
			 LEFT JOIN {$wpdb->prefix}gep_categories c ON q.category_id = c.id
			 WHERE q.id IN ($placeholders)", $ids
		));
		$stats = array();
		foreach ( (array) $questions as $q ) {
			$name = !empty($q->category_name) ? $q->category_name : 'General';
			if ( !isset($stats[$name]) ) $stats[$name] = array('total' => 0, 'correct' => 0);
			$stats[$name]['total']++;
			if ( GEP_Exam_Engine::evaluate_answer($q, $latest[$q->id]) ) $stats[$name]['correct']++;
		}
		return $stats;
	}

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
			"SELECT a.*, COALESCE(t.total_marks, 0) AS total_marks, COALESCE(t.title, 'PYQ Practice Test') as test_name
			 FROM $attempts_table a
			 LEFT JOIN $tests_table t ON a.test_id = t.id
			 WHERE a.user_id = %d AND a.status = 'submitted'
			 ORDER BY a.end_time DESC, a.id DESC",
			$user_id
		) );

		foreach ( $results as $r ) {
            $snapshot = json_decode($r->analytics_data ?? '', true);
            if (isset($snapshot['total_marks'])) $r->total_marks = (float)$snapshot['total_marks'];
            elseif ((float)$r->percentage != 0) $r->total_marks = round((float)$r->score * 100 / (float)$r->percentage, 4);
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
