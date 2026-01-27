<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Network_LinkedIn extends HWD_SS_Network_Base {
    public function post( array $data ) {
        $creds = $this->settings['credentials']['linkedin'];
        if ( empty( $creds['access_token'] ) || empty( $creds['author_urn'] ) ) {
            return [
                'success' => false,
                'error' => 'LinkedIn credentials are missing',
            ];
        }

        $url = 'https://api.linkedin.com/v2/ugcPosts';
        $share_url = $data['payload']['url'] ?? '';

        $share_content = [
            'shareCommentary' => [ 'text' => (string) $data['text'] ],
            'shareMediaCategory' => $share_url ? 'ARTICLE' : 'NONE',
        ];

        if ( $share_url ) {
            $share_content['media'] = [
                [
                    'status' => 'READY',
                    'originalUrl' => $share_url,
                    'title' => [ 'text' => (string) ( $data['payload']['title'] ?? '' ) ],
                ],
            ];
        }

        $payload = [
            'author' => $creds['author_urn'],
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => $share_content,
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = $this->request( 'POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $creds['access_token'],
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
            'body' => wp_json_encode( $payload ),
        ] );

        if ( ! $response['success'] ) {
            return [
                'success' => false,
                'error' => $response['body'] ?? 'LinkedIn request failed',
            ];
        }

        return [
            'success' => true,
            'id' => $response['data']['id'] ?? '',
        ];
    }
}
