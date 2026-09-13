<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<style>
.gep-series-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.gep-series-header {
    margin-bottom: 50px;
    text-align: center;
    animation: slideDown 0.5s ease-out;
}
.gep-series-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #eef2ff;
    color: #4f46e5;
    padding: 8px 20px;
    border-radius: 100px;
    font-weight: 800;
    font-size: 11px;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.gep-series-title {
    font-size: 38px;
    font-weight: 900;
    color: #0f172a;
    margin: 0 0 15px;
    letter-spacing: -1px;
}
.gep-series-subtitle {
    color: #475569;
    font-size: 16px;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
    font-weight: 500;
}
.gep-series-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}
.gep-test-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    position: relative;
}
.gep-test-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    border-color: #cbd5e1;
}
.gep-test-card-accent {
    height: 5px;
    width: 100%;
    background: #cbd5e1;
}
.gep-test-card-accent.free {
    background: #10b981;
}
.gep-test-card-accent.completed {
    background: #4f46e5;
}
.gep-test-card-content {
    padding: 30px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.gep-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.gep-tag-exam {
    font-size: 11px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.gep-badge-status {
    font-size: 11px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.gep-badge-status.free {
    background: #ecfdf5;
    color: #047857;
}
.gep-badge-status.completed {
    background: #e0f2fe;
    color: #0369a1;
}
.gep-badge-status.paid {
    background: #fef3c7;
    color: #b45309;
}
.gep-card-title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 20px 0;
    line-height: 1.4;
    min-height: 56px;
}
.gep-card-meta-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 25px;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
}
.gep-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    padding: 6px 12px;
    border-radius: 8px;
}
.gep-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
}
.gep-score-label {
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.gep-score-value {
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
}
.gep-price-value {
    font-size: 14px;
    font-weight: 800;
    color: #059669;
}
.gep-btn-details-new {
    height: 40px;
    padding: 0 16px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.gep-btn-details-new:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: #1e293b;
}
.gep-btn-action-new {
    height: 40px;
    padding: 0 20px;
    font-size: 13px;
    font-weight: 800;
    border-radius: 10px;
    border: none;
    background: #4f46e5;
    color: #ffffff;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
}
.gep-btn-action-new:hover {
    background: #4338ca;
    box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
    transform: translateY(-1px);
}
.gep-btn-action-new.completed {
    background: #0f172a;
    box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2);
}
.gep-btn-action-new.completed:hover {
    background: #1e293b;
    box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3);
}

/* Details Modal Enhancements */
.gep-details-modal-overlay {
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}
.gep-details-modal-content {
    border-radius: 20px !important;
    padding: 30px !important;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15) !important;
    border: 1px solid #e2e8f0 !important;
    font-family: inherit !important;
}
.gep-details-modal-close {
    font-size: 24px !important;
    top: 20px !important;
    right: 20px !important;
    color: #64748b !important;
}
.gep-details-modal-close:hover {
    color: #0f172a !important;
}
.gep-details-modal-title-info h3 {
    font-size: 22px !important;
    font-weight: 800 !important;
    color: #0f172a !important;
    margin: 0 0 8px 0;
}
.gep-details-modal-stats {
    font-weight: 700 !important;
    color: #475569 !important;
}
.gep-details-info-section h4 {
    font-size: 14px !important;
    font-weight: 800 !important;
    color: #334155 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px !important;
}
.gep-details-info-section p {
    color: #475569 !important;
    font-size: 14px !important;
    line-height: 1.6 !important;
}
.price-val {
    color: #059669 !important;
    font-weight: 800 !important;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-15px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="gep-series-container">
    <!-- Refined Header -->
    <div class="gep-series-header">
        <div class="gep-series-badge">
            <span>📚</span> Test Series Portfolio
        </div>
        <h1 class="gep-series-title"><?php echo esc_html( $test->title ); ?></h1>
        <p class="gep-series-subtitle">
            Prepare with our curated mock examinations built to simulate real test-day conditions and help you master this academic domain.
        </p>
    </div>
    <div class="gep-series-grid-container" style="width: 100%;">
        <?php if ( ! empty( $series_tests ) ) : 
            $test_logic = new GEP_Test();
            $result_logic = new GEP_Result();
            $cat_logic = new GEP_Category();
            $user_id = get_current_user_id();

            // Group tests by category
            $grouped_tests = array();
            foreach ( $series_tests as $s_test ) {
                $cat_id = (int) $s_test->category_id;
                if ( ! isset( $grouped_tests[$cat_id] ) ) {
                    $grouped_tests[$cat_id] = array();
                }
                $grouped_tests[$cat_id][] = $s_test;
            }

            // Loop through each group
            foreach ( $grouped_tests as $cat_id => $tests_in_cat ) :
                // Get category name
                $cat_name = 'Uncategorized';
                if ( $cat_id > 0 ) {
                    $category = $cat_logic->get_category_by_id( $cat_id );
                    if ( $category ) {
                        $cat_name = $category->name;
                    }
                }
                ?>
                <div class="gep-series-category-section" style="margin-bottom: 40px; width: 100%;">
                    <h3 style="font-size: 20px; font-weight: 850; color: #1e293b; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px;">
                        <span style="width: 4px; height: 18px; background: #4f46e5; border-radius: 2px; display: inline-block;"></span>
                        <?php echo esc_html( $cat_name ); ?>
                    </h3>
                    <div class="gep-series-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
                        <?php foreach ( $tests_in_cat as $s_test ) : 
                            $q_count = $test_logic->get_question_count( $s_test->id );
                            $attempts = $result_logic->get_user_attempts( $user_id, $s_test->id );
                            $has_submitted = false;
                            $last_score = 0;
                            
                            foreach($attempts as $att) {
                                if($att->status === 'submitted') {
                                    $has_submitted = true;
                                    $last_score = $att->percentage;
                                    break;
                                }
                            }

                            $accent_class = '';
                            if ($has_submitted) {
                                    $accent_class = 'completed';
                            } elseif ($s_test->is_free) {
                                    $accent_class = 'free';
                            }
                        ?>
                            <div class="gep-test-card">
                                <div class="gep-test-card-accent <?php echo $accent_class; ?>"></div>
                                <div class="gep-test-card-content">
                                    <div class="gep-card-header">
                                        <span class="gep-tag-exam"><?php 
                                            if ($s_test->type === 'single') echo 'Single Subject Test';
                                            elseif ($s_test->type === 'multiple') echo 'Multiple Subject Test';
                                            elseif ($s_test->type === 'combined') echo 'Combined Test';
                                            elseif ($s_test->type === 'self_test') echo 'Self Testing';
                                            else echo 'Online Mock Test';
                                        ?></span>
                                        <?php if ($has_submitted) : ?>
                                            <span class="gep-badge-status completed">Completed</span>
                                        <?php elseif ($s_test->is_free) : ?>
                                            <span class="gep-badge-status free">Free Test</span>
                                        <?php else : ?>
                                            <span class="gep-badge-status paid">Premium</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h4 class="gep-card-title"><?php echo esc_html( $s_test->title ); ?></h4>
                                    
                                    <div class="gep-card-meta-row">
                                        <div class="gep-meta-item" title="Questions count">❓ <?php echo $q_count; ?> Ques</div>
                                        <div class="gep-meta-item" title="Maximum Marks">🎯 <?php echo $s_test->total_marks; ?> Marks</div>
                                        <div class="gep-meta-item" title="Duration">⏱️ <?php echo $s_test->duration_minutes; ?> Mins</div>
                                        <div class="gep-meta-item" title="Languages">🌐 EN & HI</div>
                                    </div>

                                    <div class="gep-card-footer">
                                        <div>
                                            <?php if ($has_submitted) : ?>
                                                <div class="gep-score-label">Best Score</div>
                                                <div class="gep-score-value"><?php echo round($last_score); ?>%</div>
                                            <?php else : ?>
                                                <div class="gep-score-label">Access Status</div>
                                                <div class="gep-price-value"><?php echo $s_test->is_free ? 'FREE ACCESS' : 'INCLUDED'; ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <button type="button" class="gep-btn-details-new gep-view-details-btn" 
                                                data-title="<?php echo esc_attr( $s_test->title ); ?>"
                                                data-thumbnail="<?php echo esc_url( $s_test->thumbnail ?: GEP_PLUGIN_URL . 'assets/images/placeholder-test.jpg' ); ?>"
                                                data-price="<?php echo $s_test->is_free ? 'FREE' : 'INCLUDED'; ?>"
                                                data-duration="<?php echo $s_test->duration_minutes; ?> Mins"
                                                data-qcount="<?php echo $q_count; ?>"
                                                data-refundable="<?php 
                                                    $t_data = ! empty( $s_test->translated_data ) ? json_decode( $s_test->translated_data, true ) : array();
                                                    echo esc_attr( isset($t_data['refundable']) ? $t_data['refundable'] : 'no' ); 
                                                ?>"
                                                data-topics="<?php echo esc_attr( isset($t_data['topics']) ? $t_data['topics'] : '' ); ?>"
                                                data-details="<?php echo esc_attr( isset($t_data['what_you_get']) ? $t_data['what_you_get'] : '' ); ?>">
                                                Details
                                            </button>
                                            <a href="<?php echo gep_get_url('exam'); ?>?id=<?php echo $s_test->id; ?>" class="gep-btn-action-new <?php echo $has_submitted ? 'completed' : ''; ?>">
                                                <?php echo $has_submitted ? 'Re-Attempt' : 'Start Test'; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; 
        else : ?>
            <div style="background: #fff; border: 2px dashed #cbd5e1; border-radius: 20px; padding: 80px 40px; text-align: center; width: 100%;">
                <div style="font-size: 50px; margin-bottom: 20px;">🔭</div>
                <h3 style="margin: 0 0 15px; font-size: 22px; font-weight: 800; color: #1e293b;">Portfolio Empty</h3>
                <p style="color: #64748b; font-size: 15px; font-weight: 500; max-width: 500px; margin: 0 auto;">Practice tests are currently being updated. Please check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 60px; text-align: center;">
        <a href="<?php echo gep_get_url('dashboard'); ?>?view=tests" style="display: inline-flex; align-items: center; gap: 10px; color: #475569; font-weight: 700; text-decoration: none; font-size: 14px; padding: 12px 24px; border-radius: 10px; background: #f1f5f9; border: 1px solid #e2e8f0; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';">
            <span>←</span> Back to Test Hub
        </a>
    </div>
</div>

<!-- Details Modal -->
<div id="gep-details-modal" class="gep-details-modal-overlay" style="display: none;">
    <div class="gep-details-modal-content" role="dialog" aria-modal="true" aria-labelledby="gep-modal-title">
        <button type="button" aria-label="Close test details" class="gep-details-modal-close" id="gep-details-modal-close">&times;</button>
        <div class="gep-details-modal-header">
            <div class="gep-details-modal-thumb-container">
                <img id="gep-modal-thumb" src="" alt="Thumbnail">
            </div>
            <div class="gep-details-modal-title-info">
                <h3 id="gep-modal-title">Test Title</h3>
                <div class="gep-details-modal-stats">
                    <span id="gep-modal-duration">60 Mins</span>
                    <span class="dot">•</span>
                    <span id="gep-modal-qcount">100 Ques</span>
                </div>
            </div>
        </div>
        <div class="gep-details-modal-body">
            <div class="gep-details-info-section">
                <h4>📋 Topics Covered</h4>
                <p id="gep-modal-topics">Algebra, Calculus, etc.</p>
            </div>
            <div class="gep-details-info-section">
                <h4>🎁 What You Get</h4>
                <p id="gep-modal-details">Detailed Solutions, AI Doubts, etc.</p>
            </div>
            <div class="gep-details-info-section flex-row">
                <div>
                    <h4>💰 Refundable</h4>
                    <p id="gep-modal-refundable">Non-Refundable</p>
                </div>
                <div>
                    <h4>🏷️ Price</h4>
                    <p id="gep-modal-price" class="price-val">₹0</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var detailsOpener = null;
    function closeDetails() {
        $('#gep-details-modal').hide(); $('html').removeClass('gep-dialog-open');
        if (detailsOpener && detailsOpener.isConnected) detailsOpener.focus();
    }
    $('#gep-details-modal').on('keydown', function(e) {
        if (e.key === 'Escape') { e.preventDefault(); closeDetails(); }
        if (e.key === 'Tab') { e.preventDefault(); $('#gep-details-modal-close').trigger('focus'); }
    });
    // Show details modal
    $(document).on('click', '.gep-view-details-btn', function(e) {
        e.preventDefault();
        var $btn = $(this); detailsOpener = this;
        
        // Populate modal data
        $('#gep-modal-title').text($btn.data('title'));
        var thumbnail = $btn.data('thumbnail');
        $('#gep-modal-thumb').attr('src', thumbnail || '').toggle(!!thumbnail);
        $('#gep-modal-duration').text($btn.data('duration'));
        $('#gep-modal-qcount').text($btn.data('qcount') + ' Ques');
        
        var refundable = $btn.data('refundable');
        $('#gep-modal-refundable').text(refundable === 'yes' ? 'Refundable (100% money back)' : 'Non-Refundable');
        
        var topics = $btn.data('topics');
        $('#gep-modal-topics').text(topics ? topics : 'No specific topics listed.');
        
        var details = $btn.data('details');
        $('#gep-modal-details').text(details ? details : 'Full access to exam, timed simulator and instant results.');
        
        $('#gep-modal-price').text($btn.data('price'));
        
        // Open modal
        $('#gep-details-modal').show(); $('html').addClass('gep-dialog-open');
        $('#gep-details-modal-close').trigger('focus');
    });
    
    // Close modal
    $(document).on('click', '#gep-details-modal-close, .gep-details-modal-overlay', function(e) {
        if (e.target === this || $(e.target).hasClass('gep-details-modal-close')) {
            closeDetails();
        }
    });
});
</script>
