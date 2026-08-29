<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-payment-status success">
    <div class="gep-status-icon">✅</div>
    <h2>Payment Successful!</h2>
    <p>Your transaction was completed successfully. You now have full access to the test.</p>
    <div class="gep-order-info">
        <p>Order ID: <?php echo esc_html($_GET['order_id']); ?></p>
        <p>Payment ID: <?php echo esc_html($_GET['payment_id']); ?></p>
    </div>
    <a href="<?php echo gep_get_url('dashboard'); ?>" class="gep-btn gep-btn-primary">Go to My Dashboard</a>
</div>
