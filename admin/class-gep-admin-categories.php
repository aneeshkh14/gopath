<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin logic for category management.
 */
class GEP_Admin_Categories {

	public function handle_category_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-categories' ) {
			return;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			if ( check_admin_referer( 'gep_category_delete_' . $_GET['id'] ) ) {
				$this->delete_category();
				$this->safe_redirect( admin_url( 'admin.php?page=gep-categories&message=deleted' ) );
			}
		}

		if ( isset( $_POST['gep_category_nonce'] ) && wp_verify_nonce( $_POST['gep_category_nonce'], 'gep_category_action' ) ) {
			if ( isset( $_POST['gep_add_category'] ) ) {
				$this->add_category();
				$this->safe_redirect( admin_url( 'admin.php?page=gep-categories&message=added' ) );
			} elseif ( isset( $_POST['gep_edit_category'] ) ) {
				$this->update_category();
				$this->safe_redirect( admin_url( 'admin.php?page=gep-categories&message=updated' ) );
			}
		}
	}

	private function safe_redirect( $url ) {
		if ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		wp_redirect( $url );
		exit;
	}

	private function add_category() {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';

		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$slug = ! empty( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : sanitize_title( $name );
		$parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
		$description = isset( $_POST['description'] ) ? wp_kses_post( $_POST['description'] ) : '';
		$is_default = isset( $_POST['is_default'] ) ? 1 : 0;
		$menu_order = isset( $_POST['menu_order'] ) ? absint( $_POST['menu_order'] ) : 0;

		$wpdb->insert(
			$table,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'parent_id'   => $parent_id,
				'description' => $description,
				'is_default'  => $is_default,
				'menu_order'  => $menu_order,
			),
			array( '%s', '%s', '%d', '%s', '%d', '%d' )
		);
	}

	private function update_category() {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		$id = absint( $_POST['category_id'] );

		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$slug = ! empty( $_POST['slug'] ) ? sanitize_title( $_POST['slug'] ) : sanitize_title( $name );
		$parent_id = isset( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
		$description = isset( $_POST['description'] ) ? wp_kses_post( $_POST['description'] ) : '';
		$is_default = isset( $_POST['is_default'] ) ? 1 : 0;
		$menu_order = isset( $_POST['menu_order'] ) ? absint( $_POST['menu_order'] ) : 0;

		$wpdb->update(
			$table,
			array(
				'name'        => $name,
				'slug'        => $slug,
				'parent_id'   => $parent_id,
				'description' => $description,
				'is_default'  => $is_default,
				'menu_order'  => $menu_order,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d', '%s', '%d', '%d' ),
			array( '%d' )
		);
	}

	public function delete_category() {
		if ( ! current_user_can( 'edit_posts' ) ) return;
		
		global $wpdb;
		$table = $wpdb->prefix . 'gep_categories';
		$id = absint( $_GET['id'] );

		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
	}
}
