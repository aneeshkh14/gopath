<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-payment-status failed">
    <div class="gep-status-icon">❌</div>
    <h2>Payment Failed</h2>
    <p>Unfortunately, your transaction could not be processed at this time. Please try again or contact support if the issue persists.</p>
    <div class="gep-error-info">
        <p>Error Code: <?php echo esc_html(isset($_GET['error_code']) ? $_GET['error_code'] : 'Unknown'); ?></p>
        <p>Reason: <?php echo esc_html(isset($_GET['error_desc']) ? $_GET['error_desc'] : 'Transaction Declined'); ?></p>
    </div>
    <div class="gep-actions">
        <a href="javascript:history.back()" class="gep-btn gep-btn-primary">Try Again</a>
        <a href="<?php echo gep_get_url('dashboard'); ?>" class="gep-btn gep-btn-outline">Back to Dashboard</a>
    </div>
</div>

