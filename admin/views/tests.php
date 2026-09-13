<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb;
$admin_tests = new GEP_Admin_Tests();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
?>

<div class="wrap gep-admin-wrap">
    <?php if ( $action === 'add' || $action === 'edit' ) : 
        global $wpdb;
        $test = null;
        if ( $action === 'edit' && isset($_GET['id']) ) {
            $table = $wpdb->prefix . 'gep_tests';
            $test = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", absint($_GET['id']) ) );
            
            if ( ! $test ) {
                echo '<div class="notice notice-error"><p>Exam blueprint not found.</p></div>';
                return;
            }

            // Get linked questions
            $test_questions_table = $wpdb->prefix . 'gep_test_questions';
            $linked_questions = $wpdb->get_col( $wpdb->prepare( "SELECT question_id FROM $test_questions_table WHERE test_id = %d ORDER BY order_no ASC", $test->id ) );
            $question_ids_str = implode(',', $linked_questions);
        } else {
            $question_ids_str = '';
        }
        if (GEP_Admin_Tests::$submitted_test) {
            $test = GEP_Admin_Tests::$submitted_test;
            $question_ids_str = sanitize_text_field($_POST['question_ids'] ?? '');
        }
    ?>
        <?php if (GEP_Admin_Tests::$save_error) : ?><div class="notice notice-error" role="alert"><p><?php echo esc_html(GEP_Admin_Tests::$save_error); ?></p></div><?php endif; ?>
        <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h1 style="margin: 0;"><?php echo $action === 'add' ? 'Create Test' : 'Edit Test'; ?></h1>
                <p style="color: var(--admin-muted); font-weight: 600;">Add questions, set timing and pricing, then save a draft or publish.</p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=gep-tests'); ?>" class="button" style="border-radius: 12px; font-weight: 700; height: 45px; line-height: 45px; padding: 0 25px;">← Back to Tests</a>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('gep_test_save', 'gep_test_nonce'); ?>
            <?php if ($test) : ?>
                <input type="hidden" name="test_id" value="<?php echo $test->id; ?>">
            <?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     EXAM TYPE PRESET SELECTOR
══════════════════════════════════════════════════════ -->
<div id="gep-preset-banner" style="margin-bottom: 25px; background: linear-gradient(135deg, #0f172a, #1e293b); border: 1px solid #334155; border-radius: 20px; padding: 20px 25px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="margin: 0; font-size: 15px; color: #f1f5f9; font-weight: 800;">⚡ Exam Preset Engine</h3>
            <p style="margin: 4px 0 0; font-size: 12px; color: #64748b; font-weight: 600;">Click a preset to auto-fill duration, marks, negative marks &amp; sections.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="gep-preset-btn" data-preset="jee_main"
                style="background: #1e3a5f; color: #60a5fa; border: 1px solid #2563eb; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">🔬 JEE Main</button>
            <button type="button" class="gep-preset-btn" data-preset="jee_advanced"
                style="background: #1e3a5f; color: #818cf8; border: 1px solid #4f46e5; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">⚗️ JEE Advanced</button>
            <button type="button" class="gep-preset-btn" data-preset="neet"
                style="background: #1a3828; color: #34d399; border: 1px solid #10b981; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">🧬 NEET</button>
            <button type="button" class="gep-preset-btn" data-preset="upsc"
                style="background: #3b1f1f; color: #f87171; border: 1px solid #ef4444; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">🏛️ UPSC Prelims</button>
            <button type="button" class="gep-preset-btn" data-preset="ssc_cgl"
                style="background: #2d2400; color: #fbbf24; border: 1px solid #f59e0b; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">📋 SSC CGL</button>
            <button type="button" class="gep-preset-btn" data-preset="banking_po"
                style="background: #1f2d3b; color: #38bdf8; border: 1px solid #0284c7; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">🏦 Banking PO</button>
            <button type="button" class="gep-preset-btn" data-preset="gate"
                style="background: #2d1f3b; color: #c084fc; border: 1px solid #9333ea; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">🖥️ GATE</button>
            <button type="button" class="gep-preset-btn" data-preset="custom"
                style="background: #1e293b; color: #94a3b8; border: 1px solid #475569; padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">✏️ Custom</button>
        </div>
    </div>
    <input type="hidden" name="exam_mode" id="gep_exam_mode" value="<?php echo esc_attr($test && isset($test->exam_mode) ? $test->exam_mode : 'custom'); ?>">
</div>

            <div class="gep-admin-grid" style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 30px; align-items: start;">
                <div class="gep-main-settings">
                    <div class="gep-admin-console">
                        <div class="gep-admin-console-header">
                            <h2 style="margin: 0; font-size: 20px;">Test Details</h2>
                        </div>
                        <div class="gep-admin-console-body">
                            <div style="margin-bottom: 25px;">
                                <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Exam Title</label>
                                <input type="text" name="title" value="<?php echo $test ? esc_attr($test->title) : ''; ?>" placeholder="e.g. UPSC Prelims Mock 2026" required style="font-size: 18px; font-weight: 700;">
                            </div>

                            <div style="margin-bottom: 25px;">
                                <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Instructions (English)</label>
                                <?php wp_editor($test ? $test->instructions : '', 'instructions', array('textarea_rows' => 4)); ?>
                            </div>

                            <?php 
                                $trans = $test ? gep_safe_json_decode($test->translated_data, true) : array();
                                $instructions_hi = isset($trans['instructions']) ? $trans['instructions'] : '';
                            ?>
                            <div>
                                <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Instructions (Hindi Translation)</label>
                                <?php wp_editor($instructions_hi, 'instructions_hi', array('textarea_rows' => 4)); ?>
                            </div>
                        </div>
                    </div>

                    <div class="gep-admin-console" id="gep-questions-box" <?php echo ($test && $test->type === 'series') ? 'style="display:none;"' : ''; ?>>
                        <div class="gep-admin-console-header" style="background: #f0f9ff; border-bottom-color: #bae6fd;">
                            <h2 style="margin: 0; font-size: 20px; color: #0369a1;">Question Logic Engine</h2>
                        </div>
                        <div class="gep-admin-console-body">

                            <!-- ══════════════════════════════════════════════════════
                                 MULTI-SUBJECT SECTION BUILDER
                                 Allows: Physics (Q IDs), Chemistry (Q IDs), Maths (Q IDs)
                            ══════════════════════════════════════════════════════ -->
                            <div style="margin-bottom: 25px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <h4 style="margin: 0; font-size: 15px; color: #0369a1; display: flex; align-items: center; gap: 8px;">📚 Subject-Wise Sections <span style="font-size: 11px; background: #bae6fd; color: #0369a1; padding: 2px 8px; border-radius: 10px; font-weight: 700;">Multi-Subject</span></h4>
                                        <p style="margin: 4px 0 0; font-size: 12px; color: #64748b; font-weight: 600;">Add Physics, Chemistry, Maths or any subjects — each with their own question IDs.</p>
                                    </div>
                                    <button type="button" id="gep-add-section-btn" style="background: linear-gradient(135deg, #0369a1, #0284c7); color: #fff; border: none; padding: 8px 18px; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">+ Add Subject</button>
                                </div>

                                <?php
                                    // Decode existing sections from translated_data
                                    $existing_sections = array();
                                    if ( $test && !empty($test->translated_data) ) {
                                        $td = gep_safe_json_decode($test->translated_data, true);
                                        if ( isset($td['sections']) && is_array($td['sections']) ) {
                                            $existing_sections = $td['sections'];
                                        }
                                    }
                                    // If no sections but has question_ids, create a default section
                                    if ( empty($existing_sections) && !empty($question_ids_str) ) {
                                        $existing_sections = array(
                                            array('name' => 'General', 'ids' => $question_ids_str, 'time_limit' => 0)
                                        );
                                    }
                                    // Always show at least 1 section
                                    if ( empty($existing_sections) ) {
                                        $existing_sections = array(
                                            array('name' => '', 'ids' => '')
                                        );
                                    }
                                ?>

                                <div id="gep-sections-list">
                                    <?php foreach ($existing_sections as $sec_i => $sec) : ?>
                                    <div class="gep-section-row" style="background: #f8fbff; border: 1px solid #bae6fd; border-radius: 16px; padding: 18px 20px; margin-bottom: 12px; position: relative;">
                                        <div style="display: grid; grid-template-columns: 180px 1fr 100px auto; gap: 12px; align-items: end;">
                                            <div>
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px; display: block;">Subject Name</label>
                                                <input type="text" name="sections[<?php echo $sec_i; ?>][name]" value="<?php echo esc_attr($sec['name']); ?>" placeholder="e.g. Physics" style="font-weight: 800; font-size: 14px;">
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px; display: block;">Question IDs <span style="font-weight: 500; text-transform: none; color: #94a3b8;">(comma-separated)</span></label>
                                                <input type="text" name="sections[<?php echo $sec_i; ?>][ids]" value="<?php echo esc_attr($sec['ids']); ?>" placeholder="e.g. 102, 205, 301" style="font-family: monospace; font-size: 14px;">
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 6px; display: block;">Time Limit (Min)</label>
                                                <input type="number" name="sections[<?php echo $sec_i; ?>][time_limit]" value="<?php echo isset($sec['time_limit']) ? esc_attr($sec['time_limit']) : '0'; ?>" placeholder="0 = No limit" style="font-weight: 800; font-size: 14px;">
                                            </div>
                                            <?php if ($sec_i > 0) : ?>
                                            <div>
                                                <button type="button" class="gep-remove-section" style="background: #fee2e2; color: #b91c1c; border: none; width: 36px; height: 36px; border-radius: 8px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-bottom: 2px;">✕</button>
                                            </div>
                                            <?php else : ?>
                                            <div style="width: 36px;"></div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Section level Shuffling and Marks configuration -->
                                        <div style="margin-top: 12px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; background: rgba(186,230,253,0.15); padding: 12px 16px; border-radius: 12px; border: 1px dashed #bae6fd;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 11px; color: #1e293b; text-transform: uppercase;">
                                                    <input type="checkbox" name="sections[<?php echo $sec_i; ?>][shuffle_questions]" value="1" <?php if(isset($sec['shuffle_questions']) && $sec['shuffle_questions']) echo 'checked'; ?>>
                                                    Shuffle Questions
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 11px; color: #1e293b; text-transform: uppercase;">
                                                    <input type="checkbox" name="sections[<?php echo $sec_i; ?>][shuffle_options]" value="1" <?php if(isset($sec['shuffle_options']) && $sec['shuffle_options']) echo 'checked'; ?>>
                                                    Shuffle Options
                                                </label>
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Shuffle Start Q#:</label>
                                                <input type="number" name="sections[<?php echo $sec_i; ?>][shuffle_start]" value="<?php echo isset($sec['shuffle_start']) ? esc_attr($sec['shuffle_start']) : ''; ?>" placeholder="e.g. 1" style="width: 70px; height: 28px; font-size: 12px; padding: 2px 6px;">
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Shuffle End Q#:</label>
                                                <input type="number" name="sections[<?php echo $sec_i; ?>][shuffle_end]" value="<?php echo isset($sec['shuffle_end']) ? esc_attr($sec['shuffle_end']) : ''; ?>" placeholder="e.g. 10" style="width: 70px; height: 28px; font-size: 12px; padding: 2px 6px;">
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center; margin-left: auto;">
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Marks (+):</label>
                                                <input type="number" step="any" name="sections[<?php echo $sec_i; ?>][marks]" value="<?php echo isset($sec['marks']) ? esc_attr($sec['marks']) : '2'; ?>" placeholder="e.g. 2" style="width: 70px; height: 28px; font-weight: 800; font-size: 12px; padding: 2px 6px;">
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center;">
                                                <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Neg (-):</label>
                                                <input type="number" step="any" name="sections[<?php echo $sec_i; ?>][negative_marks]" value="<?php echo isset($sec['negative_marks']) ? esc_attr($sec['negative_marks']) : '0'; ?>" placeholder="e.g. 0" style="width: 70px; height: 28px; font-weight: 800; font-size: 12px; padding: 2px 6px;">
                                            </div>
                                        </div>

                                        <!-- AI Inject Row -->
                                        <div style="margin-top: 12px; display: flex; gap: 10px; align-items: center;">
                                            <select class="gep-sec-fetch-cat" style="flex: 1; height: 36px; border-radius: 8px; border: 1px solid #bae6fd; font-weight: 700; font-size: 12px;">
                                                <option value="">🪄 AI Inject: Choose subject to fetch random Q IDs...</option>
                                                <?php $cats_for_sec = (new GEP_Category())->get_categories(0); foreach($cats_for_sec as $c) echo '<option value="'.$c->id.'">'.esc_html($c->name).'</option>'; ?>
                                            </select>
                                            <input type="number" class="gep-sec-fetch-count" value="10" min="1" max="200" style="width: 70px; height: 36px; border-radius: 8px; border: 1px solid #bae6fd; text-align: center; font-weight: 800;" placeholder="#Qs">
                                            <button type="button" class="gep-sec-inject-btn" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 0 14px; height: 36px; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer; white-space: nowrap;">Inject IDs →</button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- All question IDs (merged from all sections) for backward compat -->
                                <input type="hidden" name="question_ids" id="gep_question_ids" value="<?php echo $question_ids_str; ?>">

                                <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                                    <span class="status-badge" style="background: #fdf2f8; color: #9d174d; padding: 5px 15px;">
                                        TOTAL ASSETS: <strong id="gep_q_count_display"><?php echo $question_ids_str ? count(array_filter(explode(',', $question_ids_str))) : '0'; ?></strong>
                                    </span>
                                    <p style="margin: 0; font-size: 12px; color: var(--admin-muted); font-weight: 600;">Each subject section groups questions by category in the exam.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="gep-admin-console" id="gep-series-box" <?php echo ($test && $test->type === 'series') ? '' : 'style="display:none;"'; ?>>
                        <div class="gep-admin-console-header" style="background: #fdf2f8; border-bottom-color: #fbcfe8;">
                            <h2 style="margin: 0; font-size: 20px; color: #9d174d;">Series Aggregation Engine</h2>
                        </div>
                        <div class="gep-admin-console-body">
                            <p style="font-size: 13px; color: #64748b; font-weight: 600; margin: 0 0 15px;">Link individual single exams to create a Test Series Portfolio (e.g. Weekly Tests 1-5). Each linked exam is accessed separately. For <strong>subject sections within one exam</strong>, use "Single Exam" type with the Multi-Subject sections above.</p>
                            <label style="display: block; font-weight: 800; font-size: 12px; color: var(--admin-muted); margin-bottom: 12px; text-transform: uppercase;">Arrange and Link Tests</label>
                            <?php 
                                $series_ids = '';
                                if($test && $test->type === 'series') {
                                    $series_ids = implode(',', $wpdb->get_col($wpdb->prepare("SELECT test_id FROM {$wpdb->prefix}gep_test_series WHERE series_id = %d ORDER BY order_no ASC", $test->id)));
                                }
                                
                                // Fetch all published Single Tests to add
                                $all_single_tests = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}gep_tests WHERE type = 'single' AND status = 'publish' ORDER BY title ASC");
                            ?>
                            
                            <div style="display: flex; gap: 10px; margin-bottom: 20px; max-width: 600px;">
                                <select id="gep_add_series_test_select" style="flex: 1; height: 38px; border-radius: 6px; border: 1px solid #cbd5e1; font-weight: 700; font-size: 13px; padding: 0 10px;">
                                    <option value="">-- Choose a Single Test --</option>
                                    <?php foreach ($all_single_tests as $st) : ?>
                                        <option value="<?php echo $st->id; ?>" data-title="<?php echo esc_attr($st->title); ?>">#<?php echo $st->id; ?> — <?php echo esc_html($st->title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" id="gep_add_series_test_btn" class="button" style="height: 38px; border-radius: 6px; font-weight: 800; background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;">➕ Add Test</button>
                            </div>

                            <div id="gep_series_sortable_list" style="display: grid; gap: 8px; max-width: 600px; background: #faf5ff; padding: 15px; border-radius: 12px; border: 1px dashed #d8b4fe; min-height: 50px;">
                                <?php if ($series_ids) : 
                                    $linked_test_ids = array_filter(array_map('absint', explode(',', $series_ids)));
                                    if (!empty($linked_test_ids)) :
                                        $ids_in = implode(',', $linked_test_ids);
                                        $linked_tests = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}gep_tests WHERE id IN ($ids_in) ORDER BY FIELD(id, $ids_in)");
                                        foreach ($linked_tests as $lt) :
                                ?>
                                    <div class="gep-series-item" data-id="<?php echo $lt->id; ?>" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                        <span style="font-weight: 800; color: #1e293b; font-size: 13px;">#<?php echo $lt->id; ?> — <?php echo esc_html($lt->title); ?></span>
                                        <div style="display: flex; gap: 6px; align-items: center;">
                                            <button type="button" class="gep-series-move-up" style="background:#f1f5f9; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">▲ Up</button>
                                            <button type="button" class="gep-series-move-down" style="background:#f1f5f9; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">▼ Down</button>
                                            <button type="button" class="gep-series-remove" style="background:#fee2e2; color:#b91c1c; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">✕ Remove</button>
                                        </div>
                                    </div>
                                <?php endforeach; endif; endif; ?>
                            </div>
                            
                            <input type="hidden" name="series_test_ids" id="gep_series_test_ids" value="<?php echo $series_ids; ?>">
                        </div>
                    </div>
                </div>


                <div class="gep-side-settings">
                    <div class="gep-admin-console">
                        <div class="gep-admin-console-header">
                            <h2 style="margin: 0; font-size: 16px;">Technical Config</h2>
                        </div>
                        <div class="gep-admin-console-body">
                            <div style="margin-bottom: 20px;">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Category (Target Subject)</label>
                                <select name="category_id" id="gep_category_id" style="width: 100%;">
                                    <option value="0">General Domain</option>
                                    <?php
                                    $category_logic = new GEP_Category();
                                    $categories = $category_logic->get_categories(0);
                                    foreach($categories as $cat) {
                                        $selected = ($test && $test->category_id == $cat->id) ? 'selected' : '';
                                        echo '<option value="'.$cat->id.'" '.$selected.' style="font-weight:800;">'.esc_html($cat->name).'</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Subcategory</label>
                                <select name="subcategory_id" id="gep_subcategory_id" style="width: 100%;">
                                    <option value="0">General Topic</option>
                                    <?php
                                    if ($test && isset($test->subcategory_id)) {
                                        $sub_id = $test->subcategory_id;
                                    } else {
                                        // Legacy support: if category_id is actually a child, map it back correctly.
                                        $sub_id = 0;
                                        if ($test && $test->category_id) {
                                            $is_child = $wpdb->get_row($wpdb->prepare("SELECT parent_id FROM {$wpdb->prefix}gep_categories WHERE id = %d", $test->category_id));
                                            if ($is_child && $is_child->parent_id > 0) {
                                                // Actually it's a child. This code can't easily fix the saved state during render, 
                                                // but we'll try to show it correctly by fetching subcategories for parent if known.
                                                // Normally, new data saves correctly.
                                            }
                                        }
                                    }
                                    
                                    // Let's just load subcategories if we have a parent category
                                    if ($test && $test->category_id) {
                                        $subcats = $category_logic->get_categories($test->category_id);
                                        foreach($subcats as $sub) {
                                            // Handle $test->subcategory_id which we'll add via DB sync
                                            $selected = ($test && isset($test->subcategory_id) && $test->subcategory_id == $sub->id) ? 'selected' : '';
                                            echo '<option value="'.$sub->id.'" '.$selected.'>'.esc_html($sub->name).'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Test Type</label>
                                <select name="type" style="width: 100%;">
                                    <option value="single" <?php if($test && $test->type == 'single') echo 'selected'; ?>>Single Subject Test</option>
                                    <option value="multiple" <?php if($test && $test->type == 'multiple') echo 'selected'; ?>>Multiple Subject Test</option>
                                    <option value="combined" <?php if($test && $test->type == 'combined') echo 'selected'; ?>>Combined Test</option>
                                    <option value="self_test" <?php if($test && $test->type == 'self_test') echo 'selected'; ?>>Self Testing (User Selected)</option>
                                    <option value="random" <?php if($test && $test->type == 'random') echo 'selected'; ?>>Dynamic Random Test</option>
                                    <option value="series" <?php if($test && $test->type == 'series') echo 'selected'; ?>>Test Series Portfolio</option>
                                </select>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Timer (Min)</label>
                                    <input type="number" name="duration_minutes" value="<?php echo $test ? $test->duration_minutes : '60'; ?>" min="1">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Attempt Limit <span style="color:#6366f1;">(Free users)</span></label>
                                    <input type="number" name="attempt_limit" value="<?php echo $test ? $test->attempt_limit : '1'; ?>" min="0">
                                    <p style="margin: 6px 0 0; font-size: 11px; color: #94a3b8; font-weight: 600; line-height: 1.4;">
                                        ℹ️ Set <strong>0</strong> = Unlimited. Paid purchasers always get <strong>unlimited</strong> attempts automatically.
                                    </p>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Benchmark (%)</label>
                                    <input type="number" name="pass_marks" value="<?php echo $test ? $test->pass_marks : '40'; ?>" style="width: 100%;">
                                </div>
                                <div>
                                    <label style="display: block; font-weight: 800; font-size: 11px; color: #b91c1c; margin-bottom: 8px; text-transform: uppercase;">Global Negative Penalty</label>
                                    <input type="number" step="0.01" name="global_negative_marks" value="<?php echo isset($trans['global_negative_marks']) ? esc_attr($trans['global_negative_marks']) : '0'; ?>" placeholder="e.g. 0.25" style="width: 100%; border-color: #fca5a5; font-weight: 900; color: #b91c1c;">
                                    <p style="margin: 6px 0 0; font-size: 11px; color: #94a3b8; font-weight: 600; line-height: 1.4;">
                                        Set <strong>0</strong> for no negative marking.
                                    </p>
                                </div>
                            </div>



                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Status</label>
                                <select name="status" style="width: 100%;">
                                    <option value="publish" <?php if($test && $test->status == 'publish') echo 'selected'; ?>>Live / Published</option>
                                    <option value="draft" <?php if($test && $test->status == 'draft') echo 'selected'; ?>>Development / Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="gep-admin-console" style="border-top: 5px solid #3b82f6;">
                        <div class="gep-admin-console-header">
                            <h2 style="margin: 0; font-size: 16px; color: #1e3a8a;">Marketplace & Info Settings</h2>
                        </div>
                        <div class="gep-admin-console-body">
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Promotional Thumbnail</label>
                                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                    <input type="text" name="thumbnail" id="gep_thumbnail" value="<?php echo $test ? esc_attr($test->thumbnail) : ''; ?>" placeholder="https://..." style="flex: 1;">
                                    <button type="button" id="gep_upload_thumbnail_btn" class="button" style="height: 40px; border-radius: 8px; font-weight: 700;">Select Image</button>
                                </div>
                                <div id="gep_thumbnail_preview_container" style="margin-top: 10px; <?php echo ($test && $test->thumbnail) ? '' : 'display: none;'; ?>">
                                    <img id="gep_thumbnail_preview" src="<?php echo $test ? esc_attr($test->thumbnail) : ''; ?>" style="max-width: 100%; height: 120px; object-fit: cover; border-radius: 12px; border: 1px solid #e2e8f0;">
                                </div>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Refundable Status</label>
                                <select name="refundable" style="width: 100%;">
                                    <option value="no" <?php echo (isset($trans['refundable']) && $trans['refundable'] === 'no') ? 'selected' : ''; ?>>Non-Refundable</option>
                                    <option value="yes" <?php echo (isset($trans['refundable']) && $trans['refundable'] === 'yes') ? 'selected' : ''; ?>>Refundable</option>
                                </select>
                            </div>

                            <div style="margin-bottom: 20px;">
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Topics Included</label>
                                <textarea name="topics" rows="3" style="width: 100%;" placeholder="e.g. Algebra, Calculus, Trigonometry"><?php echo isset($trans['topics']) ? esc_textarea($trans['topics']) : ''; ?></textarea>
                            </div>

                            <div>
                                <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">What is Offered / Details</label>
                                <textarea name="what_you_get" rows="3" style="width: 100%;" placeholder="e.g. 5 Sectional Tests, Detailed Solutions, AI Doubts"><?php echo isset($trans['what_you_get']) ? esc_textarea($trans['what_you_get']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="gep-admin-console" style="border-top: 5px solid #10b981;">
                        <div class="gep-admin-console-header">
                            <h2 style="margin: 0; font-size: 16px; color: #065f46;">Commercial Engine</h2>
                        </div>
                        <div class="gep-admin-console-body">
                            <div style="background: #f0fdf4; padding: 15px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                                <strong style="font-size: 13px; color: #065f46;">Free Access Mode</strong>
                                <label class="gep-switch">
                                    <input type="checkbox" name="is_free" id="gep_is_free" value="1" <?php if($test && $test->is_free) echo 'checked'; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                            
                            <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Valuation (INR)</label>
                            <input type="number" name="price" id="gep_price" value="<?php echo $test ? $test->price : '0'; ?>" style="font-size: 20px; font-weight: 900; color: #10b981;" <?php if($test && $test->is_free) echo 'disabled'; ?>>

                             <div id="gep-random-pricing-section" style="margin-top: 20px; border-top: 1px dashed #e2e8f0; padding-top: 20px; <?php echo ($test && $test->type == 'random') ? '' : 'display: none;'; ?>">
                                 <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Attempt-Based Pricing Tiers</label>
                                 <div id="gep-pricing-tiers-list" style="display: flex; flex-direction: column; gap: 10px;">
                                     <?php 
                                     $trans = !empty($test->translated_data) ? gep_safe_json_decode($test->translated_data, true) : array();
                                     $attempt_pricing = isset($trans['attempt_pricing']) ? $trans['attempt_pricing'] : array();
                                     if ( empty($attempt_pricing) ) {
                                         $attempt_pricing = array( array('attempts' => 1, 'price' => 0) );
                                     }
                                     foreach ( $attempt_pricing as $idx => $tier ) : 
                                     ?>
                                         <div class="gep-pricing-tier-row" style="display: flex; gap: 10px; align-items: center;">
                                             <input type="number" name="attempt_pricing[<?php echo $idx; ?>][attempts]" value="<?php echo esc_attr($tier['attempts']); ?>" min="1" placeholder="Attempts" style="flex: 1;" required>
                                             <span style="font-weight: 700; color: #64748b;">=</span>
                                             <input type="number" name="attempt_pricing[<?php echo $idx; ?>][price]" value="<?php echo esc_attr($tier['price']); ?>" min="0" placeholder="Price (INR)" style="flex: 1;" required>
                                             <button type="button" class="gep-remove-tier-btn button" style="color: #ef4444; border-color: #fca5a5; font-weight: 700;">×</button>
                                         </div>
                                     <?php endforeach; ?>
                                 </div>
                                 <button type="button" id="gep-add-tier-btn" class="button" style="margin-top: 10px; width: 100%; border-color: #cbd5e1; font-weight: 700;">+ Add Pricing Tier</button>
                             </div>
                        </div>
                        <div class="gep-admin-console-footer" style="padding: 20px;">
                            <button type="submit" name="submit" class="button button-primary" style="width: 100%; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">🚀 Save Test</button>
                        </div>
                    </div>

                    <div class="gep-admin-console">
                        <div class="gep-admin-console-body">
                            <h2>Exam security</h2>
                            <p>Security and violation handling use the portal settings. All tests on this form use the duration entered above.</p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=gep-settings')); ?>">Review portal security settings</a>
                        </div>
                    </div>

                </div>
            </div>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#gep_category_id').on('change', function() {
                var catId = $(this).val();
                var subDropdown = $('#gep_subcategory_id');
                subDropdown.html('<option value="0">Loading...</option>');
                
                $.post(ajaxurl, {
                    action: 'gep_get_subcategories',
                    category_id: catId,
                    nonce: gepAdminAjax.nonce
                }, function(response) {
                    if(response.success) {
                        subDropdown.html('<option value="0">General Topic</option>');
                        if(response.data.length > 0) {
                            $.each(response.data, function(i, item) {
                                subDropdown.append('<option value="' + item.id + '">' + item.name + '</option>');
                            });
                        }
                    } else {
                        subDropdown.html('<option value="0">General Topic</option>');
                    }
                }).fail(function() {
                    subDropdown.html('<option value="0">General Topic</option>');
                });
            });

            // ── Commercial Toggle ──────────────────────────────────────────
            $('#gep_is_free').on('change', function() {
                $('#gep_price').prop('disabled', this.checked);
                if(this.checked) $('#gep_price').val(0);
            });

            // ── Test Type Toggle: Single vs Series ─────────────────
            $('select[name="type"]').on('change', function() {
                const type = $(this).val();
                if (type === 'series') {
                    $('#gep-questions-box').fadeOut(200, function() { $('#gep-series-box').fadeIn(200); });
                } else {
                    $('#gep-series-box').fadeOut(200, function() { $('#gep-questions-box').fadeIn(200); });
                }
            });

            // ── Total Asset Counter (merged from all sections) ─────────────
            function updateTotalCount() {
                let total = 0;
                $('#gep-sections-list input[type="text"][name*="[ids]"]').each(function() {
                    const val = $(this).val().trim();
                    if (val) {
                        total += val.split(',').filter(v => v.trim() !== '').length;
                    }
                });
                $('#gep_q_count_display').text(total);
                // Sync merged IDs to hidden field for backward compatibility
                let allIds = [];
                $('#gep-sections-list input[type="text"][name*="[ids]"]').each(function() {
                    const val = $(this).val().trim();
                    if (val) allIds = allIds.concat(val.split(',').map(v => v.trim()).filter(Boolean));
                });
                $('#gep_question_ids').val(allIds.join(','));
            }

            $(document).on('input', '#gep-sections-list input[name*="[ids]"]', function() {
                updateTotalCount();
            });
            updateTotalCount();

            // ── Section Template (for "Add Subject" button) ────────────────
            function buildSectionRow(idx) {
                var catOptions = '';
                <?php
                    $cats_js = (new GEP_Category())->get_categories(0);
                    foreach ($cats_js as $c) {
                        echo 'catOptions += \'<option value="' . $c->id . '">' . esc_js($c->name) . '</option>\';' . "\n";
                    }
                ?>
                return `
                <div class="gep-section-row" style="background:#f8fbff;border:1px solid #bae6fd;border-radius:16px;padding:18px 20px;margin-bottom:12px;position:relative;">
                    <div style="display:grid;grid-template-columns:180px 1fr 100px auto;gap:12px;align-items:end;">
                        <div>
                            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;margin-bottom:6px;display:block;">Subject Name</label>
                            <input type="text" name="sections[${idx}][name]" placeholder="e.g. Mathematics" style="font-weight:800;font-size:14px;">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;margin-bottom:6px;display:block;">Question IDs <span style="font-weight:500;text-transform:none;color:#94a3b8;">(comma-separated)</span></label>
                            <input type="text" name="sections[${idx}][ids]" placeholder="e.g. 102, 205, 301" style="font-family:monospace;font-size:14px;">
                        </div>
                        <div>
                            <label style="font-size:11px;font-weight:800;text-transform:uppercase;color:#64748b;margin-bottom:6px;display:block;">Time (Min)</label>
                            <input type="number" name="sections[${idx}][time_limit]" placeholder="0" value="0" style="font-weight:800;font-size:14px;">
                        </div>
                        <div>
                            <button type="button" class="gep-remove-section" style="background:#fee2e2;color:#b91c1c;border:none;width:36px;height:36px;border-radius:8px;font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;margin-bottom:2px;">✕</button>
                        </div>
                    </div>

                    <!-- Section level Shuffling and Marks configuration -->
                    <div style="margin-top: 12px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center; background: rgba(186,230,253,0.15); padding: 12px 16px; border-radius: 12px; border: 1px dashed #bae6fd;">
                        <div style="display: flex; gap: 12px; align-items: center;">
                            <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 11px; color: #1e293b; text-transform: uppercase;">
                                <input type="checkbox" name="sections[${idx}][shuffle_questions]" value="1">
                                Shuffle Questions
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 11px; color: #1e293b; text-transform: uppercase;">
                                <input type="checkbox" name="sections[${idx}][shuffle_options]" value="1">
                                Shuffle Options
                            </label>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Shuffle Start Q#:</label>
                            <input type="number" name="sections[${idx}][shuffle_start]" placeholder="e.g. 1" style="width: 70px; height: 28px; font-size: 12px; padding: 2px 6px;">
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Shuffle End Q#:</label>
                            <input type="number" name="sections[${idx}][shuffle_end]" placeholder="e.g. 10" style="width: 70px; height: 28px; font-size: 12px; padding: 2px 6px;">
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-left: auto;">
                            <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Marks (+):</label>
                            <input type="number" step="any" name="sections[${idx}][marks]" value="2" placeholder="e.g. 2" style="width: 70px; height: 28px; font-weight: 800; font-size: 12px; padding: 2px 6px;">
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b;">Neg (-):</label>
                            <input type="number" step="any" name="sections[${idx}][negative_marks]" value="0" placeholder="e.g. 0" style="width: 70px; height: 28px; font-weight: 800; font-size: 12px; padding: 2px 6px;">
                        </div>
                    </div>

                    <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                        <select class="gep-sec-fetch-cat" style="flex:1;height:36px;border-radius:8px;border:1px solid #bae6fd;font-weight:700;font-size:12px;">
                            <option value="">🪄 AI Inject: Choose subject to fetch random Q IDs...</option>
                            ${catOptions}
                        </select>
                        <input type="number" class="gep-sec-fetch-count" value="10" min="1" max="200" style="width:70px;height:36px;border-radius:8px;border:1px solid #bae6fd;text-align:center;font-weight:800;" placeholder="#Qs">
                        <button type="button" class="gep-sec-inject-btn" style="background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:0 14px;height:36px;border-radius:8px;font-weight:800;font-size:12px;cursor:pointer;white-space:nowrap;">Inject IDs →</button>
                    </div>
                </div>`;
            }

            // ── Add Section Button ─────────────────────────────────────────
            $('#gep-add-section-btn').on('click', function() {
                const newIdx = $('#gep-sections-list .gep-section-row').length;
                $('#gep-sections-list').append(buildSectionRow(newIdx));
            });

            // ── Remove Section Button ──────────────────────────────────────
            $(document).on('click', '.gep-remove-section', function() {
                $(this).closest('.gep-section-row').fadeOut(150, function() {
                    $(this).remove();
                    // Renumber section indices to keep them sequential
                    $('#gep-sections-list .gep-section-row').each(function(i) {
                        $(this).find('input, select, textarea').each(function() {
                            const oldName = $(this).attr('name');
                            if (oldName) {
                                const newName = oldName.replace(/sections\[\d+\]/, 'sections[' + i + ']');
                                $(this).attr('name', newName);
                            }
                        });
                    });
                    updateTotalCount();
                });
            });

            // ── Per-Section AI Inject ──────────────────────────────────────
            $(document).on('click', '.gep-sec-inject-btn', function() {
                const row     = $(this).closest('.gep-section-row');
                const catId   = row.find('.gep-sec-fetch-cat').val();
                const count   = parseInt(row.find('.gep-sec-fetch-count').val()) || 10;
                const idsInput= row.find('input[name*="[ids]"]');
                const btn     = $(this);

                if (!catId) { alert('Please choose a subject from the dropdown first.'); return; }

                btn.text('Fetching...').prop('disabled', true);

                $.post(ajaxurl, {
                    action : 'gep_fetch_question_ids',
                    nonce  : '<?php echo wp_create_nonce("gep_test_save"); ?>',
                    cat_id : catId,
                    count  : count
                }, function(res) {
                    btn.text('Inject IDs →').prop('disabled', false);
                    if (res.success && res.data && res.data.ids) {
                        const existing = idsInput.val().trim();
                        const newIds   = res.data.ids.join(', ');
                        idsInput.val(existing ? existing + ', ' + newIds : newIds);
                        updateTotalCount();
                    } else {
                        alert('No questions found for that subject. Please check your question bank.');
                    }
                }).fail(function() {
                    btn.text('Inject IDs →').prop('disabled', false);
                    alert('Network error. Please try again.');
                });
            });

            // ── WP Media Uploader ──────────────────────────────────────────
            var frame;
            $('#gep_upload_thumbnail_btn').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Select or Upload Exam Thumbnail',
                    button: { text: 'Use this image' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#gep_thumbnail').val(attachment.url);
                    $('#gep_thumbnail_preview').attr('src', attachment.url);
                    $('#gep_thumbnail_preview_container').show();
                });
                frame.open();
            });


        // ── Exam Preset Engine ──────────────────────────────────────────────────────
        const GEP_PRESETS = {
            jee_main: {
                name: 'JEE Main', duration: 180, pass_marks: 50,
                marks: 4, neg: 1,
                sections: [
                    { name: 'Physics', count: 30 },
                    { name: 'Chemistry', count: 30 },
                    { name: 'Mathematics', count: 30 }
                ]
            },
            jee_advanced: {
                name: 'JEE Advanced', duration: 180, pass_marks: 40,
                marks: 4, neg: 2,
                sections: [
                    { name: 'Physics', count: 20 },
                    { name: 'Chemistry', count: 20 },
                    { name: 'Mathematics', count: 20 }
                ]
            },
            neet: {
                name: 'NEET UG', duration: 200, pass_marks: 50,
                marks: 4, neg: 1,
                sections: [
                    { name: 'Physics', count: 45 },
                    { name: 'Chemistry', count: 45 },
                    { name: 'Botany', count: 45 },
                    { name: 'Zoology', count: 45 }
                ]
            },
            upsc: {
                name: 'UPSC Prelims GS1', duration: 120, pass_marks: 33,
                marks: 2, neg: 0.67,
                sections: [
                    { name: 'General Studies', count: 100 }
                ]
            },
            ssc_cgl: {
                name: 'SSC CGL Tier 1', duration: 60, pass_marks: 40,
                marks: 2, neg: 0.5,
                sections: [
                    { name: 'General Intelligence', count: 25 },
                    { name: 'General Awareness', count: 25 },
                    { name: 'Quantitative Aptitude', count: 25 },
                    { name: 'English Comprehension', count: 25 }
                ]
            },
            banking_po: {
                name: 'Banking PO Prelim', duration: 60, pass_marks: 40,
                marks: 1, neg: 0.25,
                sections: [
                    { name: 'English Language', count: 30 },
                    { name: 'Quantitative Aptitude', count: 35 },
                    { name: 'Reasoning Ability', count: 35 }
                ]
            },
            gate: {
                name: 'GATE', duration: 180, pass_marks: 25,
                marks: 2, neg: 0.67,
                sections: [
                    { name: 'General Aptitude', count: 10 },
                    { name: 'Technical', count: 55 }
                ]
            },
            custom: { name: 'Custom', duration: 60, pass_marks: 40, marks: 1, neg: 0, sections: [] }
        };

        document.querySelectorAll('.gep-preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const presetKey = this.dataset.preset;
                const preset = GEP_PRESETS[presetKey];
                if (!preset) return;

                // Highlight active button
                document.querySelectorAll('.gep-preset-btn').forEach(b => b.style.opacity = '0.5');
                this.style.opacity = '1';
                this.style.transform = 'scale(1.05)';
                setTimeout(() => this.style.transform = '', 200);

                // Update exam_mode hidden field
                const modeField = document.getElementById('gep_exam_mode');
                if (modeField) modeField.value = presetKey;

                // Fill duration
                const durField = document.querySelector('input[name="duration_minutes"]');
                if (durField) { durField.value = preset.duration; durField.dispatchEvent(new Event('change')); }

                // Fill pass marks
                const passField = document.querySelector('input[name="pass_marks"]');
                if (passField) passField.value = preset.pass_marks;

                // Fill sections
                if (preset.sections.length > 0) {
                    const secList = document.getElementById('gep-sections-list');
                    if (secList) {
                        // Clear existing sections
                        secList.innerHTML = '';
                        preset.sections.forEach((sec, i) => {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = buildSectionRow(i);
                            const row = tempDiv.firstElementChild;
                            
                            // Fill preset specific values
                            $(row).find('input[name*="[name]"]').val(sec.name);
                            $(row).find('input[name*="[ids]"]').attr('placeholder', `Add ${sec.count} question IDs`);
                            $(row).find('input[name*="[marks]"]').val(preset.marks);
                            $(row).find('input[name*="[negative_marks]"]').val(preset.neg);
                            
                            secList.appendChild(row);
                        });
                        updateTotalCount();
                    }
                }

                // Show toast
                const toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#f1f5f9;padding:12px 24px;border-radius:12px;border:1px solid #6366f1;font-weight:700;font-size:13px;z-index:99999;box-shadow:0 8px 32px rgba(0,0,0,0.4);';
                toast.textContent = `✅ ${preset.name} preset applied — ${preset.sections.length} sections created`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            });
        });
        // ^ closes document.querySelectorAll('.gep-preset-btn').forEach(btn => {
        //   This `});` was missing, so every statement below was parsed as part of
        //   the forEach callback and the script ended mid-block with
        //   "SyntaxError: Unexpected end of input". That killed the WHOLE
        //   jQuery(document).ready() callback, so none of the test-editor
        //   behaviour bound: Add Subject, remove section, the section-IDs →
        //   question_ids sync, the category/subcategory dropdown, the
        //   Single/Series toggle, the presets, the series builder and the
        //   attempt-pricing tiers were all dead.

            // ─── Test Series Portfolio Builder ─────────────────────────────
            function updateSeriesIds() {
                var ids = [];
                $('#gep_series_sortable_list .gep-series-item').each(function() {
                    ids.push($(this).data('id'));
                });
                $('#gep_series_test_ids').val(ids.join(','));
            }

            $('#gep_add_series_test_btn').on('click', function() {
                var select = $('#gep_add_series_test_select');
                var val = select.val();
                if (!val) return;
                var title = select.find('option:selected').data('title');
                
                // Check if already in list
                var exists = false;
                $('#gep_series_sortable_list .gep-series-item').each(function() {
                    if (String($(this).data('id')) === String(val)) {
                        exists = true;
                    }
                });
                if (exists) {
                    alert('This test is already linked to the series.');
                    return;
                }
                
                // Add item
                var html = `
                    <div class="gep-series-item" data-id="${val}" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <span style="font-weight: 800; color: #1e293b; font-size: 13px;">#${val} — ${title}</span>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <button type="button" class="gep-series-move-up" style="background:#f1f5f9; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">▲ Up</button>
                            <button type="button" class="gep-series-move-down" style="background:#f1f5f9; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">▼ Down</button>
                            <button type="button" class="gep-series-remove" style="background:#fee2e2; color:#b91c1c; border:none; padding:4px 8px; border-radius:6px; cursor:pointer; font-weight:800; font-size:11px;">✕ Remove</button>
                        </div>
                    </div>
                `;
                $('#gep_series_sortable_list').append(html);
                updateSeriesIds();
                select.val('');
            });

            $(document).on('click', '.gep-series-remove', function() {
                $(this).closest('.gep-series-item').remove();
                updateSeriesIds();
            });

            $(document).on('click', '.gep-series-move-up', function() {
                var item = $(this).closest('.gep-series-item');
                var prev = item.prev('.gep-series-item');
                if (prev.length > 0) {
                    item.insertBefore(prev);
                    updateSeriesIds();
                }
            });

            $(document).on('click', '.gep-series-move-down', function() {
                var item = $(this).closest('.gep-series-item');
                var next = item.next('.gep-series-item');
                if (next.length > 0) {
                    item.insertAfter(next);
                    updateSeriesIds();
                }
            });

            // Type Select Toggle
            $('select[name="type"]').on('change', function() {
                var val = $(this).val();
                if (val === 'random') {
                    $('#gep-random-pricing-section').slideDown();
                } else {
                    $('#gep-random-pricing-section').slideUp();
                }
            });

            // Add Pricing Tier
            var tierIndex = <?php echo isset($attempt_pricing) ? count($attempt_pricing) : 0; ?>;
            $('#gep-add-tier-btn').on('click', function() {
                var html = '<div class="gep-pricing-tier-row" style="display: flex; gap: 10px; align-items: center; margin-top: 8px;">' +
                    '<input type="number" name="attempt_pricing[' + tierIndex + '][attempts]" min="1" placeholder="Attempts" style="flex: 1;" required>' +
                    '<span style="font-weight: 700; color: #64748b;">=</span>' +
                    '<input type="number" name="attempt_pricing[' + tierIndex + '][price]" min="0" placeholder="Price (INR)" style="flex: 1;" required>' +
                    '<button type="button" class="gep-remove-tier-btn button" style="color: #ef4444; border-color: #fca5a5; font-weight: 700;">×</button>' +
                    '</div>';
                $('#gep-pricing-tiers-list').append(html);
                tierIndex++;
            });

            // Remove Pricing Tier
            $(document).on('click', '.gep-remove-tier-btn', function() {
                if ($('.gep-pricing-tier-row').length > 1) {
                    $(this).closest('.gep-pricing-tier-row').remove();
                } else {
                    alert('At least one pricing tier is required.');
                }
            });
        });
        </script>


    <?php else : ?>
        <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
            <div>
                <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Manage Exams & Series</h1>
                <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Strategize and deploy testing assets across academic domains.</p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=gep-tests&action=add'); ?>" class="button button-primary" style="padding: 0 30px; height: 50px; line-height: 50px; border-radius: 14px; font-weight: 900; font-size: 15px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);">+ Create New Exam</a>
        </div>

        <div class="gep-admin-table-container" style="border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;">
            <?php $tests = $admin_tests->list_tests(); ?>
            <table class="gep-admin-table">
                <thead>
                    <tr>
                        <th style="padding-left: 30px; width: 35%;">Exam Blueprint</th>
                        <th style="width: 200px;">Subject Domain</th>
                        <th style="width: 120px;">Architecture</th>
                        <th style="width: 130px;">Valuation</th>
                        <th style="width: 150px;">Intelligence Density</th>
                        <th style="padding-right: 30px; width: 140px; text-align: right;">Deployment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $tests ) ) :
                        foreach ( $tests as $test ) : 
                            global $wpdb;
                            $q_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_test_questions WHERE test_id = %d", $test->id));
                        ?>
                            <tr>
                                <td style="padding-left: 30px;">
                                    <a href="<?php echo admin_url('admin.php?page=gep-tests&action=edit&id='.$test->id); ?>" class="row-title" style="font-size: 15px; color: #1e293b; font-weight: 800;">
                                        <?php echo esc_html($test->title); ?>
                                    </a>
                                    <div class="row-actions" style="margin-top: 8px; opacity: 1;">
                                        <a href="<?php echo admin_url('admin.php?page=gep-tests&action=edit&id='.$test->id); ?>" style="color: #6366f1; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Edit Blueprint</a>
                                        <span style="color: #e2e8f0;">|</span>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gep-tests&action=delete&id='.$test->id), 'gep_test_delete_'.$test->id); ?>" class="delete" style="color: #ef4444; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Decommission</a>
                                    </div>
                                </td>
                                <td>
                                    <div style="background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #f1f5f9; display: inline-block;">
                                        <span style="font-weight: 700; color: #475569; font-size: 12px;">
                                            <?php 
                                            if($test->category_id) {
                                                $cat_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $test->category_id));
                                                echo esc_html($cat_name);
                                            } else echo '—';
                                            ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: <?php echo $test->type === 'series' ? '#eef2ff; color: #4338ca; border: 1px solid #e0e7ff;' : '#f8fafc; color: #64748b; border: 1px solid #f1f5f9;'; ?>; font-weight: 800; font-size: 10px;">
                                        <?php echo strtoupper($test->type); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 900; font-size: 16px; color: <?php echo $test->is_free ? '#10b981' : '#1e293b'; ?>;">
                                        <?php echo $test->is_free ? 'FREE' : '₹' . number_format($test->price, 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="background: #fff1f2; color: #e11d48; padding: 6px 12px; border-radius: 10px; font-weight: 900; font-size: 11px; border: 1px solid #ffe4e6;">
                                            <?php echo $q_count; ?> ASSETS
                                        </div>
                                    </div>
                                </td>
                                <td style="padding-right: 30px; text-align: right;">
                                    <span class="status-badge" style="background: <?php echo $test->status === 'publish' ? '#f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : '#fffbeb; color: #92400e; border: 1px solid #fef3c7;'; ?>; font-weight: 900;">
                                        <?php echo strtoupper($test->status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="6" class="empty-state" style="padding: 100px 50px;">
                                <div style="font-size: 50px; margin-bottom: 20px;">📝</div>
                                <h3 style="font-size: 24px; font-weight: 900; color: #1e293b;">No exams found</h3>
                                <p style="color: #64748b; font-weight: 600; margin-bottom: 25px;">Create your first test or series to get started.</p>
                                <a href="<?php echo admin_url('admin.php?page=gep-tests&action=add'); ?>" class="button button-primary" style="padding: 0 25px; height: 45px; line-height: 45px; border-radius: 12px; font-weight: 800;">+ Architect Exam</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

