<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<style>
.gep-about-wrap {
    max-width: 900px;
    margin: 0 auto;
    padding: 10px 0 40px;
    color: #fff;
}
.gep-about-hero {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 28px;
    padding: 40px;
    margin-bottom: 28px;
}
.gep-about-hero h1 {
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -0.8px;
    margin: 0 0 12px;
}
.gep-about-hero p {
    font-size: 15px;
    color: #a1a1aa;
    line-height: 1.7;
    margin: 0;
}
.gep-about-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 28px;
}
@media (max-width: 768px) {
    .gep-about-grid { grid-template-columns: 1fr; }
    .gep-about-hero { padding: 24px; border-radius: 20px; }
    .gep-about-hero h1 { font-size: 24px; }
}
.gep-about-card {
    background: #18181b;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 20px;
    padding: 24px;
}
.gep-about-card .icon { font-size: 26px; margin-bottom: 10px; }
.gep-about-card h3 { font-size: 15px; font-weight: 800; margin: 0 0 8px; }
.gep-about-card p { font-size: 13px; color: #a1a1aa; line-height: 1.6; margin: 0; }
</style>

<div class="gep-about-wrap">
    <div class="gep-about-hero">
        <h1>About GoPath<span style="color:#6366f1;">.</span></h1>
        <p>
            GoPath Exam Portal is an online test-preparation platform offering bilingual (English/Hindi) mock tests,
            previous year question papers, subject-wise practice, and dedicated Sanskrit test series for competitive
            and academic exams. Our goal is to give every aspirant a realistic, exam-like practice environment along
            with detailed performance analytics to help them improve with every attempt.
        </p>
    </div>

    <div class="gep-about-grid">
        <div class="gep-about-card">
            <div class="icon">🎯</div>
            <h3>What We Offer</h3>
            <p>Full-length and sectional mock tests, previous year papers, Sanskrit-focused test series, and a custom test builder.</p>
        </div>
        <div class="gep-about-card">
            <div class="icon">🌐</div>
            <h3>Bilingual by Design</h3>
            <p>Questions, options, and solutions are available in English and Hindi where translations exist, so you can study in the language you're comfortable with.</p>
        </div>
        <div class="gep-about-card">
            <div class="icon">📊</div>
            <h3>Detailed Analytics</h3>
            <p>Every attempt comes with a subject-wise breakdown, accuracy tracking, and rank/percentile insight to guide your preparation.</p>
        </div>
    </div>

    <div class="gep-about-card" style="text-align:center;">
        <h3 style="margin-bottom:6px;">Questions or feedback?</h3>
        <p style="margin-bottom:16px;">Reach out through Help &amp; Support and our team will get back to you.</p>
        <a href="<?php echo esc_url( add_query_arg( 'view', 'support', (string) gep_get_url( 'dashboard' ) ) ); ?>" class="gep-btn-mini" style="text-decoration:none;">Go to Help &amp; Support →</a>
    </div>
</div>
