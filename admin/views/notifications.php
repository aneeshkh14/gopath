<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sovereign Notification Management Console
 */
$sent_notifications = GEP_Admin_Notifications::get_all_sent();
?>

<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow-sm);">
        <div>
            <h1 style="margin: 0; font-size: 24px; letter-spacing: -0.5px;">Neural Broadcast Intelligence</h1>
            <p style="margin: 4px 0 0; color: var(--admin-muted); font-weight: 600; font-size: 13px;">Strategize and deliver real-time notifications to your academic community.</p>
        </div>
    </div>

    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'broadcast_success' ) : ?>
        <div class="notice notice-success is-dismissible"><p>Broadcast alert delivered successfully.</p></div>
    <?php endif; ?>
    <?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'nonce' ) : ?>
        <div class="notice notice-error is-dismissible"><p>Security check failed. Please try again.</p></div>
    <?php endif; ?>

    <div class="gep-notification-manager" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
        <!-- Sent History -->
        <div class="gep-notification-list" style="background: #fff; padding: 30px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow);">
            <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px;">Broadcast History</h3>
            
            <?php if ( $sent_notifications ) : ?>
                <?php foreach ( $sent_notifications as $notif ) : ?>
                    <div class="gep-alert-item" style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: flex-start; gap: 20px;">
                        <div style="background: #f1f5f9; padding: 12px; border-radius: 12px; font-size: 24px;">
                            <?php echo ( $notif->user_id == 0 ) ? '📢' : '👤'; ?>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="margin: 0; font-weight: 800; font-size: 16px;"><?php echo esc_html( $notif->title ); ?></h4>
                            <p style="margin: 8px 0; font-size: 14px; color: var(--admin-text); line-height: 1.5;"><?php echo esc_html( $notif->message ); ?></p>
                            <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                                <span style="font-size: 11px; color: var(--admin-muted); font-weight: 600;">
                                    🕒 <?php echo date( 'M j, Y - g:i a', strtotime( $notif->created_at ) ); ?>
                                </span>
                                <span class="status-badge" style="background: #dcfce7; color: #166534;">
                                    <?php echo ( $notif->user_id == 0 ) ? 'GLOBAL' : 'DIRECT'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align: center; padding: 50px 0;">
                    <span style="font-size: 40px; opacity: 0.3;">📭</span>
                    <p style="color: var(--admin-muted); margin-top: 15px;">No broadcast history found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Compose Sidebar -->
        <div class="gep-notification-sidebar">
            <div class="gep-admin-console" style="padding: 30px;">
                <h3 style="margin-top: 0; font-weight: 900; margin-bottom: 25px;">New Broadcast</h3>
                <form action="" method="post">
                    <?php wp_nonce_field( 'gep_broadcast_notification', 'gep_nonce' ); ?>
                    <input type="hidden" name="gep_action" value="broadcast_notification">
                    
                    <div class="gep-form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Alert Title</label>
                        <input type="text" name="title" class="widefat" placeholder="e.g. UPSC Mains Schedule Out" required>
                    </div>

                    <div class="gep-form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Target Audience</label>
                        <select name="target" class="widefat">
                            <option value="all">All Registered Students</option>
                            <option value="premium">Premium Pass Holders (Mock)</option>
                        </select>
                    </div>

                    <div class="gep-form-group" style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 700; font-size: 12px; color: var(--admin-muted); text-transform: uppercase;">Message Content</label>
                        <textarea name="message" class="widefat" rows="5" placeholder="Write your announcement here..." required></textarea>
                    </div>

                    <button type="submit" class="button button-primary widefat" style="height: 50px; border-radius: 12px; font-weight: 800; font-size: 14px;">
                        🚀 Send Broadcast Now
                    </button>
                </form>
            </div>
            
            <div class="gep-card gep-glass" style="margin-top: 25px; padding: 20px; border-radius: 20px;">
                <h4 style="margin: 0; font-weight: 800;">Notification Intelligence</h4>
                <p style="font-size: 12px; color: var(--admin-muted); margin-top: 10px; line-height: 1.6;">
                    Notifications sent to "All Students" will appear instantly on their student dashboard. Direct messages are tracked for "Read" status.
                </p>
            </div>
        </div>
    </div>
</div>
