<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * High-Fidelity Landing Page Redesign
 * A stunning, premium entry point for the GoPath Exam Portal.
 */
?>

<div class="gep-home-wrapper">
    <!-- Hero Section -->
    <header class="gep-hero">
        <div class="gep-hero-overlay"></div>
        <div class="gep-container">
            <div class="gep-hero-content">
                <span class="gep-badge-promo">UGC NET · PAPER 1 · SANSKRIT</span>
                <h1>Make your next<br>practice session count.</h1>
                <p>Prepare with subject-wise mock tests, Sanskrit practice, previous year papers, and video lessons. Review each attempt and choose what to study next.</p>
                <div class="gep-hero-actions">
                    <a href="<?php echo gep_get_url('register'); ?>" class="gep-btn-hero-primary">Create free account</a>
                    <a href="<?php echo gep_get_url('dashboard'); ?>?view=tests" class="gep-btn-hero-outline">Explore Test Series</a>
                </div>
                <div class="gep-hero-stats">
                    <div class="stat"><strong>Mock tests</strong> Practice by subject</div>
                    <div class="stat"><strong>Past papers</strong> Know the question pattern</div>
                    <div class="stat"><strong>Solutions</strong> Learn from each attempt</div>
                </div>
            </div>
            <div class="gep-hero-visual">
                <div class="gep-floating-card">
                    <div class="card-icon">🏆</div>
                    <div class="card-text">
                        <span>Your next step</span>
                        <strong>Practice with purpose</strong>
                    </div>
                </div>
                <div class="gep-main-viz">
                    <!-- Placeholder for generate_image result -->
                    <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Students studying together" loading="lazy">
                </div>
            </div>
        </div>
    </header>

    <!-- Features Section -->
    <section class="gep-features">
        <div class="gep-container">
            <div class="gep-section-title">
                <h2>Why Choose GoPath?</h2>
                <p>Practice, understand your mistakes, and build confidence.</p>
            </div>
            <div class="gep-features-grid">
                <div class="gep-feature-card">
                    <div class="icon">🧬</div>
                    <h3>Practice Your Way</h3>
                    <p>Choose a full test or build a practice set from available subjects and topics.</p>
                </div>
                <div class="gep-feature-card">
                    <div class="icon">🛡️</div>
                    <h3>Exam-style Practice</h3>
                    <p>Get familiar with timed questions, the question palette, and marking answers for review.</p>
                </div>
                <div class="gep-feature-card">
                    <div class="icon">📈</div>
                    <h3>Understand Your Results</h3>
                    <p>Review solutions and subject-wise performance to plan your next study session.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="gep-social">
        <div class="gep-container">
            <div class="gep-trust-banner">
                <span>BUILD YOUR STUDY ROUTINE</span>
                <div class="logos">
                    <span class="logo">Practice</span>
                    <span class="logo">Review</span>
                    <span class="logo">Improve</span>
                    <span class="logo">Repeat</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="gep-cta">
        <div class="gep-container">
            <div class="gep-cta-box">
                <h2>Ready for your next practice test?</h2>
                <p>Create an account to explore available tests and start your preparation.</p>
                <a href="<?php echo gep_get_url('register'); ?>" class="gep-btn gep-btn-primary gep-btn-lg">Join GoPath Now</a>
            </div>
        </div>
    </section>
</div>

<style>
/* Landing Page Styles */
.gep-home-wrapper {
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    background: #fff;
    overflow-x: hidden;
}

.gep-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero */
.gep-hero {
    position: relative;
    padding: 64px 0;
    background: #0f172a;
    color: #fff;
    overflow: hidden;
}

.gep-hero .gep-container {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 60px;
    align-items: center;
    position: relative;
    z-index: 2;
}

.gep-hero-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%);
}

.gep-badge-promo {
    display: inline-block;
    padding: 6px 12px;
    background: rgba(59, 130, 246, 0.1);
    color: #60a5fa;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

.gep-hero h1 {
    font-size: 64px;
    line-height: 1.1;
    font-weight: 900;
    margin: 0 0 25px;
}

.gep-text-gradient {
    background: linear-gradient(90deg, #60a5fa, #a855f7);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.gep-hero p {
    font-size: 18px;
    line-height: 1.6;
    color: #94a3b8;
    margin-bottom: 40px;
    max-width: 500px;
}

.gep-hero-actions {
    display: flex;
    gap: 20px;
    margin-bottom: 50px;
}

.gep-btn-hero-primary {
    padding: 16px 32px;
    background: #3b82f6;
    color: #fff;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s;
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
}

.gep-btn-hero-outline {
    padding: 16px 32px;
    border: 1px solid #334155;
    color: #fff;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
}

.gep-hero-stats {
    display: flex;
    gap: 30px;
}

.gep-hero-stats .stat {
    font-size: 14px;
    color: #64748b;
}

.gep-hero-stats .stat strong {
    color: #fff;
    display: block;
    font-size: 20px;
}

/* Visuals */
.gep-hero-visual {
    position: relative;
}

.gep-main-viz img {
    width: 100%;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}

.gep-floating-card {
    position: absolute;
    top: -20px;
    left: -20px;
    background: #fff;
    color: #000;
    padding: 15px 25px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Features */
.gep-features {
    padding: 100px 0;
}

.gep-section-title {
    text-align: center;
    margin-bottom: 60px;
}

.gep-section-title h2 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 15px;
}

.gep-features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
}

.gep-feature-card {
    padding: 40px;
    background: #f8fafc;
    border-radius: 20px;
    border: 1px solid #f1f5f9;
    transition: transform 0.3s;
}

.gep-feature-card:hover {
    transform: translateY(-5px);
    background: #fff;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
}

.gep-feature-card .icon {
    font-size: 32px;
    margin-bottom: 20px;
}

/* CTA */
.gep-cta {
    padding: 100px 0;
}

.gep-cta-box {
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    padding: 60px;
    border-radius: 30px;
    text-align: center;
    color: #fff;
}

.gep-cta-box h2 {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 20px;
}

.gep-cta-box .gep-btn {
    background: #fff;
    color: #3b82f6;
    margin-top: 30px;
}

/* Social Proof Banner Styles */
.gep-social {
    padding: 60px 0;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}

.gep-trust-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.gep-trust-banner > span {
    font-size: 13px;
    font-weight: 800;
    color: #94a3b8;
    letter-spacing: 1.5px;
}

.gep-trust-banner .logos {
    display: flex;
    gap: 40px;
    align-items: center;
    flex-wrap: wrap;
}

.gep-trust-banner .logo {
    font-size: 18px;
    font-weight: 900;
    color: #cbd5e1;
    letter-spacing: 1px;
}

/* ==========================================================================
   Responsive Overrides for Landing Page
   ========================================================================== */
@media (max-width: 1024px) {
    .gep-hero {
        padding: 80px 0 60px;
    }
    .gep-hero .gep-container {
        grid-template-columns: 1fr;
        gap: 40px;
        text-align: center;
    }
    .gep-hero-content {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .gep-hero h1 {
        font-size: 44px;
    }
    .gep-hero p {
        margin: 0 auto 30px auto;
    }
    .gep-hero-actions {
        justify-content: center;
        margin-bottom: 40px;
    }
    .gep-hero-stats {
        justify-content: center;
    }
    .gep-hero-visual {
        max-width: 500px;
        margin: 0 auto;
        width: 100%;
    }
    .gep-features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .gep-feature-card {
        padding: 30px;
    }
}

@media (max-width: 768px) {
    .gep-features {
        padding: 60px 0;
    }
    .gep-features-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .gep-social {
        padding: 40px 0;
    }
    .gep-trust-banner {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    .gep-trust-banner .logos {
        justify-content: center;
        gap: 25px;
    }
    .gep-cta {
        padding: 60px 0;
    }
    .gep-cta-box {
        padding: 40px 20px;
        border-radius: 20px;
    }
    .gep-cta-box h2 {
        font-size: 32px;
    }
}

@media (max-width: 480px) {
    .gep-hero h1 {
        font-size: 32px;
    }
    .gep-hero-actions {
        flex-direction: column;
        width: 100%;
        gap: 12px;
    }
    .gep-hero-actions a {
        width: 100%;
        text-align: center;
        padding: 14px 24px;
    }
    .gep-hero-stats {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    .gep-floating-card {
        display: none;
    }
}
</style>
