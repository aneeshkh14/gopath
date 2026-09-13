<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb;

// Fetch all courses EXCEPT those in Skill Academy categories
$skill_category_slugs = array( 'english-typing', 'data-entry', 'excel-mastery', 'web-design', 'public-speaking' );
$placeholders = implode( ',', array_fill( 0, count( $skill_category_slugs ), '%s' ) );
$query = "
    SELECT c.* 
    FROM {$wpdb->prefix}gep_courses c
    LEFT JOIN {$wpdb->prefix}gep_categories cat ON c.category_id = cat.id
    WHERE ( cat.slug IS NULL OR cat.slug NOT IN ($placeholders) ) 
      AND c.status = 'publish'
    ORDER BY c.id DESC
";
$courses = $wpdb->get_results( $wpdb->prepare( $query, $skill_category_slugs ) );
?>

<div class="gep-supercoaching-sovereign">
    <!-- Advanced Dynamic Hero -->
    <div class="gep-hero-premium">
        <div class="mesh-bg"></div>
        <div class="mesh-blob blob-1"></div>
        <div class="mesh-blob blob-2"></div>
        
        <div class="hero-content">
            <div class="gep-badge-micro gep-glass">
                <span class="pulse-dot"></span> ELITE LEARNING
            </div>
            <!-- Changed h2 to div to avoid theme h2 overrides causing black text -->
            <div class="hero-title-premium">
                <span class="text-white">SuperCoaching</span> 
                <span class="text-gradient-gold">Mastery</span>
            </div>
            <p class="hero-desc-premium">
                Access high-fidelity curriculum from India's top academic minds.<br>Curated for performance and absolute precision.
            </p>
            
            <div class="gep-nav-filters gep-glass" id="gep-course-filters">
                <button class="filter-btn active" data-filter="all">All Programs</button>
                <?php 
                $active_course_cats = $wpdb->get_col( $wpdb->prepare( "
                    SELECT DISTINCT c.category_id 
                    FROM {$wpdb->prefix}gep_courses c
                    LEFT JOIN {$wpdb->prefix}gep_categories cat ON c.category_id = cat.id
                    WHERE ( cat.slug IS NULL OR cat.slug NOT IN ($placeholders) ) 
                      AND c.status = 'publish' 
                      AND c.category_id > 0
                ", $skill_category_slugs ) );
                if ( ! empty( $active_course_cats ) ) {
                    $course_categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}gep_categories WHERE parent_id = 0 AND id IN (" . implode( ',', array_map( 'intval', $active_course_cats ) ) . ") ORDER BY name ASC");
                    foreach ( $course_categories as $cc ) {
                        echo '<button class="filter-btn" data-filter="' . esc_attr($cc->id) . '">' . esc_html($cc->name) . '</button>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Marketplace Grid -->
    <div class="gep-marketplace-premium">
        <?php if ($courses): ?>
        <div class="gep-premium-grid">
            <?php foreach($courses as $c): ?>
            <div class="gep-card-elite" data-category-id="<?php echo esc_attr($c->category_id); ?>">
                <div class="card-media">
                    <img src="<?php echo $c->thumbnail ?: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'; ?>" alt="<?php echo esc_attr($c->title); ?>">
                    <div class="media-overlay">
                        <div class="play-btn-glass">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                    <div class="tag-elite"><?php echo $c->price > 0 ? 'PREMIUM' : 'OPEN ACCESS'; ?></div>
                </div>
                
                <div class="card-body">
                    <div class="instructor-row">
                        <div class="instructor-avatar">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c->instructor); ?>&background=random" alt="Avatar">
                        </div>
                        <span class="instructor-name"><?php echo esc_html($c->instructor); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo esc_html($c->title); ?></h3>
                    
                    <div class="card-stats">
                        <div class="stat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> 120+ Assets</div>
                        <div class="stat"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> 45h Runtime</div>
                    </div>
                    
                    <div class="card-footer gep-glass-subtle">
                        <div class="price-elite">
                            <span class="currency">₹</span>
                            <span class="amount"><?php echo number_format($c->price, 0); ?></span>
                        </div>
                        
                        <?php 
                        $dashboard = new GEP_Dashboard();
                        if ( $dashboard->has_access( get_current_user_id(), $c->id, 'course' ) ) : ?>
                            <a href="<?php echo add_query_arg( array( 'view' => 'watch', 'id' => $c->id ), (string) gep_get_url('dashboard') ); ?>" class="btn-action-elite btn-primary">
                                Launch <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        <?php else : ?>
                            <a href="<?php echo add_query_arg( array( 'id' => $c->id, 'type' => 'course' ), (string) gep_get_url('checkout') ); ?>" class="btn-action-elite btn-secondary">
                                Enroll Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="gep-empty-state gep-glass">
                <div class="icon">🚀</div>
                <h3>SuperCoaching Coming Soon</h3>
                <p>We are curating the finest academic content for you. Check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Reset & Base */
.gep-supercoaching-sovereign {
    background: #09090b;
    min-height: 100vh;
    padding-bottom: 120px;
    font-family: 'Inter', system-ui, sans-serif;
    color: #fff;
}

/* Premium Hero Section */
.gep-hero-premium {
    position: relative;
    padding: 100px 40px 120px;
    background: #000;
    border-radius: 0 0 60px 60px;
    overflow: hidden;
    text-align: center;
    margin-bottom: -60px;
    z-index: 1;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.mesh-bg {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 0%, rgba(99, 102, 241, 0.15) 0%, transparent 60%);
}

.mesh-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.5;
    z-index: 0;
    animation: float 20s infinite alternate;
}

.blob-1 {
    top: -100px;
    left: 20%;
    width: 400px;
    height: 400px;
    background: rgba(124, 58, 237, 0.3);
}

.blob-2 {
    bottom: -100px;
    right: 20%;
    width: 300px;
    height: 300px;
    background: rgba(59, 130, 246, 0.3);
    animation-delay: -5s;
}

@keyframes float {
    0% { transform: translate(0, 0) scale(1); }
    100% { transform: translate(50px, 50px) scale(1.1); }
}

.hero-content {
    position: relative;
    z-index: 10;
    max-width: 900px;
    margin: 0 auto;
}

/* Glassmorphism Utilities */
.gep-glass {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.gep-glass-subtle {
    background: rgba(255, 255, 255, 0.015);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

/* Micro Badge */
.gep-badge-micro {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-bottom: 30px;
    color: #e2e8f0;
}

.pulse-dot {
    width: 6px;
    height: 6px;
    background: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 10px #10b981;
    animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Typography */
.hero-title-premium {
    font-size: 64px;
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -2px;
    margin-bottom: 24px;
}

.text-white {
    color: #ffffff !important;
}

.text-gradient-gold {
    background: linear-gradient(135deg, #fde047 0%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

.hero-desc-premium {
    font-size: 20px;
    color: #a1a1aa;
    line-height: 1.6;
    margin-bottom: 50px;
    font-weight: 400;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Filters */
.gep-nav-filters {
    display: inline-flex;
    padding: 8px;
    border-radius: 50px;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: center;
}

.filter-btn {
    padding: 12px 28px;
    border-radius: 40px;
    border: none;
    background: transparent;
    color: #a1a1aa;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.filter-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
}

.filter-btn.active {
    background: #fff;
    color: #000;
    box-shadow: 0 10px 25px rgba(255,255,255,0.1);
}

/* Marketplace */
.gep-marketplace-premium {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    position: relative;
    z-index: 10;
}

.gep-premium-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 32px;
}

/* Card */
.gep-card-elite {
    background: #18181b;
    border-radius: 32px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.05);
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.gep-card-elite:hover {
    transform: translateY(-8px) scale(1.01);
    border-color: rgba(124, 58, 237, 0.3);
    box-shadow: 0 30px 60px rgba(124, 58, 237, 0.15);
}

.card-media {
    height: 220px;
    position: relative;
    overflow: hidden;
}

.card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.7s ease;
}

.gep-card-elite:hover .card-media img {
    transform: scale(1.08);
}

.media-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, #18181b 0%, rgba(24,24,27,0.1) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.8;
    transition: opacity 0.3s;
}

.gep-card-elite:hover .media-overlay {
    opacity: 1;
    background: linear-gradient(to top, #18181b 0%, rgba(124,58,237,0.2) 100%);
}

.play-btn-glass {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0;
    transform: scale(0.8);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.play-btn-glass svg {
    width: 24px;
    height: 24px;
    margin-left: 4px;
}

.gep-card-elite:hover .play-btn-glass {
    opacity: 1;
    transform: scale(1);
}

.tag-elite {
    position: absolute;
    top: 20px;
    right: 20px;
    padding: 6px 14px;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
    color: #fde047;
}

.card-body {
    padding: 30px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.instructor-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.instructor-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,0.1);
}

.instructor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.instructor-name {
    font-size: 13px;
    font-weight: 600;
    color: #a1a1aa;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.card-title {
    font-size: 22px;
    font-weight: 700;
    color: #fff;
    line-height: 1.4;
    margin-bottom: 24px;
    min-height: 60px;
}

.card-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.stat {
    font-size: 13px;
    font-weight: 500;
    color: #a1a1aa;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat svg {
    color: #6366f1;
}

.card-footer {
    margin-top: auto;
    padding: 20px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.price-elite {
    display: flex;
    align-items: flex-start;
    gap: 4px;
}

.price-elite .currency {
    font-size: 16px;
    font-weight: 600;
    color: #a1a1aa;
    margin-top: 4px;
}

.price-elite .amount {
    font-size: 28px;
    font-weight: 800;
    color: #fff;
}

.btn-action-elite {
    padding: 12px 24px;
    border-radius: 100px;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-primary {
    background: #fff;
    color: #000 !important;
}

/* White on #6366f1 is 4.47:1 — just under AA. #4f46e5 gives 6.29:1. */
.btn-secondary {
    background: #4f46e5;
    color: #fff !important;
}

.btn-action-elite:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.btn-primary:hover {
    background: #f8fafc;
}

.btn-secondary:hover {
    background: #4f46e5;
    box-shadow: 0 10px 20px rgba(99,102,241,0.3);
}

/* Empty State */
.gep-empty-state {
    padding: 80px 40px;
    border-radius: 40px;
    text-align: center;
}

.gep-empty-state .icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.gep-empty-state h3 {
    font-size: 24px;
    color: #fff;
    margin-bottom: 10px;
}

.gep-empty-state p {
    color: #a1a1aa;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title-premium { font-size: 42px; }
    .hero-desc-premium { font-size: 16px; }
    .gep-nav-filters { flex-wrap: wrap; }
    .gep-marketplace-premium { padding: 0 20px; }
    .gep-premium-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .gep-hero-premium { padding: 50px 15px 70px; margin-bottom: -40px; border-radius: 0 0 32px 32px; }
    .hero-title-premium { font-size: 32px; }
    .hero-desc-premium { font-size: 15px; margin-bottom: 30px; }
    .gep-nav-filters { padding: 4px; border-radius: 24px; }
    .filter-btn { padding: 8px 16px; font-size: 12px; }
    .gep-marketplace-premium { padding: 0 15px; }
    .card-body { padding: 20px; }
    .card-title { font-size: 18px; min-height: auto; margin-bottom: 15px; }
    .card-stats { margin-bottom: 20px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('#gep-course-filters .filter-btn');
    const cards = document.querySelectorAll('.gep-premium-grid .gep-card-elite');
    
    const feedback = document.createElement('p'); feedback.setAttribute('role', 'status');
    const filterBar = document.getElementById('gep-course-filters');
    if (filterBar) filterBar.after(feedback);
    filters.forEach(btn => {
        btn.addEventListener('click', function() {
            filters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            
            const filterVal = this.dataset.filter;
            let count = 0;
            filters.forEach(f => f.setAttribute('aria-pressed', String(f === this)));
            cards.forEach(card => {
                if (filterVal === 'all' || card.dataset.categoryId === filterVal) {
                    card.style.display = 'flex'; count++;
                } else {
                    card.style.display = 'none';
                }
            });
            feedback.textContent = count ? count + ' courses shown' : 'No courses in this category. Choose All to see available courses.';
        });
    });
});
</script>
