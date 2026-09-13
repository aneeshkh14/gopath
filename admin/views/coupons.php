<div class="wrap gep-admin-wrap">
    <div class="gep-admin-header" style="margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin: 0;">Discount & Revenue Shields</h1>
            <p style="color: var(--admin-muted); font-weight: 600;">Manage promotional codes and student acquisition campaigns.</p>
        </div>
        <button onclick="document.getElementById('add-coupon-modal').style.display='flex'" class="button button-primary" style="height: 50px; border-radius: 12px; padding: 0 30px; font-weight: 800; font-size: 14px;">+ Generate Coupon</button>
    </div>

    <!-- Generate Coupon Modal -->
    <div id="add-coupon-modal" class="gep-modal-overlay">
        <div class="gep-modal-content" style="width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h2 style="margin: 0; font-weight: 900; font-size: 22px;">Generate Revenue Shield</h2>
                <button onclick="document.getElementById('add-coupon-modal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--admin-muted);">&times;</button>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('gep_coupon_action', 'gep_coupon_nonce'); ?>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Coupon Identity (Code)</label>
                    <input type="text" name="code" placeholder="e.g. WELCOME10" required style="font-family: monospace; font-size: 18px; font-weight: 900; text-transform: uppercase;">
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Benefit Value</label>
                        <input type="number" name="value" placeholder="10" required>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Logic Type</label>
                        <select name="type">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed (INR)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Usage Velocity (Limit)</label>
                    <input type="number" name="usage_limit" value="100" placeholder="0 for unlimited">
                </div>

                <div style="margin-bottom: 35px;">
                    <label style="display: block; font-weight: 800; font-size: 11px; color: var(--admin-muted); margin-bottom: 10px; text-transform: uppercase;">Expiration Term</label>
                    <input type="date" name="expiry_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>

                <button type="submit" name="gep_add_coupon" class="button button-primary" style="width: 100%; height: 55px; border-radius: 14px; font-weight: 900; font-size: 16px;">🚀 Authorize Coupon</button>
            </form>
        </div>
    </div>

    <div class="gep-admin-table-container">
        <table class="gep-admin-table">
            <thead>
                <tr>
                    <th>Coupon Code</th>
                    <th>Discount Logic</th>
                    <th>Benefit Value</th>
                    <th>Velocity (Usage)</th>
                    <th>Term / Expiry</th>
                    <th style="text-align: right;">Shield Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                global $wpdb;
                $coupons = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gep_coupons ORDER BY id DESC" );
                if ( $coupons ) :
                    foreach ( $coupons as $c ) : ?>
                        <tr>
                            <td>
                                <strong class="row-title" style="font-family: monospace; font-size: 16px; letter-spacing: 1px; color: var(--admin-primary);"><?php echo esc_html( $c->code ); ?></strong>
                                <div class="row-actions">
                                    <a href="#">Audit Trail</a>
                                    <a href="#" class="delete">Deactivate</a>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: var(--admin-muted);"><?php echo strtoupper($c->type); ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 900; font-size: 16px; color: #10b981;">
                                    <?php echo $c->type === 'percent' ? $c->value . '%' : '₹' . number_format($c->value, 0); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; background: #f1f5f9; height: 6px; border-radius: 3px; max-width: 60px;">
                                        <?php 
                                        $percent = $c->usage_limit ? ($c->used_count / $c->usage_limit) * 100 : 0;
                                        ?>
                                        <div style="width: <?php echo min(100, $percent); ?>%; background: var(--admin-primary); height: 100%; border-radius: 3px;"></div>
                                    </div>
                                    <span style="font-weight: 800; font-size: 12px; color: #64748b;"><?php echo $c->used_count; ?> / <?php echo $c->usage_limit ?: '∞'; ?></span>
                                </div>
                            </td>
                            <td style="color: var(--admin-muted); font-weight: 700;">
                                <?php echo $c->expiry_date ? date('M d, Y', strtotime($c->expiry_date)) : '<span style="color: #cbd5e1;">Perpetual</span>'; ?>
                            </td>
                            <td style="text-align: right;">
                                <span class="status-badge" style="background: <?php echo $c->status === 'active' ? '#dcfce7; color: #15803d;' : '#f1f5f9; color: #64748b;'; ?>; font-weight: 900; letter-spacing: 0.5px;">
                                    <?php echo strtoupper($c->status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div style="font-size: 40px; margin-bottom: 15px;">🎟️</div>
                            <h3>No active promotions</h3>
                            <p>Boost your sales by creating your first discount coupon code.</p>
                            <button type="button" class="button button-primary" onclick="document.getElementById('add-coupon-modal').style.display='flex'">Create First Coupon</button>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
