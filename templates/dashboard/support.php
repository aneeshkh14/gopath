<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-dashboard-view">
    <div class="gep-page-header">
        <h1>Help & Support</h1>
        <p>We're here to help! Get in touch with our support team.</p>
    </div>

    <div class="gep-support-container" style="background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #eef2f6; max-width: 800px; margin: 0 auto;">
        
        <div class="gep-support-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <!-- Left Side: Info -->
            <div>
                <div class="gep-support-icon" style="font-size: 48px; margin-bottom: 20px;">
                    🎧
                </div>
                <h2 style="font-size: 24px; font-weight: 800; color: #1e293b; margin-bottom: 15px;">How can we help you?</h2>
                <p style="font-size: 15px; color: #64748b; line-height: 1.6; margin-bottom: 30px;">
                    If you have any questions, encounter any issues, or need assistance with your account, tests, or courses, please fill out the form.
                </p>
                
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px;">Direct Email</div>
                    <div style="font-size: 16px; font-weight: 800; color: #1e293b;">help@gopath.in</div>
                </div>
                <p style="font-size: 13px; color: #94a3b8; margin: 0;">
                    We typically respond within 24-48 hours during business days.
                </p>
            </div>

            <!-- Right Side: Form -->
            <div>
                <form id="gep-support-form" class="gep-form">
                    <div class="gep-form-group" style="margin-bottom: 20px;">
                        <label for="support_subject" style="display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px;">Subject</label>
                        <input type="text" id="support_subject" required placeholder="What is this regarding?" style="width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: all 0.2s; box-sizing: border-box;">
                    </div>
                    <div class="gep-form-group" style="margin-bottom: 20px;">
                        <label for="support_message" style="display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px;">Message</label>
                        <textarea id="support_message" required rows="5" placeholder="Describe your issue in detail..." style="width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: all 0.2s; box-sizing: border-box; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="gep-btn gep-btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 15px;">Send Message</button>
                    <div id="support-status" role="status" style="margin-top: 15px; font-size: 14px; font-weight: 600; text-align: center; display: none;"></div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#gep-support-form').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        const status = $('#support-status');
        
        btn.prop('disabled', true).text('Sending...');
        status.hide();

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gep_submit_support_ticket',
                nonce: gep_ajax.nonce,
                subject: $('#support_subject').val(),
                message: $('#support_message').val()
            },
            success: function(res) {
                status.show().text(res.data.message);
                if (res.success) {
                    status.css('color', '#10b981');
                    $('#gep-support-form')[0].reset();
                } else {
                    status.css('color', '#ef4444');
                }
            },
            error: function() {
                status.show().text('A server error occurred. Please try again.').css('color', '#ef4444');
            },
            complete: function() {
                btn.prop('disabled', false).text('Send Message');
            }
        });
    });
});
</script>
