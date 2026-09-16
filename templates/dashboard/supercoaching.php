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

<div class="gep-supercoaching-sovereign gep-learning-catalog">
    <header class="gep-learning-header">
        <div class="hero-content">
            <p class="gep-learning-eyebrow">COURSES</p>
            <h1>SuperCoaching</h1>
            <p class="hero-desc-premium">Build your knowledge with structured courses and lessons from your educators.</p>

            <div class="gep-nav-filters" aria-label="Filter courses" id="gep-course-filters">
                <button type="button" class="filter-btn active" data-filter="all" aria-pressed="true">All Programs</button>
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
                        echo '<button type="button" class="filter-btn" aria-pressed="false" data-filter="' . esc_attr($cc->id) . '">' . esc_html($cc->name) . '</button>';
                    }
                }
                ?>
            </div>
        </div>
    </header>

    <!-- Marketplace Grid -->
    <div class="gep-marketplace-premium">
        <?php if ($courses): ?>
        <div class="gep-premium-grid">
            <?php foreach($courses as $c): ?>
            <div class="gep-card-elite" data-category-id="<?php echo esc_attr($c->category_id); ?>">
                <div class="card-media">
                    <?php if ( ! empty( $c->thumbnail ) ) : ?>
                        <img src="<?php echo esc_url( $c->thumbnail ); ?>" alt="" loading="lazy" width="560" height="320">
                    <?php else : ?>
                        <div class="gep-course-placeholder" aria-hidden="true"><span class="dashicons dashicons-book-alt"></span></div>
                    <?php endif; ?>
                    <div class="tag-elite"><?php echo $c->price > 0 ? 'PREMIUM' : 'OPEN ACCESS'; ?></div>
                </div>
                
                <div class="card-body">
                    <div class="instructor-row">
                        <span class="instructor-avatar dashicons dashicons-businessperson" aria-hidden="true"></span>
                        <span class="instructor-name"><?php echo esc_html( $c->instructor ?: 'GoPath educator' ); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo esc_html($c->title); ?></h3>
                    
                    <div class="card-footer">
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
            <div class="gep-empty-state">
                <div class="icon">🚀</div>
                <h3>SuperCoaching Coming Soon</h3>
                <p>We are curating the finest academic content for you. Check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filters = document.querySelectorAll('#gep-course-filters .filter-btn');
    const cards = document.querySelectorAll('.gep-premium-grid .gep-card-elite');
    
    const feedback = document.createElement('p'); feedback.className = 'gep-course-filter-status'; feedback.setAttribute('role', 'status');
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
