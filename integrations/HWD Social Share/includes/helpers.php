<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hwd_ss_default_settings() {
    return [
        'templates' => [
            'default'  => "{title}\n{excerpt}\n{url}",
            'x'        => "{title}\n{url}",
            'facebook' => "{title}\n{excerpt}\n{url}",
            'linkedin' => "{title}\n{excerpt}\n{url}",
            'instagram'=> "{title}\n{excerpt}",
            'youtube'  => "{title}\n{url}",
        ],
        'limits' => [
            'x'         => 280,
            'facebook'  => 63206,
            'linkedin'  => 3000,
            'instagram' => 2200,
            'youtube'   => 5000,
        ],
        'credentials' => [
            'x' => [
                'api_key'        => '',
                'api_secret'     => '',
                'access_token'   => '',
                'access_secret'  => '',
                'client_id'      => '',
                'client_secret'  => '',
            ],
            'facebook' => [
                'app_id'      => '',
                'app_secret'  => '',
                'page_id'      => '',
                'access_token' => '',
            ],
            'linkedin' => [
                'client_id'    => '',
                'client_secret'=> '',
                'access_token' => '',
                'author_urn'   => '',
            ],
            'instagram' => [
                'app_id'       => '',
                'app_secret'   => '',
                'access_token' => '',
                'user_id'      => '',
            ],
            'youtube' => [
                'api_key' => '',
            ],
        ],
    ];
}

function hwd_ss_get_settings() {
    $defaults = hwd_ss_default_settings();
    $stored = get_option( 'hwd_ss_settings', [] );

    return array_replace_recursive( $defaults, is_array( $stored ) ? $stored : [] );
}

function hwd_ss_render_template( $template, array $vars ) {
    foreach ( $vars as $key => $value ) {
        $template = str_replace( '{' . $key . '}', (string) $value, $template );
    }

    return trim( $template );
}

function hwd_ss_limit_text( $text, $limit ) {
    $text = trim( (string) $text );
    $limit = (int) $limit;

    if ( $limit <= 0 ) {
        return $text;
    }

    if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
        if ( mb_strlen( $text ) <= $limit ) {
            return $text;
        }

        return mb_substr( $text, 0, max( 0, $limit - 3 ) ) . '...';
    }

    if ( strlen( $text ) <= $limit ) {
        return $text;
    }

    return substr( $text, 0, max( 0, $limit - 3 ) ) . '...';
}

function hwd_ss_get_featured_image_url( $post_id ) {
    $image_id = get_post_thumbnail_id( $post_id );
    if ( ! $image_id ) {
        return '';
    }

    $image = wp_get_attachment_image_src( $image_id, 'full' );

    return is_array( $image ) ? $image[0] : '';
}

function hwd_ss_log( array $entry ) {
    $logs = get_option( 'hwd_ss_logs', [] );
    if ( ! is_array( $logs ) ) {
        $logs = [];
    }

    $entry['time'] = gmdate( 'Y-m-d H:i:s' );
    $logs[] = $entry;

    if ( count( $logs ) > 200 ) {
        $logs = array_slice( $logs, -200 );
    }

    update_option( 'hwd_ss_logs', $logs, false );
}

function hwd_ss_get_accounts() {
    $accounts = get_option( 'hwd_ss_accounts', [] );
    return is_array( $accounts ) ? $accounts : [];
}

function hwd_ss_save_accounts( array $accounts ) {
    update_option( 'hwd_ss_accounts', $accounts, false );
}

function hwd_ss_add_account( $network, array $account ) {
    $accounts = hwd_ss_get_accounts();
    if ( ! isset( $accounts[ $network ] ) || ! is_array( $accounts[ $network ] ) ) {
        $accounts[ $network ] = [];
    }

    $filtered = [];
    foreach ( $accounts[ $network ] as $existing ) {
        if ( ! isset( $existing['account_id'] ) || $existing['account_id'] !== $account['account_id'] ) {
            $filtered[] = $existing;
        }
    }

    $filtered[] = $account;
    $accounts[ $network ] = $filtered;

    hwd_ss_save_accounts( $accounts );
}

function hwd_ss_clear_accounts( $network ) {
    $accounts = hwd_ss_get_accounts();
    unset( $accounts[ $network ] );
    hwd_ss_save_accounts( $accounts );
}

function hwd_ss_set_active_account( $network, $account_id ) {
    $accounts = hwd_ss_get_accounts();
    if ( empty( $accounts[ $network ] ) ) {
        return false;
    }

    foreach ( $accounts[ $network ] as &$account ) {
        $account['active'] = isset( $account['account_id'] ) && $account['account_id'] === $account_id;
    }
    unset( $account );

    hwd_ss_save_accounts( $accounts );
    return true;
}

function hwd_ss_get_active_account( $network ) {
    $accounts = hwd_ss_get_accounts();
    if ( empty( $accounts[ $network ] ) ) {
        return null;
    }

    foreach ( $accounts[ $network ] as $account ) {
        if ( ! empty( $account['active'] ) ) {
            return $account;
        }
    }

    return $accounts[ $network ][0] ?? null;
}

function hwd_ss_has_app_credentials( $network, $settings = null ) {
    if ( ! $settings ) {
        $settings = hwd_ss_get_settings();
    }

    $creds = $settings['credentials'];
    switch ( $network ) {
        case 'facebook':
            return ! empty( $creds['facebook']['app_id'] ) && ! empty( $creds['facebook']['app_secret'] );
        case 'linkedin':
            return ! empty( $creds['linkedin']['client_id'] ) && ! empty( $creds['linkedin']['client_secret'] );
        case 'instagram':
            return ! empty( $creds['instagram']['app_id'] ) && ! empty( $creds['instagram']['app_secret'] );
        case 'x':
            return ! empty( $creds['x']['client_id'] ) && ! empty( $creds['x']['client_secret'] );
        default:
            return false;
    }
}
