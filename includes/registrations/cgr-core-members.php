<?php
/**
 * CGR Core Members CPT and Logic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register Custom Post Type
function cgr_register_member_cpt() {
    $labels = array(
        'name'                  => _x( 'People', 'Post Type General Name', 'cgr-child' ),
        'singular_name'         => _x( 'Person', 'Post Type Singular Name', 'cgr-child' ),
        'menu_name'             => __( 'People', 'cgr-child' ),
        'all_items'             => __( 'All People', 'cgr-child' ),
        'add_new_item'          => __( 'Add New Person', 'cgr-child' ),
        'add_new'               => __( 'Add New', 'cgr-child' ),
        'new_item'              => __( 'New Person', 'cgr-child' ),
        'edit_item'             => __( 'Edit Person', 'cgr-child' ),
        'view_item'             => __( 'View Person', 'cgr-child' ),
        'search_items'          => __( 'Search People', 'cgr-child' ),
        'not_found'             => __( 'No people found', 'cgr-child' ),
        'featured_image'        => __( 'Profile Photo', 'cgr-child' ),
        'set_featured_image'    => __( 'Set photo', 'cgr-child' ),
    );
    $args = array(
        'label'                 => __( 'Person', 'cgr-child' ),
        'description'           => __( 'Core Team and Advisors', 'cgr-child' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 7,
        'menu_icon'             => 'dashicons-businessperson',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'has_archive'           => false, // Disabled to allow Elementor Page
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'rewrite'               => array( 'slug' => 'people' ),
    );
    register_post_type( 'cgr_member', $args );
}
add_action( 'init', 'cgr_register_member_cpt', 0 );

// 2. Add Meta Boxes
function cgr_member_meta_boxes() {
    add_meta_box(
        'cgr_member_details',
        __( 'Role Details', 'cgr-child' ),
        'cgr_member_meta_box_callback',
        'cgr_member',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cgr_member_meta_boxes' );

function cgr_member_meta_box_callback( $post ) {
    wp_nonce_field( 'cgr_member_save', 'cgr_member_nonce' );

    $designation = get_post_meta( $post->ID, '_cgr_designation', true );
    $role_type = get_post_meta( $post->ID, '_cgr_role_type', true ); // e.g. Core Team, Advisor
    $email = get_post_meta( $post->ID, '_cgr_email', true );
    
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">';
    
    echo '<p><label for="cgr_designation"><strong>' . __( 'Designation', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="text" id="cgr_designation" name="cgr_designation" value="' . esc_attr( $designation ) . '" class="widefat" placeholder="e.g. President, Secretary" /></p>';

    echo '<p><label for="cgr_email"><strong>' . __( 'Email (Required for Sync)', 'cgr-child' ) . '</strong></label><br>';
    echo '<input type="email" id="cgr_email" name="cgr_email" value="' . esc_attr( $email ) . '" class="widefat" /></p>';

    echo '<p><label for="cgr_role_type"><strong>' . __( 'Group / Category', 'cgr-child' ) . '</strong></label><br>';
    echo '<select id="cgr_role_type" name="cgr_role_type" class="widefat">';
    $options = array(
        'Board of Trustees'    => __( 'Board of Trustees', 'cgr-child' ),
        'Advisory Board'       => __( 'Advisory Board', 'cgr-child' ),
        'Expert Panel Members' => __( 'Core Members', 'cgr-child' ),
    );
    foreach ( $options as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '" ' . selected( $role_type, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select></p>';
    
    echo '</div>';
}

// 3. Save Meta Data
function cgr_save_member_meta( $post_id ) {
    if ( ! isset( $_POST['cgr_member_nonce'] ) ) { return; }
    if ( ! wp_verify_nonce( $_POST['cgr_member_nonce'], 'cgr_member_save' ) ) { return; }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    if ( isset( $_POST['cgr_designation'] ) ) {
        update_post_meta( $post_id, '_cgr_designation', sanitize_text_field( $_POST['cgr_designation'] ) );
    }
    if ( isset( $_POST['cgr_role_type'] ) ) {
        update_post_meta( $post_id, '_cgr_role_type', sanitize_text_field( $_POST['cgr_role_type'] ) );
    }
    if ( isset( $_POST['cgr_email'] ) ) {
        update_post_meta( $post_id, '_cgr_email', sanitize_email( $_POST['cgr_email'] ) );
    }
}
add_action( 'save_post', 'cgr_save_member_meta' );

// 4. AJAX Handler for Filtering
function cgr_filter_cgr_members_ajax() {
    $group = isset( $_POST['group'] ) ? sanitize_text_field( $_POST['group'] ) : '';
    
    $args = array(
        'post_type'      => 'cgr_member',
        'posts_per_page' => -1, // Show all for team page usually
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );

    if ( ! empty( $group ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => '_cgr_role_type',
                'value'   => $group,
                'compare' => '=',
            ),
        );
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $desig = get_post_meta( get_the_ID(), '_cgr_designation', true );
            $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
            ?>
            <div class="cgr-modern-card reveal-on-scroll">
                <div class="cgr-modern-frame">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( $thumb_url ) : ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>" class="cgr-modern-img">
                        <?php else : ?>
                            <div class="cgr-modern-placeholder">
                                <span class="dashicons dashicons-businessperson"></span>
                            </div>
                        <?php endif; ?>
                    </a>
                    <div class="cgr-modern-decoration">
                        <span class="dashicons dashicons-awards"></span>
                    </div>
                </div>
                <div class="cgr-modern-info">
                    <h3 class="cgr-modern-name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if($desig): ?><div class="cgr-modern-role"><?php echo esc_html($desig); ?></div><?php endif; ?>
                    
                    <div class="cgr-modern-social">
                        <span class="cgr-social-icon"><i class="dashicons dashicons-email"></i></span>
                        <span class="cgr-social-icon"><i class="dashicons dashicons-linkedin"></i></span>
                    </div>
                </div>
            </div>
            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p class="no-results" style="text-align:center; grid-column:1/-1;">No members found.</p>';
    }

    wp_die();
}
add_action( 'wp_ajax_cgr_filter_cgr_members', 'cgr_filter_cgr_members_ajax' );
add_action( 'wp_ajax_nopriv_cgr_filter_cgr_members', 'cgr_filter_cgr_members_ajax' );
