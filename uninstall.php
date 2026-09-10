<?php

/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Drop all custom tables (including course, lesson, doubts, notifications reads, and live classes tables)
$tables = array(
    'gep_categories',
    'gep_questions',
    'gep_tests',
    'gep_test_questions',
    'gep_test_series',
    'gep_attempts',
    'gep_violations',
    'gep_orders',
    'gep_coupons',
    'gep_notifications',
    'gep_notif_reads',
    'gep_user_test_access',
    'gep_courses',
    'gep_lessons',
    'gep_user_course_access',
    'gep_doubts',
    'gep_live_classes'
);

// foreach ( $tables as $table ) {
//     $table_name = $wpdb->prefix . $table;
//     $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
// }

// 2. Delete options (including settings, routing slugs, pages, and license configuration)
$options = array(
    'gep_db_version',
    'gep_razorpay_key_id',
    'gep_razorpay_key_secret',
    'gep_violation_action',
    'gep_violation_limit',
    'gep_default_category',
    'gep_enable_otp',
    'gep_default_instructor',
    'gep_live_meeting_platform',
    'gep_items_per_page',
    'gep_slug_dashboard',
    'gep_slug_exam',
    'gep_slug_result',
    'gep_log_secret',
    'gep_slug_checkout',
    'gep_slug_login',
    'gep_slug_register',
    'gep_ai_unlocked',
    'gep_page_dashboard',
    'gep_page_exam',
    'gep_page_result',
    'gep_page_checkout',
    'gep_page_login',
    'gep_page_register',
    'gep_page_forgot_password'
);

// foreach ( $options as $option ) {
//     delete_option( $option );
// }

// 3. Clear any transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_gep_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_gep_%'" );
