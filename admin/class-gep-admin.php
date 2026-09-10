<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Admin class for the plugin.
 */
class GEP_Admin {

	public function add_plugin_admin_menu() {
		add_menu_page(
			'GoPath Exam Portal',
			'Exam Portal',
			'edit_posts',
			'gep-dashboard',
			array( $this, 'display_dashboard' ),
			'dashicons-welcome-learn-more',
			30
		);

		$submenus = array(
			'gep-dashboard'     => array( 'Dashboard', 'Dashboard', 'edit_posts', array( $this, 'display_dashboard' ) ),
			'gep-students'      => array( 'Students', 'Students', 'edit_posts', array( $this, 'display_students' ) ),
			'gep-questions'     => array( 'Questions', 'Questions', 'edit_posts', array( $this, 'display_questions' ) ),
			'gep-tests'         => array( 'Tests', 'Tests', 'edit_posts', array( $this, 'display_tests' ) ),
			'gep-courses'       => array( 'Courses', 'Courses', 'edit_posts', array( $this, 'display_courses' ) ),
			'gep-lessons'       => array( 'Lessons', 'Lessons', 'edit_posts', array( $this, 'display_lessons' ) ),
			'gep-live-classes'  => array( 'Live Classes', 'Live Classes', 'edit_posts', array( $this, 'display_live_classes' ) ),
			'gep-lectures'      => array( 'Lectures', 'Lectures', 'edit_posts', array( $this, 'display_lectures' ) ),
			'gep-categories'    => array( 'Categories', 'Categories', 'edit_posts', array( $this, 'display_categories' ) ),
			'gep-attempts'      => array( 'Attempts', 'Attempts', 'edit_posts', array( $this, 'display_attempts' ) ),
			'gep-violations'    => array( 'Violations', 'Violations', 'edit_posts', array( $this, 'display_violations' ) ),
			'gep-payments'      => array( 'Payments', 'Payments', 'manage_options', array( $this, 'display_payments' ) ),
			'gep-coupons'       => array( 'Coupons', 'Coupons', 'manage_options', array( $this, 'display_coupons' ) ),
			'gep-notifications' => array( 'Notifications', 'Notifications', 'edit_posts', array( $this, 'display_notifications' ) ),
			'gep-reports'       => array( 'Reports', 'Reports', 'edit_posts', array( $this, 'display_reports' ) ),
			'gep-doubts'        => array( 'Doubt Engine', 'Doubt Engine', 'edit_posts', array( $this, 'display_doubts' ) ),
			'gep-settings'      => array( 'Settings', 'Settings', 'manage_options', array( $this, 'display_settings' ) ),
		);

		foreach ( $submenus as $slug => $data ) {
			add_submenu_page(
				'gep-dashboard',
				$data[0],
				$data[1],
				$data[2],
				$slug,
				$data[3]
			);
		}
	}

	public function display_dashboard() {
		include GEP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function display_doubts() {
		include GEP_PLUGIN_DIR . 'admin/views/doubts.php';
	}

	public function display_questions() {
		include GEP_PLUGIN_DIR . 'admin/views/questions.php';
	}

	public function display_tests() {
		include GEP_PLUGIN_DIR . 'admin/views/tests.php';
	}

	public function display_courses() {
		include GEP_PLUGIN_DIR . 'admin/views/courses.php';
	}

	public function display_lessons() {
		include GEP_PLUGIN_DIR . 'admin/views/lessons.php';
	}

	public function display_categories() {
		include GEP_PLUGIN_DIR . 'admin/views/categories.php';
	}

	public function display_attempts() {
		include GEP_PLUGIN_DIR . 'admin/views/attempts.php';
	}

	public function display_violations() {
		include GEP_PLUGIN_DIR . 'admin/views/violations.php';
	}

	public function display_payments() {
		include GEP_PLUGIN_DIR . 'admin/views/payments.php';
	}

	public function display_coupons() {
		include GEP_PLUGIN_DIR . 'admin/views/coupons.php';
	}

	public function display_students() {
		include GEP_PLUGIN_DIR . 'admin/views/students.php';
	}

	public function display_live_classes() {
		include GEP_PLUGIN_DIR . 'admin/views/live-classes.php';
	}

	public function display_lectures() {
		include GEP_PLUGIN_DIR . 'admin/views/lectures.php';
	}

	public function display_notifications() {
		include GEP_PLUGIN_DIR . 'admin/views/notifications.php';
	}

	public function display_reports() {
		include GEP_PLUGIN_DIR . 'admin/views/reports.php';
	}

	public function display_settings() {
		include GEP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	private function delete_violation() {
		if ( ! isset( $_GET['id'] ) || ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_delete_violation' ) ) {
			wp_redirect( admin_url( 'admin.php?page=gep-violations&error=nonce' ) );
			exit;
		}
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'gep_violations', array( 'id' => absint( $_GET['id'] ) ) );
		wp_redirect( admin_url( 'admin.php?page=gep-violations&message=deleted' ) );
		exit;
	}

	public function enqueue_styles( $hook ) {
		if ( strpos( $hook, 'gep-' ) === false ) return;
		wp_enqueue_style( 'gep-admin-css', GEP_PLUGIN_URL . 'admin/css/gep-admin.css', array(), GEP_VERSION );
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'gep-' ) === false ) return;
		wp_enqueue_media();
		wp_enqueue_script( 'gep-admin-js', GEP_PLUGIN_URL . 'admin/js/gep-admin.js', array( 'jquery' ), GEP_VERSION, true );
		wp_enqueue_style( 'gep-katex-css', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css', array(), '0.16.8' );
		wp_enqueue_script( 'gep-katex', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js', array(), '0.16.8', true );
		wp_enqueue_script( 'gep-katex-autorender', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js', array('gep-katex'), '0.16.8', true );
		wp_register_script( 'gep-katex-init', false );
		wp_enqueue_script( 'gep-katex-init' );
		wp_add_inline_script( 'gep-katex-init', 'document.addEventListener("DOMContentLoaded", function() { if (typeof renderMathInElement === "function") { renderMathInElement(document.body, { delimiters: [{left: "$$", right: "$$", display: true}, {left: "$", right: "$", display: false}, {left: "\\\\(", right: "\\\\)", display: false}, {left: "\\\\[", right: "\\\\]", display: true}], throwOnError: false }); } });' );
	}

	public function register_settings() {
		register_setting( 'gep_settings_group', 'gep_razorpay_key_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => ''
		) );
		register_setting( 'gep_settings_group', 'gep_razorpay_key_secret', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => ''
		) );
		register_setting( 'gep_settings_group', 'gep_violation_action', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'warn'
		) );
		register_setting( 'gep_settings_group', 'gep_violation_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3
		) );
		register_setting( 'gep_settings_group', 'gep_default_category', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0
		) );
		register_setting( 'gep_settings_group', 'gep_enable_otp', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'no'
		) );
		register_setting( 'gep_settings_group', 'gep_default_instructor', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Academic Lead'
		) );
		register_setting( 'gep_settings_group', 'gep_live_meeting_platform', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'zoom'
		) );
		register_setting( 'gep_settings_group', 'gep_items_per_page', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 10
		) );
		register_setting( 'gep_settings_group', 'gep_slug_dashboard', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'dashboard'
		) );
		register_setting( 'gep_settings_group', 'gep_slug_exam', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'exam'
		) );
		register_setting( 'gep_settings_group', 'gep_slug_result', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'result'
		) );
		register_setting( 'gep_settings_group', 'gep_slug_checkout', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'checkout'
		) );
		register_setting( 'gep_settings_group', 'gep_slug_login', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'login'
		) );
		register_setting( 'gep_settings_group', 'gep_slug_register', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_title',
			'default'           => 'register'
		) );

		// Slideshow Custom Banners Settings
		register_setting( 'gep_settings_group', 'gep_slide1_title', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Mission Officer 2026'
		) );
		register_setting( 'gep_settings_group', 'gep_slide1_desc', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Your Journey to Government Job Starts Here. Get access to premium tests and video courses.'
		) );
		register_setting( 'gep_settings_group', 'gep_slide1_btn', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Explore Academy'
		) );
		register_setting( 'gep_settings_group', 'gep_slide1_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );
		register_setting( 'gep_settings_group', 'gep_slide1_image', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );

		register_setting( 'gep_settings_group', 'gep_slide2_title', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'UGC NET Mock Tests'
		) );
		register_setting( 'gep_settings_group', 'gep_slide2_desc', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Challenge yourself with realistic full-length paper simulations. Track your progress with advanced cohort analytics.'
		) );
		register_setting( 'gep_settings_group', 'gep_slide2_btn', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Practice Now'
		) );
		register_setting( 'gep_settings_group', 'gep_slide2_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );
		register_setting( 'gep_settings_group', 'gep_slide2_image', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );

		register_setting( 'gep_settings_group', 'gep_slide3_title', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Live Doubt Solving'
		) );
		register_setting( 'gep_settings_group', 'gep_slide3_desc', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Connect with top educators in real-time interactively. Resolve conceptual doubts and learn exam techniques.'
		) );
		register_setting( 'gep_settings_group', 'gep_slide3_btn', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Join Live Class'
		) );
		register_setting( 'gep_settings_group', 'gep_slide3_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );
		register_setting( 'gep_settings_group', 'gep_slide3_image', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => ''
		) );

		register_setting( 'gep_settings_group', 'gep_google_verification', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => ''
		) );

	}

	public function handle_admin_actions() {
		// Guard: prevent double execution if accidentally called from multiple hooks
		static $executed = false;
		if ( $executed ) return;
		$executed = true;

		// ─── DEBUG: Log this function's entry on every POST to gep-questions ─
		$log_file = function_exists('gep_log_file') ? gep_log_file() : '';
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_GET['page'] ) && $_GET['page'] === 'gep-questions' ) {
			$uid       = get_current_user_id();
			$can_edit  = current_user_can( 'edit_posts' );
			$cur_hook  = current_action();
			gep_log_to( $log_file, sprintf(
				"[%s] handle_admin_actions ENTERED | hook='%s' | user_id=%d | can_edit=%s\n",
				date( 'Y-m-d H:i:s' ), $cur_hook, $uid, $can_edit ? 'YES' : 'NO'
			), FILE_APPEND | LOCK_EX );
		}
		// ─────────────────────────────────────────────────────────────────────

		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Self-healing database upgrade — checks EVERY column added in recent versions.
		// Previously only checked 'option_e', so 'source', 'translation_enabled', etc.
		// were never added to existing installs → INSERT fails with "Unknown column 'source'".
		if ( current_user_can( 'edit_posts' ) ) {
			global $wpdb;
			$table_questions = $wpdb->prefix . 'gep_questions';

			// Columns required by the current INSERT data array — check ALL of them
			$required_columns = array( 'option_e', 'source', 'translation_enabled', 'translated_data', 'numerical_tolerance' );
			$existing_cols    = $wpdb->get_col( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table_questions'" );
			$missing          = array_diff( $required_columns, $existing_cols );

			if ( ! empty( $missing ) || get_option( 'gep_db_version' ) !== GEP_DB_VERSION ) {
				require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
				GEP_Activator::activate();
			}
		}

		if ( current_user_can( 'manage_options' ) ) {

			// Run deferred activation tasks safely when admin is fully loaded
			if ( get_option( 'gep_run_activation_tasks' ) ) {
				require_once GEP_PLUGIN_DIR . 'includes/class-gep-polisher.php';
				GEP_Polisher::polish();

				if ( ! get_option( 'gep_data_seeded' ) ) {
					require_once GEP_PLUGIN_DIR . 'admin/class-gep-seeder.php';
					GEP_Seeder::seed_test_data();
					update_option( 'gep_data_seeded', 1 );
				}

				update_option( 'gep_ai_unlocked', 1 );
				delete_option( 'gep_run_activation_tasks' );
			}
		}

		if ( isset( $_GET['action'] ) ) {
			if ( $_GET['action'] === 'reset_data' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Unauthorized' );
				}
				if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_reset_all' ) ) {
					wp_redirect( admin_url( 'admin.php?page=gep-settings&error=nonce' ) );
					exit;
				}
				$data_admin = new GEP_Admin_Data();
				if ( $data_admin->reset_all_data() ) {
					wp_redirect( admin_url( 'admin.php?page=gep-settings&message=reset_success' ) );
					exit;
				}
			}
			
			if ( $_GET['action'] === 'export_attempts' ) {
				if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_export' ) ) {
					wp_redirect( admin_url( 'admin.php?page=gep-attempts&error=nonce' ) );
					exit;
				}
				$data_admin = new GEP_Admin_Data();
				$data_admin->export_attempts();
			}

			if ( $_GET['action'] === 'delete_violation' ) {
				$this->delete_violation();
			}

			if ( $_GET['action'] === 'purge_violations' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Unauthorized' );
				}
				if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_purge_violations' ) ) {
					wp_redirect( admin_url( 'admin.php?page=gep-violations&error=nonce' ) );
					exit;
				}
				global $wpdb;
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}gep_violations" );
				wp_redirect( admin_url( 'admin.php?page=gep-violations&message=purged' ) );
				exit;
			}

			if ( $_GET['action'] === 'seed_test_data' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Unauthorized' );
				}
				// SECURITY FIX: Add nonce verification for seed action
				if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_seed_data' ) ) {
					wp_redirect( admin_url( 'admin.php?page=gep-dashboard&error=nonce' ) );
					exit;
				}
				require_once GEP_PLUGIN_DIR . 'admin/class-gep-seeder.php';
				GEP_Seeder::seed_test_data();
				wp_redirect( admin_url( 'admin.php?page=gep-dashboard&seeded=1' ) );
				exit;
			}

			if ( $_GET['action'] === 'update_db' ) {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Unauthorized' );
				}
				if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'gep_update_db' ) ) {
					wp_redirect( admin_url( 'admin.php?page=gep-settings&error=nonce' ) );
					exit;
				}
				require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
				GEP_Activator::activate();
				wp_redirect( admin_url( 'admin.php?page=gep-settings&message=db_updated' ) );
				exit;
			}
		}

		// Core Module Handlers
		(new GEP_Admin_Questions())->handle_question_actions();
		(new GEP_Admin_Tests())->handle_test_actions();
		(new GEP_Admin_Categories())->handle_category_actions();
		(new GEP_Admin_Coupons())->handle_coupon_actions();
		
		// Course & Lesson Handlers
		(new GEP_Admin_Courses())->handle_actions();
		(new GEP_Admin_Lessons())->handle_actions();
		(new GEP_Admin_Live())->handle_actions();
		(new GEP_Admin_Lectures())->handle_actions();
		(new GEP_Admin_Notifications())->handle_actions();
		(new GEP_Admin_Attempts())->handle_actions();
	}
}
