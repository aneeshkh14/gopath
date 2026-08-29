<div class="wrap gep-admin-wrap">
    <?php if ( ! get_option( 'blog_public', 1 ) ) : ?>
        <div class="gep-indexing-warning" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 20px; border-radius: 16px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="font-size: 32px; flex-shrink: 0;">⚠️</div>
            <div>
                <strong style="font-size: 15px; color: #92400e; display: block; margin-bottom: 4px;">Search Engine Indexing is Currently Blocked!</strong>
                <p style="margin: 0; font-size: 13px; color: #b45309; line-height: 1.5; font-weight: 600;">
                    Your WordPress configuration is set to discourage search engines from indexing this site. This prevents your mock tests, PYQ papers, and coaching classes from showing up in Google search results. 
                    To fix this and allow indexing, navigate to <a href="<?php echo admin_url('options-reading.php'); ?>" style="color: #0369a1; text-decoration: underline; font-weight: 800;">Settings &gt; Reading</a> and uncheck the checkbox for <strong>"Discourage search engines from indexing this site"</strong>.
                </p>
            </div>
        </div>
    <?php endif; ?>
    <div class="gep-admin-header" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="margin: 0; font-size: 28px; letter-spacing: -1px;">Portal Infrastructure</h1>
            <p style="color: var(--admin-muted); font-weight: 600; font-size: 15px;">Architect your core payments, security protocols, and pedagogical intelligence.</p>
        </div>
        <div style="background: #f0fdf4; border: 1px solid #dcfce7; padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 10px;">
            <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px #22c55e;"></span>
            <span style="color: #166534; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">System Active: v<?php echo GEP_VERSION; ?></span>
        </div>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'gep_settings_group' ); ?>
        
        <div class="gep-admin-recent-activity" style="padding: 35px 45px; border-radius: 32px; box-shadow: var(--admin-shadow-lg); background: #fff; border: 1px solid var(--admin-border);">
            <!-- Section 1: Revenue -->
            <div style="margin-bottom: 50px;">
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #fff1f2; color: #e11d48; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #ffe4e6;">💳</div>
                    Payment & Revenue Gateway
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Secure your transactions by connecting your Razorpay merchant credentials.</p>
                
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; margin-bottom: 25px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Razorpay Key ID</label>
                    <input type="text" name="gep_razorpay_key_id" value="<?php echo esc_attr( get_option('gep_razorpay_key_id') ); ?>" style="max-width: 600px; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; background: #f8fafc;" placeholder="rzp_live_...">
                </div>

                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Razorpay Secret</label>
                    <input type="password" name="gep_razorpay_key_secret" value="<?php echo esc_attr( get_option('gep_razorpay_key_secret') ); ?>" style="max-width: 600px; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; background: #f8fafc;">
                </div>
            </div>

            <!-- Section 2: Pedagogy -->
            <div style="margin-bottom: 50px;">
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #f0f9ff; color: #0369a1; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #e0f2fe;">🎓</div>
                    Pedagogical Intelligence
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Establish the default instructional leadership and platform preferences.</p>
                
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; margin-bottom: 25px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Lead Instructor Name</label>
                    <input type="text" name="gep_default_instructor" value="<?php echo esc_attr( get_option('gep_default_instructor', 'Academic Lead') ); ?>" style="max-width: 600px; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; background: #f8fafc;">
                </div>

                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Default Stream Engine</label>
                    <select name="gep_live_meeting_platform" style="max-width: 600px; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 10px 20px; font-weight: 700; cursor: pointer; background: #f8fafc; box-sizing: border-box; line-height: 1.5;">
                        <option value="zoom" <?php selected( get_option('gep_live_meeting_platform'), 'zoom' ); ?>>Zoom Professional</option>
                        <option value="youtube" <?php selected( get_option('gep_live_meeting_platform'), 'youtube' ); ?>>YouTube Live (Broadcast)</option>
                        <option value="meet" <?php selected( get_option('gep_live_meeting_platform'), 'meet' ); ?>>Google Meet</option>
                    </select>
                </div>
            </div>

            <!-- Section 3: Security -->
            <div style="margin-bottom: 50px;">
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #fdf2f7; color: #9d174d; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #fce7f3;">🛡️</div>
                    Exam Integrity Engine
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Define how the neural engine reacts to unauthorized student activity.</p>
                
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Violation Protocol</label>
                    <select name="gep_violation_action" style="max-width: 600px; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 10px 20px; font-weight: 700; cursor: pointer; background: #f8fafc; box-sizing: border-box; line-height: 1.5;">
                        <option value="warn" <?php selected( get_option('gep_violation_action'), 'warn' ); ?>>Log Activity & Warn Student</option>
                        <option value="submit" <?php selected( get_option('gep_violation_action'), 'submit' ); ?>>Strict Termination (Auto-submit)</option>
                    </select>
                </div>
            </div>

            <!-- Section 3.5: Dashboard Slideshow -->
            <div style="margin-bottom: 50px;">
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #f5f3ff; color: #7c3aed; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #ddd6fe;">📺</div>
                    Student Dashboard Slideshow Banners
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Manage promotional slides at the top of the student dashboard.</p>
                
                <h3 style="font-size: 16px; font-weight: 800; color: #1e1b4b; margin: 25px 0 15px 0; border-left: 4px solid #7c3aed; padding-left: 10px;">Slide 1 Configuration</h3>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 1 Title</label>
                    <input type="text" name="gep_slide1_title" value="<?php echo esc_attr( get_option('gep_slide1_title', 'Mission Officer 2026') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 1 Description</label>
                    <textarea name="gep_slide1_desc" rows="2" style="max-width: 600px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 10px 15px; font-weight: 700; background: #f8fafc; font-family: inherit; font-size: 13px; resize: vertical;"><?php echo esc_textarea( get_option('gep_slide1_desc', 'Your Journey to Government Job Starts Here. Get access to premium tests and video courses.') ); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 1 Button Text</label>
                    <input type="text" name="gep_slide1_btn" value="<?php echo esc_attr( get_option('gep_slide1_btn', 'Explore Academy') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 1 Target URL</label>
                    <input type="text" name="gep_slide1_url" value="<?php echo esc_attr( get_option('gep_slide1_url', '') ); ?>" placeholder="Leave blank to use default Explore Academy link" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 35px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 1 Image Banner</label>
                    <div style="display: flex; gap: 10px; max-width: 600px; width: 100%;">
                        <input type="text" id="gep_slide1_image" name="gep_slide1_image" value="<?php echo esc_attr( get_option('gep_slide1_image', '') ); ?>" placeholder="Paste image URL or click Choose Banner" style="flex: 1; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                        <button type="button" class="button gep-upload-image-btn" data-target="gep_slide1_image" style="height: 45px; border-radius: 12px; font-weight: 800; padding: 0 20px; border: 2px solid #7c3aed; color: #7c3aed; background: transparent;">Choose Banner</button>
                    </div>
                </div>

                <h3 style="font-size: 16px; font-weight: 800; color: #064e3b; margin: 25px 0 15px 0; border-left: 4px solid #10b981; padding-left: 10px;">Slide 2 Configuration</h3>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 2 Title</label>
                    <input type="text" name="gep_slide2_title" value="<?php echo esc_attr( get_option('gep_slide2_title', 'UGC NET Mock Tests') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 2 Description</label>
                    <textarea name="gep_slide2_desc" rows="2" style="max-width: 600px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 10px 15px; font-weight: 700; background: #f8fafc; font-family: inherit; font-size: 13px; resize: vertical;"><?php echo esc_textarea( get_option('gep_slide2_desc', 'Challenge yourself with realistic full-length paper simulations. Track your progress with advanced cohort analytics.') ); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 2 Button Text</label>
                    <input type="text" name="gep_slide2_btn" value="<?php echo esc_attr( get_option('gep_slide2_btn', 'Practice Now') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 2 Target URL</label>
                    <input type="text" name="gep_slide2_url" value="<?php echo esc_attr( get_option('gep_slide2_url', '') ); ?>" placeholder="Leave blank to use default Practice Now link" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 35px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 2 Image Banner</label>
                    <div style="display: flex; gap: 10px; max-width: 600px; width: 100%;">
                        <input type="text" id="gep_slide2_image" name="gep_slide2_image" value="<?php echo esc_attr( get_option('gep_slide2_image', '') ); ?>" placeholder="Paste image URL or click Choose Banner" style="flex: 1; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                        <button type="button" class="button gep-upload-image-btn" data-target="gep_slide2_image" style="height: 45px; border-radius: 12px; font-weight: 800; padding: 0 20px; border: 2px solid #10b981; color: #10b981; background: transparent;">Choose Banner</button>
                    </div>
                </div>

                <h3 style="font-size: 16px; font-weight: 800; color: #581c87; margin: 25px 0 15px 0; border-left: 4px solid #a855f7; padding-left: 10px;">Slide 3 Configuration</h3>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 3 Title</label>
                    <input type="text" name="gep_slide3_title" value="<?php echo esc_attr( get_option('gep_slide3_title', 'Live Doubt Solving') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 3 Description</label>
                    <textarea name="gep_slide3_desc" rows="2" style="max-width: 600px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 10px 15px; font-weight: 700; background: #f8fafc; font-family: inherit; font-size: 13px; resize: vertical;"><?php echo esc_textarea( get_option('gep_slide3_desc', 'Connect with top educators in real-time interactively. Resolve conceptual doubts and learn exam techniques.') ); ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 3 Button Text</label>
                    <input type="text" name="gep_slide3_btn" value="<?php echo esc_attr( get_option('gep_slide3_btn', 'Join Live Class') ); ?>" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 3 Target URL</label>
                    <input type="text" name="gep_slide3_url" value="<?php echo esc_attr( get_option('gep_slide3_url', '') ); ?>" placeholder="Leave blank to use default Join Live Class link" style="max-width: 600px; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                </div>
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: center; margin-bottom: 15px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Slide 3 Image Banner</label>
                    <div style="display: flex; gap: 10px; max-width: 600px; width: 100%;">
                        <input type="text" id="gep_slide3_image" name="gep_slide3_image" value="<?php echo esc_attr( get_option('gep_slide3_image', '') ); ?>" placeholder="Paste image URL or click Choose Banner" style="flex: 1; height: 45px; border-radius: 12px; border: 2px solid #e2e8f0; padding: 0 15px; font-weight: 700; background: #f8fafc;">
                        <button type="button" class="button gep-upload-image-btn" data-target="gep_slide3_image" style="height: 45px; border-radius: 12px; font-weight: 800; padding: 0 20px; border: 2px solid #a855f7; color: #a855f7; background: transparent;">Choose Banner</button>
                    </div>
                </div>
            </div>

            <!-- Section 4: Routing -->
            <div>
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #f8fafc; color: #475569; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #e2e8f0;">🔗</div>
                    Navigation Architecture
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Customize the URI architecture for your frontend student portals.</p>
                
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; margin-bottom: 25px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Dashboard URI</label>
                    <div style="position: relative; max-width: 600px;">
                        <span style="position: absolute; left: 20px; top: 16px; color: #94a3b8; font-weight: 600; font-size: 14px;">/</span>
                        <input type="text" name="gep_slug_dashboard" value="<?php echo esc_attr( get_option('gep_slug_dashboard', 'dashboard') ); ?>" style="width: 100%; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px 0 35px; font-weight: 700; background: #f8fafc;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Exam Portal URI</label>
                    <div style="position: relative; max-width: 600px;">
                        <span style="position: absolute; left: 20px; top: 16px; color: #94a3b8; font-weight: 600; font-size: 14px;">/</span>
                        <input type="text" name="gep_slug_exam" value="<?php echo esc_attr( get_option('gep_slug_exam', 'exam') ); ?>" style="width: 100%; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px 0 35px; font-weight: 700; background: #f8fafc;">
                    </div>
                </div>
            </div>

            <!-- Section 5: SEO & Indexing -->
            <div style="margin-top: 50px; border-top: 1px solid #f1f5f9; padding-top: 40px;">
                <h2 style="font-size: 22px; font-weight: 900; color: #0f172a; margin-bottom: 12px; display: flex; align-items: center; gap: 15px;">
                    <div style="width: 42px; height: 42px; background: #ecfdf5; color: #059669; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #d1fae5;">🔍</div>
                    SEO & Search Engine Indexing
                </h2>
                <p style="color: #64748b; font-weight: 600; margin-bottom: 30px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; font-size: 14px;">Verify ownership with Google Search Console and monitor dynamic XML sitemaps.</p>
                
                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; margin-bottom: 25px;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">Google Site Verification Tag</label>
                    <div style="max-width: 600px; width: 100%;">
                        <input type="text" name="gep_google_verification" value="<?php echo esc_attr( get_option('gep_google_verification', '') ); ?>" style="width: 100%; height: 50px; border-radius: 16px; border: 2px solid #e2e8f0; padding: 0 20px; font-weight: 700; background: #f8fafc;" placeholder='e.g., google5776b94ff420aca2 or the full HTML tag'>
                        <span style="display: block; font-size: 12px; color: #94a3b8; font-weight: 600; margin-top: 8px; line-height: 1.4;">
                            Select <strong>"HTML tag"</strong> as your verification method in Google Search Console, copy the meta tag, and paste it here. We will dynamically output it in the HTML head.
                        </span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center;">
                    <label style="font-weight: 800; color: #334155; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">XML Sitemap Registry</label>
                    <div style="max-width: 600px; width: 100%;">
                        <a href="<?php echo esc_url( home_url( '/gep-sitemap.xml' ) ); ?>" target="_blank" style="font-size: 14px; font-weight: 800; color: #6366f1; text-decoration: none; border-bottom: 2px dashed #6366f1; padding-bottom: 2px;">
                            <?php echo esc_html( home_url( '/gep-sitemap.xml' ) ); ?> ↗
                        </a>
                        <span style="display: block; font-size: 12px; color: #94a3b8; font-weight: 600; margin-top: 8px; line-height: 1.4;">
                            Submit this URL directly in the **Sitemaps** section of Google Search Console to submit all 500+ mock tests and courses.
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 35px; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 30px 40px; border-radius: 28px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow-lg);">
            <div style="display: flex; gap: 20px;">
                <button type="submit" class="button button-primary" style="height: 55px; border-radius: 16px; padding: 0 45px; font-weight: 900; font-size: 15px; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);">Commit All Architecture</button>
                <a href="<?php echo admin_url('admin.php?page=gep-settings&action=update_db&nonce=' . wp_create_nonce('gep_update_db')); ?>" class="button" style="height: 55px; line-height: 53px; border-radius: 16px; padding: 0 30px; font-weight: 900; background: #10b981; color: #fff; border: none; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);">Sync Database Architecture</a>
            </div>
            <button type="button" class="button" style="height: 55px; border-radius: 16px; padding: 0 30px; font-weight: 900; color: #ef4444; border: 2px solid #ef4444; background: transparent; font-size: 13px;" onclick="if(confirm('🚨 CRITICAL: Are you sure you want to wipe all portal data? This cannot be undone.')) { window.location.href = '<?php echo admin_url('admin.php?page=gep-settings&action=reset_data&nonce=' . wp_create_nonce('gep_reset_all')); ?>'; }">Factory Reset Studio</button>
        </div>
    </form>
</div>
