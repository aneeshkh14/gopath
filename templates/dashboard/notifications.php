<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-notifications-center-wrapper">
    <div class="gep-section-header">
        <div class="header-title-wrap">
            <h3>Notifications</h3>
            <p class="section-subtitle">Real-time alerts, system updates, and exam notices</p>
        </div>
        <?php if ( ! empty( $notifications ) ) : ?>
            <button id="gep-mark-all-read" class="gep-btn-sovereign-action">
                <span class="icon">✓</span> Mark all as read
            </button>
        <?php endif; ?>
    </div>

    <div class="gep-notifications-hub gep-glass">
        <?php if ( ! empty( $notifications ) ) : ?>
            <div class="gep-notification-list">
                <?php foreach ( $notifications as $notif ) : ?>
                    <div class="gep-notification-item <?php echo $notif->is_read ? '' : 'unread'; ?>" data-id="<?php echo $notif->id; ?>">
                        <div class="notif-badge-icon">
                            <span class="icon"><?php echo $notif->is_read ? '🔔' : '⚡'; ?></span>
                        </div>
                        <div class="notif-details">
                            <div class="notif-meta-row">
                                <span class="notif-category">SYSTEM PROTOCOL</span>
                                <span class="notif-timestamp"><?php echo human_time_diff( strtotime( $notif->created_at ), current_datetime()->getTimestamp() ); ?> ago</span>
                            </div>
                            <h4 class="notif-item-title"><?php echo esc_html( $notif->title ); ?></h4>
                            <p class="notif-item-message"><?php echo esc_html( $notif->message ); ?></p>
                        </div>
                        <?php if ( ! $notif->is_read ) : ?>
                            <span class="notif-pulse-dot" title="Unread Alert"></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="gep-notifications-empty-state">
                <div class="empty-icon-box">📭</div>
                <h4>Sovereign Calm</h4>
                <p>Your intelligence feed is clear. There are no pending alerts or broadcasts in your console.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.gep-notifications-center-wrapper {
    max-width: 1000px;
    margin: 0 auto;
    padding: 10px 0;
}

.header-title-wrap h3 {
    margin: 0 0 4px 0;
    font-size: 26px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.8px;
}

.section-subtitle {
    margin: 0;
    font-size: 14px;
    color: #64748b;
    font-weight: 550;
}

.gep-btn-sovereign-action {
    background: rgba(37, 99, 235, 0.08);
    color: var(--gep-primary, #1a73e8);
    border: 1px solid rgba(37, 99, 235, 0.15);
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 750;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.gep-btn-sovereign-action:hover {
    background: var(--gep-primary, #1a73e8);
    color: #fff;
    border-color: var(--gep-primary, #1a73e8);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26, 115, 232, 0.2);
}

.gep-notifications-hub {
    background: #ffffff;
    border: 1px solid var(--gep-border, #e2e8f0);
    border-radius: 24px;
    padding: 8px;
    box-shadow: var(--gep-shadow-premium, 0 20px 40px rgba(0,0,0,0.04));
    overflow: hidden;
    margin-top: 25px;
}

.gep-notification-list {
    display: flex;
    flex-direction: column;
}

.gep-notification-item {
    display: flex;
    gap: 20px;
    padding: 24px;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    cursor: pointer;
    transition: all 0.25s ease;
    align-items: flex-start;
}

.gep-notification-item:last-child {
    border-bottom: none;
}

.gep-notification-item:hover {
    background: #f8fafc;
}

.gep-notification-item.unread {
    background: rgba(26, 115, 232, 0.02);
}

.gep-notification-item.unread:hover {
    background: rgba(26, 115, 232, 0.04);
}

.notif-badge-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.gep-notification-item.unread .notif-badge-icon {
    background: rgba(26, 115, 232, 0.1);
    color: var(--gep-primary, #1a73e8);
}

.notif-badge-icon .icon {
    font-size: 20px;
}

.notif-details {
    flex-grow: 1;
}

.notif-meta-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.notif-category {
    font-size: 10px;
    font-weight: 850;
    color: #94a3b8;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.gep-notification-item.unread .notif-category {
    color: var(--gep-primary, #1a73e8);
}

.notif-timestamp {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 600;
}

.notif-item-title {
    margin: 0 0 6px 0;
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.3px;
}

.gep-notification-item.unread .notif-item-title {
    color: #0f172a;
}

.notif-item-message {
    margin: 0;
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
    font-weight: 500;
}

.gep-notification-item.unread .notif-item-message {
    color: #334155;
}

.notif-pulse-dot {
    position: absolute;
    top: 24px;
    right: 24px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #1a73e8;
    box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.2);
    animation: gep-pulse 2s infinite;
}

.gep-notifications-empty-state {
    padding: 80px 40px;
    text-align: center;
    max-width: 450px;
    margin: 0 auto;
}

.empty-icon-box {
    font-size: 64px;
    margin-bottom: 20px;
    animation: gep-float 4s ease-in-out infinite;
}

.gep-notifications-empty-state h4 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 900;
    color: #0f172a;
}

.gep-notifications-empty-state p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
    line-height: 1.6;
    font-weight: 550;
}

@keyframes gep-pulse {
    0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(26, 115, 232, 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(26, 115, 232, 0); }
    100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(26, 115, 232, 0); }
}

@keyframes gep-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@media (max-width: 640px) {
    .gep-notification-item {
        padding: 16px;
        gap: 12px;
    }
    .notif-badge-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
    }
    .notif-badge-icon .icon {
        font-size: 16px;
    }
    .notif-item-title {
        font-size: 14px;
    }
    .notif-item-message {
        font-size: 13px;
    }
}
</style>
