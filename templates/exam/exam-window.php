<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! session_id() ) {
    @session_start();
}
$current_lang = isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : 'en';
if ($current_lang !== 'en' && $current_lang !== 'hi') {
    $current_lang = 'en';
}

// Ensure sections_map is set (computed in class-gep-shortcodes.php)
if ( ! isset($sections_map) || empty($sections_map) ) {
    $sections_map = array(0 => 'General');
}

// Group questions by category and calculate letters and counters
$section_letters = array();
$section_questions_count = array();
$section_counters = array();
$question_num_labels = array(); // index => label (e.g. A1, B1)

// Initialize counters
$letter_code = ord('A');
foreach ( $questions as $index => $q ) {
    $q_cat = strval($q->category_id ?: 0);
    if ( ! isset( $section_letters[$q_cat] ) ) {
        $section_letters[$q_cat] = chr($letter_code);
        $letter_code++;
        $section_questions_count[$q_cat] = 0;
        $section_counters[$q_cat] = 1;
    }
    $section_questions_count[$q_cat]++;
}

// Generate labels
foreach ( $questions as $index => $q ) {
    $question_num_labels[$index] = strval($index + 1);
}

// Reset counters for rendering loop
foreach ( $section_counters as $cat_key => $val ) {
    $section_counters[$cat_key] = 1;
}
?>

<!-- Secure Initialization Overlay (Outside Grid layout) -->
<div class="gep-secure-init-overlay" id="gep-secure-init-overlay">
    <div class="gep-secure-init-card" style="box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15) !important; border: 1px solid rgba(226, 232, 240, 0.8) !important; background: #ffffff !important; border-radius: 20px !important; padding: 40px 30px !important;">
        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="#4f46e5" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
        <h2 style="font-size: 24px !important; font-weight: 850 !important; color: #0f172a !important; margin: 0 0 12px 0 !important; letter-spacing: -0.5px !important;">Secure Exam Mode</h2>
        <p style="font-size: 14px !important; color: #475569 !important; line-height: 1.6 !important; margin: 0 0 24px 0 !important; font-weight: 600 !important;">
            To ensure session integrity, this exam operates in a highly secure environment. Please initialize the secure session to enter fullscreen and begin your exam.
        </p>
        
        <!-- Secure Proctoring Protocol Rules List -->
        <div style="background: #f8fafc !important; border: 1px solid #e2e8f0 !important; border-radius: 12px !important; padding: 20px !important; margin-bottom: 30px !important; text-align: left !important;">
            <h4 style="margin: 0 0 12px 0 !important; font-size: 11px !important; font-weight: 800 !important; color: #64748b !important; text-transform: uppercase !important; letter-spacing: 0.8px !important;">Exam Security Protocols</h4>
            <ul style="margin: 0 !important; padding-left: 18px !important; font-size: 13px !important; color: #475569 !important; line-height: 1.7 !important; font-weight: 600 !important;">
                <li style="margin-bottom: 8px !important; list-style-type: disc !important;"><strong>Strict Fullscreen:</strong> Exiting fullscreen mode or switching tabs is logged as a violation.</li>
                <li style="margin-bottom: 8px !important; list-style-type: disc !important;"><strong>Focus Monitoring:</strong> Clicking outside the browser window will temporarily lock your screen.</li>
                <li style="margin-bottom: 0 !important; list-style-type: disc !important;"><strong>Input Lockdown:</strong> Copying, pasting, right-clicks, and keyboard shortcuts are disabled.</li>
            </ul>
        </div>
        
        <button id="gep-enter-secure-btn" class="gep-enter-secure-btn" style="background: #4f46e5 !important; box-shadow: 0 4px 14px 0 rgba(79, 70, 229, 0.3) !important; font-weight: 800 !important; border-radius: 12px !important; padding: 15px 30px !important; transition: all 0.2s !important;">Initialize Secure Session</button>
        <div style="margin-top: 18px; text-align: center;">
            <a href="<?php echo esc_url( gep_get_url('dashboard') ); ?>" style="font-size: 13px !important; font-weight: 700 !important; color: #64748b !important; text-decoration: none !important; transition: color 0.2s !important;" onmouseover="this.style.color='#ef4444';" onmouseout="this.style.color='#64748b';">← Exit to Dashboard</a>
        </div>
    </div>
</div>

<div class="gep-exam-layout">
    <header class="gep-exam-header">
        <div class="gep-header-left">
            <a href="<?php echo esc_url( gep_get_url('dashboard') ); ?>" class="gep-logo-shield" style="text-decoration: none;">GP</a>
            <div class="gep-header-meta">
                <span class="gep-subject-tag"><?php echo esc_html( strtoupper( $subject_tag ) ); ?></span>
                <h2 class="gep-test-title"><?php echo esc_html( $test->title ); ?></h2>
            </div>
        </div>
        <div class="gep-header-right">
            <button class="gep-palette-toggle" id="gep-palette-toggle" title="Toggle Question Palette">📋 <span>Grid</span></button>
            <div class="gep-exam-timer-wrapper" title="Remaining Time">
                <div class="gep-timer-dot pulse"></div>
                <div class="gep-exam-timer" id="gep-timer">00:00:00</div>
            </div>
            
            <!-- Text Zoom Controls -->
            <div class="gep-zoom-controls" style="display:flex;gap:2px;background:rgba(255,255,255,0.1);padding:2px;border-radius:8px;">
                <button type="button" class="gep-zoom-btn" data-zoom="out" title="Decrease Text Size" aria-label="Decrease text size" aria-pressed="false" style="background:none;border:none;color:#f1f5f9;font-weight:800;font-size:12px;cursor:pointer;padding:2px 6px;border-radius:6px;">A-</button>
                <button type="button" class="gep-zoom-btn" data-zoom="in" title="Increase Text Size" aria-label="Increase text size" aria-pressed="false" style="background:none;border:none;color:#f1f5f9;font-weight:800;font-size:12px;cursor:pointer;padding:2px 6px;border-radius:6px;">A+</button>
            </div>

            <div class="gep-lang-selector" style="display: none;">
                <button class="gep-lang-btn<?php echo $current_lang === 'en' ? ' active' : ''; ?>" data-lang="en">EN</button>
                <button class="gep-lang-btn<?php echo $current_lang === 'hi' ? ' active' : ''; ?>" data-lang="hi">HI</button>
            </div>
            <div class="gep-user-badge" title="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                <span class="avatar"><?php echo strtoupper(substr(wp_get_current_user()->display_name, 0, 1)); ?></span>
            </div>
            <button type="button" id="gep-exit-btn" class="gep-exit-btn" data-url="<?php echo esc_url( gep_get_url('dashboard') ); ?>" style="background:#ef4444 !important; color:#fff !important; border:none; border-radius:8px; padding:6px 12px; font-weight:800; font-size:12px; cursor:pointer; text-transform:uppercase; letter-spacing:0.5px; margin-left:10px;">Exit</button>
        </div>
    </header>

    <?php
    // Fetch unique passage IDs for this exam
    $passage_ids = array();
    foreach ($questions as $q) {
        if (!empty($q->passage_id) && $q->passage_id > 0) {
            $passage_ids[] = absint($q->passage_id);
        }
    }
    $passage_ids = array_unique($passage_ids);
    $passages_data = array();
    if (!empty($passage_ids)) {
        $passage_ids_str = implode(',', $passage_ids);
        $passages = $wpdb->get_results("SELECT id, title, translated_data FROM {$wpdb->prefix}gep_questions WHERE id IN ($passage_ids_str)");
        foreach($passages as $p) {
            $passages_data[$p->id] = $p;
        }
    }
    ?>

    <main class="gep-exam-main" id="gep-exam-main-container">
        <!-- Subject/Section Tabs -->
        <div class="gep-sections-bar-wrapper" style="display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span class="gep-sections-title">SECTIONS :</span>
                <div class="gep-sections-tabs" id="gep-sections-tabs">
                    <?php 
                    $first_cat = true;
                    foreach ( $sections_map as $cat_id => $cat_name ) : 
                    ?>
                        <button class="gep-section-tab <?php echo $first_cat ? 'active' : ''; ?>" data-cat-id="<?php echo $cat_id; ?>">
                            <?php echo esc_html( $cat_name ); ?>
                        </button>
                    <?php 
                        $first_cat = false;
                    endforeach; 
                    ?>
                </div>
            </div>
            
            <!-- NTA Style Language Selector Dropdown -->
            <div class="gep-nta-lang-selector-wrap" style="display: flex; align-items: center; gap: 8px;">
                <?php if ( ! empty( $is_lang_locked ) ) : ?>
                    <span style="font-size: 11px; font-weight: 800; color: #475569; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 6px; letter-spacing: 0.3px;" title="This test uses a fixed language and cannot be switched.">🔒 Fixed Language</span>
                <?php else : ?>
                <label style="font-size: 11px; font-weight: 850; color: #475569; text-transform: uppercase; letter-spacing: 0.8px;">View In:</label>
                <select class="gep-nta-lang-select" style="height: 32px; padding: 0 8px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; font-weight: 700; font-size: 13px; color: #1e293b; cursor: pointer; outline: none; transition: border-color 0.2s;">
                    <option value="en"<?php selected($current_lang, 'en'); ?>>English</option>
                    <option value="hi"<?php selected($current_lang, 'hi'); ?>>Hindi</option>
                </select>
                <?php endif; ?>
            </div>
        </div>



        <style>
            #gep-active-passage-pane::-webkit-scrollbar,
            #gep-question-display::-webkit-scrollbar {
                width: 0;
                display: none;
            }
            #gep-active-passage-pane,
            #gep-question-display {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style>
        <div id="gep-exam-split-wrapper" style="display:flex; flex: 1; min-height: 0; min-width: 0; gap: 20px; margin-bottom: 10px;">

            <!-- Passage Pane (Left Column) -->
            <div id="gep-active-passage-pane" style="display:none; flex: 1; min-height: 0; min-width: 0; border: 1px solid #cbd5e1; border-radius: 10px; background: #f8fafc; overflow: hidden; flex-direction: column;">
                <div class="gep-pane-header" style="background: #f1f5f9; padding: 10px 15px; border-bottom: 1px solid #cbd5e1; font-weight: 850; font-size: 12px; color: #475569; display: flex; justify-content: space-between; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>">Read the Passage below:</span>
                    <span class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>">नीचे दिए गए गद्यांश को पढ़ें:</span>
                </div>
                <div id="gep-passage-body" style="flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 20px; font-size: 15px; line-height: 1.6; color: #1e293b;">

                    <!-- Dynamically populated from #gep-passages-container -->
                </div>
            </div>

            <!-- Question Pane (Right Column) -->
            <div id="gep-question-display-wrapper" style="position: relative; flex: 1; min-height: 0; min-width: 0; display: flex; flex-direction: column; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; overflow: visible;">

                <div class="gep-pane-header" style="background: #f1f5f9; padding: 10px 15px; border-bottom: 1px solid #cbd5e1; font-weight: 850; font-size: 12px; color: #475569; display: flex; justify-content: space-between; text-transform: uppercase; letter-spacing: 0.5px;">
                    <span class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>">Question <span class="gep-header-q-num">1</span></span>
                    <span class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>">प्रश्न <span class="gep-header-q-num">1</span></span>
                </div>
                <div id="gep-question-display" style="position: relative; flex: 1; min-height: 0; min-width: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 20px 20px 100px 20px;">

                <?php foreach ( $questions as $index => $q ) : 
                    $pid = isset($q->passage_id) ? absint($q->passage_id) : 0;
                ?>
                <div class="gep-question-block" data-id="<?php echo $q->id; ?>" data-cat-id="<?php echo esc_attr($q->category_id); ?>" data-type="<?php echo esc_attr( isset($q->question_type) ? $q->question_type : 'mcq' ); ?>" data-passage-id="<?php echo $pid; ?>" data-has-translation="<?php echo (isset($q->translation_enabled) && $q->translation_enabled) ? '1' : '0'; ?>" id="q-block-<?php echo $index; ?>" style="display: none;">
                    <div class="gep-question-card">
                        <?php 
                            $qtype = isset($q->question_type) ? $q->question_type : 'mcq';
                            $is_msq = ( $qtype === 'multi_select' || $qtype === 'msq' );
                            $is_numerical = ( $qtype === 'numerical' );
                            $is_matching  = ( $qtype === 'matching' );
                            $is_ar        = ( $qtype === 'assertion_reason' );
                            $qtypes_label = array(
                                'mcq' => 'MCQ', 'multi_select' => 'Multi-Select', 'msq' => 'Multi-Select',
                                'true_false' => 'True / False', 'numerical' => 'Numerical',
                                'matching' => 'Match the Following', 'assertion_reason' => 'Assertion Reason',
                                'short_answer' => 'Short Answer'
                            );
                            $qtag = isset($qtypes_label[$qtype]) ? $qtypes_label[$qtype] : strtoupper($qtype);
                        ?>
                        <div class="gep-q-header">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php if ( ! empty( $q->source ) ) : ?>
                                    <span class="gep-source-badge" style="background: rgba(99,102,241,0.15); color: #818cf8; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.5px;" title="Question Source">
                                        SOURCE: <?php echo esc_html( $q->source ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="gep-q-marks">
                                <span class="pos">+<?php echo $q->marks; ?></span>
                                <?php if ($q->negative_marks > 0) : ?>
                                <span class="neg">-<?php echo $q->negative_marks; ?></span>
                                <?php endif; ?>
                                <?php if ($is_numerical) : ?>
                                <button type="button" class="gep-calc-btn" title="Open Calculator">🖩 Calc</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="gep-q-content">
                            <?php if ($is_ar) : ?>
                                <?php
                                    $trans = !empty($q->translated_data) ? gep_safe_json_decode($q->translated_data, true) : array();
                                    $title_hi = isset($trans['title']) ? $trans['title'] : '';
                                    
                                    $main_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $q->title);
                                    $trans_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $title_hi);
                                    $is_reversed = ($main_has_devanagari && !empty($title_hi) && !$trans_has_devanagari);
                                    
                                    $title_en_val = $is_reversed ? (!empty($title_hi) ? $title_hi : $q->title) : $q->title;
                                    $title_hi_val = $is_reversed ? $q->title : (!empty($title_hi) ? $title_hi : $q->title);
                                    
                                    $display_title_hi = !empty($title_hi_val) ? $title_hi_val : $title_en_val;
                                    $display_title_en = !empty($title_en_val) ? $title_en_val : $display_title_hi;

                                    $ar_parts_en = explode('||', $display_title_en);
                                    $assertion_en = isset($ar_parts_en[0]) ? $ar_parts_en[0] : $display_title_en;
                                    $reason_en    = isset($ar_parts_en[1]) ? $ar_parts_en[1] : '';

                                    $ar_parts_hi = explode('||', $display_title_hi);
                                    $assertion_hi = isset($ar_parts_hi[0]) ? $ar_parts_hi[0] : $display_title_hi;
                                    $reason_hi    = isset($ar_parts_hi[1]) ? $ar_parts_hi[1] : '';
                                ?>
                                <div class="gep-ar-box">
                                    <div class="gep-ar-assertion en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><strong>Assertion (A):</strong> <?php echo wpautop(wp_kses_post($assertion_en)); ?></div>
                                    <?php if ($reason_en) : ?>
                                    <div class="gep-ar-reason en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>" style="margin-top:10px;"><strong>Reason (R):</strong> <?php echo wpautop(wp_kses_post($reason_en)); ?></div>
                                    <?php endif; ?>
                                    <div class="gep-ar-assertion hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><strong>अभिकथन (A):</strong> <?php echo wpautop(wp_kses_post($assertion_hi)); ?></div>
                                    <?php if ($reason_hi) : ?>
                                    <div class="gep-ar-reason hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>" style="margin-top:10px;"><strong>कारण (R):</strong> <?php echo wpautop(wp_kses_post($reason_hi)); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                            <?php 
                                $trans = !empty($q->translated_data) ? gep_safe_json_decode($q->translated_data, true) : array();
                                $title_hi = isset($trans['title']) ? $trans['title'] : '';
                                
                                // Smart detection: if main is Sanskrit/Hindi (Devanagari) and translation is English, swap them
                                $main_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $q->title);
                                $trans_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $title_hi);
                                $is_reversed = ($main_has_devanagari && !empty($title_hi) && !$trans_has_devanagari);
                                
                                $text_en = $is_reversed ? (!empty($title_hi) ? $title_hi : $q->title) : $q->title;
                                $text_hi = $is_reversed ? $q->title : (!empty($title_hi) ? $title_hi : $q->title);
                                
                                $display_text_hi = !empty($text_hi) ? $text_hi : $text_en;
                                $display_text_en = !empty($text_en) ? $text_en : $display_text_hi;
                            ?>
                            <div class="gep-q-text-wrapper en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>">
                                <?php echo gep_clean_wpautop_tables( wpautop( wp_kses_post( $display_text_en ) ) ); ?>
                            </div>
                            <div class="gep-q-text-wrapper hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>">
                                <?php echo gep_clean_wpautop_tables( wpautop( wp_kses_post( $display_text_hi ) ) ); ?>
                            </div>
                            <?php endif; ?>

                            <?php if ( $q->image_url ) : ?>
                                <div class="gep-q-image"><img src="<?php echo esc_url( $q->image_url ); ?>" alt="Question Image"></div>
                            <?php endif; ?>
                        </div>

                        <?php if ( $is_msq ) : ?>
                        <div class="gep-msq-notice" style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:8px 14px;background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:10px;">
                            <span style="font-size:14px;">☑️</span>
                            <span style="font-size:12px;font-weight:700;color:#6366f1;">Multiple Correct — Select all that apply</span>
                        </div>
                        <?php endif; ?>

                        <div class="gep-options-container" data-qtype="<?php echo esc_attr( $qtype ); ?>">
                            <?php if ( $qtype === 'short_answer' ) : ?>
                                <div class="gep-short-ans-wrapper" style="margin-top: 20px;">
                                    <label style="display: block; font-weight: 800; font-size: 12px; color: var(--gep-muted); margin-bottom: 12px; text-transform: uppercase;">Your Answer</label>
                                    <input type="text" class="gep-text-ans" name="q<?php echo $q->id; ?>" placeholder="Type your answer here..." style="width: 100%; padding: 15px 20px; border-radius: 12px; border: 2px solid #eef2f6; font-size: 16px; font-weight: 700; transition: all 0.3s;" data-id="<?php echo $q->id; ?>">
                                </div>
                            <?php elseif ( $is_numerical ) : ?>
                                <div class="gep-numerical-wrapper" style="margin-top: 20px;">
                                    <label style="display: block; font-weight: 800; font-size: 12px; color: #f59e0b; margin-bottom: 12px; text-transform: uppercase;">⌨️ Enter Numerical Answer</label>
                                    <div style="display:flex;gap:12px;align-items:center;">
                                        <input type="number" step="any" class="gep-numerical-ans" name="q<?php echo $q->id; ?>" placeholder="e.g. 3.14" style="flex:1;padding:15px 20px;border-radius:12px;border:2px solid #f59e0b;font-size:22px;font-weight:800;max-width:280px;" data-id="<?php echo $q->id; ?>">
                                        <button type="button" class="gep-calc-btn" title="Open Calculator" style="padding:10px 20px;background:#1e293b;border:1px solid #f59e0b;color:#f59e0b;border-radius:10px;font-size:16px;cursor:pointer;">🖩</button>
                                    </div>
                                    <p style="margin:8px 0 0;font-size:11px;color:#94a3b8;font-weight:600;">Tolerance: ±<?php echo isset($q->numerical_tolerance) ? $q->numerical_tolerance : '0.01'; ?></p>
                                </div>
                            <?php elseif ( $is_ar ) : ?>
                                <?php
                                    /* AR standard options */
                                    $ar_options = array(
                                        'A' => 'Both A and R are true, and R is the correct explanation of A.',
                                        'B' => 'Both A and R are true, but R is NOT the correct explanation of A.',
                                        'C' => 'A is true but R is false.',
                                        'D' => 'A is false but R is true.',
                                    );
                                    $ar_options_hi = array(
                                        'A' => 'A और R दोनों सही हैं, और R, A की सही व्याख्या है।',
                                        'B' => 'A और R दोनों सही हैं, लेकिन R, A की सही व्याख्या नहीं है।',
                                        'C' => 'A सही है लेकिन R गलत है।',
                                        'D' => 'A गलत है लेकिन R सही है।',
                                    );
                                    $ar_options_sa = array(
                                        'A' => 'A तथा R द्वयम् अपि सत्यम् अस्ति, R च A इत्यस्य समीचीना व्याख्या अस्ति।',
                                        'B' => 'A तथा R द्वयम् अपि सत्यम् अस्ति, परन्तु R च A इत्यस्य समीचीना व्याख्या नास्ति।',
                                        'C' => 'A सत्यम् अस्ति परन्तु R असत्यम् अस्ति।',
                                        'D' => 'A असत्यम् अस्ति परन्तु R सत्यम् अस्ति।',
                                    );
                                ?>
                                <div style="margin-top:16px;">
                                    <?php foreach ($ar_options as $opt_key => $opt_text) : ?>
                                    <label class="gep-option-card" data-opt="<?php echo $opt_key; ?>">
                                        <input type="radio" name="q<?php echo $q->id; ?>" value="<?php echo $opt_key; ?>">
                                        <div class="gep-opt-content">
                                            <span class="gep-opt-prefix"><?php echo $opt_key; ?>.</span>
                                            <span class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><?php echo esc_html($opt_text); ?></span>
                                            <span class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><?php echo esc_html($ar_options_hi[$opt_key]); ?></span>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <?php 
                                    $opts = array('A', 'B', 'C', 'D');
                                    if ( ! empty( $q->option_e ) ) {
                                        $opts[] = 'E';
                                    }
                                    $input_type = $is_msq ? 'checkbox' : 'radio';

                                    // ── OPTION SHUFFLING ──────────────────────────────────────
                                    $sec_shuffle_options = false;
                                    $sec_id = isset($q->category_id) ? $q->category_id : '';
                                    $sec_idx = null;
                                    if ( strpos( $sec_id, 'sec_' ) === 0 ) {
                                        $sec_idx = intval( substr( $sec_id, 4 ) );
                                    }
                                    $test_td = !empty($test->translated_data) ? gep_safe_json_decode($test->translated_data, true) : array();
                                    if ( $sec_idx !== null && isset( $test_td['sections'][$sec_idx] ) ) {
                                        $sec_conf = $test_td['sections'][$sec_idx];
                                        $sec_shuffle_options = isset( $sec_conf['shuffle_options'] ) && $sec_conf['shuffle_options'];
                                    } else {
                                        $sec_shuffle_options = ( isset($test->shuffle_options) && $test->shuffle_options );
                                    }

                                    if ( $sec_shuffle_options ) {
                                        $seed = isset($attempt) && isset($attempt->id) ? (int)$q->id * 997 + (int)$attempt->id : (int)$q->id * 997;
                                        mt_srand($seed);
                                        gep_seeded_shuffle($opts);
                                    }

                                    $display_labels = array('A', 'B', 'C', 'D', 'E');
                                    foreach($opts as $display_idx => $orig_letter) : 
                                        $key = 'option_' . strtolower($orig_letter);
                                        $val_en = $q->$key;
                                        $val_hi = isset($trans[$key]) ? $trans[$key] : '';
                                        
                                        $text_en = $is_reversed ? (!empty($val_hi) ? $val_hi : $val_en) : $val_en;
                                        $text_hi = $is_reversed ? $val_en : (!empty($val_hi) ? $val_hi : $val_en);
                                        
                                        if ( $text_en === '' && $text_hi === '' ) continue; 
                                        $display_label = $display_labels[$display_idx]; 
                                        
                                        $display_opt_hi = !empty($text_hi) ? $text_hi : $text_en;
                                        $display_opt_en = !empty($text_en) ? $text_en : $display_opt_hi;
                                ?>
                                    <label class="gep-option-card" data-opt="<?php echo $orig_letter; ?>">
                                        <input type="<?php echo $input_type; ?>" name="q<?php echo $q->id; ?><?php echo $is_msq ? '[]' : ''; ?>" value="<?php echo $orig_letter; ?>">
                                        <div class="gep-opt-content">
                                            <span class="gep-opt-prefix"><?php echo $display_label; ?>.</span>
                                            <div class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post( $display_opt_en ) ); ?></div>
                                            <div class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post( $display_opt_hi ) ); ?></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div> <!-- Close #gep-question-display -->
            <button type="button" id="gep-scroll-to-options-btn" class="gep-scroll-arrow-btn" title="Go to Options">
                ↓
            </button>
        </div> <!-- Close #gep-question-display-wrapper -->
        </div> <!-- Close #gep-exam-split-wrapper -->
    </main>

    <!-- NTA-Style Calculator Modal -->
    <div id="gep-calculator-modal" style="display:none;position:fixed;inset:0;z-index:99998;align-items:center;justify-content:center;background:rgba(0,0,0,0.7);">
        <div style="background:#1e293b;border:1px solid #f59e0b;border-radius:20px;padding:24px;width:280px;box-shadow:0 32px 80px rgba(0,0,0,0.6);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;color:#f1f5f9;font-size:14px;font-weight:800;">🖩 Calculator</h3>
                <button id="gep-calc-close" style="background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;">✕</button>
            </div>
            <input id="gep-calc-display" type="text" readonly style="width:100%;padding:12px;background:#0f172a;border:1px solid #334155;border-radius:10px;color:#f1f5f9;font-size:22px;font-weight:800;text-align:right;margin-bottom:12px;box-sizing:border-box;">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                <?php
                $calc_btns = ['C','±','%','÷','7','8','9','×','4','5','6','-','1','2','3','+','0','.','⌫','='];
                foreach($calc_btns as $cb) :
                    $color = in_array($cb, ['÷','×','-','+','=']) ? '#f59e0b' : ($cb==='C' ? '#ef4444' : '#334155');
                ?>
                <button class="gep-calc-key" data-key="<?php echo esc_attr($cb); ?>" style="background:<?php echo $color; ?>;color:#f1f5f9;border:none;border-radius:8px;padding:14px 8px;font-size:16px;font-weight:800;cursor:pointer;transition:all 0.15s;"><?php echo esc_html($cb); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <aside class="gep-exam-sidebar" id="gep-exam-sidebar">
        <!-- Desktop Collapse Toggle Button -->
        <button id="gep-sidebar-collapse-toggle" class="gep-sidebar-collapse-toggle" title="Toggle Sidebar">
            <span class="toggle-icon">❯</span>
        </button>
        <!-- User Information Header (Disabled to keep simple & real exam like) -->
        <!--
        <div class="gep-sidebar-profile-header">
            <div class="gep-sidebar-avatar-wrap">
                <span class="avatar"><?php echo strtoupper(substr(wp_get_current_user()->display_name, 0, 1)); ?></span>
            </div>
            <div class="gep-sidebar-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></div>
        </div>
        -->

        <!-- NTA 5-State Legend -->
        <div class="gep-palette-legend">
            <div class="gep-legend-item"><div class="gep-legend-dot answered" id="count-answered">0</div>Answered</div>
            <div class="gep-legend-item"><div class="gep-legend-dot not-answered" id="count-not-answered">0</div>Not Answered</div>
            <div class="gep-legend-item"><div class="gep-legend-dot not-visited" id="count-not-visited">0</div>Not Visited</div>
            <div class="gep-legend-item"><div class="gep-legend-dot flagged" id="count-flagged">0</div>Marked</div>
            <div class="gep-legend-item"><div class="gep-legend-dot answered-flagged" id="count-ans-marked">0</div>Ans+Marked</div>
        </div>

        <style>
            body, html { overflow: hidden !important; }
        </style>

        <!-- Grid of Question Numbers -->
        <div class="gep-palette-grid-container" style="overflow-y: auto; padding-bottom: 20px;">
            <?php 
            $current_cat = null;
            $sections_map = (array) $sections_map;
            $sections_keys = array_keys($sections_map);
            $first_sec_id = isset($sections_keys[0]) ? strval($sections_keys[0]) : '';
            foreach ( $questions as $index => $q ) :
                $q_cat = strval($q->category_id);
                if ($q_cat !== $current_cat) {
                    if ($current_cat !== null) {
                        echo '</div></div>';
                    }
                    $current_cat = $q_cat;
                    $cat_name = isset($sections_map[$q_cat]) ? $sections_map[$q_cat] : 'Section';
                    $sec_letter = isset($section_letters[$q_cat]) ? $section_letters[$q_cat] : 'A';
                    $is_active_sec = (strval($q_cat) === $first_sec_id);
                    $style_attr = $is_active_sec ? '' : ' style="display:none"';
                    echo '<div class="gep-palette-section-group" data-cat-id="' . esc_attr($q_cat) . '"' . $style_attr . '>';
                    echo '<div class="gep-palette-section-title" style="margin-top: 15px; margin-bottom: 8px; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">' . esc_html($cat_name) . '</div>';
                    echo '<div class="gep-palette-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px;">';
                }
            ?>
                <button class="gep-palette-btn not-visited" data-id="<?php echo $q->id; ?>" data-cat-id="<?php echo $q_cat; ?>" data-index="<?php echo $index; ?>" title="Q<?php echo $index + 1; ?>">
                    <?php echo $index + 1; ?>
                </button>
            <?php endforeach; ?>
            <?php if (!empty($questions)) echo '</div></div>'; ?>
        </div>

        <!-- Sidebar Actions at the bottom -->
        <div class="gep-sidebar-bottom-actions">
            <div class="gep-row-actions">
                <button type="button" id="gep-btn-question-paper" class="gep-btn gep-btn-info">Question Paper</button>
                <button type="button" id="gep-btn-instructions" class="gep-btn gep-btn-info">Instructions</button>
            </div>
            <button type="button" id="gep-submit-btn" class="gep-btn gep-btn-primary gep-btn-block">Submit Test</button>
        </div>
    </aside>

    <footer class="gep-exam-footer">
        <div class="gep-footer-left">
            <button id="gep-review-btn" class="gep-btn gep-btn-warning">
                <span class="gep-hide-mobile">Mark for Review & Next</span>
                <span class="gep-show-mobile">Review & Next</span>
            </button>
            <button id="gep-clear-btn" class="gep-btn gep-btn-danger">
                <span class="gep-hide-mobile">Clear Response</span>
                <span class="gep-show-mobile">Clear</span>
            </button>
        </div>
        <div class="gep-footer-right">
            <button id="gep-prev-btn" class="gep-btn gep-btn-secondary">
                <span class="gep-hide-mobile">← Previous</span>
                <span class="gep-show-mobile">← Prev</span>
            </button>
            <button id="gep-next-btn" class="gep-btn gep-btn-primary">
                <span class="gep-hide-mobile">Save & Next →</span>
                <span class="gep-show-mobile">Save & Next</span>
            </button>
        </div>
    </footer>
</div> <!-- .gep-exam-layout -->

<!-- Hidden / Helper Containers (Outside Grid layout to prevent browser auto-placement bugs) -->

<!-- Preloaded Passages (Hidden) -->
<div id="gep-passages-container" style="display:none;">
    <?php foreach($passages_data as $pid => $pdata): 
        $p_trans = !empty($pdata->translated_data) ? gep_safe_json_decode($pdata->translated_data, true) : array();
        $p_hi = isset($p_trans['title']) ? $p_trans['title'] : '';
        $display_p_hi = !empty($p_hi) ? $p_hi : $pdata->title;
        $display_p_en = !empty($pdata->title) ? $pdata->title : $display_p_hi;
    ?>
        <div class="gep-passage-content" data-pid="<?php echo $pid; ?>">
            <div class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><?php echo wpautop(wp_kses_post($display_p_en)); ?></div>
            <div class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><?php echo wpautop(wp_kses_post($display_p_hi)); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Hidden Instructions Modal -->
<div id="gep-instructions-modal" class="gep-popup-modal" style="display:none;">
    <div class="gep-popup-modal-content">
        <span class="gep-popup-modal-close" id="gep-inst-close">&times;</span>
        <h2>Exam Instructions</h2>
        <div class="gep-popup-modal-body">
            <?php echo wp_kses_post( isset($test->instructions) ? $test->instructions : '' ); ?>
            <?php 
                $trans = !empty($test->translated_data) ? gep_safe_json_decode($test->translated_data, true) : array();
                $inst_hi = isset($trans['instructions']) ? $trans['instructions'] : '';
                if ( $inst_hi ) :
            ?>
                <div class="hi-text" style="margin-top: 20px; border-top: 1px dashed #cbd5e1; padding-top: 20px;">
                    <?php echo wp_kses_post( $inst_hi ); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Hidden Question Paper Modal -->
<div id="gep-qpaper-modal" class="gep-popup-modal" style="display:none;">
    <div class="gep-popup-modal-content">
        <span class="gep-popup-modal-close" id="gep-qpaper-close">&times;</span>
        <h2>Question Paper</h2>
        <div class="gep-popup-modal-body">
            <div class="gep-qpaper-list">
                <?php 
                $qp_current_cat = null;
                foreach ( $questions as $idx => $q ) : 
                    $q_cat = strval($q->category_id);
                    if ($q_cat !== $qp_current_cat) {
                        $qp_current_cat = $q_cat;
                        $cat_name = isset($sections_map[$q_cat]) ? $sections_map[$q_cat] : 'Section';
                        echo '<div class="gep-qpaper-section-title" style="margin-top: 25px; margin-bottom: 15px; font-size: 16px; font-weight: 850; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">' . esc_html($cat_name) . '</div>';
                    }
                    $trans = !empty($q->translated_data) ? gep_safe_json_decode($q->translated_data, true) : array();
                    $title_hi = isset($trans['title']) ? $trans['title'] : '';
                    $main_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $q->title);
                    $trans_has_devanagari = preg_match('/[\x{0900}-\x{097F}]/u', $title_hi);
                    $is_reversed = ($main_has_devanagari && !empty($title_hi) && !$trans_has_devanagari);
                    
                    $text_en = $is_reversed ? (!empty($title_hi) ? $title_hi : $q->title) : $q->title;
                    $text_hi = $is_reversed ? $q->title : (!empty($title_hi) ? $title_hi : $q->title);
                    $qp_text_hi = !empty($text_hi) ? $text_hi : $text_en;
                    $qp_text_en = !empty($text_en) ? $text_en : $qp_text_hi;
                ?>
                    <div class="gep-qpaper-item">
                        <strong class="gep-qp-qnum">Question <?php echo $idx + 1; ?>:</strong>
                        <div class="gep-qp-qtext en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post($qp_text_en) ); ?></div>
                        <div class="gep-qp-qtext hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post($qp_text_hi) ); ?></div>
                        
                        <div class="gep-qp-options">
                            <?php                                    $opts = array('A', 'B', 'C', 'D');
                                if ( ! empty( $q->option_e ) ) {
                                    $opts[] = 'E';
                                }
                                
                                // ── SECTION OVERRIDE FOR OPTION SHUFFLING ──────────────────
                                $qp_shuffle_opts = false;
                                $qp_sec_id = isset($q->category_id) ? $q->category_id : '';
                                $qp_sec_idx = null;
                                if ( strpos( $qp_sec_id, 'sec_' ) === 0 ) {
                                    $qp_sec_idx = intval( substr( $qp_sec_id, 4 ) );
                                }
                                if ( $qp_sec_idx !== null && isset( $test_td['sections'][$qp_sec_idx] ) ) {
                                    $qp_sec_conf = $test_td['sections'][$qp_sec_idx];
                                    $qp_shuffle_opts = isset( $qp_sec_conf['shuffle_options'] ) && $qp_sec_conf['shuffle_options'];
                                } else {
                                    $qp_shuffle_opts = ( isset($test->shuffle_options) && $test->shuffle_options );
                                }

                                if ( $qp_shuffle_opts ) {
                                    $seed = isset($attempt) && isset($attempt->id) ? (int)$q->id * 997 + (int)$attempt->id : (int)$q->id * 997;
                                    mt_srand($seed);
                                    gep_seeded_shuffle($opts); 
                                }
                                $labels = array('1', '2', '3', '4', '5');
                                foreach ( $opts as $opt_idx => $orig_letter ) :
                                    $fld = 'option_' . strtolower($orig_letter);
                                    $opt_en_val = $q->$fld;
                                    $opt_hi_val = isset($trans[$fld]) ? $trans[$fld] : '';
                                    
                                    $opt_en = $is_reversed ? (!empty($opt_hi_val) ? $opt_hi_val : $opt_en_val) : $opt_en_val;
                                    $opt_hi = $is_reversed ? $opt_en_val : (!empty($opt_hi_val) ? $opt_hi_val : $opt_en_val);
                                    
                                    if ( $opt_en === '' && $opt_hi === '' ) continue;
                                    $qp_opt_hi = !empty($opt_hi) ? $opt_hi : $opt_en;
                                    $qp_opt_en = !empty($opt_en) ? $opt_en : $qp_opt_hi;
                            ?>
                                <div class="gep-qp-opt">
                                    <span class="gep-qp-opt-lbl"><?php echo $labels[$opt_idx]; ?>.</span>
                                    <span class="en-text<?php echo $current_lang === 'en' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post($qp_opt_en) ); ?></span>
                                    <span class="hi-text<?php echo $current_lang === 'hi' ? ' active' : ''; ?>"><?php echo gep_clean_wpautop_tables( wp_kses_post($qp_opt_hi) ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="gep-floating-scroll-btns" class="gep-floating-scroll-wrapper">
    <button type="button" id="gep-float-scroll-up" class="gep-float-btn" title="Scroll to Top">↑</button>
    <button type="button" id="gep-float-scroll-down" class="gep-float-btn" title="Scroll to Bottom">↓</button>
</div>

