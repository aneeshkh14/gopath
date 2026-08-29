<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

$lectures_logic = new GEP_Admin_Lectures();
$lectures = $lectures_logic->get_lectures();

$category_logic = new GEP_Category();
$categories = $category_logic->get_categories(0);
?>

<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0; font-size: 26px; letter-spacing: -0.8px;">Video Lectures Hub</h1>
            <p style="color: var(--admin-muted); font-weight: 600; font-size: 14px;">Manage standalone lecture videos and organize them by subjects.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <button class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 30px; font-weight: 900; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);" onclick="openLectureModal()">+ Upload Lecture</button>
        </div>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 28px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; box-shadow: var(--admin-shadow-lg);">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="padding-left: 30px; width: 35%;">Lecture Title</th>
                    <th style="width: 250px;">Subject & Topic</th>
                    <th style="width: 150px;">Duration</th>
                    <th style="padding-right: 30px; text-align: right; width: 180px;">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($lectures): foreach($lectures as $item): ?>
                <tr>
                    <td style="padding-left: 30px;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 16px;">
                            <?php echo esc_html($item->title); ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 4px;">Instructor: <?php echo esc_html($item->instructor); ?></div>
                    </td>
                    <td>
                        <div style="background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #f1f5f9; display: inline-block;">
                            <span style="font-weight: 700; color: #475569; font-size: 12px;">
                                <?php 
                                if($item->category_id) {
                                    $c_title = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $item->category_id));
                                    echo esc_html($c_title);
                                } else echo 'Uncategorized';
                                
                                if($item->subcategory_id) {
                                    $sc_title = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $item->subcategory_id));
                                    echo ' / ' . esc_html($sc_title);
                                }
                                ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: #1e293b; font-size: 14px;">⏱ <?php echo esc_html($item->duration); ?></div>
                    </td>
                    <td style="padding-right: 30px; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="<?php echo esc_url($item->video_url); ?>" target="_blank" class="button" style="border-radius: 10px; font-weight: 800; font-size: 11px; background: #f1f5f9; border: 1px solid #e2e8f0;">👁️ View</a>
                            <a href="<?php echo admin_url('admin.php?page=gep-lectures&action=delete_lecture&id='.$item->id.'&nonce='.wp_create_nonce('gep_delete_lecture')); ?>" class="button" style="color: #ef4444; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 10px; font-weight: 800; font-size: 11px;" onclick="return confirm('Delete this lecture?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="4" class="empty-state" style="padding: 120px 50px;">
                        <div style="background: #fff; width: 100px; height: 100px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;">🎥</div>
                        <h3 style="font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 12px;">No lectures found</h3>
                        <p style="color: #64748b; font-weight: 600; max-width: 450px; margin: 0 auto 40px;">Populate your video library by uploading your first standalone lecture video.</p>
                        <button class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 35px; font-weight: 900; font-size: 14px;" onclick="openLectureModal()">+ Upload First Lecture</button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Lecture Modal -->
<div id="gep-lecture-modal" class="gep-modal-overlay">
    <div class="gep-modal-content" style="max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <h2 id="modal-title" style="margin-bottom: 8px; font-size: 24px; font-weight: 900; color: #0f172a;">Upload Lecture</h2>
        <p style="color: #64748b; margin-bottom: 30px; font-weight: 600; font-size: 14px;">Add a new lecture to your video library.</p>
        
        <form method="post">
            <?php wp_nonce_field('gep_save_lecture', 'gep_nonce'); ?>
            <input type="hidden" name="gep_lecture_save" value="1">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Lecture Title</label>
                <input type="text" name="title" required placeholder="e.g. Masterclass: Advanced History" style="width: 100%;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Instructor</label>
                    <input type="text" name="instructor" required placeholder="Instructor Name" style="width: 100%;">
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Duration</label>
                    <input type="text" name="duration" placeholder="e.g. 1h 30m" style="width: 100%;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Category</label>
                    <select name="category_id" id="lecture_category_id" style="width: 100%;" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat) echo '<option value="'.$cat->id.'">'.esc_html($cat->name).'</option>'; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Subcategory</label>
                    <select name="subcategory_id" id="lecture_subcategory_id" style="width: 100%;">
                        <option value="0">Select Subcategory</option>
                        <?php
                        $subcats = $wpdb->get_results("SELECT id, parent_id, name FROM {$wpdb->prefix}gep_categories WHERE parent_id > 0 ORDER BY name ASC");
                        foreach($subcats as $sc) {
                            echo '<option value="'.$sc->id.'" data-parent="'.$sc->parent_id.'">'.esc_html($sc->name).'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Source</label>
                    <select name="video_source" style="width: 100%;">
                        <option value="youtube">YouTube</option>
                        <option value="vimeo">Vimeo</option>
                        <option value="mp4">Direct MP4 URL</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Video URL</label>
                    <input type="text" name="video_url" required placeholder="https://..." style="width: 100%;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Thumbnail URL</label>
                <input type="text" name="thumbnail" placeholder="https://..." style="width: 100%;">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="button button-primary" style="flex: 2; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">Publish Lecture</button>
                <button type="button" class="button" style="flex: 1; height: 55px; border-radius: 14px; font-weight: 800; font-size: 16px; border: 2px solid #e2e8f0;" onclick="document.getElementById('gep-lecture-modal').style.display='none'">Discard</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLectureModal() {
    const modal = document.getElementById('gep-lecture-modal');
    modal.style.display = 'flex';
}

jQuery(document).ready(function($) {
    function filterSubcategories() {
        var parentId = $('#lecture_category_id').val();
        $('#lecture_subcategory_id option').each(function() {
            var p = $(this).data('parent');
            if(p && p != parentId) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
        $('#lecture_subcategory_id').val('0');
    }
    $('#lecture_category_id').change(filterSubcategories);
    // filter on load
    $('#lecture_subcategory_id option').each(function(){
        if($(this).val() != '0') $(this).hide();
    });
});
</script>
