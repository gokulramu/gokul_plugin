<?php
if (!defined('ABSPATH')) exit;

class Gokul_Plugin_Flipkart_API {
    private $client_id;
    private $client_secret;
    private $token_option_name = 'gokul_plugin_flipkart_access_token';
    private $token_expiry_option_name = 'gokul_plugin_flipkart_access_token_expiry';

    public function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    public function get_access_token() {
        $token = get_option($this->token_option_name);
        $expiry = get_option($this->token_expiry_option_name);

        if ($token && $expiry && time() < intval($expiry) - 60) {
            return $token;
        }
        return $this->refresh_access_token();
    }

    public function refresh_access_token() {
        $url = 'https://api.flipkart.net/oauth-service/oauth/token?grant_type=client_credentials&scope=Seller_Api';
        $credentials = base64_encode($this->client_id . ':' . $this->client_secret);
        $args = [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'Accept'        => 'application/json',
            ],
            'timeout'   => 15,
            'sslverify' => true,
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            throw new Exception('Token request failed: ' . $response->get_error_message());
        }
        $body_raw = wp_remote_retrieve_body($response);
        $data = json_decode($body_raw, true);

        if (function_exists('gokul_add_log')) {
            gokul_add_log('Flipkart OAuth RAW response: ' . $body_raw, 'debug');
            gokul_add_log('Flipkart OAuth JSON-decoded: ' . print_r($data, true), 'debug');
        }

        if (empty($data['access_token']) || empty($data['expires_in'])) {
            throw new Exception('Failed to get access token: ' . (is_array($data) ? print_r($data, true) : $body_raw));
        }
        update_option($this->token_option_name, $data['access_token'], true);
        update_option($this->token_expiry_option_name, time() + intval($data['expires_in']), true);

        return $data['access_token'];
    }

    public function api_request($endpoint, $method='GET', $body=null, $query_args=[]) {
        $base = 'https://api.flipkart.net/sellers/v3';
        $url = $base . $endpoint;
        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }
        $token = $this->get_access_token();

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'sslverify' => true,
            'timeout' => 30,
        ];
        if ($body) {
            $args['body'] = is_string($body) ? $body : wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        // If unauthorized, refresh token and retry once
        if (is_wp_error($response)) {
            throw new Exception('API request error: ' . $response->get_error_message());
        }
        if (wp_remote_retrieve_response_code($response) == 401) {
            $token = $this->refresh_access_token();
            $args['headers']['Authorization'] = 'Bearer ' . $token;
            $response = wp_remote_request($url, $args);
        }
        if (is_wp_error($response)) {
            throw new Exception('API request error: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function filter_shipments($filter_array = []) {
        $endpoint = '/shipments/filter';
        $payload = empty($filter_array) ? ["filter" => (object)[]] : ["filter" => $filter_array];
        return $this->api_request($endpoint, 'POST', $payload);
    }

    /**
     * Static: Import orders from Flipkart and insert to gokul_domestic_orders table.
     * Usage: Gokul_Plugin_Flipkart_API::import_orders($client_id, $client_secret);
     */
    public static function import_orders($client_id, $client_secret) {
        global $wpdb;
        $table = $wpdb->prefix . 'gokul_domestic_orders';
        $api = new self($client_id, $client_secret);

        // Fetch orders from Flipkart
        $endpoint = '/orders/search';
        $filter = [
            'filter' => [],
            'pagination' => [
                'pageSize' => 10,
                'pageNumber' => 1
            ]
        ];
        $orders = $api->api_request($endpoint, 'POST', $filter);

        $imported = 0;
        if (!empty($orders['orderItems'])) {
            foreach ($orders['orderItems'] as $item) {
                // Map Flipkart API fields to your DB structure
                $order_id = $item['orderId'] ?? '';
                $customer_name = $item['customerName'] ?? 'Flipkart Customer';
                $platform = 'Flipkart';
                $order_date = isset($item['createdAt']) ? date('Y-m-d H:i:s', strtotime($item['createdAt'])) : current_time('mysql');
                $status = $item['orderState'] ?? '';
                $total = isset($item['sellingPrice']) ? floatval($item['sellingPrice']) : 0.0;

                // Check if order already exists
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE order_id=%s", $order_id));
                if (!$exists && $order_id) {
                    $wpdb->insert($table, [
                        'order_id' => $order_id,
                        'customer_name' => $customer_name,
                        'platform' => $platform,
                        'order_date' => $order_date,
                        'status' => $status,
                        'total' => $total
                    ]);
                    $imported++;
                }
            }
        } else {
            if (function_exists('gokul_add_log')) {
                gokul_add_log('No orderItems found in Flipkart API response: ' . print_r($orders, true), 'debug');
            }
        }
        return "Imported $imported Flipkart orders.";
    }

    /**
     * Static: Test API credentials and connection. Used in admin UI and for scheduled health checks.
     * Returns ['success' => bool, 'log' => html_string]
     */
    public static function test_account($account) {
        $log = '';
        $success = false;
        try {
            $log .= "Testing Flipkart account: <b>" . esc_html($account['account_name'] ?? '') . "</b><br>";
            $client_id = $account['client_id'] ?? '';
            $client_secret = $account['client_secret'] ?? '';
            $api = new self($client_id, $client_secret);

            $log .= "Requesting OAuth token ...<br>";
            $token = $api->refresh_access_token();
            $log .= "Received access token: <span style='color:green'>" . esc_html($token) . "</span><br>";

            // Optionally, perform a simple API request (e.g., fetch shipment filters)
            try {
                $log .= "Testing API request to /shipments/filter ...<br>";
                $response = $api->filter_shipments();
                $log .= "Response:<br><pre>" . esc_html(print_r($response, true)) . "</pre>";
                $success = true;
            } catch (Exception $e) {
                $log .= "API request failed: " . esc_html($e->getMessage()) . "<br>";
                $success = false;
            }
        } catch (Exception $e) {
            $log .= "ERROR: " . esc_html($e->getMessage()) . "<br>";
            $success = false;
        }
        return [
            'success' => $success,
            'log' => $log
        ];
    }

    public function test_account() {
        // Example: Make a lightweight API call to Flipkart to test credentials
        $endpoint = 'https://api.flipkart.net/sellers/v3/orders/search?status=APPROVED&limit=1';
        $headers = [
            'Authorization' => 'Bearer ' . $this->get_access_token(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
        $response = wp_remote_get($endpoint, [
            'headers' => $headers,
            'timeout' => 10,
        ]);
        if (is_wp_error($response)) {
            return ['success' => false, 'message' => $response->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            return ['success' => true, 'message' => 'Connection successful!'];
        } else {
            return ['success' => false, 'message' => 'API error: HTTP ' . $code];
        }
    }
}

// --- CRON SCHEDULING: Automatic health check every hour, if enabled ---

define('GOKUL_FLIPKART_API_AUTOTEST_OPTION', 'gokul_flipkart_api_autotest_enabled');

function gokul_flipkart_schedule_flipkart_api_cron() {
    if (get_option(GOKUL_FLIPKART_API_AUTOTEST_OPTION, 'yes') === 'yes' && !wp_next_scheduled('gokul_flipkart_api_hourly_test')) {
        wp_schedule_event(time(), 'hourly', 'gokul_flipkart_api_hourly_test');
    }
}
add_action('init', 'gokul_flipkart_schedule_flipkart_api_cron');

function gokul_flipkart_unschedule_flipkart_api_cron() {
    if (get_option(GOKUL_FLIPKART_API_AUTOTEST_OPTION, 'yes') !== 'yes') {
        $timestamp = wp_next_scheduled('gokul_flipkart_api_hourly_test');
        if ($timestamp) wp_unschedule_event($timestamp, 'gokul_flipkart_api_hourly_test');
    }
}
add_action('update_option_' . GOKUL_FLIPKART_API_AUTOTEST_OPTION, 'gokul_flipkart_unschedule_flipkart_api_cron', 10, 0);

// The actual cron job: test all Flipkart accounts and store results
add_action('gokul_flipkart_api_hourly_test', function () {
    if (get_option(GOKUL_FLIPKART_API_AUTOTEST_OPTION, 'yes') !== 'yes') return;
    $accounts = get_option('gokul_plugin_marketplace_accounts', []);
    $flipkarts = $accounts['flipkart'] ?? [];
    $results = [];
    foreach ($flipkarts as $idx => $acc) {
        $results[$idx] = Gokul_Plugin_Flipkart_API::test_account($acc);
    }
    update_option('gokul_flipkart_api_test_results', $results, true);
});