<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
$user = get_userdata( $user_id );
$dashboard = new GEP_Dashboard();
$stats = $dashboard->get_dashboard_stats($user->ID);

// Get extra meta
$phone = get_user_meta($user->ID, 'gep_phone', true);
$qualification = get_user_meta($user->ID, 'gep_qualification', true);
$target_exam = get_user_meta($user->ID, 'gep_target_exam', true);
$bio = get_user_meta($user->ID, 'description', true);

$first_name = $user->first_name;
$last_name = $user->last_name;
if ( empty( $first_name ) && ! empty( $user->display_name ) ) {
    $parts = explode( ' ', $user->display_name, 2 );
    $first_name = $parts[0];
    $last_name = isset( $parts[1] ) ? $parts[1] : '';
}
?>

<div class="gep-profile-wrapper">
    <div class="gep-profile-header">
        <div class="gep-profile-avatar">
            <?php echo get_avatar($user->ID, 120); ?>
            <input type="file" id="gep-avatar-upload" style="display:none;" accept="image/*">
            <button type="button" aria-label="Upload new profile photo" class="gep-avatar-edit" title="Upload New Photo" onclick="document.getElementById('gep-avatar-upload').click();">
                <i class="dashicons dashicons-camera"></i>
            </button>
        </div>
        <div class="gep-profile-intro">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h1 style="margin: 0; color: #fff;"><?php echo esc_html($user->display_name); ?></h1>
                <?php if (current_user_can('manage_options')): ?>
                    <span class="gep-tier-badge sovereign">SOVEREIGN ADMIN</span>
                <?php else: ?>
                    <span class="gep-tier-badge scholar">ELITE SCHOLAR</span>
                <?php endif; ?>
            </div>
            <p style="margin-top: 5px; opacity: 0.8; color: #94a3b8;"><?php echo esc_html($user->user_email); ?></p>
            <?php
                $reg_year = date('y', strtotime($user->user_registered));
                $enrollment_id = 'GP-' . $reg_year . '-' . str_pad($user->ID, 4, '0', STR_PAD_LEFT);
            ?>
            <div class="gep-neural-id" title="Your formal enrollment identity">
                <span class="icon">🎓</span> ENROLLMENT ID: <code><?php echo esc_html($enrollment_id); ?></code>
            </div>
            <div class="gep-profile-badges">
                <div class="gep-badge-item streak">
                    <span class="icon">🔥</span>
                    <span class="val"><?php echo $stats['streak']; ?> Day Streak</span>
                </div>
                <div class="gep-badge-item rank">
                    <span class="icon">🏆</span>
                    <span class="val">Global Rank: <?php echo is_numeric($stats['rank']) ? '#' . $stats['rank'] : 'Unranked'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="gep-profile-content">
        <div class="gep-profile-sidebar">
            <div class="gep-profile-completeness">
                <div class="label-row">
                    <span>Profile Completion</span>
                    <span class="val"><?php echo $stats['completion']; ?>%</span>
                </div>
                <div class="gep-progress-bar">
                    <div class="gep-progress-fill" style="width: <?php echo $stats['completion']; ?>%;"></div>
                </div>
                <p class="completeness-tip" style="color: #94a3b8;">Complete your academic details to reach 100%!</p>
            </div>
            <nav class="gep-profile-nav">
                <a href="#personal" class="active" data-tab="personal">
                    <span class="icon">👤</span> Personal Info
                </a>
                <a href="#academic" data-tab="academic">
                    <span class="icon">🎓</span> Academic Details
                </a>
                <a href="#security" data-tab="security">
                    <span class="icon">🛡️</span> Security & Password
                </a>
                <a href="#telemetry" data-tab="telemetry">
                    <span class="icon">📈</span> Advanced Telemetry
                </a>
            </nav>

            <div class="gep-profile-sidebar-footer" style="margin-top: auto; padding-top: 30px;">
                <a href="<?php echo wp_logout_url(gep_get_url('login')); ?>" class="gep-btn-logout-alt">
                    <span class="dashicons dashicons-logout"></span> Logout Account
                </a>
            </div>
        </div>

        <div class="gep-profile-main">
            <form id="gep-profile-update-form" class="gep-profile-form">
                <?php wp_nonce_field('gep_profile_update', 'gep_profile_nonce'); ?>
                <?php if (isset($_GET['uid'])): ?>
                    <input type="hidden" name="uid" value="<?php echo absint($_GET['uid']); ?>">
                <?php endif; ?>
                
                <!-- Personal Section -->
                <section id="tab-personal" class="gep-form-section active">
                    <div class="gep-section-header-compact">
                        <h3 style="color: #fff;">Personal Information</h3>
                        <p style="color: #94a3b8;">Manage your public identity and contact details.</p>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-form-group">
                            <label for="gep-profile-field-0" style="color: #fff;">First Name</label>
                            <input id="gep-profile-field-0" type="text" name="first_name" class="gep-input" value="<?php echo esc_attr($first_name); ?>" required placeholder="e.g. Ugant">
                        </div>
                        <div class="gep-form-group">
                            <label for="gep-profile-field-1" style="color: #fff;">Last Name</label>
                            <input id="gep-profile-field-1" type="text" name="last_name" class="gep-input" value="<?php echo esc_attr($last_name); ?>" required placeholder="e.g. Kumar">
                        </div>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-form-group">
                            <label for="gep-profile-field-2">Phone Number</label>
                            <input id="gep-profile-field-2" type="tel" name="phone" class="gep-input" value="<?php echo esc_attr($phone); ?>" placeholder="+91 00000 00000">
                        </div>
                        <div class="gep-form-group">
                            <label for="gep-profile-field-3">Email Address</label>
                            <div class="gep-input-readonly-wrapper">
                                <input id="gep-profile-field-3" type="email" value="<?php echo esc_attr($user->user_email); ?>" disabled class="gep-input gep-input-readonly">
                                <span class="gep-lock-icon">🔒</span>
                            </div>
                        </div>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-form-group">
                            <label for="gep-profile-field-4">Bio / Description</label>
                            <textarea id="gep-profile-field-4" name="bio" class="gep-input" rows="3" placeholder="Tell us a bit about yourself..."><?php echo esc_textarea($bio); ?></textarea>
                        </div>
                        <div class="gep-form-group">
                            <label>Preferred Language</label>
                            <?php $pref_lang = get_user_meta($user->ID, 'gep_preferred_lang', true) ?: 'en'; ?>
                            <select name="preferred_lang" class="gep-input">
                                <option value="en" <?php selected($pref_lang, 'en'); ?>>English</option>
                                <option value="hi" <?php selected($pref_lang, 'hi'); ?>>Hindi (हिन्दी)</option>
                            </select>
                            <small class="gep-info-text">This sets the default language for exams and instructions.</small>
                        </div>
                    </div>
                </section>

                <!-- Academic Section -->
                <section id="tab-academic" class="gep-form-section">
                    <div class="gep-section-header-compact">
                        <h3>Academic Details</h3>
                        <p>Help us personalize your learning tracks.</p>
                    </div>
                    <div class="gep-grid-2">
                        <div class="gep-form-group" style="grid-column: 1 / -1;">
                            <label>Exam Goals (Segments)</label>
                            <?php 
                                global $wpdb;
                                $active_cat_ids = $wpdb->get_col( "
                                    SELECT DISTINCT category_id FROM {$wpdb->prefix}gep_tests WHERE status = 'publish' AND category_id > 0
                                    UNION
                                    SELECT DISTINCT category_id FROM {$wpdb->prefix}gep_courses WHERE status = 'publish' AND category_id > 0
                                    UNION
                                    SELECT DISTINCT category_id FROM {$wpdb->prefix}gep_lectures WHERE status = 'publish' AND category_id > 0
                                 " );
                                if ( ! empty( $active_cat_ids ) ) {
                                    $categories = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE parent_id = 0 AND id IN (" . implode( ',', array_map( 'intval', $active_cat_ids ) ) . ") ORDER BY name ASC" );
                                } else {
                                    $categories = array();
                                }
                                $student_goals = get_user_meta($user->ID, 'gep_student_goals', true);
                                if ( !is_array($student_goals) ) $student_goals = array();
                            ?>
                            <div class="gep-goals-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                                <?php foreach ( $categories as $cat ) : ?>
                                <label class="gep-goal-label" style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; cursor: pointer; transition: all 0.2s;">
                                    <input type="checkbox" name="student_goals[]" value="<?php echo esc_attr($cat->id); ?>" <?php checked(in_array($cat->id, $student_goals)); ?> style="width: 18px; height: 18px; accent-color: #6366f1;">
                                    <span style="color: #fff; font-weight: 600; font-size: 14px;"><?php echo esc_html($cat->name); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="gep-info-text" style="display: block; margin-top: 10px; color: #94a3b8;">Select your target segments to personalize your dashboard. Only content from these segments will be shown.</small>
                        </div>
                        <div class="gep-form-group">
                            <label for="gep-profile-field-5">Highest Qualification</label>
                            <input id="gep-profile-field-5" type="text" name="qualification" class="gep-input" value="<?php echo esc_attr($qualification); ?>" placeholder="e.g. B.Tech, MBA, PhD">
                        </div>
                    </div>
                </section>

                <!-- Security Section -->
                <section id="tab-security" class="gep-form-section">
                    <div class="gep-section-header-compact">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h3>Security & Password</h3>
                                <p>Protect your account with a strong password.</p>
                            </div>
                            <div class="gep-security-shield active">
                                <span class="shield-icon">🛡️</span>
                                <span class="shield-text">SHIELD ACTIVE</span>
                            </div>
                        </div>
                    </div>
                    <div class="gep-form-group" style="max-width: 400px; position: relative;">
                        <label for="gep-new-password">New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="gep-new-password" minlength="8" autocomplete="new-password" class="gep-input" placeholder="Min. 8 characters">
                            <button type="button" aria-label="Show password" aria-pressed="false" class="gep-password-toggle" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.6;"><i class="dashicons dashicons-visibility"></i></button>
                        </div>
                        <small class="gep-info-text">Leave blank to keep your current password.</small>
                    </div>
                </section>

                <!-- Telemetry Section -->
                <section id="tab-telemetry" class="gep-form-section">
                    <div class="gep-section-header-compact">
                        <h3>Advanced Telemetry</h3>
                        <p>Analyze your cohort percentile rank and topic accuracy performance.</p>
                    </div>
                    <div class="gep-advanced-telemetry-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <!-- Percentile Gauge -->
                        <div class="gep-glass" style="padding: 30px; border-radius: 24px; text-align: center; border: 1px solid rgba(255,255,255,0.08); background: linear-gradient(145deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9)); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);">
                            <div style="font-size: 11px; font-weight: 800; color: #94a3b8; letter-spacing: 1.5px; margin-bottom: 25px;">GLOBAL PERCENTILE</div>
                            <div style="position: relative; width: 160px; height: 160px; margin: 0 auto; border-radius: 50%; background: conic-gradient(#0ea5e9 <?php echo $stats['percentile']; ?>%, #1e293b <?php echo $stats['percentile']; ?>%); box-shadow: 0 0 20px rgba(14, 165, 233, 0.2);">
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 140px; height: 140px; background: #020617; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 4px 10px rgba(0,0,0,0.5);">
                                    <span style="font-size: 42px; font-weight: 900; color: #fff; line-height: 1; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(14,165,233,0.3);"><?php echo $stats['percentile']; ?></span>
                                    <span style="font-size: 14px; color: #38bdf8; font-weight: 800; letter-spacing: 0.5px;">%ile</span>
                                </div>
                            </div>
                            <p style="margin: 25px 0 0; font-size: 13px; color: #94a3b8; line-height: 1.6;">You are ahead of <strong style="color: #fff;"><?php echo $stats['percentile']; ?>%</strong> of candidates across the platform.</p>
                        </div>
                        <!-- Topic Radar -->
                        <div class="gep-glass" style="padding: 30px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); background: linear-gradient(145deg, rgba(15, 23, 42, 0.8), rgba(2, 6, 23, 0.9)); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);">
                            <div style="font-size: 11px; font-weight: 800; color: #94a3b8; letter-spacing: 1.5px; margin-bottom: 25px;">TOPIC MASTERY (ACCURACY)</div>
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <?php if ( ! empty($stats['topic_performance']) ) : ?>
                                    <?php foreach($stats['topic_performance'] as $topic) : ?>
                                    <div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; font-weight: 600;">
                                            <span style="color: #fff;"><?php echo esc_html($topic['topic']); ?></span>
                                            <span style="color: <?php echo $topic['accuracy'] > 75 ? '#10b981' : ($topic['accuracy'] > 50 ? '#eab308' : '#ef4444'); ?>;"><?php echo $topic['accuracy']; ?>%</span>
                                        </div>
                                        <div style="height: 8px; background: #1e293b; border-radius: 4px; overflow: hidden;">
                                            <div style="height: 100%; width: <?php echo $topic['accuracy']; ?>%; background: <?php echo $topic['accuracy'] > 75 ? 'linear-gradient(90deg, #059669, #10b981)' : ($topic['accuracy'] > 50 ? 'linear-gradient(90deg, #ca8a04, #eab308)' : 'linear-gradient(90deg, #dc2626, #ef4444)'); ?>; border-radius: 4px;"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div style="text-align: center; color: #94a3b8; font-size: 14px; padding: 40px 0;">No topic performance data available. Complete an exam to view analysis.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="gep-form-actions">
                    <button type="submit" class="gep-btn gep-btn-primary gep-btn-lg">
                        <span class="gep-btn-text">Save All Changes</span>
                        <span class="gep-spinner" style="display:none;"></span>
                    </button>
                    <div id="gep-profile-msg" role="status" style="margin-top: 15px; display: none;"></div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Password Toggle
    $('.gep-password-toggle').on('click', function() {
        var input = $('#gep-new-password');
        var icon = $(this).find('i');
        $(this).attr('aria-pressed', String(input.attr('type') === 'password')).attr('aria-label', input.attr('type') === 'password' ? 'Hide password' : 'Show password');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            input.attr('type', 'password');
            icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Tab Switching Logic
    $('.gep-profile-nav a').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        
        // Update nav
        $('.gep-profile-nav a').removeClass('active');
        $(this).addClass('active');
        
        // Update sections
        $('.gep-form-section').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });

    document.getElementById('gep-profile-update-form').addEventListener('invalid', function(e) {
        var section = e.target.closest('.gep-form-section');
        if (section) $('.gep-profile-nav a[data-tab="' + section.id.replace('tab-', '') + '"]').trigger('click');
    }, true);
    // Form Submission
    $('#gep-profile-update-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var btnText = btn.find('.gep-btn-text');
        var spinner = btn.find('.gep-spinner');
        var msgBox = $('#gep-profile-msg');

        if (btn.prop('disabled')) return;
        btn.prop('disabled', true);
        btnText.text('Saving Changes...');
        spinner.show();
        msgBox.hide();

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: form.serialize() + '&action=gep_update_profile',
            success: function(response) {
                if (response.success) {
                    msgBox.html('<div class="gep-alert gep-alert-success"><span style="font-size:20px;">✅</span> Profile updated successfully! Refreshing...</div>').fadeIn();
                    $('html, body').animate({ scrollTop: 0 }, 500);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    msgBox.empty().append($('<div class="gep-alert gep-alert-danger">').text(typeof response.data === 'string' ? response.data : response.data && response.data.message || 'Failed to update profile.')).fadeIn();
                    btn.prop('disabled', false);
                    btnText.text('Save All Changes');
                }
            },
            error: function() {
                msgBox.html('<div class="gep-alert gep-alert-danger">A server error occurred. Please try again.</div>').fadeIn();
                btn.prop('disabled', false);
                btnText.text('Save All Changes');
            },
            complete: function() {
                spinner.hide();
            }
        });
    });

    // Avatar Upload Engine
    $('#gep-avatar-upload').on('change', function() {
        var file_data = $(this).prop('files')[0];
        if (!file_data) return;

        var form_data = new FormData();
        form_data.append('avatar', file_data);
        form_data.append('action', 'gep_update_avatar');
        form_data.append('gep_profile_nonce', $('#gep_profile_nonce').val());
        
        // Preserve administrative context
        var uid = $('input[name="uid"]').val();
        if (uid) form_data.append('uid', uid);

        $('.gep-profile-avatar').css('opacity', '0.5');

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: form_data,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    $('.gep-profile-avatar img').attr('src', response.data.image_url);
                } else {
                    alert(typeof response.data === 'string' ? response.data : response.data && response.data.message || 'Failed to upload photo.');
                }
            },
            error: function() { alert('Could not upload your photo. Please check your connection and try again.'); },
            complete: function() {
                $('.gep-profile-avatar').css('opacity', '1');
            }
        });
    });
});
</script>
