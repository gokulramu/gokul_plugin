<?php
if (!class_exists('Gokul_Plugin_Walmart_API')) {
    class Gokul_Plugin_Walmart_API {
        private $client_id;
        private $client_secret; // This should be the path to your PKCS#8 private key file
        private $type;
        private $service_name;
        private $partner_id;
        private $channel_type;
        private $tenant_id;
        private $locale_id;

        public function __construct($client_id, $client_secret, $type = 'marketplace', $service_name = 'Walmart Marketplace', $partner_id = null, $channel_type = '', $tenant_id = 'WALMART.US', $locale_id = 'en_US') {
            $this->client_id = $client_id;
            $this->client_secret = $client_secret; // path to private key file
            $this->type = $type;
            $this->service_name = $service_name;
            $this->partner_id = $partner_id;
            $this->channel_type = $channel_type; // WM_CONSUMER.CHANNEL.TYPE
            $this->tenant_id = $tenant_id;       // WM_TENANT_ID
            $this->locale_id = $locale_id;       // WM_LOCALE_ID
        }

        /**
         * Generate Walmart API digital signature and timestamp using the official JAR.
         */
        private function generate_signature($requestUrl, $requestMethod = 'GET') {
            $consumerId = $this->client_id;
            $privateKeyPem = file_get_contents($this->client_secret); // path to PKCS#8 private key file

            if (!$privateKeyPem) {
                return ['error' => 'Could not read private key file.'];
            }

            $timestamp = (string) round(microtime(true) * 1000);

            // Build the string to sign
            $stringToSign = $consumerId . "\n" . $requestUrl . "\n" . strtoupper($requestMethod) . "\n" . $timestamp . "\n";

            // Load the private key
            $privateKey = openssl_pkey_get_private($privateKeyPem);
            if (!$privateKey) {
                return ['error' => 'Invalid private key format or password required.'];
            }

            // Sign the string
            $signature = '';
            $success = openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            openssl_free_key($privateKey);

            if (!$success) {
                return ['error' => 'Failed to sign data with private key.'];
            }

            // Base64 encode the signature
            $signature_b64 = base64_encode($signature);

            return [
                'signature' => $signature_b64,
                'timestamp' => $timestamp
            ];
        }

        /**
         * Test Walmart API account connectivity.
         * @return array
         */
        public function test_account() {
            $endpoint = 'https://marketplace.walmartapis.com/v3/feeds'; // Use a simple GET endpoint
            $method = 'GET';

            // Generate signature and timestamp
            $sig = $this->generate_signature($endpoint, $method);
            if (!empty($sig['error'])) {
                return ['success' => false, 'message' => $sig['error']];
            }

            $headers = [
                'WM_SVC.NAME' => $this->service_name,
                'WM_QOS.CORRELATION_ID' => uniqid(),
                'WM_SEC.TIMESTAMP' => $sig['timestamp'],
                'WM_SEC.AUTH_SIGNATURE' => $sig['signature'],
                'WM_CONSUMER.CHANNEL.TYPE' => $this->channel_type, // Must be set!
                'WM_CONSUMER.ID' => $this->client_id,
                'WM_TENANT_ID' => $this->tenant_id,
                'WM_LOCALE_ID' => $this->locale_id,
                'Accept' => 'application/json',
            ];

            $response = wp_remote_get($endpoint, [
                'headers' => $headers,
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            if ($code === 200) {
                return ['success' => true, 'message' => 'Connection successful!', 'debug' => $body];
            } else {
                return [
                    'success' => false,
                    'message' => 'API error: HTTP ' . $code,
                    'debug' => $body
                ];
            }
        }

        // ... (other methods like import_products, get_catalog_items, etc. can remain unchanged) ...
    }
}