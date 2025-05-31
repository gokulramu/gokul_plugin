<?php
// Example usage
$result = $api->test_account();
if ($result['success']) {
    echo '<div class="notice notice-success"><p>' . esc_html($result['message']) . '</p></div>';
} else {
    echo '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
}