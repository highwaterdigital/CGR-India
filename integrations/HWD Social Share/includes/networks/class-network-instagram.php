<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Network_Instagram extends HWD_SS_Network_Base {
    public function post( array $data ) {
        $creds = $this->settings['credentials']['instagram'];
        if ( empty( $creds['access_token'] ) || empty( $creds['user_id'] ) ) {
            return [
                'success' => false,
                'error' => 'Instagram credentials are missing',
            ];
        }

        $image_url = $data['payload']['image_url'] ?? '';
        if ( empty( $image_url ) ) {
            return [
                'success' => false,
                'error' => 'Instagram requires a featured image',
            ];
        }

        $container_url = 'https://graph.facebook.com/v18.0/' . rawurlencode( $creds['user_id'] ) . '/media';
        $container_response = $this->request( 'POST', $container_url, [
            'body' => [
                'image_url' => $image_url,
                'caption' => (string) $data['text'],
                'access_token' => $creds['access_token'],
            ],
        ] );

        if ( ! $container_response['success'] || empty( $container_response['data']['id'] ) ) {
            return [
                'success' => false,
                'error' => $container_response['body'] ?? 'Instagram container failed',
            ];
        }

        $publish_url = 'https://graph.facebook.com/v18.0/' . rawurlencode( $creds['user_id'] ) . '/media_publish';
        $publish_response = $this->request( 'POST', $publish_url, [
            'body' => [
                'creation_id' => $container_response['data']['id'],
                'access_token' => $creds['access_token'],
            ],
        ] );

        if ( ! $publish_response['success'] ) {
            return [
                'success' => false,
                'error' => $publish_response['body'] ?? 'Instagram publish failed',
            ];
        }

        return [
            'success' => true,
            'id' => $publish_response['data']['id'] ?? '',
        ];
    }
}
