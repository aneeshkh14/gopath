<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Admin_Lectures {
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-lectures' ) return;

        if ( isset( $_POST['gep_lecture_save'] ) && wp_verify_nonce( $_POST['gep_nonce'], 'gep_save_lecture' ) ) {
            $this->save_lecture();
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete_lecture' && isset( $_GET['id'] ) ) {
            if ( wp_verify_nonce( $_GET['nonce'], 'gep_delete_lecture' ) ) {
                $this->delete_lecture( absint( $_GET['id'] ) );
                wp_redirect( admin_url( 'admin.php?page=gep-lectures&message=deleted' ) );
                exit;
            }
        }
    }

    private function save_lecture() {
        global $wpdb;
        $table = $wpdb->prefix . 'gep_lectures';

        $data = array(
            'title'            => sanitize_text_field( $_POST['title'] ),
            'video_url'        => esc_url_raw( $_POST['video_url'] ),
            'video_source'     => sanitize_text_field( $_POST['video_source'] ),
            'thumbnail'        => esc_url_raw( $_POST['thumbnail'] ),
            'instructor'       => sanitize_text_field( $_POST['instructor'] ),
            'category_id'      => absint( $_POST['category_id'] ),
            'subcategory_id'   => isset($_POST['subcategory_id']) ? absint( $_POST['subcategory_id'] ) : 0,
            'duration'         => sanitize_text_field( $_POST['duration'] ),
            'description'      => wp_kses_post( $_POST['description'] ),
            'status'           => sanitize_text_field( isset($_POST['status']) ? $_POST['status'] : 'publish' )
        );

        if ( ! empty( $_POST['lecture_id'] ) ) {
            $wpdb->update( $table, $data, array( 'id' => absint( $_POST['lecture_id'] ) ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table, $data );
        }

        wp_redirect( admin_url( 'admin.php?page=gep-lectures&message=saved' ) );
        exit;
    }

    private function delete_lecture( $id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'gep_lectures', array( 'id' => $id ) );
    }

    public function get_lectures() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_lectures ORDER BY created_at DESC" );
    }
}
