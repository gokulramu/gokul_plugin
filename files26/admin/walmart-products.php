<?php
if (!current_user_can('manage_options')) {
    wp_die('No permission');
}

require_once GOKUL_PATH . 'includes/class-walmart-api.php';

global $wpdb;
$table = $wpdb->prefix . 'gokul_products';

// Handle sync/import action
if (isset($_POST['gokul_walmart_import_products'])) {
    $accounts = get_option('gokul_plugin_marketplace_accounts', []);
    $acc = $accounts['walmart'][0] ?? [];
    if ($acc && class_exists('Gokul_Plugin_Walmart_API')) {
        $api = new Gokul_Plugin_Walmart_API(
            $acc['client_id'],
            $acc['client_secret'],
            $acc['type'] ?? 'marketplace',
            $acc['service_name'] ?? '',
            $acc['partner_id'] ?? null
        );
        // You may want to loop through pages for more products
        $response = $api->get_catalog_items(['limit' => 50, 'offset' => 0]);
        $items_key = !empty($response['items']) ? 'items' : (!empty($response['ItemResponse']) ? 'ItemResponse' : null);
        if ($items_key && !empty($response[$items_key])) {
            foreach ($response[$items_key] as $item) {
                $fields = [
                    'sku' => $item['sku'] ?? $item['itemId'] ?? '',
                    'title' => $item['title'] ?? $item['productName'] ?? $item['name'] ?? '',
                    'gtin' => $item['gtin'] ?? '',
                    'status' => $item['publishedStatus'] ?? $item['status'] ?? '',
                    'lifecycle' => $item['lifecycleStatus'] ?? '',
                    'created_at' => current_time('mysql'),
                    'product_name' => $item['productName'] ?? '',
                    'description' => $item['description'] ?? '',
                    'brand' => $item['brand'] ?? '',
                    'price' => isset($item['price']) ? floatval($item['price']) : null,
                    'main_image_url' => $item['mainImageUrl'] ?? $item['main_image_url'] ?? '',
                    'additional_images' => isset($item['additional_images']) ? (is_array($item['additional_images']) ? implode(',', $item['additional_images']) : $item['additional_images']) : '',
                    'category' => $item['category'] ?? '',
                    'condition_status' => $item['condition'] ?? $item['condition_status'] ?? '',
                    'account_name' => $acc['account_name'] ?? '',
                    'publish_status' => $item['publishStatus'] ?? '',
                    'thumbnail' => $item['thumbnail'] ?? $item['mainImageUrl'] ?? $item['main_image_url'] ?? '',
                    'product_link' => $item['productLink'] ?? $item['productUrl'] ?? $item['url'] ?? '',
                    'stock' =>
                        isset($item['availableQuantity']) ? intval($item['availableQuantity']) :
                        (isset($item['inventory']) ? intval($item['inventory']) : 0)
                ];
                $api->import_product($fields);
            }
            echo '<div class="notice notice-success"><p>Products imported/synced successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>No products found in Walmart API response.</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Walmart API credentials not configured or class missing.</p></div>';
    }
}

// Fetch products for display
$products = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 100");

?>
<div class="wrap">
    <h1>Walmart Products</h1>
    <form method="post">
        <?php submit_button('Sync/Import Products from Walmart', 'primary', 'gokul_walmart_import_products'); ?>
    </form>
    <hr>
    <h2>Products Table (Last 100)</h2>
    <table class="widefat striped" style="max-width:100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>SKU</th>
                <th>Title</th>
                <th>GTIN</th>
                <th>Status</th>
                <th>Lifecycle</th>
                <th>Brand</th>
                <th>Price</th>
                <th>Main Image</th>
                <th>Category</th>
                <th>Account</th>
                <th>Stock</th>
                <th>Published</th>
                <th>Product Link</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($products): foreach ($products as $product): ?>
            <tr>
                <td><?php echo intval($product->id); ?></td>
                <td><?php echo esc_html($product->sku); ?></td>
                <td><?php echo esc_html($product->title); ?></td>
                <td><?php echo esc_html($product->gtin); ?></td>
                <td><?php echo esc_html($product->status); ?></td>
                <td><?php echo esc_html($product->lifecycle); ?></td>
                <td><?php echo esc_html($product->brand); ?></td>
                <td><?php echo esc_html($product->price); ?></td>
                <td>
                    <?php if (!empty($product->main_image_url)): ?>
                        <img src="<?php echo esc_url($product->main_image_url); ?>" style="height:40px;width:auto;">
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($product->category); ?></td>
                <td><?php echo esc_html($product->account_name); ?></td>
                <td><?php echo intval($product->stock); ?></td>
                <td><?php echo esc_html($product->publish_status); ?></td>
                <td>
                    <?php if (!empty($product->product_link)): ?>
                        <a href="<?php echo esc_url($product->product_link); ?>" target="_blank">View</a>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($product->created_at); ?></td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="15">No products found in database.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>