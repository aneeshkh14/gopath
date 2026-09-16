<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Admin_Courses {
    public function handle_actions() {
        if ( ! current_user_can( 'edit_posts' ) || ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-courses' ) return;

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
        $input = wp_unslash( $_POST );
        $course_id = absint( $input['course_id'] ?? 0 );
        if ( trim( $input['title'] ?? '' ) === '' || trim( $input['instructor'] ?? '' ) === '' || ! is_numeric( $input['price'] ?? '' ) || (float) $input['price'] < 0 ) {
            add_settings_error( 'gep_courses', 'invalid_course', 'Enter a title, instructor and a price of zero or more.' );
            return;
        }
        if ( $course_id && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE id = %d", $course_id ) ) ) {
            add_settings_error( 'gep_courses', 'missing_course', 'This course no longer exists. Reload the course list before saving.' );
            return;
        }

        $data = array(
            'title'       => sanitize_text_field( $input['title'] ),
            'instructor'  => sanitize_text_field( $input['instructor'] ),
            'description' => wp_kses_post( $input['description'] ?? '' ),
            'price'       => round( (float) $input['price'], 2 ),
            'thumbnail'   => esc_url_raw( $input['thumbnail'] ?? '' ),
            'category_id' => absint( $input['category_id'] ?? 0 ),
            'subcategory_id' => absint( $input['subcategory_id'] ?? 0 ),
            // CRITICAL FIX: Was 'active' — but supercoaching.php filters WHERE status='publish'
            // Courses with status='active' were never shown on the frontend marketplace.
            'status'      => 'publish'
        );

        if ( $data['subcategory_id'] && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}gep_categories WHERE id = %d AND parent_id = %d", $data['subcategory_id'], $data['category_id'] ) ) ) {
            add_settings_error( 'gep_courses', 'invalid_subcategory', 'Choose a subcategory belonging to the selected category.' );
            return;
        }
        if ( $course_id ) {
            $saved = $wpdb->update( $table, $data, array( 'id' => $course_id ) );
        } else {
            $saved = $wpdb->insert( $table, $data );
        }
        if ( false === $saved ) {
            add_settings_error( 'gep_courses', 'save_failed', 'The course could not be saved. Your entries are preserved below; please try again.' );
            return;
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
