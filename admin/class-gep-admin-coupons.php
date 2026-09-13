<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin coupons management logic.
 */
class GEP_Admin_Coupons {

	public function handle_coupon_actions() {
		if ( ! current_user_can('manage_options') ) return;
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-coupons' ) return;

		if ( isset($_POST['gep_coupon_status_nonce'], $_POST['coupon_id'], $_POST['coupon_status']) ) {
			$id = absint($_POST['coupon_id']);
			check_admin_referer('gep_coupon_status_' . $id, 'gep_coupon_status_nonce');
			$status = $_POST['coupon_status'] === 'active' ? 'active' : 'inactive';
			$updated = $this->save_coupon(array('id' => $id, 'status' => $status));
			wp_safe_redirect(admin_url('admin.php?page=gep-coupons&message=' . ($updated === false ? 'failed' : 'saved')));
			exit;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
			if ( check_admin_referer( 'gep_coupon_delete_' . $_GET['id'] ) ) {
				$this->delete_coupon( absint( $_GET['id'] ) );
				wp_redirect( admin_url( 'admin.php?page=gep-coupons' ) );
				exit;
			}
		}

		if ( isset( $_POST['gep_coupon_nonce'] ) && wp_verify_nonce( $_POST['gep_coupon_nonce'], 'gep_coupon_action' ) ) {
			$this->handle_save_coupon();
		}
	}

	private function handle_save_coupon() {
		$data = array(
			'code'        => isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '',
			'type'        => isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'fixed',
			'value'       => isset( $_POST['value'] ) ? floatval( $_POST['value'] ) : 0,
			'usage_limit' => isset( $_POST['usage_limit'] ) ? absint( $_POST['usage_limit'] ) : 0,
			'expiry_date' => isset( $_POST['expiry_date'] ) ? sanitize_text_field( $_POST['expiry_date'] ) : '',
			'status'      => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
		);

		if ( ! empty( $_POST['coupon_id'] ) ) {
			$data['id'] = absint( $_POST['coupon_id'] );
		}

		$this->save_coupon( $data );

		wp_redirect( admin_url( 'admin.php?page=gep-coupons&message=saved' ) );
		exit;
	}

	public function get_coupons() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_coupons ORDER BY id DESC" );
	}

	public function save_coupon( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'gep_coupons';

		if ( isset( $data['id'] ) && ! empty( $data['id'] ) ) {
			return $wpdb->update( $table, $data, array( 'id' => $data['id'] ) );
		} else {
			return $wpdb->insert( $table, $data );
		}
	}

	public function delete_coupon( $id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'gep_coupons', array( 'id' => $id ) );
	}
}
