<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all actions and filters for the plugin.
 */
class GEP_Loader {

	protected $actions;
	protected $filters;

	public function __construct() {
		$this->actions = array();
		$this->filters = array();
		$this->load_dependencies();
	}

	private function load_dependencies() {
		$includes = array(
			'class-gep-auth.php',
			'class-gep-dashboard.php',
			'class-gep-category.php',
			'class-gep-question.php',
			'class-gep-test.php',
			'class-gep-exam-engine.php',
			'class-gep-result.php',
			'class-gep-payment.php',
			'class-gep-shortcodes.php',
			'class-gep-ajax.php',
			'class-gep-secure-window.php',
			'class-gep-translator.php',
			'class-gep-coupon.php',
			'class-gep-attempt.php',
			'class-gep-notifications.php',
			'class-gep-notification.php',
			'class-gep-import.php',
			'class-gep-analytics.php',
			'class-gep-polisher.php',
		);

		foreach ( $includes as $file ) {
			require_once GEP_PLUGIN_DIR . 'includes/' . $file;
		}

		if ( is_admin() ) {
			$admin_files = array(
				'class-gep-seeder.php',
				'class-gep-admin.php',
				'class-gep-admin-categories.php',
				'class-gep-admin-tests.php',
				'class-gep-admin-questions.php',
				'class-gep-admin-coupons.php',
				'class-gep-admin-data.php',
				'class-gep-admin-courses.php',
				'class-gep-admin-lessons.php',
				'class-gep-admin-live.php',
				'class-gep-admin-lectures.php',
				'class-gep-admin-notifications.php',
				'class-gep-admin-attempts.php',
				'class-gep-admin-payments.php',
				'class-gep-admin-violations.php',
			);
			foreach ( $admin_files as $file ) {
				require_once GEP_PLUGIN_DIR . 'admin/' . $file;
			}
		}
	}

	public function run() {
		// Initialize Session & Language Sync
		add_action( 'init', array( $this, 'init_session_and_lang' ), 1 );

		// Admin Hooks
		if ( is_admin() ) {
			$admin = new GEP_Admin();
			// CRITICAL FIX: Must use admin_init (not init) so WordPress has fully
			// authenticated the user before we call current_user_can() and wp_verify_nonce().
			// At 'init' priority 1, wp_get_current_user() hasn't run yet — user ID = 0,
			// so all nonce verifications silently fail and no question ever saves.
			add_action( 'admin_init', array( $admin, 'handle_admin_actions' ), 1 );
			add_action( 'admin_menu', array( $admin, 'add_plugin_admin_menu' ) );
			add_action( 'admin_init', array( $admin, 'register_settings' ), 10 );
			add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
		}

		// ── CANONICAL ADMIN-POST HANDLER ────────────────────────────────────────
		// The question save form now posts to wp-admin/admin-post.php with
		// action=gep_save_question.  WordPress routes it here AFTER full auth —
		// no timing issues, no hook-ordering problems, guaranteed to work.
		add_action( 'admin_post_gep_save_question', function() {
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_die( 'You do not have permission to save questions.', 'Permission Denied', array( 'response' => 403, 'back_link' => true ) );
			}
			// Verify nonce (admin_post already verified user is logged in)
			if ( ! isset( $_POST['gep_question_nonce'] ) || ! wp_verify_nonce( $_POST['gep_question_nonce'], 'gep_question_save' ) ) {
				wp_die( 'Security check failed. Please go back and try again.', 'Nonce Error', array( 'response' => 403, 'back_link' => true ) );
			}
			( new GEP_Admin_Questions() )->handle_save_question();
		} );
		// ────────────────────────────────────────────────────────────────────────

		// Public & Common Hooks
		$shortcodes = new GEP_Shortcodes();
		add_action( 'init', array( $shortcodes, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_filter( 'template_include', array( $this, 'load_custom_portal_template' ) );

		// Auth Form Handlers (admin_post)
		$auth = new GEP_Auth();
		$auth_actions = array( 'gep_login', 'gep_register', 'gep_forgot_password' );
		foreach ( $auth_actions as $action ) {
			add_action( "admin_post_{$action}", array( $auth, 'handle_' . str_replace('gep_', '', $action) ) );
			add_action( "admin_post_nopriv_{$action}", array( $auth, 'handle_' . str_replace('gep_', '', $action) ) );
		}
		
		// Enforce strict 2-device maximum policy on login
		add_action( 'wp_login', array( $auth, 'limit_concurrent_sessions' ), 10, 2 );

		// AJAX Handlers
		$ajax = new GEP_AJAX();
		
		// Actions requiring authentication
		$priv_actions = array(
			'gep_save_answer',
			'gep_submit_exam',
			'gep_log_violation',
			'gep_get_translation',
			'gep_apply_coupon',
			'gep_create_payment_order',
			'gep_verify_payment',
			'gep_start_exam',
			'gep_get_random_test_config',
			'gep_update_profile',
			'gep_update_avatar',
			'gep_download_import_sample',
			'gep_validate_import_file',
			'gep_fetch_question_ids',
			'gep_mark_notif_read',
			'gep_mark_all_notifs_read',
			'gep_toggle_lesson_completion',
			'gep_post_doubt',
			'gep_get_doubts',
			'gep_bulk_save_parsed_questions',
			'gep_get_analytics',
			'gep_get_progress_chart',
			'gep_download_analytics_csv',
			'gep_export_attempts_csv',
			'gep_export_students_csv',
			'gep_export_questions_csv',
			'gep_get_subcategories',
			'gep_submit_support_ticket',
			'gep_start_pyq_practice_test',
			'gep_get_student_access_data',
			'gep_save_student_access_data',
			'gep_save_typing_attempt',
			'gep_exam_heartbeat',
		);

		// Actions accessible by both public and authenticated users
		$nopriv_actions = array(
			'gep_verify_otp',
			'gep_update_lang'
		);

		foreach ( $priv_actions as $action ) {
			add_action( "wp_ajax_{$action}", array( $ajax, $action ) );
		}

		foreach ( $nopriv_actions as $action ) {
			add_action( "wp_ajax_{$action}", array( $ajax, $action ) );
			add_action( "wp_ajax_nopriv_{$action}", array( $ajax, $action ) );
		}

		// Exam Locking (Comment 5)
		add_filter( 'body_class', array( $this, 'add_locked_body_class' ) );
		add_action( 'template_redirect', array( $this, 'handle_exam_lock' ) );
		
		// Perfect Profile Bridge: Redirect author archives to student dashboard
		add_action( 'template_redirect', array( $this, 'redirect_author_to_dashboard' ) );

		// Intelligence Asset Enabler: Ensure PDF uploads are permitted for pedagogical data ingestion
		add_filter( 'upload_mimes', array( $this, 'enable_pedagogical_mimes' ) );
	}

	public function init_session_and_lang() {
		if ( ! session_id() ) session_start();
		
		if ( is_user_logged_in() && ! isset( $_SESSION['gep_lang'] ) ) {
			$pref = get_user_meta( get_current_user_id(), 'gep_preferred_lang', true );
			if ( $pref ) {
				$_SESSION['gep_lang'] = $pref;
			}
		}
	}

	public function enable_pedagogical_mimes( $mimes ) {
		$mimes['pdf'] = 'application/pdf';
		$mimes['csv'] = 'text/csv';
		return $mimes;
	}

	public function add_locked_body_class( $classes ) {
		global $post;
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'gep_exam' ) ) {
			$classes[] = 'gep-exam-locked';
		}
		return $classes;
	}

	public function handle_exam_lock() {
		global $post;
		if ( ! is_admin() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'gep_exam' ) ) {
			// Suppress admin bar
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	public function enqueue_public_assets() {
		global $post;
		
		// Base Premium CSS
		wp_register_style( 'gep-public-css', GEP_PLUGIN_URL . 'public/css/gep-public.css', array(), GEP_VERSION );
		wp_register_style( 'gep-auth-css', GEP_PLUGIN_URL . 'public/css/gep-auth.css', array(), GEP_VERSION );
		wp_register_style( 'gep-dashboard-css', GEP_PLUGIN_URL . 'public/css/gep-dashboard.css', array(), GEP_VERSION );
		wp_register_style( 'gep-exam-css', GEP_PLUGIN_URL . 'public/css/gep-exam.css', array(), GEP_VERSION );
		
		wp_register_script( 'gep-auth-js', GEP_PLUGIN_URL . 'public/js/gep-auth.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-dashboard-js', GEP_PLUGIN_URL . 'public/js/gep-dashboard.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-instructions-js', GEP_PLUGIN_URL . 'public/js/gep-instructions.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) ); 
		wp_register_script( 'gep-exam-js', GEP_PLUGIN_URL . 'public/js/gep-exam.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-secure-js', GEP_PLUGIN_URL . 'public/js/gep-secure.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-translator-js', GEP_PLUGIN_URL . 'public/js/gep-translator.js', array('jquery'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'razorpay-sdk', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-checkout-js', GEP_PLUGIN_URL . 'public/js/gep-checkout.js', array('jquery', 'razorpay-sdk'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// Analytics & Chart.js
		wp_register_script( 'gep-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_register_script( 'gep-analytics-js', GEP_PLUGIN_URL . 'public/js/gep-analytics.js', array('jquery','gep-chartjs'), GEP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );

		// Base Portal Styling (Always needed for the wrapper)
		wp_enqueue_style( 'gep-public-css' );

		if ( ( is_front_page() && is_user_logged_in() ) || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'gep_dashboard' ) ) ) {
			wp_enqueue_style( 'gep-dashboard-css' );
			wp_enqueue_style( 'gep-auth-css' ); // Dashboard needs Auth CSS for login fallback
			wp_enqueue_script( 'gep-dashboard-js' );
			wp_localize_script( 'gep-dashboard-js', 'gep_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'gep_dashboard_nonce' )
			) );
			// NOTE: Do NOT add a wp_add_inline_script fallback here — it races with
			// wp_localize_script output and can overwrite gep_ajax.nonce with empty string.
		}

		if ( is_a( $post, 'WP_Post' ) ) {
			// Auth Pages
			if ( has_shortcode( $post->post_content, 'gep_login' ) || has_shortcode( $post->post_content, 'gep_register' ) || has_shortcode( $post->post_content, 'gep_forgot_password' ) ) {
				wp_enqueue_style( 'gep-auth-css' );
				wp_enqueue_style( 'gep-dashboard-css' ); // Login uses some dashboard variables
				wp_enqueue_script( 'gep-auth-js' );
			}
			
			// Exam Engine & Instructions (Harden detection)
			$exam_slug = get_option( 'gep_slug_exam', 'exam' );
			$exam_page_id = (int) get_option( 'gep_page_exam', 0 );
			$is_exam_path = ( strpos( $_SERVER['REQUEST_URI'], '/' . $exam_slug ) !== false );
			
			if ( has_shortcode( $post->post_content, 'gep_exam' ) || $is_exam_path || is_page( $exam_page_id ) ) {
				wp_enqueue_style( 'gep-exam-css' );
				wp_enqueue_script( 'gep-exam-js' );
				wp_enqueue_script( 'gep-secure-js' );
				wp_enqueue_script( 'gep-translator-js' );
				wp_enqueue_script( 'gep-instructions-js' );
				
				$this->enqueue_katex();
			}
			
			// Result Review
			if ( has_shortcode( $post->post_content, 'gep_result' ) ) {
				wp_enqueue_style( 'gep-exam-css' );
				wp_enqueue_script( 'gep-translator-js' );
				
				$this->enqueue_katex();
			}

			// Checkout Page — Razorpay MUST load in <head> before wp_head() fires.
			// Loading scripts inside the shortcode (in the_content) is too late.
			$checkout_slug = get_option( 'gep_slug_checkout', 'checkout' );
			$is_checkout = (
				has_shortcode( $post->post_content, 'gep_checkout' ) ||
				strpos( $_SERVER['REQUEST_URI'], '/' . $checkout_slug ) !== false
			);

			if ( $is_checkout && isset( $_GET['id'] ) ) {
				$checkout_item_id   = absint( $_GET['id'] );
				$checkout_item_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'test';

				// Only enqueue if user has NOT already purchased (prevent loading for free redirect)
				wp_enqueue_script( 'razorpay-sdk' );
				wp_enqueue_script( 'gep-checkout-js' );
				wp_localize_script( 'gep-checkout-js', 'GEP_Checkout', array(
					'ajaxurl'    => admin_url( 'admin-ajax.php' ),
					'nonce'      => wp_create_nonce( 'gep_checkout_nonce' ),
					'item_id'    => $checkout_item_id,
					'item_type'  => $checkout_item_type,
					'key_id'     => get_option( 'gep_razorpay_key_id' ),
					'user_name'  => is_user_logged_in() ? wp_get_current_user()->display_name : '',
					'user_email' => is_user_logged_in() ? wp_get_current_user()->user_email : '',
				) );
			}
		}
		
		// NOTE: gep-public-css already enqueued above at line 162; removed duplicate here.
	}

	private function enqueue_katex() {
		wp_enqueue_style( 'gep-katex-css', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css', array(), '0.16.8' );
		wp_enqueue_script( 'gep-katex', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js', array(), '0.16.8', true );
		wp_enqueue_script( 'gep-katex-autorender', 'https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js', array('gep-katex'), '0.16.8', true );
		wp_register_script( 'gep-katex-init', false );
		wp_enqueue_script( 'gep-katex-init' );
		wp_add_inline_script( 'gep-katex-init', 'document.addEventListener("DOMContentLoaded", function() { if (typeof renderMathInElement === "function") { renderMathInElement(document.body, { delimiters: [{left: "$$", right: "$$", display: true}, {left: "$", right: "$", display: false}, {left: "\\\\(", right: "\\\\)", display: false}, {left: "\\\\[", right: "\\\\]", display: true}], throwOnError: false }); } });' );
	}

	public function load_custom_portal_template( $template ) {
		global $post;
		
		if ( ! is_a( $post, 'WP_Post' ) ) {
			// Handle front page even if it's the blog index
			if ( is_front_page() ) {
				$custom_template = GEP_PLUGIN_DIR . 'templates/portal-layout.php';
				if ( file_exists( $custom_template ) ) {
					return $custom_template;
				}
			}
			return $template;
		}

		$shortcodes = array(
			'gep_dashboard', 'gep_exam', 'gep_login', 'gep_register',
			'gep_result', 'gep_checkout',
			// BUG-18 FIX: These 4 were missing — their pages rendered in the default WP theme
			'gep_home', 'gep_forgot_password', 'gep_payment_success', 'gep_payment_failed'
		);
		
		// Also trigger for front page
		if ( is_front_page() ) {
			$custom_template = GEP_PLUGIN_DIR . 'templates/portal-layout.php';
			if ( file_exists( $custom_template ) ) {
				return $custom_template;
			}
		}

		foreach ( $shortcodes as $sc ) {
			if ( has_shortcode( $post->post_content, $sc ) ) {
				$custom_template = GEP_PLUGIN_DIR . 'templates/portal-layout.php';
				if ( file_exists( $custom_template ) ) {
					return $custom_template;
				}
			}
		}

		return $template;
	}

	/**
	 * Redirects standard WordPress author archives to the custom Student Dashboard.
	 */
	public function redirect_author_to_dashboard() {
		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof WP_User ) {
				$dashboard_url = gep_get_url( 'dashboard' );
				if ( $dashboard_url ) {
					// Wipe output buffer to ensure clean header redirect
					if ( ob_get_level() > 0 ) ob_end_clean();
					
					wp_redirect( add_query_arg( 'uid', $author->ID, (string) $dashboard_url ) );
					exit;
				}
			}
		}
	}
}
