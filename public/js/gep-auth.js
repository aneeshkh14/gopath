jQuery(function($) {
    'use strict';
    var $form = $('#gep-otp-form'), $code = $('#gep_otp_code');
    var $btn = $('#gep-verify-otp-btn'), $error = $('#gep-otp-error');
    var verifying = false;
    $code.on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        this.removeAttribute('aria-invalid');
        $error.hide();
    });
    $form.on('submit', function(e) {
        e.preventDefault();
        if (verifying) return;
        if (!/^\d{6}$/.test($code.val())) {
            $error.text('Enter the 6-digit code from your email.').show();
            $code.attr('aria-invalid', 'true').trigger('focus');
            return;
        }
        verifying = true;
        $btn.prop('disabled', true).text('Verifying…');
        $error.hide();
        $.ajax({
            url: GEP_Auth.ajaxurl,
            method: 'POST',
            timeout: 20000,
            data: { action: 'gep_verify_otp', otp: $code.val(), user_id: $('#gep_otp_uid').val(), nonce: $('#gep_nonce').val() },
            success: function(response) {
                if (response && response.success && response.data.redirect) {
                    try { sessionStorage.removeItem('gep_current_lang'); } catch (e) {}
                    window.location.href = response.data.redirect;
                } else {
                    $error.text(response && response.data && response.data.message || 'The code could not be verified. Check it and try again.').show();
                    $code.attr('aria-invalid', 'true').trigger('focus');
                }
            },
            error: function() { $error.text('Could not connect. Your code is still here; please try again.').show(); },
            complete: function() { verifying = false; $btn.prop('disabled', false).text('Verify & Login'); }
        });
    });
    // Password managers and paste remain available; revealing is always explicit.
    $('.gep-auth-card input[type="password"]').each(function() {
        var input = this;
        var $toggle = $('<button type="button" class="gep-auth-password-toggle" aria-pressed="false">Show password</button>');
        $toggle.attr('aria-controls', input.id).insertAfter(input).on('click', function() {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            $(this).attr('aria-pressed', String(show)).text(show ? 'Hide password' : 'Show password');
        });
    });
});
