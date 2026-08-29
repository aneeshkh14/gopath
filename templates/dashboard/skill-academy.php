<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 
global $wpdb;

// Fetch all courses in Skill Academy categories
$skill_category_slugs = array( 'english-typing', 'data-entry', 'excel-mastery', 'web-design', 'public-speaking' );
$placeholders = implode( ',', array_fill( 0, count( $skill_category_slugs ), '%s' ) );
$query = "
    SELECT c.*, cat.slug as cat_slug, cat.name as cat_name 
    FROM {$wpdb->prefix}gep_courses c
    JOIN {$wpdb->prefix}gep_categories cat ON c.category_id = cat.id
    WHERE cat.slug IN ($placeholders) AND c.status = 'publish'
    ORDER BY c.id DESC
";
$courses = $wpdb->get_results( $wpdb->prepare( $query, $skill_category_slugs ) );

$skill_tracks = array(
    'english-typing' => array('name' => 'English Typing', 'icon' => '⌨️'),
    'data-entry'     => array('name' => 'Data Entry', 'icon' => '📁'),
    'excel-mastery'  => array('name' => 'MS Excel Mastery', 'icon' => '📊'),
    'web-design'     => array('name' => 'Web Design', 'icon' => '🎨'),
    'public-speaking'=> array('name' => 'Public Speaking', 'icon' => '🎤'),
);

$counts = array();
foreach ( $skill_tracks as $slug => $track ) {
    $counts[$slug] = 0;
}
if ( ! empty( $courses ) ) {
    foreach ( $courses as $c ) {
        if ( isset( $counts[$c->cat_slug] ) ) {
            $counts[$c->cat_slug]++;
        }
    }
}
?>

<div class="gep-main-inner" style="font-family: 'Inter', system-ui, sans-serif; padding-bottom: 80px;">
    <!-- Skill Academy Hero Banner -->
    <div class="gep-skill-hero" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; padding: 40px; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; align-items: center; margin-bottom: 40px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.02);">
        <div class="gep-skill-hero-content">
            <h2 style="font-size: 36px; font-weight: 950; color: #0f172a; margin: 0 0 15px; letter-spacing: -1.5px; line-height: 1.1;">
                Master New Skills, <span class="gep-text-gradient-primary" style="color: #6366f1;">Get Industry Ready.</span>
            </h2>
            <p style="font-size: 16px; color: #64748b; font-weight: 600; line-height: 1.6; margin: 0 0 35px; max-width: 500px;">
                Professional certification courses designed to help you land your first job in the tech or govt. sector.
            </p>
            <div class="gep-skill-stats" style="display: flex; gap: 30px;">
                <div class="gep-skill-stat-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 25px; border-radius: 16px; min-width: 140px;">
                    <span class="val" style="display: block; font-size: 24px; font-weight: 900; color: #6366f1; margin-bottom: 2px;">12+</span>
                    <span class="label" style="font-size: 11px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Skill Tracks</span>
                </div>
                <div class="gep-skill-stat-item" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 25px; border-radius: 16px; min-width: 140px;">
                    <span class="val" style="display: block; font-size: 24px; font-weight: 900; color: #10b981; margin-bottom: 2px;">5k+</span>
                    <span class="label" style="font-size: 11px; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Active Learners</span>
                </div>
            </div>
        </div>
        <div class="gep-skill-hero-image" style="position: relative; height: 100%; min-height: 220px; background: #0f172a; border-radius: 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1);">
            <div style="position: absolute; inset: 0; background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.2), transparent 70%);"></div>
            <div style="position: relative; text-align: center; color: #fff;">
                <div style="font-size: 54px; margin-bottom: 12px; animation: float 6s ease-in-out infinite;">🎓</div>
                <h3 style="font-size: 28px; font-weight: 950; letter-spacing: -1px; margin: 0 0 5px; color: #fff;">Skill Academy</h3>
                <span style="font-size: 11px; font-weight: 800; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Premium Access</span>
            </div>
        </div>
    </div>

    <!-- Trending Skill Tracks -->
    <div class="gep-section-header" style="margin-bottom: 25px;">
        <h3 style="font-size: 22px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px;">Trending Skill Tracks</h3>
    </div>

    <div class="gep-skill-track-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
        <div class="gep-skill-card active" data-track="all" style="background: #fff; border: 2px solid #6366f1; border-radius: 20px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.05);">
            <span class="icon" style="font-size: 32px; display: block; margin-bottom: 15px;">🌐</span>
            <div class="name" style="font-weight: 800; color: #0f172a; font-size: 15px; margin-bottom: 5px;">All Tracks</div>
            <div class="count" style="font-size: 12px; color: #6366f1; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                <?php echo count($courses); ?> Courses
            </div>
        </div>
        <?php foreach($skill_tracks as $slug => $track): ?>
        <div class="gep-skill-card" data-track="<?php echo esc_attr($slug); ?>" style="background: #fff; border: 2px solid #e2e8f0; border-radius: 20px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);">
            <span class="icon" style="font-size: 32px; display: block; margin-bottom: 15px;"><?php echo $track['icon']; ?></span>
            <div class="name" style="font-weight: 800; color: #0f172a; font-size: 15px; margin-bottom: 5px;"><?php echo $track['name']; ?></div>
            <div class="count" style="font-size: 12px; color: #6366f1; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">
                <?php echo $counts[$slug]; ?> Courses
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dynamic Courses list -->
    <div class="gep-section-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <h3 id="gep-track-title" style="font-size: 22px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px;">All Skill Courses</h3>
        <span style="font-size: 13px; color: #64748b; font-weight: 600;">Priced ₹499 to ₹999</span>
    </div>

    <div id="gep-courses-container" class="gep-premium-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
        <?php if (!empty($courses)): ?>
            <?php foreach($courses as $c): 
                $dashboard = new GEP_Dashboard();
                $has_access = $dashboard->has_access( get_current_user_id(), $c->id, 'course' );
                
                // Assign a thumbnail/banner based on category
                $img_url = 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
                if ($c->cat_slug === 'english-typing') {
                    $img_url = 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
                } elseif ($c->cat_slug === 'excel-mastery') {
                    $img_url = 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
                } elseif ($c->cat_slug === 'web-design') {
                    $img_url = 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
                } elseif ($c->cat_slug === 'public-speaking') {
                    $img_url = 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80';
                }
            ?>
            <div class="gep-card-elite course-item-card" data-cat="<?php echo esc_attr($c->cat_slug); ?>" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01);">
                <div class="card-media" style="position: relative; aspect-ratio: 16/9; overflow: hidden; background: #0f172a;">
                    <img src="<?php echo $img_url; ?>" alt="<?php echo esc_attr($c->title); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                    <div style="position: absolute; top: 15px; left: 15px; font-size: 11px; font-weight: 800; background: rgba(15, 23, 42, 0.85); color: #fff; padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.08);">
                        <?php echo esc_html($c->cat_name); ?>
                    </div>
                </div>
                
                <div class="card-body" style="padding: 25px; flex: 1; display: flex; flex-direction: column;">
                    <div class="instructor-row" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c->instructor); ?>&background=random" alt="Avatar" style="width: 28px; height: 28px; border-radius: 50%;">
                        <span class="instructor-name" style="font-size: 13px; color: #64748b; font-weight: 700;"><?php echo esc_html($c->instructor); ?></span>
                    </div>
                    <h3 class="card-title" style="font-size: 18px; font-weight: 900; color: #0f172a; line-height: 1.4; margin: 0 0 15px; letter-spacing: -0.3px; min-height: 50px;"><?php echo esc_html($c->title); ?></h3>
                    <p style="font-size: 13px; color: #64748b; font-weight: 600; line-height: 1.5; margin: 0 0 20px; flex: 1;"><?php echo esc_html(wp_trim_words($c->description, 18)); ?></p>
                    
                    <div class="card-footer" style="display: flex; align-items: center; justify-content: space-between; padding-top: 20px; border-top: 1px solid #f1f5f9; margin-top: auto;">
                        <div class="price-elite" style="font-weight: 900; font-size: 20px; color: #0f172a;">
                            <span class="currency" style="font-size: 13px; color: #94a3b8; font-weight: 700; margin-right: 2px;">₹</span><?php echo number_format($c->price, 0); ?>
                        </div>
                        
                        <?php if ( $has_access ) : ?>
                            <a href="<?php echo add_query_arg( array( 'view' => 'watch', 'id' => $c->id ), (string) gep_get_url('dashboard') ); ?>" class="btn-action-elite" style="background: #10b981; color: #fff; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(16,185,129,0.15);">
                                Launch Lecture
                            </a>
                        <?php else : ?>
                            <a href="<?php echo add_query_arg( array( 'id' => $c->id, 'type' => 'course' ), (string) gep_get_url('checkout') ); ?>" class="btn-action-elite" style="background: #6366f1; color: #fff; padding: 10px 20px; border-radius: 12px; font-size: 13px; font-weight: 800; text-decoration: none; box-shadow: 0 4px 10px rgba(99,102,241,0.15);">
                                Enroll Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="gep-empty-state" style="grid-column: 1/-1; text-align: center; padding: 60px 40px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px;">
                <div class="icon" style="font-size: 40px; margin-bottom: 15px;">🚀</div>
                <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">SuperCoaching Coming Soon</h3>
                <p style="color: #64748b; font-weight: 600;">We are curating the finest academic content for you. Check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Hover lift and card effects */
.course-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.01) !important;
    border-color: #cbd5e1 !important;
}
.course-item-card:hover img {
    transform: scale(1.05);
}
.gep-skill-card:hover {
    transform: translateY(-3px);
    border-color: #cbd5e1 !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.02) !important;
}
.gep-skill-card.active {
    border-color: #6366f1 !important;
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.08) !important;
    background: #f0f3ff !important;
}
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@media (max-width: 768px) {
    .gep-skill-hero {
        grid-template-columns: 1fr !important;
        padding: 30px 20px !important;
        gap: 20px !important;
    }
    .gep-skill-hero-content h2 {
        font-size: 26px !important;
    }
    .gep-skill-hero-image {
        min-height: 160px !important;
    }
    .gep-skill-track-grid {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}
@media (max-width: 480px) {
    .gep-skill-track-grid {
        grid-template-columns: 1fr !important;
    }
    .gep-skill-stats {
        flex-direction: column;
        gap: 15px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.gep-skill-card');
    const courseCards = document.querySelectorAll('.course-item-card');
    const trackTitle = document.getElementById('gep-track-title');
    const container = document.getElementById('gep-courses-container');

    cards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove active class from all
            cards.forEach(c => {
                c.classList.remove('active');
                c.style.borderColor = '#e2e8f0';
                c.style.background = '#fff';
                c.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.01)';
            });

            // Set active class on clicked
            this.classList.add('active');
            this.style.borderColor = '#6366f1';
            this.style.background = '#f0f3ff';
            this.style.boxShadow = '0 10px 15px -3px rgba(99, 102, 241, 0.08)';

            const track = this.getAttribute('data-track');
            const trackName = this.querySelector('.name').textContent;
            trackTitle.textContent = track === 'all' ? 'All Skill Courses' : trackName + ' Courses';

            let visibleCount = 0;

            courseCards.forEach(cc => {
                const cat = cc.getAttribute('data-cat');
                if (track === 'all' || cat === track) {
                    cc.style.display = 'flex';
                    visibleCount++;
                } else {
                    cc.style.display = 'none';
                }
            });

            // Handle empty state within this filter
            const existingEmpty = container.querySelector('.gep-filter-empty-state');
            if (existingEmpty) {
                existingEmpty.remove();
            }

            if (visibleCount === 0) {
                const emptyHTML = document.createElement('div');
                emptyHTML.className = 'gep-filter-empty-state';
                emptyHTML.style.gridColumn = '1/-1';
                emptyHTML.style.textAlign = 'center';
                emptyHTML.style.padding = '60px 40px';
                emptyHTML.style.background = '#fff';
                emptyHTML.style.border = '1px solid #e2e8f0';
                emptyHTML.style.borderRadius = '20px';
                emptyHTML.innerHTML = `
                    <div style="font-size: 40px; margin-bottom: 15px;">⏳</div>
                    <h3 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Courses Coming Soon</h3>
                    <p style="color: #64748b; font-weight: 600;">We are preparing video lectures and study PDFs for ${trackName}. Please check back shortly.</p>
                `;
                container.appendChild(emptyHTML);
            }
        });
    });
});
</script>
