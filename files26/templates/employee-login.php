<?php 
if (!defined('ABSPATH')) exit;

// Check if user is already logged in
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('gokul_employee', (array)$user->roles) || in_array('administrator', (array)$user->roles)) {
        wp_safe_redirect(site_url('/employee-corner/'));
        exit;
    }
}
?>

<div class="gokul-login-wrapper">
    <div class="gokul-login-form">
        <h2>Employee Login</h2>
        
        <?php if (isset($_GET['login_error'])): ?>
            <div class="login-error">
                Invalid credentials. Please try again.
            </div>
        <?php endif; ?>
        
        <form method="post" action="" id="gokul-login-form">
            <input type="hidden" name="gokul_login" value="1">
            <?php wp_nonce_field('gokul_login_action', 'gokul_login_nonce'); ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="gokul-btn">Login</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ... (previous CSS remains the same) ... */
</style>