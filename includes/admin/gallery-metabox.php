<?php
/**
 * CGR Gallery Metabox
 * Adds a custom meta box to the 'cgr_gallery' post type for managing gallery images.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CGR_Gallery_Metabox {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts( $hook ) {
        global $post;

        if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
            if ( 'cgr_gallery' === $post->post_type ) {
                wp_enqueue_media();
                wp_enqueue_script( 'cgr-gallery-metabox', get_stylesheet_directory_uri() . '/assets/js/gallery-metabox.js', array( 'jquery' ), '1.0.0', true );
                wp_enqueue_style( 'cgr-gallery-metabox', get_stylesheet_directory_uri() . '/assets/css/gallery-metabox.css', array(), '1.0.0' );
            }
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'cgr_gallery_images',
            __( 'Gallery Images (Bulk Upload)', 'cgr-child' ),
            array( $this, 'render_meta_box' ),
            'cgr_gallery',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        // Add nonce for security and authentication.
        wp_nonce_field( 'cgr_gallery_save_data', 'cgr_gallery_meta_box_nonce' );

        // Retrieve an existing value from the database.
        $gallery_assets = get_post_meta( $post->ID, '_cgr_gallery_assets', true );
        $asset_ids = array_filter( explode( ',', (string) $gallery_assets ) );

        ?>
        <div id="cgr-gallery-metabox-container">
            <p class="description">
                <?php _e( 'Upload or select images to include in this gallery. These images will be displayed when using the [cgr_gallery] shortcode.', 'cgr-child' ); ?>
            </p>
            
            <div id="cgr-gallery-images-preview">
                <?php
                if ( ! empty( $asset_ids ) ) {
                    foreach ( $asset_ids as $id ) {
                        $url = wp_get_attachment_image_url( $id, 'thumbnail' );
                        if ( $url ) {
                            echo '<div class="cgr-gallery-image" data-id="' . esc_attr( $id ) . '">';
                            echo '<img src="' . esc_url( $url ) . '" />';
                            echo '<span class="remove-image">×</span>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>

            <div class="cgr-gallery-actions">
                <button type="button" class="button button-primary" id="cgr-add-gallery-images">
                    <?php _e( 'Add Images', 'cgr-child' ); ?>
                </button>
                <button type="button" class="button" id="cgr-clear-gallery-images">
                    <?php _e( 'Clear All', 'cgr-child' ); ?>
                </button>
            </div>

            <input type="hidden" name="cgr_gallery_assets" id="cgr_gallery_assets" value="<?php echo esc_attr( $gallery_assets ); ?>">
        </div>
        <?php
    }

    public function save_meta_box( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['cgr_gallery_meta_box_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['cgr_gallery_meta_box_nonce'], 'cgr_gallery_save_data' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize user input.
        if ( isset( $_POST['cgr_gallery_assets'] ) ) {
            $new_value = sanitize_text_field( $_POST['cgr_gallery_assets'] );
            update_post_meta( $post_id, '_cgr_gallery_assets', $new_value );
        }
    }
}

new CGR_Gallery_Metabox();
