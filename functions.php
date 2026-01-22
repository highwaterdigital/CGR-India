<?php
/*
 * CGR Advanced Theme functions
 *
 * This file sets up theme supports, registers navigation menus,
 * defines a custom post type for programme participants and declares
 * helper functions for synchronising participant data with Google Sheets.
 *
 * Note: Actual Google API calls are left as stubs.  Follow the
 * instructions within the functions to implement the integration using
 * your own service account credentials and Apps Script endpoints.
 */

if ( ! session_id() ) {
    session_start();
}

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

if ( ! defined( 'CGR_CHILD_DIR' ) ) {
    define( 'CGR_CHILD_DIR', get_stylesheet_directory() );
}

/**
 * Load integrations.
 */
require_once CGR_CHILD_DIR . '/integrations/integrations-loader.php';

/**
 * Load Earth Leaders CPT.
 */
/**
 * Load Registrations (Earth Leaders, Scientists, Members).
 */
error_log('CGR DEBUG: Loading registrations-loader.php from functions.php');
require_once CGR_CHILD_DIR . '/includes/registrations/registrations-loader.php';

/**
 * Load Custom Shortcodes.
 */
require_once CGR_CHILD_DIR . '/inc/shortcodes.php';

/**
 * Load Theme Settings.
 */
require_once CGR_CHILD_DIR . '/includes/theme-customizer.php';

/**
 * Load Gallery Metabox.
 */
require_once CGR_CHILD_DIR . '/includes/admin/gallery-metabox.php';

/**
 * Load People Manager.
 */
require_once CGR_CHILD_DIR . '/includes/people-manager.php';

/**
 * Load Awards Dashboard.
 */
require_once CGR_CHILD_DIR . '/includes/awards-manager.php';

/**
 * Load Smart Popups.
 */
require_once CGR_CHILD_DIR . '/includes/cgr-smart-popups.php';

/**
 * Load Guest Counter.
 */
require_once CGR_CHILD_DIR . '/includes/guest-counter.php';

// Register page templates stored in subfolders (e.g., pages/).
/*
add_filter( 'theme_page_templates', function( $templates ) {
    $templates['pages/page-events.php'] = 'Events Calendar';
    return $templates;
} );
*/

// Ensure Events page exists and uses the Events Calendar template.
/*
add_action( 'init', function() {
    $events_page = get_page_by_path( 'events' );
    if ( ! $events_page ) {
        $page_id = wp_insert_post( array(
            'post_type'    => 'page',
            'post_title'   => 'Events',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_name'    => 'events',
        ) );
        if ( ! is_wp_error( $page_id ) ) {
            $events_page = get_post( $page_id );
        }
    }
    if ( $events_page ) {
        update_post_meta( $events_page->ID, '_wp_page_template', 'pages/page-events.php' );
    }
} );
*/

// Redirect legacy /programs/events path to /events.
add_action( 'template_redirect', function() {
    $req = trim( $_SERVER['REQUEST_URI'], '/' );
    if ( $req === 'programs/events' ) {
        wp_safe_redirect( home_url( '/events/' ), 301 );
        exit;
    }
} );

// Temporary Debug Tool
add_action('init', function() {
    if ( isset($_GET['cgr_debug_posts']) && current_user_can('manage_options') ) {
        $type = sanitize_text_field($_GET['cgr_debug_posts']);
        $posts = get_posts([
            'post_type' => $type,
            'numberposts' => -1,
            'post_status' => 'any'
        ]);
        echo '<pre>';
        echo "Debug Report for Post Type: $type\n";
        echo "Found " . count($posts) . " posts.\n\n";
        foreach($posts as $p) {
            echo "ID: " . $p->ID . "\n";
            echo "Title: " . $p->post_title . "\n";
            echo "Status: " . $p->post_status . "\n";
            echo "Meta:\n";
            print_r(get_post_meta($p->ID));
            echo "--------------------------------\n";
        }
        echo '</pre>';
        exit;
    }
});

// Force flush rewrite rules on theme switch or if requested
add_action( 'after_switch_theme', 'flush_rewrite_rules' );
if ( isset( $_GET['cgr_flush_rewrites'] ) ) {
    add_action( 'init', 'flush_rewrite_rules' );
}

// Ensure rewrite rules are flushed if they are missing
add_action('init', function() {
    if ( ! get_option('cgr_rewrites_flushed_v12') ) {
        flush_rewrite_rules();
        update_option('cgr_rewrites_flushed_v12', true);
    }
});

/**
 * DEBUG: Template Hierarchy & Query Inspector
 */
add_action('template_redirect', function() {
    if ( !current_user_can('manage_options') ) return;

    global $wp_query, $template;
    
    $debug_log = "CGR DEBUG: Request Analysis\n";
    $debug_log .= "URL: " . $_SERVER['REQUEST_URI'] . "\n";
    $debug_log .= "Query Vars: " . print_r($wp_query->query_vars, true) . "\n";
    $debug_log .= "Is 404: " . ($wp_query->is_404() ? 'YES' : 'NO') . "\n";
    $debug_log .= "Is Home: " . ($wp_query->is_home() ? 'YES' : 'NO') . "\n";
    $debug_log .= "Is Page: " . ($wp_query->is_page() ? 'YES' : 'NO') . "\n";
    $debug_log .= "Is Archive: " . ($wp_query->is_archive() ? 'YES' : 'NO') . "\n";
    $debug_log .= "Template: " . $template . "\n";
    
    error_log($debug_log);
    
    // If we are on the blog page and it's a 404, force a flush
    if ( strpos($_SERVER['REQUEST_URI'], '/blog') !== false && $wp_query->is_404() ) {
        error_log('CGR DEBUG: 404 on /blog detected. Attempting to diagnose.');
    }
});

add_filter('template_include', function($template) {
    if ( !current_user_can('manage_options') ) return $template;
    error_log('CGR DEBUG: Template Selected: ' . $template);
    return $template;
});

/**
 * Force Home Page Template via Code
 * Ensures home-page.php is loaded for the front page regardless of page settings.
 * DISABLED: To allow Elementor editing.
 */
/*
add_filter( 'template_include', function( $template ) {
    if ( is_front_page() ) {
        $home_template = locate_template( array( 'pages/home-page.php', 'home-page.php' ) );
        if ( ! empty( $home_template ) ) {
            return $home_template;
        }
    }
    return $template;
}, 99 );
*/

/**
 * Auto-create Blog Page if missing
 */
/*
add_action('init', function() {
    if ( ! get_option('cgr_blog_page_created_v2') ) {
        $page_title = 'Blog';
        $page_check = get_page_by_title($page_title);
        
        if ( ! isset($page_check->ID) ) {
            $new_page = array(
                'post_type' => 'page',
                'post_title' => $page_title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => 1,
                'post_name' => 'blog'
            );
            $page_id = wp_insert_post($new_page);
            
            if ( ! is_wp_error( $page_id ) ) {
                // Set the page template to page-blog.php
                update_post_meta($page_id, '_wp_page_template', 'pages/page-blog.php');
                error_log('CGR DEBUG: Blog page created successfully with ID: ' . $page_id);
            }
        } else {
            // Ensure template is set correctly
            $current_template = get_post_meta($page_check->ID, '_wp_page_template', true);
            if ( $current_template !== 'pages/page-blog.php' ) {
                update_post_meta($page_check->ID, '_wp_page_template', 'pages/page-blog.php');
                error_log('CGR DEBUG: Fixed Blog page template for ID: ' . $page_check->ID);
            }
        }
        
        update_option('cgr_blog_page_created_v2', true);
        flush_rewrite_rules();
    }
});
*/

/**
 * Auto-fix Home Page Visibility (Robust Version)
 * Ensures the page with 'home-page.php' template is published and set as front page.
 */
/*
add_action('init', function() {
    // 1. Find the Home Page
    $home_page = null;
    
    // Try by template first
    $pages = get_posts([
        'post_type' => 'page',
        'meta_key' => '_wp_page_template',
        'meta_value' => 'pages/home-page.php',
        'post_status' => 'any',
        'numberposts' => 1
    ]);
    
    if ( !empty($pages) ) {
        $home_page = $pages[0];
    } else {
        // Try by title
        $home_page = get_page_by_title('Home');
    }

    // 2. If not found, create it
    if ( !$home_page ) {
        $home_page_id = wp_insert_post([
            'post_title' => 'Home',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '',
        ]);
        if ( !is_wp_error($home_page_id) ) {
            update_post_meta($home_page_id, '_wp_page_template', 'pages/home-page.php');
            $home_page = get_post($home_page_id);
            error_log('CGR FIX: Created new Home Page (ID: ' . $home_page_id . ')');
        }
    }

    // 3. Ensure it is Published and Set as Front Page
    if ( $home_page ) {
        // Force Publish
        if ( $home_page->post_status !== 'publish' ) {
            wp_update_post([
                'ID' => $home_page->ID,
                'post_status' => 'publish'
            ]);
            error_log('CGR FIX: Published Home Page (ID: ' . $home_page->ID . ')');
        }

        // Force Reading Settings
        if ( get_option('show_on_front') !== 'page' ) {
            update_option('show_on_front', 'page');
            error_log('CGR FIX: Set show_on_front to page');
        }
        
        if ( get_option('page_on_front') != $home_page->ID ) {
            update_option('page_on_front', $home_page->ID);
            error_log('CGR FIX: Set page_on_front to ID ' . $home_page->ID);
        }
    }
});
*/

/**
 * Theme setup: register menu locations and add support for title tag and thumbnails.
 */
function cgr_fresh_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'cgr-fresh' ),
        'footer'  => __( 'Footer Menu', 'cgr-fresh' ),
    ) );
}
add_action( 'after_setup_theme', 'cgr_fresh_setup' );

// Ensure Pudami link exists in primary menu (runs once).
add_action('init', function() {
    $locations = get_nav_menu_locations();
    if ( empty( $locations['primary'] ) || get_option( 'cgr_pudami_menu_added_v1' ) ) {
        return;
    }
    $menu_id = $locations['primary'];
    $items   = wp_get_nav_menu_items( $menu_id );
    $has_pudami = false;
    $pudami_url = home_url( '/pudami/' );
    if ( $items ) {
        foreach ( $items as $item ) {
            if ( rtrim( $item->url, '/' ) === rtrim( $pudami_url, '/' ) ) {
                $has_pudami = true;
                break;
            }
        }
    }
    if ( ! $has_pudami ) {
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'  => 'Pudami',
            'menu-item-url'    => $pudami_url,
            'menu-item-status' => 'publish',
        ) );
    }
update_option( 'cgr_pudami_menu_added_v1', true );
});

// Ensure Gallery link exists in primary menu.
add_action('init', function() {
    $locations = get_nav_menu_locations();
    if ( empty( $locations['primary'] ) || get_option( 'cgr_gallery_menu_added_v1' ) ) {
        return;
    }
    $menu_id = $locations['primary'];
    $items   = wp_get_nav_menu_items( $menu_id );
    $gallery_url = home_url( '/gallery/' );
    $has_gallery = false;

    if ( $items ) {
        foreach ( $items as $item ) {
            if ( rtrim( $item->url, '/' ) === rtrim( $gallery_url, '/' ) ) {
                $has_gallery = true;
                break;
            }
        }
    }

    if ( ! $has_gallery ) {
        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'  => 'Gallery',
            'menu-item-url'    => $gallery_url,
            'menu-item-status' => 'publish',
        ) );
    }

    update_option( 'cgr_gallery_menu_added_v1', true );
});

/**
 * Enqueue front‑end scripts.
 */
function cgr_fresh_enqueue_scripts() {
    // Only load on frontend, not in admin area
    if ( is_admin() ) {
        return;
    }

    // Enqueue Dashicons for frontend usage
    wp_enqueue_style( 'dashicons' );

    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    // Enqueue design system variables before component styling
    wp_enqueue_style(
        'cgr-design-system',
        $theme_uri . '/assets/css/design-system.css',
        array(),
        filemtime( $theme_dir . '/assets/css/design-system.css' )
    );

    // Enqueue main stylesheet built on design tokens
    wp_enqueue_style(
        'cgr-child-style',
        $theme_uri . '/assets/css/main.css',
        array( 'cgr-design-system' ),
        filemtime( $theme_dir . '/assets/css/main.css' )
    );

    // Enqueue Lightbox
    wp_enqueue_style(
        'cgr-lightbox',
        $theme_uri . '/assets/css/cgr-lightbox.css',
        array(),
        '1.0.1'
    );
    wp_enqueue_script(
        'cgr-lightbox',
        $theme_uri . '/assets/js/cgr-lightbox.js',
        array(),
        '1.0.1',
        true
    );

    // Register theme script with jQuery dependency; load in footer
    wp_enqueue_script(
        'cgr-fresh-scripts',
        get_stylesheet_directory_uri() . '/js/scripts.js',
        array( 'jquery' ),
        null,
        true
    );
    // Localise ajax endpoint and nonce for potential asynchronous calls
    wp_localize_script( 'cgr-fresh-scripts', 'cgrFresh', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'cgr-fresh-nonce' ),
    ) );

    wp_enqueue_style(
        'cgr-awards',
        $theme_uri . '/assets/css/awards.css',
        array( 'cgr-child-style' ),
        filemtime( $theme_dir . '/assets/css/awards.css' )
    );

    wp_enqueue_script(
        'cgr-awards-popup',
        $theme_uri . '/assets/js/awards-popup.js',
        array(),
        filemtime( $theme_dir . '/assets/js/awards-popup.js' ),
        true
    );

    wp_enqueue_style(
        'cgr-people',
        $theme_uri . '/assets/css/people.css',
        array( 'cgr-child-style' ),
        filemtime( $theme_dir . '/assets/css/people.css' )
    );

    wp_enqueue_style(
        'cgr-team',
        $theme_uri . '/assets/css/team.css',
        array( 'cgr-child-style' ),
        filemtime( $theme_dir . '/assets/css/team.css' )
    );
}
add_action( 'wp_enqueue_scripts', 'cgr_fresh_enqueue_scripts' );

/**
 * Theme design defaults for dynamic tokens.
 */
function cgr_theme_default_options() {
    return array(
        'primary_color'      => '#2E6A4A',
        'accent_color'       => '#2F8FDB',
        'heading_font'       => 'Poppins',
        'body_font'          => 'Inter',
        'header_background'  => '#163C2A',
        'button_primary'     => '#2F8FDB',
        'button_hover'       => '#0A4F6F',
        'footer_background'  => '#163C2A',
        'footer_text_color'  => '#FFFFFF',
    );
}

/**
 * Retrieve merged theme options with defaults.
 */
function cgr_get_theme_options() {
    $defaults = cgr_theme_default_options();
    $options  = get_option( 'cgr_theme_options', array() );
    return wp_parse_args( $options, $defaults );
}

/**
 * Sanitize theme option inputs.
 */
function cgr_sanitize_theme_options( $input ) {
    $defaults      = cgr_theme_default_options();
    $cleaned       = array();
    $color_fields  = array( 'primary_color', 'accent_color', 'header_background', 'button_primary', 'button_hover', 'footer_background', 'footer_text_color' );
    $string_fields = array( 'heading_font', 'body_font' );

    foreach ( $color_fields as $field ) {
        if ( isset( $input[ $field ] ) ) {
            $color = sanitize_hex_color( $input[ $field ] );
            $cleaned[ $field ] = $color ? $color : $defaults[ $field ];
        }
    }

    foreach ( $string_fields as $field ) {
        if ( isset( $input[ $field ] ) ) {
            $cleaned[ $field ] = sanitize_text_field( $input[ $field ] );
        } else {
            $cleaned[ $field ] = $defaults[ $field ];
        }
    }

    return $cleaned;
}

/**
 * Register Theme Options page.
 */
function cgr_register_theme_options_page() {
    add_theme_page(
        __( 'CGR Theme Options', 'cgr-child' ),
        __( 'CGR Theme Options', 'cgr-child' ),
        'manage_options',
        'cgr-theme-options',
        'cgr_render_theme_options_page'
    );
}
// add_action( 'admin_menu', 'cgr_register_theme_options_page' );

/**
 * Register settings, section, and fields.
 */
function cgr_register_theme_settings() {
    register_setting( 'cgr_theme_options_group', 'cgr_theme_options', 'cgr_sanitize_theme_options' );

    add_settings_section(
        'cgr_theme_options_section',
        __( 'CGR Theme Settings', 'cgr-child' ),
        '__return_false',
        'cgr-theme-options'
    );

    $fields = array(
        'primary_color'     => __( 'Primary Color', 'cgr-child' ),
        'accent_color'      => __( 'Accent Color', 'cgr-child' ),
        'heading_font'      => __( 'Heading Font', 'cgr-child' ),
        'body_font'         => __( 'Body Font', 'cgr-child' ),
        'header_background' => __( 'Header Background', 'cgr-child' ),
        'button_primary'    => __( 'Primary Button', 'cgr-child' ),
        'button_hover'      => __( 'Button Hover', 'cgr-child' ),
        'footer_background' => __( 'Footer Background', 'cgr-child' ),
        'footer_text_color' => __( 'Footer Text', 'cgr-child' ),
    );

    foreach ( $fields as $key => $label ) {
        add_settings_field(
            $key,
            $label,
            in_array( $key, array( 'heading_font', 'body_font' ), true ) ? 'cgr_render_text_field' : 'cgr_render_color_field',
            'cgr-theme-options',
            'cgr_theme_options_section',
            array(
                'label_for' => $key,
                'key'       => $key,
            )
        );
    }
}
// add_action( 'admin_init', 'cgr_register_theme_settings' );

/**
 * Render color picker fields.
 */
function cgr_render_color_field( $args ) {
    $options = cgr_get_theme_options();
    $key     = $args['key'];
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : '';
    printf(
        '<input type="color" id="%1$s" name="cgr_theme_options[%1$s]" value="%2$s" class="cgr-color-field" />',
        esc_attr( $key ),
        esc_attr( $value )
    );
}

/**
 * Render text fields (fonts).
 */
function cgr_render_text_field( $args ) {
    $options = cgr_get_theme_options();
    $key     = $args['key'];
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : '';
    printf(
        '<input type="text" id="%1$s" name="cgr_theme_options[%1$s]" value="%2$s" class="regular-text" />',
        esc_attr( $key ),
        esc_attr( $value )
    );
}

/**
 * Render Theme Options page.
 */
function cgr_render_theme_options_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'CGR Theme Options', 'cgr-child' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'cgr_theme_options_group' );
            do_settings_sections( 'cgr-theme-options' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Output dynamic CSS variables from Theme Options.
 */
function cgr_output_dynamic_design_tokens() {
    $cgr = cgr_get_theme_options();
    if ( empty( $cgr ) || ! is_array( $cgr ) ) {
        return;
    }

    echo '<style>:root {';
    echo '--forest-primary: ' . esc_html( $cgr['primary_color'] ) . ';';
    echo '--sky-primary: ' . esc_html( $cgr['accent_color'] ) . ';';
    echo "--font-heading: '" . esc_html( $cgr['heading_font'] ) . "';";
    echo "--font-body: '" . esc_html( $cgr['body_font'] ) . "';";
    echo '--header-bg: ' . esc_html( $cgr['header_background'] ) . ';';
    echo '--button-primary: ' . esc_html( $cgr['button_primary'] ) . ';';
    echo '--button-hover: ' . esc_html( $cgr['button_hover'] ) . ';';
    echo '--footer-bg: ' . esc_html( $cgr['footer_background'] ) . ';';
    echo '--footer-text: ' . esc_html( $cgr['footer_text_color'] ) . ';';
    echo '}</style>';
}
add_action( 'wp_head', 'cgr_output_dynamic_design_tokens' );

/**
 * Register the Participant custom post type and associated taxonomies.
 */
function cgr_fresh_register_participants() {
    // Post type
    $labels = array(
        'name'               => _x( 'Participants', 'post type general name', 'cgr-fresh' ),
        'singular_name'      => _x( 'Participant', 'post type singular name', 'cgr-fresh' ),
        'menu_name'          => _x( 'Participants', 'admin menu', 'cgr-fresh' ),
        'name_admin_bar'     => _x( 'Participant', 'add new on admin bar', 'cgr-fresh' ),
        'add_new'            => _x( 'Add New', 'participant', 'cgr-fresh' ),
        'add_new_item'       => __( 'Add New Participant', 'cgr-fresh' ),
        'new_item'           => __( 'New Participant', 'cgr-fresh' ),
        'edit_item'          => __( 'Edit Participant', 'cgr-fresh' ),
        'view_item'          => __( 'View Participant', 'cgr-fresh' ),
        'all_items'          => __( 'All Participants', 'cgr-fresh' ),
        'search_items'       => __( 'Search Participants', 'cgr-fresh' ),
        'not_found'          => __( 'No participants found.', 'cgr-fresh' ),
        'not_found_in_trash' => __( 'No participants found in Trash.', 'cgr-fresh' ),
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'participant' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest'       => true,
    );
    register_post_type( 'participant', $args );

    // Participant category taxonomy (students, scientists, team etc.)
    $cat_labels = array(
        'name'              => _x( 'Participant Categories', 'taxonomy general name', 'cgr-fresh' ),
        'singular_name'     => _x( 'Participant Category', 'taxonomy singular name', 'cgr-fresh' ),
        'search_items'      => __( 'Search Categories', 'cgr-fresh' ),
        'all_items'         => __( 'All Categories', 'cgr-fresh' ),
        'edit_item'         => __( 'Edit Category', 'cgr-fresh' ),
        'update_item'       => __( 'Update Category', 'cgr-fresh' ),
        'add_new_item'      => __( 'Add New Category', 'cgr-fresh' ),
        'new_item_name'     => __( 'New Category Name', 'cgr-fresh' ),
        'menu_name'         => __( 'Categories', 'cgr-fresh' ),
    );
    $cat_args = array(
        'hierarchical'      => true,
        'labels'            => $cat_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'participant-category' ),
        'show_in_rest'      => true,
    );
    register_taxonomy( 'participant_category', array( 'participant' ), $cat_args );

    // Event name taxonomy (non‑hierarchical: store the name of each event)
    $event_labels = array(
        'name'              => _x( 'Events', 'taxonomy general name', 'cgr-fresh' ),
        'singular_name'     => _x( 'Event', 'taxonomy singular name', 'cgr-fresh' ),
        'search_items'      => __( 'Search Events', 'cgr-fresh' ),
        'all_items'         => __( 'All Events', 'cgr-fresh' ),
        'edit_item'         => __( 'Edit Event', 'cgr-fresh' ),
        'update_item'       => __( 'Update Event', 'cgr-fresh' ),
        'add_new_item'      => __( 'Add New Event', 'cgr-fresh' ),
        'new_item_name'     => __( 'New Event Name', 'cgr-fresh' ),
        'menu_name'         => __( 'Events', 'cgr-fresh' ),
    );
    $event_args = array(
        'hierarchical'      => false,
        'labels'            => $event_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'event' ),
        'show_in_rest'      => true,
    );
    register_taxonomy( 'event_name', array( 'participant' ), $event_args );

    // Event year taxonomy (non‑hierarchical: 2025, 2024 etc.)
    $year_labels = array(
        'name'              => _x( 'Event Years', 'taxonomy general name', 'cgr-fresh' ),
        'singular_name'     => _x( 'Event Year', 'taxonomy singular name', 'cgr-fresh' ),
        'search_items'      => __( 'Search Event Years', 'cgr-fresh' ),
        'all_items'         => __( 'All Event Years', 'cgr-fresh' ),
        'edit_item'         => __( 'Edit Event Year', 'cgr-fresh' ),
        'update_item'       => __( 'Update Event Year', 'cgr-fresh' ),
        'add_new_item'      => __( 'Add New Event Year', 'cgr-fresh' ),
        'new_item_name'     => __( 'New Event Year', 'cgr-fresh' ),
        'menu_name'         => __( 'Event Years', 'cgr-fresh' ),
    );
    $year_args = array(
        'hierarchical'      => false,
        'labels'            => $year_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'event-year' ),
        'show_in_rest'      => true,
    );
    register_taxonomy( 'event_year', array( 'participant' ), $year_args );
}
add_action( 'init', 'cgr_fresh_register_participants' );

/**
 * Register Events CPT
 */
function cgr_register_events_cpt() {
    $labels = array(
        'name'                  => _x( 'Events', 'Post Type General Name', 'cgr-child' ),
        'singular_name'         => _x( 'Event', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'             => __( 'Events', 'cgr-child' ),
        'name_admin_bar'        => __( 'Event', 'cgr-child' ),
        'archives'              => __( 'Event Archives', 'cgr-child' ),
        'attributes'            => __( 'Event Attributes', 'cgr-child' ),
        'parent_item_colon'     => __( 'Parent Event:', 'cgr-child' ),
        'all_items'             => __( 'All Events', 'cgr-child' ),
        'add_new_item'          => __( 'Add New Event', 'cgr-child' ),
        'add_new'               => __( 'Add New', 'cgr-child' ),
        'new_item'              => __( 'New Event', 'cgr-child' ),
        'edit_item'             => __( 'Edit Event', 'cgr-child' ),
        'update_item'           => __( 'Update Event', 'cgr-child' ),
        'view_item'             => __( 'View Event', 'cgr-child' ),
        'view_items'            => __( 'View Events', 'cgr-child' ),
        'search_items'          => __( 'Search Event', 'cgr-child' ),
        'not_found'             => __( 'Not found', 'cgr-child' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'cgr-child' ),
        'featured_image'        => __( 'Featured Image', 'cgr-child' ),
        'set_featured_image'    => __( 'Set featured image', 'cgr-child' ),
        'remove_featured_image' => __( 'Remove featured image', 'cgr-child' ),
        'use_featured_image'    => __( 'Use as featured image', 'cgr-child' ),
        'insert_into_item'      => __( 'Insert into event', 'cgr-child' ),
        'uploaded_to_this_item' => __( 'Uploaded to this event', 'cgr-child' ),
        'items_list'            => __( 'Events list', 'cgr-child' ),
        'items_list_navigation' => __( 'Events list navigation', 'cgr-child' ),
        'filter_items_list'     => __( 'Filter events list', 'cgr-child' ),
    );
    $args = array(
        'label'                 => __( 'Event', 'cgr-child' ),
        'description'           => __( 'Upcoming Events', 'cgr-child' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'taxonomies'            => array( 'category', 'post_tag' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-calendar-alt',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'page',
        'rewrite'               => array( 'slug' => 'event' ),
    );
    register_post_type( 'cgr_event', $args );
}
add_action( 'init', 'cgr_register_events_cpt', 0 );

/**
 * Add Event Date Meta Box
 */
function cgr_add_event_meta_boxes() {
    add_meta_box(
        'cgr_event_date_meta',
        'Event Details',
        'cgr_event_date_meta_callback',
        'cgr_event',
        'side',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cgr_add_event_meta_boxes' );

function cgr_event_date_meta_callback( $post ) {
    wp_nonce_field( 'cgr_event_save_meta', 'cgr_event_meta_nonce' );
    $value = get_post_meta( $post->ID, '_cgr_event_date', true );
    $location = get_post_meta( $post->ID, '_cgr_event_location', true );
    $link = get_post_meta( $post->ID, '_cgr_event_link', true );
    ?>
    <p>
        <label for="cgr_event_date">Event Date:</label><br>
        <input type="date" id="cgr_event_date" name="cgr_event_date" value="<?php echo esc_attr( $value ); ?>" style="width:100%">
    </p>
    <p>
        <label for="cgr_event_location">Location:</label><br>
        <input type="text" id="cgr_event_location" name="cgr_event_location" value="<?php echo esc_attr( $location ); ?>" style="width:100%">
    </p>
    <p>
        <label for="cgr_event_link">Event Link / Map URL:</label><br>
        <input type="url" id="cgr_event_link" name="cgr_event_link" value="<?php echo esc_attr( $link ); ?>" style="width:100%" placeholder="https://...">
    </p>
    <?php
}

function cgr_save_event_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_event_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['cgr_event_meta_nonce'], 'cgr_event_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['cgr_event_date'] ) ) {
        update_post_meta( $post_id, '_cgr_event_date', sanitize_text_field( $_POST['cgr_event_date'] ) );
    }
    if ( isset( $_POST['cgr_event_location'] ) ) {
        update_post_meta( $post_id, '_cgr_event_location', sanitize_text_field( $_POST['cgr_event_location'] ) );
    }
    if ( isset( $_POST['cgr_event_link'] ) ) {
        update_post_meta( $post_id, '_cgr_event_link', esc_url_raw( $_POST['cgr_event_link'] ) );
    }
}
add_action( 'save_post', 'cgr_save_event_meta' );

/**
 * Display Placement Taxonomy
 * Lets editors tag any content with the section where it should surface (home, events, resources, etc).
 */
function cgr_register_display_area_taxonomy() {
    $labels = array(
        'name'          => _x( 'Display Areas', 'taxonomy general name', 'cgr-child' ),
        'singular_name' => _x( 'Display Area', 'taxonomy singular name', 'cgr-child' ),
        'search_items'  => __( 'Search Display Areas', 'cgr-child' ),
        'all_items'     => __( 'All Display Areas', 'cgr-child' ),
        'edit_item'     => __( 'Edit Display Area', 'cgr-child' ),
        'update_item'   => __( 'Update Display Area', 'cgr-child' ),
        'add_new_item'  => __( 'Add New Display Area', 'cgr-child' ),
        'menu_name'     => __( 'Display Areas', 'cgr-child' ),
    );

    register_taxonomy(
        'cgr_display_area',
        array(
            'cgr_event',
            'post',
            'cgr_gallery',
            'earth_leader',
            'earth_scientist',
            'cgr_member',
            'cgr_publication',
            'cgr_testimonial',
        ),
        array(
            'labels'            => $labels,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'display-area' ),
        )
    );
}
add_action( 'init', 'cgr_register_display_area_taxonomy' );

function cgr_seed_display_area_terms() {
    if ( get_option( 'cgr_seed_display_areas_v1' ) ) {
        return;
    }

    $defaults = array(
        'home-page' => 'Home Page',
        'events'    => 'Events',
        'blog'      => 'Blog',
        'gallery'   => 'Gallery',
        'resources' => 'Resources',
        'teams'     => 'Teams',
        'reports'   => 'Reports',
        'Appreciations' => 'Appreciations',
    );

    foreach ( $defaults as $slug => $name ) {
        if ( ! term_exists( $name, 'cgr_display_area' ) ) {
            wp_insert_term( $name, 'cgr_display_area', array( 'slug' => $slug ) );
        }
    }

    update_option( 'cgr_seed_display_areas_v1', true );
}
add_action( 'init', 'cgr_seed_display_area_terms' );

/**
 * Galleries CPT
 * Provides a managed gallery content type next to Events.
 */
function cgr_register_gallery_cpt() {
    $labels = array(
        'name'               => _x( 'Galleries', 'Post Type General Name', 'cgr-child' ),
        'singular_name'      => _x( 'Gallery', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'          => __( 'Galleries', 'cgr-child' ),
        'name_admin_bar'     => __( 'Gallery', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Gallery', 'cgr-child' ),
        'add_new'            => __( 'Add New', 'cgr-child' ),
        'new_item'           => __( 'New Gallery', 'cgr-child' ),
        'edit_item'          => __( 'Edit Gallery', 'cgr-child' ),
        'view_item'          => __( 'View Gallery', 'cgr-child' ),
        'all_items'          => __( 'All Galleries', 'cgr-child' ),
        'search_items'       => __( 'Search Galleries', 'cgr-child' ),
        'not_found'          => __( 'No galleries found.', 'cgr-child' ),
        'not_found_in_trash' => __( 'No galleries found in Trash.', 'cgr-child' ),
    );

    $args = array(
        'label'               => __( 'Gallery', 'cgr-child' ),
        'description'         => __( 'Photo & video galleries', 'cgr-child' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'taxonomies'          => array( 'cgr_display_area' ),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 6,
        'menu_icon'           => 'dashicons-format-gallery',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,
        'rewrite'             => array( 'slug' => 'gallery' ),
    );

    register_post_type( 'cgr_gallery', $args );
}
add_action( 'init', 'cgr_register_gallery_cpt' );

/**
 * Load Gallery Metabox.
 */
require_once CGR_CHILD_DIR . '/includes/admin/gallery-metabox.php';

/**
 * Publications (Magazines, Newsletters, Annual Reports)
 */
function cgr_register_publication_cpt() {
    $labels = array(
        'name'               => _x( 'Publications', 'Post Type General Name', 'cgr-child' ),
        'singular_name'      => _x( 'Publication', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'          => __( 'Publications', 'cgr-child' ),
        'name_admin_bar'     => __( 'Publication', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Publication', 'cgr-child' ),
        'all_items'          => __( 'All Publications', 'cgr-child' ),
    );

    $args = array(
        'label'              => __( 'Publication', 'cgr-child' ),
        'description'        => __( 'Magazines, Newsletters, Annual Reports', 'cgr-child' ),
        'labels'             => $labels,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'taxonomies'         => array( 'publication_type', 'cgr_display_area' ),
        'hierarchical'       => false,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 7,
        'menu_icon'          => 'dashicons-media-document',
        'show_in_admin_bar'  => true,
        'show_in_nav_menus'  => true,
        'can_export'         => true,
        'has_archive'        => true,
        'exclude_from_search'=> false,
        'publicly_queryable' => true,
        'capability_type'    => 'post',
        'show_in_rest'       => true,
    );

    register_post_type( 'cgr_publication', $args );
}
add_action( 'init', 'cgr_register_publication_cpt' );

function cgr_register_publication_taxonomy() {
    $labels = array(
        'name'              => _x( 'Publication Types', 'taxonomy general name', 'cgr-child' ),
        'singular_name'     => _x( 'Publication Type', 'taxonomy singular name', 'cgr-child' ),
        'search_items'      => __( 'Search Publication Types', 'cgr-child' ),
        'all_items'         => __( 'All Publication Types', 'cgr-child' ),
        'edit_item'         => __( 'Edit Publication Type', 'cgr-child' ),
        'update_item'       => __( 'Update Publication Type', 'cgr-child' ),
        'add_new_item'      => __( 'Add New Publication Type', 'cgr-child' ),
        'menu_name'         => __( 'Publication Types', 'cgr-child' ),
    );

    register_taxonomy(
        'publication_type',
        array( 'cgr_publication' ),
        array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'publication-type' ),
        )
    );
}
add_action( 'init', 'cgr_register_publication_taxonomy' );

function cgr_seed_publication_terms() {
    if ( get_option( 'cgr_seed_publication_terms_v1' ) ) {
        return;
    }
    $defaults = array(
        'pudami-magazine' => 'PDF Magazine (Pudami)',
        'newsletter'      => 'PDF Newsletter',
        'annual-report'   => 'PDF Annual Report',
    );
    foreach ( $defaults as $slug => $name ) {
        if ( ! term_exists( $name, 'publication_type' ) ) {
            wp_insert_term( $name, 'publication_type', array( 'slug' => $slug ) );
        }
    }
    update_option( 'cgr_seed_publication_terms_v1', true );
}
add_action( 'init', 'cgr_seed_publication_terms' );

function cgr_add_publication_meta_boxes() {
    add_meta_box(
        'cgr_publication_file',
        __( 'Document Details', 'cgr-child' ),
        'cgr_publication_meta_box_callback',
        'cgr_publication',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cgr_add_publication_meta_boxes' );

function cgr_publication_meta_box_callback( $post ) {
    wp_nonce_field( 'cgr_publication_save_meta', 'cgr_publication_meta_nonce' );
    $file = get_post_meta( $post->ID, '_cgr_publication_file', true );
    $year = get_post_meta( $post->ID, '_cgr_publication_year', true );
    ?>
    <p>
        <label for="cgr_publication_file"><strong><?php esc_html_e( 'PDF URL or Media ID', 'cgr-child' ); ?></strong></label><br>
        <input type="text" id="cgr_publication_file" name="cgr_publication_file" class="widefat" value="<?php echo esc_attr( $file ); ?>" placeholder="https://... or attachment ID">
    </p>
    <p>
        <label for="cgr_publication_year"><strong><?php esc_html_e( 'Year', 'cgr-child' ); ?></strong></label><br>
        <input type="text" id="cgr_publication_year" name="cgr_publication_year" class="widefat" value="<?php echo esc_attr( $year ); ?>" placeholder="2024">
    </p>
    <?php
}

function cgr_save_publication_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_publication_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['cgr_publication_meta_nonce'], 'cgr_publication_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['cgr_publication_file'] ) ) {
        update_post_meta( $post_id, '_cgr_publication_file', sanitize_text_field( $_POST['cgr_publication_file'] ) );
    }
    if ( isset( $_POST['cgr_publication_year'] ) ) {
        update_post_meta( $post_id, '_cgr_publication_year', sanitize_text_field( $_POST['cgr_publication_year'] ) );
    }
}
add_action( 'save_post', 'cgr_save_publication_meta' );

/**
 * Appreciations CPT
 */
function cgr_register_testimonial_cpt() {
    $labels = array(
        'name'               => _x( 'Appreciations', 'Post Type General Name', 'cgr-child' ),
        'singular_name'      => _x( 'Testimonial', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'          => __( 'Appreciations', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Testimonial', 'cgr-child' ),
        'all_items'          => __( 'All Appreciations', 'cgr-child' ),
    );

    $args = array(
        'label'              => __( 'Testimonial', 'cgr-child' ),
        'description'        => __( 'Appreciations with photos', 'cgr-child' ),
        'labels'             => $labels,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'taxonomies'         => array( 'cgr_display_area' ),
        'hierarchical'       => false,
        'public'             => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 8,
        'menu_icon'          => 'dashicons-format-quote',
        'show_in_admin_bar'  => true,
        'show_in_nav_menus'  => true,
        'can_export'         => true,
        'has_archive'        => 'testimonial',
        'exclude_from_search'=> false,
        'publicly_queryable' => true,
        'capability_type'    => 'post',
        'show_in_rest'       => true,
        'rewrite'            => array(
            'slug'       => 'testimonial',
            'with_front' => false,
        ),
    );

    register_post_type( 'cgr_testimonial', $args );
}
add_action( 'init', 'cgr_register_testimonial_cpt' );

function cgr_add_testimonial_meta_boxes() {
    add_meta_box(
        'cgr_testimonial_details',
        __( 'Person Details', 'cgr-child' ),
        'cgr_testimonial_meta_box_callback',
        'cgr_testimonial',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cgr_add_testimonial_meta_boxes' );

function cgr_testimonial_meta_box_callback( $post ) {
    wp_nonce_field( 'cgr_testimonial_save_meta', 'cgr_testimonial_meta_nonce' );
    $role = get_post_meta( $post->ID, '_cgr_testimonial_role', true );
    $organization = get_post_meta( $post->ID, '_cgr_testimonial_org', true );
    ?>
    <p>
        <label for="cgr_testimonial_role"><strong><?php esc_html_e( 'Role / Title', 'cgr-child' ); ?></strong></label><br>
        <input type="text" id="cgr_testimonial_role" name="cgr_testimonial_role" class="widefat" value="<?php echo esc_attr( $role ); ?>" placeholder="Parent, Volunteer, Teacher">
    </p>
    <p>
        <label for="cgr_testimonial_org"><strong><?php esc_html_e( 'Organization', 'cgr-child' ); ?></strong></label><br>
        <input type="text" id="cgr_testimonial_org" name="cgr_testimonial_org" class="widefat" value="<?php echo esc_attr( $organization ); ?>" placeholder="School / Company">
    </p>
    <?php
}

function cgr_save_testimonial_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_testimonial_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['cgr_testimonial_meta_nonce'], 'cgr_testimonial_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['cgr_testimonial_role'] ) ) {
        update_post_meta( $post_id, '_cgr_testimonial_role', sanitize_text_field( $_POST['cgr_testimonial_role'] ) );
    }
    if ( isset( $_POST['cgr_testimonial_org'] ) ) {
        update_post_meta( $post_id, '_cgr_testimonial_org', sanitize_text_field( $_POST['cgr_testimonial_org'] ) );
    }
}
add_action( 'save_post', 'cgr_save_testimonial_meta' );

// One-time flush to apply new testimonial slug/archive if missing.
add_action( 'init', function() {
    if ( ! get_option( 'cgr_testimonial_rewrite_flushed_v1' ) ) {
        flush_rewrite_rules();
        update_option( 'cgr_testimonial_rewrite_flushed_v1', true );
    }
}, 20 );

/**
 * Press Coverage CPT (Media Coverage)
 */
function cgr_register_press_coverage_cpt() {
    $labels = array(
        'name'               => _x( 'Press Coverage', 'Post Type General Name', 'cgr-child' ),
        'singular_name'      => _x( 'Press Item', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'          => __( 'Press Coverage', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Coverage', 'cgr-child' ),
        'all_items'          => __( 'All Coverage', 'cgr-child' ),
    );

    $args = array(
        'label'               => __( 'Press Coverage', 'cgr-child' ),
        'description'         => __( 'Media coverage and press mentions', 'cgr-child' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 21,
        'menu_icon'           => 'dashicons-media-text',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => 'media-coverage',
        'publicly_queryable'  => true,
        'exclude_from_search' => false,
        'rewrite'             => array( 'slug' => 'media-coverage', 'with_front' => false ),
        'show_in_rest'        => true,
    );
    register_post_type( 'cgr_press_coverage', $args );
}
add_action( 'init', 'cgr_register_press_coverage_cpt' );

function cgr_press_coverage_meta_boxes() {
    add_meta_box(
        'cgr_press_details',
        __( 'Coverage Details', 'cgr-child' ),
        'cgr_press_coverage_meta_box',
        'cgr_press_coverage',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cgr_press_coverage_meta_boxes' );

function cgr_press_coverage_meta_box( $post ) {
    wp_nonce_field( 'cgr_press_meta_nonce', 'cgr_press_meta_nonce_field' );
    $pub_name  = get_post_meta( $post->ID, '_cgr_press_publication', true );
    $pub_date  = get_post_meta( $post->ID, '_cgr_press_date', true );
    $pub_url   = get_post_meta( $post->ID, '_cgr_press_url', true );
    ?>
    <p>
        <label for="cgr_press_publication"><strong><?php esc_html_e( 'Publication', 'cgr-child' ); ?></strong></label><br>
        <input type="text" id="cgr_press_publication" name="cgr_press_publication" class="widefat" value="<?php echo esc_attr( $pub_name ); ?>" placeholder="Newspaper / Channel / Portal">
    </p>
    <p>
        <label for="cgr_press_date"><strong><?php esc_html_e( 'Date', 'cgr-child' ); ?></strong></label><br>
        <input type="date" id="cgr_press_date" name="cgr_press_date" class="widefat" value="<?php echo esc_attr( $pub_date ); ?>">
    </p>
    <p>
        <label for="cgr_press_url"><strong><?php esc_html_e( 'Link (optional)', 'cgr-child' ); ?></strong></label><br>
        <input type="url" id="cgr_press_url" name="cgr_press_url" class="widefat" value="<?php echo esc_attr( $pub_url ); ?>" placeholder="https://example.com/story">
    </p>
    <?php
}

function cgr_save_press_coverage_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_press_meta_nonce_field'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['cgr_press_meta_nonce_field'], 'cgr_press_meta_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['cgr_press_publication'] ) ) {
        update_post_meta( $post_id, '_cgr_press_publication', sanitize_text_field( $_POST['cgr_press_publication'] ) );
    }
    if ( isset( $_POST['cgr_press_date'] ) ) {
        update_post_meta( $post_id, '_cgr_press_date', sanitize_text_field( $_POST['cgr_press_date'] ) );
    }
    if ( isset( $_POST['cgr_press_url'] ) ) {
        update_post_meta( $post_id, '_cgr_press_url', esc_url_raw( $_POST['cgr_press_url'] ) );
    }
}
add_action( 'save_post', 'cgr_save_press_coverage_meta' );

// One-time flush for press coverage rewrite.
add_action( 'init', function() {
    if ( ! get_option( 'cgr_press_coverage_rewrite_flushed_v1' ) ) {
        flush_rewrite_rules();
        update_option( 'cgr_press_coverage_rewrite_flushed_v1', true );
    }
}, 25 );

/**
 * AJAX: Filter Appreciations (search + sort).
 */
function cgr_ajax_filter_Appreciations() {
    $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    $sort   = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'newest';

    $orderby = 'date';
    $order   = 'DESC';
    $meta_key = '';

    if ( $sort === 'oldest' ) {
        $order = 'ASC';
    } elseif ( $sort === 'az' ) {
        $orderby = 'title';
        $order   = 'ASC';
    } elseif ( $sort === 'za' ) {
        $orderby = 'title';
        $order   = 'DESC';
    }

    $query_args = array(
        'post_type'      => 'cgr_testimonial',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
        's'              => $search,
        'orderby'        => $orderby,
        'order'          => $order,
    );

    $results = new WP_Query( $query_args );

    ob_start();
    if ( $results->have_posts() ) :
        $colors = array('#0b4f6c', '#2e6b3f', '#d97757', '#ff7a59', '#146c94', '#5b3cc4');
        $i = 0;
        while ( $results->have_posts() ) : $results->the_post();
            $role = get_post_meta( get_the_ID(), '_cgr_testimonial_role', true );
            $org  = get_post_meta( get_the_ID(), '_cgr_testimonial_org', true );
            $avatar = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            if ( ! $avatar ) {
                $avatar = get_stylesheet_directory_uri() . '/images/hero-placeholder.jpg';
            }
            $quote = get_the_excerpt();
            if ( empty( $quote ) ) {
                $quote = wp_trim_words( wp_strip_all_tags( get_the_content() ), 30, '...' );
            }
            $accent = $colors[ $i % count( $colors ) ];
            $i++;
            ?>
            <article class="testimonial-card" style="--testimonial-accent: <?php echo esc_attr( $accent ); ?>;">
                <div class="testimonial-quote">“<?php echo esc_html( $quote ); ?>”</div>
                <div class="testimonial-meta">
                    <div class="testimonial-avatar">
                        <img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">
                    </div>
                    <div class="testimonial-person">
                        <div class="testimonial-name"><?php the_title(); ?></div>
                        <?php if ( $role || $org ) : ?>
                            <div class="testimonial-role">
                                <?php echo esc_html( $role ); ?>
                                <?php if ( $role && $org ) : ?><span class="testimonial-sep">&middot;</span><?php endif; ?>
                                <?php echo esc_html( $org ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
    else :
        ?>
        <div class="Appreciations-empty">No Appreciations found.</div>
        <?php
    endif;

    wp_send_json_success( array( 'html' => ob_get_clean() ) );
}
add_action( 'wp_ajax_cgr_filter_Appreciations', 'cgr_ajax_filter_Appreciations' );
add_action( 'wp_ajax_nopriv_cgr_filter_Appreciations', 'cgr_ajax_filter_Appreciations' );

/**
 * Activity Log (Admin Dashboard)
 * Records create/update/delete events for posts and exposes a dashboard page.
 */
function cgr_register_activity_log_cpt() {
    register_post_type( 'cgr_activity_log', array(
        'labels' => array(
            'name'          => __( 'Activity Logs', 'cgr-child' ),
            'singular_name' => __( 'Activity Log', 'cgr-child' ),
        ),
        'public'              => false,
        'show_ui'             => false, // We render a custom admin page instead.
        'show_in_menu'        => false,
        'supports'            => array( 'title' ),
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'rewrite'             => false,
    ) );
}
add_action( 'init', 'cgr_register_activity_log_cpt' );

/**
 * Helper: insert a log entry.
 */
function cgr_record_activity_log( $action, $object_id, $object_type, $extra = array() ) {
    // Avoid recursion and unwanted noise.
    if ( $object_type === 'cgr_activity_log' || defined( 'CGR_DISABLE_ACTIVITY_LOG' ) ) {
        return;
    }

    $user_id   = get_current_user_id();
    $user      = $user_id ? get_userdata( $user_id ) : null;
    $user_name = $user ? $user->display_name : __( 'System', 'cgr-child' );

    $object_title = '';
    if ( $object_type && $object_id ) {
        $maybe_post = get_post( $object_id );
        if ( $maybe_post ) {
            $object_title = $maybe_post->post_title;
        }
    }
    if ( empty( $object_title ) && isset( $extra['title'] ) ) {
        $object_title = $extra['title'];
    }

    $message = trim( sprintf(
        '%s %s %s %s',
        $user_name,
        $action,
        $object_type,
        $object_title ? '"' . wp_strip_all_tags( $object_title ) . '"' : ''
    ) );

    wp_insert_post( array(
        'post_type'   => 'cgr_activity_log',
        'post_title'  => $message,
        'post_status' => 'private',
        'meta_input'  => array(
            'actor_id'      => $user_id,
            'actor_name'    => $user_name,
            'action'        => $action,
            'object_id'     => $object_id,
            'object_type'   => $object_type,
            'object_title'  => $object_title,
        ),
    ) );
}

/**
 * Log create/update events.
 */
function cgr_activity_log_save_post( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( $post->post_type === 'cgr_activity_log' ) {
        return;
    }
    if ( in_array( $post->post_status, array( 'auto-draft', 'inherit' ), true ) ) {
        return;
    }

    $action = $update ? 'updated' : 'created';
    cgr_record_activity_log( $action, $post_id, $post->post_type, array( 'title' => $post->post_title ) );
}
add_action( 'save_post', 'cgr_activity_log_save_post', 10, 3 );

/**
 * Log trash and delete events.
 */
function cgr_activity_log_trashed_post( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type === 'cgr_activity_log' ) {
        return;
    }
    cgr_record_activity_log( 'trashed', $post_id, $post->post_type, array( 'title' => $post->post_title ) );
}
add_action( 'trashed_post', 'cgr_activity_log_trashed_post' );

function cgr_activity_log_deleted_post( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type === 'cgr_activity_log' ) {
        return;
    }
    // Skip if this delete is just moving to trash (handled above).
    if ( $post->post_status === 'trash' ) {
        return;
    }
    cgr_record_activity_log( 'deleted', $post_id, $post->post_type, array( 'title' => $post->post_title ) );
}
add_action( 'before_delete_post', 'cgr_activity_log_deleted_post' );

/**
 * Admin menu page to view the log.
 */
function cgr_register_activity_log_menu() {
    add_menu_page(
        __( 'Activity Log', 'cgr-child' ),
        __( 'Activity Log', 'cgr-child' ),
        'manage_options',
        'cgr-activity-log',
        'cgr_render_activity_log_page',
        'dashicons-list-view',
        59
    );
}
add_action( 'admin_menu', 'cgr_register_activity_log_menu' );

function cgr_render_activity_log_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $logs = new WP_Query( array(
        'post_type'      => 'cgr_activity_log',
        'posts_per_page' => 50,
        'post_status'    => array( 'private' ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Activity Log', 'cgr-child' ); ?></h1>
        <p>Recent user actions across posts, pages, and custom types.</p>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:180px;">When</th>
                    <th style="width:160px;">User</th>
                    <th>Action</th>
                    <th style="width:180px;">Target</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $logs->have_posts() ) : ?>
                    <?php while ( $logs->have_posts() ) : $logs->the_post(); ?>
                        <?php
                            $action       = get_post_meta( get_the_ID(), 'action', true );
                            $actor_name   = get_post_meta( get_the_ID(), 'actor_name', true );
                            $object_type  = get_post_meta( get_the_ID(), 'object_type', true );
                            $object_title = get_post_meta( get_the_ID(), 'object_title', true );
                            $time_diff    = human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $time_diff . ' ago' ); ?></td>
                            <td><?php echo esc_html( $actor_name ?: 'System' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $action ) ); ?></td>
                            <td>
                                <strong><?php echo esc_html( $object_type ); ?></strong>
                                <?php if ( $object_title ) : ?>
                                    <div style="color:#555;"><?php echo esc_html( $object_title ); ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr><td colspan="4">No activity recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Sync participants from Google Sheets to WordPress.
 *
 * This function demonstrates how you might pull rows from a Google Sheet
 * and create or update Participant posts. To use it:
 * 1. Set up a Google Cloud project and enable the Sheets API.
 * 2. Create a service account and download the JSON key file.
 * 3. Save your sheet ID and credentials path in WordPress options or
 *    constants and load the Google API client library via Composer.
 * 4. Replace the TODO sections below with real API calls.
 */
function cgr_sync_participants_from_google_sheets() {
    // TODO: Replace these options with your own stored values.
    $sheet_id    = get_option( 'cgr_google_sheet_id', '' );
    $credentials = get_option( 'cgr_google_credentials_path', '' );
    if ( empty( $sheet_id ) || empty( $credentials ) ) {
        return;
    }

    // TODO: Require the Google API client.  Ensure the library is installed
    // via Composer or another method.
    /*
    require_once __DIR__ . '/vendor/autoload.php';
    $client = new Google_Client();
    $client->setApplicationName( 'CGR Participants Sync' );
    $client->setScopes( [ Google_Service_Sheets::SPREADSHEETS ] );
    $client->setAuthConfig( $credentials );
    $service = new Google_Service_Sheets( $client );
    $range   = 'Sheet1!A2:E';
    $response = $service->spreadsheets_values->get( $sheet_id, $range );
    $rows    = $response->getValues();
    */

    // Example dataset for demonstration. Replace this with real rows from Sheets.
    $rows = array(
        array( 'Student Name', 'Student', 'COP30 Daily Review', '2025', 'School Name' ),
        array( 'Researcher X', 'Scientist', 'Geo Spirit Workshop', '2024', 'University' ),
        array( 'Team Member', 'CGR Team', 'Green Village Project', '2023', 'CGR Staff' ),
    );

    foreach ( $rows as $row ) {
        // Expecting columns: Name | Category | Event Name | Year | Affiliation
        list( $name, $category, $event, $year, $affiliation ) = $row;

        // Create or update a participant post based on name and year
        $existing = get_posts( array(
            'post_type'  => 'participant',
            'title'      => $name,
            'tax_query'  => array(
                array(
                    'taxonomy' => 'event_year',
                    'field'    => 'slug',
                    'terms'    => sanitize_title( $year ),
                ),
            ),
            'posts_per_page' => 1,
        ) );
        if ( $existing ) {
            $post_id = $existing[0]->ID;
        } else {
            $post_id = wp_insert_post( array(
                'post_title'   => $name,
                'post_type'    => 'participant',
                'post_status'  => 'publish',
                'post_content' => '',
            ) );
        }
        if ( ! $post_id ) {
            continue;
        }
        // Assign taxonomy terms
        wp_set_object_terms( $post_id, sanitize_title( $category ), 'participant_category', false );
        wp_set_object_terms( $post_id, sanitize_title( $event ), 'event_name', false );
        wp_set_object_terms( $post_id, sanitize_title( $year ), 'event_year', false );
        // Update meta fields
        update_post_meta( $post_id, 'cgr_affiliation', sanitize_text_field( $affiliation ) );
    }
}

/**
 * Sync participants from WordPress to Google Sheets.
 *
 * Exports participant posts to a specified Google Sheet.  Useful
 * when your site is the primary editing interface and you want to
 * update a master sheet.  Similar to the import function, you
 * must authenticate using the Google Client.  This function pulls
 * participant posts and writes them to the sheet row by row.
 */
function cgr_sync_participants_to_google_sheets() {
    $sheet_id    = get_option( 'cgr_google_sheet_id', '' );
    $credentials = get_option( 'cgr_google_credentials_path', '' );
    if ( empty( $sheet_id ) || empty( $credentials ) ) {
        return;
    }
    // TODO: Load the Google API client and fetch existing data if necessary.
    // Example pseudocode:
    /*
    require_once __DIR__ . '/vendor/autoload.php';
    $client = new Google_Client();
    $client->setApplicationName( 'CGR Participants Export' );
    $client->setScopes( [ Google_Service_Sheets::SPREADSHEETS ] );
    $client->setAuthConfig( $credentials );
    $service = new Google_Service_Sheets( $client );
    $range   = 'Sheet1!A2:E';
    */

    // Gather participants from WordPress
    $participants = get_posts( array(
        'post_type'   => 'participant',
        'numberposts' => -1,
    ) );
    $values = array();
    foreach ( $participants as $participant ) {
        $categories = wp_get_post_terms( $participant->ID, 'participant_category', array( 'fields' => 'names' ) );
        $events     = wp_get_post_terms( $participant->ID, 'event_name', array( 'fields' => 'names' ) );
        $years      = wp_get_post_terms( $participant->ID, 'event_year', array( 'fields' => 'names' ) );
        $affiliation = get_post_meta( $participant->ID, 'cgr_affiliation', true );
        $values[] = array(
            $participant->post_title,
            implode( ', ', $categories ),
            implode( ', ', $events ),
            implode( ', ', $years ),
            $affiliation,
        );
    }
    // TODO: Write $values to the Google Sheet using the Sheets API
    // $body = new Google_Service_Sheets_ValueRange([ 'values' => $values ]);
    // $params = [ 'valueInputOption' => 'RAW' ];
    // $service->spreadsheets_values->update( $sheet_id, 'Sheet1!A2', $body, $params );
}

/**
 * Register a shortcode that outputs the custom home page. Place
 * [cgr_home_page] inside a WordPress page to render the assembled
 * sections.  Each section is loaded from the `sections/` directory.
 */
function cgr_fresh_home_page_shortcode() {
    return ''; // Disabled
    /*
    ob_start();
    $template = get_stylesheet_directory() . '/pages/home-page.php';
    if ( file_exists( $template ) ) {
        include $template;
    }
    return ob_get_clean();
    */
}
add_shortcode( 'cgr_home_page', 'cgr_fresh_home_page_shortcode' );

/**
 * Create initial pages on theme activation.
 *
 * This function runs once when the theme is switched on. It creates the
 * main pages for the site if they do not already exist.
 */
function cgr_create_initial_pages() {
    // 1. Top Level Pages
    $parents = array(
        'home'         => 'Home',
        'about-us'     => 'About Us',
        'programs'     => 'Programs',
        'impact'       => 'Impact',
        'resources'    => 'Resources',
        'pudami'       => 'Pudami',
        'get-involved' => 'Get Involved',
        'contact'      => 'Contact',
        'blog'         => 'Blog',
    );

    $parent_ids = array();

    foreach ( $parents as $slug => $title ) {
        $page = get_page_by_path( $slug );
        if ( ! $page ) {
            $page_id = wp_insert_post( array(
                'post_type'    => 'page',
                'post_title'   => $title,
                'post_content' => '<!-- wp:paragraph --><p>Welcome to the ' . $title . ' page.</p><!-- /wp:paragraph -->',
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_slug'    => $slug,
            ) );
            $parent_ids[$slug] = $page_id;

            if ( 'home' === $slug ) {
                update_option( 'page_on_front', $page_id );
                update_option( 'show_on_front', 'page' );
            }
            if ( 'blog' === $slug ) {
                update_option( 'page_for_posts', $page_id );
            }
        } else {
            $parent_ids[$slug] = $page->ID;
        }
    }

    // 2. Child Pages
    $children = array(
        // About Us Children
        'vision-mission'       => array( 'title' => 'Vision | Mission', 'parent' => 'about-us' ),
        'objectives'           => array( 'title' => 'Objectives', 'parent' => 'about-us' ),
        'earth-centre'         => array( 'title' => 'Earth Centre', 'parent' => 'about-us' ),
        'partners'             => array( 'title' => 'Partners', 'parent' => 'about-us' ),
        'educational-partners' => array( 'title' => 'Educational Partners', 'parent' => 'about-us' ),
        'collaborations'       => array( 'title' => 'Collaborations', 'parent' => 'about-us' ),
        'our-team'             => array( 'title' => 'CGR Team', 'parent' => 'about-us' ),

        // Resources Children
        'annual-reports'       => array( 'title' => 'Annual Reports', 'parent' => 'resources' ),
        'pdf-magazines'        => array( 'title' => 'PDF Magazines', 'parent' => 'resources' ),
        'brochures'            => array( 'title' => 'Brochures', 'parent' => 'resources' ),
        'media-coverage'       => array( 'title' => 'Media Coverage', 'parent' => 'resources' ),
        'gallery'              => array( 'title' => 'Gallery', 'parent' => 'resources' ),

        // Impact Children
        'awards'               => array( 'title' => 'Awards', 'parent' => 'impact' ),
        'appreciations'        => array( 'title' => 'Appreciations', 'parent' => 'impact' ),

        // Get Involved Children
        'appeal-to-investors'  => array( 'title' => 'Appeal To Investors', 'parent' => 'get-involved' ),
        'my-account'           => array( 'title' => 'My Account', 'parent' => 'get-involved' ),

        // Programs Children
        'green-mission'        => array( 'title' => 'Green Mission Programmes', 'parent' => 'programs' ),
        'events'               => array( 'title' => 'Events', 'parent' => 'programs' ),
        'policy-advocacy'      => array( 'title' => 'Policy Advocacy', 'parent' => 'programs' ),
    );

    foreach ( $children as $slug => $info ) {
        // Check if page exists (check path with parent slug)
        $parent_slug = $info['parent'];
        $full_path = $parent_slug . '/' . $slug;
        
        if ( ! get_page_by_path( $full_path ) && ! get_page_by_path( $slug ) ) {
            wp_insert_post( array(
                'post_type'    => 'page',
                'post_title'   => $info['title'],
                'post_content' => '<!-- wp:paragraph --><p>Content for ' . $info['title'] . ' goes here.</p><!-- /wp:paragraph -->',
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_slug'    => $slug,
                'post_parent'  => isset($parent_ids[$parent_slug]) ? $parent_ids[$parent_slug] : 0,
            ) );
        }
    }
}
add_action( 'after_switch_theme', 'cgr_create_initial_pages' );

// Ensure About page uses child template.
add_action('init', function() {
    $about = get_page_by_path('about-us');
    if ( $about && get_post_meta( $about->ID, '_wp_page_template', true ) !== 'pages/page-about.php' ) {
        update_post_meta( $about->ID, '_wp_page_template', 'pages/page-about.php' );
    }
});

// Trigger page creation via URL
if ( isset( $_GET['cgr_create_pages'] ) ) {
    add_action( 'init', 'cgr_create_initial_pages' );
}

/**
 * Custom Rewrite Rules for Virtual Pages
 * 
 * Handles loading custom templates without database pages.
 */
function cgr_register_virtual_pages() {
    // Top Level
    add_rewrite_rule('^programs/?$', 'index.php?cgr_virtual_page=programs', 'top');
    add_rewrite_rule('^impact/?$', 'index.php?cgr_virtual_page=impact', 'top');
    add_rewrite_rule('^resources/?$', 'index.php?cgr_virtual_page=resources', 'top');
    add_rewrite_rule('^get-involved/?$', 'index.php?cgr_virtual_page=get-involved', 'top');
    add_rewrite_rule('^contact/?$', 'index.php?cgr_virtual_page=contact', 'top');
    
    // About Us Subpages
    add_rewrite_rule('^about-us/vision-mission/?$', 'index.php?cgr_virtual_page=vision-mission', 'top');
    add_rewrite_rule('^about-us/objectives/?$', 'index.php?cgr_virtual_page=objectives', 'top');
    add_rewrite_rule('^about-us/earth-centre/?$', 'index.php?cgr_virtual_page=earth-centre', 'top');
    add_rewrite_rule('^about-us/partners/?$', 'index.php?cgr_virtual_page=partners', 'top');
    add_rewrite_rule('^about-us/educational-partners/?$', 'index.php?cgr_virtual_page=educational-partners', 'top');
    add_rewrite_rule('^about-us/collaborations/?$', 'index.php?cgr_virtual_page=collaborations', 'top');

    // Programs Subpages
    add_rewrite_rule('^programs/green-mission/?$', 'index.php?cgr_virtual_page=green-mission', 'top');
    add_rewrite_rule('^programs/events/?$', 'index.php?cgr_virtual_page=events', 'top');
    add_rewrite_rule('^programs/policy-advocacy/?$', 'index.php?cgr_virtual_page=policy-advocacy', 'top');

    // Resources Subpages
    add_rewrite_rule('^resources/annual-reports/?$', 'index.php?cgr_virtual_page=annual-reports', 'top');
    add_rewrite_rule('^resources/pdf-magazines/?$', 'index.php?cgr_virtual_page=pdf-magazines', 'top');
    add_rewrite_rule('^resources/brochures/?$', 'index.php?cgr_virtual_page=brochures', 'top');
    add_rewrite_rule('^resources/media-coverage/?$', 'index.php?cgr_virtual_page=media-coverage', 'top');
    add_rewrite_rule('^pudami/?$', 'index.php?cgr_virtual_page=pudami', 'top');

    // Impact Subpages
    add_rewrite_rule('^impact/awards/?$', 'index.php?cgr_virtual_page=awards', 'top');
    add_rewrite_rule('^impact/appreciations/?$', 'index.php?cgr_virtual_page=appreciations', 'top');

    // Get Involved Subpages
    add_rewrite_rule('^get-involved/appeal-to-investors/?$', 'index.php?cgr_virtual_page=appeal-to-investors', 'top');
    add_rewrite_rule('^get-involved/my-account/?$', 'index.php?cgr_virtual_page=my-account', 'top');

    // Careers
    add_rewrite_rule('^careers/?$', 'index.php?cgr_virtual_page=careers', 'top');
    
    // Gallery - Disabled to allow Elementor editing
    // add_rewrite_rule('^gallery/?$', 'index.php?cgr_virtual_page=gallery', 'top');
}
// add_action('init', 'cgr_register_virtual_pages');

/**
 * Add Custom Columns to Gallery List
 */
add_filter( 'manage_cgr_gallery_posts_columns', 'cgr_gallery_columns' );
function cgr_gallery_columns( $columns ) {
    $new_columns = array();
    foreach( $columns as $key => $value ) {
        if ( 'title' === $key ) {
            $new_columns[$key] = $value;
            $new_columns['thumb'] = __( 'Preview', 'cgr-child' );
        } elseif ( 'date' === $key ) {
            $new_columns['shortcode'] = __( 'Shortcode', 'cgr-child' );
            $new_columns['share_link'] = __( 'Share Link', 'cgr-child' );
            $new_columns[$key] = $value;
        } else {
            $new_columns[$key] = $value;
        }
    }
    return $new_columns;
}

add_action( 'manage_cgr_gallery_posts_custom_column', 'cgr_gallery_custom_column', 10, 2 );
function cgr_gallery_custom_column( $column, $post_id ) {
    if ( 'shortcode' === $column ) {
        echo '<code style="display:block;margin-bottom:5px;">[cgr_gallery id="' . $post_id . '"]</code>';
        echo '<code style="display:block;">[cgr_gallery_grid]</code>';
    }
    if ( 'share_link' === $column ) {
        $url = get_permalink( $post_id );
        echo '<input type="text" readonly value="' . esc_url( $url ) . '" style="width:100%;background:#f9f9f9;border:1px solid #ddd;padding:2px 5px;" onclick="this.select();">';
        echo '<a href="' . esc_url( $url ) . '" target="_blank" class="button button-small" style="margin-top:5px;">View Gallery</a>';
    }
    if ( 'thumb' === $column ) {
        $assets = cgr_get_gallery_assets( $post_id );
        if ( ! empty( $assets ) ) {
            $first = $assets[0];
            $thumb = $first['thumb'] ? $first['thumb'] : $first['url'];
            echo '<div style="width:60px;height:60px;background:#eee;border-radius:4px;overflow:hidden;border:1px solid #ddd;">';
            echo '<img src="' . esc_url( $thumb ) . '" style="width:100%;height:100%;object-fit:cover;">';
            echo '</div>';
            if ( count( $assets ) > 1 ) {
                echo '<div style="font-size:10px;color:#666;margin-top:3px;">+' . (count($assets)-1) . ' more</div>';
            }
        } else {
            echo '<span style="color:#ccc;">No images</span>';
        }
    }
}

function cgr_gallery_collect_asset( $assets, $url, $thumb, $title ) {
    if ( ! $url ) {
        return $assets;
    }

    $url = esc_url_raw( $url );

    foreach ( $assets as $asset ) {
        if ( isset( $asset['url'] ) && $asset['url'] === $url ) {
            return $assets;
        }
    }

    $assets[] = array(
        'url'   => $url,
        'thumb' => $thumb ? esc_url_raw( $thumb ) : $url,
        'title' => sanitize_text_field( $title ),
    );

    return $assets;
}

function cgr_gallery_collect_block_assets( $block ) {
    $assets = array();

    if ( isset( $block['blockName'] ) && 'core/gallery' === $block['blockName'] ) {
        $attrs = isset( $block['attrs'] ) ? $block['attrs'] : array();

        if ( ! empty( $attrs['ids'] ) && is_array( $attrs['ids'] ) ) {
            foreach ( $attrs['ids'] as $attachment_id ) {
                if ( ! $attachment_id ) {
                    continue;
                }

                $image_url = wp_get_attachment_url( $attachment_id );
                $thumb_url = wp_get_attachment_image_url( $attachment_id, 'medium' );
                $title     = get_the_title( $attachment_id );

                $assets = cgr_gallery_collect_asset( $assets, $image_url, $thumb_url, $title );
            }
        }

        if ( empty( $attrs['ids'] ) && ! empty( $attrs['images'] ) && is_array( $attrs['images'] ) ) {
            foreach ( $attrs['images'] as $image ) {
                $image_url = isset( $image['url'] ) ? $image['url'] : '';
                $thumb_url = isset( $image['id'] ) ? wp_get_attachment_image_url( (int) $image['id'], 'medium' ) : $image_url;
                $title     = isset( $image['title'] ) ? $image['title'] : '';

                $assets = cgr_gallery_collect_asset( $assets, $image_url, $thumb_url, $title );
            }
        }

        if ( empty( $attrs['ids'] ) && empty( $attrs['images'] ) && ! empty( $block['innerBlocks'] ) ) {
            foreach ( $block['innerBlocks'] as $inner ) {
                $assets = array_merge( $assets, cgr_gallery_collect_block_assets( $inner ) );
            }
        }
    }

    if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
        foreach ( $block['innerBlocks'] as $inner ) {
            $assets = array_merge( $assets, cgr_gallery_collect_block_assets( $inner ) );
        }
    }

    return $assets;
}

function cgr_get_gallery_assets( $gallery_id ) {
    $raw_assets = get_post_meta( $gallery_id, '_cgr_gallery_assets', true );
    $tokens     = array_filter( array_map( 'trim', explode( ',', (string) $raw_assets ) ) );
    $assets     = array();

    foreach ( $tokens as $token ) {
        $image_url   = '';
        $image_title = '';
        $thumb_url   = '';

        if ( ctype_digit( $token ) ) {
            $attachment_id = (int) $token;
            $image_url     = wp_get_attachment_url( $attachment_id );
            if ( $image_url ) {
                $image_title = get_the_title( $attachment_id );
                $thumb_url   = wp_get_attachment_image_url( $attachment_id, 'medium' );
            }
        } elseif ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
            $image_url   = $token;
            $image_title = basename( parse_url( $token, PHP_URL_PATH ) );
        }

        $assets = cgr_gallery_collect_asset( $assets, $image_url, $thumb_url, $image_title );
    }

    if ( empty( $assets ) ) {
        $content = get_post_field( 'post_content', $gallery_id );
        if ( $content ) {
            $blocks = parse_blocks( $content );
            foreach ( $blocks as $block ) {
                $assets = array_merge( $assets, cgr_gallery_collect_block_assets( $block ) );
            }
        }
    }

    return $assets;
}

function cgr_rest_gallery_items( $request ) {
    $query = new WP_Query( array(
        'post_type'      => 'cgr_gallery',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $results = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $gallery_id     = get_the_ID();
            $gallery_title  = get_the_title();
            $gallery_excerpt = wp_strip_all_tags( get_the_excerpt() );
            $permalink      = get_permalink();
            $assets         = cgr_get_gallery_assets( $gallery_id );

            if ( empty( $assets ) ) {
                $fallback_image = get_the_post_thumbnail_url( $gallery_id, 'medium' );
                if ( $fallback_image ) {
                    $assets[] = array(
                        'url'   => esc_url_raw( $fallback_image ),
                        'title' => sanitize_text_field( $gallery_title ),
                        'thumb' => esc_url_raw( $fallback_image ),
                    );
                }
            }

            $thumbnail = ! empty( $assets ) ? $assets[0]['thumb'] : get_stylesheet_directory_uri() . '/images/gallery1.jpg';

            $results[] = array(
                'id'          => $gallery_id,
                'title'       => sanitize_text_field( $gallery_title ),
                'excerpt'     => sanitize_text_field( $gallery_excerpt ),
                'permalink'   => esc_url_raw( $permalink ),
                'asset_count' => count( $assets ),
                'assets'      => $assets,
                'thumbnail'   => esc_url_raw( $thumbnail ),
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response( array( 'galleries' => $results ) );
}
add_action( 'rest_api_init', function() {
    register_rest_route( 'cgr/v1', '/gallery', array(
        'methods'             => 'GET',
        'callback'            => 'cgr_rest_gallery_items',
        'permission_callback' => '__return_true',
    ) );
} );

function cgr_virtual_page_query_vars($vars) {
    $vars[] = 'cgr_virtual_page';
    return $vars;
}
add_filter('query_vars', 'cgr_virtual_page_query_vars');

function cgr_virtual_page_template($template) {
    $virtual_page = get_query_var('cgr_virtual_page');
    
    if ( ! empty( $virtual_page ) ) {
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_home = false;
        status_header( 200 );

        // 1. Check for specific file (e.g. page-gallery.php)
        $specific_template = locate_template(array("pages/page-{$virtual_page}.php", "page-{$virtual_page}.php"));
        if ( $specific_template ) {
            return $specific_template;
        }

        // 2. Check for CGR Team Template
        if ( $virtual_page === 'cgr-team' ) {
            $team_template = locate_template(array('includes/registrations/template-cgr-members.php'));
            if ( $team_template ) return $team_template;
        }

        // 3. Dedicated templates
        if ( $virtual_page === 'my-account' ) {
            $reg_template = locate_template(array('pages/page-registration.php'));
            if ( $reg_template ) return $reg_template;
        }
        if ( $virtual_page === 'media-coverage' ) {
            $mc_template = locate_template(array('pages/page-media-coverage.php'));
            if ( $mc_template ) return $mc_template;
        }
        if ( $virtual_page === 'pdf-magazines' ) {
            $pdf_template = locate_template(array('pages/page-pdf-magazines.php'));
            if ( $pdf_template ) return $pdf_template;
        }
        if ( $virtual_page === 'pudami' ) {
            $pudami_template = locate_template(array('pages/page-pdf-magazines.php'));
            if ( $pudami_template ) return $pudami_template;
        }
        if ( $virtual_page === 'careers' ) {
            $careers_template = locate_template(array('pages/page-careers.php'));
            if ( $careers_template ) return $careers_template;
        }

        // 3. Fallback to Generic Custom Content Template
        $generic_template = locate_template(array('pages/page-custom-content.php', 'page-custom-content.php'));
        if ( $generic_template ) {
            return $generic_template;
        }
    }
    
    return $template;
}
// add_filter('template_include', 'cgr_virtual_page_template');

function cgr_virtual_page_title($title) {
    $virtual_page = get_query_var('cgr_virtual_page');
    if ( ! empty( $virtual_page ) ) {
        // Convert slug to Title Case (e.g. 'vision-mission' -> 'Vision Mission')
        $formatted_title = ucwords(str_replace('-', ' ', $virtual_page));
        
        // Manual Overrides
        $titles = [
            'about-us' => 'About Us',
            'cgr-team' => 'Our Team',
            'pdf-magazines' => 'PDF Magazines',
            'vision-mission' => 'Vision & Mission',
        ];

        if ( isset( $titles[$virtual_page] ) ) {
            $title['title'] = $titles[$virtual_page];
        } else {
            $title['title'] = $formatted_title;
        }
    }
    return $title;
}
add_filter('document_title_parts', 'cgr_virtual_page_title');

// Debugging Tools
add_action('template_redirect', function() {
    // Only run if debug param is present
    if ( isset($_GET['cgr_debug']) ) {
        global $wp_query, $wp_rewrite;
        echo '<div style="background:#fff; color:#000; padding:20px; position:relative; z-index:99999;">';
        echo '<h3>CGR Debug Info</h3>';
        echo '<p><strong>Request:</strong> ' . $_SERVER['REQUEST_URI'] . '</p>';
        echo '<p><strong>Matched Rule:</strong> ' . $wp_query->matched_rule . '</p>';
        echo '<p><strong>Matched Query:</strong> ' . $wp_query->matched_query . '</p>';
        echo '<p><strong>Is 404:</strong> ' . ($wp_query->is_404 ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Virtual Page Var:</strong> ' . get_query_var('cgr_virtual_page') . '</p>';
        
        echo '<h4>Query Vars:</h4>';
        echo '<pre>' . print_r($wp_query->query_vars, true) . '</pre>';
        
        echo '<h4>Rewrite Rules (Top 5):</h4>';
        $rules = $wp_rewrite->wp_rewrite_rules();
        echo '<pre>' . print_r(array_slice($rules, 0, 5), true) . '</pre>';
        echo '</div>';
        exit;
    }
});

// Log request to error_log
add_filter('request', function($vars) {
    if ( isset($vars['cgr_virtual_page']) ) {
        error_log('CGR DEBUG: Virtual Page Requested: ' . $vars['cgr_virtual_page']);
    }
    return $vars;
});

// Force child-theme templates for Blog and Earth Scientists.
// DISABLED: To allow Elementor to control these pages.
/*
add_filter('template_include', function ($template) {
    $child_dir = get_stylesheet_directory();

    // /blog (page slug) -> force child template
    if (is_page('blog')) {
        $forced = $child_dir . '/pages/page-blog.php';
        if (file_exists($forced)) {
            return $forced;
        }
    }

    // Earth Scientists page slug
    if (is_page('earth-scientists')) {
        $forced = $child_dir . '/includes/registrations/template-earth-scientists.php';
        if (file_exists($forced)) {
            return $forced;
        }
    }

    // About page
    if ( is_page('about-us') ) {
        $forced = $child_dir . '/pages/page-about.php';
        if ( file_exists( $forced ) ) {
            return $forced;
        }
    }

    // Archive fallback for the CPT
    if (is_post_type_archive('earth_scientist')) {
        $forced = $child_dir . '/archive-earth_scientist.php';
        if (file_exists($forced)) {
            return $forced;
        }
    }

    // Appreciations archive
    if ( is_post_type_archive( 'cgr_testimonial' ) ) {
        $forced = $child_dir . '/archive-cgr_testimonial.php';
        if ( file_exists( $forced ) ) {
            return $forced;
        }
    }

    // Press coverage archive
    if ( is_post_type_archive( 'cgr_press_coverage' ) ) {
        $forced = $child_dir . '/pages/page-media-coverage.php';
        if ( file_exists( $forced ) ) {
            return $forced;
        }
    }

    // If Blog is the posts page, force child template
    if ( is_home() && ! is_front_page() ) {
        $forced = $child_dir . '/pages/page-blog.php';
        if ( file_exists( $forced ) ) {
            return $forced;
        }
    }

    return $template;
});
*/

/**
 * Ensure Blog page exists and is set as Posts page so /blog always works.
 */
/*
add_action('init', function() {
    $blog_page = get_page_by_path('blog');

    if ( ! $blog_page ) {
        $page_id = wp_insert_post(array(
            'post_type'    => 'page',
            'post_title'   => 'Blog',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_name'    => 'blog',
        ));
        if ( ! is_wp_error($page_id) ) {
            $blog_page = get_post($page_id);
        }
    }

    if ( $blog_page ) {
        if ( $blog_page->post_status !== 'publish' ) {
            wp_update_post(array(
                'ID'          => $blog_page->ID,
                'post_status' => 'publish',
            ));
        }

        if ( get_option('page_for_posts') != $blog_page->ID ) {
            update_option('page_for_posts', $blog_page->ID);
        }
        if ( get_post_meta( $blog_page->ID, '_wp_page_template', true ) !== 'pages/page-blog.php' ) {
            update_post_meta( $blog_page->ID, '_wp_page_template', 'pages/page-blog.php' );
        }
    }
});
*/

/**
 * AJAX: Blog search suggestions
 */
function cgr_ajax_blog_search() {
    $term = isset($_GET['term']) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
    if ( strlen( $term ) < 2 ) {
        wp_send_json_success( array() );
    }

    $query = new WP_Query( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 8,
        's'                   => $term,
        'ignore_sticky_posts' => true,
        'fields'              => 'ids',
    ) );

    $results = array();
    foreach ( $query->posts as $post_id ) {
        $thumb = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
        $results[] = array(
            'title' => get_the_title( $post_id ),
            'link'  => get_permalink( $post_id ),
            'date'  => get_the_date( 'M d, Y', $post_id ),
            'thumb' => $thumb ? $thumb : '',
        );
    }

    wp_send_json_success( $results );
}
add_action( 'wp_ajax_cgr_blog_search', 'cgr_ajax_blog_search' );
add_action( 'wp_ajax_nopriv_cgr_blog_search', 'cgr_ajax_blog_search' );

// Admin: Convert Pages to Posts
add_action('admin_menu', function() {
    add_menu_page(
        'CGR Content Tools',
        'Content Tools',
        'manage_options',
        'cgr-convert-pages',
        'cgr_render_convert_pages_admin',
        'dashicons-migrate',
        81
    );
});

function cgr_render_convert_pages_admin() {
    if ( ! current_user_can('manage_options') ) return;
    $conversion_notice = '';
    $selected_target = isset($_POST['cgr_target_type']) ? sanitize_key($_POST['cgr_target_type']) : 'post';
    $allowed_targets = array('post', 'cgr_gallery');
    if ( ! in_array( $selected_target, $allowed_targets, true ) ) {
        $selected_target = 'post';
    }

    if ( isset($_POST['cgr_convert_nonce']) && wp_verify_nonce($_POST['cgr_convert_nonce'], 'cgr_convert_pages') ) {
        $ids = isset($_POST['cgr_page_ids']) ? array_map('intval', (array) $_POST['cgr_page_ids']) : array();
        $target = $selected_target;

        $converted = 0;
        foreach ($ids as $page_id) {
            $page = get_post($page_id);
            if ( ! $page || $page->post_type !== 'page' ) continue;

            $new_post = array(
                'post_title'   => $page->post_title,
                'post_content' => $page->post_content,
                'post_excerpt' => $page->post_excerpt,
                'post_status'  => $page->post_status,
                'post_author'  => $page->post_author,
                'post_date'    => $page->post_date,
                'post_date_gmt'=> $page->post_date_gmt,
                'post_name'    => $page->post_name,
                'post_type'    => $target,
            );
            $new_id = wp_insert_post($new_post);
            if ( is_wp_error($new_id) ) continue;

            // Tag new content with a display area helper term
            if ( taxonomy_exists( 'cgr_display_area' ) ) {
                $term_slug = $target === 'cgr_gallery' ? 'gallery' : 'blog';
                wp_set_object_terms( $new_id, $term_slug, 'cgr_display_area', true );
            }

            // Copy featured image
            $thumb_id = get_post_thumbnail_id($page_id);
            if ($thumb_id) {
                set_post_thumbnail($new_id, $thumb_id);
            }

            // Copy meta
            $meta = get_post_meta($page_id);
            foreach ($meta as $key => $values) {
                foreach ($values as $value) {
                    add_post_meta($new_id, $key, maybe_unserialize($value));
                }
            }

            // Trash the original page to avoid duplicates
            wp_trash_post($page_id);
            $converted++;
        }
        $target_label = $target === 'cgr_gallery' ? 'galleries' : 'blog posts';
        $conversion_notice = '<div class="updated"><p>Conversion complete. Created ' . intval( $converted ) . ' ' . esc_html( $target_label ) . '.</p></div>';
    }

    $pages = get_posts(array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => array('publish','draft','pending','private'),
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
    ?>
    <div class="wrap">
        <h1>CGR Content Tools</h1>
        <p>Convert legacy Pages into Blog posts or Galleries without losing content, media, or metadata. Original pages are moved to Trash.</p>
        <?php if ( $conversion_notice ) { echo $conversion_notice; } ?>
        <form method="post">
            <?php wp_nonce_field('cgr_convert_pages', 'cgr_convert_nonce'); ?>
            <fieldset style="margin-bottom:15px;">
                <legend><strong>Choose target content type</strong></legend>
                <label style="margin-right:20px;"><input type="radio" name="cgr_target_type" value="post" <?php checked( $selected_target, 'post' ); ?>> Blog Post</label>
                <label><input type="radio" name="cgr_target_type" value="cgr_gallery" <?php checked( $selected_target, 'cgr_gallery' ); ?>> Gallery Item</label>
            </fieldset>
            <p>Select the pages you want to convert. The originals will be moved to Trash.</p>
            <div style="max-height:400px; overflow:auto; border:1px solid #ccc; padding:10px; background:#fff;">
                <?php foreach ($pages as $p): ?>
                    <label style="display:block; margin-bottom:4px;">
                        <input type="checkbox" name="cgr_page_ids[]" value="<?php echo esc_attr($p->ID); ?>">
                        <?php echo esc_html($p->post_title . ' (ID ' . $p->ID . ')'); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p><button class="button button-primary" type="submit">Convert Selected to Posts</button></p>
        </form>
    </div>
    <?php
}

/**
 * Elementor Configuration
 * Disable default colors and fonts to prevent overrides.
 */
add_action( 'elementor/init', function() {
    if ( get_option( 'elementor_disable_color_schemes' ) !== 'yes' ) {
        update_option( 'elementor_disable_color_schemes', 'yes' );
    }
    if ( get_option( 'elementor_disable_typography_schemes' ) !== 'yes' ) {
        update_option( 'elementor_disable_typography_schemes', 'yes' );
    }
} );

/**
 * Display Shortcode Hints in Admin List Views
 */
function cgr_show_directory_shortcode_hint() {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    $message = '';

    if ( $screen->post_type === 'earth_leader' && $screen->base === 'edit' ) {
        $message = '<strong>Directory Shortcode:</strong> Use <code>[cgr_earth_leaders_directory]</code> to display the full list of Earth Leaders.';
    }
    elseif ( $screen->post_type === 'earth_scientist' && $screen->base === 'edit' ) {
        $message = '<strong>Directory Shortcode:</strong> Use <code>[cgr_earth_scientists_directory]</code> to display the full list of Earth Scientists.';
    }
    elseif ( $screen->post_type === 'cgr_member' && $screen->base === 'edit' ) {
        $message = '<strong>Directory Shortcode:</strong> Use <code>[cgr_team_directory]</code> to display the full CGR Team.';
    }
    elseif ( $screen->post_type === 'cgr_gallery' && $screen->base === 'edit' ) {
        $message = '<strong>Gallery Grid Shortcode:</strong> Use <code>[cgr_gallery_grid]</code> to display all galleries. <br><strong>Single Gallery:</strong> Use <code>[cgr_gallery id="123"]</code> (replace 123 with ID) to display a specific gallery.';
    }

    if ( $message ) {
        echo '<div class="notice notice-info is-dismissible"><p>' . $message . '</p></div>';
    }
}
add_action( 'admin_notices', 'cgr_show_directory_shortcode_hint' );

/**
 * Auto-append Gallery to Content
 * Ensures the gallery images appear even if the user forgets the shortcode.
 */
add_filter( 'the_content', function( $content ) {
    if ( is_singular( 'cgr_gallery' ) && ! has_shortcode( $content, 'cgr_gallery' ) ) {
        $content .= '[cgr_gallery]';
    }
    return $content;
} );

/* ==========================================================================
 * CGR CHILD THEME — LITESPEED OPTIMIZATION SUITE (UPDATED)
 * ========================================================================== */

/**
 * 1. FIX MENU & GALLERY (Exclude from LiteSpeed JS Delay)
 * This forces menu and gallery scripts to load immediately so popups work.
 */
function cgr_litespeed_js_exclusions( $excludes ) {
    // --- Menu Fixes ---
    $excludes[] = 'menu-toggle';
    $excludes[] = 'menu-drawer';
    $excludes[] = 'site-header';
    $excludes[] = 'cgr-mobile-menu';
    $excludes[] = 'DOMContentLoaded'; 
    
    // --- Gallery & Popup Fixes ---
    $excludes[] = 'jquery';           // Essential for most galleries
    $excludes[] = 'lightbox';         // Common keyword
    $excludes[] = 'fancybox';         // Common keyword
    $excludes[] = 'magnific';         // Common keyword
    $excludes[] = 'swiper';           // Sliders
    $excludes[] = 'slick';            // Sliders
    $excludes[] = 'elementor-gallery'; // If using Elementor
    $excludes[] = 'wc-single-product'; // WooCommerce galleries
    
    return $excludes;
}
add_filter( 'litespeed_optm_js_defer_exc', 'cgr_litespeed_js_exclusions' );
add_filter( 'litespeed_optm_js_exc', 'cgr_litespeed_js_exclusions' );

/**
 * 2. SPEED BOOST: Remove WordPress Emojis
 */
function cgr_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'cgr_disable_emojis' );

/**
 * 3. SPEED BOOST: Conditional Asset Loading
 */
function cgr_cleanup_scripts() {
    // Only run cleanup on the frontend, not in admin
    if ( is_admin() ) {
        return;
    }
    
    // Only run this cleanup on the Front Page (Home)
    if ( is_front_page() || is_home() ) {
        wp_dequeue_script( 'contact-form-7' );
        wp_dequeue_style( 'contact-form-7' );
    }
}
add_action( 'wp_enqueue_scripts', 'cgr_cleanup_scripts', 100 );

/**
 * 4. PERFORMANCE: Remove Query Strings
 */
function cgr_remove_script_version( $src ) {
    // Only remove version strings on frontend, not in admin
    if ( is_admin() ) {
        return $src;
    }
    
    $parts = explode( '?ver', $src ); 
    return $parts[0];
}
add_filter( 'script_loader_src', 'cgr_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', 'cgr_remove_script_version', 15, 1 );

?>
