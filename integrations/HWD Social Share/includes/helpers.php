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
            ],
            'facebook' => [
                'page_id'      => '',
                'access_token' => '',
            ],
            'linkedin' => [
                'access_token' => '',
                'author_urn'   => '',
            ],
            'instagram' => [
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
