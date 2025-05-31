<?php
if (!defined('ABSPATH')) exit;
if (!is_user_logged_in() || (!current_user_can('gokul_employee') && !current_user_can('administrator'))) {
    echo '<div class="notice notice-error"><p>Access Denied.</p></div>';
    return;
}
// --- Manual Import Domestic Orders Button (top of page) ---
?>
<form method="post" style="margin-bottom:20px;">
    <?php wp_nonce_field('gokul_import_domestic_orders_action'); ?>
    <button type="submit" name="gokul_import_domestic_orders" class="gokul-btn">Manual Import Domestic Orders</button>
</form>
<?php
if (isset($_POST['gokul_import_domestic_orders']) && check_admin_referer('gokul_import_domestic_orders_action')) {
    $msg = 'Domestic orders imported successfully! (Simulated)';
    echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
}
?>

<h2>Domestic Orders</h2>
<?php
global $wpdb;
$table = $wpdb->prefix . 'gokul_domestic_orders';
$orders = $wpdb->get_results("SELECT * FROM $table ORDER BY order_date DESC LIMIT 50");
if ($orders) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Order ID</th><th>Customer</th><th>Platform</th><th>Date</th><th>Status</th><th>Total</th></tr></thead><tbody>';
    foreach ($orders as $order) {
        echo '<tr>';
        echo '<td>' . esc_html($order->order_id) . '</td>';
        echo '<td>' . esc_html($order->customer_name) . '</td>';
        echo '<td>' . esc_html($order->platform) . '</td>';
        echo '<td>' . esc_html($order->order_date) . '</td>';
        echo '<td>' . esc_html($order->status) . '</td>';
        echo '<td>' . esc_html($order->total) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>No domestic orders found.</p>';
}
?>