<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exam Engine: Session management, timer, and results calculation.
 */
class GEP_Exam_Engine {

	/**
	 * Start a new attempt or resume an existing one.
	 */
	public function start_attempt( $test_id, $user_id, $initial_data = array() ) {
        return $this->in_transaction(function() use ($test_id, $user_id, $initial_data) {
            global $wpdb;
            // Serialize starts per student, including two tabs starting together.
            if (!$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID = %d FOR UPDATE", $user_id))) return false;
            return $this->start_attempt_locked($test_id, $user_id, $initial_data);
        });
    }

    private function in_transaction($callback) {
        global $wpdb;
        if ($wpdb->query('START TRANSACTION') === false) return false;
        try {
            $result = $callback();
            if ($result === false || is_wp_error($result)) { $wpdb->query('ROLLBACK'); return $result; }
            if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); return false; }
            return $result;
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    private function start_attempt_locked( $test_id, $user_id, $initial_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';

		// 1. Check for in-progress attempt
		$in_progress = $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM $table WHERE user_id = %d AND test_id = %d AND status = 'in_progress'", 
			$user_id, $test_id 
		) );

        if ( $in_progress ) {
            if ($test_id === 999999 && !empty($initial_data['analytics_data'])) {
                $existing = json_decode($in_progress->analytics_data ?? '', true) ?: array();
                $requested = json_decode($initial_data['analytics_data'], true) ?: array();
                if (($existing['type'] ?? '') !== ($requested['type'] ?? '') || ($existing['target'] ?? '') !== ($requested['target'] ?? '')) {
                    return new WP_Error('practice_in_progress', 'Finish or submit your current practice test before starting a different one. You can resume it from your dashboard.');
                }
            }
            return (int)$in_progress->id;
		}

		// 2. Centralized Eligibility Check
		$eligibility = $this->check_eligibility( $user_id, $test_id );
		if ( is_wp_error( $eligibility ) ) {
			return $eligibility;
		}

        if (empty($initial_data['question_ids'])) {
            $ids = (new GEP_Test())->get_test_questions($test_id);
            if (!$ids) return new WP_Error('empty_test', 'This test has no questions yet. Please contact support.');
            $initial_data['question_ids'] = implode(',', $ids);
        }

		// 3. Create new attempt
		$attempt_number = $this->get_next_attempt_number( $user_id, $test_id );

		$inserted = $wpdb->insert(
			$table,
			array(
				'user_id'        => $user_id,
				'test_id'        => $test_id,
				'start_time'     => current_time( 'mysql' ),
				'status'         => 'in_progress',
				'answers'        => json_encode( array() ),
				'attempt_number' => $attempt_number,
                'question_ids' => $initial_data['question_ids'] ?? '',
                'analytics_data' => $initial_data['analytics_data'] ?? '{}'
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return $inserted === false ? false : (int)$wpdb->insert_id;
	}

	public function check_eligibility( $user_id, $test_id ) {
		$test_logic = new GEP_Test();
		$test = $test_logic->get_test( $test_id );

		if ( ! $test ) {
			return new WP_Error( 'not_found', 'Test not found.' );
		}

        if ($test->status !== 'publish' && !current_user_can('manage_options')) return new WP_Error('unavailable', 'This test is not available yet.');

		// Check Validity Date
		if ( $test->validity_date && $test->validity_date !== '0000-00-00 00:00:00' ) {
			$validity_timestamp = strtotime( $test->validity_date );
			if ( $validity_timestamp !== false && $validity_timestamp < time() ) {
				return new WP_Error( 'expired', 'This test has expired.' );
			}
		}

		// Check Access
		if ( ! $test_logic->user_has_access( $user_id, $test_id ) ) {
			return new WP_Error( 'no_access', 'You do not have access to this test. Please purchase it first.' );
		}

		// Check Attempt Limit
		$remaining = $test_logic->get_remaining_attempts( $user_id, $test_id );
		if ( $remaining <= 0 ) {
			return new WP_Error( 'limit_reached', 'You have exhausted your attempt limit for this test.' );
		}

		return true;
	}

	private function get_next_attempt_number( $user_id, $test_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		$count = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM $table WHERE user_id = %d AND test_id = %d", 
			$user_id, $test_id 
		) );
		return $count + 1;
	}

	public function save_answer( $attempt_id, $question_id, $answer, $flagged = false ) {
        return $this->in_transaction(function() use ($attempt_id, $question_id, $answer, $flagged) {
            return $this->save_answer_locked($attempt_id, $question_id, $answer, $flagged);
        });
    }

    private function save_answer_locked( $attempt_id, $question_id, $answer, $flagged ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';

		$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d FOR UPDATE", $attempt_id ) );
		if ( ! $attempt ) return false;
		if ( $attempt->user_id != get_current_user_id() || $attempt->status !== 'in_progress' ) return false;

        $question_ids = !empty($attempt->question_ids) ? array_map('absint', explode(',', $attempt->question_ids)) : (new GEP_Test())->get_test_questions($attempt->test_id);
        if (!in_array((int)$question_id, array_map('intval', $question_ids), true)) return false;

		// BUG-E FIX: Do NOT block save_answer on timer expiry.
		// The exam JS stops sending saves when the timer hits 0, and auto-submits.
		// Blocking here silently discards answers submitted right at the buzzer.
		// Timing enforcement is the sole responsibility of submit_exam().

		$answers = json_decode( $attempt->answers, true );
		if ( ! is_array( $answers ) ) $answers = array();

		$answers[$question_id] = array(
			'answer'  => $answer,
			'flagged' => $flagged,
			'time'    => current_time( 'mysql' ),
			'time_ms' => isset($_POST['time_ms']) ? absint($_POST['time_ms']) : 0,
			'visits'  => isset($answers[$question_id]['visits']) ? ($answers[$question_id]['visits'] + 1) : 1,
		);

		return $wpdb->update(
			$table,
			array( 'answers' => json_encode( $answers ) ),
			array( 'id' => $attempt_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function submit_exam( $attempt_id ) {
        return $this->in_transaction(function() use ($attempt_id) { return $this->submit_exam_locked($attempt_id); });
    }

    private function submit_exam_locked( $attempt_id ) {
		global $wpdb;
		$table_attempts = $wpdb->prefix . 'gep_attempts';
		$table_tests = $wpdb->prefix . 'gep_tests';

		$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_attempts WHERE id = %d FOR UPDATE", $attempt_id ) );
		if ( ! $attempt ) return false;

		// Ownership check: allow server-side auto-submit (get_current_user_id()=0 in cron/violation context)
		$current_uid = get_current_user_id();
		if ( $current_uid > 0 && $attempt->user_id != $current_uid ) return false;

		// Graceful Redirection Bypass: If already submitted (e.g., auto-submitted or double-clicked), return true
		if ( $attempt->status === 'submitted' ) return true;

		$test = (new GEP_Test())->get_test((int)$attempt->test_id, $attempt);
		
		if ( ! $test ) {
			return false; // Cannot grade without the test definition
		}
		
		// If expired but still in progress, we allow final submission but we enforce the duration logic if needed
		// The calculate_results already uses the current $attempt->answers
		$results = $this->calculate_results( $attempt, $test );

		// Compute per-section scores from answers
		$section_scores = array();
		$analytics_data = array();
		if ( ! empty( $test->translated_data ) ) {
			$td = gep_safe_json_decode( $test->translated_data, true );
			$sections = isset($td['sections']) ? $td['sections'] : array();
			$answers_arr = json_decode( $attempt->answers, true ) ?: array();
			$q_logic = new GEP_Question();
			$global_neg = isset($td['global_negative_marks']) && $td['global_negative_marks'] !== '' ? floatval($td['global_negative_marks']) : null;
			foreach ( $sections as $sec ) {
				$sec_ids = array_filter( array_map('absint', explode(',', isset($sec['ids']) ? $sec['ids'] : '')) );
				$sec_score = 0;
				$sec_questions = $q_logic->get_questions_by_ids( $sec_ids );
				$sec_questions = self::apply_section_overrides( $sec_questions, $test );
				foreach ( $sec_questions as $sq ) {
					if ( isset($answers_arr[$sq->id]['answer']) && $answers_arr[$sq->id]['answer'] !== '' ) {
						if ( GEP_Exam_Engine::evaluate_answer($sq, $answers_arr[$sq->id]['answer']) ) {
							$sec_score += $sq->marks;
						} else {
							$penalty = $global_neg !== null ? $global_neg : $sq->negative_marks;
							$sec_score -= $penalty;
						}
					}
				}
				$section_scores[] = array(
					'name'  => isset($sec['name']) ? $sec['name'] : 'Section',
					'score' => $sec_score,
					'total' => count($sec_questions),
				);
			}
		}
		// Basic analytics data
		$answers_for_analytics = json_decode( $attempt->answers, true ) ?: array();
		$times = array_filter(array_column(array_values($answers_for_analytics), 'time_ms'));
		$analytics_data = array_merge(json_decode($attempt->analytics_data ?? '', true) ?: array(), array(
			'total_marks'    => $results['total_marks'],
			'avg_time_ms'    => count($times) ? (int)(array_sum($times)/count($times)) : 0,
			'total_answered' => count(array_filter($answers_for_analytics, fn($a)=>isset($a['answer'])&&$a['answer']!=='')),
			'total_flagged'  => count(array_filter($answers_for_analytics, fn($a)=>!empty($a['flagged']))),
		));

		$updated = $wpdb->update(
			$table_attempts,
			array(
				'status'         => 'submitted',
				'end_time'       => current_time( 'mysql' ),
				'score'          => $results['score'],
				'percentage'     => $results['percentage'],
				'is_pass'        => $results['is_pass'],
				'section_scores' => json_encode( $section_scores ),
				'analytics_data' => json_encode( $analytics_data ),
			),
			array( 'id' => $attempt_id ),
			array( '%s', '%s', '%f', '%f', '%d', '%s', '%s' ),
			array( '%d' )
		);

		return $updated !== false;
	}

	private function calculate_results( $attempt, $test ) {
		$answers = json_decode( $attempt->answers, true );
		if ( ! is_array( $answers ) ) {
			$answers = array();
		}
		
		$question_logic = new GEP_Question();
		$test_logic = new GEP_Test();
		$question_ids = $test_logic->get_test_questions( $test->id, $attempt->id );
		$questions = $question_logic->get_questions_by_ids( $question_ids );
		$questions = self::apply_section_overrides( $questions, $test );

		$score = 0;
		$total_max_marks = 0;

		foreach ( $questions as $q ) {
			$total_max_marks += $q->marks;

			if ( isset( $answers[$q->id] ) ) {
				$user_ans    = isset($answers[$q->id]['answer']) ? $answers[$q->id]['answer'] : '';

				if ( $user_ans !== '' ) {
					$is_correct = self::evaluate_answer( $q, $user_ans );

					if ( $is_correct ) {
						$score += $q->marks;
					} else {
						$td = gep_safe_json_decode( $test->translated_data, true );
						$global_neg = isset($td['global_negative_marks']) && $td['global_negative_marks'] !== '' ? floatval($td['global_negative_marks']) : null;
						$penalty = $global_neg !== null ? $global_neg : $q->negative_marks;
						$score -= $penalty;
					}
				}
			}
		}

		$percentage = ( $total_max_marks > 0 ) ? ( $score / $total_max_marks ) * 100 : 0;
		// pass_marks is explicitly treated as Percentage required to pass
		$is_pass = ( $percentage >= $test->pass_marks ) ? 1 : 0;

		return array(
			'total_marks' => $total_max_marks,
			'score'      => $score,
			'percentage' => $percentage,
			'is_pass'    => $is_pass
		);
	}

	public static function apply_section_overrides( $questions, $test ) {
		if ( empty( $questions ) || empty( $test->translated_data ) ) {
			return $questions;
		}
		$td = gep_safe_json_decode( $test->translated_data, true );
		if ( ! isset( $td['sections'] ) || ! is_array( $td['sections'] ) ) {
			return $questions;
		}

		$overrides = array();
		foreach ( $td['sections'] as $sec ) {
			if ( ! isset( $sec['ids'] ) || empty( $sec['ids'] ) ) {
				continue;
			}
			$sec_ids = array_filter( array_map( 'absint', explode( ',', $sec['ids'] ) ) );
			$sec_marks = isset( $sec['marks'] ) && $sec['marks'] !== '' ? floatval( $sec['marks'] ) : null;
			$sec_neg = isset( $sec['negative_marks'] ) && $sec['negative_marks'] !== '' ? floatval( $sec['negative_marks'] ) : null;

			foreach ( $sec_ids as $qid ) {
				$overrides[ $qid ] = array(
					'marks'          => $sec_marks,
					'negative_marks' => $sec_neg
				);
			}
		}

		foreach ( $questions as $q ) {
			if ( isset( $overrides[ $q->id ] ) ) {
				if ( $overrides[ $q->id ]['marks'] !== null ) {
					$q->marks = $overrides[ $q->id ]['marks'];
				}
				if ( $overrides[ $q->id ]['negative_marks'] !== null ) {
					$q->negative_marks = $overrides[ $q->id ]['negative_marks'];
				}
			}
		}

		return $questions;
	}

	public static function evaluate_answer( $question, $user_ans ) {
		$correct_ans = isset($question->correct_answer) ? $question->correct_answer : '';
		$qtype       = isset($question->question_type) ? $question->question_type : 'mcq';

		if ( trim($user_ans) === '' ) return false;

		if ( $qtype === 'numerical' ) {
			if ( ! is_numeric(trim((string) $user_ans)) || ! is_numeric(trim((string) $correct_ans)) ) return false;
			// NTA-style numerical: check within tolerance
			$tolerance = isset($question->numerical_tolerance) && $question->numerical_tolerance > 0 ? floatval($question->numerical_tolerance) : 0.01;
			$user_num  = floatval($user_ans);
			$correct_num = floatval($correct_ans);
			return abs($user_num - $correct_num) <= $tolerance;
		} elseif ( $qtype === 'short_answer' ) {
			// Free text must not inherit MCQ option aliases such as 1 => A.
			$normalize = static function($text) { return strtolower(preg_replace('/\s+/u', ' ', trim((string) $text))); };
			return $normalize($user_ans) === $normalize($correct_ans);
		} elseif ( $qtype === 'true_false' ) {
			// Case-insensitive true/false
			return strtolower(trim($user_ans)) === strtolower(trim($correct_ans));
		} else {
			// MCQ / MSQ / assertion_reason / short_answer etc.
			$normalize_ans = function( $val ) {
				$val = (string) $val;
				// Map numbers, Devanagari, and Hindi option formats to standard letter choices (A-E)
				$map = array(
					'1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E',
					'१' => 'A', '२' => 'B', '३' => 'C', '४' => 'D', '५' => 'E',
					'क' => 'A', 'ख' => 'B', 'ग' => 'C', 'घ' => 'D', 'ङ' => 'E'
				);
				$parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $val ) ) ) );
				foreach ( $parts as &$part ) {
					if ( isset( $map[$part] ) ) {
						$part = $map[$part];
					}
				}
				sort( $parts );
				return implode( ',', $parts );
			};
			return $normalize_ans( $user_ans ) === $normalize_ans( $correct_ans );
		}
	}

	public function get_test_leaderboard( $test_id, $limit = 10 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';

		// Ranking criteria: Score DESC, Time Taken ASC
		// Time Taken = TIMESTAMPDIFF(SECOND, start_time, end_time)
		$query = $wpdb->prepare( "
			SELECT 
				a.id, 
				a.user_id, 
				u.display_name, 
				a.score, 
				a.percentage,
				TIMESTAMPDIFF(SECOND, a.start_time, a.end_time) as time_taken_sec
			FROM $table a
			LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
			WHERE a.test_id = %d AND a.status = 'submitted'
			ORDER BY a.score DESC, time_taken_sec ASC
			LIMIT %d
		", $test_id, $limit );

		return $wpdb->get_results( $query );
	}

	public function get_user_test_rank( $test_id, $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';

		// BUG-13 FIX: Use efficient SQL subquery instead of fetching ALL rows into PHP memory.
		// Find this user's best attempt score for the test.
		$best = $wpdb->get_row( $wpdb->prepare(
			"SELECT score, TIMESTAMPDIFF(SECOND, start_time, end_time) AS time_taken
			 FROM $table
			 WHERE test_id = %d AND user_id = %d AND status = 'submitted'
			 ORDER BY score DESC, time_taken ASC
			 LIMIT 1",
			$test_id, $user_id
		) );

		if ( ! $best ) return '--';

		// Count users who rank strictly above this user (higher score, or same score but faster)
		$rank = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) + 1
			 FROM (
			   SELECT user_id, MAX(score) AS best_score,
					  MIN(TIMESTAMPDIFF(SECOND, start_time, end_time)) AS best_time
			   FROM $table
			   WHERE test_id = %d AND status = 'submitted'
			   GROUP BY user_id
			 ) ranked
			 WHERE best_score > %f
			    OR (best_score = %f AND best_time < %d)",
			$test_id,
			(float) $best->score,
			(float) $best->score,
			(int) $best->time_taken
		) );

		return $rank ?: '--';
	}
}

