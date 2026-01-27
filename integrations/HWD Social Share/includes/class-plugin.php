<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Plugin {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate() {
        if ( ! get_option( 'hwd_ss_settings' ) ) {
            update_option( 'hwd_ss_settings', hwd_ss_default_settings(), false );
        }
    }

    private function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
        add_action( 'save_post', [ $this, 'save_meta_box' ], 10, 2 );
        add_action( 'transition_post_status', [ $this, 'schedule_on_publish' ], 10, 3 );
        add_action( 'hwd_ss_process_share', [ $this, 'process_share' ] );
    }

    public function add_meta_box() {
        $post_types = get_post_types( [ 'public' => true ], 'names' );
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'hwd_ss_meta_box',
                'HWD Social Share',
                [ $this, 'render_meta_box' ],
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function render_meta_box( $post ) {
        $enabled = get_post_meta( $post->ID, '_hwd_ss_enabled', true );
        $networks = get_post_meta( $post->ID, '_hwd_ss_networks', true );
        $schedule = get_post_meta( $post->ID, '_hwd_ss_schedule', true );
        if ( ! is_array( $networks ) ) {
            $networks = [];
        }

        $settings = hwd_ss_get_settings();
        $limits = $settings['limits'];

        wp_nonce_field( 'hwd_ss_meta_box', 'hwd_ss_nonce' );

        echo '<p><label><input type="checkbox" name="hwd_ss_enabled" value="1"' . checked( $enabled, '1', false ) . '> Share this post</label></p>';
        echo '<p><strong>Networks</strong></p>';

        $available = [
            'x' => 'X (Twitter) - limit ' . (int) $limits['x'],
            'facebook' => 'Facebook Page',
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'youtube' => 'YouTube (not supported yet)',
        ];

        foreach ( $available as $key => $label ) {
            $checked = in_array( $key, $networks, true );
            $disabled = $key === 'youtube' ? ' disabled' : '';
            echo '<label style="display:block;margin-bottom:4px;">';
            echo '<input type="checkbox" name="hwd_ss_networks[' . esc_attr( $key ) . ']" value="1"' . checked( $checked, true, false ) . $disabled . '> ' . esc_html( $label );
            echo '</label>';
        }

        echo '<p style="margin-top:8px;"><label>Share time (optional)<br>';
        echo '<input type="datetime-local" name="hwd_ss_schedule" value="' . esc_attr( $schedule ) . '" style="width:100%;"></label></p>';
        echo '<p style="font-size:12px;">If empty, shares on publish. If set, uses this time.</p>';
    }

    public function save_meta_box( $post_id, $post ) {
        if ( ! isset( $_POST['hwd_ss_nonce'] ) || ! wp_verify_nonce( $_POST['hwd_ss_nonce'], 'hwd_ss_meta_box' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $enabled = isset( $_POST['hwd_ss_enabled'] ) ? '1' : '0';
        $networks = isset( $_POST['hwd_ss_networks'] ) && is_array( $_POST['hwd_ss_networks'] ) ? array_keys( $_POST['hwd_ss_networks'] ) : [];
        $schedule = isset( $_POST['hwd_ss_schedule'] ) ? sanitize_text_field( $_POST['hwd_ss_schedule'] ) : '';

        update_post_meta( $post_id, '_hwd_ss_enabled', $enabled );
        update_post_meta( $post_id, '_hwd_ss_networks', $networks );
        update_post_meta( $post_id, '_hwd_ss_schedule', $schedule );
    }

    public function schedule_on_publish( $new_status, $old_status, $post ) {
        if ( $new_status !== 'publish' || $old_status === 'publish' ) {
            return;
        }

        $enabled = get_post_meta( $post->ID, '_hwd_ss_enabled', true );
        if ( $enabled !== '1' ) {
            return;
        }

        $networks = get_post_meta( $post->ID, '_hwd_ss_networks', true );
        if ( ! is_array( $networks ) || empty( $networks ) ) {
            return;
        }

        $schedule = get_post_meta( $post->ID, '_hwd_ss_schedule', true );
        $timestamp = time();

        if ( ! empty( $schedule ) ) {
            $parsed = strtotime( $schedule );
            if ( $parsed ) {
                $timestamp = $parsed;
            }
        }

        if ( $timestamp < time() ) {
            $timestamp = time() + 10;
        }

        if ( ! wp_next_scheduled( 'hwd_ss_process_share', [ $post->ID ] ) ) {
            wp_schedule_single_event( $timestamp, 'hwd_ss_process_share', [ $post->ID ] );
        }
    }

    public function process_share( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return;
        }

        $networks = get_post_meta( $post_id, '_hwd_ss_networks', true );
        if ( ! is_array( $networks ) || empty( $networks ) ) {
            return;
        }

        $settings = hwd_ss_get_settings();
        $templates = $settings['templates'];
        $limits = $settings['limits'];

        $payload = [
            'title' => get_the_title( $post_id ),
            'excerpt' => wp_strip_all_tags( get_the_excerpt( $post_id ) ),
            'url' => get_permalink( $post_id ),
            'image_url' => hwd_ss_get_featured_image_url( $post_id ),
        ];

        $results = get_post_meta( $post_id, '_hwd_ss_results', true );
        if ( ! is_array( $results ) ) {
            $results = [];
        }

        foreach ( $networks as $network ) {
            if ( isset( $results[ $network ] ) && ! empty( $results[ $network ]['success'] ) ) {
                continue;
            }

            $template = isset( $templates[ $network ] ) ? $templates[ $network ] : $templates['default'];
            $text = hwd_ss_render_template( $template, $payload );
            $limit = isset( $limits[ $network ] ) ? (int) $limits[ $network ] : 0;
            $text = hwd_ss_limit_text( $text, $limit );

            $adapter = $this->get_network_adapter( $network, $settings );
            if ( ! $adapter ) {
                $results[ $network ] = [
                    'success' => false,
                    'error' => 'Unsupported network',
                ];
                continue;
            }

            $response = $adapter->post( [
                'text' => $text,
                'payload' => $payload,
            ] );

            $results[ $network ] = $response;

            hwd_ss_log( [
                'post_id' => $post_id,
                'network' => $network,
                'success' => ! empty( $response['success'] ),
                'error' => $response['error'] ?? '',
            ] );
        }

        update_post_meta( $post_id, '_hwd_ss_results', $results );
    }

    private function get_network_adapter( $network, $settings ) {
        switch ( $network ) {
            case 'x':
                return new HWD_SS_Network_X( $settings );
            case 'facebook':
                return new HWD_SS_Network_Facebook( $settings );
            case 'linkedin':
                return new HWD_SS_Network_LinkedIn( $settings );
            case 'instagram':
                return new HWD_SS_Network_Instagram( $settings );
            case 'youtube':
                return new HWD_SS_Network_YouTube( $settings );
            default:
                return null;
        }
    }
}
