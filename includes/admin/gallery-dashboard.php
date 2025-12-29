<?php
/**
 * CGR Gallery Dashboard - Custom Admin Interface
 * Provides a better UI for managing gallery items with media uploader
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the custom admin page
 */
function cgr_register_gallery_dashboard() {
    add_submenu_page(
        'edit.php?post_type=cgr_gallery',
        __( 'Gallery Dashboard', 'cgr-child' ),
        __( 'Dashboard', 'cgr-child' ),
        'edit_posts',
        'cgr-gallery-dashboard',
        'cgr_render_gallery_dashboard'
    );
}
add_action( 'admin_menu', 'cgr_register_gallery_dashboard' );

/**
 * Enqueue admin scripts for media uploader
 */
function cgr_gallery_dashboard_scripts( $hook ) {
    if ( 'cgr_gallery_page_cgr-gallery-dashboard' !== $hook ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style( 'cgr-gallery-dashboard', get_stylesheet_directory_uri() . '/includes/admin/gallery-dashboard.css', array(), '1.0.0' );
    wp_enqueue_script( 'cgr-gallery-dashboard', get_stylesheet_directory_uri() . '/includes/admin/gallery-dashboard.js', array( 'jquery' ), '1.0.0', true );
    $gallery_posts = get_posts( array(
        'post_type'      => 'cgr_gallery',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $gallery_data = array();
    foreach ( $gallery_posts as $gallery ) {
        $gallery_data[] = array(
            'id'        => $gallery->ID,
            'title'     => get_the_title( $gallery ),
            'permalink' => get_permalink( $gallery ),
        );
    }

    wp_localize_script( 'cgr-gallery-dashboard', 'cgrGalleryDashboard', array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'cgr_gallery_dashboard_nonce' ),
        'galleries' => $gallery_data,
    ) );
}
add_action( 'admin_enqueue_scripts', 'cgr_gallery_dashboard_scripts' );

/**
 * Render the Gallery Dashboard page
 */
function cgr_render_gallery_dashboard() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'You do not have permission to access this page.', 'cgr-child' ) );
    }

    // Get all gallery items
    $galleries = get_posts( array(
        'post_type'      => 'cgr_gallery',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    ?>
    <div class="wrap cgr-gallery-dashboard">
        <h1><?php _e( 'Gallery Dashboard', 'cgr-child' ); ?></h1>
        <p class="description">
            <?php _e( 'Manage your gallery items with ease. Use the media uploader to add images and organize your content.', 'cgr-child' ); ?>
        </p>

        <div class="cgr-gallery-toolbox">
            <h2><?php _e( 'Bulk Upload & Share', 'cgr-child' ); ?></h2>
            <p class="description">
                <?php _e( 'Pick a gallery below to drop dozens of images at once and copy a shareable URL for social posts.', 'cgr-child' ); ?>
            </p>
            <div class="cgr-bulk-toolbar">
                <select id="cgr-bulk-gallery-select">
                    <option value=""><?php esc_html_e( 'Select gallery', 'cgr-child' ); ?></option>
                    <?php foreach ( $galleries as $gallery ) : ?>
                        <option value="<?php echo esc_attr( $gallery->ID ); ?>" data-permalink="<?php echo esc_url( get_permalink( $gallery ) ); ?>">
                            <?php echo esc_html( $gallery->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="cgr-bulk-add-media"><?php esc_html_e( 'Select media', 'cgr-child' ); ?></button>
                <button type="button" class="button button-primary" id="cgr-bulk-attach-media"><?php esc_html_e( 'Attach to gallery', 'cgr-child' ); ?></button>
            </div>

            <div class="cgr-bulk-share-row">
                <label for="cgr-bulk-share-link"><?php esc_html_e( 'Gallery share link', 'cgr-child' ); ?></label>
                <div class="cgr-bulk-share-input">
                    <input type="text" id="cgr-bulk-share-link" readonly placeholder="<?php esc_attr_e( 'Pick a gallery to reveal its share URL', 'cgr-child' ); ?>">
                    <button type="button" class="button" id="cgr-bulk-copy-link"><?php esc_html_e( 'Copy', 'cgr-child' ); ?></button>
                </div>
            </div>

            <div class="cgr-bulk-preview" id="cgr-bulk-preview">
                <p><?php esc_html_e( 'No media selected yet.', 'cgr-child' ); ?></p>
            </div>
        </div>

        <div class="cgr-gallery-settings">
            <h2><?php _e( 'Animation Settings', 'cgr-child' ); ?></h2>
            <div class="cgr-settings-grid">
                <div class="cgr-setting-item">
                    <label for="default-animation"><?php _e( 'Default Animation', 'cgr-child' ); ?></label>
                    <select id="default-animation" name="default_animation">
                        <option value="fade"><?php _e( 'Fade & scale', 'cgr-child' ); ?></option>
                        <option value="slide" selected><?php _e( 'Slide up', 'cgr-child' ); ?></option>
                        <option value="glow"><?php _e( 'Glow pulse', 'cgr-child' ); ?></option>
                    </select>
                </div>

                <div class="cgr-setting-item">
                    <label for="default-layout"><?php _e( 'Default Layout', 'cgr-child' ); ?></label>
                    <select id="default-layout" name="default_layout">
                        <option value="grid" selected><?php _e( 'Compact grid', 'cgr-child' ); ?></option>
                        <option value="stack"><?php _e( 'Stacked rows', 'cgr-child' ); ?></option>
                    </select>
                </div>

                <div class="cgr-setting-item">
                    <label for="default-accent"><?php _e( 'Accent Color', 'cgr-child' ); ?></label>
                    <input type="color" id="default-accent" name="default_accent" value="#1f4f2e">
                </div>
            </div>
        </div>

        <div class="cgr-gallery-items">
            <div class="cgr-gallery-header">
                <h2><?php _e( 'Gallery Items', 'cgr-child' ); ?></h2>
                <a href="<?php echo admin_url( 'post-new.php?post_type=cgr_gallery' ); ?>" class="button button-primary">
                    <?php _e( 'Add New Gallery', 'cgr-child' ); ?>
                </a>
            </div>

            <?php if ( empty( $galleries ) ) : ?>
                <div class="cgr-no-galleries">
                    <p><?php _e( 'No gallery items found. Create your first gallery to get started!', 'cgr-child' ); ?></p>
                </div>
            <?php else : ?>
                <div class="cgr-gallery-grid">
                    <?php foreach ( $galleries as $gallery ) : 
                        $gallery_assets = get_post_meta( $gallery->ID, '_cgr_gallery_assets', true );
                        $asset_tokens = array_filter( array_map( 'trim', explode( ',', (string) $gallery_assets ) ) );
                        
                        // Get first image for thumbnail
                        $thumbnail_url = '';
                        foreach ( $asset_tokens as $token ) {
                            if ( ctype_digit( $token ) ) {
                                $attachment_id = (int) $token;
                                $thumbnail_url = wp_get_attachment_image_url( $attachment_id, 'medium' );
                                if ( $thumbnail_url ) break;
                            } elseif ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
                                $thumbnail_url = $token;
                                break;
                            }
                        }
                        
                        if ( ! $thumbnail_url ) {
                            $thumbnail_url = get_stylesheet_directory_uri() . '/images/placeholder-gallery.png';
                        }
                        
                        $asset_count = count( $asset_tokens );
                    ?>
                    <div class="cgr-gallery-card" data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>">
                        <div class="cgr-gallery-card__thumbnail">
                            <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $gallery->post_title ); ?>">
                            <div class="cgr-gallery-card__overlay">
                                <span class="cgr-asset-count"><?php echo esc_html( $asset_count ); ?> <?php _e( 'items', 'cgr-child' ); ?></span>
                            </div>
                        </div>
                        <div class="cgr-gallery-card__content">
                            <h3><?php echo esc_html( $gallery->post_title ); ?></h3>
                            <p><?php echo esc_html( wp_trim_words( $gallery->post_excerpt, 15 ) ); ?></p>
                            
                            <div class="cgr-gallery-shortcode-box">
                                <label><?php _e( 'Shortcode:', 'cgr-child' ); ?></label>
                                <div class="cgr-shortcode-copy">
                                    <code>[cgr_gallery id="<?php echo esc_attr( $gallery->ID ); ?>"]</code>
                                    <button type="button" class="button button-small cgr-copy-shortcode" data-shortcode='[cgr_gallery id="<?php echo esc_attr( $gallery->ID ); ?>"]'>
                                        <?php _e( 'Copy', 'cgr-child' ); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="cgr-gallery-card__actions">
                                <a href="<?php echo get_edit_post_link( $gallery->ID ); ?>" class="button button-secondary">
                                    <?php _e( 'Edit', 'cgr-child' ); ?>
                                </a>
                                <button type="button" class="button cgr-quick-edit" data-gallery-id="<?php echo esc_attr( $gallery->ID ); ?>">
                                    <?php _e( 'Quick Edit Media', 'cgr-child' ); ?>
                                </button>
                                <a href="<?php echo get_permalink( $gallery->ID ); ?>" class="button" target="_blank">
                                    <?php _e( 'View', 'cgr-child' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.cgr-copy-shortcode').on('click', function() {
            var btn = $(this);
            var code = btn.data('shortcode');
            navigator.clipboard.writeText(code).then(function() {
                var originalText = btn.text();
                btn.text('Copied!');
                setTimeout(function() { btn.text(originalText); }, 2000);
            });
        });
    });
    </script>

    <!-- Quick Edit Modal -->
    <div id="cgr-quick-edit-modal" class="cgr-modal" style="display: none;">
        <div class="cgr-modal-content">
            <span class="cgr-modal-close">&times;</span>
            <h2><?php _e( 'Quick Edit Media', 'cgr-child' ); ?></h2>
            <div id="cgr-modal-body">
                <div class="cgr-media-selector">
                    <button type="button" class="button button-primary" id="cgr-add-media">
                        <?php _e( 'Add Media', 'cgr-child' ); ?>
                    </button>
                    <p class="description">
                        <?php _e( 'Click to select images from your media library. You can also paste direct URLs below.', 'cgr-child' ); ?>
                    </p>
                </div>
                <div class="cgr-selected-media" id="cgr-selected-media">
                    <!-- Selected media will appear here -->
                </div>
                <div class="cgr-manual-input">
                    <label for="cgr-manual-urls"><?php _e( 'Or paste media IDs/URLs (comma-separated):', 'cgr-child' ); ?></label>
                    <textarea id="cgr-manual-urls" rows="3" placeholder="123, 456, https://example.com/image.jpg"></textarea>
                </div>
                <div class="cgr-modal-actions">
                    <button type="button" class="button button-primary" id="cgr-save-media">
                        <?php _e( 'Save Changes', 'cgr-child' ); ?>
                    </button>
                    <button type="button" class="button" id="cgr-cancel-edit">
                        <?php _e( 'Cancel', 'cgr-child' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler to update gallery media
 */
function cgr_ajax_update_gallery_media() {
    check_ajax_referer( 'cgr_gallery_dashboard_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'cgr-child' ) ) );
    }

    $gallery_id = isset( $_POST['gallery_id'] ) ? intval( $_POST['gallery_id'] ) : 0;
    $media_data = isset( $_POST['media_data'] ) ? sanitize_text_field( $_POST['media_data'] ) : '';

    if ( ! $gallery_id || get_post_type( $gallery_id ) !== 'cgr_gallery' ) {
        wp_send_json_error( array( 'message' => __( 'Invalid gallery ID.', 'cgr-child' ) ) );
    }

    update_post_meta( $gallery_id, '_cgr_gallery_assets', $media_data );

    wp_send_json_success( array(
        'message' => __( 'Gallery media updated successfully!', 'cgr-child' ),
        'media_data' => $media_data,
    ) );
}
add_action( 'wp_ajax_cgr_update_gallery_media', 'cgr_ajax_update_gallery_media' );

/**
 * AJAX handler to get gallery media
 */
function cgr_ajax_get_gallery_media() {
    check_ajax_referer( 'cgr_gallery_dashboard_nonce', 'nonce' );

    $gallery_id = isset( $_POST['gallery_id'] ) ? intval( $_POST['gallery_id'] ) : 0;

    if ( ! $gallery_id || get_post_type( $gallery_id ) !== 'cgr_gallery' ) {
        wp_send_json_error( array( 'message' => __( 'Invalid gallery ID.', 'cgr-child' ) ) );
    }

    $gallery_assets = get_post_meta( $gallery_id, '_cgr_gallery_assets', true );
    $asset_tokens = array_filter( array_map( 'trim', explode( ',', (string) $gallery_assets ) ) );
    
    $media_items = array();
    foreach ( $asset_tokens as $token ) {
        $item = array(
            'id' => $token,
            'url' => '',
            'thumbnail' => '',
            'title' => '',
        );

        if ( ctype_digit( $token ) ) {
            $attachment_id = (int) $token;
            $item['url'] = wp_get_attachment_url( $attachment_id );
            $item['thumbnail'] = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
            $item['title'] = get_the_title( $attachment_id );
        } elseif ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
            $item['url'] = $token;
            $item['thumbnail'] = $token;
            $item['title'] = basename( parse_url( $token, PHP_URL_PATH ) );
        }

        if ( $item['url'] ) {
            $media_items[] = $item;
        }
    }

    wp_send_json_success( array(
        'media_items' => $media_items,
        'raw_data' => $gallery_assets,
    ) );
}
add_action( 'wp_ajax_cgr_get_gallery_media', 'cgr_ajax_get_gallery_media' );

/**
 * Customize the gallery admin menu so Dashboard becomes the default and Display Areas is hidden.
 */
function cgr_simplify_gallery_admin_menu() {
    global $menu, $submenu;

    foreach ( $menu as $index => $item ) {
        if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=cgr_gallery' ) {
            $menu[ $index ][2] = 'edit.php?post_type=cgr_gallery&page=cgr-gallery-dashboard';
            break;
        }
    }

    if ( empty( $submenu['edit.php?post_type=cgr_gallery'] ) ) {
        return;
    }

    foreach ( $submenu['edit.php?post_type=cgr_gallery'] as $key => $item ) {
        if ( isset( $item[2] ) && strpos( $item[2], 'taxonomy=cgr_display_area' ) !== false ) {
            unset( $submenu['edit.php?post_type=cgr_gallery'][$key] );
        }
    }

    $submenu['edit.php?post_type=cgr_gallery'] = array_values( $submenu['edit.php?post_type=cgr_gallery'] );

    foreach ( $submenu['edit.php?post_type=cgr_gallery'] as $key => $item ) {
        if ( isset( $item[2] ) && $item[2] === 'edit.php?post_type=cgr_gallery&page=cgr-gallery-dashboard' ) {
            $dashboard = $item;
            unset( $submenu['edit.php?post_type=cgr_gallery'][$key] );
            array_unshift( $submenu['edit.php?post_type=cgr_gallery'], $dashboard );
            break;
        }
    }

    $submenu['edit.php?post_type=cgr_gallery'] = array_values( $submenu['edit.php?post_type=cgr_gallery'] );
}
add_action( 'admin_menu', 'cgr_simplify_gallery_admin_menu', 999 );
