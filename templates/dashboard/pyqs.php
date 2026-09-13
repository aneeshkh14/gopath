<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-main-inner" style="font-family: 'Inter', system-ui, sans-serif; padding-bottom: 80px;">
    <!-- Skill Academy Style Hero Banner for PYQs -->
    <div class="gep-skill-hero" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 40px; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; align-items: center; margin-bottom: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02); color: #0f172a;">
        <div class="gep-skill-hero-content">
            <h2 style="font-size: 36px; font-weight: 950; color: #0f172a; margin: 0 0 15px; letter-spacing: -1.5px; line-height: 1.1;">
                Previous Year <span class="gep-text-gradient-primary" style="color: #6366f1;">Questions (PYQs)</span>
            </h2>
            <p style="font-size: 16px; color: #64748b; font-weight: 600; line-height: 1.6; margin: 0 0 35px; max-width: 500px;">
                Practise previous year questions by topic or year. Quick tests use up to 10 questions; year-wise practice uses up to 50 questions from the selected paper.
            </p>
            <div class="gep-skill-stats" style="display: flex; gap: 30px;">
                <div class="gep-skill-stat-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 25px; border-radius: 16px; min-width: 140px;">
                    <span class="val" style="display: block; font-size: 24px; font-weight: 900; color: #6366f1; margin-bottom: 2px;">100%</span>
                    <span class="label" style="font-size: 11px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Interactive</span>
                </div>
                <div class="gep-skill-stat-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 25px; border-radius: 16px; min-width: 140px;">
                    <span class="val" style="display: block; font-size: 24px; font-weight: 900; color: #10b981; margin-bottom: 2px;">Real Exam</span>
                    <span class="label" style="font-size: 11px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">NTA Simulator</span>
                </div>
            </div>
        </div>
        <div class="gep-skill-hero-image" style="position: relative; height: 100%; min-height: 220px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1);">
            <div style="position: absolute; inset: 0; background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.2), transparent 70%);"></div>
            <div style="position: relative; text-align: center; color: #fff;">
                <div style="font-size: 54px; margin-bottom: 12px; animation: float 6s ease-in-out infinite;">📁</div>
                <h3 style="font-size: 28px; font-weight: 950; letter-spacing: -1px; margin: 0 0 5px; color: #fff;">Paper Archive</h3>
                <span style="font-size: 11px; font-weight: 800; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px;">UGC NET &amp; Sets</span>
            </div>
        </div>
    </div>

    <!-- Toggle Selector Tabs -->
    <div class="gep-pyq-navigation" style="margin-top: 40px; display: flex; justify-content: center;">
        <div class="gep-pyq-tabs-wrapper" style="display: flex; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 6px; border-radius: 100px; gap: 8px; backdrop-filter: blur(10px);">
            <button class="gep-pyq-tab-btn active" data-tab="section_wise" style="padding: 12px 30px; border-radius: 100px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.3s; background: #6366f1; color: #fff;">📁 Section-Wise</button>
            <button class="gep-pyq-tab-btn" data-tab="year_wise" style="padding: 12px 30px; border-radius: 100px; border: none; font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.3s; background: transparent; color: #94a3b8;">📅 Year-Wise</button>
        </div>
    </div>

    <div class="gep-marketplace-premium" style="margin-top: 40px; padding: 0 20px;">
        
        <!-- SECTION-WISE (TOPIC-WISE) PRACTICE -->
        <div id="gep-pyq-tab-section_wise" class="gep-pyq-tab-content">
            <?php 
            global $wpdb;
            $user_id = get_current_user_id();
            
            // Get user meta goals or all parents
            $student_goals = get_user_meta( $user_id, 'gep_student_goals', true );
            if ( ! is_array( $student_goals ) || empty( $student_goals ) ) {
                $student_goals = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}gep_categories WHERE parent_id = 0" );
            }

            // Get subcategories
            $subcategories = array();
            if ( ! empty( $student_goals ) ) {
                $parent_ids_str = implode( ',', array_map( 'intval', $student_goals ) );
                $subcategories = $wpdb->get_results( "
                    SELECT c.id, c.name, p.name as parent_name 
                    FROM {$wpdb->prefix}gep_categories c
                    JOIN {$wpdb->prefix}gep_categories p ON c.parent_id = p.id
                    WHERE c.parent_id IN ($parent_ids_str) 
                    ORDER BY p.name ASC, c.name ASC
                " );
            }

            // Count published questions per subcategory
            $topic_counts = array();
            $counts_res = $wpdb->get_results( "SELECT subcategory_id, COUNT(*) as qty FROM {$wpdb->prefix}gep_questions WHERE subcategory_id > 0 AND status = 'publish' GROUP BY subcategory_id" );
            foreach ( $counts_res as $r ) {
                $topic_counts[ (int) $r->subcategory_id ] = (int) $r->qty;
            }

            // Group subcategories by parent name
            $grouped_subcats = array();
            foreach ( $subcategories as $sub ) {
                $p_name = $sub->parent_name ?: 'General';
                if ( ! isset($grouped_subcats[$p_name]) ) {
                    $grouped_subcats[$p_name] = array();
                }
                $grouped_subcats[$p_name][] = $sub;
            }
            ?>

            <?php if ( ! empty($grouped_subcats) ) : ?>
                <?php foreach ( $grouped_subcats as $parent_name => $subs ) : ?>
                    <div class="gep-pyq-group" style="margin-bottom: 40px;">
                        <h3 style="font-size: 20px; font-weight: 850; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px; text-transform: uppercase;">
                            <span style="width: 4px; height: 18px; background: linear-gradient(135deg, #facc15, #fbbf24); border-radius: 2px; display: inline-block;"></span>
                            <?php echo esc_html( $parent_name ); ?> Topics
                        </h3>
                        
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; color: #cbd5e1; font-size: 14px;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.08); color: #fff; font-weight: 800;">
                                        <th style="padding: 16px 24px;">Topic Name</th>
                                        <th style="padding: 16px 24px; text-align: center;">Available PYQs</th>
                                        <th style="padding: 16px 24px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $subs as $sub ) : 
                                        $qty = isset($topic_counts[$sub->id]) ? $topic_counts[$sub->id] : 0;
                                        if ( $qty <= 0 ) continue; // Only list topics containing questions
                                    ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 18px 24px; font-weight: 700; color: #fff;"><?php echo esc_html($sub->name); ?></td>
                                            <td style="padding: 18px 24px; text-align: center;">
                                                <span style="background: rgba(99,102,241,0.15); color: #818cf8; padding: 4px 10px; border-radius: 100px; font-weight: 800; font-size: 12px;">
                                                    <?php echo $qty; ?> Questions
                                                </span>
                                            </td>
                                            <td style="padding: 18px 24px; text-align: right;">
                                                <button type="button" class="gep-pyq-practice-btn btn-primary" data-type="topic" data-target="<?php echo $sub->id; ?>" style="background: #6366f1; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 13px; transition: all 0.3s;">
                                                    ▶ Quick Test
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="background: rgba(255,255,255,0.02); border: 2px dashed rgba(255,255,255,0.1); border-radius: 24px; padding: 80px 40px; text-align: center; width: 100%;">
                    <div style="font-size: 50px; margin-bottom: 20px;">🔭</div>
                    <h3 style="margin: 0 0 15px; font-size: 22px; font-weight: 850; color: #fff;">No Topic PYQs Available</h3>
                    <p style="color: #a1a1aa; font-size: 15px; font-weight: 600; max-width: 500px; margin: 0 auto;">No topic-wise previous year questions found in your target segments.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- YEAR-WISE PRACTICE -->
        <div id="gep-pyq-tab-year_wise" class="gep-pyq-tab-content" style="display: none;">
            <?php 
            // Query distinct years
            $years_res = $wpdb->get_results( "
                SELECT DISTINCT year 
                FROM {$wpdb->prefix}gep_questions 
                WHERE year IS NOT NULL AND year != '' AND status = 'publish' 
                ORDER BY year DESC
            " );

            $year_groups = array();
            foreach ( $years_res as $y_row ) {
                $y_val = trim($y_row->year);
                $main_year = 'General';
                if ( preg_match('/^\d{4}/', $y_val, $matches) ) {
                    $main_year = $matches[0];
                }
                
                // Count questions
                $q_count = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}gep_questions WHERE year = %s AND status = 'publish'",
                    $y_val
                ) );
                
                if ( ! isset($year_groups[$main_year]) ) {
                    $year_groups[$main_year] = array();
                }
                $year_groups[$main_year][] = array(
                    'name' => $y_val,
                    'count' => (int) $q_count
                );
            }
            ?>

            <?php if ( ! empty($year_groups) ) : ?>
                <?php foreach ( $year_groups as $calendar_year => $papers ) : ?>
                    <div class="gep-pyq-group" style="margin-bottom: 40px;">
                        <h3 style="font-size: 20px; font-weight: 850; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px; text-transform: uppercase;">
                            <span style="width: 4px; height: 18px; background: linear-gradient(135deg, #3b82f6, #6366f1); border-radius: 2px; display: inline-block;"></span>
                            Papers from <?php echo esc_html( $calendar_year ); ?>
                        </h3>
                        
                        <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 20px; overflow: hidden;">
                            <table style="width: 100%; border-collapse: collapse; text-align: left; color: #cbd5e1; font-size: 14px;">
                                <thead>
                                    <tr style="background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.08); color: #fff; font-weight: 800;">
                                        <th style="padding: 16px 24px;">Paper / Shift Name</th>
                                        <th style="padding: 16px 24px; text-align: center;">Questions Count</th>
                                        <th style="padding: 16px 24px; text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $papers as $paper ) : ?>
                                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.01)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 18px 24px; font-weight: 700; color: #fff;"><?php echo esc_html($paper['name']); ?></td>
                                            <td style="padding: 18px 24px; text-align: center;">
                                                <span style="background: rgba(59,130,246,0.15); color: #60a5fa; padding: 4px 10px; border-radius: 100px; font-weight: 800; font-size: 12px;">
                                                    <?php echo $paper['count']; ?> Questions
                                                </span>
                                            </td>
                                            <td style="padding: 18px 24px; text-align: right;">
                                                <button type="button" class="gep-pyq-practice-btn btn-primary" data-type="year" data-target="<?php echo esc_attr($paper['name']); ?>" style="background: #3b82f6; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 13px; transition: all 0.3s;">
                                                    📝 PYQ Test
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="background: rgba(255,255,255,0.02); border: 2px dashed rgba(255,255,255,0.1); border-radius: 24px; padding: 80px 40px; text-align: center; width: 100%;">
                    <div style="font-size: 50px; margin-bottom: 20px;">🔭</div>
                    <h3 style="margin: 0 0 15px; font-size: 22px; font-weight: 850; color: #fff;">No Year PYQs Available</h3>
                    <p style="color: #a1a1aa; font-size: 15px; font-weight: 600; max-width: 500px; margin: 0 auto;">No year-wise previous year questions found in the question bank.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
.gep-pyqs-sovereign {
    background: #09090b;
    min-height: 100vh;
    padding-bottom: 120px;
    font-family: 'Inter', system-ui, sans-serif;
    color: #fff;
}

.gep-pyq-tab-btn:hover {
    color: #fff !important;
}

.gep-pyq-practice-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(99,102,241,0.25);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tab Swapping Logic
    const tabs = document.querySelectorAll('.gep-pyq-tab-btn');
    const contents = document.querySelectorAll('.gep-pyq-tab-content');

    tabs.forEach(btn => {
        btn.addEventListener('click', function() {
            tabs.forEach(t => {
                t.classList.remove('active');
                t.style.background = 'transparent';
                t.style.color = '#94a3b8';
            });
            this.classList.add('active');
            if (this.dataset.tab === 'section_wise') {
                this.style.background = '#6366f1';
            } else {
                this.style.background = '#3b82f6';
            }
            this.style.color = '#fff';

            contents.forEach(c => c.style.display = 'none');
            document.getElementById('gep-pyq-tab-' + this.dataset.tab).style.display = 'block';
        });
    });

    const initialTab = new URLSearchParams(window.location.search).get('tab');
    tabs.forEach(btn => { if (btn.dataset.tab === initialTab) btn.click(); });
    // 2. AJAX Dynamic Quiz Launch
    const ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
    
    jQuery(document).on('click', '.gep-pyq-practice-btn', function() {
        const btn = jQuery(this);
        const type = btn.data('type');
        const target = btn.data('target');
        const originalText = btn.text();

        if (btn.prop('disabled')) return;
        btn.prop('disabled', true).text('⌛ Initializing...');

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'gep_start_pyq_practice_test',
                nonce: '<?php echo esc_js(wp_create_nonce('gep_exam_nonce')); ?>',
                type: type,
                target: target
            },
            success: function(res) {
                if (res.success && res.data.redirect) {
                    btn.text('🚀 Redirecting...');
                    window.location.href = res.data.redirect;
                } else {
                    alert((res.data && res.data.message) || 'Failed to start practice test. Please try again.');
                    btn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('Connection issue. Please verify your connection.');
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
