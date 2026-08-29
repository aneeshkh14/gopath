<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Admin_Lessons {
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-lessons' ) return;

        if ( isset( $_POST['gep_lesson_save'] ) ) {
            $this->save_lesson();
        }
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_lesson' ) {
            $this->delete_lesson();
        }
    }

    private function save_lesson() {
        if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_save_lesson' ) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gep_lessons';

        $data = array(
            'course_id'    => absint( $_POST['course_id'] ),
            'title'        => sanitize_text_field( $_POST['title'] ),
            'video_url'    => esc_url_raw( $_POST['video_url'] ),
            'video_source' => sanitize_text_field( $_POST['source'] ), // youtube, vimeo, direct
            'duration'     => sanitize_text_field( $_POST['duration'] ),
            'order_no'     => absint( $_POST['order_num'] ),
            'description'  => sanitize_textarea_field( $_POST['description'] ),
            'pdf_url'      => isset( $_POST['pdf_url'] ) ? esc_url_raw( $_POST['pdf_url'] ) : ''
        );

        if ( ! empty( $_POST['lesson_id'] ) ) {
            $wpdb->update( $table, $data, array( 'id' => absint( $_POST['lesson_id'] ) ) );
        } else {
            $wpdb->insert( $table, $data );
        }

        wp_redirect( admin_url( 'admin.php?page=gep-lessons&message=saved' ) );
        exit;
    }

    private function delete_lesson() {
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_delete_lesson' ) ) {
            return;
        }

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'gep_lessons', array( 'id' => absint( $_GET['id'] ) ) );

        wp_redirect( admin_url( 'admin.php?page=gep-lessons&message=deleted' ) );
        exit;
    }
}
