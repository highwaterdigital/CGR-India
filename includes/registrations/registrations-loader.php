<?php
/**
 * Registrations Loader
 * 
 * Loads CPT logic and handles template redirection for:
 * - Earth Leaders
 * - Earth Scientists
 * - CGR Members
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load Logic Files
error_log('CGR DEBUG: Loading logic files from registrations-loader.php');
require_once CGR_CHILD_DIR . '/includes/registrations/earth-leaders.php';
require_once CGR_CHILD_DIR . '/includes/registrations/earth-scientists.php';
require_once CGR_CHILD_DIR . '/includes/registrations/cgr-core-members.php';

/**
 * Filter 'single_template' to load single post templates from this directory.
 */
function cgr_registrations_single_template( $template ) {
    global $post;

    if ( ! $post ) {
        return $template;
    }

    error_log('CGR DEBUG: Checking single_template for post type: ' . $post->post_type);

    if ( 'earth_leader' === $post->post_type ) {
        $custom_template = CGR_CHILD_DIR . '/includes/registrations/single-earth_leader.php';
        error_log('CGR DEBUG: Looking for Earth Leader template at: ' . $custom_template);
        if ( file_exists( $custom_template ) ) {
            error_log('CGR DEBUG: Template found!');
            return $custom_template;
        } else {
            error_log('CGR DEBUG: Template NOT found!');
        }
    }

    if ( 'earth_scientist' === $post->post_type ) {
        $custom_template = CGR_CHILD_DIR . '/includes/registrations/single-earth_scientist.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }

    if ( 'cgr_member' === $post->post_type ) {
        $custom_template = CGR_CHILD_DIR . '/includes/registrations/single-cgr_member.php';
        if ( file_exists( $custom_template ) ) {
            return $custom_template;
        }
    }

    return $template;
}
add_filter( 'single_template', 'cgr_registrations_single_template' );

/**
 * Register custom page templates from the includes/registrations directory.
 * This is necessary because WordPress only scans 1 level deep for templates by default.
 */
function cgr_register_custom_page_templates( $templates ) {
    $custom_templates = array(
        'includes/registrations/template-earth-leaders.php'     => 'Earth Leaders Directory',
        'includes/registrations/template-earth-scientists.php'  => 'Earth Scientists Directory',
        'includes/registrations/template-cgr-members.php'       => 'CGR Team Directory',
    );

    // Merge our templates with the existing ones
    return array_merge( $templates, $custom_templates );
}
// add_filter( 'theme_page_templates', 'cgr_register_custom_page_templates' );

/**
 * Register Custom Rewrite Rules for Virtual Pages
 * This allows /earth-leaders/, /earth-scientists/, /cgr-team/ to work without creating WP Pages.
 */
/*
function cgr_register_rewrite_rules() {
    add_rewrite_rule( '^earth-leaders/?$', 'index.php?cgr_directory=earth_leaders', 'top' );
    add_rewrite_rule( '^earth-scientists/?$', 'index.php?cgr_directory=earth_scientists', 'top' );
    add_rewrite_rule( '^cgr-team/?$', 'index.php?cgr_directory=cgr_team', 'top' );
}
add_action( 'init', 'cgr_register_rewrite_rules' );
*/

function cgr_register_query_vars( $vars ) {
    $vars[] = 'cgr_directory';
    return $vars;
}
add_filter( 'query_vars', 'cgr_register_query_vars' );

/**
 * Load the custom page template if selected OR if rewrite rule matches.
 */
function cgr_load_custom_page_template( $template ) {
    // 1. Check for Virtual Page (Rewrite Rule)
    $directory_type = get_query_var( 'cgr_directory' );
    
    if ( $directory_type ) {
        error_log('CGR DEBUG: Virtual Page Detected: ' . $directory_type);
        
        $target_template = '';
        if ( 'earth_leaders' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-earth-leaders.php';
        }
        if ( 'earth_scientists' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-earth-scientists.php';
        }
        if ( 'cgr_team' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-cgr-members.php';
        }

        if ( !empty($target_template) ) {
            if ( file_exists( $target_template ) ) {
                error_log('CGR DEBUG: Loading Template: ' . $target_template);
                return $target_template;
            } else {
                error_log('CGR ERROR: Template file missing at: ' . $target_template);
            }
        }
    }

    // 2. Check for Assigned Page Template (WP Admin)
    if ( is_page() ) {
        $post_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
        
        // Check if the selected template is one of ours
        if ( strpos( $post_template, 'includes/registrations/' ) !== false ) {
            $file = CGR_CHILD_DIR . '/' . $post_template;
            if ( file_exists( $file ) ) {
                error_log('CGR DEBUG: Loading Assigned Template: ' . $file);
                return $file;
            } else {
                error_log('CGR ERROR: Assigned Template file missing at: ' . $file);
            }
        }
    }
    return $template;
}
// add_filter( 'template_include', 'cgr_load_custom_page_template' );

/**
 * Force Template Redirect (Fallback for template_include)
 */
function cgr_force_template_redirect() {
    $directory_type = get_query_var( 'cgr_directory' );
    if ( $directory_type ) {
        $target_template = '';
        if ( 'earth_leaders' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-earth-leaders.php';
        }
        if ( 'earth_scientists' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-earth-scientists.php';
        }
        if ( 'cgr_team' === $directory_type ) {
            $target_template = CGR_CHILD_DIR . '/includes/registrations/template-cgr-members.php';
        }

        if ( !empty($target_template) && file_exists( $target_template ) ) {
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_home = false;
            status_header( 200 );
            include( $target_template );
            exit;
        }
    }
}
// add_action( 'template_redirect', 'cgr_force_template_redirect', 1 );

/**
 * Fix HTTP Status and Title for Virtual Pages
 */
function cgr_directory_virtual_page_fix() {
    $directory_type = get_query_var( 'cgr_directory' );
    if ( $directory_type ) {
        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_home = false;
        status_header( 200 );
    }
}
// add_action( 'wp', 'cgr_directory_virtual_page_fix' );

function cgr_directory_document_title( $title ) {
    $directory_type = get_query_var( 'cgr_directory' );
    if ( $directory_type ) {
        if ( 'earth_leaders' === $directory_type ) {
            $title['title'] = 'Earth Leaders';
        } elseif ( 'earth_scientists' === $directory_type ) {
            $title['title'] = 'Earth Scientists';
        } elseif ( 'cgr_team' === $directory_type ) {
            $title['title'] = 'CGR Team';
        }
    }
    return $title;
}
// add_filter( 'document_title_parts', 'cgr_directory_document_title' );

/**
 * Auto-inject links into the Primary Menu
 */
function cgr_add_directories_to_menu( $items, $args ) {
    if ( 'primary' === $args->theme_location ) {
        $items .= '<li class="menu-item cgr-auto-link"><a href="' . home_url( '/earth-leaders/' ) . '">Earth Leaders</a></li>';
        $items .= '<li class="menu-item cgr-auto-link"><a href="' . home_url( '/earth-scientists/' ) . '">Earth Scientists</a></li>';
        $items .= '<li class="menu-item cgr-auto-link"><a href="' . home_url( '/cgr-team/' ) . '">CGR Team</a></li>';
    }
    return $items;
}
// add_filter( 'wp_nav_menu_items', 'cgr_add_directories_to_menu', 10, 2 );

// Force flush rewrite rules for debugging (remove after fix)
add_action( 'init', function() {
    if ( isset( $_GET['cgr_flush_rewrites'] ) ) {
        flush_rewrite_rules();
        echo '<!-- CGR DEBUG: Rewrite rules flushed -->';
    }
} );
