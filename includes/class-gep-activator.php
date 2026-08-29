<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class GEP_Activator {

	/**
	 * Create custom database tables.
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		// 1. Categories Table
		$table_categories = $wpdb->prefix . 'gep_categories';
		$sql_categories = "CREATE TABLE $table_categories (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			parent_id bigint(20) DEFAULT 0,
			description text,
			is_default tinyint(1) DEFAULT 0,
			menu_order int(11) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_categories );

		// 2. Questions Table
		$table_questions = $wpdb->prefix . 'gep_questions';
		$sql_questions = "CREATE TABLE $table_questions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title longtext NOT NULL,
			question_type varchar(50) DEFAULT 'mcq',
			description longtext,
			option_a longtext,
			option_b longtext,
			option_c longtext,
			option_d longtext,
			option_e longtext,
			correct_answer text,
			explanation longtext,
			marks float DEFAULT 1,
			negative_marks float DEFAULT 0,
			numerical_tolerance float DEFAULT 0.01,
			passage_id bigint(20) DEFAULT 0,
			difficulty varchar(255) DEFAULT NULL,
			image_url varchar(255),
			category_id bigint(20),
			subcategory_id bigint(20),
			tags text,
			translation_enabled tinyint(1) DEFAULT 0,
			translated_data longtext,
			status varchar(50) DEFAULT 'publish',
			pyqs varchar(255),
			year varchar(50),
			source varchar(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY passage_id (passage_id),
			KEY category_id (category_id),
			KEY subcategory_id (subcategory_id),
			KEY question_type (question_type)
		) $charset_collate;";
		dbDelta( $sql_questions );

		// Explicitly migrate the column datatype to LONGTEXT for existing installations
		$wpdb->query( "ALTER TABLE $table_questions MODIFY COLUMN title LONGTEXT NOT NULL" );

		// Explicitly migrate correct_answer to allow NULL to prevent MySQL Strict Mode failures on passage imports/saves
		$wpdb->query( "ALTER TABLE $table_questions MODIFY COLUMN correct_answer TEXT NULL" );

		// Add option_e column if not exists
		$option_e_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'option_e'" );
		if ( empty( $option_e_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN option_e longtext" );
		}

		// Add pyqs column if not exists
		$pyqs_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'pyqs'" );
		if ( empty( $pyqs_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN pyqs varchar(255)" );
		}

		// Add year column if not exists
		$year_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'year'" );
		if ( empty( $year_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN year varchar(50)" );
		}

		// Add source column if not exists
		$source_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'source'" );
		if ( empty( $source_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN source varchar(255) DEFAULT NULL" );
		}

		// Add translation_enabled column if not exists (added in v1.6+)
		$trans_en_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'translation_enabled'" );
		if ( empty( $trans_en_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN translation_enabled tinyint(1) DEFAULT 0" );
		}

		// Add translated_data column if not exists (added in v1.6+)
		$trans_data_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'translated_data'" );
		if ( empty( $trans_data_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN translated_data longtext" );
		}

		// Add numerical_tolerance column if not exists (added in v1.7+)
		$num_tol_exists = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions' AND COLUMN_NAME = 'numerical_tolerance'" );
		if ( empty( $num_tol_exists ) ) {
			$wpdb->query( "ALTER TABLE $table_questions ADD COLUMN numerical_tolerance float DEFAULT 0.01" );
		}

		// 3. Tests Table
		$table_tests = $wpdb->prefix . 'gep_tests';
		$sql_tests = "CREATE TABLE $table_tests (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			type varchar(50) DEFAULT 'single',
			exam_mode varchar(50) DEFAULT 'custom',
			category_id bigint(20),
			subcategory_id bigint(20) DEFAULT 0,
			price float DEFAULT 0,
			is_free tinyint(1) DEFAULT 0,
			thumbnail varchar(255),
			instructions longtext,
			translated_data longtext,
			duration_minutes int(11) DEFAULT 60,
			section_timings longtext,
			total_marks float DEFAULT 0,
			pass_marks float DEFAULT 0,
			attempt_limit int(11) DEFAULT 1,
			shuffle_questions tinyint(1) DEFAULT 0,
			shuffle_options tinyint(1) DEFAULT 0,
			proctoring_enabled tinyint(1) DEFAULT 0,
			validity_date datetime,
			status varchar(50) DEFAULT 'publish',
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_tests );

		// 4. Test Questions Table
		$table_test_questions = $wpdb->prefix . 'gep_test_questions';
		$sql_test_questions = "CREATE TABLE $table_test_questions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			test_id bigint(20) NOT NULL,
			question_id bigint(20) NOT NULL,
			order_no int(11) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_test_questions );

		// 5. Test Series Table
		$table_test_series = $wpdb->prefix . 'gep_test_series';
		$sql_test_series = "CREATE TABLE $table_test_series (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			series_id bigint(20) NOT NULL,
			test_id bigint(20) NOT NULL,
			order_no int(11) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_test_series );

		// 6. Attempts Table
		$table_attempts = $wpdb->prefix . 'gep_attempts';
		$sql_attempts = "CREATE TABLE $table_attempts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			test_id bigint(20) NOT NULL,
			start_time datetime NOT NULL,
			end_time datetime,
			status varchar(50) DEFAULT 'in_progress',
			answers longtext,
			section_scores longtext,
			analytics_data longtext,
			score float DEFAULT 0,
			percentage float DEFAULT 0,
			is_pass tinyint(1) DEFAULT 0,
			attempt_number int(11) DEFAULT 1,
			question_ids text,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY test_id (test_id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_attempts );

		// 7. Violations Table
		$table_violations = $wpdb->prefix . 'gep_violations';
		$sql_violations = "CREATE TABLE $table_violations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			attempt_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			violation_type varchar(255) NOT NULL,
			timestamp datetime NOT NULL,
			count int(11) DEFAULT 1,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_violations );

		// 8. Orders Table
		$table_orders = $wpdb->prefix . 'gep_orders';
		$sql_orders = "CREATE TABLE $table_orders (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			test_id bigint(20) NOT NULL,
			razorpay_order_id varchar(255),
			razorpay_payment_id varchar(255),
			amount float DEFAULT 0,
			discount float DEFAULT 0,
			coupon_code varchar(50),
			item_type varchar(50) DEFAULT 'test',
			item_id bigint(20) NOT NULL,
			status varchar(50) DEFAULT 'pending',
			attempts int DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_orders );

		// 9. Coupons Table
		$table_coupons = $wpdb->prefix . 'gep_coupons';
		$sql_coupons = "CREATE TABLE $table_coupons (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			code varchar(50) NOT NULL,
			type varchar(50) DEFAULT 'fixed',
			value float NOT NULL,
			expiry_date datetime,
			usage_limit int(11) DEFAULT 0,
			used_count int(11) DEFAULT 0,
			status varchar(50) DEFAULT 'active',
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_coupons );

		// 10. Notifications Table
		$table_notifications = $wpdb->prefix . 'gep_notifications';
		$sql_notifications = "CREATE TABLE $table_notifications (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT 0,
			title varchar(255) NOT NULL,
			message text NOT NULL,
			created_at datetime NOT NULL,
			is_read tinyint(1) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_notifications );

		// 15. Notification Reads Table (Bridge for global notifs)
		$table_notif_reads = $wpdb->prefix . 'gep_notif_reads';
		$sql_notif_reads = "CREATE TABLE $table_notif_reads (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			notification_id bigint(20) NOT NULL,
			read_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY notification_id (notification_id)
		) $charset_collate;";
		dbDelta( $sql_notif_reads );

		// 11. User Test Access Table
		$table_access = $wpdb->prefix . 'gep_user_test_access';
		$sql_access = "CREATE TABLE $table_access (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			test_id bigint(20) NOT NULL,
			granted_by bigint(20) DEFAULT 0,
			extra_attempts int(11) DEFAULT 0,
			assigned_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_access );

		// 12. Courses Table
		$table_courses = $wpdb->prefix . 'gep_courses';
		$sql_courses = "CREATE TABLE $table_courses (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description text,
			thumbnail varchar(255),
			instructor varchar(255),
			price float DEFAULT 0,
			is_free tinyint(1) DEFAULT 0,
			category_id bigint(20),
			subcategory_id bigint(20) DEFAULT 0,
			status varchar(50) DEFAULT 'publish',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_courses );

		// 13. Lessons Table (Video content)
		$table_lessons = $wpdb->prefix . 'gep_lessons';
		$sql_lessons = "CREATE TABLE $table_lessons (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			course_id bigint(20) NOT NULL,
			title varchar(255) NOT NULL,
			video_url varchar(255),
			video_source varchar(50) DEFAULT 'vimeo',
			duration varchar(20),
			order_no int(11) DEFAULT 0,
			description text,
			pdf_url varchar(255) DEFAULT '',
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_lessons );

		// 14. User Course Access Table
		$table_course_access = $wpdb->prefix . 'gep_user_course_access';
		$sql_course_access = "CREATE TABLE $table_course_access (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			course_id bigint(20) NOT NULL,
			assigned_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_course_access );

		// 16. Doubts Table
		$table_doubts = $wpdb->prefix . 'gep_doubts';
		$sql_doubts = "CREATE TABLE $table_doubts (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			course_id bigint(20) NOT NULL,
			lesson_id bigint(20) NOT NULL,
			question text NOT NULL,
			answer text,
			status varchar(50) DEFAULT 'unresolved',
			created_at datetime NOT NULL,
			resolved_at datetime,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_doubts );

		// End of table creation

		// 16. Live Classes Table
		$table_live = $wpdb->prefix . 'gep_live_classes';
		$sql_live = "CREATE TABLE $table_live (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			course_id bigint(20) DEFAULT 0,
			title varchar(255) NOT NULL,
			instructor varchar(255),
			meeting_id varchar(255),
			meeting_password varchar(255),
			meeting_url varchar(255),
			url_type varchar(50) DEFAULT 'external',
			scheduled_at datetime,
			duration_minutes int(11) DEFAULT 60,
			status varchar(50) DEFAULT 'scheduled',
			recording_url varchar(255),
			category_id bigint(20) DEFAULT 0,
			subcategory_id bigint(20) DEFAULT 0,
			is_free tinyint(1) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_live );

		// 17. Lectures Table
		$table_lectures = $wpdb->prefix . 'gep_lectures';
		$sql_lectures = "CREATE TABLE $table_lectures (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			video_url varchar(255),
			video_source varchar(50) DEFAULT 'vimeo',
			thumbnail varchar(255),
			instructor varchar(255),
			category_id bigint(20) DEFAULT 0,
			subcategory_id bigint(20) DEFAULT 0,
			duration varchar(20),
			description text,
			status varchar(50) DEFAULT 'publish',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";
		dbDelta( $sql_lectures );

		update_option( 'gep_db_version', GEP_DB_VERSION );

		// Set flag to run page creation and seeding on next admin load to prevent activation hook crashes
		update_option( 'gep_run_activation_tasks', 1 );
		update_option( 'gep_ai_unlocked', 1 );
	}
}
