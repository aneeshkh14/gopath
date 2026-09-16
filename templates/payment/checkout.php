<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// $item and $item_id are provided by the GEP_Shortcodes::render_checkout() method
if ( ! isset($item) || ! $item ) {
    wp_die('Invalid selection.');
}
$checkout_price = !empty($item->is_free) ? 0 : $item->price;
// NOTE: Razorpay SDK + GEP_Checkout JS object are enqueued in class-gep-loader.php
// during wp_enqueue_scripts so they load before wp_head() fires.
?>

<div class="gep-sovereign-checkout-wrap">
    <div class="gep-checkout-container">
        <div class="gep-checkout-content">
            <div class="gep-checkout-header">
                <div class="header-badge">SECURE CHECKOUT</div>
                <h1>Complete your <span>enrollment</span></h1>
                <p>Review your selection and payment details before enrolling.</p>
                <?php if ($item_type === 'pass') : ?><p>Renewing an active pass? Your remaining time is kept, and the new duration is added after it.</p><?php endif; ?>
            </div>

            <div class="gep-checkout-grid">
                <!-- Order Summary Card -->
                <div class="gep-checkout-card order-details-card">
                    <div class="card-header">
                        <h2>Order Summary</h2>
                        <span class="item-count">1 Item</span>
                    </div>
                    
                    <div class="item-preview">
                        <div class="item-icon"><?php echo $item_type === 'course' ? '🎓' : '📝'; ?></div>
                        <div class="item-info">
                            <span class="item-type"><?php echo strtoupper($item_type); ?></span>
                            <h3 class="item-title"><?php echo esc_html($item->title); ?></h3>
                        </div>
                    </div>

                    <div class="gep-order-summary">
                        <?php if ( $item_type === 'test' && isset($item->type) && $item->type === 'random' && empty($item->is_free) ) :
                            $trans = !empty($item->translated_data) ? json_decode($item->translated_data, true) : array();
                            $attempt_pricing = isset($trans['attempt_pricing']) ? $trans['attempt_pricing'] : array();
                        ?>
                            <fieldset class="gep-checkout-pricing-tiers">
                                <legend>Select attempts package</legend>
                                <div class="gep-checkout-tiers">
                                    <?php foreach ( $attempt_pricing as $idx => $tier ) : ?>
                                        <label class="gep-tier-label-wrap">
                                            <span class="gep-tier-choice">
                                                <input type="radio" name="selected_attempts_tier" value="<?php echo esc_attr($tier['attempts']); ?>" data-price="<?php echo esc_attr($tier['price']); ?>" <?php checked($idx, 0); ?>>
                                                <span><?php echo esc_html($tier['attempts']); ?> <?php echo $tier['attempts'] == 1 ? 'Attempt' : 'Attempts'; ?></span>
                                            </span>
                                            <span class="gep-tier-price">₹<?php echo number_format($tier['price'], 2); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>
                        <?php else : ?>
                            <div class="summary-row">
                                <span class="label">Original Price</span>
                                <span class="value">₹<?php echo number_format($checkout_price, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="gep-coupon-section-modern">
                            <label class="gep-coupon-label" for="gep-coupon-code">Coupon code <span>(optional)</span></label>
                            <div class="input-wrap">
                                <input type="text" id="gep-coupon-code" aria-label="Coupon code" aria-describedby="gep-coupon-status" placeholder="Enter coupon code" autocomplete="off" autocapitalize="characters" spellcheck="false">
                                <button type="button" id="gep-apply-coupon">APPLY</button>
                            </div>
                            <div id="gep-coupon-status" role="status" aria-live="polite"></div>
                        </div>

                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span class="label">Total Amount</span>
                            <div class="final-price-wrap">
                                <span class="currency">₹</span>
                                <span class="value" id="gep-final-amount" aria-live="polite"><?php echo number_format($checkout_price, 2); ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="gep-pay-button" class="btn-pay-sovereign">
                        <?php if ( $checkout_price <= 0 ) : ?>
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
                            <h2>Instant Access</h2>
                            <p>Your selection becomes available once payment is confirmed.</p>
                        </div>
                    </div>
                    <div class="info-item-glass">
                        <div class="info-icon">💎</div>
                        <div class="info-text">
                            <h2>Review Your Selection</h2>
                            <p>Check the selected item and price before completing payment.</p>
                        </div>
                    </div>
                    <div class="info-item-glass">
                        <div class="info-icon">🤝</div>
                        <div class="info-text">
                            <h2>Help &amp; Support</h2>
                            <p>Use the dashboard support form for questions about your purchase.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Keep checkout surfaces and text on the same palette in both themes. */
.gep-sovereign-checkout-wrap {
    padding: 8px 0 24px;
    color: var(--gep-c-text);
    background: transparent;
}
.gep-sovereign-checkout-wrap .gep-checkout-container {
    max-width: 1100px;
    margin: 0 auto;
}
.gep-sovereign-checkout-wrap .gep-checkout-header { margin-bottom: 28px; }
.gep-sovereign-checkout-wrap .header-badge {
    display: inline-flex;
    padding: 5px 10px;
    margin-bottom: 12px;
    background: var(--gep-c-surface-3);
    color: var(--gep-c-info);
    border: 1px solid var(--gep-c-border-strong);
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
}
.gep-sovereign-checkout-wrap .gep-checkout-header h1 {
    margin: 0 0 10px;
    color: var(--gep-c-text);
    font-size: clamp(26px, 3vw, 34px);
    font-weight: 750;
    letter-spacing: -.7px;
    line-height: 1.25;
}
.gep-sovereign-checkout-wrap .gep-checkout-header h1 span { color: var(--gep-c-info); }
.gep-sovereign-checkout-wrap .gep-checkout-header p {
    margin: 6px 0 0;
    color: var(--gep-c-text-muted);
    font-size: 15px;
    line-height: 1.6;
}
.gep-sovereign-checkout-wrap .gep-checkout-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}
.gep-sovereign-checkout-wrap .gep-checkout-grid > div,
.gep-sovereign-checkout-wrap .item-info,
.gep-sovereign-checkout-wrap .info-text { min-width: 0; }
.gep-sovereign-checkout-wrap .gep-checkout-card,
.gep-sovereign-checkout-wrap .info-item-glass {
    background: var(--gep-c-surface);
    border: 1px solid var(--gep-c-border-strong);
    border-radius: 16px;
}
.gep-sovereign-checkout-wrap .gep-checkout-card { padding: 28px; }
.gep-sovereign-checkout-wrap .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.gep-sovereign-checkout-wrap .card-header h2 {
    margin: 0;
    color: var(--gep-c-text);
    font-size: 20px;
    font-weight: 700;
}
.gep-sovereign-checkout-wrap .item-count {
    flex-shrink: 0;
    padding: 4px 8px;
    background: var(--gep-c-surface-3);
    color: var(--gep-c-text-muted);
    border-radius: 6px;
    font-size: 12px;
}
.gep-sovereign-checkout-wrap .item-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding: 18px;
    background: var(--gep-c-surface-2);
    border-radius: 12px;
}
.gep-sovereign-checkout-wrap .item-icon,
.gep-sovereign-checkout-wrap .info-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    background: var(--gep-c-surface-3);
    border-radius: 10px;
    font-size: 22px;
}
.gep-sovereign-checkout-wrap .item-type {
    color: var(--gep-c-text-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .8px;
}
.gep-sovereign-checkout-wrap .item-title {
    margin: 5px 0 0;
    color: var(--gep-c-text);
    font-size: 17px;
    font-weight: 650;
    line-height: 1.45;
    overflow-wrap: anywhere;
}
.gep-sovereign-checkout-wrap .summary-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 8px 16px;
    color: var(--gep-c-text-muted);
    font-size: 14px;
}
.gep-sovereign-checkout-wrap .summary-divider {
    height: 1px;
    margin: 24px 0;
    background: var(--gep-c-border-strong);
}
.gep-sovereign-checkout-wrap .summary-row.total .label {
    color: var(--gep-c-text);
    font-size: 16px;
    font-weight: 650;
}
.gep-sovereign-checkout-wrap .final-price-wrap {
    display: flex;
    align-items: baseline;
    gap: 2px;
    color: var(--gep-c-text);
    font-variant-numeric: tabular-nums;
}
.gep-sovereign-checkout-wrap .final-price-wrap .currency { font-size: 18px; font-weight: 650; }
.gep-sovereign-checkout-wrap .final-price-wrap .value { font-size: 30px; font-weight: 750; letter-spacing: -.6px; }
.gep-sovereign-checkout-wrap .gep-checkout-pricing-tiers { min-width: 0; margin: 0 0 24px; padding: 0; border: 0; }
.gep-sovereign-checkout-wrap .gep-checkout-pricing-tiers legend,
.gep-sovereign-checkout-wrap .gep-coupon-label {
    display: block;
    margin: 0 0 10px;
    padding: 0;
    color: var(--gep-c-text);
    font-size: 14px;
    font-weight: 600;
}
.gep-sovereign-checkout-wrap .gep-coupon-label span { color: var(--gep-c-text-muted); font-weight: 400; }
.gep-sovereign-checkout-wrap .gep-checkout-tiers { display: flex; flex-direction: column; gap: 10px; }
.gep-sovereign-checkout-wrap .gep-tier-label-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    min-height: 48px;
    padding: 14px;
    background: var(--gep-c-surface-2);
    border: 2px solid var(--gep-c-border-strong);
    border-radius: 10px;
    cursor: pointer;
}
.gep-sovereign-checkout-wrap .gep-tier-choice { display: flex; align-items: center; gap: 10px; color: var(--gep-c-text); font-size: 14px; font-weight: 600; }
.gep-sovereign-checkout-wrap .gep-tier-choice input { width: 18px; height: 18px; margin: 0; flex-shrink: 0; accent-color: var(--gep-c-accent); }
.gep-sovereign-checkout-wrap .gep-tier-price { color: var(--gep-c-info); font-size: 16px; font-weight: 700; }
.gep-sovereign-checkout-wrap .gep-coupon-section-modern { margin: 24px 0; }
.gep-sovereign-checkout-wrap .input-wrap { display: flex; gap: 10px; }
.gep-sovereign-checkout-wrap #gep-coupon-code {
    flex: 1;
    min-width: 0;
    min-height: 44px;
    padding: 10px 12px;
    background: var(--gep-c-field);
    color: var(--gep-c-field-text);
    border: 1px solid var(--gep-c-field-border);
    border-radius: 8px;
    font: inherit;
    font-size: 16px;
}
.gep-sovereign-checkout-wrap #gep-coupon-code::placeholder { color: var(--gep-c-text-muted); opacity: 1; }
.gep-sovereign-checkout-wrap #gep-apply-coupon {
    flex-shrink: 0;
    min-height: 44px;
    padding: 10px 18px;
    background: var(--gep-c-surface-3);
    color: var(--gep-c-text);
    border: 1px solid var(--gep-c-border-strong);
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.gep-sovereign-checkout-wrap #gep-coupon-status { margin-top: 10px; color: var(--gep-c-text-muted); font-size: 13px; line-height: 1.5; overflow-wrap: anywhere; }
.gep-sovereign-checkout-wrap #gep-coupon-status:empty { display: none; }
.gep-sovereign-checkout-wrap #gep-pay-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    min-height: 48px;
    margin-top: 24px;
    padding: 14px 18px;
    background: var(--gep-c-accent);
    color: var(--gep-c-accent-text);
    border: 2px solid transparent;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 650;
    line-height: 1.5;
    cursor: pointer;
}
.gep-sovereign-checkout-wrap #gep-pay-button:not(:disabled):hover { border-color: var(--gep-c-accent-text); }
.gep-sovereign-checkout-wrap #gep-apply-coupon:not(:disabled):hover { border-color: var(--gep-c-accent); }
.gep-sovereign-checkout-wrap button:disabled { cursor: not-allowed; }
.gep-sovereign-checkout-wrap .secure-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--gep-c-border-strong);
}
.gep-sovereign-checkout-wrap .secure-badge { display: flex; align-items: center; gap: 6px; color: var(--gep-c-success); font-size: 12px; font-weight: 600; }
.gep-sovereign-checkout-wrap .payment-methods { color: var(--gep-c-text-muted); font-size: 12px; }
.gep-sovereign-checkout-wrap .checkout-info-column { display: flex; flex-direction: column; gap: 16px; }
.gep-sovereign-checkout-wrap .info-item-glass { display: flex; align-items: flex-start; gap: 16px; padding: 22px; }
.gep-sovereign-checkout-wrap .info-text h2 { margin: 0 0 6px; color: var(--gep-c-text); font-size: 16px; font-weight: 650; line-height: 1.4; }
.gep-sovereign-checkout-wrap .info-text p { margin: 0; color: var(--gep-c-text-muted); font-size: 14px; line-height: 1.6; }
@media (max-width: 992px) {
    .gep-sovereign-checkout-wrap .gep-checkout-grid { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 600px) {
    .gep-sovereign-checkout-wrap { padding-top: 0; }
    .gep-sovereign-checkout-wrap .gep-checkout-header { margin-bottom: 22px; }
    .gep-sovereign-checkout-wrap .gep-checkout-card { padding: 20px; }
    .gep-sovereign-checkout-wrap .info-item-glass { padding: 18px; }
    .gep-sovereign-checkout-wrap .item-preview { padding: 14px; gap: 12px; }
    .gep-sovereign-checkout-wrap .item-title { font-size: 15px; }
}
@media (max-width: 480px) {
    .gep-sovereign-checkout-wrap .gep-coupon-section-modern .input-wrap { flex-direction: column; }
    .gep-sovereign-checkout-wrap #gep-coupon-code { width: 100%; }
    .gep-sovereign-checkout-wrap .card-header h2 { font-size: 18px; }
    .gep-sovereign-checkout-wrap .final-price-wrap .value { font-size: 26px; }
}
</style>
