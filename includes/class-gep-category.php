<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category management logic.
 */
class GEP_Category {

	public function get_categories( $parent_id = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE parent_id = %d ORDER BY menu_order ASC", $parent_id ) );
	}

	public function get_category_by_id( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
	}

	public function get_all_subcategories() {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		return $wpdb->get_results( "SELECT * FROM $table WHERE parent_id > 0 ORDER BY name ASC" );
	}

	public function get_hierarchical_categories() {
		$parents = $this->get_categories( 0 );
		foreach ( $parents as &$parent ) {
			$parent->children = $this->get_categories( $parent->id );
		}
		return $parents;
	}

	public function get_or_create_category( $name, $parent_id = 0 ) {
		$name = trim( (string) $name );
		if ( empty( $name ) ) return 0;
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE name = %s AND parent_id = %d", $name, $parent_id ) );
		
		if ( ! $id ) {
			$wpdb->insert( $table, array(
				'name'      => $name,
				'slug'      => sanitize_title( $name ),
				'parent_id' => $parent_id
			) );
			$id = $wpdb->insert_id;
		}
		return (int) $id;
	}
}
