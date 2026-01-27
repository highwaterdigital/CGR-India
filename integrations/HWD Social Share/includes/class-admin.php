<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_post_hwd_ss_save_settings', [ $this, 'save_settings' ] );
    }

    public function register_menu() {
        add_menu_page(
            'HWD Social Share',
            'HWD Social Share',
            'manage_options',
            'hwd-social-share',
            [ $this, 'render_settings_page' ],
            'dashicons-share',
            80
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = hwd_ss_get_settings();
        $logs = get_option( 'hwd_ss_logs', [] );
        if ( ! is_array( $logs ) ) {
            $logs = [];
        }

        echo '<div class="wrap">';
        echo '<h1>HWD Social Share</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'hwd_ss_save_settings', 'hwd_ss_settings_nonce' );
        echo '<input type="hidden" name="action" value="hwd_ss_save_settings">';

        echo '<h2>Templates</h2>';
        echo '<table class="form-table"><tbody>';
        $templates = $settings['templates'];
        $template_fields = [
            'default' => 'Default',
            'x' => 'X (Twitter)',
            'facebook' => 'Facebook',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube',
        ];
        foreach ( $template_fields as $key => $label ) {
            echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
            echo '<textarea name="templates[' . esc_attr( $key ) . ']" rows="3" cols="60">' . esc_textarea( $templates[ $key ] ) . '</textarea>';
            echo '<p class="description">Available: {title}, {excerpt}, {url}</p>';
            echo '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Credentials</h2>';
        echo '<table class="form-table"><tbody>';

        echo '<tr><th scope="row">X (Twitter) API Key</th><td><input type="text" name="credentials[x][api_key]" value="' . esc_attr( $settings['credentials']['x']['api_key'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X (Twitter) API Secret</th><td><input type="text" name="credentials[x][api_secret]" value="' . esc_attr( $settings['credentials']['x']['api_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Access Token</th><td><input type="text" name="credentials[x][access_token]" value="' . esc_attr( $settings['credentials']['x']['access_token'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Access Secret</th><td><input type="text" name="credentials[x][access_secret]" value="' . esc_attr( $settings['credentials']['x']['access_secret'] ) . '" class="regular-text"></td></tr>';

        echo '<tr><th scope="row">Facebook Page ID</th><td><input type="text" name="credentials[facebook][page_id]" value="' . esc_attr( $settings['credentials']['facebook']['page_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Facebook Page Access Token</th><td><input type="text" name="credentials[facebook][access_token]" value="' . esc_attr( $settings['credentials']['facebook']['access_token'] ) . '" class="regular-text"></td></tr>';

        echo '<tr><th scope="row">LinkedIn Access Token</th><td><input type="text" name="credentials[linkedin][access_token]" value="' . esc_attr( $settings['credentials']['linkedin']['access_token'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">LinkedIn Author URN</th><td><input type="text" name="credentials[linkedin][author_urn]" value="' . esc_attr( $settings['credentials']['linkedin']['author_urn'] ) . '" class="regular-text"><p class="description">Example: urn:li:person:XXXX or urn:li:organization:XXXX</p></td></tr>';

        echo '<tr><th scope="row">Instagram User ID</th><td><input type="text" name="credentials[instagram][user_id]" value="' . esc_attr( $settings['credentials']['instagram']['user_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Instagram Access Token</th><td><input type="text" name="credentials[instagram][access_token]" value="' . esc_attr( $settings['credentials']['instagram']['access_token'] ) . '" class="regular-text"></td></tr>';

        echo '<tr><th scope="row">YouTube API Key (unused)</th><td><input type="text" name="credentials[youtube][api_key]" value="' . esc_attr( $settings['credentials']['youtube']['api_key'] ) . '" class="regular-text"></td></tr>';

        echo '</tbody></table>';

        submit_button( 'Save Settings' );
        echo '</form>';

        echo '<h2>Recent Logs</h2>';
        if ( empty( $logs ) ) {
            echo '<p>No logs yet.</p>';
        } else {
            echo '<table class="widefat"><thead><tr>';
            echo '<th>Time (UTC)</th><th>Post ID</th><th>Network</th><th>Success</th><th>Error</th>';
            echo '</tr></thead><tbody>';
            $logs = array_reverse( $logs );
            foreach ( array_slice( $logs, 0, 50 ) as $log ) {
                echo '<tr>';
                echo '<td>' . esc_html( $log['time'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $log['post_id'] ?? '' ) . '</td>';
                echo '<td>' . esc_html( $log['network'] ?? '' ) . '</td>';
                echo '<td>' . ( ! empty( $log['success'] ) ? 'Yes' : 'No' ) . '</td>';
                echo '<td>' . esc_html( $log['error'] ?? '' ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'hwd_ss_save_settings', 'hwd_ss_settings_nonce' );

        $settings = hwd_ss_get_settings();

        $templates = isset( $_POST['templates'] ) && is_array( $_POST['templates'] ) ? $_POST['templates'] : [];
        foreach ( $templates as $key => $value ) {
            $settings['templates'][ $key ] = sanitize_textarea_field( $value );
        }

        $creds = isset( $_POST['credentials'] ) && is_array( $_POST['credentials'] ) ? $_POST['credentials'] : [];
        foreach ( $creds as $network => $fields ) {
            if ( ! is_array( $fields ) ) {
                continue;
            }
            foreach ( $fields as $field => $value ) {
                $settings['credentials'][ $network ][ $field ] = sanitize_text_field( $value );
            }
        }

        update_option( 'hwd_ss_settings', $settings, false );

        wp_redirect( admin_url( 'admin.php?page=hwd-social-share&updated=1' ) );
        exit;
    }
}

new HWD_SS_Admin();
