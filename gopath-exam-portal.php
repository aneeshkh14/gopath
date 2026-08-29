<?php
/**
 * Plugin Name:       GoPath Exam Portal
 * Plugin URI:        https://test.gopath.in/
 * Description:       A comprehensive, large-scale modular exam management system for WordPress.
 * Version:           1.8.2
 * Author:            GoPath
 * Author URI:        https://test.gopath.in/
 * License:           GPL-2.0+
 * Text Domain:       gopath-exam-portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Catch fatal errors and log them
register_shutdown_function( function() {
	$error = error_get_last();
	if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
		$log = dirname( __FILE__ ) . '/fatal_error.log';
		$msg = date('Y-m-d H:i:s') . ' FATAL: ' . $error['message'] . ' in ' . $error['file'] . ' line ' . $error['line'] . PHP_EOL;
		file_put_contents( $log, $msg, FILE_APPEND );
	}
} );

/**
 * Helper function to get the full URL for portal pages.
 */
if ( ! function_exists( 'gep_get_url' ) ) {
	function gep_get_url( $key ) {
		$slugs = array(
			'dashboard'    => get_option( 'gep_slug_dashboard', 'dashboard' ),
			'exam'         => get_option( 'gep_slug_exam', 'exam' ),
			'result'       => get_option( 'gep_slug_result', 'result' ),
			'checkout'     => get_option( 'gep_slug_checkout', 'checkout' ),
			'login'        => get_option( 'gep_slug_login', 'login' ),
			'register'     => get_option( 'gep_slug_register', 'register' ),
			'browse-tests' => get_option( 'gep_slug_dashboard', 'dashboard' ) . '/?view=tests',
			'my-purchases' => get_option( 'gep_slug_dashboard', 'dashboard' ) . '/?view=purchases',
		);
		$slug = isset( $slugs[ $key ] ) ? $slugs[ $key ] : $key;
		return home_url( '/' . $slug );
	}
}

/**
 * Helper to get the user ID for the current context (handles admin impersonation).
 */
if ( ! function_exists( 'gep_get_context_user_id' ) ) {
	function gep_get_context_user_id() {
		$current_id = get_current_user_id();
		if ( current_user_can( 'manage_options' ) && isset( $_GET['uid'] ) ) {
			return absint( $_GET['uid'] );
		}
		return $current_id;
	}
}

// Define standard WordPress constants if missing
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! defined( 'HOUR_IN_SECONDS' ) )   { define( 'HOUR_IN_SECONDS',   3600 ); }
if ( ! defined( 'DAY_IN_SECONDS' ) )    { define( 'DAY_IN_SECONDS',    86400 ); }
if ( ! defined( 'WEEK_IN_SECONDS' ) )   { define( 'WEEK_IN_SECONDS',   604800 ); }
if ( ! defined( 'MONTH_IN_SECONDS' ) )  { define( 'MONTH_IN_SECONDS',  2592000 ); }
if ( ! defined( 'YEAR_IN_SECONDS' ) )   { define( 'YEAR_IN_SECONDS',   31536000 ); }

/**
 * Plugin version constant.
 */
define( 'GEP_VERSION', '1.8.2' );

/**
 * Database version.
 */
define( 'GEP_DB_VERSION', '1.1.8' );

/**
 * The path to the plugin directory.
 */
define( 'GEP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The URL to the plugin directory.
 */
define( 'GEP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The main plugin file path.
 */
define( 'GEP_PLUGIN_FILE', __FILE__ );

/**
 * The code that runs during plugin activation.
 */
function activate_gep_exam_portal() {
	require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
	GEP_Activator::activate();
	update_option( 'gep_db_version', GEP_DB_VERSION );
}

add_action( 'admin_init', 'gep_auto_update_db' );
function gep_auto_update_db() {
	if ( get_option( 'gep_db_version' ) !== GEP_DB_VERSION ) {
		activate_gep_exam_portal();
	}
}

/**
 * The code that runs during plugin deactivation.
 */
function gep_activate_plugin() {
	require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
	GEP_Activator::activate();
}
register_activation_hook( __FILE__, 'gep_activate_plugin' );

function gep_deactivate_plugin() {
	require_once GEP_PLUGIN_DIR . 'includes/class-gep-deactivator.php';
	GEP_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'gep_deactivate_plugin' );

/**
 * Add Plugin Favicon to Browser Tab
 */
function gep_add_plugin_favicon() {
	echo '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">' . "\n";
	if ( ! is_admin() ) {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
	}
}
add_action( 'wp_head', 'gep_add_plugin_favicon' );
add_action( 'admin_head', 'gep_add_plugin_favicon' );

/**
 * Add SEO Meta Tags for custom portal views
 */
function gep_add_seo_meta_tags() {
	// Google Site Verification Tag
	$google_key = get_option( 'gep_google_verification' );
	if ( ! empty( $google_key ) ) {
		if ( preg_match( '/content="([^"]+)"/', $google_key, $match ) ) {
			$google_key = $match[1];
		} else {
			$google_key = sanitize_text_field( $google_key );
		}
		echo '<meta name="google-site-verification" content="' . esc_attr( $google_key ) . '" />' . "\n";
	}

	if ( ! is_page() ) return;
	
	global $post;
	$dashboard_id = (int) get_option( 'gep_page_dashboard', 0 );
	$exam_id      = (int) get_option( 'gep_page_exam', 0 );
	
	if ( $post instanceof WP_Post && $post->ID === $dashboard_id ) {
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'main';
		$titles = array(
			'main'           => 'Student Dashboard',
			'tests'          => 'Test Series',
			'purchases'      => 'My Purchases',
			'live-classes'   => 'Live Classes',
			'supercoaching'  => 'SuperCoaching',
			'skill-academy'  => 'Skill Academy',
			'rank-predictor' => 'Rank Predictor',
			'results'        => 'My Results',
			'profile'        => 'My Profile',
			'support'        => 'Help & Support'
		);
		$title = isset( $titles[$view] ) ? $titles[$view] : 'Dashboard';
		$desc = "Access your " . esc_attr($title) . " on GoPath Exam Portal. Evaluate your skills, track performance, and prepare for your exams efficiently.";
		
		echo '<meta name="description" content="' . $desc . '" />' . "\n";
		
		// Canonical URL
		$canonical = add_query_arg( 'view', $view, get_permalink( $dashboard_id ) );
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		
		// Open Graph Tags
		echo '<meta property="og:title" content="' . esc_attr( $title ) . ' - ' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
		echo '<meta property="og:image" content="' . esc_url( GEP_PLUGIN_URL . 'assets/images/gep-logo.png' ) . '" />' . "\n";
		
		// Schema.org Structured Data
		if ( in_array( $view, array( 'supercoaching', 'skill-academy', 'tests' ) ) ) {
			$list_schema = array(
				'@context' => 'https://schema.org',
				'@type' => 'ItemList',
				'name' => $title . ' Catalog',
				'description' => 'Explore professional education resources and practice test series.',
				'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
				'numberOfItems' => 50,
				'provider' => array(
					'@type' => 'Organization',
					'name' => get_bloginfo( 'name' ),
					'url' => home_url()
				)
			);
			echo '<script type="application/ld+json">' . json_encode( $list_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		}
	} elseif ( $post instanceof WP_Post && $post->ID === $exam_id ) {
		$test_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $test_id ) {
			global $wpdb;
			$test_row = $wpdb->get_row( $wpdb->prepare( "SELECT title, duration FROM {$wpdb->prefix}gep_tests WHERE id = %d", $test_id ) );
			if ( $test_row ) {
				$desc = "Attempt " . esc_attr( $test_row->title ) . " mock test on GoPath Exam Portal. Real-time test engine with advanced analytics, rank predictor, and step-by-step solutions.";
				echo '<meta name="description" content="' . $desc . '" />' . "\n";
				
				// Canonical URL
				$canonical = add_query_arg( 'id', $test_id, get_permalink( $exam_id ) );
				echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
				
				// Open Graph Tags
				echo '<meta property="og:title" content="' . esc_attr( $test_row->title ) . '" />' . "\n";
				echo '<meta property="og:description" content="' . $desc . '" />' . "\n";
				echo '<meta property="og:type" content="article" />' . "\n";
				echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
				echo '<meta property="og:image" content="' . esc_url( GEP_PLUGIN_URL . 'assets/images/gep-logo.png' ) . '" />' . "\n";
				
				// Quiz / Exam Schema
				$quiz_schema = array(
					'@context' => 'https://schema.org',
					'@type' => 'Quiz',
					'name' => $test_row->title,
					'description' => 'Online Mock Test Practice Series',
					'educationalUse' => 'Practice Test',
					'timeRequired' => 'PT' . intval( $test_row->duration ) . 'M',
					'provider' => array(
						'@type' => 'Organization',
						'name' => get_bloginfo( 'name' ),
						'url' => home_url()
					)
				);
				echo '<script type="application/ld+json">' . json_encode( $quiz_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
			}
		}
	}
}
add_action( 'wp_head', 'gep_add_seo_meta_tags', 1 );

function gep_custom_document_title( $title ) {
	if ( ! is_page() ) return $title;
	
	global $post;
	$dashboard_id = (int) get_option( 'gep_page_dashboard', 0 );
	$exam_id      = (int) get_option( 'gep_page_exam', 0 );
	
	if ( $post instanceof WP_Post && $post->ID === $dashboard_id ) {
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'main';
		$titles = array(
			'main'           => 'Dashboard',
			'tests'          => 'Test Series',
			'purchases'      => 'My Purchases',
			'live-classes'   => 'Live Classes',
			'supercoaching'  => 'SuperCoaching',
			'skill-academy'  => 'Skill Academy',
			'rank-predictor' => 'Rank Predictor',
			'results'        => 'Results',
			'profile'        => 'Profile',
			'support'        => 'Support'
		);
		if ( isset( $titles[$view] ) ) {
			return $titles[$view] . ' - ' . get_bloginfo( 'name' );
		}
	} elseif ( $post instanceof WP_Post && $post->ID === $exam_id ) {
		$test_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $test_id ) {
			global $wpdb;
			$test_title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$wpdb->prefix}gep_tests WHERE id = %d", $test_id ) );
			if ( $test_title ) {
				return $test_title . ' - ' . get_bloginfo( 'name' );
			}
		}
	}
	
	return $title;
}
add_filter( 'pre_get_document_title', 'gep_custom_document_title', 999 );

function gep_custom_wp_title( $title, $sep ) {
	if ( ! is_page() ) return $title;
	
	global $post;
	$dashboard_id = (int) get_option( 'gep_page_dashboard', 0 );
	$exam_id      = (int) get_option( 'gep_page_exam', 0 );
	
	if ( $post instanceof WP_Post && $post->ID === $dashboard_id ) {
		$view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'main';
		$titles = array(
			'main'           => 'Dashboard',
			'tests'          => 'Test Series',
			'purchases'      => 'My Purchases',
			'live-classes'   => 'Live Classes',
			'supercoaching'  => 'SuperCoaching',
			'skill-academy'  => 'Skill Academy',
			'rank-predictor' => 'Rank Predictor',
			'results'        => 'Results',
			'profile'        => 'Profile',
			'support'        => 'Support'
		);
		if ( isset( $titles[$view] ) ) {
			return $titles[$view] . ' ' . $sep . ' ' . get_bloginfo( 'name' );
		}
	} elseif ( $post instanceof WP_Post && $post->ID === $exam_id ) {
		$test_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		if ( $test_id ) {
			global $wpdb;
			$test_title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$wpdb->prefix}gep_tests WHERE id = %d", $test_id ) );
			if ( $test_title ) {
				return $test_title . ' ' . $sep . ' ' . get_bloginfo( 'name' );
			}
		}
	}
	
	return $title;
}
add_filter( 'wp_title', 'gep_custom_wp_title', 999, 2 );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require GEP_PLUGIN_DIR . 'includes/class-gep-loader.php';

/**
 * Begins execution of the plugin.
 */
function run_gep_exam_portal() {
	$loader = new GEP_Loader();
	$loader->run();
}
run_gep_exam_portal();

// Table sanitization & rendering helpers
add_filter( 'wp_kses_allowed_html', 'gep_allow_table_attributes', 10, 2 );
function gep_allow_table_attributes( $allowedtags, $context ) {
	if ( $context === 'post' ) {
		$table_tags = array( 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'colgroup', 'col' );
		foreach ( $table_tags as $tag ) {
			if ( ! isset( $allowedtags[$tag] ) ) {
				$allowedtags[$tag] = array();
			}
			$allowedtags[$tag]['style'] = true;
			$allowedtags[$tag]['class'] = true;
			$allowedtags[$tag]['id'] = true;
			$allowedtags[$tag]['border'] = true;
			$allowedtags[$tag]['cellpadding'] = true;
			$allowedtags[$tag]['cellspacing'] = true;
			$allowedtags[$tag]['width'] = true;
			$allowedtags[$tag]['height'] = true;
			$allowedtags[$tag]['align'] = true;
			$allowedtags[$tag]['valign'] = true;
			$allowedtags[$tag]['colspan'] = true;
			$allowedtags[$tag]['rowspan'] = true;
		}
	}
	return $allowedtags;
}

if ( ! function_exists( 'gep_clean_wpautop_tables' ) ) {
	function gep_clean_wpautop_tables( $html ) {
		if ( strpos( $html, '<table' ) !== false ) {
			$html = preg_replace( '/<p>\s*<table/i', '<table', $html );
			$html = preg_replace( '/<\/table>\s*<\/p>/i', '</table>', $html );
			$html = preg_replace( '/(<\/tr>|<table[^>]*>|<tr>|<thead>|<tbody>|<tfoot>)\s*<br\s*\/?>/i', '$1', $html );
			$html = preg_replace( '/<br\s*\/?>\s*(<tr>|<\/tr>|<td>|<\/td>|<th>|<\/th>|<\/table>)/i', '$1', $html );
		}
		return $html;
	}
}

if ( ! function_exists( 'gep_seeded_shuffle' ) ) {
	function gep_seeded_shuffle( &$array ) {
		$count = count( $array );
		for ( $i = $count - 1; $i > 0; $i-- ) {
			$j = mt_rand( 0, $i );
			$tmp = $array[$i];
			$array[$i] = $array[$j];
			$array[$j] = $tmp;
		}
	}
}

if ( ! function_exists( 'gep_safe_json_decode' ) ) {
	function gep_safe_json_decode( $data, $assoc = true ) {
		if ( empty( $data ) ) {
			return array();
		}
		if ( is_array( $data ) || is_object( $data ) ) {
			return (array) $data;
		}
		$decoded = json_decode( $data, $assoc );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded;
		}
		$decoded_strip = json_decode( stripslashes( $data ), $assoc );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded_strip;
		}
		$clean_data = stripslashes( html_entity_decode( $data ) );
		$decoded_clean = json_decode( $clean_data, $assoc );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			return $decoded_clean;
		}
		return array();
	}
}

// Dynamic & Physical XML Sitemap Generator for Google / Bing Search Engines
if ( ! function_exists( 'gep_write_physical_sitemap' ) ) {
	function gep_write_physical_sitemap() {
		$sitemap_path = ABSPATH . 'gep-sitemap.xml';
		
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		
		// 1. Static Pages
		$pages = array( 'home', 'dashboard', 'login', 'register', 'checkout', 'terms', 'privacy' );
		foreach ( $pages as $page_key ) {
			$page_id = get_option( 'gep_page_' . $page_key );
			if ( $page_id ) {
				$url = get_permalink( $page_id );
				echo "\t" . '<url>' . "\n";
				echo "\t\t" . '<loc>' . esc_url( $url ) . '</loc>' . "\n";
				echo "\t\t" . '<changefreq>weekly</changefreq>' . "\n";
				echo "\t\t" . '<priority>0.8</priority>' . "\n";
				echo "\t" . '</url>' . "\n";
			}
		}
		
		global $wpdb;
		// 2. Dynamic Mock Tests & PYQ Papers URLs
		$exam_page_id = get_option( 'gep_page_exam' );
		if ( $exam_page_id && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}gep_tests'" ) ) {
			$exam_base_url = get_permalink( $exam_page_id );
			$tests = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}gep_tests WHERE status = 'publish'" );
			if ( ! empty( $tests ) ) {
				foreach ( $tests as $test ) {
					$url = add_query_arg( 'id', $test->id, $exam_base_url );
					echo "\t" . '<url>' . "\n";
					echo "\t\t" . '<loc>' . esc_url( $url ) . '</loc>' . "\n";
					echo "\t\t" . '<changefreq>daily</changefreq>' . "\n";
					echo "\t\t" . '<priority>0.9</priority>' . "\n";
					echo "\t" . '</url>' . "\n";
				}
			}
		}

		// 3. Dynamic Coaching Courses & Watch URLs
		$dashboard_page_id = get_option( 'gep_page_dashboard' );
		if ( $dashboard_page_id && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}gep_courses'" ) ) {
			$dash_base_url = get_permalink( $dashboard_page_id );
			$courses = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}gep_courses" );
			if ( ! empty( $courses ) ) {
				foreach ( $courses as $course ) {
					$url = add_query_arg( array( 'view' => 'watch', 'id' => $course->id ), $dash_base_url );
					echo "\t" . '<url>' . "\n";
					echo "\t\t" . '<loc>' . esc_url( $url ) . '</loc>' . "\n";
					echo "\t\t" . '<changefreq>weekly</changefreq>' . "\n";
					echo "\t\t" . '<priority>0.7</priority>' . "\n";
					echo "\t" . '</url>' . "\n";
				}
			}
		}

		echo '</urlset>' . "\n";
		$xml_content = ob_get_clean();
		
		@file_put_contents( $sitemap_path, $xml_content );
		return $xml_content;
	}
}

if ( ! function_exists( 'gep_generate_xml_sitemap' ) ) {
	function gep_generate_xml_sitemap() {
		if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'gep-sitemap.xml' ) !== false ) {
			$xml = gep_write_physical_sitemap();
			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			header( 'Content-Type: application/xml; charset=utf-8' );
			echo $xml;
			exit;
		}
	}
	add_action( 'init', 'gep_generate_xml_sitemap' );
}

if ( is_admin() ) {
	add_action( 'admin_init', 'gep_auto_write_sitemap_on_load' );
	function gep_auto_write_sitemap_on_load() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'gep-settings' ) {
			gep_write_physical_sitemap();
		}
	}
}

// Append GEP XML Sitemap URL to WordPress dynamic robots.txt rules
if ( ! function_exists( 'gep_append_robots_sitemap' ) ) {
	function gep_append_robots_sitemap( $output, $public ) {
		$sitemap_url = home_url( '/gep-sitemap.xml' );
		$output .= "\nSitemap: " . $sitemap_url . "\n";
		return $output;
	}
	add_filter( 'robots_txt', 'gep_append_robots_sitemap', 99, 2 );
}

// Dynamic Google Site Verification file responder
if ( ! function_exists( 'gep_handle_google_verification_request' ) ) {
	function gep_handle_google_verification_request() {
		if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'google5776b94ff420aca2.html' ) !== false ) {
			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}
			header( 'Content-Type: text/html; charset=utf-8' );
			echo 'google-site-verification: google5776b94ff420aca2.html';
			exit;
		}
	}
	add_action( 'init', 'gep_handle_google_verification_request' );
}

