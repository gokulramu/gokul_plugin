<?php
if (!defined('ABSPATH')) exit;

add_action('admin_notices', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'gokul_orders' && isset($_GET['type']) && $_GET['type'] === 'international') {
        ?>
        <div style="margin:1em 0;">
            <form method="post">
                <button name="walmart_manual_fetch_orders" class="button button-primary">Manual Import Walmart Orders</button>
                <button name="walmart_manual_fetch_products" class="button button-secondary">Manual Import Walmart Products</button>
            </form>
        </div>
        <?php
    }
});

add_action('admin_init', function() {
    if (isset($_POST['walmart_manual_fetch_orders']) || isset($_POST['walmart_manual_fetch_products'])) {
        $api = new Gokul_Plugin_Walmart_API(
            'YOUR_CLIENT_ID',
            'YOUR_CLIENT_SECRET',
            'YOUR_CHANNEL_TYPE',
            'YOUR_SERVICE_NAME',
            'YOUR_PARTNER_ID' // optional
        );
        $msg = '';
        try {
            if (isset($_POST['walmart_manual_fetch_orders'])) {
                $msg = $api->import_orders_to_db();
            } elseif (isset($_POST['walmart_manual_fetch_products'])) {
                $msg = $api->import_products_to_db();
            }
        } catch (Exception $e) {
            $msg = "Walmart Import Error: " . $e->getMessage();
        }
        add_action('admin_notices', function() use ($msg) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        });
    }
});