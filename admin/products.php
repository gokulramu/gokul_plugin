<?php
if (!defined('ABSPATH')) exit;

function gokul_products_admin() {
    $accounts = [
        'amazon'   => 'Amazon',
        'walmart'  => 'Walmart',
        'flipkart' => 'Flipkart',
        'etsy'     => 'Etsy',
        'ebay'     => 'eBay'
    ];
    $selected = isset($_GET['account']) && isset($accounts[$_GET['account']]) ? $_GET['account'] : '';

    $msg = '';
    // Handle Import for any selected account
    if (isset($_POST['gokul_import_products']) && isset($_POST['gokul_import_account'])) {
        $import_account = sanitize_text_field($_POST['gokul_import_account']);
        $all_marketplace_accounts = get_option('gokul_plugin_marketplace_accounts');
        $import_accounts = $all_marketplace_accounts[$import_account] ?? [];
        if (!empty($import_accounts)) {
            $acc = $import_accounts[0]; // Use first account for this marketplace
            $class = "Gokul_Plugin_" . ucfirst($import_account) . "_API";
            if (class_exists($class)) {
                try {
                    $api = new $class(
                        $acc['client_id'],
                        $acc['client_secret'],
                        $acc['type'] ?? 'international',
                        'GokulPlugin',
                        $acc['partner_id'] ?? ''
                    );
                    $products = $api->get_catalog_items(['limit' => 100]);
                    $api->schedule_background_product_import($products);
                    $msg = 'Import completed!';
                } catch (Throwable $e) {
                    $msg = 'Import failed: ' . $e->getMessage();
                }
            } else {
                $msg = ucfirst($import_account) . " API integration is missing!";
            }
        } else {
            $msg = "No $import_account account configured!";
        }
    }

    echo '<div class="wrap"><h1>Products</h1>';

    // Account switcher
    echo '<form method="get" style="margin-bottom:16px;">';
    echo '<input type="hidden" name="page" value="gokul_products">';
    echo '<select name="account" onchange="this.form.submit()">';
    echo '<option value="">All Accounts</option>';
    foreach ($accounts as $k=>$v) echo '<option value="'.$k.'" '.($selected==$k?'selected':'').'>'.$v.'</option>';
    echo '</select></form>';

    // Show Import button(s) for all or selected account
    if ($selected) {
        echo '<form method="post" style="margin-bottom:20px;">';
        echo '<input type="hidden" name="gokul_import_account" value="' . esc_attr($selected) . '">';
        echo '<button type="submit" name="gokul_import_products" class="button button-primary">Import ' . esc_html($accounts[$selected]) . ' Products</button>';
        echo '</form>';
    } else {
        // Show all import buttons
        echo '<div style="display:flex;gap:10px;margin-bottom:20px;">';
        foreach ($accounts as $k=>$v) {
            echo '<form method="post" style="display:inline;">';
            echo '<input type="hidden" name="gokul_import_account" value="' . esc_attr($k) . '">';
            echo '<button type="submit" name="gokul_import_products" class="button">' . esc_html("Import $v Products") . '</button>';
            echo '</form>';
        }
        echo '</div>';
    }

    // Show import result message
    if ($msg) {
        echo '<div class="notice notice-success" style="margin-bottom:16px;"><p>' . esc_html($msg) . '</p></div>';
    }

    // Fetch and display products
    global $wpdb;
    $table = $wpdb->prefix . 'gokul_products';
    $all_products = [];

    if ($selected) {
        // Filter could be improved if you store account/marketplace on each row
        $all_products = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
    } else {
        $all_products = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
    }

    echo '<table class="widefat striped" style="margin-top:20px;"><thead><tr>';
    echo '<th>SKU</th><th>Title</th><th>GTIN</th><th>Status</th><th>Lifecycle</th><th>Created At</th>';
    echo '</tr></thead><tbody>';
    if ($all_products) {
        foreach ($all_products as $product) {
            echo '<tr>';
            echo '<td>' . esc_html($product->sku) . '</td>';
            echo '<td>' . esc_html($product->title) . '</td>';
            echo '<td>' . esc_html($product->gtin) . '</td>';
            echo '<td>' . esc_html($product->status) . '</td>';
            echo '<td>' . esc_html($product->lifecycle) . '</td>';
            echo '<td>' . esc_html($product->created_at) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6"><em>No products found.</em></td></tr>';
    }
    echo '</tbody></table>';

    echo '</div>';
}

// Only run for admin and when a button is clicked, for example
if (is_admin() && isset($_GET['import_walmart'])) {
    $client_id = 'YOUR_CLIENT_ID';
    $client_secret = 'YOUR_CLIENT_SECRET';
    $type = 'marketplace';
    $service_name = 'Walmart';
    $partner_id = null;

    $walmart_api = new Gokul_Plugin_Walmart_API($client_id, $client_secret, $type, $service_name, $partner_id);
    $products = $walmart_api->get_catalog_items(['limit' => 100]);
    $walmart_api->schedule_background_product_import($products);
    echo "Walmart products imported!";
}
?>