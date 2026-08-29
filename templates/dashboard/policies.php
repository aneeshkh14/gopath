<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// We do not strictly require login to view policies, but it's part of the dashboard UI.
$is_logged_in = is_user_logged_in();
?>

<div class="gep-sovereign-dashboard">
    <div style="margin-bottom: 30px;">
        <h1 class="gep-text-gradient-primary" style="font-size: 32px; font-weight: 900; margin: 0 0 8px; letter-spacing: -1px;">Legal &amp; Policies</h1>
        <p style="color: #64748b; font-weight: 600; margin: 0; font-size: 14px;">Review our terms of service, privacy, and refund policies.</p>
    </div>

    <div class="gep-policy-layout">
        <!-- Sidebar Nav -->
        <div class="gep-policy-sidebar">
            <nav class="gep-policy-nav">
                <a href="#terms" class="gep-policy-nav-link active" data-tab="terms">
                    <span class="nav-icon">📜</span>
                    <span>Terms of Service</span>
                </a>
                <a href="#privacy" class="gep-policy-nav-link" data-tab="privacy">
                    <span class="nav-icon">🔒</span>
                    <span>Privacy Policy</span>
                </a>
                <a href="#refund" class="gep-policy-nav-link" data-tab="refund">
                    <span class="nav-icon">💸</span>
                    <span>Refund Policy</span>
                </a>
            </nav>
        </div>

        <!-- Content Panel -->
        <div class="gep-policy-panel gep-glass">

            <!-- Terms of Service -->
            <section id="tab-terms" class="gep-policy-section active">
                <div class="gep-policy-section-header">
                    <h3>Terms of Service</h3>
                    <span class="policy-date">Last updated: <?php echo date('F Y'); ?></span>
                </div>
                <div class="gep-policy-body">
                    <p>Welcome to GoPath Exam Portal. By accessing or using our platform, you agree to be bound by these Terms of Service.</p>
                    <h4>1. Account Creation &amp; Security</h4>
                    <p>You are responsible for maintaining the confidentiality of your login credentials. You agree to use a maximum of 2 devices concurrently. Any unauthorized access should be reported immediately.</p>
                    <h4>2. Intellectual Property</h4>
                    <p>All test material, video lectures, and courses provided on this platform are the intellectual property of GoPath. You may not distribute, copy, or share this material without explicit written permission.</p>
                    <h4>3. Code of Conduct</h4>
                    <p>During live exams and classes, you are expected to maintain academic integrity. Any form of cheating, screen sharing, or misuse of the platform may result in immediate account suspension.</p>
                    <h4>4. Lifetime Access</h4>
                    <p>Items purchased with a "Lifetime Access" tag will remain available in your account permanently, provided the platform remains operational and your account is not suspended for violations.</p>
                </div>
            </section>

            <!-- Privacy Policy -->
            <section id="tab-privacy" class="gep-policy-section">
                <div class="gep-policy-section-header">
                    <h3>Privacy Policy</h3>
                    <span class="policy-date">Last updated: <?php echo date('F Y'); ?></span>
                </div>
                <div class="gep-policy-body">
                    <p>Your privacy is important to us. This Privacy Policy explains how we collect, use, and protect your data.</p>
                    <h4>1. Information We Collect</h4>
                    <p>We collect information you provide directly to us when creating an account (Name, Email, Phone), as well as academic goals and test performance telemetry.</p>
                    <h4>2. How We Use Your Data</h4>
                    <p>We use your data to dynamically personalize your dashboard, calculate your Global Rank, and provide advanced analytics on your topic mastery. We do not sell your data to third parties.</p>
                    <h4>3. Data Security</h4>
                    <p>We implement strict security measures, including session token validation and encrypted database storage, to protect your personal information.</p>
                </div>
            </section>

            <!-- Refund Policy -->
            <section id="tab-refund" class="gep-policy-section">
                <div class="gep-policy-section-header">
                    <h3>Refund &amp; Cancellation Policy</h3>
                    <span class="policy-date">Last updated: <?php echo date('F Y'); ?></span>
                </div>
                <div class="gep-policy-body">
                    <p>We strive to provide the best possible learning experience. Please read our refund policy carefully.</p>
                    <h4>1. Digital Products</h4>
                    <p>Due to the digital nature of our test series and recorded courses, all sales are considered final once the material has been accessed or an exam has been started.</p>
                    <h4>2. Refund Window</h4>
                    <p>If you have not accessed the purchased material and request a refund within 3 days of purchase, you may be eligible for a full refund. Please contact support.</p>
                    <h4>3. Live Classes</h4>
                    <p>Subscriptions to live classes can be cancelled at any time, but partial refunds for ongoing months are not provided.</p>
                </div>
            </section>

        </div>
    </div>
</div>

<style>
/* ===== Policy Page ===== */
.gep-policy-layout {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 24px;
    align-items: start;
}

.gep-policy-sidebar {
    position: sticky;
    top: 20px;
}

.gep-policy-nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.gep-policy-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    background: transparent;
    border: 1px solid transparent;
    transition: all 0.25s;
}

.gep-policy-nav-link:hover {
    background: rgba(255,255,255,0.04);
    color: #94a3b8;
    border-color: rgba(255,255,255,0.06);
}

.gep-policy-nav-link.active {
    background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(168,85,247,0.12));
    border-color: rgba(99,102,241,0.25);
    color: #a5b4fc !important;
    box-shadow: 0 4px 12px rgba(99,102,241,0.1);
}

.gep-policy-nav-link .nav-icon {
    font-size: 18px;
    line-height: 1;
    flex-shrink: 0;
}

/* Content Panel */
.gep-policy-panel {
    border-radius: 24px;
    border: 1px solid rgba(255,255,255,0.07);
    background: linear-gradient(145deg, rgba(15,23,42,0.8), rgba(2,6,23,0.9));
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    overflow: hidden;
    min-height: 400px;
}

.gep-policy-section {
    display: none;
    padding: 40px;
    animation: fadeInSection 0.3s ease;
}

.gep-policy-section.active {
    display: block;
}

@keyframes fadeInSection {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.gep-policy-section-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    margin-bottom: 30px;
}

.gep-policy-section-header h3 {
    font-size: 22px;
    font-weight: 900;
    color: #f1f5f9;
    margin: 0;
    letter-spacing: -0.5px;
}

.policy-date {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    white-space: nowrap;
    background: rgba(255,255,255,0.04);
    padding: 4px 10px;
    border-radius: 8px;
}

.gep-policy-body {
    color: #94a3b8;
    line-height: 1.75;
    font-size: 14px;
    font-weight: 500;
}

.gep-policy-body p {
    margin: 0 0 16px;
}

.gep-policy-body h4 {
    font-size: 15px;
    font-weight: 800;
    color: #e2e8f0;
    margin: 28px 0 10px;
    padding-left: 14px;
    border-left: 3px solid #6366f1;
}

@media (max-width: 768px) {
    .gep-policy-layout {
        grid-template-columns: 1fr;
    }
    .gep-policy-sidebar {
        position: static;
    }
    .gep-policy-nav {
        flex-direction: row;
        flex-wrap: wrap;
    }
    .gep-policy-section {
        padding: 24px 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.gep-policy-nav-link');
    const sections = document.querySelectorAll('.gep-policy-section');

    function switchTab(targetId) {
        tabs.forEach(t => t.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        const tab = document.querySelector(`.gep-policy-nav-link[data-tab="${targetId}"]`);
        const section = document.getElementById(`tab-${targetId}`);

        if(tab) tab.classList.add('active');
        if(section) section.classList.add('active');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('data-tab');
            switchTab(target);
            history.pushState(null, null, `#${target}`);
        });
    });

    // Check URL hash on load
    if (window.location.hash) {
        const hash = window.location.hash.replace('#', '');
        if (['terms', 'privacy', 'refund'].includes(hash)) {
            switchTab(hash);
        }
    }
});
</script>
