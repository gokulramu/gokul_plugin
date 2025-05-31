<?php
if (!class_exists('Gokul_Plugin_Walmart_API')) {
    class Gokul_Plugin_Walmart_API {
        private $client_id;
        private $client_secret;
        private $access_token;
        private $token_expires;

        public function __construct($client_id, $client_secret) {
            $this->client_id = $client_id;
            $this->client_secret = $client_secret;
            $this->access_token = null;
            $this->token_expires = 0;
        }

        /**
         * Get OAuth2.0 access token from Walmart
         */
        private function get_access_token() {
            // If token is still valid, reuse it
            if ($this->access_token && $this->token_expires > time() + 60) {
                return $this->access_token;
            }

            $endpoint = 'https://marketplace.walmartapis.com/v3/token';
            $body = [
                'grant_type' => 'client_credentials'
            ];
            $headers = [
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ];

            $response = wp_remote_post($endpoint, [
                'headers' => $headers,
                'body' => http_build_query($body),
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                return false;
            }

            $code = wp_remote_retrieve_response_code($response);
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && !empty($data['access_token'])) {
                $this->access_token = $data['access_token'];
                $this->token_expires = time() + intval($data['expires_in']);
                return $this->access_token;
            }

            return false;
        }

        /**
         * Test Walmart API account connectivity using OAuth2.0
         * @return array
         */
        public function test_account() {
            $token = $this->get_access_token();
            if (!$token) {
                return ['success' => false, 'message' => 'Could not obtain OAuth2.0 access token. Check your Client ID/Secret.'];
            }

            $endpoint = 'https://marketplace.walmartapis.com/v3/feeds';
            $headers = [
                'Authorization' => 'Bearer ' . $token,
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

        // ... (other methods can use $this->get_access_token() for Authorization header) ...
    }
}