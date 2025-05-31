<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'class-flipkart-api.php';

function gokul_shortcode_flipkart_orders($atts) {
    ob_start();

    // Restrict to logged-in employees if needed
    if (!is_user_logged_in()) {
        echo '<p>You must be logged in to view orders.</p>';
        return ob_get_clean();
    }

    // Optionally, add a capability check or role check for "employee"
    // $user = wp_get_current_user();
    // if (!in_array('employee', $user->roles)) { ... }

    $all_accounts = get_option('gokul_plugin_marketplace_accounts');
    $flipkart_accounts = isset($all_accounts['flipkart']) ? $all_accounts['flipkart'] : [];
    $selected = isset($_POST['flipkart_account_index']) ? intval($_POST['flipkart_account_index']) : 0;
    $account = isset($flipkart_accounts[$selected]) ? $flipkart_accounts[$selected] : null;

    $orders = null;
    $error = '';

    if ($account && isset($_POST['fetch_orders'])) {
        $flipkart_api = new Gokul_Plugin_Flipkart_API($account['client_id'], $account['client_secret']);
        try {
            $endpoint = '/orders/search';
            $filter = [
                'filter' => [],
                'pagination' => [
                    'pageSize' => 10,
                    'pageNumber' => 1
                ]
            ];
            $orders = $flipkart_api->api_request($endpoint, 'POST', $filter);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    ?>
    <form method="post" style="margin-bottom:24px;">
        <label for="flipkart_account_index"><strong>Select Flipkart Account:</strong></label>
        <select name="flipkart_account_index" id="flipkart_account_index">
            <?php foreach ($flipkart_accounts as $idx => $acc): ?>
                <option value="<?php echo esc_attr($idx); ?>" <?php selected($selected, $idx); ?>>
                    <?php echo esc_html($acc['account_name'] . ' (' . $acc['client_id'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="fetch_orders" class="button button-primary">Fetch Orders</button>
    </form>
    <?php if ($error): ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
    <?php endif; ?>
    <?php if ($orders): ?>
        <h2>Orders (First 10)</h2>
        <?php if (!empty($orders['orderItems'])): ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Item ID</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders['orderItems'] as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item['orderId'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['orderItemId'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['orderState'] ?? ''); ?></td>
                            <td><?php echo esc_html($item['createdAt'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('gokul_flipkart_orders', 'gokul_shortcode_flipkart_orders');