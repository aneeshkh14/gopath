<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode registration and processing.
 */
class GEP_Shortcodes {

	public function register_shortcodes() {
		add_shortcode( 'gep_home', array( $this, 'render_home' ) );
		add_shortcode( 'gep_login', array( $this, 'render_login' ) );
		add_shortcode( 'gep_register', array( $this, 'render_register' ) );
		add_shortcode( 'gep_forgot_password', array( $this, 'render_forgot_password' ) );
		add_shortcode( 'gep_dashboard', array( $this, 'render_dashboard' ) );
		add_shortcode( 'gep_exam', array( $this, 'render_exam' ) );
		add_shortcode( 'gep_result', array( $this, 'render_result' ) );
		add_shortcode( 'gep_checkout', array( $this, 'render_checkout' ) );
		add_shortcode( 'gep_payment_success', array( $this, 'render_payment_success' ) );
		add_shortcode( 'gep_payment_failed', array( $this, 'render_payment_failed' ) );
	}

	public function render_home() {
		ob_start();
		include GEP_PLUGIN_DIR . 'templates/home.php';
		return ob_get_clean();
	}

	public function render_login() {
		ob_start();
		include GEP_PLUGIN_DIR . 'templates/auth/login.php';
		return ob_get_clean();
	}

	public function render_register() {
		ob_start();
		include GEP_PLUGIN_DIR . 'templates/auth/register.php';
		return ob_get_clean();
	}

	public function render_forgot_password() {
		ob_start();
		include GEP_PLUGIN_DIR . 'templates/auth/forgot-password.php';
		return ob_get_clean();
	}

	public function render_checkout() {
		if ( ! is_user_logged_in() ) return $this->render_login();
		
		$item_id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$item_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'test';
		
		global $wpdb;
		if ( $item_type === 'pass' ) {
			$pass_plans = array(
				1 => (object) array(
					'id'          => 1,
					'title'       => '1-Month Mock Test Pass',
					'description' => 'Get full access to all test series, exam formats, and PYQs for 30 days.',
					'price'       => 99,
				),
				2 => (object) array(
					'id'          => 2,
					'title'       => 'Yearly Mock Test Pass Pro',
					'description' => 'Get full access to all test series, exam formats, and PYQs for 365 days. Best value!',
					'price'       => 299,
				),
				3 => (object) array(
					'id'          => 3,
					'title'       => 'Lifetime Mock Test Pass Ultimate',
					'description' => 'Unlock all present and future exams and test series forever.',
					'price'       => 599,
				),
			);
			$item = isset( $pass_plans[$item_id] ) ? $pass_plans[$item_id] : null;
		} else {
			$table = ( $item_type === 'course' ) ? "{$wpdb->prefix}gep_courses" : "{$wpdb->prefix}gep_tests";
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $item_id ) );
		}

		if ( ! $item ) {
			return '<div class="gep-error">Invalid selection.</div>';
		}

		$dashboard = new GEP_Dashboard();
		$is_random = ( $item_type === 'test' && isset( $item->type ) && $item->type === 'random' );
		if ( ! $is_random && $dashboard->has_access( get_current_user_id(), $item_id, $item_type ) ) {
			$label      = ( $item_type === 'course' ) ? 'course' : 'test';
			// BUG FIX: Use ?view=purchases query param — NOT #purchases hash (hash is not read server-side by router)
			$purchases_url = esc_url( add_query_arg( 'view', 'purchases', (string) gep_get_url( 'dashboard' ) ) );
			$go_url     = ( $item_type === 'course' )
				? esc_url( add_query_arg( array( 'view' => 'watch', 'id' => $item_id ), (string) gep_get_url( 'dashboard' ) ) )
				: $purchases_url;
			$dashboard_url = esc_url( gep_get_url( 'dashboard' ) );
			return '<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px;">
				<div class="gep-glass" style="max-width:480px;width:100%;padding:56px 48px;border-radius:32px;text-align:center;">
					<div style="width:80px;height:80px;background:rgba(16,185,129,0.12);border-radius:24px;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:40px;">✅</div>
					<h2 style="font-size:26px;font-weight:900;letter-spacing:-1px;margin:0 0 12px;color:#0f172a;">You Already Have Access!</h2>
					<p style="color:#64748b;font-size:15px;line-height:1.6;margin:0 0 32px;">You have lifetime access to this ' . esc_html( $label ) . '. No need to purchase again.</p>
					<a href="' . $go_url . '" style="display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:14px;font-weight:800;font-size:15px;text-decoration:none;margin-bottom:16px;">
						' . ( $item_type === 'course' ? '▶ Start Learning' : '📝 Go to My Purchases' ) . '
					</a>
					<br>
					<a href="' . $dashboard_url . '" style="color:#6366f1;font-size:14px;font-weight:600;text-decoration:none;">← Back to Dashboard</a>
					<script>setTimeout(function(){ window.location.href="' . $go_url . '"; }, 3000);</script>
				</div>
			</div>';
		}

		ob_start();
		include GEP_PLUGIN_DIR . 'templates/payment/checkout.php';
		return ob_get_clean();
	}

	public function render_payment_success() {
		return '<div class="gep-status-page-sovereign success">
			<div class="status-card-glass gep-glass">
				<div class="status-icon-wrap success">
					<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
				</div>
				<h1>Enrollment <span class="gep-text-gradient-primary">Confirmed</span></h1>
				<p>Welcome to the inner circle. Your intelligence assets have been provisioned and your access is now active.</p>
				<div class="status-actions">
					<a href="'.gep_get_url('dashboard').'" class="gep-btn-sovereign-primary">Enter Dashboard</a>
				</div>
			</div>
		</div>
		<style>
		.gep-status-page-sovereign { min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: 40px; }
		.status-card-glass { max-width: 500px; width: 100%; padding: 60px; border-radius: 40px; text-align: center; }
		.status-icon-wrap { width: 100px; height: 100px; margin: 0 auto 30px; border-radius: 30px; display: flex; align-items: center; justify-content: center; }
		.status-icon-wrap.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
		.status-icon-wrap.failed { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
		.gep-status-page-sovereign h1 { font-size: 36px; font-weight: 950; letter-spacing: -1.5px; margin-bottom: 20px; }
		.gep-status-page-sovereign p { color: #64748b; font-size: 16px; line-height: 1.6; margin-bottom: 40px; font-weight: 500; }
		</style>';
	}

	public function render_payment_failed() {
		return '<div class="gep-status-page-sovereign failed">
			<div class="status-card-glass gep-glass">
				<div class="status-icon-wrap failed">
					<svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
				</div>
				<h1>Transmission <span style="color:#ef4444;">Interrupted</span></h1>
				<p>We encountered a protocol error during payment verification. Your transaction could not be finalized.</p>
				<div class="status-actions">
					<a href="'.gep_get_url('dashboard').'" class="gep-btn-sovereign-primary" style="background:#ef4444;">Back to Dashboard</a>
				</div>
			</div>
		</div>';
	}

	public function render_dashboard() {
		if ( ! is_user_logged_in() ) {
			return $this->render_login();
		}
		
		$dashboard = new GEP_Dashboard();
		$user_id = gep_get_context_user_id();
		
		$stats = $dashboard->get_dashboard_stats( $user_id );
		
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'main';
		
		// MARKETPLACE DATA
		$cat_id = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
		$available_tests  = $dashboard->get_available_items( 'test', 24, $cat_id, 'all' ); // Fetch ALL types for the Hub
		$available_series = $dashboard->get_available_items( 'test', 12, $cat_id, 'series' );
		$available_courses = $dashboard->get_available_items( 'course', 12, $cat_id );
		
		// MY CONTENT
		$purchased_tests  = $dashboard->get_user_purchased_items( $user_id, 'test', 'single' );
		$purchased_series = $dashboard->get_user_purchased_items( $user_id, 'test', 'series' );
		$purchased_courses = $dashboard->get_user_purchased_items( $user_id, 'course' );

		// Tag each item with its category for the template
		foreach ( (array)$purchased_tests  as &$pt ) { $pt->item_category = 'test'; }
		foreach ( (array)$purchased_series as &$ps ) { $ps->item_category = 'series'; }
		foreach ( (array)$purchased_courses as &$pc ) { $pc->item_category = 'course'; }
		unset( $pt, $ps, $pc );

		$purchases = array_merge( (array)$purchased_tests, (array)$purchased_series, (array)$purchased_courses );

		// Populate purchase status for marketplace items
		$purchased_ids = array_map(function($item) { return $item->id; }, $purchases);
		
		foreach ( $available_tests as &$t ) {
			$t->is_purchased = in_array($t->id, $purchased_ids);
		}
		foreach ( $available_series as &$s ) {
			$s->is_purchased = in_array($s->id, $purchased_ids);
		}
		
		$attempts = (new GEP_Result())->get_user_results( $user_id );
		$notifications = GEP_Notifications::get_for_user( $user_id );

		// Set variables for sub-templates
		$tests = $available_tests;
		// Only list categories that have active tests
		global $wpdb;
		$all_cats = (new GEP_Category())->get_categories(0);
		$active_cat_ids = $wpdb->get_col( "SELECT DISTINCT category_id FROM {$wpdb->prefix}gep_tests WHERE status = 'publish' AND category_id > 0" );
		$categories = array();
		foreach ( $all_cats as $cat ) {
			if ( in_array( $cat->id, $active_cat_ids ) ) {
				$categories[] = $cat;
			}
		}
		if ( empty( $categories ) ) {
			$categories = $all_cats;
		}

		// Watch Access Control: Enforce purchase-gated lessons
		// CRITICAL: Do NOT use wp_safe_redirect()+exit inside a shortcode — use early return instead
		if ( $view === 'watch' ) {
			$course_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
			if ( ! $course_id ) {
				// No course specified — redirect to supercoaching
				ob_start();
				?><script>window.location.href='<?php echo esc_url( add_query_arg( 'view', 'supercoaching', (string) gep_get_url('dashboard') ) ); ?>';</script><?php
				return ob_get_clean();
			}
			if ( ! $dashboard->has_access( $user_id, $course_id, 'course' ) ) {
				$checkout_url = esc_url( add_query_arg( array( 'id' => $course_id, 'type' => 'course' ), (string) gep_get_url( 'checkout' ) ) );
				return '<div class="gep-paywall-gate" style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px;">
					<div class="gep-glass" style="max-width:500px;width:100%;padding:60px;border-radius:32px;text-align:center;">
						<div style="font-size:64px;margin-bottom:24px;">🔒</div>
						<h2 style="font-size:28px;font-weight:900;letter-spacing:-1px;margin-bottom:12px;">Course Access Required</h2>
						<p style="color:#64748b;font-size:16px;line-height:1.6;margin-bottom:32px;">This is a premium course. Please enroll to access all lessons, videos, and study material.</p>
						<a href="' . $checkout_url . '" style="display:inline-block;padding:16px 40px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:16px;font-weight:800;font-size:16px;text-decoration:none;">Enroll Now →</a>
					</div>
				</div>';
			}
		}

		ob_start();
		
		$template_map = array(
			'main'           => 'main.php',
			'tests'          => 'browse-tests.php',
			'purchases'      => 'my-purchases.php',
			'live-classes'   => 'live-classes.php',
			'lectures'       => 'lectures.php',
			'supercoaching'  => 'supercoaching.php',
			'skill-academy'  => 'skill-academy.php',
			'rank-predictor' => 'rank-predictor.php',
			'results'        => 'results.php',
			'pyqs'           => 'pyqs.php',
			'notifications'  => 'notifications.php',
			'profile'        => 'profile.php',
			'watch'          => 'watch.php',
			'policies'       => 'policies.php',
			'support'        => 'support.php',
			'get-pass'       => 'get-pass.php',
			'typing-test'    => 'typing-test.php',
		);

		$file = isset( $template_map[$view] ) ? $template_map[$view] : 'main.php';
		include GEP_PLUGIN_DIR . 'templates/dashboard/' . $file;
		
		return ob_get_clean();
	}

	public function render_exam( $atts = array() ) {
		// Ensure session is active before any $_SESSION reads
		if ( ! session_id() ) session_start();

		if ( ! is_user_logged_in() ) {
			return $this->render_login();
		}
		
		// BUG-16 FIX: Pass actual $atts to shortcode_atts() so [gep_exam id="5"] works
		$atts = shortcode_atts( array(
			'id' => isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0,
		), $atts );

		$test_id = absint( $atts['id'] );
		if ( ! $test_id ) {
			return '<div class="gep-error" style="padding:40px;text-align:center;"><div style="font-size:48px;margin-bottom:20px;">❌</div><h3>No Test Selected</h3><p>Please select a test from your dashboard to begin.</p><a href="' . gep_get_url('dashboard') . '" style="display:inline-block;margin-top:20px;padding:12px 30px;background:var(--gep-primary,#6366f1);color:#fff;border-radius:12px;text-decoration:none;font-weight:700;">← Back to Dashboard</a></div>';
		}

		$test_logic = new GEP_Test();
		$user_id = get_current_user_id();

		// CRITICAL FIX: NEVER call exit/wp_redirect inside a shortcode — it kills the entire page.
		// Instead return an HTML redirect notice that handles the navigation client-side.
		if ( ! $test_logic->user_has_access( $user_id, $test_id ) ) {
			$checkout_url = esc_url( gep_get_url('checkout') . '?id=' . $test_id );
			return '<div class="gep-error" style="padding:40px;text-align:center;">
				<div style="font-size:48px;margin-bottom:20px;">🔒</div>
				<h3 style="font-size:22px;font-weight:900;color:#1e293b;">Access Required</h3>
				<p style="color:#64748b;font-weight:600;">You need to purchase this test to start your exam.</p>
				<a href="' . $checkout_url . '" style="display:inline-block;margin-top:20px;padding:12px 30px;background:#6366f1;color:#fff;border-radius:12px;text-decoration:none;font-weight:700;">Enroll Now →</a>
				<script>setTimeout(function(){window.location.href="' . $checkout_url . '";},2000);</script>
			</div>';
		}
		
		$test = $test_logic->get_test( $test_id );
		
		if ( ! $test ) {
			return '<div class="gep-error">Test not found.</div>';
		}
		
		// 0. Handle Test Series Portfolio View
		if ( $test->type === 'series' ) {
			$series_tests = $test_logic->get_series_tests( $test_id );
			ob_start();
			include GEP_PLUGIN_DIR . 'templates/exam/series-view.php';
			return ob_get_clean();
		}
		
		// 1. Check for active attempt
		global $wpdb;
		$attempt = $wpdb->get_row( $wpdb->prepare( 
			"SELECT * FROM {$wpdb->prefix}gep_attempts WHERE user_id = %d AND test_id = %d AND status = 'in_progress'", 
			$user_id, $test_id 
		) );

		// 2. If no attempt, show instructions
		if ( ! $attempt ) {
			$remaining = $test_logic->get_remaining_attempts( $user_id, $test_id );
			if ( $remaining <= 0 ) {
				return '<div class="gep-error">You have reached the maximum attempt limit for this test.</div>';
			}

			wp_enqueue_style( 'gep-exam-css' );
			wp_enqueue_script( 'gep-instructions-js' ); // BUG-15 FIX: Uses centrally registered handle
			
			// Initialize session if not already started
			if ( ! session_id() ) session_start();
			
			$current_lang = (isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : (get_user_meta(get_current_user_id(), 'gep_preferred_lang', true) ?: 'en'));
			$instructions = $test->instructions;
			
			if ( $current_lang === 'hi' && ! empty( $test->translated_data ) ) {
				$trans = gep_safe_json_decode( $test->translated_data, true );
				$instructions = isset($trans['instructions']) ? $trans['instructions'] : $instructions;
			}

			wp_localize_script( 'gep-instructions-js', 'GEP_Instructions', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gep_exam_nonce' ),
				'test_id' => $test_id
			) );

			ob_start();
			include GEP_PLUGIN_DIR . 'templates/exam/instructions.php';
			$out = ob_get_clean();
			return $out;
		}

		// 3. Attempt exists, load exam window
		$question_ids = $test_logic->get_test_questions( $test_id, $attempt->id );
		$question_logic = new GEP_Question();
		$questions = $question_logic->get_questions_by_ids( $question_ids );
		$questions = GEP_Exam_Engine::apply_section_overrides( $questions, $test );

		// Extract sections/categories dynamically from questions OR use explicitly defined sections
		$sections_map = array();
		$trans = ! empty( $test->translated_data ) ? gep_safe_json_decode( $test->translated_data, true ) : array();
		
		if ( isset( $trans['sections'] ) && is_array( $trans['sections'] ) && !empty($trans['sections']) ) {
			// Mode 1: Use explicitly defined Sections (Supports Sectional Timing)
			foreach ( $trans['sections'] as $idx => $sec ) {
				$sec_id = 'sec_' . $idx;
				$sections_map[ $sec_id ] = $sec['name'] ?: 'Section ' . ($idx + 1);
				
				// Map questions matching these IDs to this section
				$sec_q_ids = array_filter( array_map('absint', explode(',', $sec['ids'])) );
				foreach ( $questions as $q ) {
					if ( in_array( $q->id, $sec_q_ids ) ) {
						// Overwrite category_id with our virtual section ID so the frontend tabs match it
						$q->category_id = $sec_id;
					}
				}
			}
			
			// Catch any questions that weren't caught in the explicitly defined sections
			foreach ( $questions as $q ) {
				if ( strpos((string)$q->category_id, 'sec_') !== 0 ) {
					$q->category_id = 'sec_misc';
					$sections_map['sec_misc'] = 'Miscellaneous';
				}
			}
		} else {
			// Mode 2: Legacy fallback - Auto-detect by category_id
			if ( ! empty( $questions ) ) {
				$cat_ids = array();
				foreach ( $questions as $q ) {
					$c_id = isset( $q->category_id ) ? absint( $q->category_id ) : 0;
					$cat_ids[] = $c_id;
				}
				$cat_ids = array_unique( $cat_ids );

				if ( ! empty( $cat_ids ) ) {
					global $wpdb;
					$db_cat_ids = array_filter( $cat_ids ); // Exclude 0
					$resolved_cats = array();
					if ( ! empty( $db_cat_ids ) ) {
						$ids_str = implode( ',', $db_cat_ids );
						$cats = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE id IN ($ids_str)" );
						foreach ( $cats as $c ) {
							$resolved_cats[ $c->id ] = $c->name;
						}
					}
					foreach ( $cat_ids as $c_id ) {
						if ( $c_id === 0 ) {
							$sections_map[0] = 'General';
						} elseif ( isset( $resolved_cats[ $c_id ] ) ) {
							$sections_map[ $c_id ] = $resolved_cats[ $c_id ];
						} else {
							$sections_map[ $c_id ] = 'Subject ' . $c_id;
						}
					}
				}
			}
			if ( empty( $sections_map ) ) {
				$sections_map[0] = 'General';
			}
		}

		// ─── ENSURE QUESTIONS ARE GROUPED BY SECTION & SHUFFLED PROPERLY ───────────
		if ( ! empty( $questions ) ) {
			// ALWAYS group by section first so that the UI palette doesn't fragment and sections are contiguous.
			$grouped = array();
			// Use the order of sections_map as the master order
			foreach ( $sections_map as $sec_id => $sec_name ) {
				$grouped[ $sec_id ] = array();
			}
			$grouped['unmapped'] = array();

			foreach ( $questions as $q ) {
				$c_id = isset( $q->category_id ) ? strval( $q->category_id ) : '0';
				if ( isset( $grouped[ $c_id ] ) ) {
					$grouped[ $c_id ][] = $q;
				} else {
					// Fallback robust string key check
					$matched = false;
					foreach ( array_keys( $grouped ) as $g_key ) {
						if ( strval( $g_key ) === strval( $c_id ) ) {
							$grouped[ $g_key ][] = $q;
							$matched = true;
							break;
						}
					}
					if ( ! $matched ) {
						$grouped['unmapped'][] = $q;
					}
				}
			}

			$final_questions = array();
			$overall_idx = 0;

			foreach ( $grouped as $sec_id => $group_qs ) {
				if ( empty( $group_qs ) ) continue;

				// Determine shuffle settings for this section
				$sec_shuffle_questions = false;
				$sec_shuffle_start = null;
				$sec_shuffle_end = null;

				$sec_idx = null;
				if ( strpos( $sec_id, 'sec_' ) === 0 ) {
					$sec_idx = intval( substr( $sec_id, 4 ) );
				}

				if ( $sec_idx !== null && isset( $trans['sections'][$sec_idx] ) ) {
					$sec_conf = $trans['sections'][$sec_idx];
					$sec_shuffle_questions = isset( $sec_conf['shuffle_questions'] ) && $sec_conf['shuffle_questions'];
					$sec_shuffle_start     = isset( $sec_conf['shuffle_start'] ) && $sec_conf['shuffle_start'] !== '' ? intval( $sec_conf['shuffle_start'] ) : null;
					$sec_shuffle_end       = isset( $sec_conf['shuffle_end'] ) && $sec_conf['shuffle_end'] !== '' ? intval( $sec_conf['shuffle_end'] ) : null;
				} else {
					// Fallback to global test settings (legacy/default)
					$sec_shuffle_questions = ( isset( $test->shuffle_questions ) && $test->shuffle_questions );
					$sec_shuffle_start     = isset( $trans['shuffle_start'] ) && $trans['shuffle_start'] !== '' ? intval( $trans['shuffle_start'] ) : null;
					$sec_shuffle_end       = isset( $trans['shuffle_end'] ) && $trans['shuffle_end'] !== '' ? intval( $trans['shuffle_end'] ) : null;
				}

				if ( $sec_shuffle_questions && isset( $attempt->id ) ) {
					// Seed random number generator with attempt ID and section index for consistency
					$seed = (int) $attempt->id * 1009 + (int)$sec_idx * 97;
					mt_srand( $seed );

					if ( $sec_shuffle_start !== null || $sec_shuffle_end !== null ) {
						// Apply slice shuffling ONLY within the bounds of this section
						$total_group = count( $group_qs );
						$start_idx_in_group = null;
						$end_idx_in_group = null;

						for ( $i = 0; $i < $total_group; $i++ ) {
							$current_global_idx = $overall_idx + $i + 1; // 1-based index
							$in_range = true;
							if ( $sec_shuffle_start !== null && $current_global_idx < $sec_shuffle_start ) {
								$in_range = false;
							}
							if ( $sec_shuffle_end !== null && $current_global_idx > $sec_shuffle_end ) {
								$in_range = false;
							}

							if ( $in_range ) {
								if ( $start_idx_in_group === null ) {
									$start_idx_in_group = $i;
								}
								$end_idx_in_group = $i;
							}
						}

						if ( $start_idx_in_group !== null && $end_idx_in_group !== null && $start_idx_in_group < $end_idx_in_group ) {
							$slice = array_slice( $group_qs, $start_idx_in_group, $end_idx_in_group - $start_idx_in_group + 1 );
							gep_seeded_shuffle( $slice );
							array_splice( $group_qs, $start_idx_in_group, $end_idx_in_group - $start_idx_in_group + 1, $slice );
						}
					} else {
						gep_seeded_shuffle( $group_qs );
					}
				}

				$overall_idx += count( $group_qs );
				$final_questions = array_merge( $final_questions, $group_qs );
			}

			// Add unmapped questions at the end if any, shuffled if needed
			if ( ! empty( $grouped['unmapped'] ) ) {
				$unmapped_qs = $grouped['unmapped'];
				// Global fallback for unmapped questions
				if ( ( isset( $test->shuffle_questions ) && $test->shuffle_questions ) && isset( $attempt->id ) ) {
					gep_seeded_shuffle( $unmapped_qs );
				}
				$final_questions = array_merge( $final_questions, $unmapped_qs );
			}

			$questions = $final_questions;
		}

		// BUG-01 FIX: Hard guard — if no questions are linked, show an actionable error
		if ( empty( $questions ) ) {
			return '<div class="gep-error" style="padding:40px;text-align:center;">
				<div style="font-size:48px;margin-bottom:20px;">⚠️</div>
				<h3>Test Has No Questions</h3>
				<p>This test does not have any questions configured yet. Please contact the administrator or try a different test.</p>
				<a href="' . gep_get_url('dashboard') . '" style="display:inline-block;margin-top:20px;padding:12px 30px;background:var(--gep-primary,#6366f1);color:#fff;border-radius:12px;text-decoration:none;font-weight:700;">← Back to Dashboard</a>
			</div>';
		}

		// Load previously saved answers
		$saved_answers = json_decode( $attempt->answers, true ) ?: array();

		// Calculate remaining time — BUG-12 FIX: use time() not deprecated current_time('timestamp')
		$start_time       = strtotime( $attempt->start_time );
		$duration_seconds = $test->duration_minutes * 60;
		$elapsed_seconds  = time() - $start_time;
		$remaining_seconds = max( 0, $duration_seconds - $elapsed_seconds );

		wp_enqueue_style( 'gep-exam-css' );
		wp_enqueue_script( 'gep-exam-js' );
		wp_enqueue_script( 'gep-secure-js' );
		wp_enqueue_script( 'gep-translator-js' );

		wp_localize_script( 'gep-exam-js', 'GEP_Exam', array(
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'gep_exam_nonce' ),
			'test_id'           => $test_id,
			'attempt_id'        => $attempt->id,
			'remaining_seconds' => $remaining_seconds,
			'elapsed_seconds'   => $elapsed_seconds,
			'startTime'         => time(),
			'lang'              => isset( $_SESSION['gep_lang'] ) ? $_SESSION['gep_lang'] : 'en',
			'saved_answers'     => $saved_answers,
			'sections_data'     => isset( $trans['sections'] ) ? $trans['sections'] : array(),
			'dashboard_url'     => gep_get_url('dashboard')
		) );

		// Get Category Name for the subject tag
		$cat_logic = new GEP_Category();
		$cat = $cat_logic->get_category_by_id( $test->category_id );
		$subject_tag = $cat ? $cat->name : 'GENERAL INTELLIGENCE';

		ob_start();
		include GEP_PLUGIN_DIR . 'templates/exam/exam-window.php';
		return ob_get_clean();
	}

	public function render_result( $atts = array() ) {
		// BUG-09 FIX: Ensure session is active before any $_SESSION reads
		if ( ! session_id() ) session_start();

		if ( ! is_user_logged_in() ) {
			return $this->render_login();
		}
		
		// BUG-17 FIX: Pass actual $atts to shortcode_atts() so [gep_result id="5"] works
		$atts = shortcode_atts( array(
			'id' => isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0,
		), $atts );

		$attempt_id = absint( $atts['id'] );
		if ( ! $attempt_id ) {
			return '<div class="gep-error">Result ID is missing.</div>';
		}

		$result_logic = new GEP_Result();
		$details = $result_logic->get_attempt_details( $attempt_id );

		if ( ! $details ) {
			return '<div class="gep-error">Result not found.</div>';
		}

		// Admin Bypass: Allow admins to view any result
		if ( $details['attempt']->user_id != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return '<div class="gep-error">Access denied.</div>';
		}

		ob_start();
		// Enqueue Chart.js + Analytics JS for the result page
		wp_enqueue_script( 'gep-chartjs' );
		wp_enqueue_script( 'gep-analytics-js' );
		include GEP_PLUGIN_DIR . 'templates/exam/result-review.php';
		return ob_get_clean();
	}
}

