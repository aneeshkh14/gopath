<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// $item and $item_id are provided by the GEP_Shortcodes::render_checkout() method
if ( ! isset($item) || ! $item ) {
    wp_die('Invalid selection.');
}
// NOTE: Razorpay SDK + GEP_Checkout JS object are enqueued in class-gep-loader.php
// during wp_enqueue_scripts so they load before wp_head() fires.
?>

<div class="gep-sovereign-checkout-wrap">
    <div class="checkout-glow-bg"></div>
    
    <div class="gep-checkout-container">
        <div class="gep-checkout-content">
            <div class="gep-checkout-header">
                <div class="header-badge">SECURE CHECKOUT</div>
                <h1>Complete Your <span class="gep-text-gradient-primary">Enrollment</span></h1>
                <p>Review your selection and payment details before enrolling.</p>
                <?php if ($item_type === 'pass') : ?><p>Renewing an active pass? Your remaining time is kept, and the new duration is added after it.</p><?php endif; ?>
            </div>

            <div class="gep-checkout-grid">
                <!-- Order Summary Card -->
                <div class="gep-checkout-card order-details-card">
                    <div class="card-header">
                        <h3>Order Summary</h3>
                        <span class="item-count">1 Item</span>
                    </div>
                    
                    <div class="item-preview">
                        <div class="item-icon"><?php echo $item_type === 'course' ? '🎓' : '📝'; ?></div>
                        <div class="item-info">
                            <span class="item-type"><?php echo strtoupper($item_type); ?></span>
                            <h4 class="item-title"><?php echo esc_html($item->title); ?></h4>
                        </div>
                    </div>

                    <div class="gep-order-summary">
                        <?php if ( $item_type === 'test' && isset($item->type) && $item->type === 'random' ) : 
                            $trans = !empty($item->translated_data) ? json_decode($item->translated_data, true) : array();
                            $attempt_pricing = isset($trans['attempt_pricing']) ? $trans['attempt_pricing'] : array();
                        ?>
                            <div class="gep-checkout-pricing-tiers" style="margin-bottom: 25px;">
                                <h4 style="margin: 0 0 12px; font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Select Attempts Package</h4>
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ( $attempt_pricing as $idx => $tier ) : ?>
                                        <label class="gep-tier-label-wrap" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 16px; cursor: pointer; transition: all 0.3s; font-weight: 700; color: #334155;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <input type="radio" name="selected_attempts_tier" value="<?php echo esc_attr($tier['attempts']); ?>" data-price="<?php echo esc_attr($tier['price']); ?>" <?php checked($idx, 0); ?> style="accent-color: #2563eb; width: 18px; height: 18px;">
                                                <span style="font-size: 15px; font-weight: 800; color: #0f172a;"><?php echo esc_html($tier['attempts']); ?> <?php echo $tier['attempts'] == 1 ? 'Attempt' : 'Attempts'; ?></span>
                                            </div>
                                            <span style="font-size: 18px; font-weight: 950; color: #2563eb;">₹<?php echo number_format($tier['price'], 2); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="summary-row">
                                <span class="label">Original Price</span>
                                <span class="value">₹<?php echo number_format($item->price, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="gep-coupon-section-modern">
                            <div class="input-wrap">
                                <input type="text" id="gep-coupon-code" aria-label="Coupon code" aria-describedby="gep-coupon-status" placeholder="HAVE A COUPON?">
                                <button type="button" id="gep-apply-coupon">APPLY</button>
                            </div>
                            <div id="gep-coupon-status" role="status" aria-live="polite"></div>
                        </div>

                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span class="label">Total Amount</span>
                            <div class="final-price-wrap">
                                <span class="currency">₹</span>
                                <span class="value" id="gep-final-amount" aria-live="polite"><?php echo number_format($item->price, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="gep-pay-button" class="btn-pay-sovereign">
                        <?php if ( $item->price <= 0 ) : ?>
                            <span>Complete Enrollment</span>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        <?php else : ?>
                            <span>Pay Safely with Razorpay</span>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <?php endif; ?>
                    </button>
                    
                    <div class="secure-footer">
                        <div class="secure-badge">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span>PCI DSS Compliant</span>
                        </div>
                        <div class="payment-methods">
                            <span>UPI • Cards • NetBanking</span>
                        </div>
                    </div>
                </div>

                <!-- Info Column -->
                <div class="checkout-info-column">
                    <div class="info-item-glass">
                        <div class="info-icon">⚡</div>
                        <div class="info-text">
                            <h5>Instant Access</h5>
                            <p>Get immediate access to all content after payment.</p>
                        </div>
                    </div>
                    <div class="info-item-glass">
                        <div class="info-icon">💎</div>
                        <div class="info-text">
                            <h5>Premium Quality</h5>
                            <p>Learn from top-tier instructors and cinema-grade videos.</p>
                        </div>
                    </div>
                    <div class="info-item-glass">
                        <div class="info-icon">🤝</div>
                        <div class="info-text">
                            <h5>24/7 Support</h5>
                            <p>Our dedicated support team is here for your success.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gep-sovereign-checkout-wrap {
    position: relative;
    background: var(--gep-c-surface-2);
    min-height: 100vh;
    padding: 80px 20px;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
}

.checkout-glow-bg {
    position: absolute;
    top: -20%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.05) 0%, transparent 70%);
    filter: blur(80px);
    z-index: 1;
}

.gep-checkout-container {
    position: relative;
    z-index: 5;
    max-width: 1100px;
    margin: 0 auto;
}

.gep-checkout-header {
    text-align: center;
    margin-bottom: 60px;
}

.header-badge {
    display: inline-block;
    padding: 6px 14px;
    background: #e0f2fe;
    color: #0369a1;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

.gep-checkout-header h1 {
    font-size: 48px;
    font-weight: 950;
    color: var(--gep-c-text);
    letter-spacing: -2px;
    margin-bottom: 15px;
}

.gep-checkout-header p {
    color: var(--gep-c-text-muted);
    font-size: 18px;
    font-weight: 500;
}

.gep-checkout-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 40px;
    align-items: start;
}

.gep-checkout-card {
    background: var(--gep-c-surface);
    border-radius: 32px;
    border: 1px solid rgba(0,0,0,0.05);
    padding: 40px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.03);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.card-header h3 {
    font-size: 20px;
    font-weight: 800;
    color: var(--gep-c-text);
}

.item-count {
    font-size: 12px;
    font-weight: 700;
    background: var(--gep-c-surface-3);
    padding: 4px 10px;
    border-radius: 8px;
    color: var(--gep-c-text-muted);
}

.item-preview {
    display: flex;
    gap: 20px;
    background: var(--gep-c-surface-2);
    padding: 20px;
    border-radius: 20px;
    margin-bottom: 30px;
}

.item-icon {
    width: 60px;
    height: 60px;
    background: var(--gep-c-surface);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.item-info .item-type {
    font-size: 10px;
    font-weight: 900;
    color: var(--gep-c-text-muted);
    letter-spacing: 1px;
}

.item-info .item-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--gep-c-text);
    margin-top: 4px;
}

.gep-order-summary .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 15px;
    font-weight: 600;
    color: var(--gep-c-text-muted);
}

.summary-divider {
    height: 1px;
    background: var(--gep-c-surface-3);
    margin: 25px 0;
}

.summary-row.total {
    align-items: center;
}

.summary-row.total .label {
    font-size: 18px;
    font-weight: 800;
    color: var(--gep-c-text);
}

.final-price-wrap {
    display: flex;
    align-items: baseline;
    gap: 2px;
}

.final-price-wrap .currency {
    font-size: 18px;
    font-weight: 800;
    color: #2563eb;
}

.final-price-wrap .value {
    font-size: 32px;
    font-weight: 950;
    color: #2563eb;
    letter-spacing: -1px;
}

.gep-coupon-section-modern {
    margin: 25px 0;
}

.gep-coupon-section-modern .input-wrap {
    display: flex;
    background: var(--gep-c-surface-3);
    padding: 6px;
    border-radius: 16px;
    border: 1px solid transparent;
    transition: all 0.3s;
}

.gep-coupon-section-modern .input-wrap:focus-within {
    background: var(--gep-c-surface);
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.gep-coupon-section-modern input {
    flex: 1;
    background: transparent;
    border: none;
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
    color: var(--gep-c-text);
    outline: none !important;
}

.gep-coupon-section-modern button {
    background: #0f172a;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s;
}

.gep-coupon-section-modern button:hover {
    background: #1e293b;
}

#gep-coupon-status {
    font-size: 12px;
    font-weight: 700;
    margin-top: 10px;
    padding-left: 10px;
}

.btn-pay-sovereign {
    width: 100%;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #fff;
    border: none;
    padding: 20px;
    border-radius: 20px;
    font-size: 18px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.btn-pay-sovereign:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px rgba(0,0,0,0.2);
}

.btn-pay-sovereign:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.secure-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px dashed var(--gep-c-border);
}

.secure-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 700;
    color: #10b981;
}

.payment-methods {
    font-size: 11px;
    font-weight: 700;
    color: var(--gep-c-text-muted);
}

.checkout-info-column {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-item-glass {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    padding: 25px;
    border-radius: 24px;
    display: flex;
    gap: 20px;
    transition: transform 0.3s;
}

.info-item-glass:hover {
    transform: translateX(10px);
}

.info-icon {
    width: 44px;
    height: 44px;
    background: var(--gep-c-surface);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    flex-shrink: 0;
}

.info-text h5 {
    font-size: 16px;
    font-weight: 800;
    color: var(--gep-c-text);
    margin-bottom: 4px;
}

.info-text p {
    font-size: 14px;
    color: var(--gep-c-text-muted);
    line-height: 1.4;
    margin: 0;
}

@media (max-width: 992px) {
    .gep-checkout-grid { grid-template-columns: 1fr; }
    .checkout-info-column { order: -1; flex-direction: row; flex-wrap: wrap; }
    .info-item-glass { flex: 1; min-width: 250px; }
}

@media (max-width: 768px) {
    .gep-checkout-header h1 { font-size: 36px; }
    .gep-checkout-card { padding: 25px; }
    .header-badge { font-size: 10px; }
}

@media (max-width: 480px) {
    .gep-checkout-header h1 { font-size: 26px; letter-spacing: -1px; }
    .gep-checkout-header p { font-size: 15px; }
    .gep-checkout-card { padding: 20px 15px; border-radius: 20px; }
    .card-header h3 { font-size: 18px; }
    .item-preview { gap: 12px; padding: 15px; }
    .item-icon { width: 48px; height: 48px; font-size: 24px; }
    .item-info .item-title { font-size: 15px; }
    .final-price-wrap .value { font-size: 28px; }
    .gep-coupon-section-modern .input-wrap { flex-direction: column; gap: 8px; background: transparent; padding: 0; }
    .gep-coupon-section-modern input { background: var(--gep-c-surface-3); border-radius: 12px; width: 100%; border: 1.5px solid transparent; }
    .gep-coupon-section-modern input:focus { background: var(--gep-c-surface); border-color: #2563eb; }
    .gep-coupon-section-modern button { width: 100%; height: 44px; border-radius: 12px; }
    .btn-pay-sovereign { padding: 15px; font-size: 16px; border-radius: 14px; }
    .secure-footer { flex-direction: column; gap: 12px; text-align: center; }
}
</style>
