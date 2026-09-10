<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( is_object( $details ) ) {
    $details = (array) $details;
}
$attempt   = isset($details['attempt']) ? (object) $details['attempt'] : new stdClass();
$test      = isset($details['test']) ? (object) $details['test'] : new stdClass();
$questions = isset($details['questions']) ? $details['questions'] : array();

foreach ( $questions as $idx => $q ) {
    if ( is_array( $q ) ) {
        $questions[$idx] = (object) $q;
    }
}

$answers = isset($details['answers']) ? $details['answers'] : array();
if ( is_object( $answers ) ) {
    $answers = json_decode(json_encode($answers), true);
} else if ( is_array( $answers ) ) {
    foreach ( $answers as $qid => $ans ) {
        if ( is_object( $ans ) ) {
            $answers[$qid] = (array) $ans;
        }
    }
}

// Extract sections/categories dynamically from questions OR use explicitly defined sections (same as in class-gep-shortcodes.php)
$sections_map = array();
$trans = ! empty( $test->translated_data ) ? gep_safe_json_decode( $test->translated_data, true ) : array();

if ( isset( $trans['sections'] ) && is_array( $trans['sections'] ) && !empty($trans['sections']) ) {
	// Mode 1: Use explicitly defined Sections (Supports Sectional Timing)
	foreach ( $trans['sections'] as $idx => $sec ) {
		$sec_id = 'sec_' . $idx;
		$sections_map[ $sec_id ] = $sec['name'] ?: 'Section ' . ($idx + 1);
		
		// Map questions matching these IDs to this section
		$sec_q_ids = array_filter( array_map('absint', explode(',', $sec['ids'])) );
		foreach ( $questions as $q ) {
			if ( in_array( $q->id, $sec_q_ids ) ) {
				// Overwrite category_id with our virtual section ID so the frontend tabs match it
				$q->category_id = $sec_id;
			}
		}
	}
	
	// Catch any questions that weren't caught in the explicitly defined sections
	foreach ( $questions as $q ) {
		if ( strpos((string)$q->category_id, 'sec_') !== 0 ) {
			$q->category_id = 'sec_misc';
			$sections_map['sec_misc'] = 'Miscellaneous';
		}
	}
} else {
	// Mode 2: Legacy fallback - Auto-detect by category_id
	if ( ! empty( $questions ) ) {
		$cat_ids = array();
		foreach ( $questions as $q ) {
			$c_id = isset( $q->category_id ) ? absint( $q->category_id ) : 0;
			$cat_ids[] = $c_id;
		}
		$cat_ids = array_unique( $cat_ids );

		if ( ! empty( $cat_ids ) ) {
			global $wpdb;
			$db_cat_ids = array_filter( $cat_ids ); // Exclude 0
			$resolved_cats = array();
			if ( ! empty( $db_cat_ids ) ) {
				$ids_str = implode( ',', $db_cat_ids );
				$cats = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE id IN ($ids_str)" );
				foreach ( $cats as $c ) {
					$resolved_cats[ $c->id ] = $c->name;
				}
			}
			foreach ( $cat_ids as $c_id ) {
				if ( $c_id === 0 ) {
					$sections_map[0] = 'General';
				} elseif ( isset( $resolved_cats[ $c_id ] ) ) {
					$sections_map[ $c_id ] = $resolved_cats[ $c_id ];
				} else {
					$sections_map[ $c_id ] = 'Subject ' . $c_id;
				}
			}
		}
	}
	if ( empty( $sections_map ) ) {
		$sections_map[0] = 'General';
	}
}

// Fetch unique passage data for side-by-side comprehension layout in result review
$passage_ids = array();
if ( ! empty( $questions ) ) {
	foreach ( $questions as $q ) {
		if ( ! empty( $q->passage_id ) && $q->passage_id > 0 ) {
			$passage_ids[] = absint( $q->passage_id );
		}
	}
}
$passage_ids = array_unique( $passage_ids );
$passages_data = array();
if ( ! empty( $passage_ids ) ) {
	global $wpdb;
	$passage_ids_str = implode( ',', $passage_ids );
	$passages = $wpdb->get_results( "SELECT id, title, translated_data FROM {$wpdb->prefix}gep_questions WHERE id IN ($passage_ids_str)" );
	foreach ( $passages as $p ) {
		$passages_data[ $p->id ] = $p;
	}
}

// ─── ENSURE QUESTIONS ARE GROUPED BY SECTION & SHUFFLED PROPERLY (Identical to shortcodes class) ───
$is_shuffle = ( isset( $attempt->id ) && isset( $test->shuffle_questions ) && $test->shuffle_questions );
$shuffle_start = isset( $trans['shuffle_start'] ) && $trans['shuffle_start'] !== '' ? intval( $trans['shuffle_start'] ) : null;
$shuffle_end = isset( $trans['shuffle_end'] ) && $trans['shuffle_end'] !== '' ? intval( $trans['shuffle_end'] ) : null;

if ( ! empty( $questions ) ) {
	// ALWAYS group by section first so that sections are contiguous and order matches the exam window 1:1.
	$grouped = array();
	// Use the order of sections_map as the master order
	foreach ( $sections_map as $sec_id => $sec_name ) {
		$grouped[ $sec_id ] = array();
	}
	$grouped['unmapped'] = array();

	foreach ( $questions as $q ) {
		$c_id = isset( $q->category_id ) ? strval( $q->category_id ) : '0';
		if ( isset( $grouped[ $c_id ] ) ) {
			$grouped[ $c_id ][] = $q;
		} else {
			// Fallback robust string key check
			$matched = false;
			foreach ( array_keys( $grouped ) as $g_key ) {
				if ( strval( $g_key ) === strval( $c_id ) ) {
					$grouped[ $g_key ][] = $q;
					$matched = true;
					break;
				}
			}
			if ( ! $matched ) {
				$grouped['unmapped'][] = $q;
			}
		}
	}

	$final_questions = array();
	$overall_idx = 0;

	foreach ( $grouped as $sec_id => $group_qs ) {
		if ( empty( $group_qs ) ) continue;

		// Determine shuffle settings for this section
		$sec_shuffle_questions = false;
		$sec_shuffle_start = null;
		$sec_shuffle_end = null;

		$sec_idx = null;
		if ( strpos( $sec_id, 'sec_' ) === 0 ) {
			$sec_idx = intval( substr( $sec_id, 4 ) );
		}

		if ( $sec_idx !== null && isset( $trans['sections'][$sec_idx] ) ) {
			$sec_conf = $trans['sections'][$sec_idx];
			$sec_shuffle_questions = isset( $sec_conf['shuffle_questions'] ) && $sec_conf['shuffle_questions'];
			$sec_shuffle_start     = isset( $sec_conf['shuffle_start'] ) && $sec_conf['shuffle_start'] !== '' ? intval( $sec_conf['shuffle_start'] ) : null;
			$sec_shuffle_end       = isset( $sec_conf['shuffle_end'] ) && $sec_conf['shuffle_end'] !== '' ? intval( $sec_conf['shuffle_end'] ) : null;
		} else {
			// Fallback to global test settings (legacy/default)
			$sec_shuffle_questions = ( isset( $test->shuffle_questions ) && $test->shuffle_questions );
			$sec_shuffle_start     = isset( $trans['shuffle_start'] ) && $trans['shuffle_start'] !== '' ? intval( $trans['shuffle_start'] ) : null;
			$sec_shuffle_end       = isset( $trans['shuffle_end'] ) && $trans['shuffle_end'] !== '' ? intval( $trans['shuffle_end'] ) : null;
		}

		if ( $sec_shuffle_questions && isset( $attempt->id ) ) {
			// Seed random number generator with attempt ID and section index for consistency
			$seed = (int) $attempt->id * 1009 + (int)$sec_idx * 97;
			mt_srand( $seed );

			if ( $sec_shuffle_start !== null || $sec_shuffle_end !== null ) {
				// Apply slice shuffling ONLY within the bounds of this section
				$total_group = count( $group_qs );
				$start_idx_in_group = null;
				$end_idx_in_group = null;

				for ( $i = 0; $i < $total_group; $i++ ) {
					$current_global_idx = $overall_idx + $i + 1; // 1-based index
					$in_range = true;
					if ( $sec_shuffle_start !== null && $current_global_idx < $sec_shuffle_start ) {
						$in_range = false;
					}
					if ( $sec_shuffle_end !== null && $current_global_idx > $sec_shuffle_end ) {
						$in_range = false;
					}

					if ( $in_range ) {
						if ( $start_idx_in_group === null ) {
							$start_idx_in_group = $i;
						}
						$end_idx_in_group = $i;
					}
				}

				if ( $start_idx_in_group !== null && $end_idx_in_group !== null && $start_idx_in_group < $end_idx_in_group ) {
					$slice = array_slice( $group_qs, $start_idx_in_group, $end_idx_in_group - $start_idx_in_group + 1 );
					gep_seeded_shuffle( $slice );
					array_splice( $group_qs, $start_idx_in_group, $end_idx_in_group - $start_idx_in_group + 1, $slice );
				}
			} else {
				gep_seeded_shuffle( $group_qs );
			}
		}

		$overall_idx += count( $group_qs );
		$final_questions = array_merge( $final_questions, $group_qs );
	}

	// Add unmapped questions at the end if any, shuffled if needed
	if ( ! empty( $grouped['unmapped'] ) ) {
		$unmapped_qs = $grouped['unmapped'];
		// Global fallback for unmapped questions
		if ( ( isset( $test->shuffle_questions ) && $test->shuffle_questions ) && isset( $attempt->id ) ) {
			gep_seeded_shuffle( $unmapped_qs );
		}
		$final_questions = array_merge( $final_questions, $unmapped_qs );
	}

	$questions = $final_questions;
}

// Group questions by category and calculate letters and counters
$section_letters = array();
$section_questions_count = array();
$section_counters = array();
$question_num_labels = array(); // index => label

// Generate labels
foreach ( $questions as $index => $q ) {
    $question_num_labels[$index] = strval($index + 1);
}

// ─── Language Strings ────────────────────────────────────────────────────────
$ui_strings = array(
    'en' => array(
        'accuracy'    => 'Accuracy',   'correct'   => 'Correct',
        'incorrect'   => 'Incorrect',  'skipped'   => 'Skipped',
        'score'       => 'Score',      'percentile'=> 'Percentile',
        'questions'   => 'Questions',  'breakdown' => 'Subject-wise Breakdown',
        'back_home'   => 'Dashboard',  'analysis'  => 'Question Analysis',
        'explanation' => 'Explanation','pass_msg'  => 'Congratulations! You Qualified',
        'fail_msg'    => 'Better Luck Next Time',
        'retake'      => 'Retake Test','share'     => 'Share Result',
        'time_taken'  => 'Time Taken', 'unanswered'=> 'Unanswered',
        'your_ans'    => 'Your Answer','correct_ans'=> 'Correct Answer',
        'mcq'         => 'Single Choice', 'msq'    => 'Multiple Choice',
        'short'       => 'Short Answer',
    ),
    'hi' => array(
        'accuracy'    => 'सटीकता',     'correct'   => 'सही',
        'incorrect'   => 'गलत',       'skipped'   => 'छोड़े',
        'score'       => 'अंक',       'percentile'=> 'प्रतिशत',
        'questions'   => 'प्रश्न',    'breakdown' => 'विषय-वार विवरण',
        'back_home'   => 'डैशबोर्ड', 'analysis'  => 'प्रश्न विश्लेषण',
        'explanation' => 'व्याख्या', 'pass_msg'  => 'बधाई हो! आप उत्तीर्ण',
        'fail_msg'    => 'अगली बार कोशिश करें',
        'retake'      => 'पुनः प्रयास', 'share'   => 'शेयर करें',
        'time_taken'  => 'समय',       'unanswered'=> 'अनुत्तरित',
        'your_ans'    => 'आपका उत्तर', 'correct_ans'=> 'सही उत्तर',
        'mcq'         => 'एकल विकल्प', 'msq'     => 'बहु विकल्प',
        'short'       => 'लघु उत्तर',
    )
);
$_gep_lang = (isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : (get_user_meta(get_current_user_id(), 'gep_preferred_lang', true) ?: 'en'));
// Fixed-language ("Sanskrit paper") tests: always review in the default content slot,
// regardless of session/user language preference — matches the exam-time lock.
if ( gep_test_requires_fixed_language( $test ) ) {
    $_gep_lang = 'en';
}
$strings   = isset($ui_strings[$_gep_lang]) ? $ui_strings[$_gep_lang] : $ui_strings['en'];

// ─── MULTI-ANSWER HELPER ─────────────────────────────────────────────────────
/**
 * Compare user answer with correct answer supporting both single (A) and
 * multi-select (A,C) formats. Returns true only if all selections match exactly.
 */
function gep_is_answer_correct( $user_ans, $correct_ans ) {
    if ( $user_ans === '' || $correct_ans === '' ) return false;
    // Normalize: sort comma-separated letters, trim, uppercase
    $normalize = function( $val ) {
        $parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $val ) ) ) );
        sort( $parts );
        return implode( ',', $parts );
    };
    return $normalize( $user_ans ) === $normalize( $correct_ans );
}

/**
 * Check if a specific option letter is in the correct answer set.
 */
function gep_option_is_correct( $opt, $correct_ans ) {
    $parts = array_map( 'trim', explode( ',', strtoupper( $correct_ans ) ) );
    return in_array( strtoupper( $opt ), $parts, true );
}

/**
 * Check if user selected a specific option letter.
 */
function gep_user_selected( $opt, $user_ans ) {
    $parts = array_map( 'trim', explode( ',', strtoupper( $user_ans ) ) );
    return in_array( strtoupper( $opt ), $parts, true );
}

// ─── Stats Calculation (supports multi-answer) ───────────────────────────────
$total_q      = count( $questions );
$correct      = 0;
$incorrect    = 0;
$skipped      = 0;
$subject_stats = array();
$diff_stats = array('easy' => array('total'=>0, 'correct'=>0), 'medium' => array('total'=>0, 'correct'=>0), 'hard' => array('total'=>0, 'correct'=>0));

foreach ( $questions as $q ) {
    $cat_id = (isset($q->subcategory_id) && $q->subcategory_id > 0) ? intval($q->subcategory_id) : (isset($q->category_id) ? intval($q->category_id) : 0);
    if ( ! isset( $subject_stats[$cat_id] ) ) {
        $subject_stats[$cat_id] = array( 
            'name' => 'Uncategorized', 
            'total' => 0, 
            'correct' => 0, 
            'wrong' => 0, 
            'skipped' => 0,
            'max_marks' => 0.0,
            'score' => 0.0
        );
    }
    $subject_stats[$cat_id]['total']++;
    $subject_stats[$cat_id]['max_marks'] += isset($q->marks) ? floatval($q->marks) : 2.0;
    
    $diff = isset($q->difficulty) ? strtolower(trim($q->difficulty)) : 'medium';
    if ( ! in_array( $diff, array('easy', 'medium', 'hard') ) ) {
        $diff = 'medium';
    }
    if(!isset($diff_stats[$diff])) $diff_stats[$diff] = array('total'=>0, 'correct'=>0);
    $diff_stats[$diff]['total']++;

    $user_ans = isset( $answers[$q->id] ) ? $answers[$q->id]['answer'] : '';

    if ( $user_ans === '' ) {
        $skipped++;
        $subject_stats[$cat_id]['skipped']++;
    } elseif ( GEP_Exam_Engine::evaluate_answer( $q, $user_ans ) ) {
        $correct++;
        $subject_stats[$cat_id]['correct']++;
        $subject_stats[$cat_id]['score'] += isset($q->marks) ? floatval($q->marks) : 2.0;
        $diff_stats[$diff]['correct']++;
    } else {
        $incorrect++;
        $subject_stats[$cat_id]['wrong']++;
        $penalty = isset($q->negative_marks) ? floatval($q->negative_marks) : 0.0;
        $subject_stats[$cat_id]['score'] -= $penalty;
    }
}

// Fetch category names
global $wpdb;
foreach ( $subject_stats as $cid => &$stat ) {
    if ( $cid > 0 ) {
        $name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $cid ) );
        if ( $name ) $stat['name'] = $name;
    }
}
unset( $stat );

$accuracy      = ( $correct + $incorrect > 0 ) ? ( $correct / ( $correct + $incorrect ) ) * 100 : 0;
$score         = $attempt->score;
$total_marks   = $test->total_marks ?: 1;
$score_pct     = min( ( $score / $total_marks ) * 100, 100 );
$topper_score  = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(score) FROM {$wpdb->prefix}gep_attempts WHERE test_id = %d AND status = 'submitted'", $test->id ) );
if (!$topper_score) $topper_score = $score; // Fallback to current score if no other attempts
$circumference = 2 * M_PI * 52; // r=52

// Time taken — use actual DB column names: start_time / end_time
$start = strtotime( isset($attempt->start_time) ? $attempt->start_time : '' );
$end   = strtotime( isset($attempt->end_time) ? $attempt->end_time : '' );
$elapsed = ( $start && $end && $end > $start ) ? $end - $start : 0;
$elapsed_str = $elapsed > 0 ? sprintf( '%02d:%02d min', floor( $elapsed / 60 ), $elapsed % 60 ) : '—';

// Rank badge
$is_pass    = ! empty( $attempt->is_pass );
$grade_info = array();
if ( $accuracy >= 90 ) $grade_info = array( 'label' => 'A+', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)' );
elseif ( $accuracy >= 75 ) $grade_info = array( 'label' => 'A',  'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.12)' );
elseif ( $accuracy >= 60 ) $grade_info = array( 'label' => 'B+', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)' );
elseif ( $accuracy >= 50 ) $grade_info = array( 'label' => 'B',  'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)' );
else                        $grade_info = array( 'label' => 'C',  'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.12)' );
?>

<style>
/* ─── Result Page — Professional Design ──────────────────────────────────── */
/* Full-width solution surface.
   The portal shell paints a near-black background on dashboard views; the result
   page is a light document, so we repaint the content area and let the solution
   use the whole screen instead of sitting in a 1400px column with black gutters. */
.gep-dashboard-container.gep-sovereign-active .gep-dashboard-content,
.gep-dashboard-container.gep-sovereign-active .gep-main-inner {
    background: #f4f6f9 !important;
}
.gep-result-wrap {
    max-width: 100%;
    margin: 0;
    padding: 16px 16px 60px;
    font-family: 'Inter', sans-serif;
    width: 100%;
    box-sizing: border-box;
}


/* Hero Summary Card */
.gep-result-hero {
    background: #fff;
    border-radius: 32px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.06);
}
.gep-result-hero-banner {
    height: 8px;
    background: <?php echo $is_pass ? 'linear-gradient(90deg,#10b981,#059669)' : 'linear-gradient(90deg,#ef4444,#dc2626)'; ?>;
}
.gep-result-hero-body {
    padding: 40px 48px 48px;
    text-align: center;
}
.gep-result-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 16px;
    background: <?php echo $is_pass ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)'; ?>;
    color: <?php echo $is_pass ? '#059669' : '#dc2626'; ?>;
    border: 1px solid <?php echo $is_pass ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'; ?>;
}
.gep-result-title {
    font-size: 22px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.5px;
    margin: 0 0 24px;
    line-height: 1.2;
}

/* Metric Rings */
.gep-metrics-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
@media(max-width:700px) { .gep-metrics-row { grid-template-columns: repeat(2,1fr); } }

.gep-metric-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    transition: transform 0.2s;
}
.gep-metric-card:hover { transform: translateY(-3px); }
.gep-metric-val {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 6px;
}
.gep-metric-label {
    font-size: 11px;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

/* Ring Chart */
.gep-ring-wrap { position: relative; display: inline-block; width: 68px; height: 68px; margin-bottom: 8px; }
.gep-ring-wrap svg { transform: rotate(-90deg); }
.gep-ring-center {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 900; letter-spacing: -0.5px;
}

/* Score Banner */
.gep-score-banner {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1px;
    background: #e2e8f0;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 36px;
}
.gep-score-cell {
    background: #f8fafc;
    padding: 20px 16px;
    text-align: center;
}
.gep-score-cell .val { font-size: 26px; font-weight: 900; color: #0f172a; letter-spacing: -1px; display: block; }
.gep-score-cell .lbl { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; display: block; margin-top: 4px; }

/* Action Buttons */
.gep-result-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.gep-btn-result-primary {
    padding: 14px 32px;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    color: #fff; border: none; border-radius: 14px;
    font-size: 14px; font-weight: 800; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s;
}
.gep-btn-result-primary:hover { transform: translateY(-2px); filter: brightness(1.1); color: #fff; text-decoration: none; }
.gep-btn-result-secondary {
    padding: 14px 32px;
    background: #f1f5f9;
    color: #475569; border: 1px solid #e2e8f0; border-radius: 14px;
    font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s;
}
.gep-btn-result-secondary:hover { background: #e2e8f0; color: #334155; text-decoration: none; }

/* Subject Grid */
.gep-subject-section {
    background: #fff;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    padding: 32px;
    margin-bottom: 28px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.gep-section-title {
    font-size: 16px; font-weight: 900; color: #0f172a;
    letter-spacing: -0.3px; margin: 0 0 24px;
    display: flex; align-items: center; gap: 10px;
}
.gep-subject-row {
    display: flex; align-items: center; gap: 16px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.gep-subject-row:last-child { border-bottom: none; }
.gep-subject-name { flex: 1; font-size: 14px; font-weight: 700; color: #334155; }
.gep-subject-bar-wrap { flex: 2; height: 8px; background: #f1f5f9; border-radius: 100px; overflow: hidden; }
.gep-subject-bar-fill { height: 100%; border-radius: 100px; background: linear-gradient(90deg,#6366f1,#8b5cf6); transition: width 1s ease; }
.gep-subject-nums { display: flex; gap: 8px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.gep-subject-nums .c { color: #10b981; } .gep-subject-nums .w { color: #ef4444; } .gep-subject-nums .s { color: #94a3b8; }

/* Question Analysis — deliberately flat & full-bleed so the solution text gets
   the entire screen width (no card border, minimal margin/padding). */
.gep-analysis-section {
    background: #fff;
    border-radius: 0;
    border: none;
    padding: 12px 14px 20px;
    margin-bottom: 12px;
    box-shadow: none;
}
.gep-analysis-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.gep-q-count-pill {
    background: #f1f5f9; border-radius: 100px;
    padding: 6px 14px; font-size: 12px; font-weight: 700; color: #475569;
}

/* Question Review Cards.
   Scrolling a long solution paper, the eye needs an unmistakable answer to
   "where does the next question start?". A 1px hairline did not give it, so each
   question is now closed by a thick rule with real breathing room around it —
   the page reads as a stack of separated questions rather than one long column. */
.gep-qr-card {
    background: #fff;
    border-radius: 0;
    border: none;
    border-bottom: 6px solid #cbd5e1;
    margin-bottom: 22px;
    padding-bottom: 20px;
    box-shadow: none;
}
.gep-qr-card:last-child { border-bottom: none; margin-bottom: 0; }

/* The header carries the question number, so it stays pinned while the body of a
   long question scrolls past — you can always see which question you are reading. */
.gep-qr-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 0 10px;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    /* Sticks below the portal's own sticky header. If a parent's overflow rules
       stop sticky from engaging, the row simply renders in place — the layout is
       correct either way, so this is an enhancement and never a dependency. */
    position: sticky;
    top: var(--gep-portal-header-h, 56px);
    z-index: 5;
    /* Pills must wrap instead of overflowing sideways on narrow screens */
    flex-wrap: wrap;
    gap: 8px;
}
.gep-qr-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0; flex: 1 1 auto; }
/* The result pill stays on the number's line and pinned right, however many
   type/difficulty pills wrap underneath it. */
.gep-qr-status { flex: 0 0 auto; align-self: flex-start; }

/* Question number: a solid badge, not a caption — it is the landmark you scan for. */
.gep-qr-num {
    display: inline-flex; align-items: center;
    font-size: 16px; font-weight: 900;
    color: #fff; background: #4338ca;
    padding: 6px 14px; border-radius: 8px;
    letter-spacing: 0.3px;
    line-height: 1.1;
    box-shadow: 0 1px 3px rgba(67,56,202,0.28);
}
/* Badge picks up the result of the question, so status is readable at a glance. */
.gep-qr-card.correct .gep-qr-num { background: #047857; box-shadow: 0 1px 3px rgba(4,120,87,0.28); }
.gep-qr-card.wrong   .gep-qr-num { background: #b91c1c; box-shadow: 0 1px 3px rgba(185,28,28,0.28); }
.gep-qr-card.skipped .gep-qr-num { background: #475569; box-shadow: 0 1px 3px rgba(71,85,105,0.28); }

.gep-qr-type-pill {
    font-size: 10px; font-weight: 700; padding: 3px 10px;
    background: #f1f5f9; color: #475569; border-radius: 100px;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.gep-qr-status {
    font-size: 11px; font-weight: 800; padding: 5px 14px;
    border-radius: 100px; text-transform: uppercase; letter-spacing: 0.5px;
}
.gep-qr-status.correct { background: rgba(4,120,87,0.10); color: #047857; }
.gep-qr-status.wrong   { background: rgba(185,28,28,0.10);  color: #b91c1c; }
.gep-qr-status.skipped { background: rgba(148,163,184,0.1); color: #64748b; }
 
/* No side padding — the question/solution text runs edge to edge so more words
   fit on a single line. */
.gep-qr-body { padding: 4px 0 14px; }
.gep-qr-text { font-size: 15px; font-weight: 500; color: #0f172a; line-height: 1.6; margin-bottom: 12px; }
.gep-qr-text p { margin: 0 0 8px; }
.gep-qr-text p:last-child { margin-bottom: 0; }

/* Option Rows — thinner, lighter, with the letter badge vertically centred */
.gep-opt-grid { display: flex; flex-direction: column; gap: 3px; }
.gep-opt-row {
    display: flex; align-items: center; gap: 10px;
    padding: 4px 10px; border-radius: 6px;
    border: 1px solid #e8edf3; background: #fff;
    transition: all 0.15s;
}
.gep-opt-row.is-correct {
    background: rgba(16,185,129,0.06);
    border-color: #10b981;
}
.gep-opt-row.is-wrong-user {
    background: rgba(239,68,68,0.06);
    border-color: #ef4444;
}
/* Option selected by user but also correct (partial multi-select correct) */
.gep-opt-row.is-partial-correct {
    background: rgba(16,185,129,0.06);
    border-color: #10b981;
}
.gep-opt-letter {
    width: 20px; height: 20px; border-radius: 5px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700;
    background: #f1f5f9; color: #64748b;
    align-self: center; /* stays vertically centred next to multi-line options */
}
.gep-opt-row.is-correct .gep-opt-letter { background: #10b981; color: #fff; }
.gep-opt-row.is-wrong-user .gep-opt-letter { background: #ef4444; color: #fff; }
.gep-opt-text { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 400; color: #0f172a; line-height: 1.5; }
.gep-opt-text p { margin: 0; }
.gep-opt-icon { font-size: 14px; flex-shrink: 0; align-self: center; margin-top: 0; }

/* Answer Summary bar */
.gep-ans-summary {
    display: flex; gap: 12px; flex-wrap: wrap;
    margin-top: 10px; padding-top: 10px;
    border-top: 1px solid #f1f5f9;
    font-size: 13px; font-weight: 600;
}
.gep-ans-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 8px;
}
.gep-ans-chip.your { background: rgba(67,56,202,0.08); color: #4338ca; }
.gep-ans-chip.correct-ans { background: rgba(4,120,87,0.08); color: #047857; }

/* Explanation — plain white, minimal padding, full-strength (non-faded) text */
.gep-explanation-box {
    margin-top: 12px;
    background: #ffffff;
    border: none;
    border-left: 2px solid #e2e8f0;
    border-radius: 0;
    padding: 2px 0 2px 12px;
}
.gep-exp-header {
    display: flex; align-items: center; gap: 8px;
    font-size: 12px; font-weight: 800; color: #4338ca;
    text-transform: uppercase; letter-spacing: 0.8px;
    margin-bottom: 6px;
}
.gep-exp-body {
    font-size: 14px;
    color: #0f172a;       /* was a faded slate grey — now full-strength near-black */
    opacity: 1;
    line-height: 1.65;
    font-weight: 400;
}
.gep-exp-body p { margin: 0 0 8px; }
.gep-exp-body p:last-child { margin-bottom: 0; }

/* Question Source — its own box at the very end of the solution */
.gep-source-box {
    margin-top: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    background: #fbfcfe;
}
.gep-source-header {
    font-size: 11px; font-weight: 800; color: #64748b;
    text-transform: uppercase; letter-spacing: 0.8px;
    margin-bottom: 4px;
}
.gep-source-body { font-size: 13px; color: #0f172a; font-weight: 600; }

/* ─── Contrast corrections for this page ───────────────────────────────────
   The solution renders on white/near-white cards, but several semantic colours
   here come from the dark-theme palette and only reached ~2.1–3.8:1 against
   white. Each is swapped for the darker end of the same hue so the meaning
   (green = easy/correct, amber = medium, red = hard/weak) is unchanged. */
.gep-result-wrap .gep-metric-label,
.gep-result-wrap .gep-score-cell .lbl,
.gep-result-wrap .gep-score-cell small,
.gep-result-wrap .gep-subject-nums .s {
    color: #64748b !important;
}
.gep-result-wrap .gep-ring-center { color: #4338ca !important; }
.gep-result-wrap .gep-grade-badge { color: #b45309 !important; }
.gep-result-wrap .gep-result-status-pill { color: #047857 !important; }
.gep-result-wrap .gep-badge-free { background: #047857 !important; color: #fff !important; }
/* difficulty + weak-area accents */
.gep-result-wrap [style*="color: #10b981"] { color: #047857 !important; }
.gep-result-wrap [style*="color: #f59e0b"] { color: #b45309 !important; }
.gep-result-wrap [style*="color: #ef4444"] { color: #b91c1c !important; }
/* The blanket slate override here was a contrast fix; keep it for the body text of
   a weak-area row, but let the two things the row exists to say — which section,
   and how badly — carry the warning colour. #b91c1c on the #fef2f2 card is 6.4:1,
   so this stays comfortably past AA. */
.gep-result-wrap .gep-weak-topic-row div { color: #475569 !important; }
.gep-result-wrap .gep-weak-topic-row .gep-weak-topic-name { color: #991b1b !important; }
.gep-result-wrap .gep-weak-topic-row .gep-weak-topic-pct  { color: #b91c1c !important; }
.gep-result-wrap h4[style*="#ef4444"] { color: #b91c1c !important; }
.gep-result-wrap .gep-score-cell .val[style*="#f59e0b"] { color: #b45309 !important; }
.gep-result-wrap .gep-subject-nums .c { color: #047857; }
.gep-result-wrap .gep-subject-nums .w { color: #b91c1c; }

/* Grade Badge */
.gep-grade-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 44px; height: 44px; border-radius: 12px;
    font-size: 18px; font-weight: 900;
    background: <?php echo $grade_info['bg']; ?>;
    color: <?php echo $grade_info['color']; ?>;
    border: 2px solid <?php echo $grade_info['color']; ?>33;
    margin-bottom: 12px;
}

/* Split layout for Comprehension Passage questions on result review page */
@media (max-width: 768px) {
    .gep-qr-split-wrapper {
        flex-direction: column !important;
        gap: 16px !important;
    }
    .gep-qr-passage-column {
        border-right: none !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding-right: 0 !important;
        padding-bottom: 20px !important;
    }
    
    /* Responsive overrides for Results Review dashboard */
    .gep-result-hero-body {
        padding: 24px 16px 24px !important;
    }
    .gep-score-banner {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1px !important;
    }
    .gep-subject-section {
        padding: 20px 16px !important;
        border-radius: 16px !important;
    }
    .gep-analysis-section {
        padding: 10px 12px 16px !important;
        border-radius: 0 !important;
    }
    .gep-result-actions {
        flex-direction: column !important;
        width: 100% !important;
        gap: 10px !important;
    }
    .gep-btn-result-primary,
    .gep-btn-result-secondary {
        width: 100% !important;
        justify-content: center !important;
        box-sizing: border-box !important;
    }
}

/* ─── PHONE (≤480px) — Full-Width Result Page ───────────────────────────── */
@media (max-width: 480px) {
    /* Fix black sides: remove horizontal padding, use full viewport width */
    .gep-result-wrap {
        padding: 12px 10px 80px !important;
        max-width: 100vw !important;
        width: 100% !important;
        overflow-x: hidden !important;
        box-sizing: border-box !important;
    }
    /* Hero card: fill screen edge-to-edge */
    .gep-result-hero {
        border-radius: 16px !important;
        margin-bottom: 16px !important;
    }
    .gep-result-hero-body {
        padding: 16px 14px !important;
    }
    .gep-result-title {
        font-size: 18px !important;
        margin-bottom: 16px !important;
    }
    /* Metrics grid: 2 columns */
    .gep-metrics-row {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 16px !important;
    }
    .gep-metric-card {
        padding: 12px 8px !important;
    }
    .gep-metric-val { font-size: 22px !important; }
    .gep-ring-wrap { width: 56px !important; height: 56px !important; }
    /* Score banner: 2 columns */
    .gep-score-banner {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    /* Difficulty cards: wrap properly */
    .gep-difficulty-row {
        flex-wrap: wrap !important;
        gap: 8px !important;
    }
    .gep-difficulty-card {
        flex: 1 1 calc(50% - 8px) !important;
        min-width: 0 !important;
    }
    /* Subject breakdown table: horizontal scroll */
    .gep-subject-table-wrap {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }
    .gep-subject-table {
        min-width: 340px !important;
    }
    /* Section cards */
    .gep-subject-section {
        padding: 14px 12px !important;
        border-radius: 14px !important;
        margin-bottom: 12px !important;
    }
    .gep-analysis-section {
        padding: 8px 10px 14px !important;
        border-radius: 0 !important;
        margin-bottom: 8px !important;
    }
    /* Question review cards stay flat/full-bleed on phones — but they keep the
       thick closing rule and the gap around it. On a phone the whole solution is
       one long scroll, so the boundary between two questions matters more here
       than anywhere else, not less. */
    .gep-qr-card {
        padding: 0 0 16px !important;
        border-radius: 0 !important;
        margin-bottom: 18px !important;
        border-bottom-width: 6px !important;
    }
    .gep-qr-card:last-child {
        margin-bottom: 0 !important;
        border-bottom: none !important;
    }
    /* FIX: this rule previously targeted `.gep-qr-card-header`, which does not
       exist in the markup — so the meta pill row never wrapped and overflowed
       sideways on narrow screens. The real class is `.gep-qr-header`. */
    .gep-qr-header {
        flex-wrap: nowrap !important;
        gap: 6px !important;
        align-items: flex-start !important;
    }
    .gep-qr-meta {
        flex-wrap: wrap !important;
        gap: 6px !important;
        min-width: 0 !important;
    }
    .gep-qr-status { white-space: nowrap !important; padding: 5px 10px !important; }
    /* Keep the number badge prominent on the smallest screens — it is the
       landmark the reader scans for while scrolling. */
    .gep-qr-num {
        font-size: 15px !important;
        padding: 5px 12px !important;
    }
    .gep-qr-type-pill { font-size: 9px !important; padding: 3px 8px !important; }
    /* Action buttons: stacked full-width */
    .gep-result-actions {
        flex-direction: column !important;
        gap: 10px !important;
        padding: 0 !important;
    }
    .gep-btn-result-primary,
    .gep-btn-result-secondary {
        width: 100% !important;
        justify-content: center !important;
        font-size: 14px !important;
        padding: 14px !important;
    }
    /* Section tab bar: horizontal scroll */
    .gep-result-sections-bar {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        flex-wrap: nowrap !important;
    }
    .gep-result-section-tab {
        flex-shrink: 0 !important;
        white-space: nowrap !important;
        font-size: 12px !important;
        padding: 6px 12px !important;
    }
}
</style>

<div class="gep-result-wrap">

    <!-- ─── HERO SUMMARY ───────────────────────────────────────────────── -->
    <div class="gep-result-hero">
        <div class="gep-result-hero-banner"></div>
        <div class="gep-result-hero-body">

            <div class="gep-grade-badge"><?php echo esc_html( $grade_info['label'] ); ?></div>

            <div class="gep-result-status-pill">
                <?php echo $is_pass ? '🏆 ' . esc_html( $strings['pass_msg'] ) : '🎯 ' . esc_html( $strings['fail_msg'] ); ?>
            </div>

            <h1 class="gep-result-title"><?php echo esc_html( $test->title ); ?></h1>

            <!-- 4 Metric Cards -->
            <div class="gep-metrics-row">
                <!-- Accuracy Ring -->
                <div class="gep-metric-card">
                    <?php $acc_offset = $circumference * ( 1 - $accuracy / 100 ); ?>
                    <div class="gep-ring-wrap">
                        <svg width="68" height="68" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#6366f1" stroke-width="10"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $acc_offset; ?>"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="gep-ring-center" style="color:#6366f1;font-size:13px;"><?php echo round( $accuracy ); ?>%</div>
                    </div>
                    <div class="gep-metric-label"><?php echo esc_html( $strings['accuracy'] ); ?></div>
                </div>

                <!-- Correct -->
                <div class="gep-metric-card">
                    <?php $c_offset = $circumference * ( 1 - ( $total_q ? $correct / $total_q : 0 ) ); ?>
                    <div class="gep-ring-wrap">
                        <svg width="68" height="68" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#10b981" stroke-width="10"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $c_offset; ?>"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="gep-ring-center" style="color:#10b981;"><?php echo $correct; ?></div>
                    </div>
                    <div class="gep-metric-label"><?php echo esc_html( $strings['correct'] ); ?></div>
                </div>

                <!-- Incorrect -->
                <div class="gep-metric-card">
                    <?php $w_offset = $circumference * ( 1 - ( $total_q ? $incorrect / $total_q : 0 ) ); ?>
                    <div class="gep-ring-wrap">
                        <svg width="68" height="68" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#ef4444" stroke-width="10"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $w_offset; ?>"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="gep-ring-center" style="color:#ef4444;"><?php echo $incorrect; ?></div>
                    </div>
                    <div class="gep-metric-label"><?php echo esc_html( $strings['incorrect'] ); ?></div>
                </div>

                <!-- Skipped -->
                <div class="gep-metric-card">
                    <?php $s_offset = $circumference * ( 1 - ( $total_q ? $skipped / $total_q : 0 ) ); ?>
                    <div class="gep-ring-wrap">
                        <svg width="68" height="68" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                            <circle cx="60" cy="60" r="52" fill="none" stroke="#94a3b8" stroke-width="10"
                                stroke-dasharray="<?php echo $circumference; ?>"
                                stroke-dashoffset="<?php echo $s_offset; ?>"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="gep-ring-center" style="color:#94a3b8;font-size:13px;"><?php echo $skipped; ?></div>
                    </div>
                    <div class="gep-metric-label"><?php echo esc_html( $strings['skipped'] ); ?></div>
                </div>
            </div>

            <!-- Difficulty Breakdown -->
            <div style="margin-bottom: 24px; padding: 16px; background: rgba(248, 250, 252, 0.5); border-radius: 16px; border: 1px solid #e2e8f0;">
                <h3 style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 12px 0; letter-spacing: 0.8px;">Difficulty Breakdown</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <?php 
                    $diff_labels = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'];
                    $diff_colors = ['easy' => '#047857', 'medium' => '#b45309', 'hard' => '#b91c1c'];
                    foreach($diff_labels as $d_key => $d_label): 
                        $d_stat = $diff_stats[$d_key];
                        $d_pct = $d_stat['total'] > 0 ? round(($d_stat['correct'] / $d_stat['total']) * 100) : 0;
                    ?>
                    <div style="background: #fff; padding: 10px 14px; border-radius: 10px; border: 1px solid <?php echo $diff_colors[$d_key]; ?>40; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="font-weight: 800; color: <?php echo $diff_colors[$d_key]; ?>;"><?php echo $d_label; ?></span>
                            <span style="font-weight: 800; color: #1e293b;"><?php echo $d_stat['correct']; ?> / <?php echo $d_stat['total']; ?></span>
                        </div>
                        <div style="height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?php echo $d_pct; ?>%; background: <?php echo $diff_colors[$d_key]; ?>; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Score Banner -->
            <div class="gep-score-banner">
                <div class="gep-score-cell">
                    <span class="val" style="color:#6366f1;"><?php echo number_format( $score, 1 ); ?><small style="font-size:16px;font-weight:600;color:#94a3b8;"> /<?php echo $total_marks; ?></small></span>
                    <span class="lbl"><?php echo esc_html( $strings['score'] ); ?></span>
                </div>
                <div class="gep-score-cell">
                    <span class="val" style="color:#f59e0b;"><?php echo number_format( $topper_score, 1 ); ?><small style="font-size:16px;font-weight:600;color:#94a3b8;"> /<?php echo $total_marks; ?></small></span>
                    <span class="lbl">Highest Score</span>
                </div>
                <div class="gep-score-cell">
                    <span class="val"><?php echo round( isset($attempt->percentage) ? $attempt->percentage : 0, 1 ); ?>%</span>
                    <span class="lbl"><?php echo esc_html( $strings['percentile'] ); ?></span>
                </div>
                <div class="gep-score-cell">
                    <span class="val" style="font-size:20px;color:#64748b;"><?php echo $elapsed_str; ?></span>
                    <span class="lbl"><?php echo esc_html( $strings['time_taken'] ); ?></span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="gep-result-actions">
                <a href="<?php echo esc_url( gep_get_url( 'dashboard' ) ); ?>" class="gep-btn-result-primary">
                    🏠 <?php echo esc_html( $strings['back_home'] ); ?>
                </a>
                <a href="<?php echo esc_url( add_query_arg( array( 'id' => $test->id, 'type' => 'test' ), (string) gep_get_url( 'checkout' ) ) ); ?>" class="gep-btn-result-secondary">
                    🔄 <?php echo esc_html( $strings['retake'] ); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- ─── ANALYTICS TAB ───────────────────────────────────────────────── -->
    <?php
        // Build analytics data for Chart.js
        $analytics = new GEP_Analytics();
        $attempt_analytics = $analytics->get_attempt_analytics( $attempt->id );
        $percentile = $analytics->get_percentile( $attempt->test_id, $attempt->score );
        // Weak areas must describe THIS test. The lifetime helper summed every
        // attempt the student had ever made, so a 16-question Ved section was
        // reported as "0/176 correct" — a total that matched nothing else on the
        // page. Scope it to the attempt, and derive the rows from the same
        // $subject_stats the breakdown below renders so the two can never drift.
        $weak_topics = array();
        foreach ( $subject_stats as $wt_cat_id => $wt_stat ) {
            if ( empty( $wt_stat['total'] ) ) continue;
            $wt_pct = (int) round( ( $wt_stat['correct'] / $wt_stat['total'] ) * 100 );
            if ( $wt_pct >= 60 ) continue; // 60%+ is not a weak area
            $weak_topics[] = array(
                'cat_id'       => $wt_cat_id,
                'name'         => $wt_stat['name'],
                'correct'      => $wt_stat['correct'],
                'wrong'        => $wt_stat['wrong'],
                'skipped'      => $wt_stat['skipped'],
                'total'        => $wt_stat['total'],
                'accuracy_pct' => $wt_pct,
            );
        }
        // Weakest first; on a tie the section carrying more questions matters more.
        usort( $weak_topics, function ( $a, $b ) {
            if ( $a['accuracy_pct'] === $b['accuracy_pct'] ) {
                return $b['total'] - $a['total'];
            }
            return $a['accuracy_pct'] - $b['accuracy_pct'];
        } );
        $weak_topics = array_slice( $weak_topics, 0, 5 );
        $section_scores_data = json_decode( isset($attempt->section_scores) ? $attempt->section_scores : '[]', true ) ?: array();

        // Build topic stats for chart
        $topic_chart_data = array();
        if ( $attempt_analytics && !empty($attempt_analytics['topic_stats']) ) {
            $topic_chart_data = array_values( $attempt_analytics['topic_stats'] );
        } elseif ( !empty($subject_stats) ) {
            foreach ($subject_stats as $sid => $stat) {
                $topic_chart_data[] = array(
                    'name'    => $stat['name'],
                    'correct' => $stat['correct'],
                    'total'   => $stat['total'],
                );
            }
        }

        // Section scores for bar chart (directly from category statistics)
        $section_scores_for_chart = array();
        foreach ($subject_stats as $stat) {
            $section_scores_for_chart[] = array(
                'name'        => $stat['name'],
                'score'       => max(0.0, floatval($stat['score'])),
                'total_marks' => floatval($stat['max_marks']),
            );
        }

        // JS data payload
        $gep_analytics_js = array(
            'attempt_id'     => $attempt->id,
            'score'          => $attempt->score,
            'percentage'     => round( isset($attempt->percentage) ? $attempt->percentage : 0, 2 ),
            'correct'        => $correct,
            'wrong'          => $incorrect,
            'skipped'        => $skipped,
            'section_scores' => $section_scores_for_chart,
            'topic_stats'    => $topic_chart_data,
            'percentile'     => $percentile,
        );
    ?>
    <script>
    const GEP_Analytics_Data = <?php echo wp_json_encode( $gep_analytics_js ); ?>;
    </script>

    <?php if (!empty($section_scores_for_chart) || !empty($topic_chart_data)) : ?>
    <div class="gep-analytics-section">
        <div class="gep-section-title">📈 Performance Analytics</div>

        <!-- Percentile Banner -->
        <div class="gep-percentile-banner">
            <div>
                <div class="gep-percentile-num"><?php echo $percentile; ?>%</div>
                <div class="gep-percentile-desc">You scored better than</div>
            </div>
            <div style="text-align:left;">
                <div style="font-size:18px;font-weight:800;color:#f1f5f9;"><?php echo $percentile; ?>%</div>
                <div class="gep-percentile-desc">of all aspirants who took this test</div>
                <?php
                    $rank_label = $percentile >= 90 ? '🏆 Top 10%' : ($percentile >= 75 ? '⭐ Top 25%' : ($percentile >= 50 ? '👍 Above Average' : '📚 Keep Practicing'));
                ?>
                <div style="margin-top:8px;font-size:13px;font-weight:800;color:#6366f1;"><?php echo $rank_label; ?></div>
            </div>
        </div>

        <div class="gep-analytics-charts-grid">
            <!-- CWS Pie -->
            <div class="gep-chart-card">
                <h4>✅ Correct / Wrong / Skipped</h4>
                <div class="gep-chart-canvas-wrap">
                    <canvas id="gep-cws-pie"></canvas>
                </div>
            </div>

            <!-- Subject Score Bar -->
            <?php if (!empty($section_scores_for_chart)) : ?>
            <div class="gep-chart-card">
                <h4>📚 Subject-wise Score</h4>
                <div class="gep-chart-canvas-wrap">
                    <canvas id="gep-subject-bar"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Topic Heatmap -->
            <?php if (!empty($topic_chart_data)) : ?>
            <div class="gep-chart-card" style="grid-column: 1 / -1;">
                <h4>🗺️ Topic Accuracy Heatmap</h4>
                <div class="gep-chart-canvas-wrap" style="height:<?php echo min(200, count($topic_chart_data) * 30 + 60); ?>px;">
                    <canvas id="gep-topic-heatmap"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($weak_topics)) : ?>
        <div style="margin-top:24px;">
            <h4 style="font-size:12px;font-weight:800;color:#b91c1c;text-transform:uppercase;letter-spacing:0.8px;margin:0 0 4px;">⚠️ Weak Areas — Focus Here</h4>
            <p style="font-size:11px;color:#64748b;font-weight:600;margin:0 0 12px;">
                Sections you scored under 60% in, out of the questions this test asked.
            </p>
            <div class="gep-weak-topics-list">
                <?php foreach ($weak_topics as $wt) :
                    if (empty($wt['name'])) continue; ?>
                <div class="gep-weak-topic-row">
                    <div class="gep-weak-topic-name">📖 <?php echo esc_html($wt['name']); ?></div>
                    <div class="gep-weak-topic-count">
                        <strong><?php echo (int) $wt['correct']; ?>/<?php echo (int) $wt['total']; ?></strong> correct
                        <?php if ( ! empty( $wt['skipped'] ) ) : ?>
                            <span class="gep-weak-topic-sub">· <?php echo (int) $wt['skipped']; ?> skipped</span>
                        <?php endif; ?>
                    </div>
                    <div class="gep-weak-topic-pct"><?php echo (int) $wt['accuracy_pct']; ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ─── SUBJECT BREAKDOWN ──────────────────────────────────────────── -->
    <?php if ( count( $subject_stats ) > 0 ) : ?>

    <div class="gep-subject-section">
        <div class="gep-section-title">📊 <?php echo esc_html( $strings['breakdown'] ); ?></div>
        <?php foreach ( $subject_stats as $stat ) :
            $pct = $stat['total'] > 0 ? ( $stat['correct'] / $stat['total'] ) * 100 : 0;
        ?>
        <div class="gep-subject-row">
            <div class="gep-subject-name"><?php echo esc_html( $stat['name'] ); ?></div>
            <div class="gep-subject-bar-wrap">
                <div class="gep-subject-bar-fill" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <div class="gep-subject-nums">
                <span class="c">✓<?php echo $stat['correct']; ?></span>
                <span class="w">✗<?php echo $stat['wrong']; ?></span>
                <span class="s">—<?php echo $stat['skipped']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ─── QUESTION ANALYSIS ──────────────────────────────────────────── -->
    <div class="gep-analysis-section">
        <div class="gep-analysis-header">
            <div class="gep-section-title" style="margin:0;">🔍 <?php echo esc_html( $strings['analysis'] ); ?></div>
            <div class="gep-q-count-pill"><?php echo $total_q; ?> <?php echo esc_html( $strings['questions'] ); ?></div>
        </div>

        <?php
        $current_lang = $_gep_lang; // honors the fixed-language lock computed above
        $review_current_cat = null;
        foreach ( $questions as $index => $q ) :
            $q_cat = strval($q->category_id);
            if ($q_cat !== $review_current_cat) {
                $review_current_cat = $q_cat;
                $cat_name = isset($sections_map[$q_cat]) ? $sections_map[$q_cat] : 'Section';
                echo '<div class="gep-analysis-section-title" style="margin-top: 35px; margin-bottom: 20px; font-size: 18px; font-weight: 850; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">';
                echo '📁 ' . esc_html($cat_name);
                echo '</div>';
            }

            // Smart translation mapping
            $trans = ! empty( $q->translated_data ) ? gep_safe_json_decode( $q->translated_data, true ) : array();
            $title_hi = isset($trans['title']) ? $trans['title'] : '';
            
            $main_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $q->title);
            $trans_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $title_hi);
            $is_reversed = ($main_has_devanagari && !empty($title_hi) && !$trans_has_devanagari);

            if ( $is_reversed ) {
                // If reversed, English (en) means we swap to the translation data
                if ( $current_lang === 'en' ) {
                    foreach ( array( 'title','option_a','option_b','option_c','option_d','option_e','explanation' ) as $field ) {
                        if ( ! empty( $trans[$field] ) ) $q->$field = $trans[$field];
                    }
                }
            } else {
                // Normal case: Hindi (hi) means we swap to translation data
                if ( $current_lang === 'hi' && ( ! empty( $q->translation_enabled ) || ! empty( $title_hi ) ) ) {
                    if ( $trans ) {
                        foreach ( array( 'title','option_a','option_b','option_c','option_d','option_e','explanation' ) as $field ) {
                            if ( ! empty( $trans[$field] ) ) $q->$field = $trans[$field];
                        }
                    }
                }
            }

            // Fallback empty fields to the other language version to avoid empty displays
            foreach ( array( 'title','option_a','option_b','option_c','option_d','option_e','explanation' ) as $field ) {
                if ( empty( $q->$field ) && ! empty( $trans[$field] ) ) {
                    $q->$field = $trans[$field];
                }
            }

            $user_ans   = isset( $answers[$q->id] ) ? (string) $answers[$q->id]['answer'] : '';
            $qtype      = isset($q->question_type) ? $q->question_type : 'mcq';

            // Evaluate correctness based on question type
            if ( $user_ans === '' ) {
                $is_correct = false;
            } else {
                $is_correct = GEP_Exam_Engine::evaluate_answer( $q, $user_ans );
            }
            $is_skipped = ( $user_ans === '' );
            $status_key = $is_skipped ? 'skipped' : ( $is_correct ? 'correct' : 'wrong' );

            // Question type label (expanded)
            $type_map   = array(
                'mcq'              => 'Single Choice',
                'multi_select'     => 'Multi-Select',
                'msq'              => 'Multi-Select',
                'numerical'        => 'Numerical',
                'true_false'       => 'True/False',
                'assertion_reason' => 'Assertion-Reason',
                'matching'         => 'Match-Type',
                'short_answer'     => 'Short Answer',
            );
            $type_label = isset($type_map[$qtype]) ? $type_map[$qtype] : strtoupper($qtype);

            // Correct answer parts (for multi-select display)
            $correct_parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $q->correct_answer ) ) ) );
            $user_parts    = array_filter( array_map( 'trim', explode( ',', strtoupper( $user_ans ) ) ) );

            // Status labels
            $status_labels = array(
                'correct' => '✓ ' . $strings['correct'],
                'wrong'   => '✗ ' . $strings['incorrect'],
                'skipped' => '— ' . $strings['skipped'],
            );
        ?>
        <div class="gep-qr-card <?php echo $status_key; ?>">
            <div class="gep-qr-header">
                <div class="gep-qr-meta">
                    <span class="gep-qr-num">Q<?php echo $index + 1; ?></span>
                    <span class="gep-qr-type-pill"><?php echo esc_html( $type_label ); ?></span>
                    <?php 
                    $q_diff = isset($q->difficulty) ? $q->difficulty : 'medium';
                    $q_diff_colors = ['easy' => '#047857', 'medium' => '#b45309', 'hard' => '#b91c1c'];
                    $q_diff_color = isset($q_diff_colors[$q_diff]) ? $q_diff_colors[$q_diff] : '#f59e0b';
                    ?>
                    <span class="gep-qr-type-pill" style="background:<?php echo $q_diff_color; ?>15; color:<?php echo $q_diff_color; ?>; border: 1px solid <?php echo $q_diff_color; ?>30;"><?php echo esc_html(ucfirst($q_diff)); ?></span>
                    <?php if ( count( $correct_parts ) > 1 ) : ?>
                        <span class="gep-qr-type-pill" style="background:rgba(79,70,229,0.08);color:#4f46e5;">Multi-Select</span>
                    <?php endif; ?>
                    <!-- Question Source is shown once, below the Explanation (see gep-source-box below) — not duplicated here. -->
                </div>
                <span class="gep-qr-status <?php echo $status_key; ?>"><?php echo esc_html( $status_labels[$status_key] ); ?></span>
            </div>

            <div class="gep-qr-body">
                <?php 
                $has_passage = ( ! empty($q->passage_id) && isset($passages_data[$q->passage_id]) );
                if ( $has_passage ) :
                    $p_data = $passages_data[$q->passage_id];
                    $p_trans = ! empty( $p_data->translated_data ) ? gep_safe_json_decode( $p_data->translated_data, true ) : array();
                    $p_title_hi = isset($p_trans['title']) ? $p_trans['title'] : '';
                    
                    // Determine language to display
                    $p_display_en = gep_format_rich_content( $p_data->title );
                    $p_display_hi = ! empty( $p_title_hi ) ? gep_format_rich_content( $p_title_hi ) : $p_display_en;
                    
                    $p_content = ($current_lang === 'hi' && ! empty( $q->translation_enabled )) ? $p_display_hi : $p_display_en;
                ?>
                <div class="gep-qr-split-wrapper" style="display: flex; gap: 24px; min-width: 0;">
                    <div class="gep-qr-passage-column" style="flex: 1; border-right: 2px solid #e2e8f0; padding-right: 20px; max-height: 450px; overflow-y: auto; font-size: 15px; line-height: 1.6; color: #334155;">
                        <div style="font-weight: 800; color: #1e293b; margin-bottom: 12px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 6px;">
                            📖 Comprehension Passage
                        </div>
                        <?php echo $p_content; ?>
                    </div>
                    <div class="gep-qr-question-column" style="flex: 1; min-width: 0;">
                <?php endif; ?>

                <div class="gep-qr-text"><?php echo gep_format_rich_content( $q->title ); ?></div>

                <?php if ( $qtype === 'short_answer' || $qtype === 'numerical' || $qtype === 'true_false' ) : ?>
                    <!-- Numerical / Short Answer / True-False Display -->
                    <div class="gep-ans-summary">
                        <span class="gep-ans-chip your">📝 Your Answer: <strong><?php echo esc_html( $user_ans ?: '—' ); ?></strong></span>
                        <span class="gep-ans-chip correct-ans">✅ Correct Answer: <strong>
                            <?php 
                            echo esc_html( $q->correct_answer );
                            if ( $qtype === 'numerical' ) {
                                $tol = isset($q->numerical_tolerance) && $q->numerical_tolerance > 0 ? $q->numerical_tolerance : 0.01;
                                echo ' <em style="color:#64748b;font-size:11px;">(±' . esc_html($tol) . ')</em>';
                            }
                            ?>
                        </strong></span>
                    </div>
                <?php else :
                    $opts = array('A', 'B', 'C', 'D');
                    if ( ! empty( $q->option_e ) ) {
                        $opts[] = 'E';
                    }
                        
                        // ── SECTION OVERRIDE FOR OPTION SHUFFLING ──────────────────
                        $rr_shuffle_options = false;
                        $rr_sec_id = isset($q->category_id) ? $q->category_id : '';
                        $rr_sec_idx = null;
                        if ( strpos( $rr_sec_id, 'sec_' ) === 0 ) {
                            $rr_sec_idx = intval( substr( $rr_sec_id, 4 ) );
                        }
                        $rr_test_td = !empty($test->translated_data) ? gep_safe_json_decode($test->translated_data, true) : array();
                        if ( $rr_sec_idx !== null && isset( $rr_test_td['sections'][$rr_sec_idx] ) ) {
                            $rr_sec_conf = $rr_test_td['sections'][$rr_sec_idx];
                            $rr_shuffle_options = isset( $rr_sec_conf['shuffle_options'] ) && $rr_sec_conf['shuffle_options'];
                        } else {
                            $rr_shuffle_options = ( isset($test->shuffle_options) && $test->shuffle_options );
                        }

                        if ( $rr_shuffle_options ) {
                            $seed = isset($attempt) && isset($attempt->id) ? (int)$q->id * 997 + (int)$attempt->id : (int)$q->id * 997;
                            mt_srand($seed);
                            gep_seeded_shuffle($opts); 
                        }

                        $display_labels = array('A', 'B', 'C', 'D', 'E'); 
                        $letter_to_num = array();
                        foreach ( $opts as $display_idx => $orig_letter ) {
                            $letter_to_num[$orig_letter] = $display_labels[$display_idx];
                        }

                        // Map user answers and correct answers to display numbers
                        $user_ans_display = '';
                        if ( $user_ans !== '' ) {
                            $user_parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $user_ans ) ) ) );
                            $user_nums = array();
                            foreach ( $user_parts as $part ) {
                                if ( isset( $letter_to_num[$part] ) ) {
                                    $user_nums[] = $letter_to_num[$part];
                                } else {
                                    $user_nums[] = $part;
                                }
                            }
                            sort( $user_nums );
                            $user_ans_display = implode( ', ', $user_nums );
                        } else {
                            $user_ans_display = '—';
                        }

                        $correct_ans_display = '';
                        if ( ! empty( $q->correct_answer ) ) {
                            $correct_parts = array_filter( array_map( 'trim', explode( ',', strtoupper( $q->correct_answer ) ) ) );
                            $correct_nums = array();
                            foreach ( $correct_parts as $part ) {
                                if ( isset( $letter_to_num[$part] ) ) {
                                    $correct_nums[] = $letter_to_num[$part];
                                } else {
                                    $correct_nums[] = $part;
                                }
                            }
                            sort( $correct_nums );
                            $correct_ans_display = implode( ', ', $correct_nums );
                        } else {
                            $correct_ans_display = '—';
                        }
                    ?>
                    <!-- MCQ / MSQ Option Display -->
                    <div class="gep-opt-grid">
                    <?php foreach ( $opts as $display_idx => $opt ) :
                        $opt_key   = 'option_' . strtolower( $opt );
                        $opt_text  = isset($q->$opt_key) ? $q->$opt_key : '';
                        if ( $opt_text === '' ) continue;

                        $is_opt_correct      = gep_option_is_correct( $opt, $q->correct_answer );
                        $user_selected_this  = gep_user_selected( $opt, $user_ans );

                        // Determine row class and icon
                        $row_class = '';
                        $icon      = '';

                        if ( $is_opt_correct && $user_selected_this ) {
                            // User selected the correct option
                            $row_class = 'is-correct';
                            $icon      = '<span class="gep-opt-icon">✅</span>';
                        } elseif ( $is_opt_correct && ! $user_selected_this ) {
                            // Correct option user MISSED (not selected)
                            $row_class = 'is-correct';
                            $icon      = '<span class="gep-opt-icon" title="Correct — not selected">☑️</span>';
                        } elseif ( ! $is_opt_correct && $user_selected_this ) {
                            // User selected WRONG option
                            $row_class = 'is-wrong-user';
                            $icon      = '<span class="gep-opt-icon">❌</span>';
                        }

                        $display_label = $display_labels[$display_idx];
                    ?>
                        <div class="gep-opt-row <?php echo $row_class; ?>">
                            <div class="gep-opt-letter"><?php echo esc_html( $display_label ); ?></div>
                            <div class="gep-opt-text"><?php echo gep_format_rich_content( $opt_text ); ?></div>
                            <?php echo $icon; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <!-- Answer chips (always show even for skipped) -->
                    <div class="gep-ans-summary">
                        <span class="gep-ans-chip your">
                            📝 <?php echo esc_html( $strings['your_ans'] ); ?>:
                            <strong><?php echo esc_html( $user_ans_display ); ?></strong>
                        </span>
                        <span class="gep-ans-chip correct-ans">
                            ✅ <?php echo esc_html( $strings['correct_ans'] ); ?>:
                            <strong><?php echo esc_html( $correct_ans_display ); ?></strong>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Explanation / Solution first ... -->
                <?php if ( ! empty( $q->explanation ) ) : ?>
                <div class="gep-explanation-box">
                    <div class="gep-exp-header">
                        <span>💡</span> <?php echo esc_html( $strings['explanation'] ); ?>
                    </div>
                    <div class="gep-exp-body"><?php echo gep_format_rich_content( $q->explanation ); ?></div>
                </div>
                <?php endif; ?>

                <!-- ... then the Sources box, as the last block of the solution -->
                <?php if ( ! empty( $q->source ) ) : ?>
                <div class="gep-source-box">
                    <div class="gep-source-header">Sources</div>
                    <div class="gep-source-body"><?php echo esc_html( $q->source ); ?></div>
                </div>
                <?php endif; ?>

                <?php if ( $has_passage ) : ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom CTA -->
    <div style="text-align:center;padding:20px 0 40px;">
        <a href="<?php echo esc_url( gep_get_url( 'dashboard' ) ); ?>" class="gep-btn-result-primary">
            🏠 <?php echo esc_html( $strings['back_home'] ); ?>
        </a>
    </div>

</div>

