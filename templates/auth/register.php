<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( is_user_logged_in() ) {
    wp_redirect( gep_get_url('dashboard') );
    exit;
}
?>

<div class="gep-auth-container">
    <div class="gep-auth-card">
        <div class="gep-auth-header">
            <img src="<?php echo GEP_PLUGIN_URL . 'assets/images/gep-logo.png'; ?>" alt="Logo" class="gep-logo">
            <h1>Create Account</h1>
            <p>Join the GoPath Exam community</p>
        </div>

        <?php if ( isset($_GET['error']) ) : ?>
            <div class="gep-auth-alert gep-alert-error">
                <?php 
                $error = $_GET['error'];
                if ($error === 'existing_user_login') {
                    echo 'This email or username is already registered. <a href="'.gep_get_url('login').'">Login instead?</a>';
                } elseif ($error === 'missing_fields') {
                    echo 'Please fill in all required fields.';
                } elseif ($error === 'invalid_email') {
                    echo 'Please enter a valid email address.';
                } elseif ($error === 'password_too_short') {
                    echo 'Password must be at least 8 characters long.';
                } elseif ($error === 'nonce') {
                    echo 'Security check failed. Please try again.';
                } else {
                    echo 'Registration failed. Please try again.';
                }
                ?>
            </div>
        <?php endif; ?>

        <form id="gep-register-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="gep_register">
            <?php wp_nonce_field( 'gep_register', 'gep_nonce' ); ?>
            
            <div class="gep-form-row">
                <div class="gep-form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="gep-input" required placeholder="John">
                </div>
                <div class="gep-form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="gep-input" required placeholder="Doe">
                </div>
            </div>

            <div class="gep-form-group">
                <label for="user_login">Username</label>
                <input type="text" name="user_login" id="user_login" class="gep-input" required placeholder="johndoe123">
            </div>

            <div class="gep-form-group">
                <label for="user_email">Email Address</label>
                <input type="email" name="user_email" id="user_email" class="gep-input" required placeholder="john@example.com">
            </div>

            <div class="gep-form-group">
                <label for="user_pass">Password</label>
                <input type="password" name="user_pass" id="user_pass" class="gep-input" required minlength="8" placeholder="••••••••">
            </div>

            <div class="gep-form-group gep-consent-group" style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
                <input type="checkbox" name="gep_consent" id="gep_consent" required style="margin-top: 4px; accent-color: #0ea5e9;">
                <label for="gep_consent" style="font-size: 13px; color: #94a3b8; font-weight: normal; margin: 0;">
                    I agree to the <a href="<?php echo esc_url(add_query_arg('view', 'policies', gep_get_url('dashboard'))); ?>#terms" target="_blank" style="color: #0ea5e9;">Terms of Service</a>, <a href="<?php echo esc_url(add_query_arg('view', 'policies', gep_get_url('dashboard'))); ?>#privacy" target="_blank" style="color: #0ea5e9;">Privacy Policy</a>, and <a href="<?php echo esc_url(add_query_arg('view', 'policies', gep_get_url('dashboard'))); ?>#refund" target="_blank" style="color: #0ea5e9;">Refund Policy</a>.
                </label>
            </div>

            <button type="submit" name="gep_register_submit" class="gep-btn gep-btn-primary gep-btn-block">Sign Up</button>
        </form>

        <div class="gep-auth-footer">
            <p>Already have an account? <a href="<?php echo gep_get_url('login'); ?>" class="gep-link">Login here</a></p>
        </div>
    </div>
</div>
