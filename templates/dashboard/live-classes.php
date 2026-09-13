<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-live-sovereign">
    <!-- Premium Header -->
    <div class="gep-hero-premium-mini">
        <div class="mesh-bg"></div>
        <div class="hero-content">
            <h2 class="hero-title-premium-mini">
                <span class="text-white">Live</span> 
                <span class="text-gradient-gold">Academy</span>
            </h2>
            <p class="hero-desc-premium">Interact with top educators in real-time. Join the community of achievers.</p>
            
            <div class="hero-actions">
                <a href="#gep-upcoming-sessions" class="btn-action-elite btn-secondary">View schedule</a>
                <a href="<?php echo esc_url(add_query_arg('view', 'support', gep_get_url('dashboard'))); ?>" class="btn-action-elite btn-primary">Contact an educator</a>
            </div>
        </div>
    </div>

    <div class="gep-marketplace-premium" style="margin-top: 40px;">
        <!-- Strategic Live & Upcoming Data Ingestion -->
        <?php 
        global $wpdb;
        $now = current_time('mysql');
        $one_hour_ago = date('Y-m-d H:i:s', strtotime($now . ' -1 hour'));
        
        // 1. Detect "Live Now" session
        $live_now = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gep_live_classes 
             WHERE status = 'scheduled' 
             AND scheduled_at BETWEEN %s AND %s 
             ORDER BY scheduled_at ASC LIMIT 1",
            $one_hour_ago,
            date('Y-m-d H:i:s', strtotime($now . ' +15 minutes'))
        ));

        // 2. Fetch Upcoming Sessions
        $upcoming = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gep_live_classes 
             WHERE status = 'scheduled' 
             AND scheduled_at > %s 
             AND id != %d
             ORDER BY scheduled_at ASC LIMIT 6",
            $now,
            $live_now ? $live_now->id : 0
        ));

        // 3. Fetch Recorded Archives
        $archives = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}gep_live_classes 
             WHERE status = 'recorded' 
             ORDER BY scheduled_at DESC LIMIT 6"
        );
        ?>

        <!-- Live Now Highlight -->
        <?php if ($live_now): ?>
        <div class="gep-live-now-banner">
            <div class="pulse-ring"></div>
            <div class="live-content" style="<?php echo (isset($live_now->url_type) && $live_now->url_type === 'native') ? 'flex: 1;' : ''; ?>">
                <div class="live-badge">
                    <span class="dot"></span> LIVE NOW
                </div>
                <h3 class="live-title"><?php echo esc_html($live_now->title); ?></h3>
                <p class="live-meta">By <strong><?php echo esc_html($live_now->instructor); ?></strong> • Started at <?php echo date('h:i A', strtotime($live_now->scheduled_at)); ?></p>
                
                <?php if (isset($live_now->url_type) && $live_now->url_type === 'native' && !empty($live_now->meeting_url)): ?>
                    <div class="native-embed" style="margin-top: 20px; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                        <iframe title="Live class video" src="<?php echo esc_url($live_now->meeting_url); ?>" width="100%" height="450" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="display: block;"></iframe>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!isset($live_now->url_type) || $live_now->url_type !== 'native'): ?>
            <div class="live-action">
                <a href="<?php echo esc_url($live_now->meeting_url); ?>" target="_blank" class="btn-action-elite btn-primary">
                    Enter Classroom <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="live-bg-icon">🎬</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Upcoming Classes -->
        <div class="section-title-wrap">
            <h3 class="section-title" id="gep-upcoming-sessions" tabindex="-1">Broadcast Schedule</h3>
            <div class="line-decorator"></div>
        </div>
        
        <div class="gep-premium-grid">
            <?php if ($upcoming): foreach($upcoming as $class): ?>
            <div class="gep-card-elite">
                <div class="card-media" style="height: 140px; background: #0f172a; display:flex; align-items:center; justify-content:center; font-size: 40px;">
                    🎥
                </div>
                <div class="card-body">
                    <h4 class="card-title-sm"><?php echo esc_html($class->title); ?></h4>
                    <div class="instructor-row" style="margin-bottom: 15px;">
                        <span class="instructor-name">👨‍🏫 <?php echo esc_html($class->instructor); ?></span>
                    </div>
                    <div class="card-footer gep-glass-subtle" style="padding: 15px;">
                        <span class="time-tag">📅 <?php echo date('j M, h:i A', strtotime($class->scheduled_at)); ?></span>
                        <a href="<?php echo esc_url($class->meeting_url); ?>" target="_blank" class="btn-link-action">Open class link ↗</a>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="gep-empty-state gep-glass" style="grid-column: 1/-1;">
                    <div class="icon">🗓️</div>
                    <h3>No Sessions Today</h3>
                    <p>Check back later for updates on the broadcast schedule.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recorded Archives -->
        <div class="section-title-wrap" style="margin-top: 60px;">
            <h3 class="section-title">Recorded Archives</h3>
            <div class="line-decorator"></div>
        </div>
        
        <div class="gep-premium-grid">
            <?php if ($archives): foreach($archives as $archive): ?>
            <div class="gep-card-elite archive-card">
                <div class="card-media" style="height: 140px; background: #000; display:flex; align-items:center; justify-content:center; font-size: 40px; position:relative;">
                    📽️
                    <div class="tag-elite" style="background: rgba(255,255,255,0.1); color: #fff; border-color: rgba(255,255,255,0.2);">RECORDED</div>
                </div>
                <div class="card-body">
                    <h4 class="card-title-sm"><?php echo esc_html($archive->title); ?></h4>
                    <div class="instructor-row" style="margin-bottom: 15px;">
                        <span class="instructor-name">👨‍🏫 <?php echo esc_html($archive->instructor); ?></span>
                    </div>
                    <div class="card-footer gep-glass-subtle" style="padding: 15px; display:flex; justify-content:center;">
                        <a href="<?php echo esc_url($archive->recording_url); ?>" target="_blank" class="btn-link-action" style="width:100%; text-align:center;">Watch Replay</a>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="gep-empty-state gep-glass" style="grid-column: 1/-1;">
                    <div class="icon">📼</div>
                    <h3>No Archives Yet</h3>
                    <p>Recorded sessions will appear here after they conclude.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Reset & Base */
.gep-live-sovereign {
    background: #09090b;
    min-height: 100vh;
    padding-bottom: 120px;
    font-family: 'Inter', system-ui, sans-serif;
    color: #fff;
}

.gep-hero-premium-mini {
    position: relative;
    padding: 60px 40px 80px;
    background: #000;
    border-radius: 0 0 40px 40px;
    overflow: hidden;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.hero-title-premium-mini {
    font-size: 48px;
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -1.5px;
    margin-bottom: 16px;
}

.hero-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

/* Shared Premium Styles from SuperCoaching */
.mesh-bg { position: absolute; inset: 0; background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 60%); }
.text-white { color: #ffffff !important; }
.text-gradient-gold { background: linear-gradient(135deg, #fde047 0%, #d97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: inline-block; }
.hero-desc-premium { font-size: 18px; color: #a1a1aa; line-height: 1.6; max-width: 600px; margin: 0 auto; }
.gep-marketplace-premium { max-width: 1400px; margin: 0 auto; padding: 0 40px; position: relative; z-index: 10; }
.gep-premium-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 32px; }
.gep-card-elite { background: #18181b; border-radius: 24px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; flex-direction: column; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
.gep-card-elite:hover { transform: translateY(-5px); border-color: rgba(99, 102, 241, 0.3); box-shadow: 0 20px 40px rgba(99, 102, 241, 0.15); }
.card-media { position: relative; overflow: hidden; }
.tag-elite { position: absolute; top: 15px; right: 15px; padding: 4px 12px; border-radius: 20px; font-size: 10px; font-weight: 800; letter-spacing: 1px; }
.card-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; }
.card-title-sm { font-size: 18px; font-weight: 700; color: #fff; line-height: 1.4; margin-bottom: 15px; min-height: 50px; }
.instructor-row { display: flex; align-items: center; gap: 10px; }
.instructor-name { font-size: 12px; font-weight: 600; color: #a1a1aa; letter-spacing: 0.5px; }
.card-footer { margin-top: auto; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; }
.gep-glass-subtle { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.05); }
.gep-empty-state { padding: 80px 40px; border-radius: 40px; text-align: center; background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); }
.gep-empty-state .icon { font-size: 48px; margin-bottom: 20px; }
.gep-empty-state h3 { font-size: 24px; color: #fff; margin-bottom: 10px; }
.gep-empty-state p { color: #a1a1aa; }

.btn-action-elite { padding: 12px 24px; border-radius: 100px; font-size: 14px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; transition: all 0.3s; cursor: pointer; border: none; }
.btn-primary { background: #fff; color: #000 !important; }
.btn-secondary { background: rgba(255,255,255,0.1); color: #fff !important; border: 1px solid rgba(255,255,255,0.2); }
.btn-action-elite:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.btn-primary:hover { background: #f8fafc; }
.btn-secondary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }

/* Custom for Live */
.section-title-wrap { margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }
.section-title { font-size: 22px; font-weight: 800; color: #fff; margin: 0; }
.line-decorator { height: 1px; flex-grow: 1; background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, transparent 100%); }

/* #6366f1 is only 3.97:1 on the dark card (#18181b); #818cf8 is 5.94:1. */
.time-tag { font-size: 13px; font-weight: 600; color: #818cf8; }
.btn-link-action { font-size: 13px; font-weight: 700; color: #fff; text-decoration: none; padding: 6px 12px; background: rgba(255,255,255,0.1); border-radius: 8px; transition: background 0.2s; }
.btn-link-action:hover { background: rgba(255,255,255,0.2); }

/* Live Now Banner */
.gep-live-now-banner {
    background: linear-gradient(135deg, #ef4444 0%, #7f1d1d 100%);
    border-radius: 32px;
    padding: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 60px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(239, 68, 68, 0.2);
}

.pulse-ring {
    position: absolute;
    top: 50%;
    left: 50px;
    width: 20px;
    height: 20px;
    transform: translateY(-50%);
    background: #fff;
    border-radius: 50%;
    animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
    opacity: 0.2;
}

@keyframes ping {
    75%, 100% { transform: translateY(-50%) scale(5); opacity: 0; }
}

.live-content { position: relative; z-index: 2; }
.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    margin-bottom: 15px;
}
.live-badge .dot { width: 8px; height: 8px; background: #fff; border-radius: 50%; }
.live-title { font-size: 32px; font-weight: 900; margin: 0 0 10px; color: #fff; letter-spacing: -0.5px; }
.live-meta { font-size: 15px; color: rgba(255,255,255,0.9); margin: 0; }
.live-action { position: relative; z-index: 2; }
.live-bg-icon { position: absolute; right: -20px; bottom: -40px; font-size: 150px; opacity: 0.1; line-height: 1; pointer-events: none; }

@media (max-width: 768px) {
    .gep-live-now-banner { flex-direction: column; text-align: center; gap: 30px; padding: 30px 20px; }
    .pulse-ring { left: 50%; top: 30px; transform: translate(-50%, 0); }
    @keyframes ping { 75%, 100% { transform: translate(-50%, 0) scale(4); opacity: 0; } }
}

@media (max-width: 480px) {
    .gep-hero-premium-mini { padding: 40px 15px 60px; border-radius: 0 0 24px 24px; }
    .hero-title-premium-mini { font-size: 32px; }
    .hero-desc-premium { font-size: 14px; }
    .gep-marketplace-premium { padding: 0 15px; }
    .live-title { font-size: 24px; }
    .hero-actions { flex-direction: column; width: 100%; gap: 10px; }
    .hero-actions .btn-action-elite { width: 100%; justify-content: center; }
}
</style>
