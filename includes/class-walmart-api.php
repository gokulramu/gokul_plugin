<?php
// This file contains the Walmart API class and ensures the database table exists/updated for all required fields.

if (!class_exists('Gokul_Plugin_Walmart_API')) {
    class Gokul_Plugin_Walmart_API {
        private $client_id;
        private $client_secret;
        private $type;
        private $service_name;
        private $partner_id;
        private $table;

        public function __construct($client_id, $client_secret, $type = 'marketplace', $service_name = '', $partner_id = null) {
            global $wpdb;
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->type = $type;
            $this->service_name = $service_name;
            $this->partner_id = $partner_id;
            $this->table = $wpdb->prefix . 'gokul_products';
            $this->ensure_products_table();
        }

        /**
         * Ensure DB table exists and is up-to-date with all columns. 
         * Will ALTER table for any new columns.
         */
        private function ensure_products_table() {
            global $wpdb;
            $fields = [
                'id INT AUTO_INCREMENT PRIMARY KEY',
                'sku VARCHAR(255) NOT NULL UNIQUE',
                'title TEXT',
                'gtin VARCHAR(50)',
                'status VARCHAR(50)',
                'lifecycle VARCHAR(50)',
                'created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'product_name TEXT',
                'description TEXT',
                'brand VARCHAR(255)',
                'price DECIMAL(12,2)',
                'main_image_url TEXT',
                'additional_images TEXT',
                'category VARCHAR(255)',
                'condition_status VARCHAR(50)',
                'account_name VARCHAR(255)',
                'publish_status VARCHAR(50)',
                'thumbnail TEXT',
                'product_link TEXT',
                'stock INT'
            ];
            $sql = "CREATE TABLE {$this->table} (" . implode(',', $fields) . ") {$wpdb->get_charset_collate()};";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        /**
         * Import or update a single product.
         * @param array $data
         * @return bool
         */
        public function import_product($data) {
            global $wpdb;
            $defaults = [
                'sku' => '',
                'title' => '',
                'gtin' => '',
                'status' => '',
                'lifecycle' => '',
                'created_at' => current_time('mysql'),
                'product_name' => '',
                'description' => '',
                'brand' => '',
                'price' => null,
                'main_image_url' => '',
                'additional_images' => '',
                'category' => '',
                'condition_status' => '',
                'account_name' => '',
                'publish_status' => '',
                'thumbnail' => '',
                'product_link' => '',
                'stock' => 0,
            ];
            $fields = array_merge($defaults, $data);

            if (empty($fields['sku'])) return false;

            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table} WHERE sku=%s", $fields['sku']));
            if ($exists) {
                $wpdb->update($this->table, $fields, ['sku' => $fields['sku']]);
            } else {
                $wpdb->insert($this->table, $fields);
            }
            return true;
        }

        /**
         * Import an array of products.
         * @param array $products
         */
        public function import_products($products) {
            foreach ($products as $product) {
                $this->import_product($product);
            }
        }

        /**
         * Fetch products from Walmart API using real credentials.
         * You must implement get_access_token() for real authentication.
         * @param array $args
         * @return array
         */
        public function get_catalog_items($args = []) {
            $endpoint = 'https://marketplace.walmartapis.com/v3/items?limit=' . intval($args['limit'] ?? 1) . '&offset=' . intval($args['offset'] ?? 0);

            $headers = [
                'WM_SEC.ACCESS_TOKEN' => $this->get_access_token(),
                'WM_QOS.CORRELATION_ID' => uniqid(),
                'WM_SVC.NAME' => $this->service_name,
                'Accept' => 'application/json',
            ];

            $response = wp_remote_get($endpoint, [
                'headers' => $headers,
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                return ['error' => $response->get_error_message()];
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (is_array($data)) {
                return $data;
            } else {
                return ['error' => 'Invalid Walmart API response'];
            }
        }

        /**
         * Placeholder for Walmart access token logic.
         * You MUST replace this with real authentication.
         * @return string
         */
        private function get_access_token() {
            // TODO: Implement real token retrieval per Walmart API docs
            return 'YOUR_ACCESS_TOKEN_HERE';
        }
    }
}