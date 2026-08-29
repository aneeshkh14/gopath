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
            <h1>Welcome Back</h1>
            <p>Login to your exam portal</p>
        </div>

        <?php if ( isset($_GET['login_error']) ) : ?>
            <div class="gep-auth-alert gep-alert-error">
                <?php 
                if ( $_GET['login_error'] === 'nonce' ) {
                    echo 'Security check failed. Please try again.';
                } else {
                    echo 'Invalid username or password. Please try again.';
                }
                ?>
            </div>
        <?php elseif ( isset($_GET['success']) && $_GET['success'] === 'reset_sent' ) : ?>
            <div class="gep-auth-alert gep-alert-success">
                Password reset link has been sent to your email.
            </div>
        <?php endif; ?>

        <?php if ( isset($_GET['view']) && $_GET['view'] === 'otp' && isset($_GET['uid']) ) : ?>
            <form id="gep-otp-form" class="gep-auth-form">
                <p class="gep-otp-info">We've sent a 6-digit code to your registered email. Please enter it below to verify.</p>
                <?php wp_nonce_field( 'gep_otp_nonce', 'gep_nonce' ); ?>
                <input type="hidden" id="gep_otp_uid" value="<?php echo absint($_GET['uid']); ?>">
                <div class="gep-form-group">
                    <label>Verification Code</label>
                    <div class="gep-otp-inputs">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="0">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="1">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="2">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="3">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="4">
                        <input type="text" class="gep-otp-box" maxlength="1" data-index="5">
                    </div>
                    <input type="hidden" id="gep_otp_code">
                </div>
                <button type="button" id="gep-verify-otp-btn" class="gep-btn gep-btn-primary gep-btn-block">Verify & Login</button>
                <p id="gep-otp-error" class="gep-error-msg" style="display:none;"></p>
            </form>
        <?php else : ?>
            <form id="gep-login-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="gep_login">
                <?php wp_nonce_field( 'gep_login', 'gep_nonce' ); ?>
                
                <div class="gep-form-group">
                    <label for="log">Username or Email</label>
                    <input type="text" name="log" id="log" class="gep-input" required placeholder="Enter your username">
                </div>

                <div class="gep-form-group">
                    <label for="pwd">Password</label>
                    <div class="gep-password-wrapper">
                        <input type="password" name="pwd" id="pwd" class="gep-input" required placeholder="••••••••">
                    </div>
                </div>

                <div class="gep-form-options">
                    <label class="gep-checkbox-label">
                        <input type="checkbox" name="rememberme" value="forever"> Remember Me
                    </label>
                    <a href="<?php echo gep_get_url('forgot-password'); ?>" class="gep-link">Forgot Password?</a>
                </div>

                <button type="submit" name="gep_login_submit" class="gep-btn gep-btn-primary gep-btn-block">Login Now</button>
            </form>
        <?php endif; ?>

        <div class="gep-auth-footer">
            <?php if ( isset($_GET['view']) && $_GET['view'] === 'otp' ) : ?>
                <a href="<?php echo gep_get_url('login'); ?>" class="gep-link">Back to Login</a>
            <?php else : ?>
                <p>Don't have an account? <a href="<?php echo gep_get_url('register'); ?>" class="gep-link">Register here</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
