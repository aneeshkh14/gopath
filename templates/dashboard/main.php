<?php
// Translation Mapping
$ui_strings = array(
    'en' => array(
        'promo_tag'     => "OFFICER'S CHOICE 2026",
        'promo_title'   => "Mission Officer 2026",
        'promo_desc'    => "Your Journey to Government Job Starts Here. Get access to premium tests and video courses.",
        'explore'       => "Explore Academy",
        'tests_att'     => "Tests Attempted",
        'exams_passed'  => "Exams Passed",
        'active_courses'=> "Active Courses",
        'global_rank'   => "Global Rank",
        'live_classes'  => "Live Classes",
        'test_series'   => "Test Series",
        'supercoaching' => "SuperCoaching",
        'skill_academy' => "Skill Academy",
        'rank_predictor'=> "Rank Predictor",
        'my_purchases'  => "My Purchases",
        'suggested'     => "Suggested Coaching",
        'view_all'      => "View All",
        'popular'       => "Popular Test Series",
        'start_learning'=> "Start Learning",
        'start_test'    => "Start",
        'no_tests'      => "No Tests Available Yet",
        'no_tests_desc' => "Tests will appear here once the administrator publishes them."
    ),
    'hi' => array(
        'promo_tag'     => "अधिकारी की पसंद 2026",
        'promo_title'   => "मिशन ऑफिसर 2026",
        'promo_desc'    => "सरकारी नौकरी की आपकी यात्रा यहीं से शुरू होती है। प्रीमियम टेस्ट और वीडियो कोर्स तक पहुंचें।",
        'explore'       => "अकादमी एक्सप्लोर करें",
        'tests_att'     => "प्रयास किए गए टेस्ट",
        'exams_passed'  => "उत्तीर्ण परीक्षा",
        'active_courses'=> "सक्रिय पाठ्यक्रम",
        'global_rank'   => "वैश्विक रैंक",
        'live_classes'  => "लाइव क्लासेस",
        'test_series'   => "टेस्ट सीरीज",
        'supercoaching' => "सुपरकोचिंग",
        'skill_academy' => "स्किल एकेडमी",
        'rank_predictor'=> "रैंक प्रेडिक्टर",
        'my_purchases'  => "मेरी खरीदारी",
        'suggested'     => "सुझाए गए कोचिंग",
        'view_all'      => "सभी देखें",
        'popular'       => "लोकप्रिय टेस्ट सीरीज",
        'start_learning'=> "सीखना शुरू करें",
        'start_test'    => "शुरू करें",
        'no_tests'      => "अभी कोई टेस्ट उपलब्ध नहीं है",
        'no_tests_desc' => "प्रशासक द्वारा प्रकाशित किए जाने के बाद टेस्ट यहां दिखाई देंगे।"
    )
);

$_gep_lang = (isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : (get_user_meta(get_current_user_id(), 'gep_preferred_lang', true) ?: 'en'));
$strings = isset($ui_strings[$_gep_lang]) ? $ui_strings[$_gep_lang] : $ui_strings['en'];
?>

<?php
$dash_url = (string) gep_get_url('dashboard');
$resume_attempt = $dashboard->get_resume_attempt( $user_id );
$study_user = get_userdata( $user_id );
$is_hindi = $_gep_lang === 'hi';
?>
    <section class="gep-study-discover gep-study-promotions" aria-label="More ways to learn">
        <h2><?php echo $is_hindi ? 'और सीखें' : 'More ways to learn'; ?></h2>
        <div class="gep-promo-controls"><button type="button" data-promo-direction="-1" aria-label="Previous poster">←</button><button type="button" data-promo-direction="1" aria-label="Next poster">→</button></div>
        <div class="gep-study-links" tabindex="0" aria-label="Swipe through study posters">
        <?php foreach (array(1 => array('Explore courses', 'Learn at your own pace with available courses.', 'View courses', 'supercoaching'), 2 => array('Mock tests', 'Practise with timed papers and review your results.', 'View tests', 'tests'), 3 => array('Live classes', 'See upcoming classes and available sessions.', 'View classes', 'live-classes')) as $slide => $defaults) :
            $promo_url = get_option('gep_slide'.$slide.'_url') ?: add_query_arg('view', $defaults[3], $dash_url);
            $promo_image = get_option('gep_slide'.$slide.'_image'); ?>
            <a href="<?php echo esc_url($promo_url); ?>" class="gep-study-link">
                <?php if ($promo_image) : ?><img src="<?php echo esc_url($promo_image); ?>" alt="" loading="lazy"><?php endif; ?>
                <h3><?php echo esc_html(get_option('gep_slide'.$slide.'_title', $defaults[0])); ?></h3>
                <p><?php echo esc_html(get_option('gep_slide'.$slide.'_desc', $defaults[1])); ?></p>
                <span><?php echo esc_html(get_option('gep_slide'.$slide.'_btn', $defaults[2])); ?> <span aria-hidden="true">→</span></span>
            </a>
        <?php endforeach; ?>
        </div>
    </section>
<section class="gep-study-start" aria-labelledby="gep-study-heading">
    <div class="gep-study-intro">
        <p class="gep-study-eyebrow"><?php echo $is_hindi ? 'आपकी पढ़ाई, आपका लक्ष्य' : 'YOUR STUDY SPACE'; ?></p>
        <h1 id="gep-study-heading"><?php echo esc_html( ($is_hindi ? 'नमस्ते, ' : 'Welcome, ') . ($study_user ? $study_user->display_name : 'learner') ); ?></h1>
        <p><?php echo $is_hindi ? 'अभ्यास करें, परिणाम देखें और अगला कदम चुनें।' : 'A little practice today. A clearer path to your next exam.'; ?></p>
    </div>
    <div class="gep-study-focus">
        <div>
            <p class="gep-study-eyebrow"><?php echo $resume_attempt ? ($is_hindi ? 'अधूरा टेस्ट' : 'PICK UP WHERE YOU LEFT OFF') : ($is_hindi ? 'अगला कदम' : 'YOUR NEXT STEP'); ?></p>
            <h2><?php echo esc_html( $resume_attempt ? $resume_attempt->title : ($is_hindi ? 'आज किस विषय का अभ्यास करेंगे?' : 'What will you practise today?') ); ?></h2>
            <p><?php echo $resume_attempt ? ($is_hindi ? 'अपना टेस्ट खोलें और बाकी प्रश्न पूरे करें।' : 'Open your saved test to continue or finish your attempt.') : ($is_hindi ? 'विषय के अनुसार टेस्ट चुनें या अपना अभ्यास सेट बनाएं।' : 'Choose a subject test, try a previous year paper, or build your own practice set.'); ?></p>
        </div>
        <div class="gep-study-actions">
            <a class="gep-study-button" href="<?php echo esc_url( $resume_attempt ? add_query_arg('id', $resume_attempt->test_id, (string) gep_get_url('exam')) : add_query_arg('view', 'tests', $dash_url) ); ?>"><?php echo $resume_attempt ? ($is_hindi ? 'टेस्ट जारी रखें' : 'Continue test') : ($is_hindi ? 'टेस्ट चुनें' : 'Find a practice test'); ?> <span aria-hidden="true">→</span></a>
            <a class="gep-study-secondary" href="<?php echo esc_url( add_query_arg('view', $resume_attempt ? 'tests' : 'pyqs', $dash_url) ); ?>"><?php echo $resume_attempt ? ($is_hindi ? 'सभी टेस्ट देखें' : 'Browse all tests') : ($is_hindi ? 'पिछले वर्ष के प्रश्न' : 'Practise past papers'); ?></a>
        </div>
    </div>
    <div class="gep-study-stats">
        <a href="<?php echo esc_url(add_query_arg('view', 'results', $dash_url)); ?>"><strong><?php echo (int) $stats['total_attempts']; ?></strong><span><?php echo $is_hindi ? 'पूरे किए गए टेस्ट' : 'Tests completed'; ?></span><span aria-hidden="true">↗</span></a>
        <a href="<?php echo esc_url(add_query_arg('view', 'purchases', $dash_url)); ?>"><strong><?php echo (int) $stats['active_courses']; ?></strong><span><?php echo $is_hindi ? 'आपके पाठ्यक्रम' : 'Your courses'; ?></span><span aria-hidden="true">↗</span></a>
        <a href="<?php echo esc_url(add_query_arg('view', 'profile', $dash_url)); ?>"><strong><?php echo (int) $stats['streak']; ?></strong><span><?php echo $is_hindi ? 'लगातार सक्रिय दिन' : 'Day visit streak'; ?></span><span aria-hidden="true">↗</span></a>
    </div>
</section>

    <?php
    // ── Required dashboard sections (per client sketch/PDF): Today's Special Tests,
    //    Create Your Own Test, Paper 1 (Single), Sanskrit (Single), Set of Test Series,
    //    Full Test. All derived from existing test/category data — no hardcoded IDs.
    $gep_hcard_render = function( $t, $is_series_override = null ) {
        $is_series = ( $is_series_override !== null ) ? $is_series_override : ( $t->type === 'series' || $t->type === 'bundle' );
        $link = esc_url( add_query_arg( 'id', $t->id, (string) gep_get_url('exam') ) );
        ob_start();
        ?>
        <a href="<?php echo $link; ?>" class="gep-hcard">
            <div class="gep-hcard-thumb">
                <?php if ( ! empty( $t->thumbnail ) ) : ?>
                    <img src="<?php echo esc_url( $t->thumbnail ); ?>" alt="" loading="lazy">
                <?php else : ?>
                    <span class="gep-hcard-thumb-icon" aria-hidden="true"><?php echo $is_series ? '📁' : '📝'; ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $t->is_free ) ) : ?><span class="gep-hcard-badge">FREE</span><?php endif; ?>
            </div>
            <div class="gep-hcard-body">
                <h4><?php echo esc_html( $t->title ); ?></h4>
                <div class="gep-hcard-meta">
                    <?php if ( $is_series ) : ?><span>Test series</span>
                    <?php else : ?><span><?php echo (int) $t->duration_minutes; ?> Mins</span>
                    <?php if ( isset( $t->total_marks ) ) : ?><span>•</span><span><?php echo (int) $t->total_marks; ?> Marks</span><?php endif; ?>
                    <?php endif; ?>
                </div>
                <span class="gep-hcard-action">View <?php echo $is_series ? 'series' : 'test'; ?> <span aria-hidden="true">→</span></span>
            </div>
        </a>
        <?php
        return ob_get_clean();
    };

    $gep_hscroll_section = function( $title, $tests, $see_more_url = '' ) use ( $gep_hcard_render ) {
        if ( empty( $tests ) ) return;
        ?>
        <section class="gep-curated-section">
            <div class="section-header-sovereign">
                <div class="header-content"><h3><?php echo esc_html( $title ); ?></h3><div class="header-line"></div></div>
                <?php if ( $see_more_url ) : ?>
                    <a href="<?php echo esc_url( $see_more_url ); ?>" class="view-all-link">See More
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </a>
                <?php endif; ?>
            </div>
            <div class="gep-test-card-grid" role="region" aria-label="<?php echo esc_attr($title); ?>">
                <?php foreach ( $tests as $t ) { echo $gep_hcard_render( $t ); } ?>
            </div>
        </section>
        <?php
    };

    $dash_url = (string) gep_get_url('dashboard');
    ?>


    <?php
    // 1) Today's Special Tests — safest available signal (no "special test" field exists in the
    //    schema): the most recently published test from each Paper 1 / Sanskrit / Combined bucket.
    $todays_special = array_filter( array( $special_paper1, $special_sanskrit, $special_combined ) );
    $gep_hscroll_section( "Featured Tests", $todays_special );

    // 2) Create Your Own Test — routes into the EXISTING random/custom-test builder (type=random).
    $browse_random_url = add_query_arg( array( 'view' => 'tests', 'type' => 'random' ), $dash_url );
    $custom_templates = $dashboard->get_available_items('test', 1, 0, 'random');
    $cyo_paper1_url   = $paper1_random_test ? add_query_arg( 'id', $paper1_random_test->id, (string) gep_get_url('exam') ) : $browse_random_url;
    $cyo_sanskrit_url = $sanskrit_random_test ? add_query_arg( 'id', $sanskrit_random_test->id, (string) gep_get_url('exam') ) : $browse_random_url;
    ?>
    <section class="gep-curated-section">
        <div class="section-header-sovereign">
            <div class="header-content"><h3>Create Your Own Test</h3><div class="header-line"></div></div>
        </div>
        <?php if (empty($custom_templates)) : ?>
        <div class="gep-cyo-card">
            <p>Custom test templates are not available yet. You can practise published PYQs by topic or year.</p>
            <a class="gep-study-secondary" href="<?php echo esc_url(add_query_arg('view', 'pyqs', $dash_url)); ?>">Browse PYQ practice</a>
        </div>
        <?php else : ?>
        <div class="gep-cyo-grid">
            <a class="gep-cyo-card" href="<?php echo esc_url( $cyo_paper1_url ); ?>">
                <div class="icon">📘</div><h4>Paper 1</h4><p>Pick subjects &amp; topics and build your own Paper 1 practice test.</p>
            </a>
            <a class="gep-cyo-card" href="<?php echo esc_url( $cyo_sanskrit_url ); ?>">
                <div class="icon">📜</div><h4>Sanskrit</h4><p>Pick topics and build your own Sanskrit practice test.</p>
            </a>
            <a class="gep-cyo-card" href="<?php echo esc_url( $browse_random_url ); ?>">
                <div class="icon">🧩</div><h4>Paper 1 + Sanskrit</h4><p>Build a combined custom test across both subjects.</p>
            </a>
        </div>
        <?php endif; ?>
    </section>

    <?php
    // 3) Paper 1 (Single Test)
    $gep_hscroll_section(
        'Paper 1 (Single Test)',
        $paper1_single_tests,
        add_query_arg( 'view', 'tests', $dash_url )
    );

    // 4) Sanskrit (Single Test)
    $gep_hscroll_section(
        'Sanskrit (Single Test)',
        $sanskrit_single_tests,
        add_query_arg( 'view', 'tests', $dash_url )
    );

    // 5) Set of Test Series — reuses the existing type='series' system
    $gep_hscroll_section(
        'Set of Test Series',
        $available_series,
        add_query_arg( array( 'view' => 'tests', 'type' => 'series' ), $dash_url )
    );

    // 6) Full Test — reuses the existing type='multiple' (full-syllabus) tests
    $full_test_groups = array_filter( array_merge( (array) $paper1_full_tests, (array) $sanskrit_full_tests ) );
    if ( empty( $full_test_groups ) ) {
        $full_test_groups = $full_length_tests; // fallback: any full-length test
    }
    $gep_hscroll_section(
        'Full Test',
        $full_test_groups,
        add_query_arg( array( 'view' => 'tests', 'type' => 'multiple' ), $dash_url )
    );
    ?>

    <!-- Recent Activity & Resume Practice -->
    <?php
    $recent_attempts = array_slice( (new GEP_Result())->get_user_results( $user_id ), 0, 3 );
    foreach ( $recent_attempts as $recent ) { $recent->title = $recent->test_name; }
    ?>
    <?php if ( ! empty( $recent_attempts ) ) : ?>
    <section class="gep-curated-section" style="margin-top: 20px; margin-bottom: 40px;">
        <div class="section-header-sovereign">
            <div class="header-content">
                <h3>Recent Activity & Mock Results</h3>
                <div class="header-line"></div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ( $recent_attempts as $att ) : 
                $is_ip = ($att->status === 'in_progress');
            ?>
            <div class="gep-test-strip gep-glass" style="padding: 16px 24px; border-radius: 18px; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="font-size: 20px;"><?php echo $is_ip ? '⏳' : '📊'; ?></div>
                    <div>
                        <h5 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: #fff;"><?php echo esc_html($att->title); ?></h5>
                        <span style="font-size: 11px; color: #a1a1aa; font-weight: 600;">
                            <?php if ( $is_ip ) : ?>
                                In Progress • Started recently
                            <?php else : ?>
                                Submitted • <?php echo esc_html( date_i18n( get_option('date_format'), strtotime($att->end_time) ) ); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 24px;">
                    <?php if ( ! $is_ip ) : ?>
                    <div style="text-align: right;">
                        <div style="font-size: 16px; font-weight: 900; color: #10b981;"><?php echo number_format($att->score, 1); ?> <small style="font-size: 11px; color: #a1a1aa;">/<?php echo number_format($att->total_marks, 0); ?></small></div>
                        <div style="font-size: 10px; color: #a1a1aa; font-weight: 700; text-transform: uppercase;">Score</div>
                    </div>
                    <div style="text-align: right; min-width: 60px;">
                       <div style="font-size: 16px; font-weight: 900; color: #818cf8;"><?php echo round($att->percentage); ?>%</div>
                       <div style="font-size: 10px; color: #a1a1aa; font-weight: 700; text-transform: uppercase;">Score %</div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <?php if ( $is_ip ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( 'id', $att->test_id, (string) gep_get_url('exam') ) ); ?>" class="gep-btn-strip" style="background: linear-gradient(135deg,#6366f1,#8b5cf6); border: none; font-size: 12px; padding: 8px 18px; border-radius: 10px;">
                                Resume Test
                            </a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( add_query_arg( 'id', $att->id, (string) gep_get_url('result') ) ); ?>" class="gep-btn-strip" style="font-size: 12px; padding: 8px 18px; border-radius: 10px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
                                Review Analysis
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>



    <!-- Popular Exams Section (Testbook-style) -->
    <?php
    $cat_helper = new GEP_Category();
    $hierarchical_cats = $cat_helper->get_hierarchical_categories();
    
    // Filter hierarchical categories to keep only those with content
    $active_hierarchical_cats = array();
    foreach ( $hierarchical_cats as $pcat ) {
        // Fetch children
        $children = $pcat->children;
        
        // Also fetch tests directly under this parent category
        $direct_tests = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gep_tests WHERE category_id = %d AND status = 'publish' LIMIT 8",
            $pcat->id
        ) );
        
        if ( ! empty( $children ) || ! empty( $direct_tests ) ) {
            $pcat->children = array_slice((array)$children, 0, 11); // Limit child cards to max 11 to fit cleanly with the last "Explore all" card
            $pcat->direct_tests = array_slice((array)$direct_tests, 0, 11);
            $active_hierarchical_cats[] = $pcat;
        }
    }
    ?>

    <?php if ( ! empty( $active_hierarchical_cats ) ) : ?>
    <style>
    /* Popular Exams Widget - Dark/Glass Theme */
    .gep-popular-exams-section {
        background: transparent;
        padding: 0;
        border: none;
        margin-top: 40px;
        margin-bottom: 35px;
        box-shadow: none;
    }
    /* Horizontal scrolling tab list */
    .gep-popular-tabs-list {
        display: flex;
        gap: 12px;
        margin-bottom: 25px;
        overflow-x: auto;
        padding-bottom: 8px;
        scrollbar-width: none; /* Firefox */
    }
    .gep-popular-tabs-list::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }
    .gep-popular-tab-btn {
        padding: 10px 24px;
        border-radius: 100px;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        color: #94a3b8;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .gep-popular-tab-btn:hover {
        border-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        background: rgba(255, 255, 255, 0.06);
    }
    .gep-popular-tab-btn.active {
        background: #6366f1;
        border-color: #6366f1;
        color: #fff;
        box-shadow: 0 4px 12px rgba(99,102,241,0.25);
    }
    /* Grid layout for exam cards */
    .gep-popular-exams-grid {
        display: none;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
    .gep-popular-exams-grid.active {
        display: grid;
    }
    @media (max-width: 1024px) {
        .gep-popular-exams-grid.active {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 640px) {
        .gep-popular-exams-grid.active {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }
    .gep-exam-card-link {
        text-decoration: none !important;
    }
    .gep-exam-category-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .gep-exam-category-card:hover {
        border-color: rgba(99, 102, 241, 0.4) !important;
        box-shadow: 0 8px 24px rgba(99,102,241,0.15) !important;
        transform: translateY(-2px);
    }
    .gep-exam-card-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .gep-exam-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.15);
        color: #818cf8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        overflow: hidden;
        flex-shrink: 0;
    }
    .gep-exam-card-icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gep-exam-card-name {
        font-size: 15px;
        font-weight: 800;
        color: #fff;
    }
    .gep-exam-card-arrow {
        color: #94a3b8;
        transition: transform 0.2s;
        display: flex;
        align-items: center;
    }
    .gep-exam-category-card:hover .gep-exam-card-arrow {
        transform: translateX(4px);
        color: #6366f1;
    }
    </style>

    <section class="gep-popular-exams-section">
        <div class="section-header-sovereign">
            <div class="header-content">
                <h3>Popular Exams</h3>
                <div class="header-line"></div>
            </div>
        </div>
        <p style="font-size: 14px; color: #94a3b8; margin: -10px 0 25px 0; font-weight: 600;">Get exam-ready with mock tests, PYQs, and study materials as per the latest pattern.</p>
        
        <!-- Tabs List -->
        <div class="gep-popular-tabs-list">
            <?php foreach ( $active_hierarchical_cats as $idx => $pcat ) : ?>
                <button type="button" class="gep-popular-tab-btn<?php echo $idx === 0 ? ' active' : ''; ?>" data-parent-id="<?php echo $pcat->id; ?>">
                    <?php echo esc_html( $pcat->name ); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Grids Content -->
        <?php foreach ( $active_hierarchical_cats as $idx => $pcat ) : ?>
            <div class="gep-popular-exams-grid<?php echo $idx === 0 ? ' active' : ''; ?>" id="gep-popular-exams-grid-<?php echo $pcat->id; ?>">
                <?php if ( ! empty( $pcat->children ) ) : ?>
                    <?php foreach ( $pcat->children as $child ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( array( 'view' => 'tests', 'cat' => $child->id ), (string) gep_get_url('dashboard') ) ); ?>" class="gep-exam-card-link">
                            <div class="gep-exam-category-card gep-glass-dark" style="border: 1px solid rgba(255,255,255,0.08);">
                                <div class="gep-exam-card-left">
                                    <div class="gep-exam-card-icon">🎓</div>
                                    <div class="gep-exam-card-name"><?php echo esc_html( $child->name ); ?></div>
                                </div>
                                <div class="gep-exam-card-arrow">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php elseif ( ! empty( $pcat->direct_tests ) ) : ?>
                    <?php foreach ( $pcat->direct_tests as $test ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( array( 'id' => $test->id ), (string) gep_get_url('exam') ) ); ?>" class="gep-exam-card-link">
                            <div class="gep-exam-category-card gep-glass-dark" style="border: 1px solid rgba(255,255,255,0.08);">
                                <div class="gep-exam-card-left">
                                    <div class="gep-exam-card-icon">
                                        <?php if ( ! empty($test->thumbnail) ) : ?>
                                            <img src="<?php echo esc_url($test->thumbnail); ?>" alt="<?php echo esc_attr($test->title); ?>" loading="lazy">
                                        <?php else : ?>
                                            📝
                                        <?php endif; ?>
                                    </div>
                                    <div class="gep-exam-card-name"><?php echo esc_html( $test->title ); ?></div>
                                </div>
                                <div class="gep-exam-card-arrow">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Explore all exams card -->
                <a href="<?php echo esc_url( add_query_arg( 'view', 'tests', (string) gep_get_url('dashboard') ) ); ?>" class="gep-exam-card-link">
                    <div class="gep-exam-category-card gep-glass-dark" style="background: rgba(255,255,255,0.01) !important; border-style: dashed !important; border-color: rgba(255,255,255,0.15) !important;">
                        <div class="gep-exam-card-left">
                            <div class="gep-exam-card-icon" style="background: rgba(255,255,255,0.05); color: #94a3b8;">🔍</div>
                            <div class="gep-exam-card-name" style="color: #94a3b8;">Explore all exams</div>
                        </div>
                        <div class="gep-exam-card-arrow">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </section>

    <script>
    jQuery(document).ready(function($) {
        $('.gep-popular-tab-btn').on('click', function() {
            var parentId = $(this).data('parent-id');
            
            // Update tabs active state
            $('.gep-popular-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Hide all grids, show selected
            $('.gep-popular-exams-grid').removeClass('active');
            $('#gep-popular-exams-grid-' + parentId).addClass('active');
        });
    });
    </script>
    <?php endif; ?>

    <!-- Curated Modules -->
    <div class="gep-dashboard-sections">
        <!-- Suggested Coaching -->
        <?php if ( ! empty( $available_courses ) ) : ?>
        <section class="gep-curated-section">
            <div class="section-header-sovereign">
                <div class="header-content">
                    <h3><?php echo esc_html($strings['suggested']); ?></h3>
                    <div class="header-line"></div>
                </div>
                <a href="<?php echo add_query_arg( 'view', 'supercoaching', (string) gep_get_url('dashboard') ); ?>" class="view-all-link">
                    <?php echo esc_html($strings['view_all']); ?>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>
            
            <div class="gep-curated-grid">
                <?php foreach ( array_slice($available_courses, 0, 3) as $idx => $course ) : ?>
                <div class="gep-curated-card gep-glass">
                    <div class="card-thumb">
                        <?php if ( $course->thumbnail ) : ?>
                            <img src="<?php echo esc_url($course->thumbnail); ?>" alt="<?php echo esc_attr($course->title); ?>" loading="lazy" width="600" height="400" decoding="async">
                        <?php else : ?>
                            <?php 
                            $placeholders = array(
                                'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
                                'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80',
                                'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=600&q=80'
                            );
                            $cur_image = $placeholders[$idx % count($placeholders)];
                            ?>
                            <div class="thumb-placeholder">
                                <img src="<?php echo esc_url($cur_image); ?>" alt="<?php echo esc_attr($course->title); ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" width="600" height="400" decoding="async">
                                <div class="placeholder-overlay-tag">PREMIUM CONTENT</div>
                            </div>
                        <?php endif; ?>
                        <div class="thumb-overlay"></div>
                    </div>
                    <div class="card-body">
                        <h4><?php echo esc_html($course->title); ?></h4>
                        <div class="card-instructor">Prof. <?php echo esc_html($course->instructor); ?></div>
                        <div class="card-footer">
                            <span class="card-price">₹<?php echo number_format($course->price, 0); ?></span>
                            <?php 
                            $temp_dashboard = new GEP_Dashboard();
                            if ( $temp_dashboard->has_access( get_current_user_id(), $course->id, 'course' ) ) : ?>
                                <a href="<?php echo add_query_arg( 'id', $course->id, add_query_arg( 'view', 'watch', (string) gep_get_url( 'dashboard' ) ) ); ?>" class="gep-btn-mini">
                                    <?php echo esc_html($strings['start_learning']); ?>
                                </a>
                            <?php else : ?>
                                <a href="<?php echo add_query_arg( array( 'id' => $course->id, 'type' => 'course' ), (string) gep_get_url( 'checkout' ) ); ?>" class="gep-btn-mini" style="background: #10b981; border-color: #059669;">
                                    Enroll Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Popular Tests -->
        <?php if ( ! empty( $available_tests ) ) : ?>
        <section class="gep-curated-section">
            <div class="section-header-sovereign">
                <div class="header-content">
                    <h3><?php echo esc_html($strings['popular']); ?></h3>
                    <div class="header-line"></div>
                </div>
                <a href="<?php echo add_query_arg( 'view', 'tests', (string) gep_get_url('dashboard') ); ?>" class="view-all-link">
                    <?php echo esc_html($strings['view_all']); ?>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            </div>

            <div class="gep-test-matrix">
                <?php foreach ( array_slice($available_tests, 0, 4) as $test ) : ?>
                <div class="gep-test-strip gep-glass">
                    <?php 
                    $is_series = ($test->type === 'series' || $test->type === 'bundle');
                    $strip_icon = '⚡';
                    if ( $is_series ) {
                        $strip_icon = '📁';
                    } else {
                        if ( $test->type === 'single' ) {
                            $strip_icon = '📝';
                        } elseif ( $test->type === 'multiple' ) {
                            $strip_icon = '📚';
                        } elseif ( $test->type === 'combined' ) {
                            $strip_icon = '🧩';
                        } elseif ( $test->type === 'self_test' ) {
                            $strip_icon = '⚙️';
                        }
                    }
                    ?>
                    <div class="strip-icon">
                        <?php if ( ! empty($test->thumbnail) ) : ?>
                            <img src="<?php echo esc_url($test->thumbnail); ?>" alt="<?php echo esc_attr($test->title); ?>" loading="lazy">
                        <?php else : ?>
                            <?php echo $strip_icon; ?>
                        <?php endif; ?>
                    </div>
                    <div class="strip-info">
                        <h5><?php echo esc_html($test->title); ?></h5>
                        <div class="strip-meta">
                            <span><?php echo (int)$test->duration_minutes; ?> Mins</span>
                            <span>•</span>
                            <span><?php echo isset($test->pass_marks) ? (float)$test->pass_marks . ' marks to pass' : '—'; ?></span>
                        </div>
                    </div>
                    <?php 
                    $temp_dashboard = new GEP_Dashboard();
                    if ( $temp_dashboard->has_access( get_current_user_id(), $test->id, 'test' ) ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'id', $test->id, (string) gep_get_url('exam') ) ); ?>" class="gep-btn-strip">
                            <?php echo esc_html($strings['start_test']); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( add_query_arg( array( 'id' => $test->id, 'type' => 'test' ), (string) gep_get_url('checkout') ) ); ?>" class="gep-btn-strip" style="background: #10b981; border-color: #059669; color: white;">
                            Buy Now
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</div>

<style>
.gep-sovereign-dashboard {
    padding: 0 20px 20px 20px;
    font-family: 'Inter', sans-serif;
    color: #fff;
    background: #09090b;
    min-height: 100vh;
    max-width: 100%;
    overflow-x: hidden;
}

.gep-promo-title {
    font-size: 56px !important;
}

.gep-promo-desc {
    font-size: 19px !important;
}

.gep-dashboard-hero {
    position: relative;
    margin-bottom: 40px;
}

.hero-glow {
    position: absolute;
    top: -50px;
    left: 10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, transparent 70%);
    filter: blur(60px);
    z-index: 0;
}

.gep-promo-banner-premium {
    position: relative;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 60px;
    border-radius: 40px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.05);
    overflow: hidden;
    z-index: 1;
}

.gep-banner-tag-sovereign {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: rgba(255,255,255,0.1);
    color: #e2e8f0;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 20px;
}

.gep-promo-banner-premium h2 {
    font-size: 48px;
    font-weight: 950;
    letter-spacing: -2px;
    margin-bottom: 15px;
    line-height: 1.1;
    background: linear-gradient(135deg, #fde047 0%, #d97706 100%) !important;
    -webkit-background-clip: text !important;
    background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
}

.gep-promo-banner-premium p {
    font-size: 18px;
    color: #a1a1aa;
    max-width: 500px;
    margin-bottom: 35px;
    font-weight: 500;
}

.gep-btn-sovereign-primary {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: #fff;
    color: #000;
    padding: 16px 32px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 800;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.gep-btn-sovereign-primary:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 20px 40px rgba(255,255,255,0.1);
}

.gep-banner-visual {
    position: relative;
    width: 200px;
    height: 200px;
}

.visual-orb {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 160px;
    height: 160px;
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
    border-radius: 50%;
    filter: blur(40px);
    opacity: 0.5;
    animation: pulseOrb 4s infinite alternate;
}

.visual-icon {
    font-size: 80px;
    z-index: 2;
}

@keyframes pulseOrb {
    from { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
    to { transform: translate(-50%, -50%) scale(1.3); opacity: 0.6; }
}

/* Telemetry Grid */
.gep-telemetry-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.gep-telemetry-card-micro {
    padding: 20px;
    border-radius: 24px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.4s;
    background: #18181b;
    border: 1px solid rgba(255,255,255,0.05);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.gep-telemetry-card-micro:hover {
    transform: translateY(-5px);
    border-color: rgba(99, 102, 241, 0.3);
    box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15);
}

.gep-telemetry-card-micro .card-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.05);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.gep-telemetry-card-micro .val {
    font-size: 24px;
    font-weight: 900;
    color: #fff;
    line-height: 1;
}

.gep-telemetry-card-micro .lbl {
    font-size: 11px;
    font-weight: 800;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

.rank-card .val { color: #fde047; }

/* Matrix Navigation */
.gep-nav-matrix {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
    margin-bottom: 60px;
}

.matrix-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 20px 10px;
    border-radius: 24px;
    text-decoration: none;
    transition: all 0.3s;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
}

.matrix-item:hover {
    background: rgba(255,255,255,0.1);
    transform: scale(1.05);
    border-color: rgba(255,255,255,0.2);
}

.matrix-icon {
    font-size: 24px;
}

.matrix-item span {
    font-size: 11px;
    font-weight: 800;
    color: #e2e8f0;
    text-align: center;
}

/* Curated Sections */
.gep-curated-section {
    margin-bottom: 50px;
}

.section-header-sovereign {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.header-content h3 {
    font-size: 24px;
    font-weight: 900;
    color: #fff;
    white-space: nowrap;
}

.header-line {
    height: 1px;
    background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, transparent 100%);
    flex: 1;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #a1a1aa;
    text-decoration: none;
    font-weight: 800;
    font-size: 13px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    transition: background 0.3s;
}
.view-all-link:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.gep-curated-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

.gep-curated-card {
    border-radius: 28px;
    overflow: hidden;
    transition: all 0.4s;
    background: #18181b;
    border: 1px solid rgba(255,255,255,0.05);
}

.gep-curated-card:hover {
    transform: translateY(-8px);
    border-color: rgba(124, 58, 237, 0.3);
    box-shadow: 0 30px 60px rgba(124, 58, 237, 0.15);
}

.card-thumb {
    position: relative;
    height: 160px;
}

.card-thumb img { width: 100%; height: 100%; object-fit: cover; }

.thumb-placeholder {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.thumb-placeholder.course-grad-0 {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #7c3aed 100%);
}

.thumb-placeholder.course-grad-1 {
    background: linear-gradient(135deg, #7c2d12 0%, #ea580c 50%, #f43f5e 100%);
}

.thumb-placeholder.course-grad-2 {
    background: linear-gradient(135deg, #064e3b 0%, #10b981 50%, #06b6d4 100%);
}

.thumb-placeholder:not([class*="course-grad-"]) {
    background: linear-gradient(135deg, #1e1b4b 0%, #4f46e5 50%, #9333ea 100%);
}

.placeholder-pattern {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.15) 1px, transparent 1px),
                      radial-gradient(circle at 75% 60%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.8;
    z-index: 1;
}

.placeholder-icon {
    font-size: 52px;
    z-index: 2;
    text-shadow: 0 8px 16px rgba(0,0,0,0.3);
    animation: placeholderFloat 3s ease-in-out infinite alternate;
    will-change: transform;
}

.placeholder-overlay-tag {
    position: absolute;
    bottom: 12px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1.5px;
    padding: 4px 12px;
    border-radius: 50px;
    text-transform: uppercase;
    z-index: 2;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

@keyframes placeholderFloat {
    from { transform: translateY(0) scale(1); }
    to { transform: translateY(-6px) scale(1.05); }
}

.card-body { padding: 25px; }

.card-body h4 {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 8px;
    color: #fff;
}

.card-instructor {
    font-size: 13px;
    font-weight: 600;
    color: #a1a1aa;
    margin-bottom: 20px;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-price {
    font-size: 20px;
    font-weight: 900;
    color: #fff;
}

.gep-btn-mini {
    background: rgba(255,255,255,0.1);
    color: #fff;
    padding: 8px 16px;
    border-radius: 12px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 800;
    transition: background 0.3s;
}
.gep-btn-mini:hover { background: rgba(255,255,255,0.2); }

/* Test Matrix */
.gep-test-matrix {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.gep-test-strip {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px 25px;
    border-radius: 24px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
}

.strip-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    overflow: hidden;
    flex-shrink: 0;
}
.strip-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.strip-info { flex: 1; }
.strip-info h5 {
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 4px;
    color: #fff;
}
.strip-meta {
    font-size: 12px;
    font-weight: 700;
    color: #a1a1aa;
    display: flex;
    gap: 8px;
}

.gep-btn-strip {
    background: rgba(255,255,255,0.1);
    color: #fff;
    padding: 10px 20px;
    border-radius: 14px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 800;
    transition: background 0.3s;
}
.gep-btn-strip:hover { background: rgba(255,255,255,0.2); }

@media (max-width: 1200px) {
    .gep-telemetry-grid { grid-template-columns: repeat(2, 1fr); }
    .gep-nav-matrix { grid-template-columns: repeat(3, 1fr); }
    .gep-curated-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .gep-promo-banner-premium { padding: 30px; flex-direction: column; text-align: center; }
    .gep-banner-visual { order: -1; margin-bottom: 30px; }
    .gep-promo-title { font-size: 32px !important; }
    .gep-promo-desc { font-size: 15px !important; }
    .gep-test-matrix { grid-template-columns: 1fr; }
    
    .section-header-sovereign {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
    }
    .header-content {
        width: 100% !important;
    }
    .header-content h3 {
        white-space: normal !important;
        font-size: 20px !important;
    }
    .header-line {
        display: none !important;
    }
    .gep-banner-tag-sovereign {
        margin-bottom: 0 !important;
    }
}

@media (max-width: 480px) {
    .gep-telemetry-grid { grid-template-columns: 1fr; }
    .gep-curated-grid { grid-template-columns: 1fr; }
}

</style>

