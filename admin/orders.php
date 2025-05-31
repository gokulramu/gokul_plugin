<?php
if (!function_exists('gokul_orders_admin')) {
function gokul_orders_admin() {
    $accounts = [
        'amazon'   => 'Amazon',
        'walmart'  => 'Walmart',
        'flipkart' => 'Flipkart',
        'etsy'     => 'Etsy',
        'ebay'     => 'eBay'
    ];
    $selected = isset($_GET['account']) && isset($accounts[$_GET['account']]) ? $_GET['account'] : '';

    echo '<div class="wrap"><h1>Orders</h1>';

    // --- Import Orders Button ---
    echo '<form method="post" style="margin-bottom:20px;">';
    wp_nonce_field('gokul_import_orders_action');
    echo '<button type="submit" name="gokul_import_orders" class="button button-primary">Import Orders From All Channels</button>';
    echo '</form>';

    // --- Handle Manual Import ---
    if (isset($_POST['gokul_import_orders']) && check_admin_referer('gokul_import_orders_action')) {
        $accounts_option = get_option('gokul_plugin_marketplace_accounts');
        // Flipkart
        if (!empty($accounts_option['flipkart']) && class_exists('Gokul_Plugin_Flipkart_API') && method_exists('Gokul_Plugin_Flipkart_API', 'import_orders')) {
            foreach ($accounts_option['flipkart'] as $flipkart_acc) {
                try {
                    $result = Gokul_Plugin_Flipkart_API::import_orders($flipkart_acc['client_id'], $flipkart_acc['client_secret']);
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Imported orders from Flipkart account {$flipkart_acc['account_name']}: $result", 'import');
                    }
                    echo '<div class="updated"><p>Imported orders from Flipkart ('.$flipkart_acc['account_name'].'): '.esc_html($result).'</p></div>';
                } catch (Exception $e) {
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Error importing from Flipkart ({$flipkart_acc['account_name']}): ".$e->getMessage(), 'error');
                    }
                    echo '<div class="error"><p>Error importing from Flipkart ('.$flipkart_acc['account_name'].'): '.esc_html($e->getMessage()).'</p></div>';
                }
            }
        }
        // Amazon
        if (!empty($accounts_option['amazon']) && class_exists('Gokul_Amazon_API') && method_exists('Gokul_Amazon_API', 'import_orders')) {
            foreach ($accounts_option['amazon'] as $amazon_acc) {
                try {
                    $result = Gokul_Amazon_API::import_orders($amazon_acc['client_id'], $amazon_acc['client_secret']);
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Imported orders from Amazon account {$amazon_acc['account_name']}: $result", 'import');
                    }
                    echo '<div class="updated"><p>Imported orders from Amazon ('.$amazon_acc['account_name'].'): '.esc_html($result).'</p></div>';
                } catch (Exception $e) {
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Error importing from Amazon ({$amazon_acc['account_name']}): ".$e->getMessage(), 'error');
                    }
                    echo '<div class="error"><p>Error importing from Amazon ('.$amazon_acc['account_name'].'): '.esc_html($e->getMessage()).'</p></div>';
                }
            }
        }
        // eBay
        if (!empty($accounts_option['ebay']) && class_exists('Gokul_Ebay_API') && method_exists('Gokul_Ebay_API', 'import_orders')) {
            foreach ($accounts_option['ebay'] as $ebay_acc) {
                try {
                    $result = Gokul_Ebay_API::import_orders($ebay_acc['client_id'], $ebay_acc['client_secret']);
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Imported orders from eBay account {$ebay_acc['account_name']}: $result", 'import');
                    }
                    echo '<div class="updated"><p>Imported orders from eBay ('.$ebay_acc['account_name'].'): '.esc_html($result).'</p></div>';
                } catch (Exception $e) {
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Error importing from eBay ({$ebay_acc['account_name']}): ".$e->getMessage(), 'error');
                    }
                    echo '<div class="error"><p>Error importing from eBay ('.$ebay_acc['account_name'].'): '.esc_html($e->getMessage()).'</p></div>';
                }
            }
        }
        // Etsy
        if (!empty($accounts_option['etsy']) && class_exists('Gokul_Etsy_API') && method_exists('Gokul_Etsy_API', 'import_orders')) {
            foreach ($accounts_option['etsy'] as $etsy_acc) {
                try {
                    $result = Gokul_Etsy_API::import_orders($etsy_acc['client_id'], $etsy_acc['client_secret']);
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Imported orders from Etsy account {$etsy_acc['account_name']}: $result", 'import');
                    }
                    echo '<div class="updated"><p>Imported orders from Etsy ('.$etsy_acc['account_name'].'): '.esc_html($result).'</p></div>';
                } catch (Exception $e) {
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Error importing from Etsy ({$etsy_acc['account_name']}): ".$e->getMessage(), 'error');
                    }
                    echo '<div class="error"><p>Error importing from Etsy ('.$etsy_acc['account_name'].'): '.esc_html($e->getMessage()).'</p></div>';
                }
            }
        }
        // Walmart
        if (!empty($accounts_option['walmart']) && class_exists('Gokul_Walmart_API') && method_exists('Gokul_Walmart_API', 'import_orders')) {
            foreach ($accounts_option['walmart'] as $walmart_acc) {
                try {
                    $result = Gokul_Walmart_API::import_orders($walmart_acc['client_id'], $walmart_acc['client_secret']);
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Imported orders from Walmart account {$walmart_acc['account_name']}: $result", 'import');
                    }
                    echo '<div class="updated"><p>Imported orders from Walmart ('.$walmart_acc['account_name'].'): '.esc_html($result).'</p></div>';
                } catch (Exception $e) {
                    if (function_exists('gokul_add_log')) {
                        gokul_add_log("Error importing from Walmart ({$walmart_acc['account_name']}): ".$e->getMessage(), 'error');
                    }
                    echo '<div class="error"><p>Error importing from Walmart ('.$walmart_acc['account_name'].'): '.esc_html($e->getMessage()).'</p></div>';
                }
            }
        }
    }

    // --- Filter Form ---
    echo '<form method="get" style="margin-bottom:16px;">';
    echo '<input type="hidden" name="page" value="gokul_orders">';
    echo '<select name="account" onchange="this.form.submit()">';
    echo '<option value="">All Accounts</option>';
    foreach ($accounts as $k=>$v) echo '<option value="'.$k.'" '.($selected==$k?'selected':'').'>'.$v.'</option>';
    echo '</select></form>';

    // --- Display Orders Table ---
    $all_orders = [];
    if ($selected) {
        $class = 'Gokul_' . ucfirst($selected) . '_API';
        if (class_exists($class) && method_exists($class, 'get_orders')) {
            $all_orders = $class::get_orders();
        }
    } else {
        foreach ($accounts as $k=>$v) {
            $class = 'Gokul_' . ucfirst($k) . '_API';
            if (class_exists($class) && method_exists($class, 'get_orders')) {
                foreach ($class::get_orders() as $o) $all_orders[] = array_merge(['account'=>$v], $o);
            }
        }
    }

    echo '<table class="widefat striped"><tr><th>Account</th><th>Order ID</th><th>Items</th><th>Total</th></tr>';
    foreach ($all_orders as $row) {
        echo '<tr>';
        echo '<td>'.esc_html($row['account'] ?? ($selected ? $accounts[$selected] : '')).'</td>';
        echo '<td>'.esc_html($row['order_id'] ?? '').'</td>';
        echo '<td>'.esc_html($row['items'] ?? '').'</td>';
        echo '<td>'.esc_html($row['total'] ?? '').'</td>';
        echo '</tr>';
    }
    echo '</table></div>';
}
}