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
                    If you have any questions, encounter any issues, or need assistance with your account, tests, or courses, chat with us on WhatsApp, email us, or use the form.
                </p>
                
                <div class="gep-support-contact-card">
                    <span class="gep-support-contact-label">WhatsApp helpline</span>
                    <span class="gep-support-contact-number">+91 89863 88960</span>
                    <a class="gep-whatsapp-link" href="https://wa.me/918986388960" target="_blank" rel="noopener noreferrer">Chat on WhatsApp <span aria-hidden="true">↗</span></a>
                    <span class="gep-support-contact-hint">Opens WhatsApp to start a chat.</span>
                </div>
                <div class="gep-support-contact-card">
                    <span class="gep-support-contact-label">Email support</span>
                    <a class="gep-support-email" href="mailto:help@gopath.in">help@gopath.in</a>
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
        
        if (btn.prop('disabled')) return;
        if (!$('#support_subject').val().trim() || !$('#support_message').val().trim()) { status.text('Enter a subject and message.').show(); return; }
        btn.prop('disabled', true).text('Sending...');
        status.hide();

        $.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'gep_submit_support_ticket',
                nonce: gep_ajax.nonce,
                subject: $('#support_subject').val(),
                message: $('#support_message').val()
            },
            success: function(res) {
                status.show().text(res && res.data && res.data.message || 'Could not send your message. Please retry.');
                if (res && res.success) {
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
