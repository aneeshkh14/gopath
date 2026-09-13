<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="gep-auth-container">
    <div class="gep-auth-card">
        <div class="gep-auth-header">
            <img src="<?php echo GEP_PLUGIN_URL . 'assets/images/gep-logo.png'; ?>" alt="Logo" class="gep-logo">
            <h1>Reset Password</h1>
            <p>Enter your email to receive a reset link</p>
        </div>

        <?php if ( isset($_GET['error']) ) : ?>
            <div class="gep-auth-alert gep-alert-error">
                <?php 
                if ( $_GET['error'] === 'nonce' ) {
                    echo 'Security check failed. Please try again.';
                } else {
                    echo 'Invalid username or email. Please try again.';
                }
                ?>
            </div>
        <?php endif; ?>

        <form id="gep-forgot-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="gep_forgot_password">
            <?php wp_nonce_field( 'gep_forgot', 'gep_nonce' ); ?>
            
            <div class="gep-form-group">
                <label for="user_login">Username or Email</label>
                <input type="text" name="user_login" id="user_login" autocomplete="username" class="gep-input" required placeholder="Enter your email">
            </div>

            <button type="submit" name="gep_forgot_submit" class="gep-btn gep-btn-primary gep-btn-block">Send Reset Link</button>
        </form>

        <div class="gep-auth-footer">
            <a href="<?php echo gep_get_url('login'); ?>" class="gep-link">Back to Login</a>
        </div>
    </div>
</div>
