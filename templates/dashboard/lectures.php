<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-live-sovereign">
    <!-- Premium Header -->
    <div class="gep-hero-premium-mini" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
        <div class="mesh-bg" style="opacity: 0.4;"></div>
        <div class="hero-content">
            <h2 class="hero-title-premium-mini">
                <span style="color: #fff;">Video</span> 
                <span class="text-gradient-gold" style="background: linear-gradient(135deg, #facc15 0%, #fbbf24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Lectures</span>
            </h2>
            <p class="hero-desc-premium" style="color: #cbd5e1;">High-fidelity standalone lecture videos organized by subject and topic.</p>
        </div>
    </div>

    <div class="gep-marketplace-premium" style="margin-top: 40px;">
        <?php 
        global $wpdb;
        $lectures = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gep_lectures WHERE status = 'publish' ORDER BY created_at DESC");

        // Group lectures by Category -> Subcategory
        $grouped = array();
        foreach ($lectures as $lec) {
            $cat_name = 'General';
            $subcat_name = 'Miscellaneous';

            if ($lec->category_id) {
                $c = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $lec->category_id));
                if ($c) $cat_name = $c;
            }
            if ($lec->subcategory_id) {
                $s = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}gep_categories WHERE id = %d", $lec->subcategory_id));
                if ($s) $subcat_name = $s;
            }

            if (!isset($grouped[$cat_name])) {
                $grouped[$cat_name] = array();
            }
            if (!isset($grouped[$cat_name][$subcat_name])) {
                $grouped[$cat_name][$subcat_name] = array();
            }
            $grouped[$cat_name][$subcat_name][] = $lec;
        }
        ?>

        <?php if (!empty($grouped)): ?>
            <?php foreach ($grouped as $cat_name => $subcats): ?>
                <div class="gep-lecture-category-group" style="margin-bottom: 50px;">
                    <div class="section-title-wrap" style="margin-bottom: 30px;">
                        <h2 class="section-title" style="font-size: 28px; font-weight: 900; color: #1e293b;"><?php echo esc_html($cat_name); ?></h2>
                        <div class="line-decorator" style="width: 60px; height: 4px; background: #6366f1; border-radius: 2px; margin-top: 8px;"></div>
                    </div>

                    <?php foreach ($subcats as $subcat_name => $lecs): ?>
                        <div class="gep-lecture-subcategory-group" style="margin-bottom: 30px; padding-left: 20px; border-left: 3px solid #e2e8f0;">
                            <h3 style="font-size: 20px; font-weight: 800; color: #cbd5e1; margin-bottom: 20px;"><?php echo esc_html($subcat_name); ?></h3>
                            
                            <div class="gep-premium-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px;">
                                <?php foreach ($lecs as $lec): ?>
                                    <div class="gep-card-elite" style="background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); transition: transform 0.3s, box-shadow 0.3s;">
                                        <div class="card-media" style="height: 160px; background: #0f172a url('<?php echo esc_url($lec->thumbnail); ?>') center/cover; position: relative;">
                                            <?php if (empty($lec->thumbnail)): ?>
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 48px; background: linear-gradient(135deg, #1e293b, #0f172a);">
                                                    🎥
                                                </div>
                                            <?php endif; ?>
                                            <div style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.75); color: #fff; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                                                <?php echo esc_html($lec->duration); ?>
                                            </div>
                                        </div>
                                        <div class="card-body" style="padding: 24px;">
                                            <h4 class="card-title-sm" style="font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 10px; line-height: 1.4;"><?php echo esc_html($lec->title); ?></h4>
                                            <div class="instructor-row" style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; color: #64748b;">
                                                    <?php echo strtoupper(substr($lec->instructor, 0, 1)); ?>
                                                </div>
                                                <span style="font-size: 13px; font-weight: 600; color: #64748b;"><?php echo esc_html($lec->instructor); ?></span>
                                            </div>
                                            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6;"><?php echo wp_trim_words(wp_strip_all_tags($lec->description), 15); ?></p>
                                            <div style="margin-top: auto;">
                                                <?php 
                                                    $play_url = esc_url($lec->video_url);
                                                ?>
                                                <a href="<?php echo $play_url; ?>" target="_blank" class="btn-action-elite btn-primary" style="display: block; text-align: center; width: 100%; padding: 12px; background: #6366f1; color: #fff; border-radius: 12px; font-weight: 800; text-decoration: none; transition: background 0.3s;">
                                                    ▶ Play Lecture
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="gep-empty-state-premium" style="position: relative; text-align: center; padding: 100px 20px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); overflow: hidden; border: 1px solid rgba(255,255,255,0.05); margin-top: 20px;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 50% 50%, rgba(99,102,241,0.15) 0%, transparent 60%); z-index: 0;"></div>
                <div style="position: relative; z-index: 1;">
                    <div style="font-size: 80px; margin-bottom: 30px; animation: placeholderFloat 3s infinite alternate; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5));">🎬</div>
                    <h3 style="font-size: 32px; font-weight: 900; color: #fff; margin-bottom: 16px; letter-spacing: -0.5px;">No Lectures Available Yet</h3>
                    <p style="color: #94a3b8; font-size: 16px; max-width: 500px; margin: 0 auto 30px auto; line-height: 1.6;">Our educators are currently curating high-quality video content. Please check back later for new premium lectures.</p>
                    <a href="<?php echo add_query_arg( 'view', 'supercoaching', (string) gep_get_url('dashboard') ); ?>" style="display: inline-flex; align-items: center; gap: 10px; background: #fff; color: #0f172a; padding: 14px 32px; border-radius: 16px; font-weight: 800; font-size: 15px; text-decoration: none; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(0,0,0,0.2);">
                        <span>Explore SuperCoaching</span>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
