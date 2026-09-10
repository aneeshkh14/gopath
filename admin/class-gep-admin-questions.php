<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin questions management.
 */
class GEP_Admin_Questions {

	public function handle_question_actions() {
		// ─── DEEP DEBUG LOGGER ──────────────────────────────────────────────────
		// Writes to the protected log under wp-content/uploads/gopath-logs/.
		$log_file  = function_exists('gep_log_file') ? gep_log_file() : '';
		$is_post   = ( $_SERVER['REQUEST_METHOD'] === 'POST' );
		$page_ok   = ( isset( $_GET['page'] ) && $_GET['page'] === 'gep-questions' );
		$user_id   = get_current_user_id();
		$can_edit  = current_user_can( 'edit_posts' );
		$nonce_set = isset( $_POST['gep_question_nonce'] );
		$nonce_ok  = $nonce_set ? (bool) wp_verify_nonce( $_POST['gep_question_nonce'], 'gep_question_save' ) : false;

		if ( $is_post && $page_ok ) {
			$post_keys  = implode( ', ', array_keys( $_POST ) );
			$log_line   = sprintf(
				"\n[%s] handle_question_actions | user_id=%d | can_edit=%s | nonce_set=%s | nonce_ok=%s | POST_keys=[%s]\n",
				date( 'Y-m-d H:i:s' ),
				$user_id,
				$can_edit   ? 'YES' : 'NO',
				$nonce_set  ? 'YES' : 'NO',
				$nonce_ok   ? 'YES' : 'NO',
				$post_keys
			);
			gep_log_to( $log_file, $log_line, FILE_APPEND | LOCK_EX );
		}
		// ────────────────────────────────────────────────────────────────────────

		if ( ! $page_ok ) return;

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			if ( check_admin_referer( 'gep_question_delete_' . $_GET['id'] ) ) {
				$this->delete_question( absint( $_GET['id'] ) );
				if ( ob_get_level() > 0 ) ob_end_clean();
				wp_cache_flush();
				wp_redirect( admin_url( 'admin.php?page=gep-questions' ) );
				exit;
			}
		}

		if ( $nonce_set && $nonce_ok ) {
			$this->handle_save_question();
		} elseif ( $is_post && $nonce_set && ! $nonce_ok ) {
			// Nonce was set but FAILED — log the raw nonce value for diagnosis
			$raw_nonce = sanitize_text_field( $_POST['gep_question_nonce'] );
			$log_line  = sprintf(
				"[%s] NONCE FAILED — raw_nonce='%s' user_id=%d hook='%s'\n",
				date( 'Y-m-d H:i:s' ), $raw_nonce, $user_id, current_action()
			);
			gep_log_to( $log_file, $log_line, FILE_APPEND | LOCK_EX );
		}

		if ( isset( $_POST['gep_import_nonce'] ) && wp_verify_nonce( $_POST['gep_import_nonce'], 'gep_bulk_import' ) ) {
			$this->handle_bulk_import();
		}
	}


	private function handle_bulk_import() {
		$category_id = absint( $_POST['category_id'] );
		$subcategory_id = isset( $_POST['subcategory_id'] ) ? absint( $_POST['subcategory_id'] ) : 0;
		$manual_text = isset( $_POST['manual_text'] ) ? $_POST['manual_text'] : '';

		// Auto-create subcategory if name provided
		if ( ! empty( $_POST['subcategory_name'] ) ) {
			global $wpdb;
			$cat_table = $wpdb->prefix . 'gep_categories';
			$sub_name = sanitize_text_field( $_POST['subcategory_name'] );
			
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $cat_table WHERE name = %s AND parent_id = %d LIMIT 1", $sub_name, $category_id ) );
			if ( $existing ) {
				$subcategory_id = $existing;
			} else {
				$slug = sanitize_title( $sub_name );
				$wpdb->insert( $cat_table, array(
					'name'      => $sub_name,
					'slug'      => $slug,
					'parent_id' => $category_id
				) );
				$subcategory_id = $wpdb->insert_id;
			}
		}

		$import_logic = new GEP_Import();
		$merged_result = array( 'inserted' => 0, 'skipped' => 0, 'errors' => array() );
		$has_input = false;

		// 1. Handle File Upload
		if ( ! empty( $_FILES['import_file']['tmp_name'] ) ) {
			$has_input = true;

			// Validate file upload error code first
			if ( $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
				$upload_errors = array(
					UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload_max_filesize limit.',
					UPLOAD_ERR_FORM_SIZE  => 'File exceeds the MAX_FILE_SIZE form directive.',
					UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
					UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
					UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
					UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
					UPLOAD_ERR_EXTENSION  => 'Upload was blocked by a server extension.',
				);
				$err_msg = isset( $upload_errors[ $_FILES['import_file']['error'] ] )
					? $upload_errors[ $_FILES['import_file']['error'] ]
					: 'Unknown upload error (code: ' . $_FILES['import_file']['error'] . ')';
				$merged_result['errors'][] = 'Upload Error: ' . $err_msg;
			} else {
				$res = $import_logic->import_questions( $_FILES['import_file']['tmp_name'], $category_id, $subcategory_id, array(), $_FILES['import_file']['name'] );
				if ( is_wp_error( $res ) ) {
					// Graceful: store error in result instead of wp_die()
					$merged_result['errors'][] = $res->get_error_message();
				} else {
					$merged_result['inserted'] += (int) $res['inserted'];
					$merged_result['skipped']  += (int) $res['skipped'];
					$merged_result['errors']    = array_merge( $merged_result['errors'], (array) $res['errors'] );
				}
			}
		}

		// 2. Handle Manual Text (PDF/Copy-Paste)
		if ( ! empty( trim( $manual_text ) ) ) {
			$has_input = true;
			$res = $import_logic->process_manual_text( $manual_text, $category_id, $subcategory_id );
			if ( is_wp_error( $res ) ) {
				// Graceful: store error in result instead of wp_die()
				$merged_result['errors'][] = $res->get_error_message();
			} else {
				$merged_result['inserted'] += (int) $res['inserted'];
				$merged_result['skipped']  += (int) $res['skipped'];
				$merged_result['errors']    = array_merge( $merged_result['errors'], (array) $res['errors'] );
			}
		}

		if ( ! $has_input ) {
			$merged_result['errors'][] = 'No file uploaded and no text entered. Please provide questions via file or copy-paste.';
		}

		set_transient( 'gep_import_last_result', $merged_result, 60 );

		if ( ob_get_level() > 0 ) ob_end_clean();
		wp_cache_flush();
		wp_redirect( admin_url( 'admin.php?page=gep-questions&message=imported' ) );
		exit;
	}


	public function handle_save_question() {
		global $wpdb;
		$table    = $wpdb->prefix . 'gep_questions';
		$log_file = function_exists('gep_log_file') ? gep_log_file() : '';

		// ─── DEBUG: Confirm handle_save_question was entered ─────────────────
		$raw_title_debug = isset( $_POST['question_title'] ) ? substr( wp_unslash( $_POST['question_title'] ), 0, 80 ) : '[NOT SET]';
		gep_log_to( $log_file, sprintf(
			"[%s] handle_save_question ENTERED | title_raw='%s' | POST_count=%d\n",
			date( 'Y-m-d H:i:s' ), $raw_title_debug, count( $_POST )
		), FILE_APPEND | LOCK_EX );
		// ─────────────────────────────────────────────────────────────────────

		// Handle Passage Auto-Creation/Update for Manual Authoring
		$passage_text = isset( $_POST['passage_text'] ) ? wp_kses_post( wp_unslash( $_POST['passage_text'] ) ) : '';
		$passage_text_hi = isset( $_POST['passage_text_hi'] ) ? wp_kses_post( wp_unslash( $_POST['passage_text_hi'] ) ) : '';
		$passage_id = isset( $_POST['passage_id'] ) ? absint( $_POST['passage_id'] ) : 0;
		$category_id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;

		$question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
		$is_parent_passage = false;
		if ( $question_id ) {
			$existing_q = $wpdb->get_row( $wpdb->prepare( "SELECT passage_id, question_type FROM $table WHERE id = %d", $question_id ) );
			if ( $existing_q ) {
				if ( $existing_q->passage_id > 0 ) {
					$passage_id = $existing_q->passage_id;
				} elseif ( $existing_q->question_type === 'passage' ) {
					$passage_id = $question_id;
					$is_parent_passage = true;
				}
			}
		}

		if ( ! empty( $passage_text ) ) {
			$passage_translated_data = array();
			if ( ! empty( $passage_text_hi ) ) {
				$passage_translated_data = array( 'title' => $passage_text_hi );
			}

			if ( $passage_id > 0 ) {
				// Update existing parent passage content
				$wpdb->update(
					$table,
					array(
						'title'               => $passage_text,
						'translation_enabled' => ! empty( $passage_text_hi ) ? 1 : 0,
						'translated_data'     => wp_slash( wp_json_encode( $passage_translated_data, JSON_UNESCAPED_UNICODE ) )
					),
					array( 'id' => $passage_id )
				);
				if ( $is_parent_passage ) {
					if ( ob_get_level() > 0 ) ob_end_clean();
					wp_cache_flush();
					wp_redirect( admin_url( 'admin.php?page=gep-questions&message=saved' ) );
					exit;
				}
			} else {
				// Search for existing passage to reuse using normalized matching
				$passages = $wpdb->get_results( $wpdb->prepare(
					"SELECT id, title FROM $table WHERE question_type = 'passage' AND category_id = %d",
					$category_id
				) );
				$existing_passage_id = 0;
				if ( ! empty( $passages ) ) {
					$get_normalized = function( $text ) {
						$text = strip_tags( $text );
						$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
						$text = preg_replace( '/\s+/u', '', $text );
						return mb_strtolower( $text, 'UTF-8' );
					};
					$target_norm = $get_normalized( $passage_text );
					foreach ( $passages as $p ) {
						if ( $get_normalized( $p->title ) === $target_norm ) {
							$existing_passage_id = (int) $p->id;
							break;
						}
					}
				}

				if ( $existing_passage_id ) {
					$passage_id = $existing_passage_id;
					if ( ! empty( $passage_text_hi ) ) {
						$wpdb->update(
							$table,
							array(
								'translation_enabled' => 1,
								'translated_data'     => wp_slash( wp_json_encode( $passage_translated_data, JSON_UNESCAPED_UNICODE ) )
							),
							array( 'id' => $passage_id )
						);
					}
				} else {
					// Create new passage
					$wpdb->insert(
						$table,
						array(
							'title'               => $passage_text,
							'question_type'       => 'passage',
							'category_id'         => $category_id,
							'correct_answer'      => '',
							'translation_enabled' => ! empty( $passage_text_hi ) ? 1 : 0,
							'translated_data'     => wp_slash( wp_json_encode( $passage_translated_data, JSON_UNESCAPED_UNICODE ) ),
							'status'              => 'publish'
						)
					);
					$passage_id = $wpdb->insert_id;
				}

				// If we are adding a passage and the main question title is empty, redirect immediately.
				$raw_title_check = isset( $_POST['question_title'] ) ? $_POST['question_title'] : ( isset( $_POST['title'] ) ? $_POST['title'] : '' );
				$clean_title = trim( strip_tags( wp_unslash( $raw_title_check ) ) );
				$clean_title = html_entity_decode( $clean_title, ENT_QUOTES, 'UTF-8' );
				$clean_title = trim( str_replace( array( '&nbsp;', "\xc2\xa0" ), '', $clean_title ) );
				if ( empty( $clean_title ) ) {
					if ( ob_get_level() > 0 ) ob_end_clean();
					wp_cache_flush();
					wp_redirect( admin_url( 'admin.php?page=gep-questions&message=saved' ) );
					exit;
				}
			}
			$_POST['passage_id'] = $passage_id;
		}

		// Overwrite question_type 'passage' to 'mcq' for the sub-question itself
		if ( isset( $_POST['question_type'] ) && $_POST['question_type'] === 'passage' ) {
			$_POST['question_type'] = 'mcq';
		}

		$question_hi = isset($_POST['question_hi']) ? wp_kses_post( wp_unslash( $_POST['question_hi'] ) ) : '';
		$option_a_hi = isset($_POST['option_a_hi']) ? wp_kses_post( wp_unslash( $_POST['option_a_hi'] ) ) : '';
		$option_b_hi = isset($_POST['option_b_hi']) ? wp_kses_post( wp_unslash( $_POST['option_b_hi'] ) ) : '';
		$option_c_hi = isset($_POST['option_c_hi']) ? wp_kses_post( wp_unslash( $_POST['option_c_hi'] ) ) : '';
		$option_d_hi = isset($_POST['option_d_hi']) ? wp_kses_post( wp_unslash( $_POST['option_d_hi'] ) ) : '';
		$option_e_hi = isset($_POST['option_e_hi']) ? wp_kses_post( wp_unslash( $_POST['option_e_hi'] ) ) : '';
		$explanation_hi = isset($_POST['explanation_hi']) ? wp_kses_post( wp_unslash( $_POST['explanation_hi'] ) ) : '';

		$has_translation_content = ( ! empty( $question_hi ) || ! empty( $option_a_hi ) || ! empty( $option_b_hi ) || ! empty( $option_c_hi ) || ! empty( $option_d_hi ) || ! empty( $option_e_hi ) || ! empty( $explanation_hi ) );
		$translation_enabled = ! empty( $_POST['translation_enabled'] ) ? 1 : 0;
		
		$translated_data = array();
		if ( $translation_enabled || $has_translation_content ) {
			$translated_data = array(
				'title'       => $question_hi,
				'option_a'    => $option_a_hi,
				'option_b'    => $option_b_hi,
				'option_c'    => $option_c_hi,
				'option_d'    => $option_d_hi,
				'option_e'    => $option_e_hi,
				'explanation' => $explanation_hi
			);
		}

		$raw_title = isset( $_POST['question_title'] ) ? $_POST['question_title'] : ( isset( $_POST['title'] ) ? $_POST['title'] : '' );
		$final_title = wp_kses_post( wp_unslash( $raw_title ) );
		if ( empty( trim( strip_tags( $final_title ) ) ) && empty( $passage_text ) ) {
			$final_title = 'Untitled Question';
		}

		$data = array(
			'title'               => $final_title,
			'question_type'       => isset( $_POST['question_type'] ) ? sanitize_text_field( $_POST['question_type'] ) : 'mcq',
			'option_a'            => isset( $_POST['option_a'] ) ? wp_kses_post( wp_unslash( $_POST['option_a'] ) ) : '',
			'option_b'            => isset( $_POST['option_b'] ) ? wp_kses_post( wp_unslash( $_POST['option_b'] ) ) : '',
			'option_c'            => isset( $_POST['option_c'] ) ? wp_kses_post( wp_unslash( $_POST['option_c'] ) ) : '',
			'option_d'            => isset( $_POST['option_d'] ) ? wp_kses_post( wp_unslash( $_POST['option_d'] ) ) : '',
			'option_e'            => isset( $_POST['option_e'] ) ? wp_kses_post( wp_unslash( $_POST['option_e'] ) ) : '',
			'correct_answer'      => isset( $_POST['correct_answer'] ) ? wp_kses_post( wp_unslash( $_POST['correct_answer'] ) ) : '',
			'numerical_tolerance' => isset( $_POST['numerical_tolerance'] ) ? floatval( $_POST['numerical_tolerance'] ) : 0.01,
			'explanation'         => isset( $_POST['question_explanation'] ) ? wp_kses_post( wp_unslash( $_POST['question_explanation'] ) ) : ( isset( $_POST['explanation'] ) ? wp_kses_post( wp_unslash( $_POST['explanation'] ) ) : '' ),
			'marks'               => isset( $_POST['marks'] ) ? floatval( $_POST['marks'] ) : 1.0,
			'negative_marks'      => isset( $_POST['negative_marks'] ) ? floatval( $_POST['negative_marks'] ) : 0.0,
			'category_id'         => isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0,
			'subcategory_id'      => 0, // Will be set below
			'passage_id'          => isset( $_POST['passage_id'] ) ? absint( $_POST['passage_id'] ) : 0,
			'difficulty'          => isset( $_POST['difficulty'] ) ? sanitize_text_field( $_POST['difficulty'] ) : '',
			'tags'                => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
			'pyqs'                => isset( $_POST['pyqs'] ) ? sanitize_text_field( wp_unslash( $_POST['pyqs'] ) ) : '',
			'year'                => isset( $_POST['year'] ) ? sanitize_text_field( wp_unslash( $_POST['year'] ) ) : '',
			'source'              => isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '',
			'translation_enabled' => $translation_enabled,
			'translated_data'     => wp_slash( wp_json_encode( $translated_data, JSON_UNESCAPED_UNICODE ) ),
			'status'              => 'publish'
		);

		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$cat_table = $wpdb->prefix . 'gep_categories';

		// Handle Category & Subcategory / Subject & Topic
		$data['subcategory_id'] = 0;
		$sub_id_post = isset( $_POST['subcategory_id'] ) ? $_POST['subcategory_id'] : '';
		
		if ( is_numeric( $sub_id_post ) && absint( $sub_id_post ) > 0 ) {
			$data['subcategory_id'] = absint( $sub_id_post );
		}
		
		if ( ( $sub_id_post === 'new' || empty($data['subcategory_id']) ) && ! empty( $_POST['subcategory_name'] ) ) {
			$sub_name = sanitize_text_field( $_POST['subcategory_name'] );
			$parent_id = $data['category_id'];
			
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $cat_table WHERE name = %s AND parent_id = %d LIMIT 1", $sub_name, $parent_id ) );
			if ( $existing ) {
				$data['subcategory_id'] = $existing;
			} else {
				$slug = sanitize_title( $sub_name );
				$wpdb->insert( $cat_table, array(
					'name'      => $sub_name,
					'slug'      => $slug,
					'parent_id' => $parent_id
				) );
				$data['subcategory_id'] = $wpdb->insert_id;
			}
		}

		$question_id = 0;
		if ( ! empty( $_POST['question_id'] ) ) {

			// ── UPDATE existing question ──────────────────────────────────────
			$question_id = absint( $_POST['question_id'] );
			$res         = $wpdb->update( $table, $data, array( 'id' => $question_id ) );
			$db_err      = $wpdb->last_error;

			// If update failed with a schema error, self-heal and retry once
			if ( $res === false && ( strpos( $db_err, 'Unknown column' ) !== false || strpos( $db_err, 'Field' ) !== false ) ) {
				require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
				GEP_Activator::activate();
				$res    = $wpdb->update( $table, $data, array( 'id' => $question_id ) );
				$db_err = $wpdb->last_error;
			}

			gep_log_to( $log_file, sprintf(
				"[%s] DB UPDATE | question_id=%d | res=%s | last_error='%s'\n",
				date( 'Y-m-d H:i:s' ), $question_id, var_export( $res, true ), $db_err
			), FILE_APPEND | LOCK_EX );

			if ( $res === false ) {
				error_log( 'GoPath Exam Portal Admin Question Update Failed: ' . $db_err );
				wp_die(
					'<strong>Question Update Failed.</strong><br>Database error: <code>' . esc_html( $db_err ) . '</code><br><a href="' . esc_url( admin_url( 'admin.php?page=gep-questions&action=edit&id=' . $question_id ) ) . '">← Go back</a>',
					'Database Error', array( 'response' => 500 )
				);
			}

		} else {

			// ── INSERT new question ───────────────────────────────────────────
			$data_keys = implode( ', ', array_keys( $data ) );
			$res       = $wpdb->insert( $table, $data );
			$db_err    = $wpdb->last_error;

			// If insert failed with a schema error, self-heal and retry once
			if ( $res === false && ( strpos( $db_err, 'Unknown column' ) !== false || strpos( $db_err, 'Field' ) !== false ) ) {
				require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
				GEP_Activator::activate();
				$res    = $wpdb->insert( $table, $data );
				$db_err = $wpdb->last_error;
			}

			$question_id = $wpdb->insert_id;

			gep_log_to( $log_file, sprintf(
				"[%s] DB INSERT | res=%s | insert_id=%d | last_error='%s' | data_keys=[%s]\n",
				date( 'Y-m-d H:i:s' ), var_export( $res, true ), $question_id, $db_err, $data_keys
			), FILE_APPEND | LOCK_EX );

			if ( $res === false || $question_id === 0 ) {
				error_log( 'GoPath Exam Portal Admin Question Insert Failed: ' . $db_err );
				wp_die(
					'<strong>Question Save Failed.</strong><br>Database error: <code>' . esc_html( $db_err ) . '</code><br><a href="' . esc_url( admin_url( 'admin.php?page=gep-questions&action=add' ) ) . '">← Go back</a>',
					'Database Error', array( 'response' => 500 )
				);
			}
		}

		// Handle Linking to Tests
		$test_questions_table = $wpdb->prefix . 'gep_test_questions';
		$linked_test_ids = isset( $_POST['linked_test_ids'] ) && is_array( $_POST['linked_test_ids'] ) ? array_map( 'absint', $_POST['linked_test_ids'] ) : array();

		// Get currently linked tests
		$current_linked_tests = $wpdb->get_col( $wpdb->prepare( "SELECT test_id FROM $test_questions_table WHERE question_id = %d", $question_id ) );

		// Delete associations no longer present
		if ( ! empty( $current_linked_tests ) ) {
			foreach ( $current_linked_tests as $ct_id ) {
				if ( ! in_array( $ct_id, $linked_test_ids ) ) {
					$wpdb->delete( $test_questions_table, array( 'test_id' => $ct_id, 'question_id' => $question_id ) );
				}
			}
		}

		// Insert new associations
		foreach ( $linked_test_ids as $lt_id ) {
			if ( ! in_array( $lt_id, $current_linked_tests ) ) {
				$next_order = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(order_no) FROM $test_questions_table WHERE test_id = %d", $lt_id ) );
				$next_order = $next_order ? intval( $next_order ) + 1 : 1;
				$wpdb->insert( $test_questions_table, array(
					'test_id'     => $lt_id,
					'question_id' => $question_id,
					'order_no'    => $next_order
				) );
			}
		}

		gep_log_to( $log_file, sprintf(
			"[%s] REDIRECTING | question_id=%d | to=gep-questions&message=saved\n",
			date( 'Y-m-d H:i:s' ), $question_id
		), FILE_APPEND | LOCK_EX );

		if ( ob_get_level() > 0 ) ob_end_clean();
		wp_cache_flush();
		wp_redirect( admin_url( 'admin.php?page=gep-questions&message=saved' ) );
		exit;
	}
	
	public function list_questions( $filters = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		
		$where = array();
		$params = array();

		if ( ! empty( $filters['q_id'] ) ) {
			$id_parts = array_filter( array_map( 'absint', explode( ',', $filters['q_id'] ) ) );
			if ( ! empty( $id_parts ) ) {
				$where[] = "id IN (" . implode( ',', $id_parts ) . ")";
			}
		}

		if ( isset( $filters['category_id'] ) && $filters['category_id'] !== 'all' && $filters['category_id'] !== '' ) {
			$where[] = "category_id = %d";
			$params[] = absint( $filters['category_id'] );
		}

		if ( ! empty( $filters['subcategory_id'] ) ) {
			$where[] = "subcategory_id = %d";
			$params[] = absint( $filters['subcategory_id'] );
		}

		if ( ! empty( $filters['question_type'] ) ) {
			$where[]  = 'question_type = %s';
			$params[] = sanitize_text_field( $filters['question_type'] );
		} else {
			$where[]  = "question_type != 'passage'";
		}

		if ( ! empty( $filters['difficulty'] ) ) {
			$where[]  = 'difficulty LIKE %s';
			$params[] = '%' . $wpdb->esc_like( sanitize_text_field( $filters['difficulty'] ) ) . '%';
		}

		if ( ! empty( $filters['pyqs'] ) ) {
			$where[]  = 'pyqs = %s';
			$params[] = sanitize_text_field( $filters['pyqs'] );
		}

		if ( ! empty( $filters['year'] ) ) {
			$where[]  = 'year = %s';
			$params[] = sanitize_text_field( $filters['year'] );
		}

		if ( ! empty( $filters['s'] ) ) {
			$where[]     = '(title LIKE %s OR tags LIKE %s OR explanation LIKE %s OR pyqs LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $filters['s'] ) ) . '%';
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// FIX: Paginated queries - 50 per page default
		$per_page = isset( $filters['per_page'] ) ? absint( $filters['per_page'] ) : 50;
		$paged    = isset( $filters['paged'] )    ? max( 1, absint( $filters['paged'] ) ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$count_q = "SELECT COUNT(*) FROM $table $where_sql";
		$data_q  = "SELECT * FROM $table $where_sql ORDER BY id DESC LIMIT %d OFFSET %d";

		if ( ! empty( $params ) ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_q, $params ) );
			$items = $wpdb->get_results( $wpdb->prepare( $data_q, array_merge( $params, array( $per_page, $offset ) ) ) );
		} else {
			$total = (int) $wpdb->get_var( $count_q );
			$items = $wpdb->get_results( $wpdb->prepare( $data_q, $per_page, $offset ) );
		}

		return array(
			'items'    => $items ?: array(),
			'total'    => $total,
			'per_page' => $per_page,
			'paged'    => $paged,
		);
	}

	public function add_question($data) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$wpdb->insert($table, $data);
		return $wpdb->insert_id;
	}

	public function update_question($id, $data) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$wpdb->update($table, $data, array('id' => $id));
	}

	public function delete_question($id) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		// Decouple linked sub-questions from deleted passage to avoid orphans
		$wpdb->update( $table, array( 'passage_id' => 0 ), array( 'passage_id' => $id ) );
		$wpdb->delete($table, array('id' => $id));
	}
}
