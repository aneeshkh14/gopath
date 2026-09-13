<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Test & Test Series management logic.
 */
class GEP_Test {

	public function get_test( $id, $attempt = null ) {
		if ( (int)$id === 999999 ) {
			$user_id = get_current_user_id();
			global $wpdb;
			if ( !$attempt && isset( $_GET['attempt_id'] ) ) {
				$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT analytics_data FROM {$wpdb->prefix}gep_attempts WHERE id = %d", absint( $_GET['attempt_id'] ) ) );
			} elseif (!$attempt) {
				$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT analytics_data FROM {$wpdb->prefix}gep_attempts WHERE user_id = %d AND test_id = 999999 ORDER BY id DESC LIMIT 1", $user_id ) );
			}
			$title = 'PYQ Practice Test';
			$duration = 15;
			if ( $attempt && ! empty( $attempt->analytics_data ) ) {
				$analytics = json_decode( $attempt->analytics_data, true );
				if ( isset( $analytics['practice_title'] ) ) {
					$title = $analytics['practice_title'];
				}
				if ( isset( $analytics['duration'] ) ) {
					$duration = absint($analytics['duration']);
				}
			}
			return (object) array(
				'id' => 999999,
				'title' => $title,
				'slug' => 'pyq-practice-test',
				'type' => 'practice',
				'exam_mode' => 'custom',
				'category_id' => 0,
				'subcategory_id' => 0,
				'price' => 0,
				'is_free' => 1,
				'thumbnail' => '',
				'instructions' => 'Welcome to the dynamic PYQ Practice Test. Answer all questions within the time limit.',
				'translated_data' => '',
				'duration_minutes' => $duration,
				'section_timings' => '',
				'total_marks' => 10,
				'pass_marks' => 4,
				'attempt_limit' => 99999,
				'shuffle_questions' => 1,
				'shuffle_options' => 1,
				'proctoring_enabled' => 0,
				'validity_date' => null,
				'status' => 'publish'
			);
		}
		global $wpdb;
		$table = $wpdb->prefix . 'gep_tests';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public function get_test_questions( $test_id, $attempt_id = 0 ) {
		global $wpdb;
		if ( $attempt_id ) {
			$attempt = $wpdb->get_row( $wpdb->prepare( "SELECT question_ids FROM {$wpdb->prefix}gep_attempts WHERE id = %d", $attempt_id ) );
			if ( $attempt && ! empty( $attempt->question_ids ) ) {
				return array_filter( array_map( 'absint', explode( ',', $attempt->question_ids ) ) );
			}
		}
		$table = $wpdb->prefix . 'gep_test_questions';
		return $wpdb->get_col( $wpdb->prepare( "SELECT question_id FROM $table WHERE test_id = %d ORDER BY order_no ASC", $test_id ) );
	}

	public function get_tests( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_tests';
		
		$defaults = array(
			'category_id' => null,
			'type'        => 'all', // Changed default to all
			'status'      => 'publish',
			'limit'       => 50,
			'offset'      => 0
		);
		$args = wp_parse_args( $args, $defaults );

		$where = $args['status'] === 'all' ? "WHERE 1=1" : "WHERE status = %s";
		$params = $args['status'] === 'all' ? array() : array( $args['status'] );

		if ( $args['type'] !== 'all' ) {
			$where .= " AND type = %s";
			$params[] = $args['type'];
		}

		if ( $args['category_id'] ) {
			$where .= " AND category_id = %d";
			$params[] = $args['category_id'];
		}

		$sql = "SELECT * FROM $table $where ORDER BY id DESC LIMIT %d OFFSET %d";
		$params[] = absint( $args['limit'] );
		$params[] = absint( $args['offset'] );

		return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
	}

	/**
	 * Check if a user has access to a test.
	 */
	public function user_has_access( $user_id, $test_id ) {
		if ( $test_id == 999999 ) {
			return true;
		}
		$test = $this->get_test( $test_id );
		if ( ! $test ) return false;
		
		// 0. Administrators always have access
		if ( current_user_can( 'manage_options' ) ) return true;

		// 1. If it's free, they always have access
		// NOTE: Attempt limits for free tests are enforced in render_exam separately
		if ( $test->is_free ) return true;

		global $wpdb;
		// 2. Check explicit user test access grant (admin-granted access)
		$access_table = $wpdb->prefix . 'gep_user_test_access';
		$access_exists = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $access_table WHERE user_id = %d AND test_id = %d", 
			$user_id, $test_id 
		) );
		if ( $access_exists ) return true;

		// 3. Check paid orders for this test
		$order_table = $wpdb->prefix . 'gep_orders';
		$order_exists = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $order_table WHERE user_id = %d AND item_id = %d AND item_type = 'test' AND status = 'success' ORDER BY created_at DESC LIMIT 1", 
			$user_id, $test_id 
		) );
		if ( $order_exists ) return true;

		// 4. Check if test belongs to a series the user has access to (either purchased or manually assigned)
		$series_ids = $wpdb->get_col( $wpdb->prepare( "SELECT series_id FROM {$wpdb->prefix}gep_test_series WHERE test_id = %d", $test_id ) );
		if ( ! empty( $series_ids ) ) {
			$s_ids_str = implode( ',', array_map( 'absint', $series_ids ) );
			
			// Check if purchased
			$has_series_purchase = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $order_table WHERE user_id = %d AND item_id IN ($s_ids_str) AND item_type = 'test' AND status = 'success' LIMIT 1", 
				$user_id 
			) );
			if ( $has_series_purchase ) return true;

			// Check if manually assigned
			$has_series_manual = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $access_table WHERE user_id = %d AND test_id IN ($s_ids_str) LIMIT 1", 
				$user_id 
			) );
			if ( $has_series_manual ) return true;
		}

		return false;
	}

	/**
	 * Get attempts remaining for a user.
	 */
	public function get_remaining_attempts( $user_id, $test_id ) {
		if ( $test_id == 999999 ) {
			return 99999;
		}
		$test = $this->get_test( $test_id );
		if ( ! $test ) return 0;

		global $wpdb;

		// If it's a random test, attempts are purchased in packages and are NOT unlimited
		if ( isset($test->type) && $test->type === 'random' ) {
			$access_table = $wpdb->prefix . 'gep_user_test_access';
			$purchased_attempts = $wpdb->get_var( $wpdb->prepare( 
				"SELECT SUM(extra_attempts) FROM $access_table WHERE user_id = %d AND test_id = %d", 
				$user_id, $test_id 
			) );
			$purchased_attempts = $purchased_attempts ? absint($purchased_attempts) : 0;
			
			$attempt_table = $wpdb->prefix . 'gep_attempts';
			$submitted_count = $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM $attempt_table WHERE user_id = %d AND test_id = %d AND status = 'submitted'", 
				$user_id, $test_id 
			) );
			
			return max( 0, $purchased_attempts - $submitted_count );
		}

		// 1. Unlimited by default
		if ( $test->attempt_limit == 0 ) return 999999;

		// 2. Unlimited if user has purchased the test (lifetime access = unlimited attempts)
		$order_table = $wpdb->prefix . 'gep_orders';
		$has_purchased = $wpdb->get_var( $wpdb->prepare( 
			"SELECT id FROM $order_table WHERE user_id = %d AND item_id = %d AND item_type = 'test' AND status = 'success' LIMIT 1", 
			$user_id, $test_id 
		) );
		
		if ( $has_purchased ) return 999999;

		// 3. Unlimited if user has access to the series containing this test (either purchased or manually assigned)
		$series_ids = $wpdb->get_col( $wpdb->prepare( "SELECT series_id FROM {$wpdb->prefix}gep_test_series WHERE test_id = %d", $test_id ) );
		if ( ! empty( $series_ids ) ) {
			$s_ids_str = implode( ',', array_map( 'absint', $series_ids ) );
			
			// Check if purchased
			$has_series_purchase = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $order_table WHERE user_id = %d AND item_id IN ($s_ids_str) AND item_type = 'test' AND status = 'success' LIMIT 1", 
				$user_id 
			) );
			if ( $has_series_purchase ) return 999999;

			// Check if manually assigned
			$access_table = $wpdb->prefix . 'gep_user_test_access';
			$has_series_manual = $wpdb->get_var( $wpdb->prepare( 
				"SELECT id FROM $access_table WHERE user_id = %d AND test_id IN ($s_ids_str) LIMIT 1", 
				$user_id 
			) );
			if ( $has_series_manual ) return 999999;
		}

		$attempt_table = $wpdb->prefix . 'gep_attempts';
		$submitted_count = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM $attempt_table WHERE user_id = %d AND test_id = %d AND status = 'submitted'", 
			$user_id, $test_id 
		) );

		$access_table = $wpdb->prefix . 'gep_user_test_access';
		$extra_attempts = $wpdb->get_var( $wpdb->prepare( 
			"SELECT extra_attempts FROM $access_table WHERE user_id = %d AND test_id = %d", 
			$user_id, $test_id 
		) );

		return ( $test->attempt_limit + absint( $extra_attempts ) ) - $submitted_count;
	}

	public function get_question_count( $test_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_test_questions';
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE test_id = %d", $test_id ) ) );
	}

	/**
	 * Question counts for many tests in ONE query.
	 *
	 * Listing screens previously called get_question_count() inside the card loop,
	 * so a page of 50 tests issued 50 extra queries.
	 *
	 * @param int[] $test_ids
	 * @return array test_id => count
	 */
	public function get_question_counts( $test_ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', (array) $test_ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$ids_str = implode( ',', array_unique( $ids ) );
		$rows = $wpdb->get_results(
			"SELECT test_id, COUNT(*) AS c FROM {$wpdb->prefix}gep_test_questions WHERE test_id IN ($ids_str) GROUP BY test_id"
		);
		$map = array();
		foreach ( $rows as $r ) {
			$map[ (int) $r->test_id ] = (int) $r->c;
		}
		return $map;
	}

	/**
	 * Series test counts for many series in ONE query (same N+1 problem).
	 *
	 * @param int[] $series_ids
	 * @return array series_id => count
	 */
	public function get_series_test_counts( $series_ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', (array) $series_ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$ids_str = implode( ',', array_unique( $ids ) );
		$rows = $wpdb->get_results(
			"SELECT series_id, COUNT(*) AS c FROM {$wpdb->prefix}gep_test_series WHERE series_id IN ($ids_str) GROUP BY series_id"
		);
		$map = array();
		foreach ( $rows as $r ) {
			$map[ (int) $r->series_id ] = (int) $r->c;
		}
		return $map;
	}

	public function get_series_test_count( $series_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_test_series';
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE series_id = %d", $series_id ) ) );
	}

	public function get_series_tests( $series_id ) {
		global $wpdb;
		$table_series = $wpdb->prefix . 'gep_test_series';
		$table_tests  = $wpdb->prefix . 'gep_tests';
		
		return $wpdb->get_results( $wpdb->prepare( 
			"SELECT t.* FROM $table_tests t 
			JOIN $table_series s ON t.id = s.test_id 
			WHERE s.series_id = %d 
			ORDER BY s.id ASC", 
			$series_id 
		) );
	}
}
