<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb;

$course_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$lesson_id = isset($_GET['lesson']) ? absint($_GET['lesson']) : 0;

$course = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gep_courses WHERE id = %d", $course_id));
if (!$course) {
    echo '<div class="gep-error-screen"><h2>Course Unavailable</h2><p>Please return to the dashboard.</p></div>';
    return;
}

$dashboard = new GEP_Dashboard();
$lessons = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gep_lessons WHERE course_id = %d ORDER BY order_no ASC", $course_id));

if (!$lesson_id && !empty($lessons)) {
    $current_lesson = $lessons[0];
} else {
    $found_lessons = array_filter($lessons, function($l) use ($lesson_id) { return $l->id == $lesson_id; });
    $current_lesson = !empty($found_lessons) ? reset($found_lessons) : ( !empty($lessons) ? $lessons[0] : null );
}

$total_lessons = count($lessons);
$completed_map = get_user_meta( get_current_user_id(), 'gep_completed_lessons', true );
if ( ! is_array( $completed_map ) ) {
    $completed_map = array();
}
$completed_lesson_ids = isset( $completed_map[ $course_id ] ) ? $completed_map[ $course_id ] : array();
$completed_lessons = count( array_intersect( $completed_lesson_ids, wp_list_pluck( $lessons, 'id' ) ) );
$progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
?>

<div class="gep-cinema-container">
    <!-- Cinema Main: Player + Details -->
    <div class="gep-cinema-main" id="gep-cinema-main">
        <div class="gep-theater-wrap">
            <div class="theater-glow"></div>
            <div class="player-aspect-lock">
                <div class="player-viewport">
                    <?php if ($current_lesson): ?>
                        <?php if ($current_lesson->video_source === 'youtube'): ?>
                            <?php 
                            $video_url = $current_lesson->video_url;
                            $video_id = '';
                            if (strpos($video_url, 'watch?v=') !== false) {
                                parse_str(parse_url($video_url, PHP_URL_QUERY), $vars);
                                $video_id = $vars['v'];
                            } elseif (strpos($video_url, 'youtu.be/') !== false) {
                                $video_id = substr(parse_url($video_url, PHP_URL_PATH), 1);
                            } else {
                                $video_id = $video_url;
                            }
                            ?>
                            <iframe width="100%" height="100%" src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>?autoplay=1&rel=0&modestbranding=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        <?php elseif ($current_lesson->video_source === 'vimeo'): ?>
                            <iframe src="https://player.vimeo.com/video/<?php echo esc_attr($current_lesson->video_url); ?>?autoplay=1" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
                        <?php else: ?>
                            <video width="100%" height="100%" controls autoplay class="gep-native-player">
                                <source src="<?php echo esc_url($current_lesson->video_url); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="theater-empty">
                            <div class="empty-card-glass">
                                <div class="visual-pulse">🎬</div>
                                <h3>Curtain Up!</h3>
                                <p>Select a lesson from the syllabus to begin your premium learning experience.</p>
                                <div class="perks-row">
                                    <span>💎 4K Content</span>
                                    <span>📝 Expert Notes</span>
                                    <span>🛡️ Lifetime Access</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Content Details Area -->
        <div class="gep-content-details-sovereign">
            <div class="details-inner-max">
                    <?php if ($current_lesson) : 
                        $is_current_completed = in_array( $current_lesson->id, $completed_lesson_ids );
                        ?>
                        <div class="lesson-meta-row">
                            <span class="l-badge">LESSON <span class="l-num"><?php echo array_search($current_lesson, $lessons) + 1; ?></span></span>
                            <span class="l-instructor">👨‍🏫 <?php echo esc_html($course->instructor); ?></span>
                            <span class="l-dur">⏱️ <?php echo esc_html($current_lesson->duration); ?></span>
                            <button class="gep-btn-complete-lesson <?php echo $is_current_completed ? 'completed' : ''; ?>" data-lesson-id="<?php echo esc_attr($current_lesson->id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>" id="gep-toggle-completion-btn">
                                <?php echo $is_current_completed ? '✓ Completed' : 'Mark as Completed'; ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <div class="lesson-meta-row">
                            <span class="l-instructor">👨‍🏫 <?php echo esc_html($course->instructor); ?></span>
                            <span class="l-badge success">READY FOR ENROLLMENT</span>
                        </div>
                    <?php endif; ?>
                    <h1 class="lesson-title-h1"><?php echo $current_lesson ? esc_html($current_lesson->title) : esc_html($course->title); ?></h1>

                <div class="sovereign-tabs-wrap">
                    <div class="tabs-header">
                        <button class="t-btn active" data-tab="desc">Description</button>
                        <button class="t-btn" data-tab="res">Resources</button>
                        <button class="t-btn" data-tab="disc">Discussion</button>
                    </div>
                    <div class="tabs-content">
                        <div class="tab-pane active" id="tab-desc">
                            <div class="description-rich">
                                <?php echo function_exists('wpautop') ? wpautop( $current_lesson ? $current_lesson->description : 'Welcome to ' . $course->title . '. Select a lesson to see full details.' ) : ($current_lesson ? $current_lesson->description : ''); ?>
                            </div>
                        </div>
                        <div class="tab-pane" id="tab-res">
                            <?php if ($current_lesson && !empty($current_lesson->pdf_url)): ?>
                                <div class="gep-resource-card" style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 16px; padding: 25px; display: flex; align-items: center; gap: 20px; margin-top: 10px; transition: all 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
                                    <div style="font-size: 36px; background: #fee2e2; width: 64px; height: 64px; border-radius: 14px; display: flex; align-items: center; justify-content: center;">📄</div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 16px;">Study Guide &amp; Practice Exercises</h4>
                                        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight: 600;">Download complete course study notes, reference sheets, and assignments to learn from scratch.</p>
                                    </div>
                                    <a href="<?php echo esc_url($current_lesson->pdf_url); ?>" target="_blank" class="gep-btn-complete-lesson" style="margin-left: auto; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                                        <span>Download PDF</span> <span>📥</span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="resource-grid-empty">
                                    <span>No resources attached to this lesson yet.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane" id="tab-disc">
                            <div class="gep-doubt-engine">
                                <?php if ($current_lesson) : ?>
                                    <div class="doubt-input-area">
                                        <textarea aria-label="Your question about this lesson" id="gep-doubt-text" placeholder="Ask a question about this lesson..."></textarea>
                                        <button id="gep-submit-doubt" class="button button-primary" data-lesson-id="<?php echo esc_attr($current_lesson->id); ?>" data-course-id="<?php echo esc_attr($course_id); ?>">Post Doubt</button>
                                    </div>
                                    <div id="gep-doubt-list" role="status" class="doubt-feed" style="margin-top: 25px;">
                                        <!-- Doubts loaded via AJAX -->
                                        <div style="text-align: center; color: var(--admin-muted); font-size: 14px; padding: 20px;">Loading doubts...</div>
                                    </div>
                                <?php else : ?>
                                    <div class="resource-grid-empty">
                                        <span>Select a lesson to view or ask questions.</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sovereign Sidebar -->
    <div class="gep-cinema-sidebar" id="gep-cinema-sidebar">
        <div class="sidebar-resizer-handle" id="gep-sidebar-resizer"></div>
        
        <?php if ($total_lessons > 0) : ?>
            <div class="sidebar-head-premium">
                <div class="sh-top">
                    <h3>Course Syllabus</h3>
                    <span class="lesson-count-pill"><?php echo $total_lessons; ?> Lessons</span>
                </div>
                <div class="sidebar-progress-premium">
                    <div class="sp-labels">
                        <span>PROGRESS</span>
                        <span><?php echo $progress_percent; ?>%</span>
                    </div>
                    <div class="sp-bar-bg"><div class="sp-bar-fill" style="width: <?php echo $progress_percent; ?>%"></div></div>
                </div>
            </div>
        <?php else : ?>
            <div class="sidebar-head-premium compact">
                <h3>Course Syllabus</h3>
            </div>
        <?php endif; ?>

        <div class="sidebar-playlist-premium">
            <?php if ($lessons): foreach($lessons as $index => $lesson): 
                $is_active = ($current_lesson && $lesson->id == $current_lesson->id);
                $is_completed = in_array( $lesson->id, $completed_lesson_ids );
                ?>
            <div class="playlist-card <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>" 
                 data-lesson-id="<?php echo esc_attr($lesson->id); ?>"
                 data-lesson-title="<?php echo esc_attr($lesson->title); ?>"
                 data-lesson-num="<?php echo $index + 1; ?>"
                 data-lesson-duration="<?php echo esc_attr($lesson->duration); ?>"
                 data-lesson-desc='<?php echo esc_attr($lesson->description); ?>'>
                
                <div class="p-status">
                    <?php if ($is_active): ?>
                        <div class="playing-anim">
                            <span></span><span></span><span></span>
                        </div>
                    <?php elseif ($is_completed): ?>
                        <span class="p-completed-check" style="color: #10b981; font-weight: 900; font-size: 15px;">✓</span>
                    <?php else: ?>
                        <span class="p-index"><?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                    <?php endif; ?>
                </div>

                <div class="p-info">
                    <h4 class="p-title"><?php echo esc_html($lesson->title); ?></h4>
                    <div class="p-meta">
                        <span>🎬 <?php echo esc_html($lesson->duration); ?></span>
                        <span class="p-dot">•</span>
                        <span>PDF Available</span>
                    </div>
                </div>

                <div class="p-action-hover">
                    <div class="play-icon-mini">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="sidebar-empty-state">
                    <div class="empty-icon-box">📽️</div>
                    <h4>Curriculum Preparing</h4>
                    <p>Our SuperTeachers are currently indexing the final lessons for this program.</p>
                    <a href="<?php echo gep_get_url('dashboard'); ?>" class="btn-empty-back">Return to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.gep-cinema-container {
    display: flex;
    height: calc(100vh - 70px);
    background: #fff;
    overflow: hidden;
    position: relative;
}

/* Premium Lesson Completion Button */
.gep-btn-complete-lesson {
    background: rgba(37, 99, 235, 0.08);
    color: #2563eb;
    border: 1px solid rgba(37, 99, 235, 0.15);
    padding: 8px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
    outline: none;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.gep-btn-complete-lesson:hover {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
    transform: translateY(-1.5px);
}

.gep-btn-complete-lesson:active {
    transform: translateY(0);
}

.gep-btn-complete-lesson.completed {
    background: rgba(16, 185, 129, 0.08);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.15);
}

.gep-btn-complete-lesson.completed:hover {
    background: #10b981;
    color: #fff;
    border-color: #10b981;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);
}

/* Main Area */
.gep-cinema-main {
    flex: 1;
    overflow-y: auto;
    background: #fff;
    display: flex;
    flex-direction: column;
}

.gep-theater-wrap {
    background: #000;
    padding: 60px 0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.theater-glow {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 80%; height: 80%;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
    pointer-events: none;
    filter: blur(60px);
}

.player-aspect-lock {
    width: 90%;
    max-width: 1100px;
    aspect-ratio: 16 / 9;
    background: #0f172a;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 40px 100px rgba(0,0,0,0.6);
    position: relative;
    z-index: 5;
    border: 1px solid rgba(255,255,255,0.05);
}

.player-viewport { height: 100%; width: 100%; }

.theater-empty {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: #fff;
}

.empty-card-glass {
    padding: 50px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(10px);
    border-radius: 40px;
    border: 1px solid rgba(255,255,255,0.05);
}

.visual-pulse {
    font-size: 64px;
    margin-bottom: 20px;
    animation: icon-pulse 2s ease-in-out infinite;
}

@keyframes icon-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.7; }
}

.perks-row {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
    font-size: 12px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Details Area */
.gep-content-details-sovereign {
    padding: 60px 0;
    flex: 1;
}

.details-inner-max {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 40px;
}

.lesson-header-premium {
    margin-bottom: 50px;
}

.lesson-meta-row {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 13px;
    font-weight: 700;
    color: #94a3b8;
}

.l-badge {
    background: #f1f5f9;
    color: #2563eb;
    padding: 4px 12px;
    border-radius: 8px;
    font-size: 11px;
    letter-spacing: 0.5px;
}

.lesson-title-h1 {
    font-size: 42px;
    font-weight: 950;
    letter-spacing: -2px;
    color: #0f172a;
    line-height: 1.1;
}

/* Tabs */
.sovereign-tabs-wrap {
    margin-top: 40px;
}

.tabs-header {
    display: flex;
    gap: 40px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 35px;
}

.t-btn {
    background: none;
    border: none;
    padding: 15px 0;
    font-size: 15px;
    font-weight: 800;
    color: #94a3b8;
    cursor: pointer;
    position: relative;
    transition: all 0.3s;
}

.t-btn:hover { color: #0f172a; }

.t-btn.active {
    color: #2563eb;
}

.t-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0; width: 100%;
    height: 3px;
    background: #2563eb;
    border-radius: 10px 10px 0 0;
    box-shadow: 0 -4px 10px rgba(37, 99, 235, 0.2);
}

.description-rich {
    font-size: 17px;
    line-height: 1.8;
    color: #475569;
}

/* Sidebar */
.gep-cinema-sidebar {
    width: 420px;
    background: #fff;
    border-left: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    position: relative;
    z-index: 100;
}

.sidebar-resizer-handle {
    position: absolute;
    left: -3px; top: 0; bottom: 0;
    width: 6px;
    cursor: col-resize;
    z-index: 10;
    transition: background 0.3s;
}

.sidebar-resizer-handle:hover {
    background: linear-gradient(to bottom, transparent, #2563eb, transparent);
}

.sidebar-head-premium {
    padding: 35px;
    border-bottom: 1px solid #f1f5f9;
}

.sh-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.sh-top h3 {
    font-size: 20px;
    font-weight: 900;
    letter-spacing: -0.5px;
}

.lesson-count-pill {
    font-size: 11px;
    font-weight: 800;
    background: #0f172a;
    color: #fff;
    padding: 4px 10px;
    border-radius: 8px;
}

.sidebar-progress-premium {
    margin-top: 20px;
}

.sp-labels {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    font-weight: 900;
    color: #94a3b8;
    margin-bottom: 8px;
    letter-spacing: 1px;
}

.sp-bar-bg {
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}

.sp-bar-fill {
    height: 100%;
    background: #2563eb;
    border-radius: 4px;
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.3);
}

.sidebar-playlist-premium {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
}

.sidebar-playlist-premium::-webkit-scrollbar { width: 4px; }
.sidebar-playlist-premium::-webkit-scrollbar-track { background: transparent; }
.sidebar-playlist-premium::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

.playlist-card {
    margin: 0 20px 10px;
    padding: 18px;
    border-radius: 20px;
    display: flex;
    gap: 15px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
}

.playlist-card:hover {
    background: #f8fafc;
    transform: translateX(5px);
}

.playlist-card.active {
    background: #f0f7ff;
    border-color: rgba(37, 99, 235, 0.2);
}

.p-status {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.p-index {
    font-size: 13px;
    font-weight: 900;
    color: #cbd5e1;
}

.playlist-card.active .p-index { color: #2563eb; }

.p-info { flex: 1; }

.p-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.4;
    margin-bottom: 5px;
}

.p-meta {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 8px;
}

.p-action-hover {
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.3s;
}

.playlist-card:hover .p-action-hover {
    opacity: 1;
    transform: scale(1);
}

.play-icon-mini {
    width: 28px;
    height: 28px;
    background: #2563eb;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-left: 2px;
}

/* Playing Bars */
.playing-anim {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 14px;
}

.playing-anim span {
    width: 3px;
    background: #2563eb;
    animation: bar-dance 1s infinite ease-in-out;
}

.playing-anim span:nth-child(2) { animation-delay: 0.2s; }
.playing-anim span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bar-dance {
    0%, 100% { height: 4px; }
    50% { height: 14px; }
}

.sidebar-head-premium.compact {
    padding: 25px 35px;
}

.sidebar-head-premium.compact h3 {
    margin: 0;
}

.sidebar-empty-state {
    padding: 60px 30px;
    text-align: center;
}

.empty-icon-box {
    width: 64px;
    height: 64px;
    background: #f8fafc;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    margin: 0 auto 20px;
}

.sidebar-empty-state h4 {
    font-size: 16px;
    font-weight: 900;
    color: #1e293b;
    margin-bottom: 10px;
}

.sidebar-empty-state p {
    font-size: 13px;
    color: #94a3b8;
    font-weight: 600;
    line-height: 1.6;
    margin-bottom: 25px;
}

.btn-empty-back {
    display: inline-block;
    padding: 10px 20px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 800;
    text-decoration: none;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-empty-back:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.l-badge.success {
    background: #ecfdf5;
    color: #059669;
}

@media (max-width: 1200px) {
    .gep-cinema-container { flex-direction: column; height: auto; }
    .gep-cinema-sidebar { width: 100% !important; border-left: none; height: 600px; }
}

@media (max-width: 768px) {
    .gep-theater-wrap {
        padding: 20px 0 !important;
    }
    .player-aspect-lock {
        width: 100% !important;
        border-radius: 0 !important;
    }
    .gep-content-details-sovereign {
        padding: 30px 0 !important;
    }
    .details-inner-max {
        padding: 0 20px !important;
    }
    .lesson-title-h1 {
        font-size: 28px !important;
        letter-spacing: -1px !important;
    }
    .lesson-meta-row {
        flex-wrap: wrap !important;
        gap: 10px 15px !important;
    }
    .tabs-header {
        gap: 20px !important;
        margin-bottom: 25px !important;
    }
    .gep-resource-card {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 20px !important;
    }
    .gep-resource-card a {
        margin-left: 0 !important;
        width: 100% !important;
        justify-content: center !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Tab Switching
    const tabBtns = document.querySelectorAll('.t-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabId = btn.getAttribute('data-tab');
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });

    // 2. Sidebar Resizer
    const resizer = document.getElementById('gep-sidebar-resizer');
    const sidebar = document.getElementById('gep-cinema-sidebar');
    
    if (resizer && sidebar) {
        let startX, startWidth;

        resizer.addEventListener('mousedown', e => {
            startX = e.clientX;
            startWidth = sidebar.offsetWidth;
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', () => {
                document.removeEventListener('mousemove', handleMouseMove);
                document.body.style.cursor = 'default';
            });
            document.body.style.cursor = 'col-resize';
        });

        function handleMouseMove(e) {
            const dx = startX - e.clientX;
            const newWidth = startWidth + dx;
            if (newWidth > 320 && newWidth < 800) {
                sidebar.style.width = newWidth + 'px';
            }
        }
    }

    // 3. Dynamic Lesson Updates
    const playlistItems = document.querySelectorAll('.playlist-card');
    const lTitle = document.querySelector('.lesson-title-h1');
    const lDesc = document.querySelector('.description-rich');
    const lNum = document.querySelector('.l-num');
    const lDur = document.querySelector('.l-dur');

    playlistItems.forEach(item => {
        item.addEventListener('click', function() {
            // Update UI State
            playlistItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // Extraction
            const title = this.getAttribute('data-lesson-title');
            const desc = this.getAttribute('data-lesson-desc');
            const num = this.getAttribute('data-lesson-num');
            const dur = this.getAttribute('data-lesson-duration');
            const id = this.getAttribute('data-lesson-id');

            // Updates with micro-fades
            if (lTitle) lTitle.textContent = title;
            if (lDesc) lDesc.innerHTML = desc;
            if (lNum) lNum.textContent = num;
            if (lDur) lDur.textContent = '⏱️ ' + dur;

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('lesson', id);
            window.history.pushState({}, '', url);

            // Optional: Trigger video change via iframe reload if needed
            // For now, let's keep it simple. If the user wants full AJAX video, 
            // we'd need to re-render the player area too.
            if (!this.classList.contains('active-originally')) {
                // To keep it simple for the user, a quick reload for video context
                // but we can make it smoother later if they ask.
                window.location.href = url.href;
            }
        });
    });

    // Doubt Engine Logic
    function loadDoubts() {
        const doubtList = jQuery('#gep-doubt-list');
        const lessonId = jQuery('#gep-submit-doubt').data('lesson-id');
        const courseId = jQuery('#gep-submit-doubt').data('course-id');
        if (!lessonId) return;

        jQuery.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: { action: 'gep_get_doubts', lesson_id: lessonId, course_id: courseId, nonce: gep_ajax.nonce },
            success: function(res) {
                if(res.success) {
                    let html = '';
                    if(res.data.length === 0) {
                        html = '<div style="text-align: center; color: var(--admin-muted); font-size: 13px;">No doubts yet. Be the first to ask!</div>';
                    } else {
                        res.data.forEach(function(d) {
                            html += '<div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 12px; padding: 15px; margin-bottom: 15px;">';
                            html += '<div style="font-size: 11px; font-weight: 800; color: var(--admin-muted); text-transform: uppercase; margin-bottom: 5px;">STUDENT QUESTION</div>';
                            html += '<div style="font-size: 14px; color: var(--admin-text); font-weight: 600; margin-bottom: 10px;">' + jQuery('<div>').text(d.question).html() + '</div>';
                            if (d.answer) {
                                html += '<div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px;">';
                                html += '<div style="font-size: 11px; font-weight: 800; color: #166534; text-transform: uppercase; margin-bottom: 3px;">EXPERT REPLY</div>';
                                html += '<div style="font-size: 13px; color: #166534; font-weight: 600;">' + jQuery('<div>').text(d.answer).html() + '</div>';
                                html += '</div>';
                            } else {
                                html += '<div style="font-size: 11px; color: #d97706; font-weight: 700; margin-top: 5px;">⏳ Awaiting Expert Reply</div>';
                            }
                            html += '</div>';
                        });
                    }
                    doubtList.html(html);
                } else doubtList.text('Could not load the discussion. Open this tab again to retry.');
            },
            error: function() { doubtList.text('Could not load the discussion. Open this tab again to retry.'); }
        });
    }

    jQuery('#gep-submit-doubt').on('click', function() {
        const text = jQuery('#gep-doubt-text').val().trim();
        const lessonId = jQuery(this).data('lesson-id');
        const courseId = jQuery(this).data('course-id');
        const btn = jQuery(this);

        if(!text) { alert('Please enter a question.'); return; }

        btn.prop('disabled', true).text('Posting...');
        jQuery.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: { action: 'gep_post_doubt', lesson_id: lessonId, course_id: courseId, question: text, nonce: gep_ajax.nonce },
            success: function(res) {
                btn.prop('disabled', false).text('Post Doubt');
                if(res.success) {
                    jQuery('#gep-doubt-text').val('');
                    loadDoubts();
                } else {
                    alert('Could not post your question. Your text is still here; please try again.');
                }
            },
            error: function() { alert('Could not connect. Your question is still here; please try again.'); },
            complete: function() { btn.prop('disabled', false).text('Post Doubt'); }
        });
    });

    // Load initial doubts when discussion tab is clicked
    jQuery('.t-btn[data-tab="disc"]').on('click', function() {
        if(jQuery('#gep-doubt-list').children().length <= 1) {
            loadDoubts();
        }
    });

    // 4. Toggle Lesson Completion via AJAX
    jQuery('#gep-toggle-completion-btn').on('click', function(e) {
        e.preventDefault();
        var $btn = jQuery(this);
        var lessonId = $btn.data('lesson-id');
        var courseId = $btn.data('course-id');
        
        $btn.prop('disabled', true).css('opacity', '0.6');
        
        jQuery.ajax({
            url: gep_ajax.ajax_url,
            type: 'POST',
            timeout: 20000,
            data: {
                action: 'gep_toggle_lesson_completion',
                lesson_id: lessonId,
                course_id: courseId,
                nonce: gep_ajax.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (response.success) {
                    var isCompleted = (response.data.status === 'completed');
                    
                    // Update Button State
                    if (isCompleted) {
                        $btn.addClass('completed').html('✓ Completed');
                    } else {
                        $btn.removeClass('completed').html('Mark as Completed');
                    }
                    
                    // Update playlist card
                    var $playlistCard = jQuery('.playlist-card[data-lesson-id="' + lessonId + '"]');
                    var lessonNum = $playlistCard.data('lesson-num');
                    var formattedNum = String(lessonNum).padStart(2, '0');
                    
                    if (isCompleted) {
                        $playlistCard.addClass('completed');
                        if (!$playlistCard.hasClass('active')) {
                            $playlistCard.find('.p-status').html('<span class="p-completed-check" style="color: #10b981; font-weight: 900; font-size: 15px;">✓</span>');
                        }
                    } else {
                        $playlistCard.removeClass('completed');
                        if (!$playlistCard.hasClass('active')) {
                            $playlistCard.find('.p-status').html('<span class="p-index">' + formattedNum + '</span>');
                        }
                    }
                    
                    // Update progress bar
                    var newProgress = response.data.progress_percent;
                    jQuery('.sp-labels span:last-child').text(newProgress + '%');
                    jQuery('.sp-bar-fill').css('width', newProgress + '%');
                } else {
                    alert(response.data.message || 'Operation failed');
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', '1');
                alert('AJAX communication failure.');
            }
        });
    });
});
</script>
