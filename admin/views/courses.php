<?php if ( ! defined( 'ABSPATH' ) ) exit; global $wpdb; 
$courses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_courses ORDER BY id DESC" ); ?>
<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0;">Course Portfolio</h1>
            <p style="color: var(--admin-muted); font-weight: 600;">Manage your premium video lectures and curriculum.</p>
        </div>
        <button class="button button-primary" style="height: 50px; border-radius: 12px; padding: 0 30px; font-weight: 800; font-size: 14px;" onclick="document.getElementById('gep-course-modal').style.display='flex'">+ Create New Course</button>
    </div>

    <!-- Stats Row -->
    <div class="gep-admin-stats-grid" style="margin-bottom: 40px;">
        <div class="gep-stat-card" style="border-left: 5px solid var(--admin-primary);">
            <h3>Total Courses</h3>
            <p class="number"><?php echo count($courses); ?></p>
            <span style="font-size: 12px; color: var(--admin-muted); font-weight: 700;">LIVE CURRICULUM</span>
        </div>
        <div class="gep-stat-card" style="border-left: 5px solid #8b5cf6;">
            <h3>Active Lessons</h3>
            <p class="number">
                <?php echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gep_lessons"); ?>
            </p>
            <span style="font-size: 12px; color: var(--admin-muted); font-weight: 700;">VIDEO LECTURES</span>
        </div>
        <div class="gep-stat-card" style="border-left: 5px solid #10b981;">
            <h3>Total Sales</h3>
            <p class="number">
                <?php 
                $course_revenue = $wpdb->get_var( "SELECT SUM(amount) FROM {$wpdb->prefix}gep_orders WHERE status = 'success' AND item_type = 'course'" );
                echo '₹' . number_format( floatval( $course_revenue ), 0 );
                ?>
            </p>
            <span style="font-size: 12px; color: var(--admin-muted); font-weight: 700;">GROSS REVENUE</span>
        </div>
    </div>

    <!-- Target Segment Integration Guidance -->
    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 16px; padding: 20px; margin-bottom: 30px; display: flex; gap: 15px; align-items: flex-start;">
        <span style="font-size: 24px; line-height: 1;">💡</span>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 5px; color: #1e3a8a; font-size: 15px; font-weight: 800;">Target Segment Mapping Guide</h4>
            <p style="margin: 0; color: #1e40af; font-size: 13px; font-weight: 600; line-height: 1.5;">
                Courses are dynamically displayed in separate frontend sections based on their Category Slugs:
            </p>
            <ul style="margin: 8px 0 0; padding-left: 20px; color: #1e40af; font-size: 13px; font-weight: 600; line-height: 1.5; list-style-type: disc;">
                <li><strong>Skill Academy:</strong> Assign the course to a Category whose slug is exactly <code>english-typing</code>, <code>data-entry</code>, <code>excel-mastery</code>, <code>web-design</code>, or <code>public-speaking</code>.</li>
                <li><strong>SuperCoaching:</strong> Assign the course to any other category.</li>
            </ul>
            <p style="margin: 8px 0 0; color: #1e40af; font-size: 13px; font-weight: 600;">
                Category slugs can be managed from the <a href="<?php echo admin_url('admin.php?page=gep-categories'); ?>" style="color: #2563eb; text-decoration: underline; font-weight: 800;">Categories Panel</a>.
            </p>
        </div>
    </div>

    <!-- Course List -->
    <div class="gep-admin-table-container" style="border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff;">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="width: 120px; padding-left: 25px;">Preview</th>
                    <th>Course Title</th>
                    <th>Lead Instructor</th>
                    <th>Enrollment Price</th>
                    <th style="text-align: right; padding-right: 25px;">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($courses): foreach($courses as $c): ?>
                <tr>
                    <td style="padding-left: 25px;">
                        <div style="width: 100px; height: 60px; overflow: hidden; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <img src="<?php echo $c->thumbnail ?: GEP_PLUGIN_URL.'assets/images/placeholder-test.jpg'; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </td>
                    <td>
                        <strong class="row-title" style="font-size: 16px; color: #1e293b;"><?php echo esc_html($c->title); ?></strong>
                        <div style="font-size: 11px; color: var(--admin-muted); font-weight: 700; margin-top: 4px;">UID: #CRS-<?php echo str_pad($c->id, 4, '0', STR_PAD_LEFT); ?></div>
                    </td>
                    <td style="color: #475569; font-weight: 700; font-size: 14px;"><?php echo esc_html($c->instructor); ?></td>
                    <td style="font-weight: 900; font-size: 16px; color: #10b981;">₹<?php echo number_format($c->price, 0); ?></td>
                    <td style="text-align: right; padding-right: 25px;">
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="<?php echo admin_url('admin.php?page=gep-lessons&course_id='.$c->id); ?>" class="button button-secondary" style="border-radius: 10px; font-weight: 800;">Curriculum</a>
                            <a href="<?php echo admin_url('admin.php?page=gep-courses&action=delete_course&id='.$c->id.'&nonce='.wp_create_nonce('gep_delete_course')); ?>" class="button" style="color: #ef4444; border-color: #fee2e2; border-radius: 10px; font-weight: 800; background: #fff5f5;" onclick="return confirm('Delete this course?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="empty-state">
                        <div style="font-size: 40px; margin-bottom: 15px;">🎬</div>
                        <h3>No courses published yet</h3>
                        <p>Start your academy by uploading your first video course.</p>
                        <button class="button button-primary" onclick="document.getElementById('gep-course-modal').style.display='flex'">Launch First Course</button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Course Modal (Premium Refined) -->
<div id="gep-course-modal" class="gep-modal-overlay">
    <div class="gep-modal-content">
        <h2 style="margin-bottom: 8px; font-size: 22px; font-weight: 900; color: #0f172a;">New Course Launch</h2>
        <p style="color: #64748b; margin-bottom: 25px; font-weight: 600; font-size: 13px;">Complete the fields below to publish a new course.</p>
        
        <form method="post">
            <?php wp_nonce_field('gep_save_course', 'gep_nonce'); ?>
            <input type="hidden" name="gep_course_save" value="1">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Course Title</label>
                <input type="text" name="title" required placeholder="e.g. Masterclass in Advanced Mathematics">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Lead Instructor</label>
                    <input type="text" name="instructor" required placeholder="Full Name">
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Enrollment Price (₹)</label>
                    <input type="number" name="price" required placeholder="0">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Promotional Thumbnail URL</label>
                <input type="url" name="thumbnail" placeholder="https://your-storage.com/image.jpg">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Category</label>
                    <select name="category_id" id="gep_course_category_id" style="width: 100%;">
                        <option value="0">Select Category</option>
                        <?php
                        $category_logic = new GEP_Category();
                        $categories = $category_logic->get_categories(0);
                        foreach($categories as $cat) {
                            echo '<option value="'.$cat->id.'" style="font-weight:800;">'.esc_html($cat->name).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Subcategory</label>
                    <select name="subcategory_id" id="gep_course_subcategory_id" style="width: 100%;">
                        <option value="0">Select Subcategory</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 30px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Course Syllabus / Description</label>
                <textarea name="description" rows="4" style="width: 100%;"></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" class="button button-primary" style="flex: 1; height: 48px; border-radius: 12px; font-weight: 900; font-size: 15px;">Publish Course</button>
                <button type="button" class="button button-discard" style="flex: 1; height: 48px; border-radius: 12px; font-weight: 800; font-size: 15px;" onclick="document.getElementById('gep-course-modal').style.display='none'">Discard</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#gep_course_category_id').on('change', function() {
        var catId = $(this).val();
        var subDropdown = $('#gep_course_subcategory_id');
        subDropdown.html('<option value="0">Loading...</option>');
        
        $.post(ajaxurl, {
            action: 'gep_get_subcategories',
            category_id: catId,
            nonce: gepAdminAjax.nonce
        }, function(response) {
            if(response.success) {
                subDropdown.html('<option value="0">Select Subcategory</option>');
                if(response.data.length > 0) {
                    $.each(response.data, function(i, item) {
                        subDropdown.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                }
            } else {
                subDropdown.html('<option value="0">Select Subcategory</option>');
            }
        }).fail(function() {
            subDropdown.html('<option value="0">Select Subcategory</option>');
        });
    });
});
</script>
