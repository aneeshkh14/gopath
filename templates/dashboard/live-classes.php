<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-live-sovereign gep-learning-catalog">
    <header class="gep-learning-header">
        <div class="hero-content">
            <p class="gep-learning-eyebrow">LEARN TOGETHER</p>
            <h1>Live classes</h1>
            <p class="hero-desc-premium">Find upcoming sessions, join your educators and catch up with recorded lessons.</p>

            <div class="hero-actions">
                <a href="#gep-upcoming-sessions" class="btn-action-elite btn-secondary">View schedule</a>
                <a href="<?php echo esc_url(add_query_arg('view', 'support', gep_get_url('dashboard'))); ?>" class="btn-action-elite btn-primary">Contact an educator</a>
            </div>
        </div>
    </header>

    <div class="gep-marketplace-premium">
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
            <h3 class="section-title" id="gep-upcoming-sessions" tabindex="-1">Upcoming sessions</h3>
            <div class="line-decorator"></div>
        </div>
        
        <div class="gep-premium-grid">
            <?php if ($upcoming): foreach($upcoming as $class): ?>
            <div class="gep-card-elite">
                <div class="card-media gep-class-media">
                    🎥
                </div>
                <div class="card-body">
                    <h4 class="card-title-sm"><?php echo esc_html($class->title); ?></h4>
                    <div class="instructor-row" style="margin-bottom: 15px;">
                        <span class="instructor-name">👨‍🏫 <?php echo esc_html($class->instructor); ?></span>
                    </div>
                    <div class="card-footer" style="padding: 15px;">
                        <span class="time-tag">📅 <?php echo date('j M, h:i A', strtotime($class->scheduled_at)); ?></span>
                        <a href="<?php echo esc_url($class->meeting_url); ?>" target="_blank" class="btn-link-action">Open class link ↗</a>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="gep-empty-state" style="grid-column: 1/-1;">
                    <div class="icon">🗓️</div>
                    <h3>No Sessions Today</h3>
                    <p>Check back later for updates on the broadcast schedule.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recorded Archives -->
        <div class="section-title-wrap" style="margin-top: 32px;">
            <h3 class="section-title">Recorded lessons</h3>
            <div class="line-decorator"></div>
        </div>
        
        <div class="gep-premium-grid">
            <?php if ($archives): foreach($archives as $archive): ?>
            <div class="gep-card-elite archive-card">
                <div class="card-media gep-class-media">
                    📽️
                    <div class="tag-elite">RECORDED</div>
                </div>
                <div class="card-body">
                    <h4 class="card-title-sm"><?php echo esc_html($archive->title); ?></h4>
                    <div class="instructor-row" style="margin-bottom: 15px;">
                        <span class="instructor-name">👨‍🏫 <?php echo esc_html($archive->instructor); ?></span>
                    </div>
                    <div class="card-footer" style="padding: 15px; display:flex; justify-content:center;">
                        <a href="<?php echo esc_url($archive->recording_url); ?>" target="_blank" class="btn-link-action" style="width:100%; text-align:center;">Watch Replay</a>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="gep-empty-state" style="grid-column: 1/-1;">
                    <div class="icon">📼</div>
                    <h3>No Archives Yet</h3>
                    <p>Recorded sessions will appear here after they conclude.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
