<?php
// Walmart Product Importer with API error logging
// Place this file as inc/gokul-walmart-importer.php
// Make sure to create inc/gokul-walmart-logger.php (see below) and use your real API fetch logic

require_once plugin_dir_path(__FILE__) . 'gokul-walmart-logger.php';

function gokul_run_walmart_import() {
    global $wpdb;
    $table = $wpdb->prefix . 'gokul_products';

    // --------- Fetch products from Walmart API ----------
    // TODO: Replace with your real fetch function!
    // Example: $api_products = your_walmart_api_fetch_function();
    $api_products = gokul_walmart_fetch_api_products();
    // ---------------------------------------------------

    // Log if API is empty
    if (empty($api_products) || !is_array($api_products)) {
        gokul_walmart_api_error_log('No products fetched from API or API response is not an array');
        return;
    }

    $imported = 0;
    foreach ($api_products as $api_product) {
        $sku          = $api_product['sku'] ?? '';
        $title        = $api_product['title'] ?? '';
        $gtin         = $api_product['gtin'] ?? '';
        $status       = $api_product['status'] ?? '';
        $lifecycle    = $api_product['lifecycle'] ?? '';
        $thumbnail    = $api_product['thumbnail'] ?? '';
        $product_link = $api_product['product_link'] ?? '';
        $stock        = isset($api_product['stock']) && is_numeric($api_product['stock']) ? intval($api_product['stock']) : 0;

        // Log missing SKU (critical for DB insert)
        if (!$sku) {
            gokul_walmart_api_error_log('Missing SKU in API data', $api_product);
            continue;
        }

        // Log if important fields are empty (for debugging)
        if (empty($thumbnail) || empty($product_link)) {
            gokul_walmart_api_error_log('Missing thumbnail/product_link', [
                'sku' => $sku,
                'thumbnail' => $thumbnail,
                'product_link' => $product_link,
                'api_data' => $api_product
            ]);
        }
        if (!isset($api_product['stock'])) {
            gokul_walmart_api_error_log('Missing stock value', [
                'sku' => $sku,
                'api_data' => $api_product
            ]);
        }

        // Insert or update in the DB
        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO $table
                    (sku, title, gtin, status, lifecycle, thumbnail, product_link, stock)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %d)
                ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    gtin = VALUES(gtin),
                    status = VALUES(status),
                    lifecycle = VALUES(lifecycle),
                    thumbnail = VALUES(thumbnail),
                    product_link = VALUES(product_link),
                    stock = VALUES(stock)",
                $sku, $title, $gtin, $status, $lifecycle, $thumbnail, $product_link, $stock
            )
        );

        // Log DB errors
        if ($result === false) {
            gokul_walmart_api_error_log('DB Insert/Update failed', [
                'sku' => $sku,
                'wpdb_error' => $wpdb->last_error,
                'api_product' => $api_product
            ]);
        } else {
            $imported++;
        }
    }

    gokul_walmart_api_error_log("Import finished: $imported products processed.");
}

/**
 * Dummy Walmart API fetcher.
 * Replace this entire function with your real Walmart API integration.
 * Must return an array of associative arrays with keys:
 * sku, title, gtin, status, lifecycle, thumbnail, product_link, stock
 */
function gokul_walmart_fetch_api_products() {
    // Example of 2 dummy products (replace this with API call)
    return [
        [
            'sku' => 'ABC123',
            'title' => 'Test Widget',
            'gtin' => '8855555555',
            'status' => 'active',
            'lifecycle' => 'new',
            'thumbnail' => 'https://via.placeholder.com/80x80.png?text=Test+Widget',
            'product_link' => 'https://www.walmart.com/ip/123456',
            'stock' => 14,
        ],
        [
            'sku' => 'XYZ789',
            'title' => 'Sample Product',
            'gtin' => '9991112222',
            'status' => 'inactive',
            'lifecycle' => 'discontinued',
            'thumbnail' => '',
            'product_link' => '',
            'stock' => 0,
        ],
        // Add more or fetch from Walmart's real API
    ];
}
?>