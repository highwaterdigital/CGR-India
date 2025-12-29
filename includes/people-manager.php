<?php
/**
 * People Directory Manager
 *
 * Registers the people CPT, taxonomy sections, and meta fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register CPT and taxonomy for people.
 */
function cgr_register_people_cpt() {
    $labels = array(
        'name'               => __( 'People', 'cgr-child' ),
        'singular_name'      => __( 'Person', 'cgr-child' ),
        'add_new'            => __( 'Add Person', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Person', 'cgr-child' ),
        'edit_item'          => __( 'Edit Person', 'cgr-child' ),
        'new_item'           => __( 'New Person', 'cgr-child' ),
        'view_item'          => __( 'View Person', 'cgr-child' ),
        'search_items'       => __( 'Search People', 'cgr-child' ),
        'not_found'          => __( 'No people found', 'cgr-child' ),
        'not_found_in_trash' => __( 'No people found in trash', 'cgr-child' ),
        'menu_name'          => __( 'People', 'cgr-child' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-groups',
        'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
    );
    register_post_type( 'cgr_person', $args );

    register_taxonomy( 'people_section', 'cgr_person', array(
        'label'             => __( 'Sections', 'cgr-child' ),
        'public'            => false,
        'show_ui'           => true,
        'hierarchical'      => false,
        'show_in_menu'      => false,
        'show_admin_column' => true,
        'rewrite'           => false,
    ) );
}
add_action( 'init', 'cgr_register_people_cpt' );

/**
 * Ensure default sections exist in the desired order.
 */
function cgr_people_seed_sections() {
    $sections = array(
        'board-of-trustees'      => __( 'Board of Trustees', 'cgr-child' ),
        'advisory-board'         => __( 'Advisory Board', 'cgr-child' ),
        'expert-panel-members'   => __( 'Core Members', 'cgr-child' ),
    );

    foreach ( $sections as $slug => $name ) {
        if ( ! term_exists( $slug, 'people_section' ) ) {
            wp_insert_term( $name, 'people_section', array( 'slug' => $slug ) );
        }
    }
}
add_action( 'init', 'cgr_people_seed_sections', 20 );

/**
 * Meta box for person details.
 */
function cgr_people_meta_boxes() {
    add_meta_box(
        'cgr-people-details',
        __( 'Profile Details', 'cgr-child' ),
        'cgr_render_people_meta_box',
        'cgr_person',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cgr_people_meta_boxes' );

function cgr_render_people_meta_box( $post ) {
    wp_nonce_field( 'cgr_people_meta_nonce', 'cgr_people_meta_nonce' );

    $designation = get_post_meta( $post->ID, '_cgr_person_designation', true );
    $organization = get_post_meta( $post->ID, '_cgr_person_organization', true );
    $notes = get_post_meta( $post->ID, '_cgr_person_notes', true );
    ?>
    <p>
        <label for="cgr-person-designation"><?php esc_html_e( 'Designation / Role', 'cgr-child' ); ?></label><br>
        <input type="text" id="cgr-person-designation" name="cgr_person_designation" class="widefat" value="<?php echo esc_attr( $designation ); ?>">
    </p>
    <p>
        <label for="cgr-person-organization"><?php esc_html_e( 'Organization / Additional info', 'cgr-child' ); ?></label><br>
        <input type="text" id="cgr-person-organization" name="cgr_person_organization" class="widefat" value="<?php echo esc_attr( $organization ); ?>">
    </p>
    <p>
        <label for="cgr-person-notes"><?php esc_html_e( 'Notes / Credentials', 'cgr-child' ); ?></label><br>
        <textarea id="cgr-person-notes" name="cgr_person_notes" rows="4" class="widefat"><?php echo esc_textarea( $notes ); ?></textarea>
    </p>
    <?php
}

/**
 * Save person meta.
 */
function cgr_save_person_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['cgr_people_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cgr_people_meta_nonce'], 'cgr_people_meta_nonce' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'designation'  => 'sanitize_text_field',
        'organization' => 'sanitize_text_field',
        'notes'        => 'sanitize_textarea_field',
    );

    foreach ( $fields as $key => $sanitize ) {
        $value = isset( $_POST[ "cgr_person_{$key}" ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ "cgr_person_{$key}" ] ) ) : '';
        $meta_key = '_cgr_person_' . $key;
        if ( '' === $value ) {
            delete_post_meta( $post_id, $meta_key );
        } else {
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post', 'cgr_save_person_meta' );

/**
 * Customize admin columns.
 */
function cgr_people_columns( $columns ) {
    $columns = array(
        'cb'            => $columns['cb'],
        'title'         => __( 'Name', 'cgr-child' ),
        'designation'   => __( 'Designation', 'cgr-child' ),
        'section'       => __( 'Section', 'cgr-child' ),
        'organization'  => __( 'Organization', 'cgr-child' ),
        'date'          => $columns['date'],
    );
    return $columns;
}
add_filter( 'manage_cgr_person_posts_columns', 'cgr_people_columns' );

function cgr_people_column_content( $column, $post_id ) {
    if ( 'designation' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_cgr_person_designation', true ) );
    }
    if ( 'organization' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_cgr_person_organization', true ) );
    }
    if ( 'section' === $column ) {
        $terms = get_the_terms( $post_id, 'people_section' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
        }
    }
}
add_action( 'manage_cgr_person_posts_custom_column', 'cgr_people_column_content', 10, 2 );
