jQuery(document).ready(function($) {
    // OTP Verification
    $('#gep-verify-otp-btn').on('click', function() {
        const btn = $(this);
        const code = $('#gep_otp_code').val();
        const userId = $('#gep_otp_uid').val();
        const errorMsg = $('#gep-otp-error');

        if (code.length !== 6) {
            errorMsg.text('Please enter a 6-digit code').show();
            return;
        }

        btn.prop('disabled', true).text('Verifying...');
        errorMsg.hide();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'gep_verify_otp',
                otp: code,
                user_id: userId,
                nonce: $('#gep_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    sessionStorage.removeItem('gep_current_lang');
                    window.location.href = response.data.redirect;
                } else {
                    btn.prop('disabled', false).text('Verify & Login');
                    errorMsg.text(response.data.message).show();
                }
            }
        });
    });

    // Handle OTP input formatting and auto-focus
    $('.gep-otp-box').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value) {
            $(this).next('.gep-otp-box').focus();
        }
        collectOTP();
    });

    $('.gep-otp-box').on('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value) {
            $(this).prev('.gep-otp-box').focus();
        }
    });

    function collectOTP() {
        let code = '';
        $('.gep-otp-box').each(function() {
            code += $(this).val();
        });
        $('#gep_otp_code').val(code);
        
        if (code.length === 6) {
            $('#gep-verify-otp-btn').click();
        }
    }
});
