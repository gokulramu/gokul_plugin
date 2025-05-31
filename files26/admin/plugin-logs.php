<?php
if (!defined('ABSPATH')) exit;

function gokul_add_log($msg, $type = 'info') {
    $logs = get_option('gokul_plugin_log', []);
    $logs[] = [
        'datetime' => date('Y-m-d H:i:s'),
        'type' => $type,
        'user' => is_user_logged_in() ? wp_get_current_user()->user_login : 'guest',
        'msg' => $msg
    ];
    update_option('gokul_plugin_log', $logs, false);
}
function gokul_get_log()  { return get_option('gokul_plugin_log', []); }
function gokul_clear_log(){ update_option('gokul_plugin_log', []); }

function gokul_plugin_logs_admin() {
    if (isset($_POST['gokul_clear_log']) && check_admin_referer('gokul_clear_log_action')) {
        gokul_clear_log();
        echo '<div class="updated"><p>Log cleared!</p></div>';
    }
    $logs = gokul_get_log();
    echo '<div class="wrap"><h1>Plugin Logs</h1>';
    echo '<form method="post" style="margin-bottom:12px;">';
    wp_nonce_field('gokul_clear_log_action');
    echo '<button type="submit" name="gokul_clear_log" class="button button-danger" onclick="return confirm(\'Clear all logs?\');">Clear Logs</button> ';
    echo '<button type="button" class="button" onclick="gokul_copy_log()">Copy Log To Clipboard</button></form>';
    echo '<textarea id="gokul_log_area" style="width:100%;height:320px;" readonly>';
    foreach ($logs as $log) {
        echo "[{$log['datetime']}] {$log['type']} | {$log['user']} | {$log['msg']}\n";
    }
    echo '</textarea>';
    echo <<<JS
    <script>
    function gokul_copy_log() {
        var area = document.getElementById('gokul_log_area');
        area.select();
        document.execCommand('copy');
        alert('Log copied to clipboard!');
    }
    </script>
    </div>
JS;
}