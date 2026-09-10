<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$user_id = get_current_user_id();
$pass_expiry = get_user_meta( $user_id, 'gep_pass_expiry', true );
$is_active = $pass_expiry && strtotime( $pass_expiry ) > current_time( 'timestamp' );
?>

<div class="gep-main-inner" style="font-family: 'Inter', system-ui, sans-serif; padding-bottom: 80px;">
    <!-- Pass Banner -->
    <div style="background: linear-gradient(135deg, #1e1b4b, #311042); border-radius: 24px; padding: 45px; color: #fff; margin-bottom: 40px; position: relative; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="position: absolute; right: -50px; bottom: -50px; font-size: 180px; opacity: 0.08; transform: rotate(-10deg);">🎟️</div>
        <div style="position: relative; z-index: 1; max-width: 600px;">
            <span style="font-size: 11px; font-weight: 800; background: rgba(99, 102, 241, 0.25); border: 1px solid rgba(99, 102, 241, 0.35); color: #c7d2fe; padding: 5px 12px; border-radius: 8px; text-transform: uppercase; letter-spacing: 1px; display: inline-block; margin-bottom: 15px;">
                <?php _e( 'GoPath Premium Pass', 'gopath-exam-portal' ); ?>
            </span>
            <h2 style="font-size: 38px; font-weight: 950; margin: 0 0 12px; letter-spacing: -1.5px; line-height: 1.1; color: #fff;">
                <?php printf( __( 'Unlock All %s', 'gopath-exam-portal' ), '<span style="color: #a5b4fc;">' . __( 'Mock Tests &amp; PYQs', 'gopath-exam-portal' ) . '</span>' ); ?>
            </h2>
            <p style="font-size: 16px; color: #cbd5e1; font-weight: 600; line-height: 1.6; margin: 0 0 10px;">
                <?php _e( 'Get complete access to all test series, typing assessments, chapter tests, and analytics with a single, affordable pass. No separate purchases needed!', 'gopath-exam-portal' ); ?>
            </p>
        </div>
    </div>

    <?php if ( $is_active ) : ?>
        <!-- Active Subscription Badge -->
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 20px; padding: 25px; display: flex; align-items: center; gap: 20px; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);">
            <div style="font-size: 40px; background: #d1fae5; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 15px;">⚡</div>
            <div>
                <h3 style="margin: 0 0 4px; font-size: 18px; font-weight: 900; color: #065f46;"><?php _e( 'Premium Pass Active!', 'gopath-exam-portal' ); ?></h3>
                <p style="margin: 0; color: #047857; font-weight: 600; font-size: 14px;">
                    <?php _e( 'Your subscription is fully active. You have full access to all resources.', 'gopath-exam-portal' ); ?> 
                    <strong style="color: #065f46; margin-left: 5px;"><?php _e( 'Valid until:', 'gopath-exam-portal' ); ?> <?php echo date('M j, Y h:i A', strtotime($pass_expiry)); ?></strong>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pricing Grid -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px;">
        
        <!-- 1 Month Card -->
        <div class="gep-pricing-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 35px; display: flex; flex-direction: column; transition: all 0.3s; position: relative;">
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 900; color: #0f172a;"><?php _e( '1-Month Pass', 'gopath-exam-portal' ); ?></h3>
            <p style="margin: 0 0 25px; color: #64748b; font-size: 13px; font-weight: 600; min-height: 40px;"><?php _e( 'Ideal for quick, short-term practice sessions before exams.', 'gopath-exam-portal' ); ?></p>
            
            <div style="margin-bottom: 30px;">
                <span style="font-size: 36px; font-weight: 950; color: #0f172a;">₹99</span>
                <span style="color: #94a3b8; font-size: 14px; font-weight: 700;">/ <?php _e( 'Month', 'gopath-exam-portal' ); ?></span>
            </div>
            
            <ul style="list-style: none; padding: 0; margin: 0 0 35px; display: flex; flex-direction: column; gap: 12px; font-size: 13px; font-weight: 600; color: #475569;">
                <li>✓ <?php _e( 'Full access to all Exams &amp; PYQs', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Bilingual support (English &amp; Hindi)', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Core Dashboard Analytics', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Doubt solver access', 'gopath-exam-portal' ); ?></li>
                <li style="color: #94a3b8; text-decoration: line-through;">✗ <?php _e( 'Extra test attempts allocation', 'gopath-exam-portal' ); ?></li>
            </ul>

            <a href="<?php echo add_query_arg( array('id' => 1, 'type' => 'pass'), (string) gep_get_url('checkout') ); ?>" style="margin-top: auto; display: block; text-align: center; background: #f1f5f9; color: #1e293b; border-radius: 14px; padding: 14px 20px; font-weight: 800; text-decoration: none; transition: all 0.2s;">
                <?php _e( 'Buy 1-Month Pass', 'gopath-exam-portal' ); ?>
            </a>
        </div>

        <!-- Yearly Card (Recommended) -->
        <div class="gep-pricing-card" style="background: #fff; border: 2px solid #6366f1; border-radius: 24px; padding: 35px; display: flex; flex-direction: column; transition: all 0.3s; position: relative; box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.08);">
            <div style="position: absolute; top: -15px; right: 25px; background: #4f46e5; color: #fff; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; padding: 6px 12px; border-radius: 20px; border: 2px solid #fff;">
                <?php _e( 'Best Value', 'gopath-exam-portal' ); ?>
            </div>
            
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 900; color: #0f172a;"><?php _e( 'Yearly Pass Pro', 'gopath-exam-portal' ); ?></h3>
            <p style="margin: 0 0 25px; color: #64748b; font-size: 13px; font-weight: 600; min-height: 40px;"><?php _e( 'Most popular option for students looking to prep for multiple exams.', 'gopath-exam-portal' ); ?></p>
            
            <div style="margin-bottom: 30px;">
                <span style="font-size: 36px; font-weight: 950; color: #0f172a;">₹299</span>
                <span style="color: #94a3b8; font-size: 14px; font-weight: 700;">/ <?php _e( 'Year', 'gopath-exam-portal' ); ?></span>
                <span style="display: block; font-size: 11px; color: #10b981; font-weight: 800; margin-top: 5px;"><?php _e( 'Save 75% compared to Monthly', 'gopath-exam-portal' ); ?></span>
            </div>
            
            <ul style="list-style: none; padding: 0; margin: 0 0 35px; display: flex; flex-direction: column; gap: 12px; font-size: 13px; font-weight: 600; color: #475569;">
                <li>✓ <?php _e( 'Full access to all Exams &amp; PYQs', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Bilingual support (English &amp; Hindi)', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Core Dashboard Analytics', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Doubt solver access', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Unlimited extra attempts', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Priority support &amp; assistance', 'gopath-exam-portal' ); ?></li>
            </ul>

            <a href="<?php echo add_query_arg( array('id' => 2, 'type' => 'pass'), (string) gep_get_url('checkout') ); ?>" style="margin-top: auto; display: block; text-align: center; background: #6366f1; color: #fff; border-radius: 14px; padding: 14px 20px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 10px rgba(99,102,241,0.25); transition: all 0.2s;">
                <?php _e( 'Buy Yearly Pass Pro', 'gopath-exam-portal' ); ?>
            </a>
        </div>

        <!-- Lifetime Card -->
        <div class="gep-pricing-card" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 35px; display: flex; flex-direction: column; transition: all 0.3s; position: relative;">
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 900; color: #0f172a;"><?php _e( 'Lifetime Pass Ultimate', 'gopath-exam-portal' ); ?></h3>
            <p style="margin: 0 0 25px; color: #64748b; font-size: 13px; font-weight: 600; min-height: 40px;"><?php _e( 'Never pay again. Access all future mock exams, courses, and updates.', 'gopath-exam-portal' ); ?></p>
            
            <div style="margin-bottom: 30px;">
                <span style="font-size: 36px; font-weight: 950; color: #0f172a;">₹599</span>
                <span style="color: #94a3b8; font-size: 14px; font-weight: 700;">/ <?php _e( 'Forever', 'gopath-exam-portal' ); ?></span>
            </div>
            
            <ul style="list-style: none; padding: 0; margin: 0 0 35px; display: flex; flex-direction: column; gap: 12px; font-size: 13px; font-weight: 600; color: #475569;">
                <li>✓ <?php _e( 'Full access to all current Exams &amp; PYQs', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Unlocks all FUTURE added mock tests', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Bilingual support (English &amp; Hindi)', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Full Core Dashboard Analytics', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Priority VIP teacher support', 'gopath-exam-portal' ); ?></li>
                <li>✓ <?php _e( 'Lifetime valid - never expires', 'gopath-exam-portal' ); ?></li>
            </ul>

            <a href="<?php echo add_query_arg( array('id' => 3, 'type' => 'pass'), (string) gep_get_url('checkout') ); ?>" style="margin-top: auto; display: block; text-align: center; background: #0f172a; color: #fff; border-radius: 14px; padding: 14px 20px; font-weight: 800; text-decoration: none; transition: all 0.2s;">
                <?php _e( 'Buy Lifetime Pass', 'gopath-exam-portal' ); ?>
            </a>
        </div>
    </div>
</div>

<style>
.gep-pricing-card:hover {
    transform: translateY(-5px);
    border-color: #cbd5e1 !important;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.04), 0 10px 10px -5px rgba(0,0,0,0.01) !important;
}
</style>
