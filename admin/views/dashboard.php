<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);">
        <div>
            <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Intelligence Command Center</h1>
            <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Strategic overview of platform performance and student engagement.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <a href="<?php echo admin_url('admin.php?page=gep-dashboard&action=seed_test_data&nonce=' . wp_create_nonce('gep_seed_data')); ?>" class="button button-primary" style="height: 45px; border-radius: 12px; font-weight: 800; padding: 0 20px;" onclick="return confirm('This will add sample courses, tests, and PYQ questions. Continue?');">🚀 Seed Production Assets</a>
        </div>
    </div>

    <?php if (isset($_GET['seeded'])) : ?>
        <div class="notice notice-success is-dismissible" style="margin-bottom: 25px; border-radius: 12px; border-left-width: 5px;">
            <p><strong>Database Synchronized!</strong> Sample courses, tests, and questions have been injected perfectly.</p>
        </div>
    <?php endif; ?>

    <div class="gep-admin-stats-grid" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 35px;">
        <div class="gep-stat-card" style="background: #fff; padding: 20px; border-radius: 20px; border: 1px solid var(--admin-border); border-top: 4px solid #6366f1;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Students</span>
            <span style="font-size: 28px; font-weight: 900; color: #1e293b;">
                <?php 
                $user_count = count_users();
                echo isset($user_count['avail_roles']['subscriber']) ? $user_count['avail_roles']['subscriber'] : 0; 
                ?>
            </span>
            <a href="<?php echo admin_url('admin.php?page=gep-students'); ?>" style="display: block; font-size: 11px; font-weight: 700; color: #6366f1; margin-top: 10px; text-decoration: none;">Registry →</a>
        </div>

        <div class="gep-stat-card" style="background: #fff; padding: 20px; border-radius: 20px; border: 1px solid var(--admin-border); border-top: 4px solid #8b5cf6;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Courses</span>
            <?php 
            global $wpdb;
            $course_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gep_courses WHERE status = 'publish'" ); 
            ?>
            <span style="font-size: 28px; font-weight: 900; color: #1e293b;"><?php echo absint( $course_count ); ?></span>
            <a href="<?php echo admin_url('admin.php?page=gep-courses'); ?>" style="display: block; font-size: 11px; font-weight: 700; color: #8b5cf6; margin-top: 10px; text-decoration: none;">Curriculum →</a>
        </div>

        <div class="gep-stat-card" style="background: #fff; padding: 20px; border-radius: 20px; border: 1px solid var(--admin-border); border-top: 4px solid #10b981;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Live Exams</span>
            <?php 
            $test_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gep_tests WHERE status = 'publish'" ); 
            ?>
            <span style="font-size: 28px; font-weight: 900; color: #1e293b;"><?php echo absint( $test_count ); ?></span>
            <a href="<?php echo admin_url('admin.php?page=gep-tests'); ?>" style="display: block; font-size: 11px; font-weight: 700; color: #10b981; margin-top: 10px; text-decoration: none;">Bank →</a>
        </div>

        <div class="gep-stat-card" style="background: #fff; padding: 20px; border-radius: 20px; border: 1px solid var(--admin-border); border-top: 4px solid #f59e0b;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Gross Revenue</span>
            <?php 
            $revenue = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}gep_orders WHERE status = 'success'" ); 
            ?>
            <span style="font-size: 28px; font-weight: 900; color: #1e293b;">₹<?php echo number_format( floatval( $revenue ), 0 ); ?></span>
            <a href="<?php echo admin_url('admin.php?page=gep-payments'); ?>" style="display: block; font-size: 11px; font-weight: 700; color: #f59e0b; margin-top: 10px; text-decoration: none;">Transactions →</a>
        </div>

        <div class="gep-stat-card" style="background: #fff; padding: 20px; border-radius: 20px; border: 1px solid var(--admin-border); border-top: 4px solid #ef4444;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Violations</span>
            <?php 
            $violation_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gep_violations WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)" ); 
            ?>
            <span style="font-size: 28px; font-weight: 900; color: #ef4444;"><?php echo absint( $violation_count ); ?></span>
            <a href="<?php echo admin_url('admin.php?page=gep-violations'); ?>" style="display: block; font-size: 11px; font-weight: 700; color: #ef4444; margin-top: 10px; text-decoration: none;">Security →</a>
        </div>
    </div>

    <div style="background: #f8fafc; padding: 25px; border-radius: 24px; border: 1px solid #e2e8f0; margin-bottom: 35px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="width: 50px; height: 50px; background: #e0e7ff; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                🎧
            </div>
            <div>
                <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #1e293b;">Developer Support</h3>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px; font-weight: 600;">Encountering bugs or need custom features? Reach out to the GoPath engineering team.</p>
            </div>
        </div>
        <a href="mailto:help@gopath.in" class="button button-primary" style="height: 45px; line-height: 43px; padding: 0 25px; border-radius: 12px; font-weight: 800; background: #4f46e5; border-color: #4f46e5; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);">Contact help@gopath.in</a>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 900; color: #1e293b;">Live Performance Stream</h2>
            <span style="font-size: 11px; font-weight: 800; color: #10b981; display: flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span>
                REAL-TIME MONITORING
            </span>
        </div>
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="padding: 15px 25px;">Student</th>
                    <th>Exam Asset</th>
                    <th>Engagement Window</th>
                    <th>Performance</th>
                    <th>Outcome</th>
                    <th style="text-align: right; padding-right: 25px;">Intelligence</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent_attempts = $wpdb->get_results( "SELECT a.*, t.title as test_name, u.display_name FROM {$wpdb->prefix}gep_attempts a JOIN {$wpdb->prefix}gep_tests t ON a.test_id = t.id JOIN {$wpdb->users} u ON a.user_id = u.ID ORDER BY a.start_time DESC LIMIT 8" );
                if ( $recent_attempts ) :
                    foreach ( $recent_attempts as $attempt ) : ?>
                        <tr>
                            <td style="padding: 15px 25px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #6366f1; font-size: 12px;">
                                        <?php echo strtoupper(substr($attempt->display_name, 0, 1)); ?>
                                    </div>
                                    <strong style="color: #1e293b; font-size: 14px;"><?php echo esc_html( $attempt->display_name ); ?></strong>
                                </div>
                            </td>
                            <td class="row-title" style="font-weight: 600;"><?php echo esc_html( $attempt->test_name ); ?></td>
                            <td style="color: #64748b; font-weight: 700; font-size: 12px;"><?php echo date('M d, Y • H:i', strtotime($attempt->start_time)); ?></td>
                            <td style="font-weight: 900; font-size: 15px;">
                                <?php echo $attempt->status === 'submitted' ? $attempt->score . '%' : '<span style="color: #6366f1; font-size: 11px;">SESSION ACTIVE</span>'; ?>
                            </td>
                            <td>
                                <?php if ($attempt->status === 'submitted') : ?>
                                    <span class="status-badge" style="background: <?php echo $attempt->is_pass ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;'; ?>; width: 80px; text-align: center; font-weight: 900;">
                                        <?php echo $attempt->is_pass ? 'SUCCESS' : 'FAILURE'; ?>
                                    </span>
                                <?php else : ?>
                                    <span class="status-badge" style="background: #f1f5f9; color: #64748b; width: 80px; text-align: center;">PENDING</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; padding-right: 25px;">
                                <a href="<?php echo esc_url( gep_get_url('dashboard') . '?uid=' . $attempt->user_id ); ?>" class="button button-secondary" style="border-radius: 8px; font-weight: 800; font-size: 11px;" target="_blank">View Pulse</a>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="6" class="empty-state" style="padding: 100px;">
                            <div style="font-size: 50px; margin-bottom: 20px;">📉</div>
                            <h3 style="font-weight: 900; color: #1e293b;">Data Stream Inactive</h3>
                            <p style="color: #64748b; font-weight: 600;">Activity will appear here as soon as students engage with intelligence assets.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

