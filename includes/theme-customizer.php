<?php
/**
 * CGR Theme Settings
 * Adds a settings page under Settings > CGR Theme.
 * Controls Global Colors, Fonts, and UI Elements via CSS Variables.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CGR_Theme_Settings {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'wp_head', array( __CLASS__, 'output_css' ) );
    }

    public static function add_admin_menu() {
        add_options_page(
            'CGR Theme Settings',
            'CGR Theme',
            'manage_options',
            'cgr-theme-settings',
            array( __CLASS__, 'settings_page_html' )
        );
    }

    public static function register_settings() {
        // Register a setting for each field
        $settings = [
            'cgr_primary' => '#2E6B3F',
            'cgr_secondary' => '#1A4225',
            'cgr_accent' => '#007BFF',
            'cgr_earth' => '#8B5E3B',
            'cgr_sand' => '#F7F5F0',
            'cgr_text_dark' => '#212529',
            'cgr_text_light' => '#6C757D',
            'cgr_heading_font' => 'Poppins',
            'cgr_body_font' => 'Open Sans',
            'cgr_btn_bg' => '#2E6B3F',
            'cgr_btn_text' => '#FFFFFF',
            'cgr_btn_radius' => '4px',
            'cgr_btn_padding' => '10px 20px',
        ];

        foreach ($settings as $key => $default) {
            register_setting( 'cgr_theme_options_group', $key );
        }

        // Sections
        add_settings_section( 'cgr_colors_section', 'Global Colors', null, 'cgr-theme-settings' );
        add_settings_section( 'cgr_typography_section', 'Typography', null, 'cgr-theme-settings' );
        add_settings_section( 'cgr_buttons_section', 'Buttons & UI', null, 'cgr-theme-settings' );

        // Fields - Colors
        self::add_field('cgr_primary', 'Primary Color', 'cgr_colors_section', 'color');
        self::add_field('cgr_secondary', 'Secondary Color', 'cgr_colors_section', 'color');
        self::add_field('cgr_accent', 'Accent Color', 'cgr_colors_section', 'color');
        self::add_field('cgr_earth', 'Earth Color', 'cgr_colors_section', 'color');
        self::add_field('cgr_sand', 'Sand Color', 'cgr_colors_section', 'color');
        self::add_field('cgr_text_dark', 'Text Dark', 'cgr_colors_section', 'color');
        self::add_field('cgr_text_light', 'Text Light', 'cgr_colors_section', 'color');

        // Fields - Typography
        self::add_field('cgr_heading_font', 'Heading Font Family', 'cgr_typography_section', 'text');
        self::add_field('cgr_body_font', 'Body Font Family', 'cgr_typography_section', 'text');

        // Fields - Buttons
        self::add_field('cgr_btn_bg', 'Button Background', 'cgr_buttons_section', 'color');
        self::add_field('cgr_btn_text', 'Button Text Color', 'cgr_buttons_section', 'color');
        self::add_field('cgr_btn_radius', 'Button Border Radius', 'cgr_buttons_section', 'text');
        self::add_field('cgr_btn_padding', 'Button Padding', 'cgr_buttons_section', 'text');
    }

    private static function add_field($id, $label, $section, $type) {
        add_settings_field(
            $id,
            $label,
            array( __CLASS__, 'render_field' ),
            'cgr-theme-settings',
            $section,
            array( 'id' => $id, 'type' => $type )
        );
    }

    public static function render_field($args) {
        $value = get_option( $args['id'] );
        if ( $args['type'] === 'color' ) {
            echo '<input type="color" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '">';
        } else {
            echo '<input type="text" name="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
        }
    }

    public static function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>CGR Theme Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'cgr_theme_options_group' );
                do_settings_sections( 'cgr-theme-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function output_css() {
        ?>
        <style type="text/css" id="cgr-theme-settings-css">
            :root {
                --cgr-primary: <?php echo get_option('cgr_primary', '#2E6B3F'); ?>;
                --cgr-secondary: <?php echo get_option('cgr_secondary', '#1A4225'); ?>;
                --cgr-accent: <?php echo get_option('cgr_accent', '#007BFF'); ?>;
                --cgr-earth: <?php echo get_option('cgr_earth', '#8B5E3B'); ?>;
                --cgr-sand: <?php echo get_option('cgr_sand', '#F7F5F0'); ?>;
                --cgr-text-dark: <?php echo get_option('cgr_text_dark', '#212529'); ?>;
                --cgr-text-light: <?php echo get_option('cgr_text_light', '#6C757D'); ?>;
                
                --cgr-heading-font: '<?php echo get_option('cgr_heading_font', 'Poppins'); ?>', sans-serif;
                --cgr-body-font: '<?php echo get_option('cgr_body_font', 'Open Sans'); ?>', sans-serif;

                --cgr-btn-bg: <?php echo get_option('cgr_btn_bg', '#2E6B3F'); ?>;
                --cgr-btn-text: <?php echo get_option('cgr_btn_text', '#FFFFFF'); ?>;
                --cgr-btn-radius: <?php echo get_option('cgr_btn_radius', '4px'); ?>;
                --cgr-btn-padding: <?php echo get_option('cgr_btn_padding', '10px 20px'); ?>;
            }

            /* Apply Button Styles */
            button, .button, input[type="submit"], .cgr-btn {
                background-color: var(--cgr-btn-bg);
                color: var(--cgr-btn-text);
                border-radius: var(--cgr-btn-radius);
                padding: var(--cgr-btn-padding);
                border: none;
                cursor: pointer;
            }
            button:hover, .button:hover, input[type="submit"]:hover, .cgr-btn:hover {
                opacity: 0.9;
            }
        </style>
        <?php
    }
}

CGR_Theme_Settings::init();
