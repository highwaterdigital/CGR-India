<?php
/**
 * Earth Leaders CPT and Logic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register Custom Post Type
function cgr_register_earth_leader_cpt() {
    error_log('CGR DEBUG: Registering Earth Leader CPT');
    $labels = array(
        'name'                  => _x( 'Earth Leaders', 'Post Type General Name', 'cgr-child' ),
        'singular_name'         => _x( 'Earth Leader', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'             => __( 'Earth Leaders', 'cgr-child' ),
        'name_admin_bar'        => __( 'Earth Leader', 'cgr-child' ),
        'archives'              => __( 'Earth Leader Archives', 'cgr-child' ),
        'attributes'            => __( 'Item Attributes', 'cgr-child' ),
        'parent_item_colon'     => __( 'Parent Item:', 'cgr-child' ),
        'all_items'             => __( 'All Earth Leaders', 'cgr-child' ),
        'add_new_item'          => __( 'Add New Earth Leader', 'cgr-child' ),
        'add_new'               => __( 'Add New', 'cgr-child' ),
        'new_item'              => __( 'New Earth Leader', 'cgr-child' ),
        'edit_item'             => __( 'Edit Earth Leader', 'cgr-child' ),
        'update_item'           => __( 'Update Earth Leader', 'cgr-child' ),
        'view_item'             => __( 'View Earth Leader', 'cgr-child' ),
        'view_items'            => __( 'View Items', 'cgr-child' ),
        'search_items'          => __( 'Search Earth Leader', 'cgr-child' ),
        'not_found'             => __( 'Not found', 'cgr-child' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'cgr-child' ),
        'featured_image'        => __( 'Leader Photo', 'cgr-child' ),
        'set_featured_image'    => __( 'Set leader photo', 'cgr-child' ),
        'remove_featured_image' => __( 'Remove leader photo', 'cgr-child' ),
        'use_featured_image'    => __( 'Use as leader photo', 'cgr-child' ),
    );
    $args = array(
        'label'                 => __( 'Earth Leader', 'cgr-child' ),
        'description'           => __( 'Earth Leaders who attended training', 'cgr-child' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail' ), // Title = Name
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-groups',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    register_post_type( 'earth_leader', $args );
}
add_action( 'init', 'cgr_register_earth_leader_cpt', 0 );

// 2. Add Meta Boxes
function cgr_earth_leader_meta_boxes() {
    add_meta_box(
        'cgr_earth_leader_details',
        __( 'Training Details', 'cgr-child' ),
        'cgr_earth_leader_meta_box_callback',
        'earth_leader',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cgr_earth_leader_meta_boxes' );

function cgr_earth_leader_meta_box_callback( $post ) {
    wp_nonce_field( 'cgr_earth_leader_save', 'cgr_earth_leader_nonce' );

    $year = get_post_meta( $post->ID, '_cgr_training_year', true );
    $district = get_post_meta( $post->ID, '_cgr_district', true );
    $org = get_post_meta( $post->ID, '_cgr_organization', true );
    $email = get_post_meta( $post->ID, '_cgr_email', true );
    
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    
    echo '<p><label for="cgr_training_year"><strong>' . __( 'Training Year', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="number" id="cgr_training_year" name="cgr_training_year" value="' . esc_attr( $year ) . '" class="widefat" placeholder="e.g. 2024" /></p>';

    echo '<p><label for="cgr_district"><strong>' . __( 'District', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_district" name="cgr_district" value="' . esc_attr( $district ) . '" class="widefat" /></p>';

    echo '<p><label for="cgr_email"><strong>' . __( 'Email (Required for Sync)', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="email" id="cgr_email" name="cgr_email" value="' . esc_attr( $email ) . '" class="widefat" /></p>';

    echo '<p><label for="cgr_organization"><strong>' . __( 'School / Organization', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_organization" name="cgr_organization" value="' . esc_attr( $org ) . '" class="widefat" /></p>';
    
    echo '</div>';
}

// 3. Save Meta Data
function cgr_save_earth_leader_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_earth_leader_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['cgr_earth_leader_nonce'], 'cgr_earth_leader_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['cgr_training_year'] ) ) {
        update_post_meta( $post_id, '_cgr_training_year', sanitize_text_field( $_POST['cgr_training_year'] ) );
    }
    if ( isset( $_POST['cgr_district'] ) ) {
        update_post_meta( $post_id, '_cgr_district', sanitize_text_field( $_POST['cgr_district'] ) );
    }
    if ( isset( $_POST['cgr_organization'] ) ) {
        update_post_meta( $post_id, '_cgr_organization', sanitize_text_field( $_POST['cgr_organization'] ) );
    }
    if ( isset( $_POST['cgr_email'] ) ) {
        update_post_meta( $post_id, '_cgr_email', sanitize_email( $_POST['cgr_email'] ) );
    }
}
add_action( 'save_post', 'cgr_save_earth_leader_meta' );

// 4. AJAX Handler for Filtering
function cgr_filter_earth_leaders_ajax() {
    // Verify nonce if you want strict security, but for public search usually open.
    // check_ajax_referer( 'cgr_leaders_nonce', 'nonce' );

    $year = isset( $_POST['year'] ) ? sanitize_text_field( $_POST['year'] ) : '';
    $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
    $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'title_asc';
    $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;

    $args = array(
        'post_type'      => 'earth_leader',
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

    // Search
    if ( ! empty( $search ) ) {
        $args['s'] = $search;
    }

    // Meta Query for Year
    if ( ! empty( $year ) ) {
        $args['meta_key'] = '_cgr_training_year';
        $args['orderby'] = array( 'meta_value_num' => 'DESC', 'title' => 'ASC' );
        $args['meta_query'] = array(
            array(
                'key'     => '_cgr_training_year',
                'value'   => $year,
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $args );

    error_log("CGR DEBUG: Earth Leaders Query found " . $query->found_posts . " posts.");

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $district = get_post_meta( get_the_ID(), '_cgr_district', true );
            $org = get_post_meta( get_the_ID(), '_cgr_organization', true );
            $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            ?>
            <div class="cgr-modern-card reveal-on-scroll">
                <div class="cgr-modern-frame">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" class="cgr-modern-img">
                        <?php else : ?>
                            <div class="cgr-modern-placeholder">
                                <span class="dashicons dashicons-groups"></span>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="cgr-modern-info">
                    <h3 class="cgr-modern-name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if($district): ?><div class="cgr-modern-role"><?php echo esc_html($district); ?></div><?php endif; ?>
                    <?php if($org): ?><div class="cgr-modern-inst"><?php echo esc_html($org); ?></div><?php endif; ?>
                    
                    <div class="cgr-modern-social">
                        <span class="cgr-social-icon"><i class="dashicons dashicons-email"></i></span>
                        <span class="cgr-social-icon"><i class="dashicons dashicons-share"></i></span>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p class="no-results" style="text-align:center; grid-column:1/-1;">No leaders found.</p>';
    }

    wp_die();
}
add_action( 'wp_ajax_cgr_filter_earth_leaders', 'cgr_filter_earth_leaders_ajax' );
add_action( 'wp_ajax_nopriv_cgr_filter_earth_leaders', 'cgr_filter_earth_leaders_ajax' );
