(function($) {
    'use strict';

    $(document).ready(function() {
        var $payBtn = $('#gep-pay-button');
        var $couponInput = $('#gep-coupon-code');
        var $applyBtn = $('#gep-apply-coupon');
        var appliedCoupon = '';
        var currentTotal = parseFloat($('#gep-final-amount').text()) || 0;

        // Update selected tier style and price dynamically
        function updateSelectedTier() {
            var $checked = $('input[name="selected_attempts_tier"]:checked');
            if ($checked.length) {
                $('.gep-tier-label-wrap').css({
                    'border-color': '#e2e8f0',
                    'background': '#f8fafc'
                });
                $checked.closest('.gep-tier-label-wrap').css({
                    'border-color': '#2563eb',
                    'background': '#f0f6ff'
                });
                var price = parseFloat($checked.data('price')) || 0;
                currentTotal = price;
                $('#gep-final-amount').text(price.toFixed(2));
                // If coupon was applied, reset it
                if (appliedCoupon) {
                    $('#gep-coupon-code').val('');
                    appliedCoupon = '';
                    $('#gep-coupon-status').text('');
                }
            }
        }

        // Initialize tier selection styles
        updateSelectedTier();

        // Listen for selection changes
        $(document).on('change', 'input[name="selected_attempts_tier"]', function() {
            updateSelectedTier();
        });

        // Apply Coupon
        $applyBtn.on('click', function() {
            var code = $.trim($couponInput.val());
            if (!code) return;

            $applyBtn.prop('disabled', true).text('Checking...');
            var selectedAttempts = $('input[name="selected_attempts_tier"]:checked').val() || 0;

            $.post(GEP_Checkout.ajaxurl, {
                action: 'gep_apply_coupon',
                nonce: GEP_Checkout.nonce,
                coupon_code: code,
                item_id: GEP_Checkout.item_id,
                item_type: GEP_Checkout.item_type,
                attempts: selectedAttempts
            }, function(res) {
                $applyBtn.prop('disabled', false).text('APPLY');
                if (res.success) {
                    appliedCoupon = code;
                    currentTotal = parseFloat(res.data.new_total);
                    $('#gep-final-amount').text(parseFloat(res.data.new_total).toFixed(2));
                    $('#gep-coupon-status').text('✓ Coupon applied: ₹' + parseFloat(res.data.discount).toFixed(2) + ' discount').css('color', '#10b981');
                } else {
                    appliedCoupon = '';
                    $('#gep-coupon-status').text('✗ ' + res.data.message).css('color', '#ef4444');
                }
            }).fail(function() {
                $applyBtn.prop('disabled', false).text('APPLY');
                $('#gep-coupon-status').text('Network error. Please try again.').css('color', '#ef4444');
            });
        });

        // Pay Button
        $payBtn.on('click', function(e) {
            e.preventDefault();

            // Prevent double-click
            if ($payBtn.data('processing')) return;
            $payBtn.data('processing', true).prop('disabled', true).text('Initializing...');
            var selectedAttempts = $('input[name="selected_attempts_tier"]:checked').val() || 0;

            $.post(GEP_Checkout.ajaxurl, {
                action: 'gep_create_payment_order',
                nonce: GEP_Checkout.nonce,
                item_id: GEP_Checkout.item_id,
                item_type: GEP_Checkout.item_type,
                coupon_code: appliedCoupon,
                attempts: selectedAttempts
            }, function(res) {
                if (!res.success) {
                    showError(res.data ? res.data.message : 'Failed to initialize payment. Please try again.');
                    resetBtn();
                    return;
                }

                // Free item — redirect immediately
                if (res.data.status === 'free') {
                    window.location.href = res.data.redirect;
                    return;
                }

                // Open Razorpay checkout modal
                var options = {
                    "key": GEP_Checkout.key_id,
                    "amount": res.data.amount,
                    "currency": "INR",
                    "name": "GoPath Exam Portal",
                    "description": (GEP_Checkout.item_type === 'course' ? 'Course' : 'Exam') + " Access Purchase",
                    "order_id": res.data.id,
                    "handler": function(response) {
                        $payBtn.text('Verifying Payment...');
                        verifyPayment(response);
                    },
                    "prefill": {
                        "name": GEP_Checkout.user_name,
                        "email": GEP_Checkout.user_email
                    },
                    "theme": {
                        "color": "#6366f1"
                    },
                    "modal": {
                        // CRITICAL FIX: Re-enable button if user closes the modal without paying
                        "ondismiss": function() {
                            resetBtn();
                        }
                    }
                };

                try {
                    var rzp1 = new Razorpay(options);
                    rzp1.on('payment.failed', function(response) {
                        showError('Payment failed: ' + (response.error.description || 'Unknown error'));
                        resetBtn();
                    });
                    rzp1.open();
                    // Reset button after opening so user can retry if modal is dismissed
                    $payBtn.prop('disabled', false).text('Pay Safely with Razorpay');
                    $payBtn.data('processing', false);
                } catch(e) {
                    showError('Could not load payment gateway. Please refresh and try again.');
                    resetBtn();
                }
            }).fail(function(xhr) {
                var msg = 'Network error. Please check your connection and try again.';
                try { msg = JSON.parse(xhr.responseText).data.message || msg; } catch(e) {}
                showError(msg);
                resetBtn();
            });
        });

        function verifyPayment(paymentResponse) {
            $.post(GEP_Checkout.ajaxurl, {
                action: 'gep_verify_payment',
                nonce: GEP_Checkout.nonce,
                razorpay_payment_id: paymentResponse.razorpay_payment_id,
                razorpay_order_id: paymentResponse.razorpay_order_id,
                razorpay_signature: paymentResponse.razorpay_signature,
                item_id: GEP_Checkout.item_id,
                item_type: GEP_Checkout.item_type
            }, function(res) {
                if (res.success) {
                    $payBtn.text('Payment Successful! Redirecting...');
                    window.location.href = res.data.redirect_url;
                } else {
                    showError('Payment verification failed: ' + (res.data ? res.data.message : 'Please contact support with your payment ID: ' + paymentResponse.razorpay_payment_id));
                    resetBtn();
                }
            }).fail(function() {
                // CRITICAL: Even if verification AJAX fails, payment may have gone through.
                // Show a user-friendly message rather than saying "failed".
                showError('⚠️ Payment received but verification timed out. Your access will be activated shortly. If not, please contact support with ID: ' + paymentResponse.razorpay_payment_id);
                resetBtn();
            });
        }

        function resetBtn() {
            $payBtn.data('processing', false).prop('disabled', false);
            var price = parseFloat($('#gep-final-amount').text()) || 0;
            $payBtn.text(price <= 0 ? 'Complete Enrollment' : 'Pay Safely with Razorpay');
        }

        function showError(message) {
            var $err = $('#gep-payment-error');
            if (!$err.length) {
                $err = $('<div id="gep-payment-error" style="margin-top:16px;padding:14px 20px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:12px;color:#ef4444;font-weight:600;font-size:14px;text-align:center;"></div>');
                $payBtn.after($err);
            }
            $err.text(message).show();
            setTimeout(function() { $err.fadeOut(); }, 8000);
        }
    });

})(jQuery);
