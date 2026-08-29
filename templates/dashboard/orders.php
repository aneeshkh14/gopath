<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-orders-container">
    <h2>Transaction History</h2>
    <table class="gep-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Test Name</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            global $wpdb;
            $orders = $wpdb->get_results( $wpdb->prepare( "SELECT o.*, t.title as test_name FROM {$wpdb->prefix}gep_orders o JOIN {$wpdb->prefix}gep_tests t ON o.test_id = t.id WHERE o.user_id = %d ORDER BY o.created_at DESC", get_current_user_id() ) );
            if ( $orders ) :
                foreach ( $orders as $order ) : ?>
                    <tr>
                        <td><?php echo esc_html( $order->razorpay_order_id ); ?></td>
                        <td><?php echo esc_html( $order->test_name ); ?></td>
                        <td>₹<?php echo number_format( $order->amount, 2 ); ?></td>
                        <td>
                            <span class="gep-status-badge <?php echo $order->status; ?>">
                                <?php echo ucfirst($order->status); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M, Y', strtotime($order->created_at)); ?></td>
                    </tr>
                <?php endforeach;
            else : ?>
                <tr><td colspan="5">No transactions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
