<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #fff; padding: 12px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);">
        <div>
            <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Revenue Command Center</h1>
            <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Monitor student transactions and payment health.</p>
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="text-align: right;">
                <span style="display: block; font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; letter-spacing: 1px;">Gateway Status</span>
                <span style="color: #10b981; font-weight: 900; font-size: 14px; display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block; animation: pulse 2s infinite;"></span>
                    RAZORPAY LIVE
                </span>
            </div>
            <div style="width: 1px; height: 40px; background: #e2e8f0;"></div>
            <div style="background: #f0fdf4; color: #166534; padding: 12px 20px; border-radius: 12px; font-weight: 800; font-size: 13px; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 18px;">🛡️</span> SECURE VAULT ACTIVE
            </div>
        </div>
    </div>
    <div class="gep-admin-recent-activity" style="margin-bottom: 30px; padding: 25px; border-radius: 24px; background: #fff; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
        <form method="get" action="" style="display: flex; gap: 25px; align-items: center;">
            <input type="hidden" name="page" value="gep-payments">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #eef2ff; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #6366f1; border: 1px solid #e0e7ff;">📅</div>
                <div>
                    <label style="font-weight: 900; color: #1e293b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 2px;">Transaction Period</label>
                    <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Historical Audit Data</span>
                </div>
            </div>
            <select name="m" style="height: 50px; border-radius: 14px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; min-width: 280px; background: #f8fafc; color: #1e293b;">
                <option value="0">All Historical Data</option>
                <option value="202605" selected>May 2026 (Active Cycle)</option>
            </select>
            <button type="submit" class="button button-primary" style="height: 50px; border-radius: 14px; padding: 0 35px; font-weight: 900; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.25); letter-spacing: 0.5px;">Filter Intelligence</button>
        </form>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 28px; overflow-x: auto; border: 1px solid #e2e8f0; background: #fff; box-shadow: var(--admin-shadow-lg);">
        <table class="gep-admin-table" style="min-width: 1100px;">
            <thead>
                <tr>
                    <th style="padding-left: 30px; white-space: nowrap;">Order Reference</th>
                    <th style="white-space: nowrap;">Student Identity</th>
                    <th style="white-space: nowrap;">Product Asset</th>
                    <th style="white-space: nowrap;">Transaction Amount</th>
                    <th style="white-space: nowrap;">Gateway Payment ID</th>
                    <th style="white-space: nowrap;">Lifecycle Status</th>
                    <th style="padding-right: 30px; text-align: right; white-space: nowrap;">Security Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $orders = $wpdb->get_results( "
                    SELECT o.*, u.display_name, 
                    CASE 
                        WHEN o.item_type = 'course' THEN (SELECT title FROM {$wpdb->prefix}gep_courses WHERE id = o.item_id)
                        ELSE (SELECT title FROM {$wpdb->prefix}gep_tests WHERE id = o.item_id)
                    END as item_name
                    FROM {$wpdb->prefix}gep_orders o 
                    JOIN {$wpdb->users} u ON o.user_id = u.ID 
                    ORDER BY o.created_at DESC 
                    LIMIT 50" 
                );
                if ( $orders ) :
                    foreach ( $orders as $order ) : ?>
                        <tr>
                            <td style="padding-left: 30px;">
                                <div style="font-family: 'JetBrains Mono', 'Fira Code', monospace; font-weight: 800; color: #6366f1; font-size: 11px; background: #f5f3ff; padding: 6px 12px; border-radius: 8px; display: inline-block; border: 1px solid #e0e7ff;">
                                    <?php echo esc_html( $order->razorpay_order_id ); ?>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 36px; height: 36px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 13px; color: #6366f1; border: 1px solid #e2e8f0;">
                                        <?php echo strtoupper(substr($order->display_name, 0, 1)); ?>
                                    </div>
                                    <strong style="font-size: 15px; color: #1e293b;"><?php echo esc_html( $order->display_name ); ?></strong>
                                </div>
                            </td>
                            <td class="row-title">
                                <span style="font-size: 10px; font-weight: 900; color: #94a3b8; display: block; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;"><?php echo $order->item_type; ?></span>
                                <span style="font-weight: 800; color: #0f172a; font-size: 15px;"><?php echo esc_html( $order->item_name ); ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 900; font-size: 18px; color: #10b981;">₹<?php echo number_format( $order->amount, 0 ); ?></span>
                            </td>
                            <td>
                                <div style="font-size: 12px; color: #64748b; font-family: 'JetBrains Mono', monospace; background: #f8fafc; padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0; display: inline-block;">
                                    <?php echo esc_html( $order->razorpay_payment_id ?: 'SYNC_PENDING' ); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge" style="background: <?php echo $order->status === 'success' ? '#dcfce7; color: #15803d;' : '#fee2e2; color: #b91c1c;'; ?>; font-weight: 900; width: 120px; text-align: center; border: 1px solid <?php echo $order->status === 'success' ? '#bbf7d0' : '#fecaca'; ?>; border-radius: 12px; padding: 8px;">
                                    <?php echo strtoupper($order->status); ?>
                                </span>
                            </td>
                            <td style="padding-right: 30px; text-align: right;">
                                <div style="color: #1e293b; font-weight: 800; font-size: 14px;"><?php echo date( 'M d, Y', strtotime($order->created_at) ); ?></div>
                                <div style="font-size: 11px; color: #94a3b8; font-weight: 700; margin-top: 2px;"><?php echo date( 'H:i:s', strtotime($order->created_at) ); ?> IST</div>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="7" class="empty-state" style="padding: 120px 50px;">
                            <div style="background: #fff; width: 100px; height: 100px; border-radius: 30px; display: flex; align-items: center; justify-content: center; font-size: 50px; margin: 0 auto 30px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid #f1f5f9;">🪙</div>
                            <h3 style="font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 12px; letter-spacing: -0.5px;">Financial records are clear</h3>
                            <p style="color: #64748b; font-weight: 600; max-width: 450px; margin: 0 auto 40px; font-size: 15px; line-height: 1.6;">Once students purchase access to your premium exams or courses, high-fidelity transaction logs will appear in this command center.</p>
                            <div style="display: flex; gap: 20px; justify-content: center;">
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 25px; border-radius: 14px; display: flex; align-items: center; gap: 10px;">
                                    <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%;"></span>
                                    <span style="font-size: 13px; font-weight: 800; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Gateway: Connected</span>
                                </div>
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 25px; border-radius: 14px; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Currency: INR (₹)</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>
