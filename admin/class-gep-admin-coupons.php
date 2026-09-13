<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin coupons management logic.
 */
class GEP_Admin_Coupons {
    public static $form_error = '';
    public static $form_values = array();

	public function handle_coupon_actions() {
		if ( ! current_user_can('manage_options') ) return;
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-coupons' ) return;

		if ( isset($_POST['gep_coupon_status_nonce'], $_POST['coupon_id'], $_POST['coupon_status']) ) {
			$id = absint($_POST['coupon_id']);
			check_admin_referer('gep_coupon_status_' . $id, 'gep_coupon_status_nonce');
			$status = $_POST['coupon_status'] === 'active' ? 'active' : 'inactive';
			$updated = $this->save_coupon(array('id' => $id, 'status' => $status));
			wp_safe_redirect(admin_url('admin.php?page=gep-coupons&message=' . ($updated === false || is_wp_error($updated) ? 'failed' : 'saved')));
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
			'usage_limit' => isset( $_POST['usage_limit'] ) ? sanitize_text_field(wp_unslash($_POST['usage_limit'])) : 0,
			'expiry_date' => isset( $_POST['expiry_date'] ) ? sanitize_text_field( $_POST['expiry_date'] ) : '',
			'status'      => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
		);

		if ( ! empty( $_POST['coupon_id'] ) ) {
			$data['id'] = absint( $_POST['coupon_id'] );
		}

		$saved = $this->save_coupon( $data );
        if ($saved === false || is_wp_error($saved)) {
            self::$form_values = $data;
            self::$form_error = is_wp_error($saved) ? $saved->get_error_message() : 'Could not save the coupon. Your entries are retained; please try again.';
            return;
        }

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

        // Validate the complete creation form; status-only updates retain existing terms.
        if (array_key_exists('code', $data)) {
            $data['code'] = strtoupper(trim((string)$data['code']));
            if ($data['code'] === '' || strlen($data['code']) > 50) return new WP_Error('invalid_code', 'Enter a coupon code of 1–50 characters.');
            if (!in_array($data['type'] ?? '', array('percent','fixed'), true)) return new WP_Error('invalid_type', 'Choose percentage or fixed discount.');
            if (!is_numeric($data['value'] ?? null) || !is_finite((float)$data['value']) || (float)$data['value'] <= 0 || ($data['type'] === 'percent' && (float)$data['value'] > 100)) return new WP_Error('invalid_value', 'Enter a positive discount; percentages cannot exceed 100.');
            if (filter_var($data['usage_limit'] ?? 0, FILTER_VALIDATE_INT, array('options'=>array('min_range'=>0, 'max_range'=>2147483647))) === false) return new WP_Error('invalid_limit', 'Usage limit must be a whole number of zero or more.');
            $data['usage_limit'] = (int)($data['usage_limit'] ?? 0);
            $expiry = $data['expiry_date'] ?? '';
            if ($expiry !== '') {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d', $expiry, wp_timezone());
                if (!$date || $date->format('Y-m-d') !== $expiry) return new WP_Error('invalid_expiry', 'Choose a valid expiry date.');
                // A selected date includes that whole day in the site's timezone.
                $data['expiry_date'] = $date->format('Y-m-d') . ' 23:59:59';
            } else $data['expiry_date'] = null;
            $duplicate = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE code = %s AND id <> %d LIMIT 1", $data['code'], absint($data['id'] ?? 0)));
            if ($duplicate) return new WP_Error('duplicate_code', 'This coupon code already exists. Choose another code.');
        }
        if (isset($data['status']) && !in_array($data['status'], array('active','inactive'), true)) return new WP_Error('invalid_status', 'Choose an active or inactive status.');

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
