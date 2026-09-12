<?php
/**
 * Plugin Name:       GoPath Exam Portal
 * Plugin URI:        https://test.gopath.in/
 * Description:       A comprehensive, large-scale modular exam management system for WordPress.
 * Version:           2.0.0
 * Author:            GoPath
 * Author URI:        https://test.gopath.in/
 * License:           GPL-2.0+
 * Text Domain:       gopath-exam-portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guard against a second copy of this plugin being loaded.
 *
 * None of this plugin's classes/functions are declared conditionally, so if two
 * copies are active at once (e.g. the original `gopath-exam-portal` folder plus a
 * second folder created by uploading a differently-named ZIP), PHP dies with a
 * "Cannot redeclare class GEP_Loader" fatal and WordPress only reports
 * "Plugin could not be activated because it triggered a fatal error."
 *
 * Bail out cleanly instead, and tell the admin exactly what happened.
 */
if ( defined( 'GEP_VERSION' ) ) {
	add_action( 'admin_notices', function() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>GoPath Exam Portal:</strong> '
			. esc_html__( 'Another copy of this plugin is already active, so this copy was not loaded. Deactivate and delete the duplicate copy under Plugins, then keep only one.', 'gopath-exam-portal' )
			. '</p></div>';
	} );
	return;
}

/**
 * Diagnostic log file, kept OUT of the web-served plugin directory.
 *
 * This log previously lived at wp-content/plugins/gopath-exam-portal/fatal_error.log,
 * which any visitor could download over HTTP. It records PHP stack traces, absolute
 * server paths and (from the question-save debug lines) question content — i.e. paid
 * exam material. It now lives in an uploads subfolder guarded by .htaccess/index.php,
 * and is capped so it cannot grow without bound.
 */
if ( ! function_exists( 'gep_log_file' ) ) {
	function gep_log_file() {
		$uploads = wp_get_upload_dir();
		if ( empty( $uploads['basedir'] ) ) {
			return '';
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'gopath-logs';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		// Block direct web access (Apache, and a stub index for any server).
		if ( is_dir( $dir ) ) {
			if ( ! file_exists( $dir . '/.htaccess' ) ) {
				@file_put_contents( $dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" );
			}
			if ( ! file_exists( $dir . '/index.php' ) ) {
				@file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" );
			}
		}
		// The .htaccess above covers Apache/LiteSpeed. nginx ignores it, so the
		// filename also carries a per-site random suffix: without it the log
		// cannot be guessed even if the directory is served.
		$secret = get_option( 'gep_log_secret' );
		if ( ! $secret ) {
			$secret = wp_generate_password( 16, false, false );
			update_option( 'gep_log_secret', $secret, false );
		}
		return $dir . '/gep-diagnostics-' . $secret . '.log';
	}
}

if ( ! function_exists( 'gep_log' ) ) {
	function gep_log( $message ) {
		$file = gep_log_file();
		if ( ! $file ) {
			return;
		}
		// Keep the log bounded (1 MB) so a repeating error cannot fill the disk.
		if ( file_exists( $file ) && filesize( $file ) > 1048576 ) {
			@unlink( $file );
		}
		@file_put_contents( $file, $message, FILE_APPEND );
	}
}

if ( ! function_exists( 'gep_log_to' ) ) {
	function gep_log_to( $file, $message, $flags = FILE_APPEND ) {
		if ( empty( $file ) ) {
			return;
		}
		if ( file_exists( $file ) && filesize( $file ) > 1048576 ) {
			@unlink( $file );
		}
		@file_put_contents( $file, $message, $flags );
	}
}

// Catch fatal errors and log them
register_shutdown_function( function() {
	$error = error_get_last();
	if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
		gep_log( date('Y-m-d H:i:s') . ' FATAL: ' . $error['message'] . ' in ' . $error['file'] . ' line ' . $error['line'] . PHP_EOL );
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
define( 'GEP_VERSION', '2.0.0' );

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
if ( ! function_exists( 'activate_gep_exam_portal' ) ) {
	function activate_gep_exam_portal() {
		require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
		GEP_Activator::activate();
		update_option( 'gep_db_version', GEP_DB_VERSION );
		gep_remove_legacy_public_log();
	}
}

/**
 * Delete the old publicly-downloadable crash log left in the plugin folder by
 * earlier versions. It contained stack traces, server paths and question text.
 */
if ( ! function_exists( 'gep_remove_legacy_public_log' ) ) {
	function gep_remove_legacy_public_log() {
		$legacy = GEP_PLUGIN_DIR . 'fatal_error.log';
		if ( file_exists( $legacy ) ) {
			@unlink( $legacy );
		}
	}
}
add_action( 'admin_init', 'gep_remove_legacy_public_log' );

add_action( 'admin_init', 'gep_auto_update_db' );
if ( ! function_exists( 'gep_auto_update_db' ) ) {
	function gep_auto_update_db() {
		if ( get_option( 'gep_db_version' ) !== GEP_DB_VERSION ) {
			activate_gep_exam_portal();
		}
	}
}

/**
 * The code that runs during plugin deactivation.
 */
if ( ! function_exists( 'gep_activate_plugin' ) ) {
	function gep_activate_plugin() {
		require_once GEP_PLUGIN_DIR . 'includes/class-gep-activator.php';
		GEP_Activator::activate();
	}
}
register_activation_hook( __FILE__, 'gep_activate_plugin' );

if ( ! function_exists( 'gep_deactivate_plugin' ) ) {
	function gep_deactivate_plugin() {
		require_once GEP_PLUGIN_DIR . 'includes/class-gep-deactivator.php';
		GEP_Deactivator::deactivate();
	}
}
register_deactivation_hook( __FILE__, 'gep_deactivate_plugin' );

/**
 * Add Plugin Favicon to Browser Tab
 */
if ( ! function_exists( 'gep_add_plugin_favicon' ) ) {
	function gep_add_plugin_favicon() {
		echo '<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">' . "\n";
		if ( ! is_admin() ) {
			echo '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
			echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
		}
	}
}
add_action( 'wp_head', 'gep_add_plugin_favicon' );
add_action( 'admin_head', 'gep_add_plugin_favicon' );

/**
 * Add SEO Meta Tags for custom portal views
 */
if ( ! function_exists( 'gep_add_seo_meta_tags' ) ) {
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
					// The column is duration_minutes; "duration" does not exist on this
					// table, so this query errored on every exam page view and the
					// meta description and canonical link below were never emitted.
					$test_row = $wpdb->get_row( $wpdb->prepare( "SELECT title, duration_minutes FROM {$wpdb->prefix}gep_tests WHERE id = %d", $test_id ) );
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
						'timeRequired' => 'PT' . intval( $test_row->duration_minutes ) . 'M',
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
}
add_action( 'wp_head', 'gep_add_seo_meta_tags', 1 );

if ( ! function_exists( 'gep_custom_document_title' ) ) {
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
}
add_filter( 'pre_get_document_title', 'gep_custom_document_title', 999 );

if ( ! function_exists( 'gep_custom_wp_title' ) ) {
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
if ( ! function_exists( 'run_gep_exam_portal' ) ) {
	function run_gep_exam_portal() {
		$loader = new GEP_Loader();
		$loader->run();
	}
}
run_gep_exam_portal();

// Table sanitization & rendering helpers
add_filter( 'wp_kses_allowed_html', 'gep_allow_table_attributes', 10, 2 );
if ( ! function_exists( 'gep_allow_table_attributes' ) ) {
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

/**
 * Drop the sizing declarations from one inline style attribute, keeping the rest.
 *
 * Used only for table-related tags. Percentage widths survive (they scale with the
 * screen); absolute ones and `white-space: nowrap` do not, because either will pin
 * a cell wider than a phone.
 */
if ( ! function_exists( 'gep_strip_table_sizing_css' ) ) {
	function gep_strip_table_sizing_css( $css ) {
		$kept = array();
		foreach ( explode( ';', $css ) as $decl ) {
			if ( trim( $decl ) === '' || strpos( $decl, ':' ) === false ) {
				continue;
			}
			$prop = strtolower( trim( substr( $decl, 0, strpos( $decl, ':' ) ) ) );
			$val  = trim( substr( $decl, strpos( $decl, ':' ) + 1 ) );

			// Absolute widths/heights pin a cell wider than the screen; percentages
			// are relative and safe, so those stay.
			if ( in_array( $prop, array( 'width', 'min-width', 'height', 'min-height' ), true ) ) {
				if ( substr( $val, -1 ) !== '%' ) {
					continue;
				}
			}
			// nowrap is the other way a cell refuses to shrink.
			if ( $prop === 'white-space' && stripos( $val, 'nowrap' ) !== false ) {
				continue;
			}
			$kept[] = $prop . ': ' . $val;
		}
		return implode( '; ', $kept );
	}
}

/**
 * Normalise author-supplied table markup so a table can actually shrink to the
 * screen instead of forcing a horizontal scroll.
 *
 * Question banks are usually pasted in from Word / Google Docs / older HTML, and
 * that markup carries hard sizing along for the ride: `width="600"`,
 * `style="width:481.5pt"`, a legacy `nowrap` attribute, `<colgroup>` entries with
 * pixel widths. Any single one of those pins a column wider than a phone screen,
 * and the table then overflows no matter what the stylesheet asks for.
 *
 * So we strip only the *sizing* instructions from table-related tags. Text,
 * alignment, colours, borders, colspan/rowspan and every other authored choice
 * are left exactly as they were; percentage widths are kept because they scale.
 *
 * Attributes are walked one at a time rather than pattern-matched across the whole
 * tag, so a value that merely contains the word "nowrap" (or "width") is never
 * mistaken for the attribute itself.
 */
if ( ! function_exists( 'gep_make_tables_responsive' ) ) {
	function gep_make_tables_responsive( $html ) {
		if ( stripos( $html, '<table' ) === false ) {
			return $html;
		}

		// <colgroup>/<col> exist only to size columns — drop them wholesale.
		$html = preg_replace( '#<colgroup\b[^>]*>.*?</colgroup>#is', '', $html );
		$html = preg_replace( '#</?col(group)?\b[^>]*>#i', '', $html );

		// The attribute run is matched quote-aware rather than as [^>]*, so a value
		// that legitimately contains '>' (title="a>b") does not end the tag early
		// and leave the rest of it rewritten as text.
		return preg_replace_callback(
			'#<(table|tr|td|th|tbody|thead|tfoot)\b((?:[^>"\']|"[^"]*"|\'[^\']*\')*)>#i',
			function ( $m ) {
				$tag      = $m[1];
				$attr_str = $m[2];

				// An XHTML-style self-closing slash is not an attribute.
				$self_close = '';
				if ( substr( rtrim( $attr_str ), -1 ) === '/' ) {
					$self_close = '/';
					$attr_str   = substr( rtrim( $attr_str ), 0, -1 );
				}

				if ( trim( $attr_str ) === '' ) {
					return '<' . $tag . $self_close . '>';
				}

				$rebuilt = '';
				preg_match_all(
					'/([-\w:.]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'>]+))?/',
					$attr_str,
					$attrs,
					PREG_SET_ORDER
				);

				foreach ( $attrs as $a ) {
					$name     = strtolower( $a[1] );
					$has_val  = isset( $a[2] ) && $a[2] !== '';
					$raw_val  = $has_val ? $a[2] : '';
					$quote    = ( $has_val && ( $raw_val[0] === '"' || $raw_val[0] === "'" ) ) ? $raw_val[0] : '"';
					$value    = ( $has_val && ( $raw_val[0] === '"' || $raw_val[0] === "'" ) )
						? substr( $raw_val, 1, -1 )
						: $raw_val;

					// Legacy sizing attributes: keep percentages, drop absolute values.
					if ( 'width' === $name || 'height' === $name ) {
						if ( ! $has_val || substr( trim( $value ), -1 ) !== '%' ) {
							continue;
						}
					}

					// Legacy nowrap attribute (valueless or nowrap="nowrap").
					if ( 'nowrap' === $name ) {
						continue;
					}

					if ( 'style' === $name && $has_val ) {
						$value = gep_strip_table_sizing_css( $value );
						if ( trim( $value ) === '' ) {
							continue;
						}
					}

					// A rewritten style value could in principle contain the quote we
					// were going to wrap it in; pick the other one rather than emit
					// a broken attribute.
					if ( $has_val && strpos( $value, $quote ) !== false ) {
						$quote = ( $quote === '"' ) ? "'" : '"';
						if ( strpos( $value, $quote ) !== false ) {
							continue; // both quote styles present: drop rather than corrupt the tag
						}
					}

					$rebuilt .= ' ' . $a[1] . ( $has_val ? '=' . $quote . $value . $quote : '' );
				}

				return '<' . $tag . $rebuilt . $self_close . '>';
			},
			$html
		);
	}
}

/**
 * Wrap every <table> in a container that fits the table to the available width.
 *
 * After gep_make_tables_responsive() has removed the hard-coded sizing, the table
 * itself wraps its cell text and fits the screen, so the wrapper normally never
 * scrolls. It stays a scroll container purely as a safety net for the rare table
 * that genuinely cannot fit (many columns, a long unbreakable string) — and even
 * then the overflow stays inside the box instead of making the whole page slide
 * sideways.
 *
 * Nested tables stay balanced: every <table> gains an opening wrapper and every
 * </table> its matching close.
 */
if ( ! function_exists( 'gep_wrap_tables_scrollable' ) ) {
	function gep_wrap_tables_scrollable( $html ) {
		if ( strpos( $html, '<table' ) === false ) {
			return $html;
		}
		$html = preg_replace( '/<table\b/i', '<div class="gep-table-scroll"><table', $html );
		$html = preg_replace( '/<\/table>/i', '</table></div>', $html );
		return $html;
	}
}

/**
 * Single formatting pipeline for stored rich text (questions, options, passages,
 * explanations): sanitize -> optional paragraph/line-break formatting -> table cleanup
 * -> responsive table wrapping. Keeps every render site consistent.
 */
if ( ! function_exists( 'gep_format_rich_content' ) ) {
	function gep_format_rich_content( $html, $autop = true ) {
		if ( $html === null || $html === '' ) {
			return '';
		}
		$html = wp_kses_post( $html );
		if ( $autop ) {
			$html = wpautop( $html );
		}
		$html = gep_clean_wpautop_tables( $html );
		$html = gep_make_tables_responsive( $html );
		return gep_wrap_tables_scrollable( $html );
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

// ─── Fixed-Language detection ───────────────────────────────────────────────
// A "fixed language" paper is one that exists in a single language, so offering an
// English/Hindi switch would be a lie — the switch would change nothing.
//
// This is decided PER SECTION, not per test, because a paper is not the unit that
// has a language. UGC NET Sanskrit is one test containing two: Paper 1 is the
// general paper and is published in English and Hindi; Paper 2 is in Sanskrit.
// Locking the whole test took the switch away from Paper 1 as well.
//
// The signal is the content itself — does this section actually carry a second
// language? — never the name of the test or its category. Name matching failed
// twice over: it locked a bilingual Paper 1 for sitting under a "Sanskrit"
// category, and it silently missed sections whose name is spelled differently
// ("Sansrit") from the word it was looking for. An explicit admin flag still wins
// over the content check where one is set.
//
// This only ever governs the language selector; it never touches stored answers,
// marks, or evaluation.

/**
 * Does this question carry a usable second language?
 */
if ( ! function_exists( 'gep_question_has_translation' ) ) {
	function gep_question_has_translation( $q ) {
		if ( empty( $q ) || empty( $q->translated_data ) ) {
			return false;
		}
		$trans = gep_safe_json_decode( $q->translated_data, true );
		if ( ! is_array( $trans ) ) {
			return false;
		}
		// A title in the other language is what makes a question switchable; a
		// translated option alone still leaves the question itself untranslated.
		return ! empty( $trans['title'] ) && trim( (string) $trans['title'] ) !== '';
	}
}

/**
 * Is this section single-language?
 *
 * @param array  $questions   All questions in the test (filtered by section here).
 * @param string $section_key The section's id, matched against $q->category_id.
 * @param array  $section_cfg That section's config from the test's translated_data.
 */
if ( ! function_exists( 'gep_section_requires_fixed_language' ) ) {
	function gep_section_requires_fixed_language( $questions, $section_key, $section_cfg = array() ) {
		// An explicit admin choice wins over anything inferred from content.
		if ( is_array( $section_cfg ) && isset( $section_cfg['fixed_language'] ) ) {
			return (bool) $section_cfg['fixed_language'];
		}

		$found_question = false;
		foreach ( (array) $questions as $q ) {
			if ( (string) $q->category_id !== (string) $section_key ) {
				continue;
			}
			$found_question = true;
			if ( gep_question_has_translation( $q ) ) {
				return false; // one translated question is enough to make the switch real
			}
		}

		// No question carries a translation: the switch would do nothing. An empty
		// section is not evidence of anything, so leave it unlocked.
		return $found_question;
	}
}

/**
 * Is the WHOLE test single-language?
 *
 * True only when every section is. A test with one bilingual section keeps its
 * language selector — the exam window then locks the individual sections that
 * cannot honour it.
 *
 * @param object $test      The test row.
 * @param array  $questions Optional; without them only an explicit admin flag can lock.
 */
if ( ! function_exists( 'gep_test_requires_fixed_language' ) ) {
	function gep_test_requires_fixed_language( $test, $questions = null ) {
		if ( empty( $test ) ) {
			return false;
		}

		$td = ! empty( $test->translated_data ) ? gep_safe_json_decode( $test->translated_data, true ) : array();

		// 1. Explicit admin opt-in/opt-out, if ever set (most reliable when present).
		if ( isset( $td['fixed_language'] ) ) {
			return (bool) $td['fixed_language'];
		}

		// 2. Otherwise decide from the questions themselves, when we have them.
		//    No questions to inspect means no evidence — and a guess here is what
		//    took the language switch away from Paper 1, so we do not guess.
		if ( ! is_array( $questions ) || empty( $questions ) ) {
			return false;
		}
		foreach ( $questions as $q ) {
			if ( gep_question_has_translation( $q ) ) {
				return false;
			}
		}
		return true;
	}
}

/**
 * The language a paper is being READ in.
 *
 * This is deliberately not $_SESSION['gep_lang']. That key is the language of
 * the portal itself — the dashboard, the results list, the navigation — and it
 * follows the student's saved profile preference. The reading language is a
 * per-paper choice made on the instructions screen or in the exam header, and it
 * used to be written straight into gep_lang: picking Hindi to read one question,
 * or merely opening a single-language paper (which pinned the key to 'en'),
 * re-language the student's whole dashboard behind their back.
 *
 * Falls back to the portal language, so a Hindi-reading student still opens a
 * paper in Hindi by default.
 */
if ( ! function_exists( 'gep_get_exam_lang' ) ) {
	function gep_get_exam_lang() {
		if ( ! session_id() && ! headers_sent() ) {
			@session_start();
		}
		if ( isset( $_SESSION['gep_exam_lang'] ) ) {
			$lang = $_SESSION['gep_exam_lang'];
		} elseif ( isset( $_SESSION['gep_lang'] ) ) {
			$lang = $_SESSION['gep_lang'];
		} else {
			$lang = get_user_meta( get_current_user_id(), 'gep_preferred_lang', true );
		}
		return in_array( $lang, array( 'en', 'hi' ), true ) ? $lang : 'en';
	}
}

/**
 * Set the reading language for the current paper. Never touches gep_lang.
 */
if ( ! function_exists( 'gep_set_exam_lang' ) ) {
	function gep_set_exam_lang( $lang ) {
		if ( ! in_array( $lang, array( 'en', 'hi' ), true ) ) {
			return false;
		}
		if ( ! session_id() && ! headers_sent() ) {
			@session_start();
		}
		$_SESSION['gep_exam_lang'] = $lang;
		return true;
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

