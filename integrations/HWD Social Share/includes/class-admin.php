<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class HWD_SS_Admin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
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

    public function enqueue_assets( $hook ) {
        if ( $hook !== 'toplevel_page_hwd-social-share' ) {
            return;
        }

        $css_path = HWD_SS_DIR . '/assets/admin.css';
        $js_path  = HWD_SS_DIR . '/assets/admin.js';

        if ( file_exists( $css_path ) ) {
            wp_enqueue_style(
                'hwd-ss-admin',
                HWD_SS_URL . 'assets/admin.css',
                [],
                filemtime( $css_path )
            );
        }

        if ( file_exists( $js_path ) ) {
            wp_enqueue_script(
                'hwd-ss-admin',
                HWD_SS_URL . 'assets/admin.js',
                [],
                filemtime( $js_path ),
                true
            );
        }
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

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'accounts';
        $tabs = [
            'accounts' => 'Accounts',
            'settings' => 'Templates & Credentials',
        ];

        echo '<div class="wrap hwd-ss-admin">';
        echo '<h1>HWD Social Share</h1>';
        $status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        if ( $status ) {
            $message = '';
            $class = 'updated';
            if ( $status === 'connected' ) {
                $message = 'Account connected successfully.';
            } elseif ( $status === 'disconnected' ) {
                $message = 'Account disconnected.';
            } elseif ( $status === 'missing_app_credentials' ) {
                $message = 'App credentials are missing. Add them in Settings first.';
                $class = 'error';
            } else {
                $message = 'Connection failed. Please try again.';
                $class = 'error';
            }
            if ( $message ) {
                echo '<div class="notice ' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
            }
        }
        echo '<nav class="nav-tab-wrapper hwd-ss-tabs">';
        foreach ( $tabs as $key => $label ) {
            $class = $active_tab === $key ? ' nav-tab-active' : '';
            $url = admin_url( 'admin.php?page=hwd-social-share&tab=' . $key );
            echo '<a class="nav-tab' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';

        if ( $active_tab === 'settings' ) {
            $this->render_settings_tab( $settings, $logs );
        } else {
            $this->render_accounts_tab( $settings );
        }

        echo '</div>';
    }

    private function render_accounts_tab( $settings ) {
        $networks = $this->get_networks( $settings );
        $accounts = hwd_ss_get_accounts();
        $settings_url = admin_url( 'admin.php?page=hwd-social-share&tab=settings' );

        echo '<div class="hwd-ss-accounts">';
        echo '<div class="hwd-ss-layout">';

        echo '<aside class="hwd-ss-sidebar">';
        echo '<div class="hwd-ss-sidebar-header">Accounts</div>';
        echo '<div class="hwd-ss-sidebar-filter">All Networks</div>';
        echo '<ul class="hwd-ss-network-list">';
        foreach ( $networks as $network ) {
            $status_class = $network['connected'] ? 'is-connected' : 'is-disconnected';
            echo '<li class="hwd-ss-network-item ' . esc_attr( $status_class ) . '">';
            echo '<span class="hwd-ss-icon ' . esc_attr( $network['icon_class'] ) . '" style="--hwd-color:' . esc_attr( $network['color'] ) . '"></span>';
            echo '<span class="hwd-ss-network-label">' . esc_html( $network['label'] ) . '</span>';
            echo $network['connected'] ? '<span class="hwd-ss-pill">Connected</span>' : '<span class="hwd-ss-pill hwd-ss-pill-muted">Not connected</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</aside>';

        echo '<section class="hwd-ss-main">';
        echo '<div class="hwd-ss-topbar">';
        echo '<label class="hwd-ss-search">';
        echo '<span class="dashicons dashicons-search"></span>';
        echo '<input type="search" placeholder="Search accounts" aria-label="Search accounts">';
        echo '</label>';
        echo '</div>';

        echo '<div class="hwd-ss-hero">';
        echo '<button type="button" class="hwd-ss-connect-card hwd-ss-open-connect">';
        echo '<span class="hwd-ss-plus">+</span>';
        echo '<span>Connect Account</span>';
        echo '</button>';

        echo '<div class="hwd-ss-help-card">';
        echo '<h3>Attach your social media accounts</h3>';
        echo '<p>Use <strong>Connect</strong> to authenticate quickly, or choose <strong>Custom App</strong> to enter your own API credentials.</p>';
        echo '<p class="hwd-ss-help-note">Custom App setup is available under Settings. You can keep drafts until credentials are saved.</p>';
        echo '<a class="hwd-ss-link" href="' . esc_url( $settings_url ) . '#hwd-ss-credentials">Go to credentials</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="hwd-ss-connected">';
        echo '<h2>Current connections</h2>';
        echo '<div class="hwd-ss-connected-grid">';
        foreach ( $networks as $network ) {
            $status = $network['connected'] ? 'Connected' : 'Not connected';
            $button_label = $network['connected'] ? 'Manage' : 'Custom App';
            $button_url = $settings_url . '#hwd-ss-cred-' . $network['key'];
            $connect_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=hwd_ss_connect&network=' . $network['key'] ),
                'hwd_ss_connect_' . $network['key']
            );
            $disconnect_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=hwd_ss_disconnect&network=' . $network['key'] ),
                'hwd_ss_disconnect_' . $network['key']
            );
            echo '<div class="hwd-ss-card">';
            echo '<div class="hwd-ss-card-head">';
            echo '<span class="hwd-ss-icon ' . esc_attr( $network['icon_class'] ) . '" style="--hwd-color:' . esc_attr( $network['color'] ) . '"></span>';
            echo '<div>';
            echo '<div class="hwd-ss-card-title">' . esc_html( $network['label'] ) . '</div>';
            echo '<div class="hwd-ss-card-status">' . esc_html( $status ) . '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="hwd-ss-card-actions">';
            echo '<a class="button hwd-ss-button-outline" href="' . esc_url( $button_url ) . '">' . esc_html( $button_label ) . '</a>';
            if ( ! $network['connected'] ) {
                if ( $network['can_connect'] ) {
                    echo '<a class="button hwd-ss-button-primary" href="' . esc_url( $connect_url ) . '">Connect</a>';
                } else {
                    echo '<button type="button" class="button hwd-ss-button-muted" disabled>Add App Credentials</button>';
                }
            } else {
                echo '<a class="button hwd-ss-button-muted" href="' . esc_url( $disconnect_url ) . '">Disconnect</a>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';

        if ( ! empty( $accounts ) ) {
            echo '<div class="hwd-ss-account-list">';
            echo '<h2>Connected accounts</h2>';
            echo '<div class="hwd-ss-connected-grid">';
            foreach ( $networks as $network ) {
                if ( empty( $accounts[ $network['key'] ] ) ) {
                    continue;
                }
                foreach ( $accounts[ $network['key'] ] as $account ) {
                    $account_name = $account['account_name'] ?? '';
                    $account_id = $account['account_id'] ?? '';
                    $type = $account['type'] ?? '';
                    $active_label = ! empty( $account['active'] ) ? 'Active' : 'Connected';
                    echo '<div class="hwd-ss-card">';
                    echo '<div class="hwd-ss-card-head">';
                    echo '<span class="hwd-ss-icon ' . esc_attr( $network['icon_class'] ) . '" style="--hwd-color:' . esc_attr( $network['color'] ) . '"></span>';
                    echo '<div>';
                    echo '<div class="hwd-ss-card-title">' . esc_html( $account_name ) . '</div>';
                    if ( $type ) {
                        echo '<div class="hwd-ss-card-status">' . esc_html( ucfirst( $type ) ) . '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="hwd-ss-card-actions">';
                    echo '<span class="hwd-ss-pill">' . esc_html( $active_label ) . '</span>';
                    if ( $account_id ) {
                        echo '<span class="hwd-ss-meta">ID: ' . esc_html( $account_id ) . '</span>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</section>';

        echo '</div>';

        echo '<div class="hwd-ss-modal" aria-hidden="true">';
        echo '<div class="hwd-ss-modal-dialog" role="dialog" aria-modal="true" aria-label="Connect Account">';
        echo '<div class="hwd-ss-modal-header">';
        echo '<h2>Connect Account</h2>';
        echo '<button type="button" class="hwd-ss-modal-close" aria-label="Close">&times;</button>';
        echo '</div>';
        echo '<div class="hwd-ss-modal-grid">';
        foreach ( $networks as $network ) {
            $status = $network['connected'] ? 'Connected' : 'Ready to connect';
            $custom_url = $settings_url . '#hwd-ss-cred-' . $network['key'];
            $connect_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=hwd_ss_connect&network=' . $network['key'] ),
                'hwd_ss_connect_' . $network['key']
            );
            echo '<div class="hwd-ss-modal-card">';
            echo '<div class="hwd-ss-card-head">';
            echo '<span class="hwd-ss-icon ' . esc_attr( $network['icon_class'] ) . '" style="--hwd-color:' . esc_attr( $network['color'] ) . '"></span>';
            echo '<div>';
            echo '<div class="hwd-ss-card-title">' . esc_html( $network['label'] ) . '</div>';
            echo '<div class="hwd-ss-card-status">' . esc_html( $status ) . '</div>';
            echo '</div>';
            echo '</div>';
            echo '<div class="hwd-ss-card-actions">';
            echo '<a class="button hwd-ss-button-outline" href="' . esc_url( $custom_url ) . '">Custom App</a>';
            if ( $network['connected'] ) {
                echo '<button type="button" class="button hwd-ss-button-muted" disabled>Connected</button>';
            } elseif ( $network['can_connect'] ) {
                echo '<a class="button hwd-ss-button-primary" href="' . esc_url( $connect_url ) . '">Connect</a>';
            } else {
                echo '<button type="button" class="button hwd-ss-button-muted" disabled>Add App Credentials</button>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p class="hwd-ss-modal-note">Connect uses OAuth when app credentials are saved. Use Custom App to enter your credentials first.</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    private function render_settings_tab( $settings, $logs ) {
        echo '<div class="hwd-ss-settings">';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'hwd_ss_save_settings', 'hwd_ss_settings_nonce' );
        echo '<input type="hidden" name="action" value="hwd_ss_save_settings">';

        echo '<h2 id="hwd-ss-templates">Templates</h2>';
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

        echo '<h2 id="hwd-ss-credentials">Credentials</h2>';
        $fb_redirect = admin_url( 'admin-post.php?action=hwd_ss_oauth_callback&network=facebook' );
        $li_redirect = admin_url( 'admin-post.php?action=hwd_ss_oauth_callback&network=linkedin' );
        echo '<p class="description">OAuth Redirect URLs: Facebook <code>' . esc_html( $fb_redirect ) . '</code> | LinkedIn <code>' . esc_html( $li_redirect ) . '</code></p>';
        echo '<table class="form-table"><tbody>';

        echo '<tr id="hwd-ss-cred-x"><th scope="row">X (Twitter) API Key</th><td><input type="text" name="credentials[x][api_key]" value="' . esc_attr( $settings['credentials']['x']['api_key'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X (Twitter) API Secret</th><td><input type="text" name="credentials[x][api_secret]" value="' . esc_attr( $settings['credentials']['x']['api_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Access Token</th><td><input type="text" name="credentials[x][access_token]" value="' . esc_attr( $settings['credentials']['x']['access_token'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Access Secret</th><td><input type="text" name="credentials[x][access_secret]" value="' . esc_attr( $settings['credentials']['x']['access_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Client ID (OAuth)</th><td><input type="text" name="credentials[x][client_id]" value="' . esc_attr( $settings['credentials']['x']['client_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">X Client Secret (OAuth)</th><td><input type="text" name="credentials[x][client_secret]" value="' . esc_attr( $settings['credentials']['x']['client_secret'] ) . '" class="regular-text"></td></tr>';

        echo '<tr id="hwd-ss-cred-facebook"><th scope="row">Facebook App ID</th><td><input type="text" name="credentials[facebook][app_id]" value="' . esc_attr( $settings['credentials']['facebook']['app_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Facebook App Secret</th><td><input type="text" name="credentials[facebook][app_secret]" value="' . esc_attr( $settings['credentials']['facebook']['app_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Facebook Page ID</th><td><input type="text" name="credentials[facebook][page_id]" value="' . esc_attr( $settings['credentials']['facebook']['page_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Facebook Page Access Token</th><td><input type="text" name="credentials[facebook][access_token]" value="' . esc_attr( $settings['credentials']['facebook']['access_token'] ) . '" class="regular-text"></td></tr>';

        echo '<tr id="hwd-ss-cred-linkedin"><th scope="row">LinkedIn Client ID</th><td><input type="text" name="credentials[linkedin][client_id]" value="' . esc_attr( $settings['credentials']['linkedin']['client_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">LinkedIn Client Secret</th><td><input type="text" name="credentials[linkedin][client_secret]" value="' . esc_attr( $settings['credentials']['linkedin']['client_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">LinkedIn Access Token</th><td><input type="text" name="credentials[linkedin][access_token]" value="' . esc_attr( $settings['credentials']['linkedin']['access_token'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">LinkedIn Author URN</th><td><input type="text" name="credentials[linkedin][author_urn]" value="' . esc_attr( $settings['credentials']['linkedin']['author_urn'] ) . '" class="regular-text"><p class="description">Example: urn:li:person:XXXX or urn:li:organization:XXXX</p></td></tr>';

        echo '<tr id="hwd-ss-cred-instagram"><th scope="row">Instagram App ID</th><td><input type="text" name="credentials[instagram][app_id]" value="' . esc_attr( $settings['credentials']['instagram']['app_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Instagram App Secret</th><td><input type="text" name="credentials[instagram][app_secret]" value="' . esc_attr( $settings['credentials']['instagram']['app_secret'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Instagram User ID</th><td><input type="text" name="credentials[instagram][user_id]" value="' . esc_attr( $settings['credentials']['instagram']['user_id'] ) . '" class="regular-text"></td></tr>';
        echo '<tr><th scope="row">Instagram Access Token</th><td><input type="text" name="credentials[instagram][access_token]" value="' . esc_attr( $settings['credentials']['instagram']['access_token'] ) . '" class="regular-text"></td></tr>';

        echo '<tr id="hwd-ss-cred-youtube"><th scope="row">YouTube API Key (unused)</th><td><input type="text" name="credentials[youtube][api_key]" value="' . esc_attr( $settings['credentials']['youtube']['api_key'] ) . '" class="regular-text"></td></tr>';

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

    private function get_networks( $settings ) {
        $accounts = hwd_ss_get_accounts();
        return [
            [
                'key' => 'facebook',
                'label' => 'Facebook',
                'icon_class' => 'dashicons dashicons-facebook-alt',
                'color' => '#1877F2',
                'connected' => $this->network_connected( 'facebook', $settings, $accounts ),
                'can_connect' => hwd_ss_has_app_credentials( 'facebook', $settings ),
            ],
            [
                'key' => 'linkedin',
                'label' => 'LinkedIn',
                'icon_class' => 'dashicons dashicons-linkedin',
                'color' => '#0A66C2',
                'connected' => $this->network_connected( 'linkedin', $settings, $accounts ),
                'can_connect' => hwd_ss_has_app_credentials( 'linkedin', $settings ),
            ],
            [
                'key' => 'x',
                'label' => 'X (Twitter)',
                'icon_class' => 'dashicons dashicons-twitter',
                'color' => '#111111',
                'connected' => $this->network_connected( 'x', $settings, $accounts ),
                'can_connect' => false,
            ],
            [
                'key' => 'instagram',
                'label' => 'Instagram',
                'icon_class' => 'dashicons dashicons-instagram',
                'color' => '#E1306C',
                'connected' => $this->network_connected( 'instagram', $settings, $accounts ),
                'can_connect' => false,
            ],
            [
                'key' => 'youtube',
                'label' => 'YouTube',
                'icon_class' => 'dashicons dashicons-video-alt3',
                'color' => '#FF0000',
                'connected' => $this->network_connected( 'youtube', $settings, $accounts ),
                'can_connect' => false,
            ],
        ];
    }

    private function network_connected( $network, $settings, $accounts ) {
        if ( ! empty( $accounts[ $network ] ) ) {
            return true;
        }
        $creds = $settings['credentials'];
        switch ( $network ) {
            case 'x':
                return ! empty( $creds['x']['access_token'] ) && ! empty( $creds['x']['access_secret'] );
            case 'facebook':
                return ! empty( $creds['facebook']['page_id'] ) && ! empty( $creds['facebook']['access_token'] );
            case 'linkedin':
                return ! empty( $creds['linkedin']['access_token'] ) && ! empty( $creds['linkedin']['author_urn'] );
            case 'instagram':
                return ! empty( $creds['instagram']['access_token'] ) && ! empty( $creds['instagram']['user_id'] );
            case 'youtube':
                return ! empty( $creds['youtube']['api_key'] );
            default:
                return false;
        }
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
