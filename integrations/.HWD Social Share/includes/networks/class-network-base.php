<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class HWD_SS_Network_Base implements HWD_SS_Network_Interface {
    protected $settings;

    public function __construct( array $settings ) {
        $this->settings = $settings;
    }

    protected function request( $method, $url, array $args = [] ) {
        $defaults = [
            'method'  => $method,
            'timeout' => 20,
        ];

        $response = wp_remote_request( $url, array_replace_recursive( $defaults, $args ) );

        if ( is_wp_error( $response ) ) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return [
            'success' => $code >= 200 && $code < 300,
            'code' => $code,
            'body' => $body,
            'data' => $data,
        ];
    }
}
