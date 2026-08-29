<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Premium Student Management View
 */
global $wpdb;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$query = "SELECT u.*, um.meta_value as phone 
          FROM {$wpdb->users} u 
          LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'gep_phone'";

if ($search) {
    $query .= $wpdb->prepare(" WHERE u.display_name LIKE %s OR u.user_email LIKE %s", "%$search%", "%$search%");
}

$total_items = $wpdb->get_var("SELECT COUNT(*) FROM ($query) as t");
$query .= " LIMIT $offset, $per_page";
$students = $wpdb->get_results($query);
?>

<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);">
        <div>
            <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Student Registry</h1>
            <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Manage student identities and academic performance.</p>
        </div>
        <div class="gep-header-actions">
            <form method="get" class="gep-search-form" style="display: flex; gap: 10px;">
                <input type="hidden" name="page" value="gep-students">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search Identity..." style="height: 45px; border-radius: 12px; border: 1px solid #e2e8f0; padding: 0 15px; font-weight: 600;">
                <button type="submit" class="button button-primary" style="height: 45px; border-radius: 12px; padding: 0 20px; font-weight: 800;">Scan Database</button>
            </form>
        </div>
    </div>

    <div class="gep-stats-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
        <div class="gep-stat-card" style="background: #fff; padding: 25px; border-radius: 20px; border: 1px solid var(--admin-border); position: relative; overflow: hidden;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Total Registered</span>
            <span style="font-size: 32px; font-weight: 900; color: #1e293b;"><?php echo $total_items; ?></span>
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.05; transform: rotate(-15deg);">👥</div>
        </div>
        <div class="gep-stat-card" style="background: #fff; padding: 25px; border-radius: 20px; border: 1px solid var(--admin-border); position: relative; overflow: hidden;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Active Sessions</span>
            <span style="font-size: 32px; font-weight: 900; color: #10b981;"><?php echo $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}gep_attempts WHERE start_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)"); ?></span>
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.05; transform: rotate(-15deg);">⚡</div>
        </div>
        <div class="gep-stat-card" style="background: #fff; padding: 25px; border-radius: 20px; border: 1px solid var(--admin-border); position: relative; overflow: hidden;">
            <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">New This Week</span>
            <span style="font-size: 32px; font-weight: 900; color: #6366f1;"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered > DATE_SUB(NOW(), INTERVAL 7 DAY)"); ?></span>
            <div style="position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.05; transform: rotate(-15deg);">🆕</div>
        </div>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="padding: 20px 25px;">Student Identity</th>
                    <th>Contact Information</th>
                    <th>Academic Status</th>
                    <th>Registration Date</th>
                    <th style="text-align: right; padding-right: 25px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($students): foreach ($students as $student): 
                    $attempts_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_attempts WHERE user_id = %d", $student->ID));
                ?>
                    <tr>
                        <td style="padding: 15px 25px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 900; color: #6366f1;">
                                    <?php echo strtoupper(substr($student->display_name, 0, 1)); ?>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 15px; color: #1e293b;"><?php echo esc_html($student->display_name); ?></strong>
                                    <?php
                                        $reg_year = date('y', strtotime($student->user_registered));
                                        $enrollment_id = 'GP-' . $reg_year . '-' . str_pad($student->ID, 4, '0', STR_PAD_LEFT);
                                    ?>
                                    <span style="font-size: 12px; color: var(--admin-muted); font-weight: 600;">ENROLLMENT: <?php echo esc_html($enrollment_id); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 13px; font-weight: 600; color: #475569;"><?php echo esc_html($student->user_email); ?></div>
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;"><?php echo esc_html($student->phone ?: 'No Phone Record'); ?></div>
                        </td>
                        <td>
                            <span class="status-badge" style="background: #e0e7ff; color: #4338ca; font-weight: 800;">
                                <?php echo $attempts_count; ?> EXAMS ATTEMPTED
                            </span>
                        </td>
                        <td style="font-weight: 700; color: #64748b; font-size: 13px;">
                            <?php echo date('M j, Y', strtotime($student->user_registered)); ?>
                        </td>
                        <td style="text-align: right; padding-right: 25px;">
                            <button class="button button-primary gep-assign-access-btn" 
                                    data-userid="<?php echo $student->ID; ?>" 
                                    data-username="<?php echo esc_attr($student->display_name); ?>"
                                    style="border-radius: 10px; font-weight: 700; margin-right: 5px; background: #6366f1; border-color: #6366f1;">
                                Assign Access
                            </button>
                            <a href="<?php echo add_query_arg( 'uid', $student->ID, (string) gep_get_url('dashboard') ); ?>" class="button button-secondary" style="border-radius: 10px; font-weight: 700;" target="_blank">View Pulse</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 100px;">No identities found in the current registry.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="tablenav bottom" style="margin-top: 20px;">
        <div class="tablenav-pages">
            <?php
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%', admin_url('admin.php?page=gep-students')),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil($total_items / $per_page),
                'current' => $paged
            ));
            ?>
        </div>
    </div>
</div>

<!-- Assign Access Modal -->
<div id="gep-assign-access-modal" class="gep-modal-overlay" style="display: none; position: fixed; inset: 0; z-index: 99999; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
    <div class="gep-modal-content" style="background: #fff; border-radius: 20px; padding: 30px; width: 650px; max-width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
            <div>
                <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;" id="assign-modal-title">Assign Course & Test Access</h2>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 13px; font-weight: 600;">Grant manual academic permissions directly to this student.</p>
            </div>
            <button type="button" class="gep-close-modal-btn" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; outline: none;">✕</button>
        </div>
        
        <form id="gep-assign-access-form">
            <input type="hidden" name="student_id" id="assign-student-id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px;">
                <!-- Courses Column -->
                <div>
                    <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
                        🎬 Courses
                    </h3>
                    <div id="assign-courses-list" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; max-height: 250px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
                
                <!-- Tests/Series Column -->
                <div>
                    <h3 style="margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;">
                        📝 Exams & Series
                    </h3>
                    <div id="assign-tests-list" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; max-height: 250px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <button type="button" class="button gep-close-modal-btn" style="border-radius: 10px; font-weight: 800; height: 40px; padding: 0 20px;">Cancel</button>
                <button type="submit" class="button button-primary" style="border-radius: 10px; font-weight: 800; background: #6366f1; border-color: #6366f1; color: #fff; height: 40px; padding: 0 20px;">Save Enrollment Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Close modal buttons
    $('.gep-close-modal-btn').on('click', function() {
        $('#gep-assign-access-modal').hide();
    });

    // Assign Access Button Click
    $('.gep-assign-access-btn').on('click', function() {
        var userId = $(this).data('userid');
        var userName = $(this).data('username');
        
        $('#assign-student-id').val(userId);
        $('#assign-modal-title').text('Grant Access: ' + userName);
        
        // Show loading states
        $('#assign-courses-list').html('<p style="font-size: 13px; color: #94a3b8; font-weight: 600;">Loading courses...</p>');
        $('#assign-tests-list').html('<p style="font-size: 13px; color: #94a3b8; font-weight: 600;">Loading exams...</p>');
        
        $('#gep-assign-access-modal').css('display', 'flex');
        
        // Fetch current access and available items
        $.post(ajaxurl, {
            action: 'gep_get_student_access_data',
            student_id: userId,
            nonce: '<?php echo wp_create_nonce("gep_student_access"); ?>'
        }, function(response) {
            if (response.success) {
                var data = response.data;
                
                // Render Courses
                var coursesHTML = '';
                if (data.courses && data.courses.length > 0) {
                    data.courses.forEach(function(c) {
                        var checked = data.current_courses.indexOf(c.id.toString()) !== -1 || data.current_courses.indexOf(parseInt(c.id)) !== -1 ? 'checked' : '';
                        coursesHTML += '<label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer; margin-bottom: 2px;">' +
                            '<input type="checkbox" name="course_ids[]" value="' + c.id + '" ' + checked + '> ' +
                            '<span>' + c.title + '</span>' +
                            '</label>';
                    });
                } else {
                    coursesHTML = '<p style="font-size: 13px; color: #94a3b8; font-weight: 600;">No courses published.</p>';
                }
                $('#assign-courses-list').html(coursesHTML);
                
                // Render Tests
                var testsHTML = '';
                if (data.tests && data.tests.length > 0) {
                    data.tests.forEach(function(t) {
                        var checked = data.current_tests.indexOf(t.id.toString()) !== -1 || data.current_tests.indexOf(parseInt(t.id)) !== -1 ? 'checked' : '';
                        testsHTML += '<label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer; margin-bottom: 2px;">' +
                            '<input type="checkbox" name="test_ids[]" value="' + t.id + '" ' + checked + '> ' +
                            '<span>' + t.title + '</span>' +
                            '</label>';
                    });
                } else {
                    testsHTML = '<p style="font-size: 13px; color: #94a3b8; font-weight: 600;">No tests/series published.</p>';
                }
                $('#assign-tests-list').html(testsHTML);
            } else {
                alert('Error loading access registry: ' + response.data);
            }
        });
    });

    // Form Submission
    $('#gep-assign-access-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Saving Changes...');
        
        $.post(ajaxurl, {
            action: 'gep_save_student_access_data',
            student_id: $('#assign-student-id').val(),
            course_ids: form.find('input[name="course_ids[]"]:checked').map(function() { return this.value; }).get(),
            test_ids: form.find('input[name="test_ids[]"]:checked').map(function() { return this.value; }).get(),
            nonce: '<?php echo wp_create_nonce("gep_student_access"); ?>'
        }, function(response) {
            submitBtn.prop('disabled', false).text('Save Enrollment Changes');
            if (response.success) {
                $('#gep-assign-access-modal').hide();
                alert('Access settings updated successfully.');
                window.location.reload();
            } else {
                alert('Failed to update access: ' + response.data);
            }
        });
    });
});
</script>
