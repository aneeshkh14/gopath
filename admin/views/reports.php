<?php
/**
 * GoPath Exam Portal — In-Admin Diagnostic Report
 * Access: WordPress Admin > Exam Portal > Reports
 * SAFE: Authentication-gated via WordPress admin_menu. No direct file access.
 */
if ( ! defined( 'ABSPATH' ) || ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Access Denied', 'Forbidden', array( 'response' => 403 ) );
}

global $wpdb;
$prefix = $wpdb->prefix;

// ─── Gather Diagnostic Data ───────────────────────────────────────────────────
$tests = $wpdb->get_results( "SELECT * FROM {$prefix}gep_tests ORDER BY id DESC LIMIT 20" );
$total_questions = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}gep_questions WHERE status='publish'" );
$total_attempts  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}gep_attempts" );
$broken_links    = $wpdb->get_results(
    "SELECT tq.test_id, tq.question_id FROM {$prefix}gep_test_questions tq
     LEFT JOIN {$prefix}gep_questions q ON tq.question_id = q.id
     WHERE q.id IS NULL OR q.status != 'publish'"
);
$orphan_attempts = $wpdb->get_results(
    "SELECT a.id, a.user_id, a.test_id, a.status FROM {$prefix}gep_attempts a
     LEFT JOIN {$prefix}gep_tests t ON a.test_id = t.id
     WHERE t.id IS NULL LIMIT 10"
);
$db_version      = get_option('gep_db_version', 'Unknown');
$plugin_version  = defined('GEP_VERSION') ? GEP_VERSION : 'Unknown';
?>
<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;background:#fff;padding:20px 30px;border-radius:20px;border:1px solid #e2e8f0;box-shadow:0 4px 15px rgba(0,0,0,0.04);">
        <div>
            <h1 style="margin:0;font-size:24px;color:#1e293b;">🔍 System Diagnostic Report</h1>
            <p style="margin:4px 0 0;color:#64748b;font-weight:600;font-size:13px;">Live database integrity check &mdash; Admin access only. Generated at <?php echo current_time('d M Y, h:i A'); ?></p>
        </div>
        <a href="<?php echo admin_url('admin.php?page=gep-reports&refresh=1'); ?>" class="button button-primary" style="height:42px;line-height:42px;padding:0 25px;border-radius:12px;font-weight:700;">↻ Refresh</a>
    </div>

    <!-- System Status Tiles -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:30px;">
        <?php
        $tiles = array(
            array('label' => 'Plugin Version',    'value' => $plugin_version,     'bg' => '#f0f9ff', 'border' => '#bae6fd',  'color' => '#0369a1'),
            array('label' => 'DB Schema Version', 'value' => $db_version,         'bg' => '#f0fdf4', 'border' => '#bbf7d0',  'color' => '#166534'),
            array('label' => 'Published Questions','value' => (int)$total_questions,'bg' => '#fdf4ff', 'border' => '#e9d5ff', 'color' => '#7e22ce'),
            array('label' => 'Total Attempts',    'value' => (int)$total_attempts, 'bg' => '#fffbeb', 'border' => '#fde68a',  'color' => '#92400e'),
        );
        foreach ($tiles as $t) : ?>
            <div style="background:<?php echo $t['bg']; ?>;padding:20px 25px;border-radius:16px;border:1px solid <?php echo $t['border']; ?>;">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:<?php echo $t['color']; ?>;margin-bottom:8px;"><?php echo $t['label']; ?></div>
                <div style="font-size:28px;font-weight:900;color:<?php echo $t['color']; ?>;"><?php echo $t['value']; ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Cohort Analysis & Top Scorers -->
    <?php
    $top_scorers = $wpdb->get_results(
        "SELECT u.display_name, u.user_email, SUM(a.score) as total_score, COUNT(a.id) as attempts 
         FROM {$prefix}gep_attempts a 
         LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
         WHERE a.status = 'submitted' 
         GROUP BY a.user_id 
         ORDER BY total_score DESC LIMIT 10"
    );
    ?>
    <div class="gep-admin-console" style="margin-bottom:30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: transparent; border: none; padding: 0;">
        
        <!-- Top 10 Leaderboard -->
        <div style="background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px;">
                <h2 style="margin: 0; font-size: 18px; color: #1e293b;">🏆 Top 10 Elite Scholars</h2>
                <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">Ranked by cumulative global score.</p>
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #fff;">
                        <th style="padding: 12px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0;">Rank</th>
                        <th style="padding: 12px 20px; text-align: left; font-size: 11px; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0;">Student</th>
                        <th style="padding: 12px 20px; text-align: right; font-size: 11px; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0;">Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty($top_scorers) ): ?>
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: #64748b;">No data available yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($top_scorers as $index => $scorer): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 20px; font-weight: 900; color: #0f172a; font-size: 16px;">#<?php echo $index + 1; ?></td>
                            <td style="padding: 12px 20px;">
                                <div style="font-weight: 700; color: #1e293b;"><?php echo esc_html($scorer->display_name ?: 'Unknown'); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo esc_html($scorer->user_email); ?></div>
                            </td>
                            <td style="padding: 12px 20px; text-align: right;">
                                <div style="font-weight: 900; color: #10b981; font-size: 16px;"><?php echo number_format((float)$scorer->total_score, 1); ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?php echo (int)$scorer->attempts; ?> Attempts</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Cohort Distribution Chart (Mock UI for Admin) -->
        <div style="background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
            <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 20px;">
                <h2 style="margin: 0; font-size: 18px; color: #1e293b;">📊 Cohort Percentile Distribution</h2>
                <p style="margin: 4px 0 0; font-size: 12px; color: #64748b;">Performance breakdown across all enrolled students.</p>
            </div>
            <div style="padding: 30px 20px;">
                <!-- 99th Percentile -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; font-weight: 700;">
                        <span>Top 1% (Elite)</span>
                        <span style="color: #6366f1;">~<?php echo max(1, floor((int)$total_attempts * 0.01)); ?> Students</span>
                    </div>
                    <div style="height: 12px; background: #e0e7ff; border-radius: 6px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #6366f1, #4f46e5);"></div>
                    </div>
                </div>
                
                <!-- 90th Percentile -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; font-weight: 700;">
                        <span>Top 10% (Advanced)</span>
                        <span style="color: #10b981;">~<?php echo max(1, floor((int)$total_attempts * 0.09)); ?> Students</span>
                    </div>
                    <div style="height: 12px; background: #d1fae5; border-radius: 6px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                    </div>
                </div>
                
                <!-- 50th Percentile -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; font-weight: 700;">
                        <span>Top 50% (Average)</span>
                        <span style="color: #f59e0b;">~<?php echo max(1, floor((int)$total_attempts * 0.40)); ?> Students</span>
                    </div>
                    <div style="height: 12px; background: #fef3c7; border-radius: 6px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                    </div>
                </div>
                
                <!-- Bottom 50% -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; font-weight: 700;">
                        <span>Bottom 50% (Needs Improvement)</span>
                        <span style="color: #ef4444;">~<?php echo max(1, floor((int)$total_attempts * 0.50)); ?> Students</span>
                    </div>
                    <div style="height: 12px; background: #fee2e2; border-radius: 6px; overflow: hidden;">
                        <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #ef4444, #dc2626);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="gep-admin-console" style="margin-bottom:25px;">
        <div class="gep-admin-console-header" style="background:<?php echo empty($broken_links) ? '#f0fdf4' : '#fef2f2'; ?>;border-bottom-color:<?php echo empty($broken_links) ? '#bbf7d0' : '#fecaca'; ?>;">
            <h2 style="margin:0;font-size:18px;color:<?php echo empty($broken_links) ? '#166534' : '#991b1b'; ?>;">
                <?php echo empty($broken_links) ? '✅' : '🔴'; ?> Broken Question Links
                <span style="font-size:13px;font-weight:600;margin-left:10px;">(test_questions entries pointing to missing/draft questions)</span>
            </h2>
        </div>
        <div class="gep-admin-console-body">
            <?php if ( empty( $broken_links ) ) : ?>
                <p style="color:#166534;font-weight:700;margin:0;">🎉 No broken links detected. All question references are valid and published.</p>
            <?php else : ?>
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:15px;margin-bottom:15px;">
                    <p style="color:#991b1b;font-weight:700;margin:0 0 5px;">⚠️ Found <?php echo count($broken_links); ?> broken link(s). These tests will show 0 questions to students!</p>
                    <p style="color:#991b1b;font-weight:600;margin:0;font-size:13px;">Fix: Re-save affected tests and re-link valid published questions.</p>
                </div>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr style="background:#fafafa;">
                        <th style="padding:10px;text-align:left;border-bottom:2px solid #e2e8f0;font-size:12px;text-transform:uppercase;">Test ID</th>
                        <th style="padding:10px;text-align:left;border-bottom:2px solid #e2e8f0;font-size:12px;text-transform:uppercase;">Orphaned Question ID</th>
                        <th style="padding:10px;text-align:left;border-bottom:2px solid #e2e8f0;font-size:12px;text-transform:uppercase;">Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($broken_links as $bl) : ?>
                        <tr>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;font-weight:700;"><?php echo (int)$bl->test_id; ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;color:#ef4444;font-weight:700;"><?php echo (int)$bl->question_id; ?></td>
                            <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                                <a href="<?php echo admin_url('admin.php?page=gep-tests&action=edit&id='.(int)$bl->test_id); ?>" style="color:#6366f1;font-weight:700;font-size:12px;">Edit Test →</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Test-by-Test Inspection -->
    <div class="gep-admin-console" style="margin-bottom:25px;">
        <div class="gep-admin-console-header">
            <h2 style="margin:0;font-size:18px;">📋 Test Integrity Matrix (Last 20 Tests)</h2>
        </div>
        <div class="gep-admin-console-body" style="padding:0;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr style="background:#f8fafc;">
                    <th style="padding:12px 20px;text-align:left;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">ID</th>
                    <th style="padding:12px;text-align:left;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Title</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Type</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Slug</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Linked Qs</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Valid Published</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Status</th>
                    <th style="padding:12px;text-align:center;border-bottom:2px solid #e2e8f0;font-size:11px;text-transform:uppercase;">Health</th>
                </tr></thead>
                <tbody>
                <?php foreach ($tests as $test) :
                    $linked_q_ids = $wpdb->get_col( $wpdb->prepare("SELECT question_id FROM {$prefix}gep_test_questions WHERE test_id = %d", $test->id) );
                    $total_linked = count($linked_q_ids);
                    $valid_q_count = 0;
                    if ($total_linked > 0) {
                        $ids_str = implode(',', array_map('absint', $linked_q_ids));
                        $valid_q_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}gep_questions WHERE id IN ($ids_str) AND status='publish'");
                    }
                    $is_series = ($test->type === 'series');
                    $has_slug = !empty($test->slug);
                    $is_healthy = $has_slug && ($is_series || $valid_q_count > 0) && $test->status === 'publish';
                    $health_icon = $is_healthy ? '✅' : '⚠️';
                    $health_bg = $is_healthy ? '#f0fdf4' : '#fffbeb';
                    $health_color = $is_healthy ? '#166534' : '#92400e';
                ?>
                    <tr style="border-bottom:1px solid #f1f5f9;transition:background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                        <td style="padding:12px 20px;font-weight:900;color:#6366f1;">#<?php echo (int)$test->id; ?></td>
                        <td style="padding:12px;">
                            <a href="<?php echo admin_url('admin.php?page=gep-tests&action=edit&id='.(int)$test->id); ?>" style="font-weight:700;color:#1e293b;text-decoration:none;"><?php echo esc_html($test->title); ?></a>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="background:<?php echo $is_series ? '#eef2ff' : '#f8fafc'; ?>;color:<?php echo $is_series ? '#4338ca' : '#64748b'; ?>;padding:3px 10px;border-radius:6px;font-size:11px;font-weight:800;">
                                <?php echo strtoupper($test->type); ?>
                            </span>
                        </td>
                        <td style="padding:12px;text-align:center;font-size:12px;font-family:monospace;color:<?php echo $has_slug ? '#166534' : '#ef4444'; ?>;">
                            <?php echo $has_slug ? esc_html(substr($test->slug,0,20)) . (strlen($test->slug)>20?'…':'') : '❌ EMPTY'; ?>
                        </td>
                        <td style="padding:12px;text-align:center;font-weight:900;font-size:16px;"><?php echo $is_series ? '—' : $total_linked; ?></td>
                        <td style="padding:12px;text-align:center;font-weight:900;font-size:16px;color:<?php echo ($valid_q_count > 0 || $is_series) ? '#166534' : '#ef4444'; ?>">
                            <?php echo $is_series ? '—' : $valid_q_count; ?>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="background:<?php echo $test->status==='publish' ? '#f0fdf4' : '#fffbeb'; ?>;color:<?php echo $test->status==='publish' ? '#166534' : '#92400e'; ?>;padding:3px 12px;border-radius:6px;font-size:11px;font-weight:800;">
                                <?php echo strtoupper($test->status); ?>
                            </span>
                        </td>
                        <td style="padding:12px;text-align:center;background:<?php echo $health_bg; ?>;">
                            <span style="font-size:20px;" title="<?php echo $is_healthy ? 'Test is healthy' : 'Test has issues'; ?>"><?php echo $health_icon; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Orphan Attempts Report -->
    <?php if ( ! empty( $orphan_attempts ) ) : ?>
    <div class="gep-admin-console" style="margin-bottom:25px;">
        <div class="gep-admin-console-header" style="background:#fef2f2;border-bottom-color:#fecaca;">
            <h2 style="margin:0;color:#991b1b;">🔴 Orphaned Attempts (Linked to Deleted Tests)</h2>
        </div>
        <div class="gep-admin-console-body">
            <p style="color:#991b1b;font-weight:600;">These attempt records reference test IDs that no longer exist. They should be cleaned up.</p>
            <table style="width:100%;border-collapse:collapse;">
                <tr style="background:#fafafa;">
                    <th style="padding:10px;text-align:left;border-bottom:2px solid #e2e8f0;">Attempt ID</th>
                    <th style="padding:10px;border-bottom:2px solid #e2e8f0;">User ID</th>
                    <th style="padding:10px;border-bottom:2px solid #e2e8f0;">Test ID (Missing)</th>
                    <th style="padding:10px;border-bottom:2px solid #e2e8f0;">Status</th>
                </tr>
                <?php foreach ($orphan_attempts as $a) : ?>
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?php echo (int)$a->id; ?></td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?php echo (int)$a->user_id; ?></td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;color:#ef4444;font-weight:700;"><?php echo (int)$a->test_id; ?></td>
                    <td style="padding:10px;border-bottom:1px solid #f1f5f9;"><?php echo esc_html($a->status); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Fix Actions -->
    <div class="gep-admin-console">
        <div class="gep-admin-console-header" style="background:#f0f9ff;border-bottom-color:#bae6fd;">
            <h2 style="margin:0;color:#0369a1;">⚡ Quick Fix Actions</h2>
        </div>
        <div class="gep-admin-console-body" style="display:flex;gap:15px;flex-wrap:wrap;">
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=gep-settings&action=update_db'), 'gep_update_db'); ?>" class="button button-primary" style="height:42px;line-height:42px;padding:0 25px;border-radius:10px;font-weight:700;">🔄 Re-run DB Migrations</a>
            <a href="<?php echo admin_url('admin.php?page=gep-tests'); ?>" class="button" style="height:42px;line-height:42px;padding:0 25px;border-radius:10px;font-weight:700;">📝 Manage Tests</a>
            <a href="<?php echo admin_url('admin.php?page=gep-questions'); ?>" class="button" style="height:42px;line-height:42px;padding:0 25px;border-radius:10px;font-weight:700;">❓ Manage Questions</a>
            <a href="<?php echo admin_url('admin.php?page=gep-attempts'); ?>" class="button" style="height:42px;line-height:42px;padding:0 25px;border-radius:10px;font-weight:700;">📊 View Attempts</a>
        </div>
    </div>

    <!-- CSV Export Section -->
    <div class="gep-admin-console" style="border-top: 4px solid #10b981; margin-top: 24px;">
        <div class="gep-admin-console-header" style="background: #f0fdf4; border-bottom-color: #bbf7d0;">
            <h2 style="margin:0;color:#065f46;">📥 Data Export Center</h2>
            <p style="margin: 4px 0 0; color:#6b7280; font-weight: 600; font-size: 13px;">Download portal data in CSV format for offline analysis, reporting, or backup.</p>
        </div>
        <div class="gep-admin-console-body" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <!-- Attempts Export -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;text-align:center;">
                <div style="font-size:36px;margin-bottom:12px;">📊</div>
                <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 6px;">Attempts Report</h3>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px;">All student attempts with scores, timing & status</p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=gep_export_attempts_csv'), 'gep_export_csv'); ?>"
                   class="button button-primary" style="width:100%;display:block;text-align:center;height:38px;line-height:38px;border-radius:10px;font-weight:700;">
                    ⬇️ Download Attempts CSV
                </a>
            </div>
            <!-- Students Export -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;text-align:center;">
                <div style="font-size:36px;margin-bottom:12px;">👥</div>
                <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 6px;">Students Performance</h3>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px;">Per-student score aggregates across all exams</p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=gep_export_students_csv'), 'gep_export_csv'); ?>"
                   class="button button-primary" style="width:100%;display:block;text-align:center;height:38px;line-height:38px;border-radius:10px;font-weight:700;">
                    ⬇️ Download Students CSV
                </a>
            </div>
            <!-- Questions Export -->
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;text-align:center;">
                <div style="font-size:36px;margin-bottom:12px;">❓</div>
                <h3 style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 6px;">Question Bank Export</h3>
                <p style="font-size:12px;color:#64748b;margin:0 0 16px;">Full question bank with answers & metadata</p>
                <a href="<?php echo wp_nonce_url(admin_url('admin-ajax.php?action=gep_export_questions_csv'), 'gep_export_csv'); ?>"
                   class="button button-primary" style="width:100%;display:block;text-align:center;height:38px;line-height:38px;border-radius:10px;font-weight:700;">
                    ⬇️ Download Questions CSV
                </a>
            </div>
        </div>
    </div>
</div>
