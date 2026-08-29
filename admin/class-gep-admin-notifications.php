<?php
/**
 * Admin Notification Handler for GoPath Exam Portal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GEP_Admin_Notifications {

    /**
     * Handle admin actions for notifications.
     */
    public function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gep-notifications' ) {
            return;
        }

        if ( ! isset( $_POST['gep_action'] ) || $_POST['gep_action'] !== 'broadcast_notification' ) {
            return;
        }

        if ( ! isset( $_POST['gep_nonce'] ) || ! wp_verify_nonce( $_POST['gep_nonce'], 'gep_broadcast_notification' ) ) {
            wp_redirect( admin_url( 'admin.php?page=gep-notifications&error=nonce' ) );
            exit;
        }

        $title   = sanitize_text_field( $_POST['title'] );
        $message = sanitize_textarea_field( $_POST['message'] );
        $target  = sanitize_text_field( $_POST['target'] ); // 'all', 'premium', or user_id

        $user_id = 0; // Default global
        if ( is_numeric( $target ) ) {
            $user_id = absint( $target );
        }

        $result = GEP_Notifications::create( $user_id, $title, $message );

        if ( $result ) {
            wp_redirect( admin_url( 'admin.php?page=gep-notifications&message=broadcast_success' ) );
            exit;
        }
    }

    /**
     * Get all sent notifications for admin view.
     */
    public static function get_all_sent( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'gep_notifications';
        return $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT $limit" );
    }
}
