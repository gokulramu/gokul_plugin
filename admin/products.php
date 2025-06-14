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

    // --- Progress Bar (Visual Only) ---
    echo '<div id="import-progress" style="display:none;margin-bottom:10px;">
        <div style="background:#eee;width:100%;height:20px;position:relative;">
            <div id="import-bar" style="background:#0073aa;width:0;height:100%;transition:width 0.3s;"></div>
            <span id="import-percent" style="position:absolute;left:50%;top:0;transform:translateX(-50%);color:#fff;">0%</span>
        </div>
    </div>';

    // Fetch and display products from custom post type
    $args = [
        'post_type'      => 'gokul_product',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
    ];
    $products_query = new WP_Query($args);
    $total_products = $products_query->found_posts;

    echo '<p><strong>Total Listings: ' . esc_html($total_products) . '</strong></p>';

    echo '<table class="widefat striped" style="margin-top:20px;"><thead><tr>';
    echo '<th>SKU</th><th>Title</th><th>Date</th>';
    echo '</tr></thead><tbody>';
    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $sku = get_post_meta(get_the_ID(), '_gokul_product_sku', true);
            echo '<tr>';
            echo '<td>' . esc_html($sku) . '</td>';
            echo '<td>' . esc_html(get_the_title()) . '</td>';
            echo '<td>' . esc_html(get_the_date()) . '</td>';
            echo '</tr>';
        }
        wp_reset_postdata();
    } else {
        echo '<tr><td colspan="3"><em>No products found.</em></td></tr>';
    }
    echo '</tbody></table>';

    echo '</div>';
    ?>
    <script>
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            var progress = document.getElementById('import-progress');
            var bar = document.getElementById('import-bar');
            var percent = document.getElementById('import-percent');
            if(progress && bar && percent) {
                progress.style.display = 'block';
                bar.style.width = '0';
                percent.textContent = '0%';
                let i = 0;
                let interval = setInterval(function() {
                    i += 10;
                    if(i > 100) i = 100;
                    bar.style.width = i + '%';
                    percent.textContent = i + '%';
                    if(i === 100) clearInterval(interval);
                }, 200);
            }
        });
    });
    </script>
    <?php
}