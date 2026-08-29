<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Question bulk import logic.
 */
class GEP_Import {

	private $passage_cache = array();

	public function import_questions( $file_path, $category_id, $subcategory_id = 0, $mapping = array(), $original_filename = '' ) {
		@set_time_limit( 300 );
		@ini_set( 'memory_limit', '256M' );

		$check_path = ! empty( $original_filename ) ? $original_filename : $file_path;
		$extension = pathinfo( $check_path, PATHINFO_EXTENSION );

		if ( 'csv' === strtolower( $extension ) ) {
			return $this->process_csv( $file_path, $category_id, $subcategory_id, $mapping );
		} else if ( in_array( strtolower( $extension ), array( 'xlsx', 'xls' ) ) ) {
			return $this->process_excel( $file_path, $category_id, $subcategory_id, $mapping );
		} else if ( 'pdf' === strtolower( $extension ) ) {
			return $this->process_pdf( $file_path, $category_id, $subcategory_id );
		}

		return new WP_Error( 'invalid_format', 'Unsupported file format. Please use CSV, Excel, or PDF.' );
	}

	private function process_csv( $file_path, $category_id, $subcategory_id, $mapping ) {
		// Set a UTF-8 compatible locale for reliable mult-byte parsing on limited servers
		$old_locale = setlocale( LC_ALL, '0' );
		setlocale( LC_ALL, 'en_US.UTF-8' );

		// Auto-convert non-UTF-8 CSV contents to UTF-8 to prevent Hindi/Sanskrit character corruption
		$file_content = @file_get_contents( $file_path );
		$handle = false;
		if ( $file_content ) {
			$encoding = 'UTF-8';
			if ( function_exists( 'mb_detect_encoding' ) ) {
				$detected = mb_detect_encoding( $file_content, array( 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ASCII', 'Windows-1252', 'ISO-8859-1' ), true );
				if ( $detected && $detected !== 'UTF-8' ) {
					$encoding = $detected;
				}
			}
			if ( $encoding !== 'UTF-8' ) {
				if ( function_exists( 'mb_convert_encoding' ) ) {
					$file_content = mb_convert_encoding( $file_content, 'UTF-8', $encoding );
				} elseif ( function_exists( 'iconv' ) ) {
					$file_content = iconv( $encoding, 'UTF-8//IGNORE', $file_content );
				}
				$temp_handle = fopen( 'php://temp', 'r+' );
				if ( $temp_handle ) {
					fwrite( $temp_handle, $file_content );
					rewind( $temp_handle );
					$handle = $temp_handle;
				}
			}
		}

		if ( ! $handle ) {
			$handle = fopen( $file_path, 'r' );
		}

		if ( ! $handle ) {
			if ( $old_locale ) setlocale( LC_ALL, $old_locale );
			return new WP_Error( 'file_error', 'Could not open file.' );
		}

		// Auto-detect delimiter
		$first_line = fgets( $handle );
		if ( ! $first_line ) {
			fclose( $handle );
			if ( $old_locale ) setlocale( LC_ALL, $old_locale );
			return new WP_Error( 'empty_file', 'File is empty or invalid.' );
		}

		$delimiters = array( ',', ';', "\t" );
		$delimiter = ',';
		$max_count = 0;
		foreach ( $delimiters as $delim ) {
			$count = substr_count( $first_line, $delim );
			if ( $count > $max_count ) {
				$max_count = $count;
				$delimiter = $delim;
			}
		}

		rewind( $handle );
		$headers = fgetcsv( $handle, 0, $delimiter );
		if ( ! $headers ) {
			fclose( $handle );
			if ( $old_locale ) setlocale( LC_ALL, $old_locale );
			return new WP_Error( 'empty_file', 'File is empty or invalid.' );
		}

		// Strip UTF-8 BOM from the first header element if present
		$bom = pack( 'H*', 'EFBBBF' );
		if ( isset( $headers[0] ) && 0 === strpos( $headers[0], $bom ) ) {
			$headers[0] = substr( $headers[0], strlen( $bom ) );
		}

		$results = array( 'inserted' => 0, 'skipped' => 0, 'errors' => array() );
		$question_logic = new GEP_Question();
		$cat_logic = new GEP_Category();
		$this->passage_cache = array(); // Clear cache for new import session

		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== FALSE ) {
			$data = $this->map_row_to_data( $row, $headers, $mapping );
			if ( ! $data ) {
				$results['skipped']++;
				continue;
			}

			$this->process_row_data( $data, $results, $question_logic, $cat_logic, $category_id, $subcategory_id );
		}

		fclose( $handle );
		if ( $old_locale ) {
			setlocale( LC_ALL, $old_locale );
		}
		return $results;
	}

	private function process_excel( $file_path, $category_id, $subcategory_id, $mapping ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'env_error', 'ZipArchive PHP extension required for Excel import.' );
		}
		
		require_once GEP_PLUGIN_DIR . 'includes/class-gep-xlsx-parser.php';
		$rows = GEP_XLSX_Parser::parse( $file_path );

		if ( ! $rows || empty( $rows ) ) {
			return new WP_Error( 'file_error', 'Could not read Excel file. Please ensure it is a valid .xlsx file.' );
		}

		$headers = array_shift( $rows );
		if ( empty( $headers ) ) {
			return new WP_Error( 'empty_file', 'File is empty or invalid.' );
		}

		$results = array( 'inserted' => 0, 'skipped' => 0, 'errors' => array() );
		$question_logic = new GEP_Question();
		$cat_logic = new GEP_Category();
		$this->passage_cache = array(); // Clear cache for new import session

		foreach ( $rows as $row ) {
			// Skip entirely empty rows
			if ( empty( array_filter( $row ) ) ) continue;

			$data = $this->map_row_to_data( $row, $headers, $mapping );
			if ( ! $data ) {
				$results['skipped']++;
				continue;
			}

			$this->process_row_data( $data, $results, $question_logic, $cat_logic, $category_id, $subcategory_id );
		}

		return $results;
	}

	private function process_row_data( $data, &$results, $question_logic, $cat_logic, $category_id, $subcategory_id ) {
		// Dynamic Subject/Topic Support
		$row_cat_id = $category_id;
		$row_sub_id = $subcategory_id;

		if ( ! empty( $data['subject'] ) ) {
			$row_cat_id = $cat_logic->get_or_create_category( $data['subject'], 0 );
		}
		if ( ! empty( $data['topic'] ) ) {
			$row_sub_id = $cat_logic->get_or_create_category( $data['topic'], $row_cat_id );
		}

		$data['category_id']    = $row_cat_id;
		$data['subcategory_id'] = $row_sub_id;
		$data['status']         = 'publish';

		if ( empty( $data['marks'] ) )          { $data['marks']          = 1.0; }
		if ( empty( $data['negative_marks'] ) ) { $data['negative_marks'] = 0.0; }
		$data['pyqs'] = isset( $data['pyqs'] ) ? sanitize_text_field( $data['pyqs'] ) : '';
		$data['year'] = isset( $data['year'] ) ? sanitize_text_field( $data['year'] ) : '';
		$data['tags'] = isset( $data['tags'] ) ? sanitize_text_field( $data['tags'] ) : '';

		// Persist question_type (all supported types)
		$valid_types = array( 'mcq', 'short_answer', 'true_false', 'multi_select', 'msq', 'numerical', 'assertion_reason', 'matching', 'passage' );
		$data['question_type'] = ( ! empty( $data['question_type'] ) && in_array( strtolower( trim( $data['question_type'] ) ), $valid_types ) )
			? strtolower( trim( $data['question_type'] ) )
			: 'mcq'; // default

		// Set numerical_tolerance if present
		if ( ! isset( $data['numerical_tolerance'] ) || $data['numerical_tolerance'] === '' ) {
			$data['numerical_tolerance'] = 0.01;
		} else {
			$data['numerical_tolerance'] = floatval( $data['numerical_tolerance'] );
		}

		// Handle Passage Auto-Linking and Creation
		$passage_text = isset( $data['passage'] ) ? trim( $data['passage'] ) : '';
		$passage_hi   = isset( $data['passage_hi'] ) ? trim( $data['passage_hi'] ) : '';

		if ( ! empty( $passage_text ) ) {
			$passage_key = $this->get_normalized_hash( $passage_text );
			if ( isset( $this->passage_cache[ $passage_key ] ) ) {
				$data['passage_id'] = $this->passage_cache[ $passage_key ];
			} else {
				global $wpdb;
				$table = $wpdb->prefix . 'gep_questions';
				
				// Look up if a passage with this text already exists in the database for this subject using normalized hashing
				$existing_passage_id = 0;
				$passages = $wpdb->get_results( $wpdb->prepare(
					"SELECT id, title FROM $table WHERE question_type = 'passage' AND category_id = %d",
					$row_cat_id
				) );
				if ( ! empty( $passages ) ) {
					$target_hash = $passage_key;
					foreach ( $passages as $p ) {
						if ( $this->get_normalized_hash( $p->title ) === $target_hash ) {
							$existing_passage_id = (int) $p->id;
							break;
						}
					}
				}

				if ( $existing_passage_id ) {
					$this->passage_cache[ $passage_key ] = $existing_passage_id;
					$data['passage_id'] = $existing_passage_id;
					if ( ! empty( $passage_hi ) ) {
						$passage_translated_data = array(
							'title'       => wp_kses_post( $passage_hi ),
							'option_a'    => '',
							'option_b'    => '',
							'option_c'    => '',
							'option_d'    => '',
							'explanation' => '',
						);
						$wpdb->update( $table, array(
							'translation_enabled' => 1,
							'translated_data'     => wp_slash( wp_json_encode( $passage_translated_data, JSON_UNESCAPED_UNICODE ) )
						), array( 'id' => $existing_passage_id ) );
					}
				} else {
					// Create new passage
					$passage_translation_enabled = ! empty( $passage_hi ) ? 1 : 0;
					$passage_translated_data = array();
					if ( $passage_translation_enabled ) {
						$passage_translated_data = array(
							'title'       => wp_kses_post( $passage_hi ),
							'option_a'    => '',
							'option_b'    => '',
							'option_c'    => '',
							'option_d'    => '',
							'explanation' => '',
						);
					}

					$passage_data = array(
						'title'               => $this->preprocess_content( $passage_text ),
						'question_type'       => 'passage',
						'correct_answer'      => '',
						'option_a'            => '',
						'option_b'            => '',
						'option_c'            => '',
						'option_d'            => '',
						'explanation'         => '',
						'marks'               => 0.0,
						'negative_marks'      => 0.0,
						'category_id'         => $row_cat_id,
						'subcategory_id'      => $row_sub_id,
						'translation_enabled' => $passage_translation_enabled,
						'translated_data'     => wp_slash( wp_json_encode( $passage_translated_data, JSON_UNESCAPED_UNICODE ) ),
						'status'              => 'publish',
					);

					$new_passage_id = $question_logic->save_question( $passage_data );
					if ( $new_passage_id ) {
						$this->passage_cache[ $passage_key ] = $new_passage_id;
						$data['passage_id'] = $new_passage_id;
					} else {
						$data['passage_id'] = 0;
					}
				}
			}
		}

		// If this row itself is explicitly a passage row
		if ( $data['question_type'] === 'passage' ) {
			// Cache this passage in case subsequent rows link to it by title
			$passage_key = $this->get_normalized_hash( $data['title'] );
			// Check translation content
			$hi_title = isset( $data['question_hi'] ) ? trim( $data['question_hi'] ) : '';
			$has_translation_content = ! empty( $hi_title );
			$translation_enabled = ( $has_translation_content || ( ! empty( $data['translation_enabled'] ) && $data['translation_enabled'] != '0' ) ) ? 1 : 0;
			$translated_data = array();
			if ( $translation_enabled || $has_translation_content ) {
				$translated_data = array(
					'title'       => wp_kses_post( $hi_title ),
					'option_a'    => '',
					'option_b'    => '',
					'option_c'    => '',
					'option_d'    => '',
					'explanation' => '',
				);
			}
			$data['translation_enabled'] = $translation_enabled;
			$data['translated_data']     = wp_slash( wp_json_encode( $translated_data, JSON_UNESCAPED_UNICODE ) );

			// Remove temp Hi fields and other irrelevant columns
			unset( $data['question_hi'], $data['option_a_hi'], $data['option_b_hi'],
				   $data['option_c_hi'], $data['option_d_hi'], $data['explanation_hi'],
				   $data['passage'], $data['passage_hi'], $data['subject'], $data['topic'] );

			$data['title']       = $this->preprocess_content( $data['title'] );
			$data['explanation'] = '';
			$data['option_a']    = '';
			$data['option_b']    = '';
			$data['option_c']    = '';
			$data['option_d']    = '';
			$data['correct_answer'] = '';

			$validation = $this->validate_question_data( $data );
			if ( is_wp_error( $validation ) ) {
				$results['errors'][] = $validation->get_error_message();
				$results['skipped']++;
				return;
			}

			if ( $this->is_duplicate( $data['title'], $row_cat_id ) ) {
				// Find existing ID to still cache it
				global $wpdb;
				$table = $wpdb->prefix . 'gep_questions';
				$existing_id = $wpdb->get_var( $wpdb->prepare(
					"SELECT id FROM $table WHERE question_type = 'passage' AND title = %s AND category_id = %d LIMIT 1",
					$data['title'],
					$row_cat_id
				) );
				if ( $existing_id ) {
					$this->passage_cache[ $passage_key ] = $existing_id;
					if ( $translation_enabled && ! empty( $data['translated_data'] ) ) {
						$wpdb->update( $table, array(
							'translation_enabled' => 1,
							'translated_data'     => $data['translated_data']
						), array( 'id' => $existing_id ) );
					}
				}
				$results['skipped']++;
				return;
			}

			$res = $question_logic->save_question( $data );
			if ( $res ) {
				$this->passage_cache[ $passage_key ] = $res;
				$results['inserted']++;
			} else {
				$results['skipped']++;
			}
			return;
		}

		// Normal Question (MCQ, MSQ, Numerical, etc.)
		$hi_title   = isset( $data['question_hi'] )   ? trim( $data['question_hi'] )   : '';
		$hi_opt_a   = isset( $data['option_a_hi'] )   ? trim( $data['option_a_hi'] )   : '';
		$hi_opt_b   = isset( $data['option_b_hi'] )   ? trim( $data['option_b_hi'] )   : '';
		$hi_opt_c   = isset( $data['option_c_hi'] )   ? trim( $data['option_c_hi'] )   : '';
		$hi_opt_d   = isset( $data['option_d_hi'] )   ? trim( $data['option_d_hi'] )   : '';
		$hi_opt_e   = isset( $data['option_e_hi'] )   ? trim( $data['option_e_hi'] )   : '';
		$hi_expl    = isset( $data['explanation_hi'] ) ? trim( $data['explanation_hi'] ) : '';

		$has_translation_content = ( ! empty( $hi_title ) || ! empty( $hi_opt_a ) || ! empty( $hi_opt_b ) || ! empty( $hi_opt_c ) || ! empty( $hi_opt_d ) || ! empty( $hi_opt_e ) || ! empty( $hi_expl ) );
		$translation_enabled = ( $has_translation_content || ( ! empty( $data['translation_enabled'] ) && $data['translation_enabled'] != '0' ) ) ? 1 : 0;

		$translated_data = array();
		if ( $translation_enabled || $has_translation_content ) {
			$translated_data = array(
				'title'       => wp_kses_post( $hi_title ),
				'option_a'    => wp_kses_post( $hi_opt_a ),
				'option_b'    => wp_kses_post( $hi_opt_b ),
				'option_c'    => wp_kses_post( $hi_opt_c ),
				'option_d'    => wp_kses_post( $hi_opt_d ),
				'option_e'    => wp_kses_post( $hi_opt_e ),
				// BUG FIX: Hindi/translated explanation was missing the nl2br() line-break
				// conversion that the English explanation already gets via preprocess_content(),
				// so plain newlines from imported content silently collapsed on render.
				'explanation' => $this->preprocess_content( $hi_expl ),
			);
		}
		$data['translation_enabled'] = $translation_enabled;
		$data['translated_data']     = wp_slash( wp_json_encode( $translated_data, JSON_UNESCAPED_UNICODE ) );

		// Remove temp and mapping fields
		unset( $data['question_hi'], $data['option_a_hi'], $data['option_b_hi'],
			   $data['option_c_hi'], $data['option_d_hi'], $data['option_e_hi'], $data['explanation_hi'],
			   $data['passage'], $data['passage_hi'], $data['subject'], $data['topic'] );

		// PRESERVE FORMATTING
		$data['explanation'] = $this->preprocess_content( $data['explanation'] );
		$data['title']       = $this->preprocess_content( $data['title'] );

		$validation = $this->validate_question_data( $data );
		if ( is_wp_error( $validation ) ) {
			$results['errors'][] = $validation->get_error_message();
			$results['skipped']++;
			return;
		}

		$existing_id = $this->is_duplicate( $data['title'], $row_cat_id );
		if ( $existing_id ) {
			global $wpdb;
			$table = $wpdb->prefix . 'gep_questions';
			
			$update_data = array(
				'option_a'            => $data['option_a'],
				'option_b'            => $data['option_b'],
				'option_c'            => $data['option_c'],
				'option_d'            => $data['option_d'],
				'option_e'            => $data['option_e'],
				'correct_answer'      => $data['correct_answer'],
				'explanation'         => $data['explanation'],
				'marks'               => $data['marks'],
				'negative_marks'      => $data['negative_marks'],
				'pyqs'                => $data['pyqs'],
				'year'                => $data['year'],
				'tags'                => $data['tags'],
				'source'              => isset($data['source']) ? $data['source'] : '',
				'question_type'       => $data['question_type'],
				'numerical_tolerance' => $data['numerical_tolerance'],
				'translation_enabled' => $data['translation_enabled'],
				'translated_data'     => $data['translated_data']
			);
			
			if ( isset( $data['passage_id'] ) && $data['passage_id'] > 0 ) {
				$update_data['passage_id'] = $data['passage_id'];
			}
			
			$wpdb->update( $table, $update_data, array( 'id' => $existing_id ) );
			$results['inserted']++;
			return;
		}

		$res = $question_logic->save_question( $data );
		if ( $res ) {
			$results['inserted']++;
		} else {
			$results['skipped']++;
		}
	}

	private function process_pdf( $file_path, $category_id, $subcategory_id ) {
		// High-Fidelity PDF Handling:
		// Direct PDF parsing is computationally intensive. We redirect the user to the 
		// "Manual Ingestion" tool which is optimized for PDF text extraction (Copy-Paste).
		return new WP_Error( 'pdf_ingestion', 'Intelligence Alert: PDF files require text extraction. Please copy the text from your PDF and use the "Manual Ingest" tab for 100% accuracy.' );
	}

	private function preprocess_content( $content ) {
		// Preserving rich formatting: line breaks, bold, tables
		if ( empty( $content ) ) return '';
		
		$content = wp_kses_post( $content ); // Allowed tags: b, i, em, strong, table, tr, td, br
		$content = nl2br( $content ); // Convert newlines to BRs
		return $content;
	}

	private function map_row_to_data( $row, $headers, $mapping ) {
		$data = array();
		// FIX: Added question_type, tags, passage/passage_hi, and full Hindi/bilingual translation column support
		$fields = array(
			'title', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e',
			'correct_answer', 'explanation', 'marks', 'negative_marks',
			'subject', 'topic', 'pyqs', 'year', 'tags', 'source',
			'question_type', 'numerical_tolerance',
			'question_hi', 'option_a_hi', 'option_b_hi', 'option_c_hi', 'option_d_hi', 'option_e_hi',
			'explanation_hi', 'translation_enabled',
			'passage', 'passage_hi',
		);

		// Normalize headers to lowercase and strip all non-alphanumeric and special characters, preserving letters/numbers/marks (including Unicode/Devanagari)
		$normalized_headers = array_map( function( $header ) {
			return preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( trim( (string) $header ) ) );
		}, $headers );

		foreach ( $fields as $field ) {
			$norm_field = preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( $field ) );
			$index = array_search( $norm_field, $normalized_headers );

			// Support common alternate header names if standard name not found
			if ( $index === false ) {
				$alternates = array();
				if ( $norm_field === 'title' ) {
					$alternates = array( 'question', 'questiontext', 'qustion', 'प्रशन', 'प्रश्न', 'प्रश्नशीर्षक' );
				} elseif ( $norm_field === 'optiona' ) {
					$alternates = array( 'option1', 'choice1', 'a', 'विकल्पक', 'क', 'विकल्पअ', 'विकल्प1', 'विकल्पइ', 'विकल्प१' );
				} elseif ( $norm_field === 'optionb' ) {
					$alternates = array( 'option2', 'choice2', 'b', 'विकल्पख', 'ख', 'विकल्पब', 'विकल्प2', 'विकल्पउ', 'विकल्प२' );
				} elseif ( $norm_field === 'optionc' ) {
					$alternates = array( 'option3', 'choice3', 'c', 'विकल्पग', 'ग', 'विकल्पस', 'विकल्प3', 'विकल्पए', 'विकल्प३' );
				} elseif ( $norm_field === 'optiond' ) {
					$alternates = array( 'option4', 'choice4', 'd', 'विकल्पघ', 'घ', 'विकल्पद', 'विकल्प4', 'विकल्पओ', 'विकल्प४' );
				} elseif ( $norm_field === 'optione' ) {
					$alternates = array( 'option5', 'choice5', 'e', 'विकल्पङ', 'ङ', 'विकल्पइ', 'विकल्प5', 'विकल्प५' );
				} elseif ( $norm_field === 'correctanswer' ) {
					$alternates = array( 'answer', 'correctoption', 'key', 'ans', 'उत्तर', 'सहीउत्तर', 'सहीविकल्प' );
				} elseif ( $norm_field === 'explanation' ) {
					$alternates = array( 'explation', 'solution', 'reason', 'hint', 'व्याख्या', 'स्पष्टीकरण', 'हल' );
				} elseif ( $norm_field === 'subject' ) {
					$alternates = array( 'category', 'subjectname', 'विषय' );
				} elseif ( $norm_field === 'topic' ) {
					$alternates = array( 'subcategory', 'chapter', 'अध्याय', 'शीर्षक' );
				} elseif ( $norm_field === 'pyqs' ) {
					$alternates = array( 'pyq', 'previousyear', 'गतवर्ष' );
				} elseif ( $norm_field === 'source' ) {
					$alternates = array( 'from', 'book', 'questionsource', 'origin', 'स्रोत', 'कहाँसे' );
				} elseif ( $norm_field === 'questiontype' ) {
					$alternates = array( 'type', 'qtype', 'प्रश्नप्रकार', 'प्रकार' );
				} elseif ( $norm_field === 'numericaltolerance' ) {
					$alternates = array( 'tolerance', 'tolerancevalue', 'acceptedrange' );
				} elseif ( $norm_field === 'questionhi' ) {
					$alternates = array( 'titlehi', 'questionhindi', 'titlehindi', 'prashna', 'प्रश्नहिंदी', 'प्रश्नहिन्दी', 'प्रश्नहिं' );
				} elseif ( $norm_field === 'optionahi' ) {
					$alternates = array( 'optionahindi', 'option1hi', 'option1hindi', 'choice1hi', 'choice1hindi', 'ahi', 'ahindi', 'विकल्पकहिंदी', 'विकल्पकहिन्दी', 'विकल्पअहिंदी', 'विकल्पअहिन्दी' );
				} elseif ( $norm_field === 'optionbhi' ) {
					$alternates = array( 'optionbhindi', 'option2hi', 'option2hindi', 'choice2hi', 'choice2hindi', 'bhi', 'bhindi', 'विकल्पखहिंदी', 'विकल्पखहिन्दी', 'विकल्पबहिंदी', 'विकल्पबहिन्दी' );
				} elseif ( $norm_field === 'optionchi' ) {
					$alternates = array( 'optionchindi', 'option3hi', 'option3hindi', 'choice3hi', 'choice3hindi', 'chi', 'chindi', 'विकल्पगहिंदी', 'विकल्पगहिन्दी', 'विकल्पसहिंदी', 'विकल्पसहिन्दी' );
				} elseif ( $norm_field === 'optiondhi' ) {
					$alternates = array( 'optiondhindi', 'option4hi', 'option4hindi', 'choice4hi', 'choice4hindi', 'dhi', 'dhindi', 'विकल्पघहिंदी', 'विकल्पघहिन्दी', 'विकल्पदहिंदी', 'विकल्पदहिन्दी' );
				} elseif ( $norm_field === 'optionehi' ) {
					$alternates = array( 'optionehindi', 'option5hi', 'option5hindi', 'choice5hi', 'choice5hindi', 'ehi', 'ehindi', 'विकल्पङहिंदी', 'विकल्पङहिन्दी', 'विकल्पइहिंदी', 'विकल्पइहिन्दी' );
				} elseif ( $norm_field === 'translationenabled' ) {
					$alternates = array( 'bilingual', 'hindi', 'translation' );
				} elseif ( $norm_field === 'passage' ) {
					$alternates = array( 'passage', 'passagetext', 'comprehension', 'गद्यांश', 'पैराग्राफ' );
				} elseif ( $norm_field === 'passagehi' ) {
					$alternates = array( 'passagehindi', 'passagehi', 'comprehensionhi', 'comprehensionhindi', 'गद्यांशहिंदी', 'गद्यांशहिन्दी' );
				}

				foreach ( $alternates as $alt ) {
					$norm_alt = preg_replace( '/[^\p{L}\p{N}\p{M}]/u', '', strtolower( $alt ) );
					$index = array_search( $norm_alt, $normalized_headers );
					if ( $index !== false ) {
						break;
					}
				}
			}

			if ( isset( $mapping[$field] ) ) $index = $mapping[$field];

			if ( $index !== false && isset( $row[$index] ) ) {
				$data[$field] = $row[$index];
			} else {
				$data[$field] = '';
			}
		}

		return ! empty( $data['title'] ) ? $data : false;
	}

	private function validate_question_data( $data ) {
		if ( empty( $data['title'] ) ) return new WP_Error( 'val_err', 'Title is required.' );
		// For numerical, short_answer, and passage types, options are not required
		$types_no_options = array( 'numerical', 'short_answer', 'passage' );
		$q_type = isset( $data['question_type'] ) ? $data['question_type'] : 'mcq';
		if ( ! in_array( $q_type, $types_no_options ) ) {
			if ( empty( $data['option_a'] ) || empty( $data['option_b'] ) ) {
				return new WP_Error( 'val_err', 'At least two options (A and B) are required for MCQ/MSQ questions.' );
			}
		}
		return true;
	}

	/**
	 * Process raw text (PDF copy-paste) from the manual ingestion tool.
	 */
	public function process_manual_text( $text, $category_id, $subcategory_id = 0 ) {
		if ( empty( $text ) ) return new WP_Error( 'empty', 'No content provided.' );

		// Normalize line endings
		$text = str_replace( array("\r\n", "\r"), "\n", $text );
		
		// Pattern 1: Look for pipe-separated format (legacy fallback)
		if ( strpos( $text, '|' ) !== false ) {
			return $this->process_pipe_text( $text, $category_id, $subcategory_id );
		}

		// State-machine parser to parse multiple questions cleanly and prevent splitting long questions
		$lines = explode( "\n", $text );
		$blocks = array();
		$current_block = "";
		$seen_answer_in_block = false;

		foreach ( $lines as $line ) {
			$trimmed_line = trim( $line );
			if ( $trimmed_line === '' ) {
				if ( ! empty( $current_block ) ) {
					$current_block .= "\n";
				}
				continue;
			}

			// Check if line starts with an answer indicator
			$is_answer_line = preg_match( '/^(?:Ans|Answer|Correct|Key|CorrectAnswer|उत्तर|उत्तरम)[\:\s\-]+/iu', $trimmed_line );

			// Check if line starts with a new question indicator (e.g. "Question 2.", "Q3.", "प्रश्न 4.", or just "2.")
			// We only trigger a new question if we have already seen an answer in the current block,
			// OR if the current block is empty (start of file).
			$is_new_question_start = false;
			if ( $is_answer_line ) {
				$seen_answer_in_block = true;
			} else {
				// Match Question 1., Q1., 1., प्रश्न 1.
				$is_numbered_start = preg_match( '/^(?:Question|Q|प्रश्न|Prashna|)\s*(\d+|[०-९]+)[\.\)\-\s\x{0964}]+/iu', $trimmed_line );
				if ( $is_numbered_start ) {
					if ( $seen_answer_in_block || empty( $current_block ) ) {
						$is_new_question_start = true;
					}
				}
				
				// Explicit Question prefix words always start a new question
				$has_explicit_prefix = preg_match( '/^(?:Question|Q|प्रश्न|Prashna)\s*(\d+|[०-९]+)/iu', $trimmed_line );
				if ( $has_explicit_prefix ) {
					$is_new_question_start = true;
				}
			}

			if ( $is_new_question_start && ! empty( $current_block ) ) {
				$blocks[] = $current_block;
				$current_block = "";
				$seen_answer_in_block = false;
			}

			$current_block .= $line . "\n";
		}

		if ( ! empty( $current_block ) ) {
			$blocks[] = $current_block;
		}

		$results = array( 'inserted' => 0, 'skipped' => 0, 'errors' => array() );
		$question_logic = new GEP_Question();

		foreach ( $blocks as $block ) {
			$data = $this->parse_smart_block( $block );
			if ( ! $data ) {
				$results['skipped']++;
				continue;
			}

			// Preprocess formatting to convert newlines to BRs and sanitize HTML
			$data['title']       = $this->preprocess_content( $data['title'] );
			$data['explanation'] = $this->preprocess_content( $data['explanation'] );
			foreach ( array( 'option_a', 'option_b', 'option_c', 'option_d', 'option_e' ) as $opt_key ) {
				if ( ! empty( $data[$opt_key] ) ) {
					$data[$opt_key] = $this->preprocess_content( $data[$opt_key] );
				}
			}

			$data['category_id'] = $category_id;
			$data['subcategory_id'] = $subcategory_id;
			$data['status'] = 'publish';
			$data['marks'] = 1;
			$data['negative_marks'] = 0.25;

			$res = $question_logic->save_question( $data );
			if ( $res ) {
				$results['inserted']++;
			} else {
				$results['skipped']++;
			}
		}

		return $results;
	}

	private function parse_smart_block( $block ) {
		$data = array();
		
		// 1. Extract Correct Answer (Handles both MCQ keys A-E and Full Text Answers)
		if ( preg_match( '/(?:Ans|Answer|Correct|Key|उत्तर|Chosen\s+Option)[\:\s\-]*([A-E]|[1-5]|[१-५]|[अबसदइकखगघङ]|[a-e])/ui', $block, $match ) ) {
			$raw_ans = strtoupper( trim( $match[1] ) );
			if ( $raw_ans === '1' || $raw_ans === 'अ' || $raw_ans === 'क' || $raw_ans === 'A' ) $data['correct_answer'] = 'A';
			else if ( $raw_ans === '2' || $raw_ans === 'ब' || $raw_ans === 'ख' || $raw_ans === 'B' ) $data['correct_answer'] = 'B';
			else if ( $raw_ans === '3' || $raw_ans === 'स' || $raw_ans === 'ग' || $raw_ans === 'C' ) $data['correct_answer'] = 'C';
			else if ( $raw_ans === '4' || $raw_ans === 'द' || $raw_ans === 'घ' || $raw_ans === 'D' ) $data['correct_answer'] = 'D';
			else if ( $raw_ans === '5' || $raw_ans === 'इ' || $raw_ans === 'ङ' || $raw_ans === 'E' ) $data['correct_answer'] = 'E';
			else $data['correct_answer'] = $raw_ans;
			$block = str_replace( $match[0], '', $block ); 
		} else if ( preg_match( '/(?:Ans|Answer|Correct|Key|उत्तर)[\:\s]*(.*)/i', $block, $match ) ) {
			$data['correct_answer'] = trim( $match[1] );
			$block = str_replace( $match[0], '', $block ); 
		}

		// 2. Extract Options (Look for (A), A., (1), 1., etc. - handles Arabic, Devanagari numerals, and alphabets)
		$option_map = array(
			'A' => array('A', '1', 'a', '१', 'अ', 'क'),
			'B' => array('B', '2', 'b', '२', 'ब', 'ख'),
			'C' => array('C', '3', 'c', '३', 'स', 'ग'),
			'D' => array('D', '4', 'd', '४', 'द', 'घ'),
			'E' => array('E', '5', 'e', '५', 'इ', 'ङ')
		);

		$options_found = false;
		foreach ( $option_map as $key => $identifiers ) {
			$id_pattern = implode( '|', $identifiers );
			$pattern = '/(?:\n|^|[\s　])[\(\[]?(' . $id_pattern . ')[\)\]\.\s\-]+(.*?)(?=(?:\n\s*|[\s　]+)[\(\[]?(?:[A-E]|[1-5]|[१-५]|[अबसदइकखगघङ])[\)\]\.\s\-]|(?:\n\s*|[\s　]+)(?:Ans|Answer|Correct|उत्तर|उत्तरम|Expl|Explanation)|$)/isu';
			if ( preg_match( $pattern, $block, $match ) ) {
				$data['option_' . strtolower($key)] = trim( $match[2] );
				$block = str_replace( $match[0], '', $block );
				$options_found = true;
			}
		}

		// 3. Extract Explanation
		if ( preg_match( '/(?:Expl|Explanation|व्याख्या)[\:\s]*(.*)/is', $block, $match ) ) {
			$data['explanation'] = trim( $match[1] );
			$block = str_replace( $match[0], '', $block );
		} else {
			$data['explanation'] = '';
		}

		// 4. Remaining text is the Question Title
		$data['title'] = trim( $block );

		// 5. Intelligent Fallback: If no explicit Answer key was found and no options exist,
		// the last line of the remaining block is likely the verified answer (Direct Filling).
		if ( empty( $data['correct_answer'] ) && ! $options_found ) {
			$title_lines = explode( "\n", $data['title'] );
			if ( count( $title_lines ) > 1 ) {
				$data['correct_answer'] = trim( array_pop( $title_lines ) );
				$data['title'] = trim( implode( "\n", $title_lines ) );
			}
		}

		// Intelligence Logic: Determine question type
		$data['question_type'] = ($options_found) ? 'mcq' : 'short_answer';

		return ( ! empty( $data['title'] ) && ! empty( $data['correct_answer'] ) ) ? $data : false;
	}

	private function process_pipe_text( $text, $category_id, $subcategory_id ) {
		$lines = explode( "\n", $text );
		$results = array( 'inserted' => 0, 'skipped' => 0, 'errors' => array() );
		$question_logic = new GEP_Question();

		foreach ( $lines as $line ) {
			$parts = explode( '|', $line );
			if ( count( $parts ) < 6 ) continue;

			$data = array(
				'title'          => trim( $parts[0] ),
				'option_a'       => trim( $parts[1] ),
				'option_b'       => trim( $parts[2] ),
				'option_c'       => trim( $parts[3] ),
				'option_d'       => trim( $parts[4] ),
				'correct_answer' => strtoupper( trim( $parts[5] ) ),
				'explanation'    => isset( $parts[6] ) ? trim( $parts[6] ) : '',
				'category_id'    => $category_id,
				'subcategory_id' => $subcategory_id,
				'status'         => 'publish',
				'marks'          => 1,
				'negative_marks' => 0.25
			);

			$res = $question_logic->save_question( $data );
			if ( $res ) $results['inserted']++;
			else $results['skipped']++;
		}
		return $results;
	}

	private function get_normalized_hash( $text ) {
		// Strip HTML tags to get pure text content
		$text = strip_tags( $text );
		// Decode HTML entities (e.g. &nbsp; to space)
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		// Normalize all whitespace/newlines to empty string
		$text = preg_replace( '/\s+/u', '', $text );
		// Case-insensitive mb_strtolower
		return md5( mb_strtolower( $text, 'UTF-8' ) );
	}

	private function is_duplicate( $title, $category_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		
		// 1. Fast path: check direct MD5 on the raw stored text
		$hash = md5( trim( $title ) );
		$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE MD5(title) = %s AND category_id = %d LIMIT 1", $hash, $category_id ) );
		if ( $existing_id ) {
			return (int) $existing_id;
		}
		
		// 2. Resilient fallback: compare normalized text hashes in PHP
		$questions = $wpdb->get_results( $wpdb->prepare( "SELECT id, title FROM $table WHERE category_id = %d", $category_id ) );
		if ( ! empty( $questions ) ) {
			$target_hash = $this->get_normalized_hash( $title );
			foreach ( $questions as $q ) {
				if ( $this->get_normalized_hash( $q->title ) === $target_hash ) {
					return (int) $q->id;
				}
			}
		}
		
		return 0;
	}
}
