<?php
if (!defined('ABSPATH')) exit;

// Restrict access to employees/admins only
if (!is_user_logged_in() || (!current_user_can('gokul_employee') && !current_user_can('administrator'))) {
    echo '<div class="notice notice-error"><p>Access Denied.</p></div>';
    return;
}

global $wpdb;
$table = $wpdb->prefix . 'gokul_products';
$msg = '';

if (isset($_POST['gokul_import_products']) && check_admin_referer('gokul_import_products_action')) {
    // Import logic for Walmart (you can extend for other marketplaces)
    if (class_exists('Gokul_Walmart_API')) {
        $api = new Gokul_Walmart_API();
        if (method_exists($api, 'fetch_and_import_products')) {
            $msg = $api->fetch_and_import_products();
        } else {
            $msg = 'Import method not found in Walmart API class.';
        }
    } else {
        $msg = 'Walmart API integration is missing.';
    }
    echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
}
?>

<form method="post" style="margin-bottom:20px;">
    <?php wp_nonce_field('gokul_import_products_action'); ?>
    <button type="submit" name="gokul_import_products" class="gokul-btn">Manual Import Products</button>
</form>

<h2>All Products</h2>
<?php
// Fetch and display products from your database table
$products = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
if ($products) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>SKU</th><th>Title</th><th>GTIN</th><th>Status</th><th>Lifecycle</th><th>Created At</th></tr></thead><tbody>';
    foreach ($products as $product) {
        echo '<tr>';
        echo '<td>' . esc_html($product->sku) . '</td>';
        echo '<td>' . esc_html($product->title) . '</td>';
        echo '<td>' . esc_html($product->gtin) . '</td>';
        echo '<td>' . esc_html($product->status) . '</td>';
        echo '<td>' . esc_html($product->lifecycle) . '</td>';
        echo '<td>' . esc_html($product->created_at) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p>No products found.</p>';
}
?>