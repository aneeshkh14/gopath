jQuery(function($) {
    'use strict';
    if (typeof GEP_Checkout === 'undefined') return;
    var $pay = $('#gep-pay-button'), $input = $('#gep-coupon-code'), $apply = $('#gep-apply-coupon');
    if (!$pay.length) return;
    var basePrice = Number($('#gep-final-amount').text().replace(/,/g, '')) || 0;
    var total = basePrice, coupon = '', revision = 0, checking = false, busy = false, pendingPayment = null;
    var $tiers = $('input[name="selected_attempts_tier"]');
    function message(res, fallback) { return res && res.data && typeof res.data.message === 'string' ? res.data.message : fallback; }
    function request(data) {
        return $.ajax({ url: GEP_Checkout.ajaxurl, type: 'POST', timeout: 25000, data: $.extend({nonce: GEP_Checkout.nonce}, data) });
    }
    function render() {
        $('#gep-final-amount').text(total.toFixed(2));
        $pay.prop('disabled', busy || checking).attr('aria-busy', String(busy));
        if (!busy) $pay.text(pendingPayment ? 'Retry payment verification' : total <= 0 ? 'Complete Enrollment' : 'Pay Safely with Razorpay');
        $tiers.add($input).prop('disabled', busy || !!pendingPayment);
        $apply.prop('disabled', busy || checking || !!pendingPayment);
    }
    function clearCoupon() {
        revision++; checking = false; coupon = ''; total = basePrice;
        $('#gep-coupon-status').text(''); $apply.text('APPLY'); render();
    }
    function selectTier() {
        var $selected = $tiers.filter(':checked');
        if ($selected.length) basePrice = Number($selected.data('price')) || 0;
        $('.gep-tier-label-wrap').removeClass('is-selected');
        $selected.closest('.gep-tier-label-wrap').addClass('is-selected');
        clearCoupon();
    }
    $tiers.on('change', selectTier); selectTier();
    $input.on('input', clearCoupon).on('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); $apply.trigger('click'); }
    });
    $apply.on('click', function() {
        if (busy || checking || pendingPayment) return;
        clearCoupon();
        var code = $.trim($input.val());
        if (!code) { $('#gep-coupon-status').text('Enter a coupon code first.'); $input.trigger('focus'); return; }
        var token = revision;
        checking = true; render(); $apply.text('Checking…');
        request({ action: 'gep_apply_coupon', coupon_code: code, item_id: GEP_Checkout.item_id, item_type: GEP_Checkout.item_type, attempts: $tiers.filter(':checked').val() || 0 })
            .done(function(res) {
                if (token !== revision) return;
                if (res && res.success && isFinite(Number(res.data.new_total))) {
                    coupon = code; total = Number(res.data.new_total);
                    $('#gep-coupon-status').text('Coupon applied. You save ₹' + Number(res.data.discount).toFixed(2) + '.');
                } else $('#gep-coupon-status').text(message(res, 'This coupon could not be applied.'));
            }).fail(function() {
                if (token === revision) $('#gep-coupon-status').text('Could not check the coupon. Please try again.');
            }).always(function() {
                if (token !== revision) return;
                checking = false; $apply.text('APPLY'); render();
            });
    });
    function showError(text) {
        var $error = $('#gep-payment-error');
        if (!$error.length) $error = $('<div id="gep-payment-error" class="gep-payment-error" role="alert" tabindex="-1"></div>').insertAfter($pay);
        $error.text(text).show().trigger('focus');
    }
    function release() { busy = false; render(); }
    function verify() {
        busy = true; render(); $pay.text('Verifying payment…');
        request($.extend({action: 'gep_verify_payment', item_id: GEP_Checkout.item_id, item_type: GEP_Checkout.item_type}, pendingPayment))
            .done(function(res) {
                if (res && res.success && res.data.redirect_url) window.location.href = res.data.redirect_url;
                else showError(message(res, 'Payment verification is not confirmed.') + ' Retry verification or contact support with payment ID: ' + pendingPayment.razorpay_payment_id + '. Do not pay again.');
            }).fail(function() {
                showError('We could not confirm payment verification. Retry verification or contact support with payment ID: ' + pendingPayment.razorpay_payment_id + '. Do not pay again.');
            }).always(release);
    }
    $pay.on('click', function() {
        if (busy || checking) return;
        $('#gep-payment-error').hide();
        if (pendingPayment) { verify(); return; }
        busy = true; render(); $pay.text('Preparing checkout…');
        request({action: 'gep_create_payment_order', item_id: GEP_Checkout.item_id, item_type: GEP_Checkout.item_type, coupon_code: coupon, attempts: $tiers.filter(':checked').val() || 0})
            .done(function(res) {
                if (!res || !res.success) { showError(message(res, 'Could not prepare checkout. Please try again.')); release(); return; }
                if (res.data.status === 'free') { window.location.href = res.data.redirect; return; }
                try {
                    var gateway = new Razorpay({
                        key: GEP_Checkout.key_id, amount: res.data.amount, currency: 'INR', name: 'GoPath Exam Portal',
                        description: 'Learning access', order_id: res.data.id,
                        prefill: {name: GEP_Checkout.user_name, email: GEP_Checkout.user_email},
                        theme: {color: '#4f46e5'},
                        modal: {ondismiss: function() { if (!pendingPayment) release(); }},
                        handler: function(payment) {
                            pendingPayment = {razorpay_payment_id: payment.razorpay_payment_id, razorpay_order_id: payment.razorpay_order_id, razorpay_signature: payment.razorpay_signature};
                            verify();
                        }
                    });
                    gateway.on('payment.failed', function(response) {
                        showError(response.error && response.error.description || 'Payment was not completed. You can retry in the payment window or close it.');
                        // The gateway can still be open. Its dismiss callback releases the order lock.
                    });
                    gateway.open(); $pay.text('Complete payment in the payment window');
                } catch (e) { showError('The payment window could not load. Refresh the page and try again.'); release(); }
            }).fail(function() { showError('Could not connect to checkout. Please try again.'); release(); });
    });
});
