<?php
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
            $jarPath = '/path/to/DigitalSignatureUtil-1.0.0.jar'; // <-- UPDATE THIS
            $consumerId = $this->client_id;
            $privateKey = $this->client_secret; // path to PKCS#8 private key file

            $cmd = escapeshellcmd("java -jar \"$jarPath\" DigitalSignatureUtil \"$requestUrl\" \"$consumerId\" \"$privateKey\" \"$requestMethod\"");
            exec($cmd, $output, $return_var);

            if ($return_var !== 0) {
                return ['error' => 'Failed to run DigitalSignatureUtil JAR.'];
            }

            $signature = '';
            $timestamp = '';
            foreach ($output as $line) {
                if (stripos($line, 'WM_SEC.AUTH_SIGNATURE:') !== false) {
                    $signature = trim(str_replace('WM_SEC.AUTH_SIGNATURE:', '', $line));
                }
                if (stripos($line, 'WM_SEC.TIMESTAMP:') !== false) {
                    $timestamp = trim(str_replace('WM_SEC.TIMESTAMP:', '', $line));
                }
            }

            if ($signature && $timestamp) {
                return [
                    'signature' => $signature,
                    'timestamp' => $timestamp
                ];
            } else {
                return ['error' => 'Could not parse signature or timestamp from JAR output.'];
            }
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