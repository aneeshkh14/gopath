<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Question management logic.
 */
class GEP_Question {

	public function get_question( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public function get_questions_by_ids( $ids ) {
		if ( empty( $ids ) ) return array();
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		$ids_str = implode( ',', array_map( 'absint', $ids ) );
		// Perfect Asset Filter: Ensure only published questions are retrieved to maintain integrity
		return $wpdb->get_results( "SELECT * FROM $table WHERE id IN ($ids_str) AND status = 'publish' ORDER BY FIELD(id, $ids_str)" );
	}

	/**
	 * Advanced random question selection with multiple filters.
	 */
	public function get_random_questions( $args ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';
		
		$defaults = array(
			'count'          => 10,
			'category_id'    => null,
			'subcategory_id' => null,
			'tags'           => array(),
			'status'         => 'publish'
		);
		$args = wp_parse_args( $args, $defaults );

		$where = "WHERE status = %s";
		$params = array( $args['status'] );

		if ( $args['category_id'] ) {
			$where .= " AND category_id = %d";
			$params[] = $args['category_id'];
		}
		if ( $args['subcategory_id'] ) {
			$where .= " AND subcategory_id = %d";
			$params[] = $args['subcategory_id'];
		}

		if ( ! empty( $args['tags'] ) ) {
			$tag_conditions = array();
			foreach ( $args['tags'] as $tag ) {
				$tag_conditions[] = "tags LIKE %s";
				$params[] = '%' . $wpdb->esc_like( $tag ) . '%';
			}
			$where .= " AND (" . implode( " OR ", $tag_conditions ) . ")";
		}

		$sql = "SELECT id FROM $table $where ORDER BY RAND() LIMIT %d";
		$params[] = absint( $args['count'] );

		return $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
	}

	public function save_question( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_questions';

		$format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%d', '%s', '%d', '%s', '%s' );
		
		if ( isset( $data['id'] ) ) {
			$id = absint( $data['id'] );
			unset( $data['id'] );
			$res = $wpdb->update( $table, $data, array( 'id' => $id ) );
			if ( $res === false ) {
				error_log( "GoPath Exam Portal DB Update Error: " . $wpdb->last_error );
			}
			return $id;
		} else {
			$res = $wpdb->insert( $table, $data );
			if ( $res === false ) {
				error_log( "GoPath Exam Portal DB Insert Error: " . $wpdb->last_error );
			}
			return $wpdb->insert_id;
		}
	}
}
