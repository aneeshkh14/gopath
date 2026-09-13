<?php
/**
 * Sovereign Notification Engine for GoPath Exam Portal.
 * Handles the creation, retrieval, and management of user alerts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GEP_Notifications {

    /**
     * Create a new notification.
     * 
     * @param int $user_id The user ID (0 for global notifications).
     * @param string $title The notification title.
     * @param string $message The notification content.
     * @return int|bool The inserted ID or false.
     */
    public static function create( $user_id, $title, $message ) {
        global $wpdb;
        $table = $wpdb->prefix . 'gep_notifications';

        $result = $wpdb->insert(
            $table,
            array(
                'user_id'    => $user_id,
                'title'      => $title,
                'message'    => $message,
                'created_at' => current_time( 'mysql' ),
                'is_read'    => 0,
            ),
            array( '%d', '%s', '%s', '%s', '%d' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get notifications for a specific user.
     * Includes global notifications (user_id = 0).
     */
    public static function get_for_user( $user_id, $limit = 10, $unread_only = false ) {
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'gep_notifications';
        $table_reads  = $wpdb->prefix . 'gep_notif_reads';

        $query = "SELECT n.*, r.id as read_record_id 
                  FROM $table_notifs n 
                  LEFT JOIN $table_reads r ON n.id = r.notification_id AND r.user_id = %d 
                  WHERE (n.user_id = %d OR n.user_id = 0)";
        
        if ( $unread_only ) {
            $query .= " AND ( (n.user_id != 0 AND n.is_read = 0) OR (n.user_id = 0 AND r.id IS NULL) )";
        }

        $query .= " ORDER BY n.created_at DESC LIMIT %d";
        
        $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $user_id, $limit ) );
        
        // Normalize results to include is_read boolean
        foreach ( $results as &$r ) {
            $r->is_read = ($r->user_id != 0) ? (bool)$r->is_read : !is_null($r->read_record_id);
        }
        
        return $results;
    }

    /**
     * Get unread count for a user.
     */
    public static function get_unread_count( $user_id ) {
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'gep_notifications';
        $table_reads  = $wpdb->prefix . 'gep_notif_reads';
        
        $query = $wpdb->prepare( 
            "SELECT COUNT(n.id) 
             FROM $table_notifs n 
             LEFT JOIN $table_reads r ON n.id = r.notification_id AND r.user_id = %d 
             WHERE (n.user_id = %d AND n.is_read = 0) 
                OR (n.user_id = 0 AND r.id IS NULL)", 
            $user_id, $user_id 
        );
        
        return (int) $wpdb->get_var( $query );
    }

    /**
     * Mark a notification as read.
     */
    public static function mark_as_read( $notification_id, $user_id = 0 ) {
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'gep_notifications';
        $table_reads  = $wpdb->prefix . 'gep_notif_reads';

        $notif = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $table_notifs WHERE id = %d", $notification_id ) );
        if ( ! $notif ) return false;

        if ( ! $user_id ) $user_id = get_current_user_id();

        if ( $notif->user_id == 0 ) {
            // Check if already read
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_reads WHERE user_id = %d AND notification_id = %d", $user_id, $notification_id ) );
            if ( ! $exists ) {
                return $wpdb->insert( $table_reads, array( 'user_id' => $user_id, 'notification_id' => $notification_id, 'read_at' => current_time('mysql') ) );
            }
            return true;
        }

        // Enforce ownership check for user-specific notifications
        if ( $notif->user_id != $user_id ) {
            return false;
        }

        return $wpdb->update(
            $table_notifs,
            array( 'is_read' => 1 ),
            array( 'id' => $notification_id )
        );
    }

    /**
     * Mark all for user as read.
     */
    public static function mark_all_read( $user_id ) {
        global $wpdb;
        $table_notifs = $wpdb->prefix . 'gep_notifications';
        $table_reads  = $wpdb->prefix . 'gep_notif_reads';

        // 1. Mark per-user notifs as read
        $updated = $wpdb->update(
            $table_notifs, 
            array( 'is_read' => 1 ), 
            array( 'user_id' => $user_id, 'is_read' => 0 ) 
        );

        if ( $updated === false ) return false;

        // 2. Mark global notifs as read (insert into bridge for this user)
        $unread_globals = $wpdb->get_col( $wpdb->prepare( 
            "SELECT n.id FROM $table_notifs n 
             LEFT JOIN $table_reads r ON n.id = r.notification_id AND r.user_id = %d 
             WHERE n.user_id = 0 AND r.id IS NULL", 
            $user_id 
        ) );

        if ( $unread_globals ) {
            foreach ( $unread_globals as $notif_id ) {
                $inserted = $wpdb->insert( $table_reads, array(
                    'user_id' => $user_id, 
                    'notification_id' => $notif_id, 
                    'read_at' => current_time('mysql') 
                ) );
                if ( $inserted === false ) return false;
            }
        }
        
        return true;
    }
}
