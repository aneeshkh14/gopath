<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Admin_Live {
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-live-classes' ) return;

        if ( isset( $_POST['gep_live_save'] ) && wp_verify_nonce( $_POST['gep_nonce'], 'gep_save_live' ) ) {
            $this->save_live_class();
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_live' && isset( $_GET['id'] ) ) {
            if ( wp_verify_nonce( $_GET['nonce'], 'gep_delete_live' ) ) {
                $this->delete_live_class( absint( $_GET['id'] ) );
                wp_redirect( admin_url( 'admin.php?page=gep-live-classes&message=deleted' ) );
                exit;
            }
        }
    }

    private function save_live_class() {
        global $wpdb;
        $table = $wpdb->prefix . 'gep_live_classes';

        $data = array(
            'title'            => sanitize_text_field( $_POST['title'] ),
            'course_id'        => absint( $_POST['course_id'] ),
            'instructor'       => sanitize_text_field( $_POST['instructor'] ),
            'meeting_id'       => sanitize_text_field( isset($_POST['meeting_id']) ? $_POST['meeting_id'] : '' ),
            'meeting_password' => sanitize_text_field( isset($_POST['meeting_password']) ? $_POST['meeting_password'] : '' ),
            'meeting_url'      => esc_url_raw( isset($_POST['meeting_url']) ? $_POST['meeting_url'] : '' ),
            'url_type'         => sanitize_text_field( isset($_POST['url_type']) ? $_POST['url_type'] : 'external' ),
            'scheduled_at'     => sanitize_text_field( $_POST['scheduled_at'] ),
            'duration_minutes' => absint( isset($_POST['duration']) ? $_POST['duration'] : 60 ),
            'status'           => sanitize_text_field( isset($_POST['status']) ? $_POST['status'] : 'scheduled' ),
            'recording_url'    => esc_url_raw( isset($_POST['recording_url']) ? $_POST['recording_url'] : '' ),
            'category_id'      => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'subcategory_id'   => isset($_POST['subcategory_id']) ? absint($_POST['subcategory_id']) : 0,
            'is_free'          => isset( $_POST['is_free'] ) ? 1 : 0
        );

        if ( ! empty( $_POST['live_id'] ) ) {
            $wpdb->update( $table, $data, array( 'id' => absint( $_POST['live_id'] ) ) );
        } else {
            $wpdb->insert( $table, $data );
        }

        wp_redirect( admin_url( 'admin.php?page=gep-live-classes&message=saved' ) );
        exit;
    }

    private function delete_live_class( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'gep_live_classes', array( 'id' => $id ) );
    }

    public function get_live_classes( $status = 'scheduled' ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gep_live_classes WHERE status = %s ORDER BY scheduled_at ASC", $status ) );
    }
}

