<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard logic for student area.
 */
class GEP_Dashboard {

	public function get_dashboard_stats( $user_id ) {
		// Memoised per request: portal-layout.php renders the sidebar stats and
		// render_dashboard() renders the tiles, which ran this whole set twice.
		static $cache = array();
		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}
		global $wpdb;
		$attempts_table = $wpdb->prefix . 'gep_attempts';
		$orders_table = $wpdb->prefix . 'gep_orders';
		$course_access = $wpdb->prefix . 'gep_user_course_access';

		$total_attempts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $attempts_table WHERE user_id = %d", $user_id ) );
		$passed_exams   = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $attempts_table WHERE user_id = %d AND is_pass = 1", $user_id ) );
		$total_spent    = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $orders_table WHERE user_id = %d AND status = 'success'", $user_id ) );
		$active_courses = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $course_access WHERE user_id = %d", $user_id ) );

		// Streak & Rank
		$streak = $this->get_user_streak( $user_id );
		$rank   = $this->get_user_rank( $user_id );
		$percentile = $this->get_user_percentile( $user_id );
		$topics = $this->get_topic_performance( $user_id );

		return $cache[ $user_id ] = array(
			'total_attempts' => absint( $total_attempts ),
			'passed_exams'   => absint( $passed_exams ),
			'total_spent'    => floatval( $total_spent ),
			'active_courses' => absint( $active_courses ),
			'streak'         => $streak,
			'rank'           => $rank,
			'percentile'     => $percentile,
			'topic_performance' => $topics,
			'completion'     => $this->get_profile_completion_percentage( $user_id )
		);
	}

	public function get_profile_completion_percentage( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) return 0;
		
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		
		// Fallback to display name parts
		if ( empty( $first_name ) && ! empty( $user->display_name ) ) {
			$parts = explode( ' ', $user->display_name, 2 );
			$first_name = $parts[0];
			$last_name = isset( $parts[1] ) ? $parts[1] : $last_name;
		}
		
		$fields = array(
			'first_name'     => $first_name,
			'last_name'      => $last_name,
			'phone'          => get_user_meta( $user_id, 'gep_phone', true ),
			'bio'            => get_user_meta( $user_id, 'description', true ),
			'qualification'  => get_user_meta( $user_id, 'gep_qualification', true ),
			'student_goals'  => get_user_meta( $user_id, 'gep_student_goals', true )
		);

		$total = count( $fields );
		$filled = 0;
		foreach ( $fields as $val ) {
			if ( ! empty( $val ) ) $filled++;
		}

		return round( ( $filled / $total ) * 100 );
	}

	public function get_user_streak( $user_id ) {
		$last_activity = get_user_meta( $user_id, 'gep_last_activity', true );
		$current_streak = (int) get_user_meta( $user_id, 'gep_current_streak', true );
		$today = date( 'Y-m-d' );
		$yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );

		if ( $last_activity === $today ) {
			return $current_streak;
		}

		if ( $last_activity === $yesterday ) {
			$current_streak++;
		} else {
			$current_streak = 1;
		}

		update_user_meta( $user_id, 'gep_last_activity', $today );
		update_user_meta( $user_id, 'gep_current_streak', $current_streak );

		return $current_streak;
	}

	public function get_user_rank( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';

		// Rank = (number of students scoring more) + 1, computed in SQL.
		// The previous version pulled one row per student into PHP and looped —
		// on a portal with tens of thousands of students that is a full scan on
		// every dashboard render.
		$my_total = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(score) FROM $table WHERE user_id = %d AND status = 'submitted'",
			$user_id
		) );
		if ( $my_total === null ) {
			return '--';
		}

		$ahead = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM (
				SELECT user_id, SUM(score) AS total_score
				FROM $table WHERE status = 'submitted' GROUP BY user_id
			) AS totals WHERE totals.total_score > %f",
			(float) $my_total
		) );

		return (int) $ahead + 1;
	}

	public function get_user_percentile( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_attempts';
		
		$total_students = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table WHERE status = 'submitted'");
		if ( $total_students <= 1 ) return 100;

		$my_score = $wpdb->get_var( $wpdb->prepare("SELECT SUM(score) FROM $table WHERE user_id = %d AND status = 'submitted'", $user_id) );
		if ( $my_score === null ) return 0;

		$students_below = $wpdb->get_var( $wpdb->prepare("
			SELECT COUNT(*) FROM (
				SELECT SUM(score) as total_score FROM $table WHERE status = 'submitted' GROUP BY user_id
			) as scores WHERE total_score < %f
		", $my_score) );

		return round( ($students_below / $total_students) * 100, 1 );
	}

	public function get_topic_performance( $user_id ) {
		global $wpdb;
		$table_attempts = $wpdb->prefix . 'gep_attempts';
		$table_questions = $wpdb->prefix . 'gep_questions';
		$table_categories = $wpdb->prefix . 'gep_categories';
		
		$attempts = $wpdb->get_results( $wpdb->prepare( "SELECT answers, test_id FROM $table_attempts WHERE user_id = %d AND status = 'submitted'", $user_id ) );
		
		if ( empty($attempts) ) {
			return array();
		}

		// 1. Gather all question IDs
		$all_q_ids = array();
		$attempts_data = array();
		foreach ( $attempts as $attempt ) {
			$answers = json_decode( $attempt->answers, true );
			if ( ! is_array( $answers ) || empty( $answers ) ) continue;
			$attempts_data[] = $answers;
			foreach ( array_keys( $answers ) as $qid ) {
				$all_q_ids[] = (int) $qid;
			}
		}

		if ( empty( $all_q_ids ) ) {
			return array();
		}

		$all_q_ids = array_unique( $all_q_ids );
		$q_ids_str = implode( ',', $all_q_ids );

		// 2. Batch fetch question details
		$questions = $wpdb->get_results( "SELECT id, category_id, marks, negative_marks, correct_answer, question_type, numerical_tolerance FROM $table_questions WHERE id IN ($q_ids_str)" );
		$questions_map = array();
		$cat_ids = array();
		foreach ( $questions as $q ) {
			$questions_map[ $q->id ] = $q;
			$cat_ids[] = (int) $q->category_id;
		}
		$cat_ids = array_unique( $cat_ids );

		// 3. Batch fetch category names
		$category_names = array();
		if ( ! empty( $cat_ids ) ) {
			$cat_ids_str = implode( ',', $cat_ids );
			$categories_res = $wpdb->get_results( "SELECT id, name FROM $table_categories WHERE id IN ($cat_ids_str)" );
			foreach ( $categories_res as $cat ) {
				$category_names[ $cat->id ] = $cat->name;
			}
		}

		$category_scores = array();

		$test_ids_for_neg = array_unique( wp_list_pluck( $attempts, 'test_id' ) );
		$global_neg_map = array();
		if ( ! empty($test_ids_for_neg) ) {
			$ids_str = implode(',', $test_ids_for_neg);
			$tests_res = $wpdb->get_results("SELECT id, translated_data FROM {$wpdb->prefix}gep_tests WHERE id IN ($ids_str)");
			foreach($tests_res as $t) {
				$td = gep_safe_json_decode($t->translated_data, true);
				$global_neg_map[$t->id] = isset($td['global_negative_marks']) && $td['global_negative_marks'] !== '' ? floatval($td['global_negative_marks']) : null;
			}
		}

		foreach ( $attempts as $attempt ) {
			$test_id = $attempt->test_id;
			$global_neg = isset($global_neg_map[$test_id]) ? $global_neg_map[$test_id] : null;
			
			$answers = json_decode( $attempt->answers, true );
			if ( ! is_array( $answers ) ) continue;

			foreach ( $answers as $q_id => $ans_details ) {
				if ( ! isset( $questions_map[ $q_id ] ) ) continue;
				$q = $questions_map[ $q_id ];
				$cat_id = (int)$q->category_id;

				if ( ! isset( $category_scores[$cat_id] ) ) {
					$category_scores[$cat_id] = array('earned' => 0, 'total' => 0);
				}

				$category_scores[$cat_id]['total'] += (float)$q->marks;

				$user_ans = isset($ans_details['answer']) ? $ans_details['answer'] : '';

				if ( $user_ans !== '' && class_exists('GEP_Exam_Engine') && GEP_Exam_Engine::evaluate_answer($q, $user_ans) ) {
					$category_scores[$cat_id]['earned'] += (float)$q->marks;
				} else if ( ! empty( $user_ans ) ) {
					$penalty = $global_neg !== null ? $global_neg : (float)$q->negative_marks;
					$category_scores[$cat_id]['earned'] -= $penalty;
				}
			}
		}

		$aggregated = array();

		foreach ( $category_scores as $cat_id => $data ) {
			if ( $data['total'] <= 0 ) continue;
			
			$cat_name = isset( $category_names[$cat_id] ) ? $category_names[$cat_id] : 'General';

			if ( ! isset( $aggregated[$cat_name] ) ) {
				$aggregated[$cat_name] = array('earned' => 0, 'total' => 0);
			}

			$aggregated[$cat_name]['earned'] += $data['earned'];
			$aggregated[$cat_name]['total'] += $data['total'];
		}

		$performance = array();
		foreach ( $aggregated as $topic => $data ) {
			$accuracy = round( ($data['earned'] / $data['total']) * 100 );
			$accuracy = max(0, min(100, $accuracy)); // Ensure bounds

			$performance[] = array(
				'topic' => $topic,
				'accuracy' => $accuracy
			);
		}

		// Sort by accuracy DESC and limit to top 4 for dashboard display
		usort($performance, function($a, $b) {
			return $b['accuracy'] <=> $a['accuracy'];
		});
		
		return array_slice($performance, 0, 4);
	}

	public function get_user_purchased_items( $user_id, $item_type = 'test', $type_filter = 'all' ) {
		global $wpdb;
		$orders_table = $wpdb->prefix . 'gep_orders';
		
		if ( $item_type === 'course' ) {
			$access_table = "{$wpdb->prefix}gep_user_course_access";
			$data_table   = "{$wpdb->prefix}gep_courses";
			$column       = 'course_id';
		} else {
			$access_table = "{$wpdb->prefix}gep_user_test_access";
			$data_table   = "{$wpdb->prefix}gep_tests";
			$column       = 'test_id';
		}

		$order_items = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT item_id FROM $orders_table WHERE user_id = %d AND item_type = %s AND status = 'success'", $user_id, $item_type ) );
		$manual_items = $wpdb->get_col( $wpdb->prepare( "SELECT $column FROM $access_table WHERE user_id = %d", $user_id ) );

		$all_ids = array_unique( array_merge( (array)$order_items, (array)$manual_items ) );

		if ( empty( $all_ids ) ) return array();

		$ids_str = implode( ',', array_map( 'absint', $all_ids ) );
		
		$where_type = '';
		if ( $item_type === 'test' && $type_filter !== 'all' ) {
			if ( $type_filter === 'single' ) {
				$where_type = " AND type != 'series'";
			} else {
				$where_type = $wpdb->prepare(" AND type = %s", $type_filter);
			}
		}

		// BUG-08 FIX: Replaced raw SQL string interpolation with a safe prepared query
		$safe_ids_str = implode( ',', array_map( 'absint', $all_ids ) );
		$safe_user_id = absint( $user_id );
		$safe_item_type = esc_sql( $item_type );

		$attempts_table = $wpdb->prefix . 'gep_attempts';
        $extra_attempts_sql = $item_type === 'course' ? '0' : 'COALESCE(a.extra_attempts, 0)';

		return $wpdb->get_results( "
			SELECT d.*, 
			       (SELECT created_at FROM $orders_table WHERE user_id = $safe_user_id AND item_id = d.id AND item_type = '$safe_item_type' AND status = 'success' LIMIT 1) as enrollment_date,
			       $extra_attempts_sql as extra_attempts,
			       (SELECT COUNT(*) FROM $attempts_table WHERE user_id = $safe_user_id AND test_id = d.id AND status = 'submitted') as used_attempts
			FROM $data_table d
			LEFT JOIN $access_table a ON d.id = a.$column AND a.user_id = $safe_user_id
			WHERE d.id IN ($safe_ids_str) AND d.status = 'publish' $where_type
			ORDER BY d.id DESC
		" );
	}

	public function get_available_items( $item_type = 'test', $limit = 50, $category_id = 0, $type_filter = 'all' ) {
		global $wpdb;
		
		$params = array();
		if ( $item_type === 'course' ) {
			$data_table = "{$wpdb->prefix}gep_courses";
			$query = "SELECT * FROM $data_table WHERE status = %s";
			$params[] = 'publish';
		} else {
			$data_table = "{$wpdb->prefix}gep_tests";
			if ( $type_filter === 'all' ) {
				$query = "SELECT * FROM $data_table WHERE status = %s";
				$params[] = 'publish';
			} else {
				$query = "SELECT * FROM $data_table WHERE status = %s AND type = %s";
				$params[] = 'publish';
				$params[] = $type_filter;
			}
		}
		
		// Goal-based segment filtering (only apply if no specific category is selected)
		$user_id = get_current_user_id();
		$student_goals = get_user_meta( $user_id, 'gep_student_goals', true );
		if ( $category_id <= 0 && is_array( $student_goals ) && ! empty( $student_goals ) ) {
			$goal_ids = implode( ',', array_map( 'absint', $student_goals ) );
			$query .= " AND category_id IN ($goal_ids)";
		}

		if ( $category_id > 0 ) {
			// Find all child categories of this category to include them in the query
			$cat_ids = array( $category_id );
			$children = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}gep_categories WHERE parent_id = %d",
				$category_id
			) );
			if ( ! empty( $children ) ) {
				$cat_ids = array_merge( $cat_ids, array_map( 'absint', $children ) );
			}
			
			// Fallback: If this is a child category and has NO tests directly,
			// check if we should fall back to its parent category
			$test_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $data_table WHERE status = 'publish' AND category_id = %d",
				$category_id
			) );
			if ( $test_count == 0 ) {
				$parent_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT parent_id FROM {$wpdb->prefix}gep_categories WHERE id = %d",
					$category_id
				) );
				if ( $parent_id > 0 ) {
					// Fall back to parent and all its siblings/children
					$cat_ids[] = $parent_id;
					$sibling_children = $wpdb->get_col( $wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}gep_categories WHERE parent_id = %d",
						$parent_id
					) );
					if ( ! empty( $sibling_children ) ) {
						$cat_ids = array_merge( $cat_ids, array_map( 'absint', $sibling_children ) );
					}
				}
			}
			
			$cat_ids = array_unique( array_filter( $cat_ids ) );
			$safe_cat_ids = implode( ',', $cat_ids );
			$query .= " AND category_id IN ($safe_cat_ids)";
		}
		
		$query .= " ORDER BY id DESC LIMIT %d";
		$params[] = absint( $limit );
		
		return $wpdb->get_results( $wpdb->prepare( $query, $params ) );
	}
	/**
	 * Find published tests whose category or subcategory name matches any of the given
	 * keywords (case-insensitive). Used to derive "Paper 1" / "Sanskrit" style dashboard
	 * groupings from existing category data instead of hardcoding category/test IDs,
	 * which would break between environments (TRAP #14/#15).
	 *
	 * @param string|array $keywords    One or more substrings to match against category names.
	 * @param string       $type_filter Test type to restrict to, or 'all'.
	 * @param int          $limit       Max tests to return.
	 */
	public function get_tests_by_subject_keywords( $keywords, $type_filter = 'all', $limit = 12 ) {
		global $wpdb;
		$keywords = array_filter( (array) $keywords );
		if ( empty( $keywords ) ) return array();

		// The same keyword set is looked up several times per page (single /
		// multiple / random buckets); resolve the categories once per request.
		static $cat_cache = array();
		$cache_key = md5( implode( '|', $keywords ) );
		if ( isset( $cat_cache[ $cache_key ] ) ) {
			$cat_ids = $cat_cache[ $cache_key ];
		} else {
			$like_clauses = array();
			$like_params  = array();
			foreach ( $keywords as $kw ) {
				$like_clauses[] = 'name LIKE %s';
				$like_params[]  = '%' . $wpdb->esc_like( $kw ) . '%';
			}
			$cat_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}gep_categories WHERE " . implode( ' OR ', $like_clauses ),
				$like_params
			) );
			$cat_cache[ $cache_key ] = $cat_ids;
		}
		if ( empty( $cat_ids ) ) return array();

		$cat_ids_str = implode( ',', array_map( 'absint', $cat_ids ) );
		$where_type  = '';
		$params      = array();
		if ( $type_filter !== 'all' ) {
			$where_type = ' AND type = %s';
			$params[]   = $type_filter;
		}

		$sql = "SELECT * FROM {$wpdb->prefix}gep_tests
				WHERE status = 'publish' AND (category_id IN ($cat_ids_str) OR subcategory_id IN ($cat_ids_str))
				$where_type
				ORDER BY id DESC LIMIT %d";
		$params[] = absint( $limit );

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	public function has_access( $user_id, $item_id, $item_type = 'test' ) {
		// Memoised per request — dashboards call this once per rendered card and
		// repeatedly hit the same rows.
		static $access_cache = array();
		$cache_key = $user_id . '|' . $item_type . '|' . $item_id;
		if ( isset( $access_cache[ $cache_key ] ) ) {
			return $access_cache[ $cache_key ];
		}
		$access_cache[ $cache_key ] = $this->compute_access( $user_id, $item_id, $item_type );
		return $access_cache[ $cache_key ];
	}

	private function compute_access( $user_id, $item_id, $item_type = 'test' ) {
		// ADMIN BYPASS: Only bypass for admins when using the admin impersonation (uid=) param
		// BUG FIX: Do NOT auto-grant access to admins — admins must be able to test payment flow
		if ( current_user_can( 'manage_options' ) && isset( $_GET['uid'] ) ) return true;

		// MOCK TEST PASS SUBSCRIPTION BYPASS:
		// Grant full access to all tests & test series if user has an active pass subscription
		if ( $item_type === 'test' || $item_type === 'series' ) {
			$pass_expiry = get_user_meta( $user_id, 'gep_pass_expiry', true );
			if ( $pass_expiry && strtotime( $pass_expiry ) > current_time( 'timestamp' ) ) {
				return true;
			}
		}
		
		global $wpdb;
		// 'series' items are stored in the test access table as type='test'
		$access_item_type = ( $item_type === 'series' ) ? 'test' : $item_type;
		$table  = ( $access_item_type === 'course' ) ? "{$wpdb->prefix}gep_user_course_access" : "{$wpdb->prefix}gep_user_test_access";
		$column = ( $access_item_type === 'course' ) ? 'course_id' : 'test_id';

		$manual = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id = %d AND $column = %d", $user_id, $item_id ) );
		if ( $manual ) return true;

		// Check successful orders (check both 'test' and 'series' for series items)
		$types_to_check = ( $item_type === 'series' ) ? array( 'test', 'series' ) : array( $item_type );
		$placeholders = implode( ',', array_fill( 0, count( $types_to_check ), '%s' ) );
		$order = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM {$wpdb->prefix}gep_orders WHERE user_id = %d AND item_id = %d AND item_type IN ($placeholders) AND status = 'success'", 
			array_merge( array( $user_id, $item_id ), $types_to_check )
		) );

		return (bool) $order;
	}
}
