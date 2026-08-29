<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Translator System: Handles bilingual question content.
 */
class GEP_Translator {

	public function get_translated_content( $question_id ) {
		$question_logic = new GEP_Question();
		$question = $question_logic->get_question( $question_id );

		if ( ! $question || ! $question->translation_enabled ) {
			return false;
		}

		$data = gep_safe_json_decode( $question->translated_data, true );
		if ( empty( $data ) ) return false;

		// SECURITY: this endpoint is reachable via AJAX by any logged-in student for any
		// question ID (see GEP_AJAX::gep_get_translation()) — it must only ever return
		// translated question/option TEXT, never the answer key or explanation, otherwise
		// a student could fetch the correct answer for a question during an active exam.
		return array(
			'title'    => isset( $data['title'] ) ? wp_kses_post( $data['title'] ) : '',
			'option_a' => isset( $data['option_a'] ) ? wp_kses_post( $data['option_a'] ) : '',
			'option_b' => isset( $data['option_b'] ) ? wp_kses_post( $data['option_b'] ) : '',
			'option_c' => isset( $data['option_c'] ) ? wp_kses_post( $data['option_c'] ) : '',
			'option_d' => isset( $data['option_d'] ) ? wp_kses_post( $data['option_d'] ) : '',
			'option_e' => isset( $data['option_e'] ) ? wp_kses_post( $data['option_e'] ) : '',
		);
	}
}
