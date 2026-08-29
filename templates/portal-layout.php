<?php
/**
 * Custom portal layout for GoPath Exam Portal.
 * This provides a clean, full-screen wrapper for the dashboard and exam engine.
 */

// NO-CACHE HEADERS: Prevent browser and proxy caching of portal pages.
// Critical for ensuring admin changes to tests/courses appear immediately on frontend.
if ( ! headers_sent() ) {
    header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
    header( 'Cache-Control: post-check=0, pre-check=0', false );
    header( 'Pragma: no-cache' );
    header( 'Expires: Thu, 01 Jan 1970 00:00:00 GMT' );
}

// Dynamic SEO Engine for GoPath Exam Portal
$seo_title = '';
$seo_desc = '';
$seo_keywords = '';
$schema_json = '';

$current_view = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'main';
global $wp;
$canonical_url = esc_url( home_url( add_query_arg( $_GET, $wp->request ) ) );
$logo_url = plugins_url( 'assets/images/gep-logo.png', GEP_PLUGIN_FILE );

global $wpdb;

if ( is_front_page() ) {
    $seo_title = "GoPath Exam Portal - Online Coaching, Bilingual Test Series & Sanskrit Prep";
    $seo_desc = "Crack competitive exams with top-tier bilingual mock tests, online coaching courses, live classes, typing practice, and previous year papers (PYQs). Access specialized Sanskrit grammar and literature mock tests with rank predictor on GoPath.";
    $seo_keywords = "mock test, online test series, sanskrit mock test, practice exams, previous year question papers, pyqs, supercoaching, exam preparation portal, sanskrit grammar test, online coaching classes, test prep, mock exam simulator, test series hub, संस्कृत मॉक टेस्ट, संस्कृत व्याकरण";
} elseif ( isset( $_GET['id'] ) && ( $current_view === 'watch' || ( isset( $_GET['type'] ) && $_GET['type'] === 'course' ) ) ) {
    // Course Page
    $course_id = absint( $_GET['id'] );
    $course = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gep_courses WHERE id = %d", $course_id ) );
    if ( $course ) {
        $seo_title = esc_html( $course->title ) . " Course & Certification - GoPath Skill Academy";
        $seo_desc = "Master " . esc_html( $course->title ) . " with premium video lectures, complete study notes, and hands-on practice. Enroll today to get certified and job-ready on GoPath Skill Academy.";
        $seo_keywords = esc_attr( $course->title ) . " course, online coaching, skill academy certification, typing practice test, data entry tutorial, excel mastery class, learn " . esc_attr( $course->title ) . " online, study notes, video lectures";
        
        // Course JSON-LD Schema
        $schema_data = array(
            "@context" => "https://schema.org",
            "@type" => "Course",
            "name" => $course->title,
            "description" => $course->description,
            "provider" => array(
                "@type" => "Organization",
                "name" => "GoPath Exam Portal",
                "sameAs" => home_url()
            )
        );
        $schema_json = json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    }
} elseif ( isset( $_GET['id'] ) && ( $current_view === 'tests' || $current_view === 'instructions' || $current_view === 'exam' ) ) {
    // Exam Page
    $test_id = absint( $_GET['id'] );
    $test = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gep_tests WHERE id = %d", $test_id ) );
    if ( $test ) {
        $seo_title = esc_html( $test->title ) . " Online Mock Test 2026 - GoPath Exam Series";
        $seo_desc = "Attempt the full-length " . esc_html( $test->title ) . " mock test. Features real-time exam layout, bilingual questions, detailed solutions, and dynamic rank prediction. Free trial available.";
        $seo_keywords = esc_attr( $test->title ) . " mock test, online test series, sanskrit mock test, practice papers, test prep, real exam simulator, rank predictor, solutions, bilingual questions, free mock test, previous year papers";
        
        // Quiz JSON-LD Schema
        $schema_data = array(
            "@context" => "https://schema.org",
            "@type" => "Quiz",
            "name" => $test->title,
            "educationalAlignment" => array(
                "@type" => "AlignmentObject",
                "educationalFramework" => "Competitive Exams India"
            )
        );
        $schema_json = json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
    }
} else {
    // Sub-view fallbacks
    $view_meta = array(
        'main'           => array( 
            'Dashboard - GoPath Exam Portal', 
            'Access your learning dashboard, active courses, mock exam results, and study plan.',
            'student dashboard, exam prep portal, study planner, online learning account'
        ),
        'tests'          => array( 
            'Online Mock Test Series Hub - Practice Exams', 
            'Browse our bilingual practice papers, topic quizzes, and past year question series. Practice mock tests for SSC, Railways, Banking, REET, UPTET, and Sanskrit exams.',
            'mock test series, free practice exam, ssc test series, sanskrit literature test, reet sanskrit mock test, pyqs online, quiz practice'
        ),
        'skill-academy'  => array( 
            'Skill Academy Certification Hub - GoPath', 
            'Learn in-demand digital jobs skills including typing speed, Excel mastery, and data entry. Enroll in premium professional certification tracks.',
            'skill academy, typing speed course, ms excel certification, data entry training, career development'
        ),
        'get-pass'       => array( 
            'GoPath Mock Test Pass Subscription - Unlock All Exams', 
            'Get yearly or lifetime access to all mock tests, full-length series, pyqs, and doubt solves. Unlocks all premium test series instantly.',
            'mock test pass pro, yearly test pass, lifetime mock test pass, free test series membership, unlock all mock tests'
        ),
        'typing-test'    => array( 
            'Online Typing Practice Test Simulator - WPM Speed', 
            'Test your words-per-minute (WPM) speed and accuracy with CHSL/Clerk typing simulator. Live highlighting, mistake logs, and analytics included.',
            'online typing test, typing practice simulator, wpm speed test, typing accuracy test, ssc typing test, data entry assessment, english typing test'
        ),
        'live-classes'   => array( 
            'Live Interactive Video Classes - GoPath Coaching', 
            'Join live coaching webinars, learn tips/tricks from expert educators, and clarify exam doubts in real-time sessions.',
            'live coaching classes, exam prep webinars, online tutor sessions, live classes, doubt clearance'
        ),
        'results'        => array( 
            'Performance Analytics & Scorecards - GoPath', 
            'View detailed post-exam reports, category-wise correctness, scorecards, and subject-wise strength and weakness analysis.',
            'performance scorecard, subject weakness tracker, test rank predictor, exam metrics analytics, score cards'
        ),
        'profile'        => array( 
            'My Account Profile - GoPath Exam Portal', 
            'Manage your subscription billing, password security, preferred exam language, and enrollment details.',
            'profile manager, account settings, billing dashboard, language preferences'
        ),
        'support'        => array( 
            'Help Center & Ticket Support - GoPath', 
            'Need assistance? Reach our student success support desk, check policies, or resolve account concerns.',
            'student support help desk, submit ticket, exam portal policies, contact teachers'
        )
    );
    
    if ( isset( $view_meta[$current_view] ) ) {
        $seo_title    = $view_meta[$current_view][0];
        $seo_desc     = $view_meta[$current_view][1];
        $seo_keywords = $view_meta[$current_view][2];
    } else {
        $seo_title    = wp_get_document_title();
        $seo_desc     = "GoPath Exam Portal helps you learn new skills and crack competitive exams with best-in-class mock tests, courses, and rank prediction.";
        $seo_keywords = "mock test, online test series, sanskrit test, coaching classes, previous year papers, pyqs, study materials, student portal";
    }
}

// Hook to filter title inside wp_head if any plugin relies on it
add_filter( 'pre_get_document_title', function( $title ) use ($seo_title) {
    return $seo_title ?: $title;
}, 999 );
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, shrink-to-fit=no">

    <title><?php echo esc_html( $seo_title ); ?></title>
    <meta name="description" content="<?php echo esc_attr( $seo_desc ); ?>">
    <meta name="keywords" content="<?php echo esc_attr( $seo_keywords ); ?>">
    
    <!-- Open Graph Metadata -->
    <meta property="og:title" content="<?php echo esc_attr( $seo_title ); ?>">
    <meta property="og:description" content="<?php echo esc_attr( $seo_desc ); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url( $canonical_url ); ?>">
    <meta property="og:image" content="<?php echo esc_url( $logo_url ); ?>">
    
    <!-- Twitter Metadata -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr( $seo_title ); ?>">
    <meta name="twitter:description" content="<?php echo esc_attr( $seo_desc ); ?>">
    <meta name="twitter:image" content="<?php echo esc_url( $logo_url ); ?>">
    
    <!-- Structured JSON-LD Schema -->
    <?php if ( ! empty( $schema_json ) ) : ?>
    <script type="application/ld+json">
    <?php echo $schema_json; ?>
    </script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: <?php echo $is_exam_page ? 'hidden' : 'auto'; ?> !important; /* Desktop: no double scrollbars during exam */
            background: #f3f7fa;
            font-family: 'Inter', sans-serif;
        }
        /* Mobile exam: MUST allow body scroll — fixed layouts don't work on phone */
        @media (max-width: 992px) {
            body, html {
                overflow-y: auto !important;
                overflow-x: hidden !important;
                height: auto !important;
            }
        }
        body, html, input, select, textarea, button, h1, h2, h3, h4, h5, h6, p, span, div, a, li, label {
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        }
        #wpadminbar { display: none !important; }
        html { margin-top: 0 !important; }
        
        /* Global safety layout constraints to prevent horizontal bleeding */
        img, svg, video, canvas {
            max-width: 100%;
            height: auto;
        }
        .gep-dashboard-container {
            max-width: 100vw !important;
            overflow-x: hidden !important;
        }
        .gep-dashboard-content {
            overflow-x: hidden !important;
        }
        
        /* Hide duplicate scrollbar on the sidebar */
        .gep-dashboard-sidebar {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        .gep-dashboard-sidebar::-webkit-scrollbar {
            display: none !important;
        }

        /* Mobile: result page fill fix — ONLY targets result wrap, not dashboard layout */
        @media (max-width: 768px) {
            .gep-result-wrap {
                padding: 10px 10px 80px !important;
                max-width: 100vw !important;
                width: 100% !important;
                box-sizing: border-box !important;
                overflow-x: hidden !important;
            }
        }


        
        /* Sovereign Active Theme overrides */
        .gep-dashboard-container.gep-sovereign-active {
            background: #09090b !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-dashboard-content {
            background: #09090b !important;
            overflow-x: hidden !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-main-inner {
            background: #09090b !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-dashboard-header {
            background: #09090b !important;
            border-bottom-color: rgba(255,255,255,0.08) !important;
            color: #fff !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-header-search input {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-header-search input:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(99, 102, 241, 0.5) !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-mobile-toggle {
            color: #fff !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-header-streak {
            color: #fff !important;
            background: transparent !important;
            border: none !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-header-avatar {
            background: rgba(255,255,255,0.1) !important;
            color: #fff !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-watch-breadcrumbs .active {
            color: #fff !important;
        }
        .gep-dashboard-container.gep-sovereign-active .gep-watch-breadcrumbs a {
            color: #94a3b8 !important;
        }
        /* Portal footer dark theme — handled by gep-dashboard.css */
        
        /* Mobile menu layout fixes */
        @media (max-width: 1024px) {
            .gep-dashboard-container.gep-sovereign-active .gep-dashboard-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body <?php body_class(); ?>>

    <?php 
    $exam_slug = get_option( 'gep_slug_exam', 'exam' );
    $current_uri = $_SERVER['REQUEST_URI'];
    $current_path = parse_url( $current_uri, PHP_URL_PATH );
    $current_path = rtrim( $current_path, '/' );
    $exam_slug_clean = rtrim( '/' . $exam_slug, '/' );

    $exam_page_id = (int) get_option( 'gep_page_exam', 0 );
    $is_exam_page = (
        $current_path === $exam_slug_clean ||
        strpos( $current_path, $exam_slug_clean . '/' ) === 0 ||
        ( $exam_page_id && is_page( $exam_page_id ) ) ||
        ( isset( $_GET['page_id'] ) && $_GET['page_id'] == $exam_page_id )
    );

    // Initialize context user ID safely to avoid undefined variable notices
    $context_user_id = is_user_logged_in() ? gep_get_context_user_id() : 0;

    // Define current view early for class detection and layout parameters
    $current_view = isset($_GET['view']) ? $_GET['view'] : 'main'; 

    // Detect sovereign (dark theme) views
    $is_sovereign_view = in_array( $current_view, array( 'main', 'supercoaching', 'live-classes', 'lectures', 'tests', 'pyqs', 'skill-academy', 'rank-predictor', 'profile', 'policies', 'purchases', 'support', 'watch' ) );
    ?>

    <div id="gep-page-wrapper" style="width:100%; height:100%; display:flex; flex-direction:column;">
    <?php if ( ! $is_exam_page ) : ?>
    <div class="gep-dashboard-container<?php echo $is_sovereign_view ? ' gep-sovereign-active' : ''; ?>">
        <!-- Persistent Sidebar -->
        <aside class="gep-dashboard-sidebar">
            <div class="gep-sidebar-logo">
                <span>GoPath<span style="color: var(--gep-primary);">.</span></span>
            </div>

            <?php if ( is_user_logged_in() ) : 
                $dashboard_logic = new GEP_Dashboard();
                $user_stats = $dashboard_logic->get_dashboard_stats( $context_user_id );
            ?>
            <div class="gep-sidebar-user-stats">
                <div class="gep-stat-mini streak" title="Learning Streak">
                    <span class="gep-mini-icon">🔥</span>
                    <span class="val"><?php echo $user_stats['streak']; ?> Days</span>
                </div>
                <div class="gep-stat-mini rank" title="Global Rank">
                    <span class="gep-mini-icon">🏆</span>
                    <span class="val"><?php echo is_numeric($user_stats['rank']) ? '#' . $user_stats['rank'] : $user_stats['rank']; ?> Rank</span>
                </div>
            </div>
            <?php endif; ?>
            
            <nav class="gep-dashboard-nav">
                <?php 
                $current_view = isset($_GET['view']) ? $_GET['view'] : 'main'; 
                $uid = isset($_GET['uid']) ? absint($_GET['uid']) : 0;
                $base_url = gep_get_url('dashboard');
                
                // Helper to preserve context
                $get_nav_link = function($view) use ($base_url, $uid) {
                    $url = add_query_arg('view', $view, (string) $base_url);
                    if ($uid && current_user_can('manage_options')) {
                        $url = add_query_arg('uid', $uid, (string) $url);
                    }
                    return $url;
                };
                ?>
                <div class="gep-nav-section-title">Learn</div>
                <a href="<?php echo $get_nav_link('main'); ?>" class="<?php echo $current_view === 'main' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">🏠</span> Dashboard
                </a>
                <a href="<?php echo $get_nav_link('live-classes'); ?>" class="<?php echo $current_view === 'live-classes' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📖</span> Live Classes <span class="gep-badge-new">New</span>
                </a>
                <a href="<?php echo $get_nav_link('lectures'); ?>" class="<?php echo $current_view === 'lectures' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">🎥</span> Video Lectures
                </a>
                <a href="<?php echo $get_nav_link('supercoaching'); ?>" class="<?php echo $current_view === 'supercoaching' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">🎬</span> SuperCoaching
                </a>
                
                <div class="gep-nav-section-title">Tests</div>
                <a href="<?php echo $get_nav_link('tests'); ?>" class="<?php echo $current_view === 'tests' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📝</span> Test Series <span class="gep-badge-free">Free</span>
                </a>
                <a href="<?php echo $get_nav_link('pyqs'); ?>" class="<?php echo $current_view === 'pyqs' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📜</span> Previous Year PYQs <span class="gep-badge-new" style="background:#ef4444;color:#fff;font-size:10px;padding:2px 6px;border-radius:4px;margin-left:5px;font-weight:800;">New</span>
                </a>
                <a href="<?php echo $get_nav_link('skill-academy'); ?>" class="<?php echo $current_view === 'skill-academy' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📊</span> Skill Academy
                </a>
                <a href="<?php echo $get_nav_link('typing-test'); ?>" class="<?php echo $current_view === 'typing-test' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">⌨️</span> Typing Practice
                </a>
                <a href="<?php echo $get_nav_link('rank-predictor'); ?>" class="<?php echo $current_view === 'rank-predictor' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📈</span> Rank Predictor
                </a>

                <div class="gep-nav-section-title">Miscellaneous</div>
                <a href="<?php echo $get_nav_link('purchases'); ?>" class="<?php echo $current_view === 'purchases' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">💰</span> My Purchases
                </a>
                <a href="<?php echo $get_nav_link('profile'); ?>" class="<?php echo $current_view === 'profile' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">👤</span> My Profile
                </a>
                <a href="<?php echo $get_nav_link('policies'); ?>" class="<?php echo $current_view === 'policies' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📜</span> Legal & Policies
                </a>
                <a href="<?php echo $get_nav_link('support'); ?>" class="<?php echo $current_view === 'support' ? 'active' : ''; ?>">
                    <span class="gep-nav-icon">📧</span> Help & Support
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="gep-dashboard-content">
            <!-- Persistent Header -->
            <header class="gep-dashboard-header">
                <button class="gep-mobile-toggle" id="gep-menu-toggle" type="button" aria-label="Open menu" style="background:transparent;border:none;padding:8px;cursor:pointer;touch-action:manipulation;-webkit-tap-highlight-color:transparent;">☰</button>

                <div class="gep-header-search">
                    <?php if ($current_view !== 'watch') : ?>
                    <form action="<?php echo gep_get_url('dashboard'); ?>" method="get" id="gep-header-search-form">
                        <input type="hidden" name="view" value="main">
                        <input type="text" name="gep_s" id="gep-header-search-input" placeholder="Search for tests, courses, or videos..." value="<?php echo isset($_GET['gep_s']) ? esc_attr($_GET['gep_s']) : ''; ?>">
                    </form>
                    <?php else : ?>
                        <div class="gep-watch-breadcrumbs">
                            <a href="<?php echo add_query_arg('view', 'supercoaching', (string) gep_get_url('dashboard')); ?>">SuperCoaching</a>
                            <span class="sep">/</span>
                            <?php 
                            $c_title = 'Learning';
                            if (isset($_GET['id'])) {
                                global $wpdb;
                                $c_id = absint($_GET['id']);
                                $c_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}gep_courses WHERE id = %d", $c_id)) ?: 'Learning';
                            }
                            ?>
                            <span class="active"><?php echo esc_html($c_title); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="gep-header-actions">
                    <div class="gep-header-notification-wrapper" id="gep-notif-trigger">
                        <div class="gep-notif-bell">🔔</div>
                        <?php 
                        $unread_count = GEP_Notifications::get_unread_count( get_current_user_id() );
                        if ( $unread_count > 0 ) : 
                        ?>
                            <span class="gep-notif-count"><?php echo $unread_count; ?></span>
                        <?php endif; ?>
                        
                        <div class="gep-notif-dropdown" id="gep-notif-panel">
                            <div class="notif-header">
                                <span>Notifications</span>
                                <a href="#" id="gep-mark-all-read">Mark all as read</a>
                            </div>
                            <div class="notif-body">
                                <?php 
                                $user_notifs = GEP_Notifications::get_for_user( get_current_user_id(), 5 );
                                if ( $user_notifs ) :
                                    foreach ( $user_notifs as $un ) :
                                ?>
                                    <div class="notif-item <?php echo $un->is_read ? '' : 'unread'; ?>" data-id="<?php echo $un->id; ?>">
                                        <div class="notif-icon">📢</div>
                                        <div class="notif-content">
                                            <div class="notif-title"><?php echo esc_html( $un->title ); ?></div>
                                            <div class="notif-msg"><?php echo esc_html( wp_trim_words($un->message, 10) ); ?></div>
                                            <div class="notif-time"><?php echo human_time_diff( strtotime($un->created_at), current_datetime()->getTimestamp() ); ?> ago</div>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach;
                                else :
                                ?>
                                    <div class="notif-empty">No new notifications.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="gep-header-streak" title="Daily Streak">
                        🔥 <?php echo isset($user_stats) ? $user_stats['streak'] : 0; ?>
                    </div>
                    <a href="<?php echo add_query_arg('view', 'get-pass', (string) gep_get_url('dashboard')); ?>" class="gep-btn-pass">Get Pass</a>
                    <a href="<?php echo add_query_arg('view', 'profile', (string) gep_get_url('dashboard')); ?>" class="gep-header-avatar" title="View Profile">
                        <?php 
                        $context_user = get_userdata( $context_user_id );
                        echo $context_user ? esc_html( strtoupper( substr( $context_user->display_name, 0, 1 ) ) ) : 'U'; 
                        ?>
                    </a>
                </div>
            </header>
            
            <?php 
            $inner_class = 'gep-main-inner';
            if ( $is_sovereign_view ) {
                $inner_class .= ' gep-full-width-view';
            }
            ?>
            <div class="<?php echo $inner_class; ?>">
                <?php 
                if ( is_front_page() && is_user_logged_in() ) {
                    // Force render dashboard on front page for students
                    $sc = new GEP_Shortcodes();
                    echo $sc->render_dashboard();
                } else {
                    if ( have_posts() ) :
                        while ( have_posts() ) : the_post();
                            the_content();
                        endwhile;
                    else:
                        // Fallback for empty front page
                        $sc = new GEP_Shortcodes();
                        echo $sc->render_dashboard();
                    endif;
                }
                ?>
            </div>

        </main>
    </div>
    <?php else : ?>
        <!-- Exam Mode: No Dashboard Wrapper -->
        <main class="gep-exam-fullscreen-container">
            <?php 
            if ( is_user_logged_in() ) {
                // High-Fidelity Fallback: Force render exam engine
                $sc = new GEP_Shortcodes();
                $exam_html = $sc->render_exam();
                if (empty($exam_html)) { 
                    echo '<div style="padding:50px;color:red;font-size:30px;">EXAM HTML IS EMPTY</div>'; 
                } else {
                    echo $exam_html;
                }
            } else {
                if ( have_posts() ) :
                    while ( have_posts() ) : the_post();
                        $content = get_the_content();
                        if ( empty( $content ) ) {
                            $sc = new GEP_Shortcodes();
                            echo $sc->render_exam();
                        } else {
                            the_content();
                        }
                    endwhile;
                endif;
            }
            ?>
        </main>
    <?php endif; ?>
    </div><!-- #gep-page-wrapper -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('gep-menu-toggle');
            const sidebar = document.querySelector('.gep-dashboard-sidebar');
            
            if (toggle && sidebar) {
                toggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });

                // Close sidebar when clicking outside on mobile (aligned with 1024px responsive breakpoint)
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 1024 && 
                        !sidebar.contains(e.target) && 
                        !toggle.contains(e.target) && 
                        sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            }

            // Notification Panel Toggle
            const notifTrigger = document.getElementById('gep-notif-trigger');
            const notifPanel = document.getElementById('gep-notif-panel');
            if (notifTrigger && notifPanel) {
                notifTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifPanel.classList.toggle('active');
                });

                document.addEventListener('click', function() {
                    notifPanel.classList.remove('active');
                });
            }
        });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>
