<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Admin_Courses {
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-courses' ) return;

        if ( isset( $_POST['gep_course_save'] ) ) {
            $this->save_course();
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_course' ) {
            $this->delete_course();
        }
    }

    private function save_course() {
        if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_save_course' ) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gep_courses';

        $data = array(
            'title'       => sanitize_text_field( $_POST['title'] ),
            'instructor'  => sanitize_text_field( $_POST['instructor'] ),
            'description' => wp_kses_post( $_POST['description'] ),
            'price'       => floatval( $_POST['price'] ),
            'thumbnail'   => esc_url_raw( $_POST['thumbnail'] ),
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'subcategory_id' => isset($_POST['subcategory_id']) ? absint($_POST['subcategory_id']) : 0,
            // CRITICAL FIX: Was 'active' — but supercoaching.php filters WHERE status='publish'
            // Courses with status='active' were never shown on the frontend marketplace.
            'status'      => 'publish'
        );

        if ( ! empty( $_POST['course_id'] ) ) {
            $wpdb->update( $table, $data, array( 'id' => absint( $_POST['course_id'] ) ) );
        } else {
            $wpdb->insert( $table, $data );
        }

        wp_cache_flush(); // Ensure frontend sees the new/updated course immediately
        wp_redirect( admin_url( 'admin.php?page=gep-courses&message=saved' ) );
        exit;
    }

    private function delete_course() {
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_delete_course' ) ) {
            return;
        }

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'gep_courses', array( 'id' => absint( $_GET['id'] ) ) );

        wp_cache_flush();
        wp_redirect( admin_url( 'admin.php?page=gep-courses&message=deleted' ) );
        exit;
    }
}
