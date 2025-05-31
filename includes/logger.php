<?php
if (!defined('ABSPATH')) exit;

class Gokul_Logger {
    public static function log($msg, $type='employee') {
        $log = get_option("gokul_{$type}_log", '');
        $log .= date('Y-m-d H:i:s')." | $msg\n";
        update_option("gokul_{$type}_log", $log);
    }
    public static function get_log($type='employee') {
        return get_option("gokul_{$type}_log", '');
    }
}

/**
 * Log a message to a file in wp-content/gokul-debug.log
 * (Use this function everywhere for file-based logging)
 */
function gokul_log($message) {
    $log_file = WP_CONTENT_DIR . '/gokul-debug.log';
    $timestamp = current_time('Y-m-d H:i:s');
    $log_message = sprintf("[%s] %s\n", $timestamp, $message);
    error_log($log_message, 3, $log_file);
}

// Debug block below can be removed in production.
// add_action('init', function() {
//     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gokul_login'])) {
//         gokul_log('Login attempt detected');
//         gokul_log('POST data: ' . print_r($_POST, true));
//     }
// });