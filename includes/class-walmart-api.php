<?php
// This file contains the Walmart API class for product import as custom posts.

if (!class_exists('Gokul_Plugin_Walmart_API')) {
    class Gokul_Plugin_Walmart_API {
        private $client_id;
        private $client_secret;
        private $type;
        private $service_name;
        private $partner_id;

        public function __construct($client_id, $client_secret, $type = 'marketplace', $service_name = '', $partner_id = null) {
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->type = $type;
            $this->service_name = $service_name;
            $this->partner_id = $partner_id;
        }

        /**
         * Import or update a single product as a custom post.
         * @param array $data
         * @return bool
         */
        public function import_product($data) {
            if (empty($data['sku'])) return false;
            $this->upsert_product_as_post($data);
            return true;
        }

        /**
         * Import an array of products as custom posts.
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

        private function upsert_product_as_post($product) {
            // $product should be an array/object from the API with at least SKU and title
            $sku = $product['sku'];
            $title = $product['title'];
            $desc = $product['description'] ?? '';

            // Check if a product post with this SKU exists
            $existing = new WP_Query([
                'post_type' => 'gokul_product',
                'meta_query' => [
                    [
                        'key' => '_gokul_product_sku',
                        'value' => $sku,
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);

            if ($existing->have_posts()) {
                $post_id = $existing->posts[0];
                // Update post
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $title,
                    'post_content' => $desc,
                ]);
            } else {
                // Insert new post
                $post_id = wp_insert_post([
                    'post_type' => 'gokul_product',
                    'post_title' => $title,
                    'post_content' => $desc,
                    'post_status' => 'publish'
                ]);
            }

            // Update SKU meta
            update_post_meta($post_id, '_gokul_product_sku', $sku);

            // Add more meta fields as needed (price, image, etc)
        }

        /**
         * Schedule background product import (placeholder).
         * You can implement WP Cron or Action Scheduler here.
         */
        public function schedule_background_product_import($products) {
            // For now, just import directly (synchronously)
            $this->import_products($products);
            // In production, use WP Cron or Action Scheduler for real background jobs.
        }
    }
}

// Make sure you have something like this BEFORE calling any method:
$walmart_api = new Gokul_Plugin_Walmart_API($client_id, $client_secret, $type, $service_name, $partner_id);

// Now you can safely call:
$walmart_api->schedule_background_product_import($products);