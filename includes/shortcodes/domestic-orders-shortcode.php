<?php
/**
 * Shortcode handler for [gokul_domestic_orders]
 * Fetches orders from wp_gokul_domestic_orders and displays as a table.
 */
function fetch_gokul_domestic_orders() {
    global $wpdb;
    $table = $wpdb->prefix . 'gokul_domestic_orders';

    // Fetch the latest 50 orders, newest first
    $orders = $wpdb->get_results("SELECT * FROM $table ORDER BY order_date DESC LIMIT 50");

    if (empty($orders)) {
        return '<div>No domestic orders found.</div>';
    }

    $html = '<table class="gokul-orders-table" style="width:100%;border-collapse:collapse;margin-top:20px;">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Platform</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($orders as $order) {
        $html .= '<tr>
            <td>' . esc_html($order->order_id) . '</td>
            <td>' . esc_html($order->customer_name) . '</td>
            <td>' . esc_html($order->platform) . '</td>
            <td>' . esc_html(date('d M Y', strtotime($order->order_date))) . '</td>
            <td>' . esc_html($order->status) . '</td>
            <td>' . esc_html(number_format((float)$order->total, 2)) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

function gokul_domestic_orders_shortcode() {
    ob_start();
    echo fetch_gokul_domestic_orders();
    return ob_get_clean();
}
add_shortcode('gokul_domestic_orders', 'gokul_domestic_orders_shortcode');