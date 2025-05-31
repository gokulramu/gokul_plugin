<?php
require_once GOKUL_PATH . 'includes/class-walmart-api.php';

if (!current_user_can('manage_options')) {
    wp_die('No permission');
}

global $wpdb;

// Minimal debug log (in-memory for this page only)
global $debug_log;
if (!isset($debug_log)) $debug_log = [];
if (!function_exists('gokul_debug_log')) {
    function gokul_debug_log($message, $data = null) {
        global $debug_log;
        $entry = '[' . date('H:i:s') . '] ' . $message;
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $entry .= ' | ' . print_r($data, true);
            } else {
                $entry .= ' | ' . $data;
            }
        }
        $debug_log[] = $entry;
    }
}

$table = $wpdb->prefix . 'gokul_products';
$product = null;

// ---- 1. API Connect Test ----
gokul_debug_log('Connecting to Walmart API...');
try {
    // Load API credentials
    $accounts = get_option('gokul_plugin_marketplace_accounts', []);
    $acc = $accounts['walmart'][0] ?? [];
    if (!$acc) throw new Exception('No Walmart account found in config.');
    gokul_debug_log('API credentials loaded', $acc);

    // Instantiate API class
    if (!class_exists('Gokul_Plugin_Walmart_API')) {
        throw new Exception('Gokul_Plugin_Walmart_API not loaded.');
    }
    $api = new Gokul_Plugin_Walmart_API(
        $acc['client_id'],
        $acc['client_secret'],
        $acc['type'] ?? 'marketplace',
        $acc['service_name'] ?? 'MyAppService',
        $acc['partner_id'] ?? null
    );
    gokul_debug_log('API class instantiated');

    // Get 1 product from API
    $response = $api->get_catalog_items(['limit' => 1, 'offset' => 0]);
    gokul_debug_log('API connected, got response', $response);

    // Find the correct key for products
    $items_key = !empty($response['items']) ? 'items' : (!empty($response['ItemResponse']) ? 'ItemResponse' : null);
    if (!$items_key || empty($response[$items_key])) {
        gokul_debug_log('No products found in API response', $response);
        throw new Exception('No products found in Walmart API response.');
    }
    $item = $response[$items_key][0];
    gokul_debug_log('Raw API product', $item);

    // ---- 2. Field Extraction & Debug ----
    $fields = [];
    $fields['sku'] = $item['sku'] ?? $item['itemId'] ?? '';
    gokul_debug_log('SKU extracted', $fields['sku']);
    $fields['title'] = $item['title'] ?? $item['productName'] ?? $item['name'] ?? '';
    gokul_debug_log('Title extracted', $fields['title']);
    $fields['gtin'] = $item['gtin'] ?? '';
    gokul_debug_log('GTIN extracted', $fields['gtin']);
    $fields['status'] = $item['publishedStatus'] ?? $item['status'] ?? '';
    gokul_debug_log('Status extracted', $fields['status']);
    $fields['lifecycle'] = $item['lifecycleStatus'] ?? '';
    gokul_debug_log('Lifecycle extracted', $fields['lifecycle']);
    $fields['thumbnail'] = $item['thumbnail'] ?? $item['mainImageUrl'] ?? $item['main_image_url'] ?? $item['imageUrl'] ?? '';
    gokul_debug_log('Thumbnail extracted', $fields['thumbnail']);
    $fields['product_link'] = $item['productLink'] ?? $item['productUrl'] ?? $item['url'] ?? '';
    gokul_debug_log('Product link extracted', $fields['product_link']);
    $fields['stock'] =
        isset($item['availableQuantity']) ? intval($item['availableQuantity']) :
        (isset($item['inventory']) ? intval($item['inventory']) : 0);
    gokul_debug_log('Stock extracted', $fields['stock']);

    // ---- 3. Insert or Update DB ----
    if (!empty($fields['sku'])) {
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE sku = %s", $fields['sku']));
        if ($existing) {
            $wpdb->update($table, $fields, ['sku' => $fields['sku']]);
            gokul_debug_log('Product updated in DB', $fields);
        } else {
            $wpdb->insert($table, $fields);
            gokul_debug_log('Product inserted in DB', $fields);
        }
    } else {
        gokul_debug_log('Import aborted: SKU is missing!', $item);
    }

    // ---- 4. Fetch and show result ----
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE sku = %s", $fields['sku']));
    if ($product) {
        gokul_debug_log('Fetched product from DB after import:', (array)$product);
    } else {
        gokul_debug_log('Product not found after import!', $fields['sku']);
    }

} catch (Exception $e) {
    gokul_debug_log('Exception caught during import', $e->getMessage());
    $product = null;
}
?>
<div class="wrap">
    <h1>Walmart API One Product Import (Debug Mode)</h1>
    <table class="widefat striped" style="max-width:800px;">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Title</th>
                <th>GTIN</th>
                <th>Status</th>
                <th>Lifecycle</th>
                <th>Thumbnail</th>
                <th>Product Link</th>
                <th>Stock</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($product)): ?>
            <tr>
                <td><?php echo esc_html($product->sku); ?></td>
                <td><?php echo esc_html($product->title); ?></td>
                <td><?php echo esc_html($product->gtin); ?></td>
                <td><?php echo esc_html($product->status); ?></td>
                <td><?php echo esc_html($product->lifecycle); ?></td>
                <td>
                <?php if (!empty($product->thumbnail)): ?>
                    <img src="<?php echo esc_url($product->thumbnail); ?>" style="height:40px;width:auto;">
                <?php endif; ?>
                </td>
                <td>
                <?php if (!empty($product->product_link)): ?>
                    <a href="<?php echo esc_url($product->product_link); ?>" target="_blank">View</a>
                <?php endif; ?>
                </td>
                <td><?php echo intval($product->stock); ?></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="8">Product not found in database.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <div style="margin-top:40px;">
        <h3>Step-by-step Debug Log:</h3>
        <pre style="background:#f9f9f9;border:1px solid #ccc;padding:10px;max-height:500px;overflow:auto;"><?php
            foreach ($debug_log as $line) echo esc_html($line) . "\n";
        ?></pre>
    </div>
</div>