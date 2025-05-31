<?php
if (!function_exists('gokul_walmart_api_error_log')) {
    function gokul_walmart_api_error_log($message, $data = null) {
        $log_file = plugin_dir_path(__FILE__) . '../../logs/walmart_api_error.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        $date = date('Y-m-d H:i:s');
        $entry = "[$date] $message";
        if ($data !== null) {
            $entry .= ' | ' . print_r($data, true);
        }
        $entry .= "\n";
        file_put_contents($log_file, $entry, FILE_APPEND);
    }
}
?>