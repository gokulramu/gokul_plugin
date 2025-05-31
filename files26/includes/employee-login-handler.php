<?php
if (!defined('ABSPATH')) exit;

function gokul_handle_login() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gokul_login'])) {
        gokul_log("Processing login form submission");
        
        if (!isset($_POST['email'], $_POST['password'])) {
            gokul_log("Missing email or password");
            return;
        }

        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        
        if ($user && wp_check_password($_POST['password'], $user->user_pass, $user->ID)) {
            gokul_log("Valid credentials for user: " . $user->user_login);
            
            if (in_array('gokul_employee', (array)$user->roles) || in_array('administrator', (array)$user->roles)) {
                gokul_log("User has correct role, processing login");
                
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                
                gokul_log("Login successful for: " . $user->user_login);
                
                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                // Use direct HTML output with meta refresh
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <meta http-equiv="refresh" content="0;url=<?php echo esc_url(site_url('/employee-corner/')); ?>">
                    <title>Redirecting...</title>
                </head>
                <body>
                    <p>Login successful. Redirecting to Employee Corner...</p>
                    <script>
                        window.location.href = '<?php echo esc_url(site_url('/employee-corner/')); ?>';
                    </script>
                </body>
                </html>
                <?php
                exit;
            }
        }
        
        // Login failed
        gokul_log("Login failed");
        wp_safe_redirect(add_query_arg('login_error', '1', site_url('/employee-login/')));
        exit;
    }
}
add_action('init', 'gokul_handle_login', 1);

// Handle page access
function gokul_check_page_access() {
    $current_url = $_SERVER['REQUEST_URI'];
    gokul_log("Checking access for: " . $current_url);
    
    if (strpos($current_url, '/employee-corner/') !== false) {
        if (!is_user_logged_in()) {
            gokul_log("User not logged in, redirecting to login");
            wp_safe_redirect(site_url('/employee-login/'));
            exit;
        }
        
        $user = wp_get_current_user();
        if (!in_array('gokul_employee', (array)$user->roles) && !in_array('administrator', (array)$user->roles)) {
            gokul_log("User lacks required role, redirecting to login");
            wp_safe_redirect(site_url('/employee-login/'));
            exit;
        }
        
        gokul_log("Access granted to employee corner for: " . $user->user_login);
    }
    
    if (strpos($current_url, '/employee-login/') !== false) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('gokul_employee', (array)$user->roles) || in_array('administrator', (array)$user->roles)) {
                gokul_log("Logged in user accessing login page, redirecting");
                wp_safe_redirect(site_url('/employee-corner/'));
                exit;
            }
        }
    }
}
add_action('template_redirect', 'gokul_check_page_access', 1);