<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Network_Facebook extends HWD_SS_Network_Base {
    public function post( array $data ) {
        $creds = $this->settings['credentials']['facebook'];
        if ( empty( $creds['page_id'] ) || empty( $creds['access_token'] ) ) {
            return [
                'success' => false,
                'error' => 'Facebook credentials are missing',
            ];
        }

        $url = 'https://graph.facebook.com/v18.0/' . rawurlencode( $creds['page_id'] ) . '/feed';

        $body = [
            'message' => (string) $data['text'],
            'link' => $data['payload']['url'] ?? '',
            'access_token' => $creds['access_token'],
        ];

        $response = $this->request( 'POST', $url, [
            'body' => $body,
        ] );

        if ( ! $response['success'] ) {
            return [
                'success' => false,
                'error' => $response['body'] ?? 'Facebook request failed',
            ];
        }

        return [
            'success' => true,
            'id' => $response['data']['id'] ?? '',
        ];
    }
}
