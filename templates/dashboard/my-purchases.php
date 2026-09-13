<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php
// Pre-calculate totals for header
$total_tests   = count( array_filter( $purchases, function($p) { return in_array(isset($p->item_category) ? $p->item_category : '', array('test', 'series')); } ) );
$total_courses = count( array_filter( $purchases, function($p) { return (isset($p->item_category) ? $p->item_category : '') === 'course'; } ) );

// Helper: get launch URL
function gep_purchase_launch_url( $p ) {
    $cat = isset($p->item_category) ? $p->item_category : 'test';
    if ( $cat === 'course' ) {
        return add_query_arg( array( 'view' => 'watch', 'id' => $p->id ), (string) gep_get_url('dashboard') );
    }
    if ( $cat === 'series' ) {
        return add_query_arg( 'id', $p->id, (string) gep_get_url('exam') );
    }
    return add_query_arg( 'id', $p->id, (string) gep_get_url('exam') );
}
?>

<div class="gep-purchases-page">

    <!-- Header -->
    <div class="gep-purchases-header">
        <div class="purchases-title-block">
            <h2>My <span class="gep-text-gradient-primary">Purchases</span></h2>
            <p>All your active tests, series, and courses — in one place.</p>
        </div>
        <div class="purchases-summary-pills">
            <div class="summary-pill pill-test">
                <span class="pill-icon">📝</span>
                <div>
                    <div class="pill-count"><?php echo $total_tests; ?></div>
                    <div class="pill-label">Tests / Series</div>
                </div>
            </div>
            <div class="summary-pill pill-course">
                <span class="pill-icon">🎓</span>
                <div>
                    <div class="pill-count"><?php echo $total_courses; ?></div>
                    <div class="pill-label">Courses</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ( ! empty( $purchases ) ) : ?>
        <div class="gep-purchases-grid">
            <?php foreach ( $purchases as $p ) :
                $cat = isset($p->item_category) ? $p->item_category : 'test';

                // Type config
                if ( $cat === 'series' ) {
                    $type_label = 'TEST SERIES';
                    $type_class = 'badge-series';
                    $icon       = '📁';
                    $launch_label = 'View Series';
                } elseif ( $cat === 'course' ) {
                    $type_label = 'COURSE';
                    $type_class = 'badge-course';
                    $icon       = '🎓';
                    $launch_label = 'Watch Now';
                } else {
                    $type_class = 'badge-test';
                    $launch_label = 'Start Exam';
                    
                    if ( isset($p->type) && $p->type === 'single' ) {
                        $type_label = 'SINGLE TEST';
                        $icon       = '📝';
                    } elseif ( isset($p->type) && $p->type === 'multiple' ) {
                        $type_label = 'MULTIPLE SUBJECT';
                        $icon       = '📚';
                    } elseif ( isset($p->type) && $p->type === 'combined' ) {
                        $type_label = 'COMBINED TEST';
                        $icon       = '🧩';
                    } elseif ( isset($p->type) && $p->type === 'self_test' ) {
                        $type_label = 'SELF TESTING';
                        $icon       = '⚙️';
                    } else {
                        $type_label = 'SINGLE TEST';
                        $icon       = '📝';
                    }
                }

                // Attempt info (only for tests, not courses)
                $show_attempts = ( $cat !== 'course' ) && isset( $p->attempt_limit );
                if ( $show_attempts ) {
                    $total_allowed = (int) $p->attempt_limit;
                    $used          = (int) $p->used_attempts;
                    $is_unlimited  = ( $total_allowed == 0 );
                    $remaining     = $is_unlimited ? '∞' : max( 0, $total_allowed - $used );
                    $bar_pct       = ( !$is_unlimited && $total_allowed > 0 ) ? min(100, max(0, $used / $total_allowed * 100)) : 0;
                }

                $launch_url       = gep_purchase_launch_url( $p );
                $enrollment_date  = $p->enrollment_date ? date( 'M j, Y', strtotime( $p->enrollment_date ) ) : 'Manual Grant';
            ?>
            <div class="gep-purchase-card gep-glass">

                <div class="purchase-card-top">
                    <div class="purchase-icon-wrap <?php echo $type_class; ?>">
                        <span><?php echo $icon; ?></span>
                    </div>
                    <span class="purchase-type-badge <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                </div>

                <div class="purchase-card-body">
                    <h4 class="purchase-title"><?php echo esc_html( $p->title ); ?></h4>
                    <div class="purchase-enrolled">Enrolled: <strong><?php echo $enrollment_date; ?></strong></div>
                </div>

                <?php if ( $show_attempts ) : ?>
                <div class="purchase-attempts-block">
                    <div class="attempts-row">
                        <span class="attempts-label">Attempts</span>
                        <span class="attempts-value">
                            <?php if ( $is_unlimited ) : ?>
                                <strong class="unlimited">∞ Unlimited</strong>
                            <?php else : ?>
                                <strong><?php echo $remaining; ?></strong> remaining of <?php echo $total_allowed; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ( !$is_unlimited && $total_allowed > 0 ) : ?>
                    <div class="attempts-bar-bg">
                        <div class="attempts-bar-fill" style="width: <?php echo 100 - $bar_pct; ?>%;"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="purchase-card-footer">
                    <div class="purchase-status-dot">
                        <span class="dot-green"></span> Access Active
                    </div>
                    <a href="<?php echo esc_url( $launch_url ); ?>" class="purchase-launch-btn">
                        <?php echo $launch_label; ?>
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
        <div class="gep-purchases-empty gep-glass">
            <div class="empty-icon">📦</div>
            <h3>No Purchases Yet</h3>
            <p>You haven't bought any tests, series, or courses yet. Explore the marketplace and get started!</p>
            <div class="empty-actions">
                <a href="<?php echo esc_url( add_query_arg('view', 'tests', (string)gep_get_url('dashboard')) ); ?>" class="gep-btn-sovereign-primary">Browse Tests</a>
                <a href="<?php echo esc_url( add_query_arg('view', 'supercoaching', (string)gep_get_url('dashboard')) ); ?>" class="gep-btn-sovereign-secondary">Browse Courses</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* ===================== My Purchases ===================== */
.gep-purchases-page {
    padding: 10px;
}

.gep-purchases-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    gap: 20px;
    flex-wrap: wrap;
}

.purchases-title-block h2 {
    font-size: 26px;
    font-weight: 900;
    margin: 0 0 6px;
    letter-spacing: -0.5px;
    color: #f1f5f9;
}

/* Sits on the dark page background, where #64748b is 4.2:1. #94a3b8 is 7.8:1. */
.purchases-title-block p {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 600;
    margin: 0;
}

.purchases-summary-pills {
    display: flex;
    gap: 12px;
}

.summary-pill {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.03);
    min-width: 120px;
}

.pill-icon {
    font-size: 24px;
    line-height: 1;
}

.pill-count {
    font-size: 22px;
    font-weight: 900;
    color: #f1f5f9;
    line-height: 1;
}

.pill-label {
    font-size: 10px;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* Grid */
.gep-purchases-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

/* Card */
.gep-purchase-card {
    border-radius: 24px;
    border: 1px solid rgba(255,255,255,0.07);
    background: linear-gradient(145deg, rgba(15,23,42,0.7), rgba(2,6,23,0.85));
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
}

.gep-purchase-card:hover {
    transform: translateY(-5px);
    border-color: rgba(99,102,241,0.25);
    box-shadow: 0 20px 40px rgba(99,102,241,0.12);
}

/* Top Row */
.purchase-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.purchase-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.badge-test .purchase-icon-wrap, .purchase-icon-wrap.badge-test  { background: rgba(99,102,241,0.15); }
.badge-series .purchase-icon-wrap, .purchase-icon-wrap.badge-series { background: rgba(168,85,247,0.15); }
.badge-course .purchase-icon-wrap, .purchase-icon-wrap.badge-course { background: rgba(16,185,129,0.15); }

/* Type Badge */
.purchase-type-badge {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.8px;
    padding: 4px 10px;
    border-radius: 20px;
}

.badge-test  { background: rgba(99,102,241,0.15); color: #818cf8; border: 1px solid rgba(99,102,241,0.2); }
.badge-series { background: rgba(168,85,247,0.15); color: #c084fc; border: 1px solid rgba(168,85,247,0.2); }
.badge-course { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }

/* Body */
.purchase-card-body {
    flex: 1;
}

.purchase-title {
    font-size: 16px;
    font-weight: 800;
    color: #f1f5f9;
    margin: 0 0 8px;
    line-height: 1.4;
}

.purchase-enrolled {
    font-size: 12px;
    color: #475569;
    font-weight: 600;
}

.purchase-enrolled strong {
    color: #64748b;
}

/* Attempts Block */
.purchase-attempts-block {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 14px;
    padding: 14px 16px;
}

.attempts-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.attempts-label {
    font-size: 10px;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.attempts-value {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}

.attempts-value strong {
    color: #f1f5f9;
    font-weight: 900;
    font-size: 14px;
}

.attempts-value strong.unlimited {
    color: #34d399;
    font-size: 13px;
}

.attempts-bar-bg {
    height: 5px;
    background: rgba(255,255,255,0.06);
    border-radius: 10px;
    overflow: hidden;
}

.attempts-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #6366f1, #a855f7);
    border-radius: 10px;
    transition: width 0.5s ease;
}

/* Footer */
.purchase-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.purchase-status-dot {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}

.dot-green {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 6px rgba(16,185,129,0.5);
    animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.purchase-launch-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    background: linear-gradient(135deg, #6366f1, #a855f7);
    color: #fff !important;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.25s;
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

.purchase-launch-btn:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 8px 20px rgba(99,102,241,0.4);
}

/* Empty State */
.gep-purchases-empty {
    padding: 80px 40px;
    text-align: center;
    border-radius: 32px;
    border: 1px solid rgba(255,255,255,0.06);
    background: linear-gradient(145deg, rgba(15,23,42,0.6), rgba(2,6,23,0.8));
}

.gep-purchases-empty .empty-icon {
    font-size: 64px;
    margin-bottom: 24px;
}

.gep-purchases-empty h3 {
    font-size: 22px;
    font-weight: 900;
    color: #f1f5f9;
    margin: 0 0 10px;
}

.gep-purchases-empty p {
    font-size: 14px;
    color: #64748b;
    font-weight: 600;
    max-width: 400px;
    margin: 0 auto 30px;
    line-height: 1.6;
}

.empty-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.gep-btn-sovereign-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 28px;
    border-radius: 16px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: #94a3b8 !important;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
    transition: all 0.25s;
}

.gep-btn-sovereign-secondary:hover {
    background: rgba(255,255,255,0.1);
    color: #f1f5f9 !important;
}

@media (max-width: 768px) {
    .gep-purchases-header { flex-direction: column; align-items: flex-start; }
    .gep-purchases-grid { grid-template-columns: 1fr; }
    .purchases-summary-pills { width: 100%; }
    .summary-pill { flex: 1; }
}
</style>


