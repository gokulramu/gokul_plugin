<?php
// International Orders Shortcode
function gokul_international_orders_shortcode() {
    // Only show to employees
    if (!current_user_can('gokul_employee') && !current_user_can('administrator')) {
        return '<p>Access Denied.</p>';
    }
    ob_start();
    include GOKUL_PATH . 'templates/international-orders.php';
    return ob_get_clean();
}
add_shortcode('gokul_international_orders', 'gokul_international_orders_shortcode');

// International Products Shortcode
function gokul_international_products_shortcode() {
    if (!current_user_can('gokul_employee') && !current_user_can('administrator')) {
        return '<p>Access Denied.</p>';
    }
    ob_start();
    include GOKUL_PATH . 'templates/international-products.php';
    return ob_get_clean();
}
add_shortcode('gokul_international_products', 'gokul_international_products_shortcode');