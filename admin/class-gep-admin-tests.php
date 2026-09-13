<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin tests management logic.
 */
class GEP_Admin_Tests {
    public static $save_error = '';
    public static $submitted_test = null;

	public function handle_test_actions() {
        if (!current_user_can('manage_options')) return;
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-tests' ) return;

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			if ( check_admin_referer( 'gep_test_delete_' . $_GET['id'] ) ) {
				$this->delete_test( absint( $_GET['id'] ) );
				if ( ob_get_level() > 0 ) ob_end_clean();
				wp_cache_flush(); // CACHE FIX: Bust cache after delete
				wp_redirect( admin_url( 'admin.php?page=gep-tests&message=deleted' ) );
				exit;
			}
		}

		if ( isset( $_POST['gep_test_nonce'] ) && wp_verify_nonce( $_POST['gep_test_nonce'], 'gep_test_save' ) ) {
			$this->handle_save_test();
		}
	}

	private function handle_save_test() {
		// ── Handle Multi-Subject Sections ────────────────────────────────────
		$sections_data = array();
		$all_question_ids_from_sections = array();

		if ( isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ) {
			foreach ( $_POST['sections'] as $sec ) {
				$sec_name = sanitize_text_field( isset($sec['name']) ? $sec['name'] : '' );
				$sec_ids  = sanitize_text_field( isset($sec['ids'])  ? $sec['ids']  : '' );
				$sec_time = isset($sec['time_limit']) ? absint($sec['time_limit']) : 0;
				$sec_shuffle_q = isset($sec['shuffle_questions']) && $sec['shuffle_questions'] == '1' ? 1 : 0;
				$sec_shuffle_o = isset($sec['shuffle_options']) && $sec['shuffle_options'] == '1' ? 1 : 0;
				$sec_start = isset($sec['shuffle_start']) && $sec['shuffle_start'] !== '' ? absint($sec['shuffle_start']) : '';
				$sec_end   = isset($sec['shuffle_end']) && $sec['shuffle_end'] !== '' ? absint($sec['shuffle_end']) : '';
				$sec_marks = isset($sec['marks']) && $sec['marks'] !== '' ? floatval($sec['marks']) : 2.0;
				$sec_neg   = isset($sec['negative_marks']) && $sec['negative_marks'] !== '' ? floatval($sec['negative_marks']) : 0.0;

				if ( $sec_ids !== '' || $sec_name !== '' ) {
					$sections_data[] = array(
						'name'              => $sec_name,
						'ids'               => $sec_ids,
						'time_limit'        => $sec_time,
						'shuffle_questions' => $sec_shuffle_q,
						'shuffle_options'   => $sec_shuffle_o,
						'shuffle_start'     => $sec_start,
						'shuffle_end'       => $sec_end,
						'marks'             => $sec_marks,
						'negative_marks'    => $sec_neg
					);
					// Collect all IDs for linking
					if ( $sec_ids ) {
						$id_parts = array_filter( array_map( 'trim', explode( ',', $sec_ids ) ) );
						foreach ( $id_parts as $sid ) {
							if ( is_numeric($sid) ) {
								$all_question_ids_from_sections[] = absint($sid);
							}
						}
					}
				}
			}
		}

		// Merge section IDs with any directly-entered IDs (backward compat)
		$direct_ids_str = isset( $_POST['question_ids'] ) ? sanitize_text_field( $_POST['question_ids'] ) : '';
		if ( empty($all_question_ids_from_sections) && $direct_ids_str ) {
			// Old-style single ID field: use as-is
			$all_question_ids_from_sections = array_filter( array_map( 'absint', explode( ',', $direct_ids_str ) ) );
		}

		$attempt_pricing = array();
		if ( isset( $_POST['attempt_pricing'] ) && is_array( $_POST['attempt_pricing'] ) ) {
			foreach ( $_POST['attempt_pricing'] as $tier ) {
				$attempts = absint( isset($tier['attempts']) ? $tier['attempts'] : 0 );
				$price    = floatval( isset($tier['price']) ? $tier['price'] : 0 );
				if ( $attempts > 0 ) {
					$attempt_pricing[] = array(
						'attempts' => $attempts,
						'price'    => $price
					);
				}
			}
		}

        $existing = !empty($_POST['test_id']) ? (new GEP_Test())->get_test(absint($_POST['test_id'])) : null;
        $previous_data = $existing ? (json_decode($existing->translated_data, true) ?: array()) : array();
		// ── Build translated_data (preserves existing + adds sections) ────────
		$translated_data = array_merge($previous_data, array(
			'instructions'  => wp_kses_post( isset($_POST['instructions_hi']) ? $_POST['instructions_hi'] : '' ),
			'refundable'    => sanitize_text_field( isset($_POST['refundable']) ? $_POST['refundable'] : 'no' ),
			'topics'        => sanitize_textarea_field( isset($_POST['topics']) ? $_POST['topics'] : '' ),
			'what_you_get'  => sanitize_textarea_field( isset($_POST['what_you_get']) ? $_POST['what_you_get'] : '' ),
			'shuffle_start' => isset($_POST['shuffle_start']) && $_POST['shuffle_start'] !== '' ? absint($_POST['shuffle_start']) : '',
			'shuffle_end'   => isset($_POST['shuffle_end']) && $_POST['shuffle_end'] !== '' ? absint($_POST['shuffle_end']) : '',
			'global_negative_marks' => isset($_POST['global_negative_marks']) && $_POST['global_negative_marks'] !== '' ? floatval($_POST['global_negative_marks']) : '',
			'sections'      => $sections_data, // ← NEW: multi-subject sections
			'attempt_pricing' => $attempt_pricing, // ← NEW: attempt pricing tiers
		));

		// ── CRITICAL FIX: Generate a slug from the title ──────────────────────
		$title = sanitize_text_field( $_POST['title'] );
		$base_slug = sanitize_title( $title );
		$is_edit = ! empty( $_POST['test_id'] );

		$data = array(
			'title'            => $title,
			'type'             => sanitize_text_field( $_POST['type'] ),
			'exam_mode'        => sanitize_text_field( isset($_POST['exam_mode']) ? $_POST['exam_mode'] : 'custom' ),
			'category_id'      => absint( $_POST['category_id'] ),
			'subcategory_id'   => isset($_POST['subcategory_id']) ? absint($_POST['subcategory_id']) : 0,
			'price'            => floatval( $_POST['price'] ),
			'is_free'          => isset( $_POST['is_free'] ) ? 1 : 0,
			'thumbnail'        => esc_url_raw( isset($_POST['thumbnail']) ? $_POST['thumbnail'] : '' ),
			'duration_minutes' => intval( $_POST['duration_minutes'] ),
			'attempt_limit'    => absint( $_POST['attempt_limit'] ),
			'shuffle_questions'=> isset( $_POST['shuffle_questions'] ) ? 1 : 0,
			'shuffle_options'  => isset( $_POST['shuffle_options'] ) ? 1 : 0,
			'pass_marks'       => absint( $_POST['pass_marks'] ),
			'status'           => sanitize_text_field( $_POST['status'] ),
			'instructions'     => wp_kses_post( $_POST['instructions'] ),
			'translated_data'  => json_encode( $translated_data )
		);

		if ( ! $is_edit ) {
			$data['slug'] = $base_slug . '-' . time();
		}

		if ( ! empty( $_POST['test_id'] ) ) {
			$data['id'] = absint( $_POST['test_id'] );
		}

        $series_ids = array_filter(array_map('absint', explode(',', $_POST['series_test_ids'] ?? '')));
        $test_id = $this->save_test_with_links($data, $all_question_ids_from_sections, $series_ids);
        if (is_wp_error($test_id)) {
            self::$save_error = $test_id->get_error_message();
            self::$submitted_test = (object)array_merge(array('id' => 0), $data);
            return;
        }

		if ( ob_get_level() > 0 ) ob_end_clean();
		wp_cache_flush();
		wp_redirect( admin_url( 'admin.php?page=gep-tests&message=saved' ) );
		exit;
	}

	public function list_tests( $args = array() ) {
		$test_logic = new GEP_Test();
		return $test_logic->get_tests( array_merge(array('status' => 'all'), $args) );
	}

	public function save_test( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_tests';

		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			$id = $data['id'];
			unset( $data['id'] );
			return $wpdb->update( $table, $data, array( 'id' => $id ) ) === false ? false : $id;
		} else {
			return $wpdb->insert( $table, $data ) === false ? false : (int)$wpdb->insert_id;
		}
	}

    /** Validate the whole paper before changing any stored links. */
    public function save_test_with_links($data, $question_ids, $series_ids = array()) {
        global $wpdb;
        $error = static function($message) { return new WP_Error('invalid_test', $message); };
        if (trim($data['title'] ?? '') === '') return $error('Enter a test title.');
        if (!in_array($data['type'] ?? '', array('single','multiple','combined','self_test','random','series'), true)) return $error('Choose a valid test type.');
        if (!in_array($data['status'] ?? '', array('publish','draft'), true)) return $error('Choose Published or Draft.');
        if (($data['duration_minutes'] ?? 0) < 1) return $error('Duration must be at least one minute.');
        if (($data['price'] ?? 0) < 0) return $error('Price cannot be negative.');
        if (!empty($data['id']) && !(new GEP_Test())->get_test($data['id'])) return $error('This test no longer exists. Your edits have not been saved.');
        $td = json_decode($data['translated_data'] ?? '', true) ?: array();
        $section_ids = array(); $times = array();
        foreach (($td['sections'] ?? array()) as $section) {
            $raw = trim($section['ids'] ?? '');
            if ($raw !== '' && !preg_match('/^[1-9][0-9]*(\s*,\s*[1-9][0-9]*)*$/', $raw)) return $error('Section question IDs must be positive whole numbers separated by commas.');
            $ids = $raw === '' ? array() : array_map('absint', explode(',', $raw));
            foreach ($ids as $id) {
                if (in_array($id, $section_ids, true)) return $error('A question can appear only once in a test. Remove duplicate IDs across sections.');
                $section_ids[] = $id;
            }
            if (($section['marks'] ?? 0) < 0 || ($section['negative_marks'] ?? 0) < 0) return $error('Marks and penalties cannot be negative.');
            if ($data['status'] === 'publish' && !$ids && $data['type'] !== 'random') return $error('Add questions to every section before publishing, or save as Draft.');
            $times[] = (int)($section['time_limit'] ?? 0);
        }
        if (array_sum($times) > $data['duration_minutes'] || (array_sum($times) > 0 && in_array(0, $times, true))) return $error('Give every timed section a duration. Their total must not exceed the exam duration.');
        $question_ids = array_values(array_unique(array_filter(array_map('absint', $question_ids))));
        $series_ids = array_values(array_unique(array_filter(array_map('absint', $series_ids))));
        $ids = $data['type'] === 'series' ? $series_ids : $question_ids;
        if ($data['status'] === 'publish' && !$ids && $data['type'] !== 'random') return $error('Add questions or child tests before publishing, or save as Draft.');
        foreach (($td['attempt_pricing'] ?? array()) as $tier) if (($tier['price'] ?? 0) < 0 || ($tier['attempts'] ?? 0) < 1) return $error('Each attempt package needs a positive attempt count and a nonnegative price.');
        if ($wpdb->query('START TRANSACTION') === false) return $error('Could not save. Please retry.');
        try {
            if ($ids) {
                $table = $wpdb->prefix . ($data['type'] === 'series' ? 'gep_tests' : 'gep_questions');
                $found = $wpdb->get_col("SELECT id FROM $table WHERE id IN (" . implode(',', $ids) . ") FOR UPDATE");
                if (count($found) !== count($ids)) throw new RuntimeException('One or more question or child-test IDs do not exist. Check the IDs and retry.');
                if ($data['type'] === 'series') {
                    if (in_array((int)($data['id'] ?? 0), $ids, true)) throw new RuntimeException('A series cannot contain itself.');
                    if ($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE type = 'series' AND id IN (" . implode(',', $ids) . ")")) throw new RuntimeException('Add individual tests to a series; nested series are not supported.');
                }
            }
            $id = $this->save_test($data);
            if (!$id) throw new RuntimeException('The test could not be saved. Please retry.');
            $linked = $data['type'] === 'series' ? $this->link_tests_to_series($id, $series_ids) : $this->link_questions_to_test($id, $question_ids);
            if ($linked === false || $wpdb->query('COMMIT') === false) throw new RuntimeException('The questions could not be saved. Your existing test has been preserved. Please retry.');
            return $id;
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            return $error($e->getMessage());
        }
    }

	public function delete_test( $id ) {
		global $wpdb;
		// 1. Delete test-question links
		$wpdb->delete( $wpdb->prefix . 'gep_test_questions', array( 'test_id' => $id ) );
		// 2. Delete from series links (as a child)
		$wpdb->delete( $wpdb->prefix . 'gep_test_series', array( 'test_id' => $id ) );
		// 3. Delete series links (as a parent)
		$wpdb->delete( $wpdb->prefix . 'gep_test_series', array( 'series_id' => $id ) );
		// 4. Delete associated attempt records
		$wpdb->delete( $wpdb->prefix . 'gep_attempts', array( 'test_id' => $id ) );
		// 5. Delete test
		return $wpdb->delete( $wpdb->prefix . 'gep_tests', array( 'id' => $id ) );
	}

	public function link_questions_to_test( $test_id, $question_ids ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_test_questions';
		
		// Clear existing
		if ($wpdb->delete( $table, array( 'test_id' => $test_id ) ) === false) return false;

		// Perfect Intelligence Filter: Remove empty or invalid IDs to prevent debris
		$question_ids = array_filter( array_map( 'absint', $question_ids ) );

		$marks_map = array();
		if ( ! empty( $question_ids ) ) {
			$ids_str = implode( ',', $question_ids );
			$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_questions WHERE id IN ($ids_str)" );
            $results = GEP_Exam_Engine::apply_section_overrides($results, (new GEP_Test())->get_test($test_id));
			foreach ( $results as $row ) {
				$marks_map[ $row->id ] = floatval( $row->marks );
			}
		}

		$total_marks = 0;
		foreach ( $question_ids as $order => $q_id ) {
			if ( ! $q_id ) continue;

			if ($wpdb->insert( $table, array(
				'test_id'     => $test_id,
				'question_id' => $q_id,
				'order_no'    => $order + 1
			) ) === false) return false;

			$total_marks += isset( $marks_map[ $q_id ] ) ? $marks_map[ $q_id ] : 0.0;
		}

		// Sync total marks to test record
		return $wpdb->update(
			$wpdb->prefix . 'gep_tests', 
			array( 'total_marks' => $total_marks ), 
			array( 'id' => $test_id ) 
		);
	}

	public function link_tests_to_series( $series_id, $test_ids ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_test_series';
		if ($wpdb->delete( $table, array( 'series_id' => $series_id ) ) === false) return false;

		// Perfect Intelligence Filter: Remove empty or invalid IDs
		$test_ids = array_filter( array_map( 'absint', $test_ids ) );

		$marks_map = array();
		if ( ! empty( $test_ids ) ) {
			$ids_str = implode( ',', $test_ids );
			$results = $wpdb->get_results( "SELECT id, total_marks FROM {$wpdb->prefix}gep_tests WHERE id IN ($ids_str)" );
			foreach ( $results as $row ) {
				$marks_map[ $row->id ] = floatval( $row->total_marks );
			}
		}

		$total_marks = 0;
		foreach ( $test_ids as $order => $t_id ) {
			if ( ! $t_id ) continue;

			if ($wpdb->insert( $table, array(
				'series_id' => $series_id,
				'test_id'   => $t_id,
				'order_no'  => $order + 1
			) ) === false) return false;
			
			$total_marks += isset( $marks_map[ $t_id ] ) ? $marks_map[ $t_id ] : 0.0;
		}

		return $wpdb->update(
			$wpdb->prefix . 'gep_tests', 
			array( 'total_marks' => $total_marks ), 
			array( 'id' => $series_id ) 
		);
	}
}

