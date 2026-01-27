<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_OAuth {
    public function __construct() {
        add_action( 'admin_post_hwd_ss_connect', [ $this, 'start_connect' ] );
        add_action( 'admin_post_hwd_ss_oauth_callback', [ $this, 'handle_callback' ] );
        add_action( 'admin_post_hwd_ss_disconnect', [ $this, 'disconnect' ] );
    }

    public function start_connect() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $network = isset( $_GET['network'] ) ? sanitize_key( $_GET['network'] ) : '';
        if ( ! $this->is_supported_network( $network ) ) {
            wp_die( 'Unsupported network' );
        }

        check_admin_referer( 'hwd_ss_connect_' . $network );

        $settings = hwd_ss_get_settings();
        if ( ! hwd_ss_has_app_credentials( $network, $settings ) ) {
            $this->redirect_with_notice( 'missing_app_credentials' );
        }

        $state = wp_generate_password( 20, false, false );
        $state_payload = [
            'network' => $network,
            'state' => $state,
            'time' => time(),
        ];
        update_user_meta( get_current_user_id(), 'hwd_ss_oauth_state', $state_payload );

        $redirect_uri = $this->get_redirect_uri( $network );
        $auth_url = $this->build_auth_url( $network, $settings, $redirect_uri, $state );

        if ( ! $auth_url ) {
            $this->redirect_with_notice( 'connect_failed' );
        }

        wp_redirect( $auth_url );
        exit;
    }

    public function handle_callback() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $network = isset( $_GET['network'] ) ? sanitize_key( $_GET['network'] ) : '';
        if ( ! $this->is_supported_network( $network ) ) {
            wp_die( 'Unsupported network' );
        }

        $state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
        $code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

        if ( isset( $_GET['error'] ) ) {
            $this->redirect_with_notice( 'oauth_error' );
        }

        if ( empty( $state ) || empty( $code ) || ! $this->validate_state( $network, $state ) ) {
            $this->redirect_with_notice( 'invalid_state' );
        }

        delete_user_meta( get_current_user_id(), 'hwd_ss_oauth_state' );

        $settings = hwd_ss_get_settings();
        $redirect_uri = $this->get_redirect_uri( $network );

        $result = null;
        if ( $network === 'facebook' ) {
            $result = $this->handle_facebook_callback( $settings, $redirect_uri, $code );
        } elseif ( $network === 'linkedin' ) {
            $result = $this->handle_linkedin_callback( $settings, $redirect_uri, $code );
        }

        if ( isset( $result['error'] ) ) {
            $this->redirect_with_notice( 'connect_failed' );
        }

        $this->redirect_with_notice( 'connected' );
    }

    public function disconnect() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $network = isset( $_GET['network'] ) ? sanitize_key( $_GET['network'] ) : '';
        if ( ! $this->is_supported_network( $network ) ) {
            wp_die( 'Unsupported network' );
        }

        check_admin_referer( 'hwd_ss_disconnect_' . $network );

        hwd_ss_clear_accounts( $network );

        $settings = hwd_ss_get_settings();
        if ( isset( $settings['credentials'][ $network ] ) ) {
            foreach ( $settings['credentials'][ $network ] as $key => $value ) {
                if ( $key === 'app_id' || $key === 'app_secret' || $key === 'client_id' || $key === 'client_secret' ) {
                    continue;
                }
                $settings['credentials'][ $network ][ $key ] = '';
            }
        }

        update_option( 'hwd_ss_settings', $settings, false );

        $this->redirect_with_notice( 'disconnected' );
    }

    private function handle_facebook_callback( $settings, $redirect_uri, $code ) {
        $app_id = $settings['credentials']['facebook']['app_id'];
        $app_secret = $settings['credentials']['facebook']['app_secret'];

        $token_url = add_query_arg(
            [
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'redirect_uri' => $redirect_uri,
                'code' => $code,
            ],
            'https://graph.facebook.com/v18.0/oauth/access_token'
        );

        $token_data = $this->request_json( $token_url, 'GET' );
        if ( empty( $token_data['access_token'] ) ) {
            return [ 'error' => 'token_failed' ];
        }

        $long_token_url = add_query_arg(
            [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'fb_exchange_token' => $token_data['access_token'],
            ],
            'https://graph.facebook.com/v18.0/oauth/access_token'
        );

        $long_token_data = $this->request_json( $long_token_url, 'GET' );
        $access_token = $long_token_data['access_token'] ?? $token_data['access_token'];

        $pages_url = add_query_arg(
            [
                'fields' => 'access_token,category,name,id',
                'access_token' => $access_token,
            ],
            'https://graph.facebook.com/v18.0/me/accounts'
        );

        $pages_data = $this->request_json( $pages_url, 'GET' );
        if ( empty( $pages_data['data'] ) || ! is_array( $pages_data['data'] ) ) {
            return [ 'error' => 'no_pages' ];
        }

        $accounts = [];
        foreach ( $pages_data['data'] as $page ) {
            if ( empty( $page['id'] ) ) {
                continue;
            }
            $accounts[] = [
                'account_id' => (string) $page['id'],
                'account_name' => $page['name'] ?? '',
                'category' => $page['category'] ?? '',
                'access_token' => $page['access_token'] ?? '',
                'type' => 'page',
                'active' => false,
                'connected_at' => gmdate( 'Y-m-d H:i:s' ),
            ];
        }

        if ( empty( $accounts ) ) {
            return [ 'error' => 'no_pages' ];
        }

        $accounts[0]['active'] = true;

        $settings['credentials']['facebook']['page_id'] = $accounts[0]['account_id'];
        $settings['credentials']['facebook']['access_token'] = $accounts[0]['access_token'];
        update_option( 'hwd_ss_settings', $settings, false );

        foreach ( $accounts as $account ) {
            hwd_ss_add_account( 'facebook', $account );
        }

        return [ 'success' => true ];
    }

    private function handle_linkedin_callback( $settings, $redirect_uri, $code ) {
        $client_id = $settings['credentials']['linkedin']['client_id'];
        $client_secret = $settings['credentials']['linkedin']['client_secret'];

        $token_response = wp_remote_post(
            'https://www.linkedin.com/oauth/v2/accessToken',
            [
                'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                ],
                'timeout' => 20,
            ]
        );

        $token_data = $this->response_json( $token_response );
        if ( empty( $token_data['access_token'] ) ) {
            return [ 'error' => 'token_failed' ];
        }

        $access_token = $token_data['access_token'];

        $user_response = wp_remote_get(
            'https://api.linkedin.com/v2/userinfo',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                ],
                'timeout' => 20,
            ]
        );

        $user_data = $this->response_json( $user_response );
        if ( empty( $user_data['sub'] ) ) {
            return [ 'error' => 'profile_failed' ];
        }

        $account = [
            'account_id' => (string) $user_data['sub'],
            'account_name' => $user_data['name'] ?? '',
            'urn' => 'urn:li:person:' . $user_data['sub'],
            'access_token' => $access_token,
            'type' => 'profile',
            'active' => true,
            'connected_at' => gmdate( 'Y-m-d H:i:s' ),
        ];

        $settings['credentials']['linkedin']['access_token'] = $access_token;
        $settings['credentials']['linkedin']['author_urn'] = $account['urn'];
        update_option( 'hwd_ss_settings', $settings, false );

        hwd_ss_add_account( 'linkedin', $account );

        return [ 'success' => true ];
    }

    private function get_redirect_uri( $network ) {
        return admin_url( 'admin-post.php?action=hwd_ss_oauth_callback&network=' . rawurlencode( $network ) );
    }

    private function build_auth_url( $network, $settings, $redirect_uri, $state ) {
        if ( $network === 'facebook' ) {
            $app_id = $settings['credentials']['facebook']['app_id'];
            $scopes = [
                'pages_show_list',
                'pages_read_engagement',
                'pages_manage_posts',
                'public_profile',
                'email',
            ];

            return add_query_arg(
                [
                    'client_id' => $app_id,
                    'redirect_uri' => $redirect_uri,
                    'state' => $state,
                    'scope' => implode( ',', $scopes ),
                    'response_type' => 'code',
                ],
                'https://www.facebook.com/v18.0/dialog/oauth'
            );
        }

        if ( $network === 'linkedin' ) {
            $client_id = $settings['credentials']['linkedin']['client_id'];
            $scopes = [
                'r_liteprofile',
                'r_emailaddress',
                'w_member_social',
            ];

            return add_query_arg(
                [
                    'response_type' => 'code',
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect_uri,
                    'state' => $state,
                    'scope' => implode( ' ', $scopes ),
                ],
                'https://www.linkedin.com/oauth/v2/authorization'
            );
        }

        return null;
    }

    private function is_supported_network( $network ) {
        return in_array( $network, [ 'facebook', 'linkedin' ], true );
    }

    private function validate_state( $network, $state ) {
        $stored = get_user_meta( get_current_user_id(), 'hwd_ss_oauth_state', true );
        if ( empty( $stored ) || ! is_array( $stored ) ) {
            return false;
        }

        if ( $stored['network'] !== $network || $stored['state'] !== $state ) {
            return false;
        }

        if ( ( time() - (int) $stored['time'] ) > 600 ) {
            return false;
        }

        return true;
    }

    private function request_json( $url, $method = 'GET', $args = [] ) {
        $response = 'GET' === $method
            ? wp_remote_get( $url, $args )
            : wp_remote_post( $url, $args );

        return $this->response_json( $response );
    }

    private function response_json( $response ) {
        if ( is_wp_error( $response ) ) {
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return [];
        }

        $decoded = json_decode( $body, true );
        return is_array( $decoded ) ? $decoded : [];
    }

    private function redirect_with_notice( $status ) {
        $url = admin_url( 'admin.php?page=hwd-social-share&tab=accounts&status=' . rawurlencode( $status ) );
        wp_redirect( $url );
        exit;
    }
}

new HWD_SS_OAuth();
