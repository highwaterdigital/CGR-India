<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Network_X extends HWD_SS_Network_Base {
    public function post( array $data ) {
        $creds = $this->settings['credentials']['x'];
        if ( empty( $creds['api_key'] ) || empty( $creds['api_secret'] ) || empty( $creds['access_token'] ) || empty( $creds['access_secret'] ) ) {
            return [
                'success' => false,
                'error' => 'X credentials are missing',
            ];
        }

        $url = 'https://api.twitter.com/2/tweets';
        $payload = [ 'text' => (string) $data['text'] ];
        $body = wp_json_encode( $payload );

        $oauth_params = [
            'oauth_consumer_key' => $creds['api_key'],
            'oauth_nonce' => wp_generate_password( 16, false, false ),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $creds['access_token'],
            'oauth_version' => '1.0',
        ];

        $base_string = $this->build_base_string( 'POST', $url, $oauth_params );
        $signing_key = rawurlencode( $creds['api_secret'] ) . '&' . rawurlencode( $creds['access_secret'] );
        $oauth_params['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $base_string, $signing_key, true ) );

        $auth_header = 'OAuth ' . $this->build_authorization_header( $oauth_params );

        $response = $this->request( 'POST', $url, [
            'headers' => [
                'Authorization' => $auth_header,
                'Content-Type' => 'application/json',
            ],
            'body' => $body,
        ] );

        if ( ! $response['success'] ) {
            return [
                'success' => false,
                'error' => $response['body'] ?? 'X request failed',
            ];
        }

        return [
            'success' => true,
            'id' => $response['data']['data']['id'] ?? '',
        ];
    }

    private function build_base_string( $method, $url, array $params ) {
        ksort( $params );
        $pairs = [];
        foreach ( $params as $key => $value ) {
            $pairs[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
        }

        return strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( implode( '&', $pairs ) );
    }

    private function build_authorization_header( array $params ) {
        $header = [];
        foreach ( $params as $key => $value ) {
            if ( strpos( $key, 'oauth_' ) !== 0 ) {
                continue;
            }
            $header[] = rawurlencode( $key ) . '="' . rawurlencode( $value ) . '"';
        }

        return implode( ', ', $header );
    }
}
