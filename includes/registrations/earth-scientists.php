<?php
/**
 * Earth Scientists CPT and Logic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register Custom Post Type (Keep existing registration code...)
function cgr_register_earth_scientist_cpt() {
    $labels = array(
        'name'                  => _x( 'Earth Scientists', 'Post Type General Name', 'cgr-child' ),
        'singular_name'         => _x( 'Earth Scientist', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'             => __( 'Earth Scientists', 'cgr-child' ),
        'all_items'             => __( 'All Scientists', 'cgr-child' ),
        'add_new_item'          => __( 'Add New Scientist', 'cgr-child' ),
        'add_new'               => __( 'Add New', 'cgr-child' ),
        'new_item'              => __( 'New Scientist', 'cgr-child' ),
        'edit_item'             => __( 'Edit Scientist', 'cgr-child' ),
        'view_item'             => __( 'View Scientist', 'cgr-child' ),
        'search_items'          => __( 'Search Scientists', 'cgr-child' ),
        'not_found'             => __( 'No scientists found', 'cgr-child' ),
        'featured_image'        => __( 'Scientist Photo', 'cgr-child' ),
        'set_featured_image'    => __( 'Set photo', 'cgr-child' ),
    );
    $args = array(
        'label'                 => __( 'Earth Scientist', 'cgr-child' ),
        'description'           => __( 'Earth Scientists and Researchers', 'cgr-child' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail' ), // Editor for bio
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 6,
        'menu_icon'             => 'dashicons-welcome-learn-more',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'has_archive'           => false, // Disabled to allow Elementor Page with same slug
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'rewrite'               => array( 'slug' => 'earth-scientists' ),
    );
    register_post_type( 'earth_scientist', $args );
}
add_action( 'init', 'cgr_register_earth_scientist_cpt', 0 );

// 2. Add Meta Boxes (Keep existing...)
function cgr_earth_scientist_meta_boxes() {
    add_meta_box(
        'cgr_scientist_details',
        __( 'Professional Details', 'cgr-child' ),
        'cgr_scientist_meta_box_callback',
        'earth_scientist',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cgr_earth_scientist_meta_boxes' );

function cgr_scientist_meta_box_callback( $post ) {
    wp_nonce_field( 'cgr_scientist_save', 'cgr_scientist_nonce' );

    $specialization = get_post_meta( $post->ID, '_cgr_specialization', true );
    $institution = get_post_meta( $post->ID, '_cgr_institution', true );
    $location = get_post_meta( $post->ID, '_cgr_location', true );
    
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    
    echo '<p><label for="cgr_specialization"><strong>' . __( 'Specialization / Field', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_specialization" name="cgr_specialization" value="' . esc_attr( $specialization ) . '" class="widefat" placeholder="e.g. Ecology, Botany" /></p>';

    echo '<p><label for="cgr_location"><strong>' . __( 'Location', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_location" name="cgr_location" value="' . esc_attr( $location ) . '" class="widefat" /></p>';

    echo '<p style="grid-column: span 2;"><label for="cgr_institution"><strong>' . __( 'Institution / Organization', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_institution" name="cgr_institution" value="' . esc_attr( $institution ) . '" class="widefat" /></p>';
    
    echo '</div>';
}

// 3. Save Meta Data (Keep existing...)
function cgr_save_scientist_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_scientist_nonce'] ) ) { return; }
    if ( ! wp_verify_nonce( $_POST['cgr_scientist_nonce'], 'cgr_scientist_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    if ( isset( $_POST['cgr_specialization'] ) ) {
        update_post_meta( $post_id, '_cgr_specialization', sanitize_text_field( $_POST['cgr_specialization'] ) );
    }
    if ( isset( $_POST['cgr_institution'] ) ) {
        update_post_meta( $post_id, '_cgr_institution', sanitize_text_field( $_POST['cgr_institution'] ) );
    }
    if ( isset( $_POST['cgr_location'] ) ) {
        update_post_meta( $post_id, '_cgr_location', sanitize_text_field( $_POST['cgr_location'] ) );
    }
}
add_action( 'save_post', 'cgr_save_scientist_meta' );

// 4. AJAX Handler for Filtering (UPDATED TO MATCH NEW DESIGN)
function cgr_filter_earth_scientists_ajax() {
    error_log('CGR DEBUG: AJAX cgr_filter_earth_scientists called');
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'title_asc';
    $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;

    error_log("CGR DEBUG: Search: $search, Paged: $paged");

    $args = array(
        'post_type'      => 'earth_scientist',
        'posts_per_page' => 12,
        'paged'          => $paged,
    );

    // Sorting Logic
    switch ($sort) {
        case 'title_desc':
            $args['orderby'] = 'title';
            $args['order'] = 'DESC';
            break;
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'title_asc':
        default:
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
            break;
    }

    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    $query = new WP_Query( $args );

    error_log("CGR DEBUG: Earth Scientists Query found " . $query->found_posts . " posts.");

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $spec = get_post_meta( get_the_ID(), '_cgr_specialization', true );
            $inst = get_post_meta( get_the_ID(), '_cgr_institution', true );
            $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            ?>
            <div class="cgr-modern-card reveal-on-scroll">
                <div class="cgr-modern-frame">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" class="cgr-modern-img">
                        <?php else : ?>
                            <div class="cgr-modern-placeholder">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="cgr-modern-info">
                    <h3 class="cgr-modern-name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if($spec): ?><div class="cgr-modern-role"><?php echo esc_html($spec); ?></div><?php endif; ?>
                    <?php if($inst): ?><div class="cgr-modern-inst"><?php echo esc_html($inst); ?></div><?php endif; ?>
                    
                    <div class="cgr-modern-social">
                        <span class="cgr-social-icon"><i class="dashicons dashicons-email"></i></span>
                        <span class="cgr-social-icon"><i class="dashicons dashicons-share"></i></span>
                    </div>
                </div>
            </div>
            <?php
        }
        
        // We don't echo pagination here as the JS handles the button logic based on max_num_pages
        wp_reset_postdata();
    } else {
        echo '<p class="no-results" style="text-align:center; grid-column:1/-1;">No scientists found.</p>';
    }

    wp_die();
}
add_action( 'wp_ajax_cgr_filter_earth_scientists', 'cgr_filter_earth_scientists_ajax' );
add_action( 'wp_ajax_nopriv_cgr_filter_earth_scientists', 'cgr_filter_earth_scientists_ajax' );