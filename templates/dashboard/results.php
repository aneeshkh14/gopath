<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Translation Mapping
$ui_strings = array(
    'en' => array(
        'perf_title'    => "Performance Analytics",
        'perf_desc'     => "Review your examination history and detailed performance insights.",
        'col_title'     => "Test Title",
        'col_date'      => "Date Attempted",
        'col_perf'      => "Performance",
        'col_verdict'   => "Verdict",
        'col_insights'  => "Insights",
        'passed'        => "PASSED",
        'failed'        => "FAILED",
        'view_review'   => "View Review",
        'no_history'    => "No history found",
        'no_history_desc'=> "Complete your first examination to see performance analytics here."
    ),
    'hi' => array(
        'perf_title'    => "प्रदर्शन विश्लेषण",
        'perf_desc'     => "अपने परीक्षा इतिहास और विस्तृत प्रदर्शन अंतर्दृष्टि की समीक्षा करें।",
        'col_title'     => "टेस्ट शीर्षक",
        'col_date'      => "प्रयास की तिथि",
        'col_perf'      => "प्रदर्शन",
        'col_verdict'   => "परिणाम",
        'col_insights'  => "अंतर्दृष्टि",
        'passed'        => "उत्तीर्ण",
        'failed'        => "अनुत्तीर्ण",
        'view_review'   => "समीक्षा देखें",
        'no_history'    => "कोई इतिहास नहीं मिला",
        'no_history_desc'=> "यहां प्रदर्शन विश्लेषण देखने के लिए अपनी पहली परीक्षा पूरी करें।"
    )
);

$_gep_lang = (isset($_SESSION['gep_lang']) ? $_SESSION['gep_lang'] : (get_user_meta(get_current_user_id(), 'gep_preferred_lang', true) ?: 'en'));
$strings = isset($ui_strings[$_gep_lang]) ? $ui_strings[$_gep_lang] : $ui_strings['en'];

global $wpdb;
$user_id = get_current_user_id();

// Fetch attempts for calculations
$attempts = (new GEP_Result())->get_user_results( $user_id );

// get_user_results returns newest completed attempts first.
$subject_stats = (new GEP_Result())->get_subject_stats($attempts);
?>

<div class="gep-results-container" style="animation: fadeIn 0.5s ease-out; font-family: 'Inter', system-ui, sans-serif; padding-bottom: 50px;">
    
    <!-- Page Header -->
    <div class="gep-results-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 28px; font-weight: 950; color: #0f172a; margin: 0; letter-spacing: -1px;"><?php echo esc_html($strings['perf_title']); ?></h2>
            <p style="color: #64748b; margin: 5px 0 0; font-weight: 600; font-size: 14px;"><?php echo esc_html($strings['perf_desc']); ?></p>
        </div>
    </div>

    <!-- 2. SUBJECT-WISE WEAKNESS TRACKER PANEL -->
    <?php if ( ! empty( $subject_stats ) ) : ?>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 30px; margin-bottom: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02);">
            <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    🎯 <?php _e( 'Subject-Wise Weakness &amp; Strength Tracker', 'gopath-exam-portal' ); ?>
                </h3>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px; font-weight: 600;"><?php _e( 'Based on your latest answered response to each question. Use this breakdown to choose what to practise next.', 'gopath-exam-portal' ); ?></p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 20px;">
                <?php foreach ( $subject_stats as $name => $stat ) : 
                    $accuracy = round( ($stat['correct'] / $stat['total']) * 100 );
                    
                    // Determine strength category
                    $badge_bg = '#fee2e2';
                    $badge_color = '#b91c1c';
                    $verdict = __( 'Weak Zone', 'gopath-exam-portal' );
                    $icon = '🔴';
                    $suggestion = __( 'Focus on lectures in SuperCoaching.', 'gopath-exam-portal' );
                    
                    if ( $accuracy >= 70 ) {
                        $badge_bg = '#d1fae5';
                        $badge_color = '#065f46';
                        $verdict = __( 'Strong Zone', 'gopath-exam-portal' );
                        $icon = '🟢';
                        $suggestion = __( 'Keep practicing to maintain edge.', 'gopath-exam-portal' );
                    } elseif ( $accuracy >= 45 ) {
                        $badge_bg = '#fef3c7';
                        $badge_color = '#92400e';
                        $verdict = __( 'Moderate Zone', 'gopath-exam-portal' );
                        $icon = '🟡';
                        $suggestion = __( 'Solve PYQs to bridge core concepts.', 'gopath-exam-portal' );
                    }
                ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                <strong style="font-size: 15px; color: #1e293b; font-weight: 800;"><?php echo esc_html( $name ); ?></strong>
                                <span style="background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>; font-size: 10px; font-weight: 900; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php echo $icon; ?> <?php echo $verdict; ?>
                                </span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <div style="flex-grow: 1; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $accuracy; ?>%; background: <?php echo $badge_color; ?>; border-radius: 10px;"></div>
                                </div>
                                <span style="font-weight: 900; color: #0f172a; font-size: 15px;"><?php echo $accuracy; ?>%</span>
                            </div>
                        </div>

                        <div style="margin-top: 10px; border-top: 1px dashed #e2e8f0; padding-top: 10px; display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: #64748b;">
                            <span><?php _e( 'Correct:', 'gopath-exam-portal' ); ?> <?php echo $stat['correct']; ?>/<?php echo $stat['total']; ?></span>
                            <span style="color: <?php echo $badge_color; ?>; font-style: italic;"><?php echo $suggestion; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Historical Performance Registry -->
    <div class="gep-card" style="padding: 0; overflow: hidden; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; background: #fff;">
        <div role="region" aria-label="Exam history" tabindex="0" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="gep-premium-table" style="width: 100%; border-collapse: collapse; min-width: 650px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 20px; text-align: left; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($strings['col_title']); ?></th>
                        <th style="padding: 20px; text-align: left; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($strings['col_date']); ?></th>
                        <th style="padding: 20px; text-align: left; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($strings['col_perf']); ?></th>
                        <th style="padding: 20px; text-align: left; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php _e( 'Rank', 'gopath-exam-portal' ); ?></th>
                        <th style="padding: 20px; text-align: left; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($strings['col_verdict']); ?></th>
                        <th style="padding: 20px; text-align: right; font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo esc_html($strings['col_insights']); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $attempts ) ) : 
                        require_once GEP_PLUGIN_DIR . 'includes/class-gep-exam-engine.php';
                        $exam_engine = new GEP_Exam_Engine();

                        foreach ( $attempts as $a ) : 
                            $percentage = (float) $a->percentage;
                            $is_pass = (bool) $a->is_pass; 
                            $rank = $exam_engine->get_user_test_rank( $a->test_id, $a->user_id );
                    ?>
                        <tr class="gep-table-row" style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 20px;">
                                <div style="font-weight: 800; color: #1e293b; font-size: 15px;"><?php echo esc_html( $a->test_name ); ?></div>
                                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">ID: #<?php echo $a->id; ?></div>
                            </td>
                            <td style="padding: 20px; color: #475569; font-weight: 600; font-size: 14px;">
                                <?php echo date('d M, Y', strtotime($a->start_time)); ?>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex-grow: 1; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; width: 80px;">
                                        <div style="height: 100%; width: <?php echo max(0, min(100, $percentage)); ?>%; background: <?php echo $is_pass ? '#10b981' : '#ef4444'; ?>;"></div>
                                    </div>
                                    <span style="font-weight: 800; color: #1e293b; font-size: 14px;"><?php echo round($percentage); ?>%</span>
                                </div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; margin-top: 5px;"><?php echo $a->score; ?> / <?php echo $a->total_marks; ?> <?php echo esc_html($strings['col_perf']); ?></div>
                            </td>
                            <td style="padding: 20px;">
                                <div style="display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px; font-weight: 900; color: #0ea5e9; font-size: 14px;">
                                    #<?php echo $rank; ?>
                                </div>
                            </td>
                            <td style="padding: 20px;">
                                <?php if ( $is_pass ) : ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;"><?php echo esc_html($strings['passed']); ?></span>
                                <?php else : ?>
                                    <span style="background: #fee2e2; color: #b91c1c; padding: 6px 14px; border-radius: 100px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;"><?php echo esc_html($strings['failed']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 20px; text-align: right;">
                                <a href="<?php echo gep_get_url('result'); ?>?id=<?php echo $a->id; ?>" class="gep-btn-mini" style="background: #eff6ff; color: #2563eb; padding: 8px 18px; border-radius: 10px; font-size: 13px; font-weight: 800; text-decoration: none; display: inline-block; transition: all 0.2s; border: 1px solid #dbeafe;">
                                    <?php echo esc_html($strings['view_review']); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; 
                    else : ?>
                        <tr>
                            <td colspan="6" style="padding: 80px 20px; text-align: center;">
                                <div style="font-size: 50px; margin-bottom: 20px;">📜</div>
                                <h3 style="margin: 0; color: #1e293b; font-weight: 800;"><?php echo esc_html($strings['no_history']); ?></h3>
                                <p style="color: #64748b; margin-top: 10px;"><?php echo esc_html($strings['no_history_desc']); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.gep-table-row:hover {
    background: #f8fafc;
}
.gep-btn-mini:hover {
    background: #2563eb !important;
    color: #fff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}
@media (max-width: 640px) {
    .gep-results-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
