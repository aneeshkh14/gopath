<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

// Ensure url_type column exists
$table_live = $wpdb->prefix . 'gep_live_classes';
$row = $wpdb->get_results( "SHOW COLUMNS FROM {$table_live} LIKE 'url_type'" );
if ( empty( $row ) ) {
    $wpdb->query( "ALTER TABLE {$table_live} ADD COLUMN url_type varchar(50) DEFAULT 'external'" );
}

$live_logic = new GEP_Admin_Live();
$view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'streams';
$courses = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}gep_courses ORDER BY title ASC" );
$streams = $live_logic->get_live_classes('scheduled');
$recordings = $live_logic->get_live_classes('recorded');
?>

<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0; font-size: 26px; letter-spacing: -0.8px;">Broadcast Intelligence Hub</h1>
            <p style="color: var(--admin-muted); font-weight: 600; font-size: 14px;">Orchestrate real-time pedagogy and maintain high-fidelity video archives.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <button class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 30px; font-weight: 900; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);" onclick="openLiveModal('scheduled')">+ Schedule Stream</button>
            <button class="button" style="height: 50px; border-radius: 14px; padding: 0 30px; font-weight: 800; font-size: 14px; border: 2px solid #e2e8f0; background: #fff;" onclick="openLiveModal('recorded')">🎞️ Upload Recording</button>
        </div>
    </div>

    <div class="gep-admin-tabs" style="display: flex; gap: 10px; margin-bottom: 30px; background: #f1f5f9; padding: 6px; border-radius: 16px; width: fit-content;">
        <a href="<?php echo admin_url('admin.php?page=gep-live-classes&view=streams'); ?>" class="gep-tab <?php echo $view === 'streams' ? 'active' : ''; ?>" style="padding: 10px 25px; border-radius: 12px; font-weight: 800; font-size: 13px; text-decoration: none; color: <?php echo $view === 'streams' ? '#fff' : '#64748b'; ?>; background: <?php echo $view === 'streams' ? 'var(--admin-primary)' : 'transparent'; ?>;">Upcoming Streams</a>
        <a href="<?php echo admin_url('admin.php?page=gep-live-classes&view=recordings'); ?>" class="gep-tab <?php echo $view === 'recordings' ? 'active' : ''; ?>" style="padding: 10px 25px; border-radius: 12px; font-weight: 800; font-size: 13px; text-decoration: none; color: <?php echo $view === 'recordings' ? '#fff' : '#64748b'; ?>; background: <?php echo $view === 'recordings' ? 'var(--admin-primary)' : 'transparent'; ?>;">Recorded Archives</a>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 28px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; box-shadow: var(--admin-shadow-lg);">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th style="padding-left: 30px; width: 35%;">Session Intelligence</th>
                    <th style="width: 200px;">Academic Domain</th>
                    <th style="width: 150px;">Status</th>
                    <th style="width: 200px;"><?php echo $view === 'streams' ? 'Scheduled Broadcast' : 'Recording Source'; ?></th>
                    <th style="padding-right: 30px; text-align: right; width: 180px;">Management</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $active_list = ($view === 'streams') ? $streams : $recordings;
                if ($active_list): foreach($active_list as $item): ?>
                <tr>
                    <td style="padding-left: 30px;">
                        <div style="font-weight: 800; color: #1e293b; font-size: 16px;">
                            <?php echo esc_html($item->title); ?>
                            <?php if (isset($item->url_type) && $item->url_type === 'native'): ?>
                                <span style="background: #3b82f6; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">NATIVE</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 4px;">Instructor: <?php echo esc_html($item->instructor); ?></div>
                    </td>
                    <td>
                        <div style="background: #f8fafc; padding: 6px 12px; border-radius: 8px; border: 1px solid #f1f5f9; display: inline-block;">
                            <span style="font-weight: 700; color: #475569; font-size: 12px;">
                                <?php 
                                if($item->course_id) {
                                    $c_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}gep_courses WHERE id = %d", $item->course_id));
                                    echo esc_html($c_title);
                                } else echo 'General Stream';
                                ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: <?php echo $item->status === 'scheduled' ? '#f0f9ff; color: #0369a1; border: 1px solid #e0f2fe;' : '#f0fdf4; color: #166534; border: 1px solid #dcfce7;'; ?>; font-weight: 900; font-size: 10px;">
                            <?php echo strtoupper($item->status); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($view === 'streams'): ?>
                            <div style="font-weight: 700; color: #1e293b; font-size: 14px;">📅 <?php echo date('M d, H:i', strtotime($item->scheduled_at)); ?></div>
                        <?php else: ?>
                            <div style="font-size: 12px; color: #4338ca; font-weight: 800; letter-spacing: 0.5px;"><?php echo $item->recording_url ? 'LINKED_RESOURCE' : 'PENDING_UPLOAD'; ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding-right: 30px; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <?php if($item->status === 'scheduled'): ?>
                                <a href="<?php echo esc_url($item->meeting_url); ?>" target="_blank" class="button button-primary" style="border-radius: 10px; font-weight: 800; font-size: 11px;">🚀 Launch</a>
                            <?php else: ?>
                                <a href="<?php echo esc_url($item->recording_url); ?>" target="_blank" class="button" style="border-radius: 10px; font-weight: 800; font-size: 11px; background: #f1f5f9; border: 1px solid #e2e8f0;">👁️ View</a>
                            <?php endif; ?>
                            <a href="<?php echo admin_url('admin.php?page=gep-live-classes&action=delete_live&id='.$item->id.'&nonce='.wp_create_nonce('gep_delete_live')); ?>" class="button" style="color: #ef4444; background: #fff5f5; border: 1px solid #fee2e2; border-radius: 10px; font-weight: 800; font-size: 11px;" onclick="return confirm('Archive session?')">Archive</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" class="empty-state" style="padding: 120px 50px;">
                        <div style="background: #fff; width: 100px; height: 100px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;">📡</div>
                        <h3 style="font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 12px;">No sessions found</h3>
                        <p style="color: #64748b; font-weight: 600; max-width: 450px; margin: 0 auto 40px;">Ready to broadcast? Schedule your first live stream or archive a past recording to populate your studio.</p>
                        <button class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 35px; font-weight: 900; font-size: 14px;" onclick="openLiveModal('scheduled')">+ Schedule First Stream</button>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Live/Recording Modal -->
<div id="gep-live-modal" class="gep-modal-overlay">
    <div class="gep-modal-content" style="max-width: 600px;">
        <h2 id="modal-title" style="margin-bottom: 8px; font-size: 24px; font-weight: 900; color: #0f172a;">Schedule Live Stream</h2>
        <p style="color: #64748b; margin-bottom: 30px; font-weight: 600; font-size: 14px;">Connect with your audience in high-fidelity.</p>
        
        <form method="post">
            <?php wp_nonce_field('gep_save_live', 'gep_nonce'); ?>
            <input type="hidden" name="gep_live_save" value="1">
            <input type="hidden" name="status" id="form-status" value="scheduled">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Session Title</label>
                <input type="text" name="title" required placeholder="e.g. Masterclass: Advanced History" style="width: 100%;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Lead Instructor</label>
                    <input type="text" name="instructor" required placeholder="Instructor Name" style="width: 100%;">
                </div>
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Academic Course</label>
                    <select name="course_id" style="width: 100%;">
                        <option value="0">General Audience</option>
                        <?php foreach($courses as $c) echo '<option value="'.$c->id.'">'.esc_html($c->title).'</option>'; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Category</label>
                    <select name="category_id" id="gep_live_category_id" style="width: 100%;">
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
                    <select name="subcategory_id" id="gep_live_subcategory_id" style="width: 100%;">
                        <option value="0">Select Subcategory</option>
                    </select>
                </div>
            </div>

            <div id="stream-fields">
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Delivery Mode</label>
                        <select name="url_type" style="width: 100%;">
                            <option value="external">External Link (Zoom/Meet)</option>
                            <option value="native">In-App Native (YT Live/Embed)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Broadcast URL / Embed Src</label>
                        <input type="text" name="meeting_url" placeholder="https://..." style="width: 100%;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Scheduled At</label>
                        <input type="datetime-local" name="scheduled_at" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Duration (Min)</label>
                        <input type="number" name="duration" value="60" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div id="recording-fields" style="display: none; margin-bottom: 30px;">
                <label style="display: block; font-weight: 800; font-size: 11px; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">Recording URL (Archived Asset)</label>
                <input type="text" name="recording_url" placeholder="https://vimeo.com/..." style="width: 100%;">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" class="button button-primary" style="flex: 2; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">Commit Session</button>
                <button type="button" class="button" style="flex: 1; height: 55px; border-radius: 14px; font-weight: 800; font-size: 16px; border: 2px solid #e2e8f0;" onclick="document.getElementById('gep-live-modal').style.display='none'">Discard</button>
            </div>
        </form>
    </div>
</div>

<script>
function openLiveModal(status) {
    const modal = document.getElementById('gep-live-modal');
    const title = document.getElementById('modal-title');
    const statusField = document.getElementById('form-status');
    const streamFields = document.getElementById('stream-fields');
    const recordingFields = document.getElementById('recording-fields');

    statusField.value = status;
    if (status === 'recorded') {
        title.innerText = 'Upload Recorded Class';
        streamFields.style.display = 'none';
        recordingFields.style.display = 'block';
    } else {
        title.innerText = 'Schedule Live Stream';
        streamFields.style.display = 'block';
        recordingFields.style.display = 'none';
    }
    modal.style.display = 'flex';
}

jQuery(document).ready(function($) {
    $('#gep_live_category_id').on('change', function() {
        var catId = $(this).val();
        var subDropdown = $('#gep_live_subcategory_id');
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
