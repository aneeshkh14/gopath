<?php if ( ! defined( 'ABSPATH' ) ) exit; global $wpdb; 
$filter_course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;
$query = "SELECT l.*, c.title as course_title FROM {$wpdb->prefix}gep_lessons l JOIN {$wpdb->prefix}gep_courses c ON l.course_id = c.id";
if ($filter_course_id) {
    $query .= $wpdb->prepare(" WHERE l.course_id = %d", $filter_course_id);
}
$query .= " ORDER BY l.order_no ASC";
$lessons = $wpdb->get_results( $query );
$courses = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gep_courses ORDER BY title ASC" );
?>
<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0;">Lesson & Video Manager</h1>
            <p style="color: var(--admin-muted); font-weight: 600;">Organize and schedule your video curriculum across courses.</p>
        </div>
        <button class="button button-primary" style="height: 50px; border-radius: 12px; padding: 0 30px; font-weight: 800; font-size: 14px;" onclick="openLessonModal()">+ Upload Video Lesson</button>
    </div>

    <div class="gep-admin-recent-activity" style="margin-bottom: 30px; padding: 25px; border-radius: 24px; background: #fff; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
        <form method="get" action="" style="display: flex; gap: 25px; align-items: center;">
            <input type="hidden" name="page" value="gep-lessons">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #f0f9ff; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #0369a1; border: 1px solid #e0f2fe;">📚</div>
                <div>
                    <label style="font-weight: 900; color: #1e293b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 2px;">Curriculum Filter</label>
                    <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Segment by Academic Course</span>
                </div>
            </div>
            <select name="course_id" style="height: 50px; border-radius: 14px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; min-width: 300px; background: #f8fafc; color: #1e293b;">
                <option value="">All Active Courses</option>
                <?php foreach($courses as $c): 
                    $selected = (isset($_GET['course_id']) && $_GET['course_id'] == $c->id) ? 'selected' : '';
                ?>
                    <option value="<?php echo $c->id; ?>" <?php echo $selected; ?>><?php echo esc_html($c->title); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 35px; font-weight: 900; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.25);">Apply Intelligence Filter</button>
        </form>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 28px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; box-shadow: var(--admin-shadow-lg);">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="padding-left: 30px; width: 30%;">Lesson Title</th>
                    <th style="width: 220px;">Associated Course</th>
                    <th style="width: 150px;">Platform Source</th>
                    <th style="width: 130px;">Duration</th>
                    <th style="padding-right: 30px; text-align: right; width: 220px;">Management Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lessons): foreach($lessons as $l): ?>
                <tr>
                    <td style="padding-left: 30px;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 15px;"><?php echo esc_html($l->title); ?></div>
                        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px;"><?php echo esc_url($l->video_url); ?></div>
                    </td>
                    <td>
                        <div style="background: #eef2ff; color: #4f46e5; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 12px; border: 1px solid #e0e7ff; display: inline-block;">
                            <?php echo esc_html($l->course_title); ?>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: <?php echo $l->video_source === 'youtube' ? '#fef2f2; color: #dc2626; border: 1px solid #fee2e2;' : '#e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;'; ?>; font-weight: 900; font-size: 10px;">
                            <?php echo strtoupper($l->video_source ?: 'VIDEO'); ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-weight: 700; font-size: 14px;">
                            <span style="font-size: 16px;">⏱️</span> <?php echo esc_html($l->duration); ?>
                        </div>
                    </td>
                    <td style="padding-right: 30px; text-align: right;">
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <a href="<?php echo esc_url(add_query_arg(array('view' => 'watch', 'id' => $l->course_id, 'lesson' => $l->id), gep_get_url('dashboard'))); ?>" target="_blank" rel="noopener" class="button button-secondary" style="border-radius: 10px; font-weight: 800; font-size: 12px; padding: 8px 15px;">Preview</a>
                            <button type="button" class="button button-secondary" style="border-radius: 10px; font-weight: 800; font-size: 12px; padding: 4px 15px;" 
                                onclick="editLesson(<?php echo esc_attr(json_encode(array(
                                    'id' => $l->id,
                                    'title' => $l->title,
                                    'course_id' => $l->course_id,
                                    'video_source' => $l->video_source,
                                    'video_url' => $l->video_url,
                                    'pdf_url' => $l->pdf_url,
                                    'description' => $l->description,
                                    'duration' => $l->duration,
                                    'order_no' => $l->order_no
                                ))); ?>)">Edit</button>
                            <a href="<?php echo admin_url('admin.php?page=gep-lessons&action=delete_lesson&id='.$l->id.'&nonce='.wp_create_nonce('gep_delete_lesson')); ?>" class="button" style="color: #ef4444; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 10px; font-weight: 800; font-size: 12px; padding: 8px 15px;" onclick="return confirm('Archive this lesson?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="empty-state" style="padding: 120px 50px;">
                        <div style="background: #fff; width: 100px; height: 100px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;">📽️</div>
                        <h3 style="font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 12px; letter-spacing: -0.5px;">The studio is empty</h3>
                        <p style="color: #64748b; font-weight: 600; max-width: 450px; margin: 0 auto 40px; font-size: 15px; line-height: 1.6;">No video lessons have been uploaded yet. Start building your curriculum to populate this manager.</p>
                        <button class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 35px; font-weight: 900; font-size: 14px;" onclick="openLessonModal()">+ Upload First Lesson</button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lesson Modal (Premium Refined) -->
<div id="gep-lesson-modal" class="gep-modal-overlay">
    <div class="gep-modal-content">
        <h2 id="gep-lesson-modal-title" style="margin-bottom: 8px; font-size: 22px; font-weight: 900; color: #0f172a;">Upload Video Content</h2>
        <p style="color: #64748b; margin-bottom: 25px; font-weight: 600; font-size: 13px;">Link your video lectures to specific courses.</p>
        
        <form method="post">
            <?php wp_nonce_field('gep_save_lesson', 'gep_nonce'); ?>
            <input type="hidden" name="gep_lesson_save" value="1">
            <input type="hidden" name="lesson_id" id="gep_lesson_id" value="">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Lesson Title</label>
                <input type="text" id="gep_lesson_title" name="title" required placeholder="e.g. Chapter 1: Foundations of Algebra" style="width: 100%;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Select Course</label>
                    <select id="gep_lesson_course_id" name="course_id" required style="width: 100%;" <?php echo empty($courses) ? 'disabled' : ''; ?>>
                        <?php if ( empty($courses) ) : ?>
                            <option value="">No active courses found...</option>
                        <?php else : ?>
                            <?php foreach($courses as $c): ?>
                                <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->title); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php if ( empty($courses) ) : ?>
                        <div style="margin-top: 8px; color: #ef4444; font-size: 11px; font-weight: 800;">
                            ⚠️ <a href="<?php echo admin_url('admin.php?page=gep-courses'); ?>" style="color: #ef4444; text-decoration: underline; font-weight: 800;">Create a course first</a> before adding lessons.
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Platform Source</label>
                    <select id="gep_lesson_source" name="source" style="width: 100%;">
                        <option value="youtube">YouTube (Public/Unlisted)</option>
                        <option value="vimeo">Vimeo (Private)</option>
                        <option value="direct">Direct CDN Link (MP4)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Video Link / Resource ID</label>
                <input type="text" id="gep_lesson_video_url" name="video_url" required placeholder="Paste URL or unique video ID" style="width: 100%;" <?php echo empty($courses) ? 'disabled' : ''; ?>>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Document Link (PDF Study Material)</label>
                <input type="text" id="gep_lesson_pdf_url" name="pdf_url" placeholder="Paste URL to PDF study guide or notes" style="width: 100%;" <?php echo empty($courses) ? 'disabled' : ''; ?>>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Lesson Description</label>
                <textarea id="gep_lesson_description" name="description" placeholder="Briefly describe what students will learn..." style="width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px; font-size: 14px;" rows="3" <?php echo empty($courses) ? 'disabled' : ''; ?>></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Est. Duration (MM:SS)</label>
                    <input type="text" id="gep_lesson_duration" name="duration" placeholder="45:00" style="width: 100%;" <?php echo empty($courses) ? 'disabled' : ''; ?>>
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Sequence Order</label>
                    <input type="number" id="gep_lesson_order_num" name="order_num" value="1" style="width: 100%;" <?php echo empty($courses) ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" id="gep_lesson_submit_btn" class="button button-primary" style="flex: 1; height: 48px; border-radius: 12px; font-weight: 900; font-size: 15px;" <?php echo empty($courses) ? 'disabled style="opacity: 0.5; cursor: not-allowed; background: #94a3b8 !important; box-shadow: none !important;"' : ''; ?>>Publish Lesson</button>
                <button type="button" class="button button-discard" style="flex: 1; height: 48px; border-radius: 12px; font-weight: 800; font-size: 15px;" onclick="document.getElementById('gep-lesson-modal').style.display='none'">Discard</button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
    function openLessonModal() {
        document.getElementById('gep_lesson_id').value = '';
        document.getElementById('gep_lesson_title').value = '';
        document.getElementById('gep_lesson_video_url').value = '';
        document.getElementById('gep_lesson_pdf_url').value = '';
        document.getElementById('gep_lesson_description').value = '';
        document.getElementById('gep_lesson_duration').value = '';
        document.getElementById('gep_lesson_order_num').value = '1';
        
        document.getElementById('gep-lesson-modal-title').innerText = 'Upload Video Content';
        document.getElementById('gep_lesson_submit_btn').innerText = 'Publish Lesson';
        document.getElementById('gep-lesson-modal').style.display = 'flex';
    }

    function editLesson(lesson) {
        document.getElementById('gep_lesson_id').value = lesson.id;
        document.getElementById('gep_lesson_title').value = lesson.title;
        document.getElementById('gep_lesson_course_id').value = lesson.course_id;
        document.getElementById('gep_lesson_source').value = lesson.video_source;
        document.getElementById('gep_lesson_video_url').value = lesson.video_url;
        document.getElementById('gep_lesson_pdf_url').value = lesson.pdf_url || '';
        document.getElementById('gep_lesson_description').value = lesson.description || '';
        document.getElementById('gep_lesson_duration').value = lesson.duration || '';
        document.getElementById('gep_lesson_order_num').value = lesson.order_no || '1';

        document.getElementById('gep-lesson-modal-title').innerText = 'Edit Video Lesson';
        document.getElementById('gep_lesson_submit_btn').innerText = 'Save Changes';
        document.getElementById('gep-lesson-modal').style.display = 'flex';
    }
</script>
