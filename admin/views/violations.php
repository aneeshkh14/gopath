<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: #fff; padding: 15px 25px; border-radius: 20px; border: 1px solid var(--admin-border); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);">
        <div>
            <h1 style="margin: 0; font-size: 28px; letter-spacing: -1px;">Integrity Intelligence Monitor</h1>
            <p style="margin: 5px 0 0; color: var(--admin-muted); font-weight: 600;">Detect and audit unauthorized student activity during active exam sessions.</p>
        </div>
        <div style="display: flex; gap: 15px;">
            <div style="background: #fff; border: 2px solid #fee2e2; color: #ef4444; padding: 12px 25px; border-radius: 12px; font-weight: 900; font-size: 13px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);" onmouseover="this.style.background='#ef4444'; this.style.color='#fff'; this.style.borderColor='#ef4444';" onmouseout="this.style.background='#fff'; this.style.color='#ef4444'; this.style.borderColor='#fee2e2';" onclick="if(confirm('🚨 CRITICAL: Are you sure you want to purge all security logs? This cannot be undone.')) { window.location.href='<?php echo esc_url( admin_url('admin.php?page=gep-violations&action=purge_violations&nonce=' . wp_create_nonce('gep_purge_violations') ) ); ?>'; }">
                <span style="font-size: 18px;">🗑️</span> PURGE ALL INTELLIGENCE
            </div>
        </div>
    </div>
    
    <?php if ( isset( $_GET['message'] ) && $_GET['message'] === 'purged' ) : ?>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 15px 20px; border-radius: 12px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">✅</span> Intelligence logs successfully purged.
        </div>
    <?php endif; ?>
    <?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'nonce' ) : ?>
        <div style="background: #fdf2f2; border: 1px solid #fde8e8; color: #9b1c1c; padding: 15px 20px; border-radius: 12px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">❌</span> Security check failed. Please try again.
        </div>
    <?php endif; ?>

    <div class="gep-admin-recent-activity" style="margin-bottom: 25px; padding: 20px; border-radius: 20px;">
        <form method="get" action="" style="display: flex; gap: 20px; align-items: center;">
            <input type="hidden" name="page" value="gep-violations">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="background: #f1f5f9; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px;">🔍</span>
                <label style="font-weight: 800; color: #334155; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Audit Protocol Filter</label>
            </div>
            <select name="v_type" style="height: 45px !important; line-height: 41px !important; border-radius: 12px !important; border: 2px solid #e2e8f0 !important; padding: 0 35px 0 15px !important; font-weight: 600 !important; min-width: 260px !important; background: #fff url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'16\' height=\'16\' fill=\'%2364748b\' viewBox=\'0 0 24 24\'><path d=\'M7 10l5 5 5-5z\'/></svg>') no-repeat right 15px center / 16px !important; appearance: none !important; -webkit-appearance: none !important; -moz-appearance: none !important; vertical-align: middle !important; box-sizing: border-box !important;">
                <option value="">All Security Events</option>
                <option value="tab_switch" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'tab_switch' ); ?>>Tab Switching / Loss of Focus</option>
                <option value="fullscreen_exit" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'fullscreen_exit' ); ?>>Fullscreen Termination</option>
                <option value="prohibited_key_combo" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'prohibited_key_combo' ); ?>>Prohibited Key Combination</option>
                <option value="dev_tools_attempt" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'dev_tools_attempt' ); ?>>Unauthorized Console Access</option>
                <option value="dev_tools_detected" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'dev_tools_detected' ); ?>>Console / DevTools Detected</option>
                <option value="screenshot_attempt" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'screenshot_attempt' ); ?>>Visual Data Extraction</option>
                <option value="back_button_attempt" <?php selected( isset($_GET['v_type']) ? $_GET['v_type'] : '', 'back_button_attempt' ); ?>>Back Navigation Attempt</option>
            </select>
            <button type="submit" class="button button-primary" style="height: 45px !important; line-height: 41px !important; border-radius: 12px !important; padding: 0 25px !important; font-weight: 800 !important; font-size: 13px !important; vertical-align: middle !important; display: inline-flex !important; align-items: center !important; justify-content: center !important;">Filter Logs</button>
        </form>
    </div>

    <div class="gep-admin-table-container" style="border-radius: 24px; overflow: hidden; border: 1px solid #e2e8f0;">
        <table class="gep-admin-table" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th style="width: 250px;">Student Identity</th>
                    <th style="width: 150px;">Session ID</th>
                    <th>Violation Protocol Breach</th>
                    <th style="width: 150px;">Frequency</th>
                    <th style="width: 220px;">Security Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $where = "";
                if ( isset($_GET['v_type']) && !empty($_GET['v_type']) ) {
                    $where = $wpdb->prepare( "WHERE v.violation_type = %s", sanitize_text_field($_GET['v_type']) );
                }
                
                $logs = $wpdb->get_results( "SELECT v.*, u.display_name FROM {$wpdb->prefix}gep_violations v JOIN {$wpdb->users} u ON v.user_id = u.ID $where ORDER BY v.timestamp DESC LIMIT 100" );
                if ( $logs ) :
                    foreach ( $logs as $log ) : ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 12px; color: #ef4444;">
                                        <?php echo strtoupper(substr($log->display_name, 0, 1)); ?>
                                    </div>
                                    <strong style="font-size: 14px; color: #0f172a;"><?php echo esc_html( $log->display_name ); ?></strong>
                                </div>
                            </td>
                            <td style="font-family: monospace; font-weight: 800; color: #64748b; font-size: 13px;">#<?php echo $log->attempt_id; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="width: 6px; height: 6px; background: #ef4444; border-radius: 50%;"></span>
                                    <span class="status-badge" style="background: #fee2e2; color: #b91c1c; font-weight: 900; letter-spacing: 0.5px; border: 1px solid #fecaca;">
                                        <?php echo strtoupper(str_replace('_', ' ', $log->violation_type)); ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: baseline; gap: 5px;">
                                    <span style="font-size: 20px; font-weight: 900; color: #0f172a;"><?php echo $log->count; ?></span>
                                    <span style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase;"><?php echo $log->count == 1 ? 'Incident' : 'Incidents'; ?></span>
                                </div>
                            </td>
                            <td style="color: #64748b; font-weight: 700; font-size: 12px;">
                                <?php echo date( 'M d, Y', strtotime($log->timestamp) ); ?>
                                <span style="display: block; font-size: 11px; color: #cbd5e1;"><?php echo date( 'H:i:s', strtotime($log->timestamp) ); ?> IST</span>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="5" class="empty-state" style="padding: 100px 50px;">
                            <div style="font-size: 60px; margin-bottom: 25px;">🛡️</div>
                            <h3 style="font-size: 24px; font-weight: 900; color: #0f172a; margin-bottom: 10px;">Security Shields are Holding</h3>
                            <p style="color: #64748b; font-weight: 600; max-width: 400px; margin: 0 auto 30px;">All active exam sessions are currently operating within authorized integrity protocols. No breaches detected.</p>
                            <div style="display: flex; gap: 15px; justify-content: center;">
                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 10px 20px; border-radius: 12px; font-size: 12px; font-weight: 800; color: #166534;">
                                    REAL-TIME MONITOR: <span style="color: #10b981;">ACTIVE</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
