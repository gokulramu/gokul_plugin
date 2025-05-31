<?php
if (!defined('ABSPATH')) exit;

// Load marketplace API classes as needed
require_once plugin_dir_path(__FILE__) . '../includes/class-flipkart-api.php';
// Include other marketplace classes similarly (e.g., class-amazon-api.php)

function gokul_domestic_orders_admin() {
    // Load account data
    $all_accounts = get_option('gokul_plugin_marketplace_accounts');
    if (!$all_accounts) {
        echo '<div class="notice notice-error"><p>No marketplace accounts configured.</p></div>';
        return;
    }

    // Build list of all domestic accounts
    $domestic_accounts = [];
    foreach ($all_accounts as $marketplace => $accounts) {
        foreach ($accounts as $idx => $acc) {
            if ($acc['type'] === 'domestic') {
                $acc['marketplace'] = $marketplace;
                $acc['account_index'] = $idx;
                $domestic_accounts[] = $acc;
            }
        }
    }

    // Prepare filter options
    $selected_marketplace = isset($_POST['filter_marketplace']) ? sanitize_text_field($_POST['filter_marketplace']) : '';
    $selected_account = isset($_POST['filter_account']) ? intval($_POST['filter_account']) : '';
    $filtered_accounts = array_filter($domestic_accounts, function($acc) use ($selected_marketplace, $selected_account) {
        if ($selected_marketplace && $acc['marketplace'] !== $selected_marketplace) return false;
        if ($selected_account !== '' && $selected_account !== null && isset($_POST['filter_account']) && $acc['account_index'] !== $selected_account) return false;
        return true;
    });

    // Fetch orders for all filtered domestic accounts
    $all_orders = [];
    foreach ($filtered_accounts as $acc) {
        try {
            switch ($acc['marketplace']) {
                case 'flipkart':
                    $api = new Gokul_Plugin_Flipkart_API($acc['client_id'], $acc['client_secret']);
                    $endpoint = '/orders/search';
                    $filter = [
                        'filter' => [],
                        'pagination' => [
                            'pageSize' => 10,
                            'pageNumber' => 1
                        ]
                    ];
                    $orders = $api->api_request($endpoint, 'POST', $filter);
                    if (!empty($orders['orderItems'])) {
                        foreach ($orders['orderItems'] as $item) {
                            $item['marketplace'] = 'Flipkart';
                            $item['account_name'] = $acc['account_name'];
                            $all_orders[] = $item;
                        }
                    }
                    break;
                // Add similar cases for Amazon, etc.
                // case 'amazon':
                //     ...
                //     break;
            }
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Error fetching orders for ' . esc_html($acc['account_name']) . ': ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Domestic Orders</h1>
        <form method="post" style="margin-bottom:18px;">
            <label><strong>Filter by Marketplace:</strong>
                <select name="filter_marketplace" onchange="this.form.submit()">
                    <option value="">All Marketplaces</option>
                    <?php
                    $marketplaces = array_unique(array_column($domestic_accounts, 'marketplace'));
                    foreach ($marketplaces as $mp):
                    ?>
                        <option value="<?php echo esc_attr($mp); ?>" <?php selected($selected_marketplace, $mp); ?>><?php echo ucfirst($mp); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label style="margin-left:18px;"><strong>Filter by Account:</strong>
                <select name="filter_account" onchange="this.form.submit()">
                    <option value="">All Accounts</option>
                    <?php foreach ($domestic_accounts as $acc): ?>
                        <?php if (!$selected_marketplace || $acc['marketplace'] === $selected_marketplace): ?>
                            <option value="<?php echo esc_attr($acc['account_index']); ?>" <?php selected($selected_account, $acc['account_index']); ?>>
                                <?php echo esc_html($acc['account_name'] . ' (' . ucfirst($acc['marketplace']) . ')'); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <noscript><button type="submit" class="button">Apply</button></noscript>
        </form>

        <h2>Orders</h2>
        <?php if (!empty($all_orders)): ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Marketplace</th>
                        <th>Account</th>
                        <th>Order ID</th>
                        <th>Order Item ID</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($all_orders as $order): ?>
                    <tr>
                        <td><?php echo esc_html($order['marketplace'] ?? ''); ?></td>
                        <td><?php echo esc_html($order['account_name'] ?? ''); ?></td>
                        <td><?php echo esc_html($order['orderId'] ?? ''); ?></td>
                        <td><?php echo esc_html($order['orderItemId'] ?? ''); ?></td>
                        <td><?php echo esc_html($order['orderState'] ?? ''); ?></td>
                        <td><?php echo esc_html($order['createdAt'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found for the selected filter(s).</p>
        <?php endif; ?>
    </div>
    <?php
}