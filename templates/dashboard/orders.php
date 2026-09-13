<?php if ( ! defined( 'ABSPATH' ) ) exit;
$orders = (new GEP_Payment())->get_user_orders(get_current_user_id());
$status_labels = array('success' => 'Completed', 'pending' => 'Pending confirmation', 'failed' => 'Failed');
?>
<div class="gep-orders-container">
    <h2>Transaction History</h2>
    <p>Tests, courses and passes, including free enrollments. If a charged payment is still pending, contact support with the reference below.</p>
    <div role="region" aria-label="Transaction history" tabindex="0" style="max-width:100%;overflow-x:auto;">
    <table class="gep-table">
        <thead><tr>
            <th scope="col">Reference</th><th scope="col">Purchase</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Date</th>
        </tr></thead>
        <tbody>
        <?php foreach ( $orders as $order ) :
            $status = isset($status_labels[$order->status]) ? $status_labels[$order->status] : 'Unknown';
        ?>
            <tr>
                <td><?php echo esc_html(!empty($order->razorpay_payment_id) ? $order->razorpay_payment_id : (!empty($order->razorpay_order_id) ? $order->razorpay_order_id : 'Order #' . $order->id)); ?></td>
                <td><?php echo esc_html($order->item_title); ?></td>
                <td>₹<?php echo number_format((float)$order->amount, 2); ?></td>
                <td><span class="gep-status-badge <?php echo esc_attr($order->status); ?>"><?php echo esc_html($status); ?></span></td>
                <td><?php echo esc_html(date('d M, Y', strtotime($order->created_at))); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ( !$orders ) : ?><tr><td colspan="5">No transactions yet. Your enrollments will appear here.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
    <p><a href="<?php echo esc_url(add_query_arg('view', 'support', gep_get_url('dashboard'))); ?>">Contact support</a> · <a href="<?php echo esc_url(add_query_arg('view', 'tests', gep_get_url('dashboard'))); ?>">Browse tests</a></p>
</div>
