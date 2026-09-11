<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Analytics Engine: Percentile, topic heatmap, progress tracking.
 * Powers the result page analytics tab and dashboard charts.
 */
class GEP_Analytics {

    /**
     * Get full analytics for a submitted attempt.
     * Returns: per-question stats, section scores, correct/wrong/skipped counts.
     */
    public function get_attempt_analytics( $attempt_id ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, t.total_marks, t.pass_marks FROM {$wpdb->prefix}gep_attempts a
             JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id
             WHERE a.id = %d",
            $attempt_id
        ) );
        if ( ! $attempt ) return null;

        // These three columns are NULL on a fresh attempt, and PHP 8 deprecates
        // passing NULL to json_decode — every such attempt wrote three notices to
        // the log before this cast.
        $answers = json_decode( (string) $attempt->answers, true ) ?: array();
        $section_scores_raw = json_decode( (string) $attempt->section_scores, true ) ?: array();
        $analytics_raw = json_decode( (string) $attempt->analytics_data, true ) ?: array();

        // Get all questions for this test
        $test_logic = new GEP_Test();
        $q_logic = new GEP_Question();
        $q_ids = $test_logic->get_test_questions( $attempt->test_id, $attempt->id );
        $questions = $q_logic->get_questions_by_ids( $q_ids );

        // Build per-question result
        $correct = 0; $wrong = 0; $skipped = 0;
        $q_results = array();
        $topic_stats = array(); // topic_id => [correct, total]

        foreach ( $questions as $q ) {
            $qid = $q->id;
            $cat_id = absint( $q->subcategory_id ?: $q->category_id );
            if ( ! isset( $topic_stats[$cat_id] ) ) $topic_stats[$cat_id] = ['correct'=>0,'total'=>0,'name'=>''];
            $topic_stats[$cat_id]['total']++;

            $user_ans = isset( $answers[$qid]['answer'] ) ? $answers[$qid]['answer'] : null;
            $time_ms  = isset( $answers[$qid]['time_ms'] ) ? (int)$answers[$qid]['time_ms'] : 0;
            $correct_ans = $q->correct_answer;

            $status = 'skipped';
            $earned = 0;

            if ( $user_ans !== null && $user_ans !== '' ) {
                // One evaluator for the whole plugin. The local copy this replaced
                // did not know about true/false casing or the Devanagari/numeric
                // option labels the paper actually uses, so the charts disagreed
                // with the score the student was shown.
                $is_correct = GEP_Exam_Engine::evaluate_answer( $q, $user_ans );
                if ( $is_correct ) {
                    $status = 'correct'; $earned = $q->marks;
                    $correct++;
                    $topic_stats[$cat_id]['correct']++;
                } else {
                    $status = 'wrong'; $earned = -$q->negative_marks;
                    $wrong++;
                }
            } else {
                $skipped++;
            }

            $q_results[$qid] = [
                'status'  => $status,
                'earned'  => $earned,
                'time_ms' => $time_ms,
                'user_ans'=> $user_ans,
                'correct_ans' => $correct_ans,
            ];
        }

        // Topic names
        $cat_ids_list = array_filter( array_keys( $topic_stats ) );
        if ( ! empty( $cat_ids_list ) ) {
            $ids_str = implode( ',', array_map('absint', $cat_ids_list) );
            $cats = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE id IN ($ids_str)" );
            foreach ( $cats as $c ) {
                if ( isset( $topic_stats[$c->id] ) ) $topic_stats[$c->id]['name'] = $c->name;
            }
        }

        // Percentile
        $percentile = $this->get_percentile( $attempt->test_id, $attempt->score );

        return [
            'attempt'       => $attempt,
            'q_results'     => $q_results,
            'correct'       => $correct,
            'wrong'         => $wrong,
            'skipped'       => $skipped,
            'topic_stats'   => $topic_stats,
            'percentile'    => $percentile,
            'section_scores'=> $section_scores_raw,
            'analytics'     => $analytics_raw,
            'total_q'       => count( $questions ),
        ];
    }

    /**
     * Get real percentile rank for a score in a test.
     * Returns 0-100 (int). "You are better than X% of students."
     */
    public function get_percentile( $test_id, $score ) {
        global $wpdb;
        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}gep_attempts
             WHERE test_id = %d AND status = 'submitted'", $test_id
        ) );
        if ( $total <= 1 ) return 100;
        $below = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM (
                SELECT user_id, MAX(score) as best FROM {$wpdb->prefix}gep_attempts
                WHERE test_id = %d AND status = 'submitted' GROUP BY user_id
             ) ranked WHERE best < %f",
            $test_id, (float) $score
        ) );
        return (int) round( ( $below / $total ) * 100 );
    }

    /**
     * Get score progression over multiple attempts for a user.
     */
    public function get_user_progress( $user_id, $test_id = null ) {
        global $wpdb;
        $where = $test_id ? $wpdb->prepare( 'AND test_id = %d', $test_id ) : '';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT a.id, a.test_id, t.title, a.score, a.percentage, a.is_pass,
                    a.start_time, TIMESTAMPDIFF(SECOND, a.start_time, a.end_time) as duration_sec
             FROM {$wpdb->prefix}gep_attempts a
             JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id
             WHERE a.user_id = %d AND a.status = 'submitted' $where
             ORDER BY a.start_time ASC LIMIT 50",
            $user_id
        ) );
    }

    /**
     * Get topic-level accuracy heatmap for a user across their LAST 20 ATTEMPTS.
     *
     * NOTE ON SCOPE — read before using this for a result page.
     * This is a *lifetime* view: it walks many attempts across many tests, so its
     * totals are the sum of every question the student has ever seen in a topic.
     * That is the right number for a long-term progress widget and the wrong one
     * for "how did I do in this test", where the reader expects the totals to
     * match the test they just sat. For a single attempt use
     * get_weak_topics_for_attempt() instead.
     *
     * Returns: array of {cat_id, name, correct, total, accuracy_pct}
     */
    public function get_topic_heatmap( $user_id ) {
        global $wpdb;
        // Get all submitted attempts
        $attempts = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, test_id, answers FROM {$wpdb->prefix}gep_attempts
             WHERE user_id = %d AND status = 'submitted' ORDER BY id DESC LIMIT 20",
            $user_id
        ) );
        if ( empty( $attempts ) ) return array();

        $topic_map = array(); // cat_id => [correct, total, name]
        $q_logic = new GEP_Question();
        $test_logic = new GEP_Test();

        foreach ( $attempts as $att ) {
            $answers = json_decode( $att->answers, true ) ?: array();
            $q_ids = $test_logic->get_test_questions( $att->test_id, $att->id );
            $questions = $q_logic->get_questions_by_ids( $q_ids );
            foreach ( $questions as $q ) {
                $cat = absint( $q->subcategory_id ?: $q->category_id );
                if ( ! isset( $topic_map[$cat] ) ) $topic_map[$cat] = ['correct'=>0,'total'=>0,'name'=>''];
                $topic_map[$cat]['total']++;
                if ( isset($answers[$q->id]['answer']) && $answers[$q->id]['answer'] !== '' ) {
                    // Use the one canonical evaluator. A raw string compare marked
                    // every multi-select answer ("A,C" vs "C,A") and every numerical
                    // answer within tolerance as wrong, so those topics always looked
                    // weaker than the student actually was.
                    if ( GEP_Exam_Engine::evaluate_answer( $q, $answers[$q->id]['answer'] ) ) {
                        $topic_map[$cat]['correct']++;
                    }
                }
            }
        }

        // Fetch category names
        if ( ! empty( $topic_map ) ) {
            $ids_str = implode(',', array_map('absint', array_keys($topic_map)));
            $cats = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE id IN ($ids_str)" );
            foreach ( $cats as $c ) {
                if ( isset($topic_map[$c->id]) ) $topic_map[$c->id]['name'] = $c->name;
            }
        }

        // Add accuracy_pct and sort by accuracy ASC (weakest first)
        $result = array();
        foreach ( $topic_map as $cat_id => $data ) {
            $data['cat_id'] = $cat_id;
            $data['accuracy_pct'] = $data['total'] > 0 ? round( ($data['correct']/$data['total'])*100 ) : 0;
            $result[] = $data;
        }
        usort( $result, fn($a,$b) => $a['accuracy_pct'] - $b['accuracy_pct'] );
        return $result;
    }

    /**
     * Get top weak topics for a user across their recent attempts (lifetime view).
     * See the scope note on get_topic_heatmap() before using this on a result page.
     */
    public function get_weak_topics( $user_id, $limit = 5 ) {
        $heatmap = $this->get_topic_heatmap( $user_id );
        return array_slice( $heatmap, 0, $limit );
    }

    /**
     * Weak topics for ONE attempt — scoped to the questions in that test only.
     *
     * This is what a result page needs: if the test carried 16 Ved questions, the
     * row reads "0/16", the same 16 the subject breakdown below it shows. The
     * lifetime variant answers a different question and made the result page claim
     * totals (0/176) that appeared nowhere else on the page.
     *
     * @param int   $attempt_id  Attempt to analyse.
     * @param int   $limit       Max rows to return.
     * @param float $threshold   Only topics below this accuracy % count as weak.
     * @return array of {cat_id, name, correct, wrong, skipped, total, accuracy_pct}
     */
    public function get_weak_topics_for_attempt( $attempt_id, $limit = 5, $threshold = 60 ) {
        global $wpdb;

        $attempt = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id
        ) );
        if ( ! $attempt ) return array();

        $answers   = json_decode( $attempt->answers, true ) ?: array();
        $test_logic = new GEP_Test();
        $q_logic    = new GEP_Question();
        $questions  = $q_logic->get_questions_by_ids(
            $test_logic->get_test_questions( $attempt->test_id, $attempt->id )
        );
        if ( empty( $questions ) ) return array();

        $topics = array();
        foreach ( $questions as $q ) {
            $cat = absint( ! empty( $q->subcategory_id ) ? $q->subcategory_id : $q->category_id );
            if ( ! isset( $topics[$cat] ) ) {
                $topics[$cat] = array(
                    'cat_id' => $cat, 'name' => '',
                    'correct' => 0, 'wrong' => 0, 'skipped' => 0, 'total' => 0,
                );
            }
            $topics[$cat]['total']++;

            $user_ans = isset( $answers[$q->id]['answer'] ) ? (string) $answers[$q->id]['answer'] : '';
            if ( $user_ans === '' ) {
                $topics[$cat]['skipped']++;
            } elseif ( GEP_Exam_Engine::evaluate_answer( $q, $user_ans ) ) {
                $topics[$cat]['correct']++;
            } else {
                $topics[$cat]['wrong']++;
            }
        }

        $ids = array_filter( array_keys( $topics ) );
        if ( ! empty( $ids ) ) {
            $ids_str = implode( ',', array_map( 'absint', $ids ) );
            $cats = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE id IN ($ids_str)" );
            foreach ( $cats as $c ) {
                if ( isset( $topics[$c->id] ) ) $topics[$c->id]['name'] = $c->name;
            }
        }

        $weak = array();
        foreach ( $topics as $t ) {
            if ( $t['total'] < 1 || $t['name'] === '' ) continue;
            $t['accuracy_pct'] = (int) round( ( $t['correct'] / $t['total'] ) * 100 );
            if ( $t['accuracy_pct'] < $threshold ) {
                $weak[] = $t;
            }
        }
        // Weakest first; on a tie the topic carrying more marks is the bigger problem.
        usort( $weak, function ( $a, $b ) {
            if ( $a['accuracy_pct'] === $b['accuracy_pct'] ) {
                return $b['total'] - $a['total'];
            }
            return $a['accuracy_pct'] - $b['accuracy_pct'];
        } );

        return array_slice( $weak, 0, $limit );
    }

    /**
     * Compare user attempt with the top scorer for the same test.
     */
    public function get_comparison_with_topper( $attempt_id ) {
        global $wpdb;
        $attempt = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id
        ) );
        if ( ! $attempt ) return null;

        $topper = $wpdb->get_row( $wpdb->prepare(
            "SELECT a.*, u.display_name FROM {$wpdb->prefix}gep_attempts a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.test_id = %d AND a.status = 'submitted'
             ORDER BY a.score DESC, TIMESTAMPDIFF(SECOND, a.start_time, a.end_time) ASC
             LIMIT 1",
            $attempt->test_id
        ) );

        return [
            'my_score'    => $attempt->score,
            'my_pct'      => $attempt->percentage,
            'my_time'     => strtotime($attempt->end_time) - strtotime($attempt->start_time),
            'top_score'   => $topper ? $topper->score : $attempt->score,
            'top_pct'     => $topper ? $topper->percentage : $attempt->percentage,
            'top_time'    => $topper ? strtotime($topper->end_time) - strtotime($topper->start_time) : 0,
            'top_name'    => $topper ? $topper->display_name : 'You',
        ];
    }
}
