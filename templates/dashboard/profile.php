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
    <div class="gep-account-heading"><p class="gep-account-eyebrow">YOUR ACCOUNT</p><h1>Profile &amp; preferences</h1><p>Keep your details up to date and make GoPath work for you.</p></div>
    <header class="gep-account-card gep-account-identity">
        <div class="gep-profile-avatar">
            <?php echo get_avatar( $user->ID, 96 ); ?>
            <input type="file" id="gep-avatar-upload" hidden accept="image/jpeg,image/png,image/gif,image/webp">
            <button type="button" class="gep-avatar-edit" aria-label="Upload new profile photo" title="Change photo" onclick="document.getElementById('gep-avatar-upload').click();"><span class="dashicons dashicons-camera" aria-hidden="true"></span></button>
        </div>
        <div class="gep-account-intro">
            <div class="gep-account-name"><h2><?php echo esc_html( $user->display_name ); ?></h2><span class="gep-account-role"><?php echo user_can( $user, 'manage_options' ) ? 'Administrator' : 'Student'; ?></span></div>
            <p class="gep-account-email"><?php echo esc_html( $user->user_email ); ?></p>
            <?php $enrollment_id = 'GP-' . date( 'y', strtotime( $user->user_registered ) ) . '-' . str_pad( $user->ID, 4, '0', STR_PAD_LEFT ); ?>
            <p class="gep-account-enrollment">Enrollment <code><?php echo esc_html( $enrollment_id ); ?></code></p>
        </div>
        <dl class="gep-account-summary">
            <div><dt>Study streak</dt><dd><?php echo absint( $stats['streak'] ); ?> <small><?php echo (int) $stats['streak'] === 1 ? 'day' : 'days'; ?></small></dd></div>
            <div><dt>Tests completed</dt><dd><?php echo absint( $stats['total_attempts'] ); ?></dd></div>
        </dl>
    </header>

    <div class="gep-account-layout">
        <aside class="gep-account-sidebar" aria-label="Account settings">
            <div class="gep-account-card gep-account-progress">
                <div class="gep-account-progress-heading"><strong>Profile completion</strong><span><?php echo absint( $stats['completion'] ); ?>%</span></div>
                <progress max="100" value="<?php echo absint( $stats['completion'] ); ?>" aria-label="Profile completion"><?php echo absint( $stats['completion'] ); ?>%</progress>
                <p>Add your personal and academic details to complete your profile.</p>
            </div>
            <nav class="gep-profile-nav" role="tablist" aria-label="Profile sections" aria-orientation="vertical">
                <?php foreach ( array( 'personal' => array( 'admin-users', 'Personal details' ), 'academic' => array( 'welcome-learn-more', 'Learning goals' ), 'security' => array( 'lock', 'Password & security' ), 'telemetry' => array( 'chart-bar', 'Learning activity' ) ) as $tab => $item ) : ?>
                <a href="#<?php echo esc_attr( $tab ); ?>" id="profile-tab-<?php echo esc_attr( $tab ); ?>" data-tab="<?php echo esc_attr( $tab ); ?>" role="tab" aria-controls="tab-<?php echo esc_attr( $tab ); ?>" aria-selected="<?php echo $tab === 'personal' ? 'true' : 'false'; ?>" tabindex="<?php echo $tab === 'personal' ? '0' : '-1'; ?>" class="<?php echo $tab === 'personal' ? 'active' : ''; ?>"><span class="dashicons dashicons-<?php echo esc_attr( $item[0] ); ?>" aria-hidden="true"></span><?php echo esc_html( $item[1] ); ?></a>
                <?php endforeach; ?>
            </nav>
            <a href="<?php echo esc_url( wp_logout_url( gep_get_url( 'login' ) ) ); ?>" class="gep-account-logout"><span class="dashicons dashicons-exit" aria-hidden="true"></span>Sign out</a>
        </aside>

        <div class="gep-account-card gep-account-main">
            <form id="gep-profile-update-form" class="gep-profile-form">
                <?php wp_nonce_field( 'gep_profile_update', 'gep_profile_nonce' ); ?>
                <?php if ( isset( $_GET['uid'] ) ) : ?><input type="hidden" name="uid" value="<?php echo absint( $_GET['uid'] ); ?>"><?php endif; ?>
                <section id="tab-personal" class="gep-form-section gep-account-panel active" role="tabpanel" aria-labelledby="profile-tab-personal">
                    <div class="gep-account-section-heading"><h2>Personal details</h2><p>Your name, contact information and language preferences.</p></div>
                    <div class="gep-account-fields">
                        <div class="gep-form-group"><label for="gep-profile-field-0">First name <span class="gep-field-required" aria-hidden="true">*</span></label><input id="gep-profile-field-0" name="first_name" class="gep-input" type="text" autocomplete="given-name" value="<?php echo esc_attr( $first_name ); ?>" required></div>
                        <div class="gep-form-group"><label for="gep-profile-field-1">Last name</label><input id="gep-profile-field-1" name="last_name" class="gep-input" type="text" autocomplete="family-name" value="<?php echo esc_attr( $last_name ); ?>"></div>
                        <div class="gep-form-group"><label for="gep-profile-field-2">Phone number</label><input id="gep-profile-field-2" type="tel" name="phone" autocomplete="tel" class="gep-input" value="<?php echo esc_attr( $phone ); ?>" placeholder="+91 00000 00000"></div>
                        <div class="gep-form-group"><label for="gep-profile-field-3">Email address <span class="gep-field-hint">Read only</span></label><input id="gep-profile-field-3" type="email" value="<?php echo esc_attr( $user->user_email ); ?>" readonly class="gep-input" aria-describedby="gep-email-help"><small id="gep-email-help" class="gep-info-text">Contact support if you need to change your email.</small></div>
                        <div class="gep-form-group gep-account-field-wide"><label for="gep-profile-field-4">About you <span class="gep-field-hint">Optional</span></label><textarea id="gep-profile-field-4" name="bio" class="gep-input" rows="3" placeholder="A little about your background or what you’re working towards…"><?php echo esc_textarea( $bio ); ?></textarea></div>
                        <div class="gep-form-group gep-account-field-wide"><label for="gep-profile-language">Preferred language</label><?php $pref_lang = get_user_meta( $user->ID, 'gep_preferred_lang', true ) ?: 'en'; ?><select id="gep-profile-language" name="preferred_lang" class="gep-input" aria-describedby="gep-language-help"><option value="en" <?php selected( $pref_lang, 'en' ); ?>>English</option><option value="hi" <?php selected( $pref_lang, 'hi' ); ?>>Hindi (हिन्दी)</option></select><small id="gep-language-help" class="gep-info-text">Used by default for exams and instructions.</small></div>
                    </div>
                </section>
                <section id="tab-academic" class="gep-form-section gep-account-panel" role="tabpanel" aria-labelledby="profile-tab-academic">
                    <div class="gep-account-section-heading"><h2>Learning goals</h2><p>Choose your subjects and tell us about your academic background.</p></div>
                    <div class="gep-account-fields">
                        <fieldset class="gep-account-field-wide gep-account-goals"><legend>Subjects you’re preparing for</legend>
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
                            <div class="gep-account-goal-grid">
                                <?php foreach ( $categories as $cat ) : ?><label class="gep-account-goal"><input type="checkbox" name="student_goals[]" value="<?php echo esc_attr( $cat->id ); ?>" <?php checked( in_array( $cat->id, $student_goals ) ); ?>><span><?php echo esc_html( $cat->name ); ?></span></label><?php endforeach; ?>
                                <?php if ( ! $categories ) : ?><p class="gep-info-text">Subjects will appear here when courses or tests are available.</p><?php endif; ?>
                            </div>
                            <p class="gep-info-text">Your dashboard will show content from these subjects. Leave all unselected to see everything.</p>
                        </fieldset>
                        <div class="gep-form-group gep-account-field-wide"><label for="gep-profile-field-5">Highest qualification</label><input id="gep-profile-field-5" type="text" name="qualification" class="gep-input" value="<?php echo esc_attr( $qualification ); ?>" placeholder="For example, B.Tech or MBA"></div>
                    </div>
                </section>
                <section id="tab-security" class="gep-form-section gep-account-panel" role="tabpanel" aria-labelledby="profile-tab-security">
                    <div class="gep-account-section-heading"><h2>Password &amp; security</h2><p>Choose a unique password to keep your account secure.</p></div>
                    <div class="gep-form-group"><label for="gep-new-password">New password</label><div class="gep-account-password"><input type="password" name="new_password" id="gep-new-password" minlength="8" autocomplete="new-password" class="gep-input" aria-describedby="gep-password-help" placeholder="At least 8 characters"><button type="button" class="gep-password-toggle" aria-label="Show password" aria-pressed="false"><i class="dashicons dashicons-visibility" aria-hidden="true"></i></button></div><small id="gep-password-help" class="gep-info-text">Leave this blank to keep your current password.</small></div>
                </section>
                <section id="tab-telemetry" class="gep-form-section gep-account-panel" role="tabpanel" aria-labelledby="profile-tab-telemetry">
                    <div class="gep-account-section-heading"><h2>Learning activity</h2><p>Review your progress and accuracy by subject.</p></div>
                    <div class="gep-account-activity">
                        <div class="gep-account-stat"><span>Platform percentile</span><strong><?php echo esc_html( $stats['percentile'] ); ?><small>%</small></strong><p><?php echo $stats['total_attempts'] ? 'Based on completed tests across the platform.' : 'Complete a test to start tracking your progress.'; ?></p></div>
                        <div class="gep-account-stat"><span>Overall rank</span><strong><?php echo is_numeric( $stats['rank'] ) ? '#' . absint( $stats['rank'] ) : '—'; ?></strong><p><?php echo absint( $stats['total_attempts'] ); ?> completed tests</p></div>
                    </div>
                    <h3 class="gep-account-subheading">Subject accuracy</h3>
                    <?php if ( ! empty( $stats['topic_performance'] ) ) : ?>
                    <div class="gep-account-topics"><?php foreach ( $stats['topic_performance'] as $topic ) : ?><div><div class="gep-account-topic-label"><span><?php echo esc_html( $topic['topic'] ); ?></span><strong><?php echo esc_html( $topic['accuracy'] ); ?>%</strong></div><progress max="100" value="<?php echo esc_attr( $topic['accuracy'] ); ?>" aria-label="<?php echo esc_attr( $topic['topic'] . ' accuracy' ); ?>"><?php echo esc_html( $topic['accuracy'] ); ?>%</progress></div><?php endforeach; ?></div>
                    <?php else : ?><p class="gep-account-empty">Complete an exam to see your accuracy for each subject.</p><?php endif; ?>
                </section>
                <div class="gep-account-actions"><p class="gep-info-text">Changes apply to your GoPath account.</p><button type="submit" class="gep-btn gep-btn-primary"><span class="gep-btn-text">Save changes</span><span class="gep-spinner" style="display:none"></span></button><div id="gep-profile-msg" role="status" style="display:none"></div></div>
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
        $('.gep-profile-nav a').removeClass('active').attr({'aria-selected':'false',tabindex:'-1'});
        $(this).addClass('active').attr({'aria-selected':'true',tabindex:'0'});
        
        // Update sections
        $('.gep-form-section').removeClass('active');
        $('#tab-' + tabId).addClass('active');
        $('.gep-account-actions').toggle(tabId !== 'telemetry');
    });

    $('.gep-profile-nav a').on('keydown', function(e) {
        var tabs = $('.gep-profile-nav a'), index = tabs.index(this), next;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (index + 1) % tabs.length;
        else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (index - 1 + tabs.length) % tabs.length;
        else if (e.key === 'Home') next = 0;
        else if (e.key === 'End') next = tabs.length - 1;
        else return;
        e.preventDefault(); tabs.eq(next).trigger('click').trigger('focus');
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
        btnText.text('Saving…');
        spinner.show();
        msgBox.hide();

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: form.serialize() + '&action=gep_update_profile',
            success: function(response) {
                if (response && response.success) {
                    msgBox.html('<div class="gep-alert gep-alert-success"><span style="font-size:20px;">✅</span> Profile updated successfully! Refreshing...</div>').fadeIn();
                    $('html, body').animate({ scrollTop: 0 }, 500);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    msgBox.empty().append($('<div class="gep-alert gep-alert-danger">').text(response && typeof response.data === 'string' ? response.data : response && response.data && response.data.message || 'Failed to update profile.')).fadeIn();
                    btn.prop('disabled', false);
                    btnText.text('Save changes');
                }
            },
            error: function() {
                msgBox.html('<div class="gep-alert gep-alert-danger">A server error occurred. Please try again.</div>').fadeIn();
                btn.prop('disabled', false);
                btnText.text('Save changes');
            },
            complete: function() {
                spinner.hide();
            }
        });
    });

    // Avatar feedback stays next to its control and supports same-file retries.
    var avatarBusy = false;
    function avatarStatus(message, failed) {
        var $status = $('#gep-avatar-status');
        if (!$status.length) $status = $('<p id="gep-avatar-status" role="status"></p>').insertAfter('#gep-avatar-upload');
        $status.text(message).attr('role', failed ? 'alert' : 'status');
    }
    // Avatar Upload Engine
    $('#gep-avatar-upload').on('change', function() {
        if (avatarBusy) return;
        var file_data = $(this).prop('files')[0];
        if (!file_data) return;

        avatarBusy = true;
        $(this).prop('disabled', true);
        avatarStatus('Uploading photo…', false);
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
                if (response && response.success && response.data && response.data.image_url) {
                    $('.gep-profile-avatar img').attr('src', response.data.image_url);
                    avatarStatus('Photo updated.', false);
                } else {
                    avatarStatus(response && typeof response.data === 'string' ? response.data : response && response.data && response.data.message || 'Failed to upload photo. Choose the file again to retry.', true);
                }
            },
            error: function() { avatarStatus('Could not upload your photo. Check your connection and choose the file again to retry.', true); },
            complete: function() {
                avatarBusy = false;
                $('#gep-avatar-upload').prop('disabled', false).val('');
                $('.gep-profile-avatar').css('opacity', '1');
            }
        });
    });
});
</script>
