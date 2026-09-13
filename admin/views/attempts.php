<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap gep-admin-wrap">
    <?php 
    global $wpdb; 
    $handler = new GEP_Admin_Attempts();
    
    // Feedback Messages
    if ( isset( $_GET['message'] ) ) {
        $msg = sanitize_text_field( $_GET['message'] );
        if ( $msg === 'granted' ) echo '<div class="notice notice-success is-dismissible"><p>Extra attempts granted successfully.</p></div>';
        if ( $msg === 'deleted' ) echo '<div class="notice notice-info is-dismissible"><p>Attempt session has been terminated.</p></div>';
    }
    if ( isset( $_GET['error'] ) && $_GET['error'] === 'nonce' ) {
        echo '<div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>';
    }
    ?>
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow-sm);">
        <div>
            <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Exam Session Audit</h1>
            <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Comprehensive registry of student engagement and attempt lifecycles.</p>
        </div>
    </div>

    <!-- Premium Grant Tool -->
    <div class="gep-admin-recent-activity" style="margin-bottom: 40px; border-left: 5px solid var(--admin-primary);">
        <h3 style="margin-top: 0; font-size: 18px; font-weight: 800; color: var(--admin-text);">🚀 Quick Grant: Extra Attempts</h3>
        <p style="color: var(--admin-muted); font-size: 13px; margin-bottom: 25px;">Need to give a student another chance? Use the tool below to instantly add attempt credits.</p>
        
        <form class="gep-responsive-grid" method="post" action="" style="display: grid; grid-template-columns: 1fr 1fr 120px 200px; gap: 20px; align-items: flex-end;">
            <?php wp_nonce_field('gep_grant_attempt', 'gep_nonce'); ?>
            <div>
                <label style="display: block; font-weight: 700; font-size: 12px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Student Account</label>
                <select name="user_id" style="width: 100%; border-radius: 10px; border-color: var(--admin-border); box-sizing: border-box;">
                    <option value="">Select a student...</option>
                    <?php
                    $students = get_users(array('role' => 'subscriber'));
                    if ( ! empty( $students ) && is_array( $students ) ) {
                        foreach($students as $s) {
                            if ( is_object( $s ) ) {
                                echo '<option value="'.$s->ID.'">'.esc_html($s->display_name).' ('.$s->user_email.')</option>';
                            }
                        }
                    }
                    ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 12px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Target Exam</label>
                <select name="test_id" style="width: 100%; border-radius: 10px; border-color: var(--admin-border); box-sizing: border-box;">
                    <?php
                    $tests = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}gep_tests");
                    if ( $tests ) {
                        foreach($tests as $t) echo '<option value="'.$t->id.'">'.esc_html($t->title).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 12px; color: var(--admin-muted); margin-bottom: 8px; text-transform: uppercase;">Credits</label>
                <input type="number" name="count" value="1" min="1" style="width: 100%; border-radius: 10px; border-color: var(--admin-border); text-align: center; box-sizing: border-box;">
            </div>
            <div>
                <button type="submit" name="gep_grant_submit" class="button button-primary" style="width: 100%; height: 48px; border-radius: 10px; font-weight: 800; box-sizing: border-box;">Grant Now</button>
            </div>
        </form>
    </div>

    <!-- Attempts Table -->
    <div class="gep-admin-table-container">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--admin-border); display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 900;">All Student Attempts</h2>
            <div style="font-size: 13px; color: var(--admin-muted); font-weight: 600;">Showing last 50 entries</div>
        </div>
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>User</th>
                    <th>Exam Title</th>
                    <th>Score</th>
                    <th>Result</th>
                    <th>Date Started</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $attempts = $handler->get_attempts( 50 );
                if ( ! empty( $attempts ) ) :
                    foreach ( $attempts as $a ) : ?>
                        <tr>
                            <td style="font-weight: 800; color: var(--admin-muted);">#<?php echo $a->id; ?></td>
                            <td><strong><?php echo esc_html( $a->display_name ); ?></strong></td>
                            <td class="row-title"><?php echo esc_html( $a->test_name ); ?></td>
                            <td style="font-weight: 800; font-size: 15px; color: var(--admin-text);"><?php echo $a->score; ?>%</td>
                            <td>
                                <span class="status-badge" style="background: <?php echo $a->is_pass ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;'; ?>">
                                    <?php echo $a->is_pass ? 'PASS' : 'FAIL'; ?>
                                </span>
                            </td>
                            <td style="color: var(--admin-muted); font-weight: 600;"><?php echo date('M d, Y • H:i', strtotime($a->start_time)); ?></td>
                            <td>
                                <span class="status-badge" style="background: #f1f5f9; color: #64748b;">
                                    <?php echo strtoupper($a->status); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo gep_get_url('result') . '?id=' . $a->id; ?>" target="_blank" class="button button-secondary" style="border-radius: 8px; font-weight: 700; font-size: 11px;">View</a>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gep-attempts&action=delete&id=' . $a->id ), 'gep_delete_attempt', 'nonce' ); ?>" class="button" style="border-radius: 8px; font-weight: 700; font-size: 11px; color: #ef4444; border-color: #fecaca;" onclick="return confirm('Are you sure you want to terminate this session?')">Kill</a>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div style="font-size: 40px; margin-bottom: 15px;">🏁</div>
                            <h3>No attempts found</h3>
                            <p>Student exam activity will be listed here automatically.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
