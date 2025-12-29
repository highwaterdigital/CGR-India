<?php
/**
 * Awards & Achievements Manager
 *
 * Provides the CPT, metadata handling, and admin dashboard used to register awards.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Awards CPT used to keep the data structured.
 */
function cgr_register_awards_cpt() {
    $labels = array(
        'name'               => __( 'Awards & Achievements', 'cgr-child' ),
        'singular_name'      => __( 'Award', 'cgr-child' ),
        'add_new'            => __( 'Add Award', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Award', 'cgr-child' ),
        'edit_item'          => __( 'Edit Award', 'cgr-child' ),
        'new_item'           => __( 'New Award', 'cgr-child' ),
        'view_item'          => __( 'View Award', 'cgr-child' ),
        'search_items'       => __( 'Search Awards', 'cgr-child' ),
        'not_found'          => __( 'No awards found', 'cgr-child' ),
        'not_found_in_trash' => __( 'No awards found in trash', 'cgr-child' ),
        'all_items'          => __( 'All Awards', 'cgr-child' ),
        'menu_name'          => __( 'Awards', 'cgr-child' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'exclude_from_search'=> true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-awards',
        'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
    );

    register_post_type( 'cgr_award', $args );
}
add_action( 'init', 'cgr_register_awards_cpt' );

/**
 * Add details meta box for award information.
 */
function cgr_award_meta_boxes() {
    add_meta_box(
        'cgr-award-details',
        __( 'Award Details', 'cgr-child' ),
        'cgr_render_award_meta_box',
        'cgr_award',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cgr_award_meta_boxes' );

/**
 * Meta box callback output.
 */
function cgr_render_award_meta_box( $post ) {
    wp_nonce_field( 'cgr_award_meta_nonce', 'cgr_award_meta_nonce' );

    $year    = get_post_meta( $post->ID, '_cgr_award_year', true );
    $issuer  = get_post_meta( $post->ID, '_cgr_award_issuer', true );
    $type    = get_post_meta( $post->ID, '_cgr_award_type', true );
    $link    = get_post_meta( $post->ID, '_cgr_award_link', true );
    ?>
    <p>
        <label for="cgr-award-year"><?php _e( 'Year', 'cgr-child' ); ?></label>
        <input type="text" id="cgr-award-year" name="cgr_award_year" value="<?php echo esc_attr( $year ); ?>" class="widefat">
    </p>
    <p>
        <label for="cgr-award-issuer"><?php _e( 'Issued by', 'cgr-child' ); ?></label>
        <input type="text" id="cgr-award-issuer" name="cgr_award_issuer" value="<?php echo esc_attr( $issuer ); ?>" class="widefat">
    </p>
    <p>
        <label for="cgr-award-type"><?php _e( 'Type / Category', 'cgr-child' ); ?></label>
        <input type="text" id="cgr-award-type" name="cgr_award_type" value="<?php echo esc_attr( $type ); ?>" class="widefat">
    </p>
    <p>
        <label for="cgr-award-link"><?php _e( 'External link (optional)', 'cgr-child' ); ?></label>
        <input type="url" id="cgr-award-link" name="cgr_award_link" value="<?php echo esc_attr( $link ); ?>" class="widefat">
    </p>
    <?php
}

/**
 * Save meta box data.
 */
function cgr_save_award_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['cgr_award_meta_nonce'] ) && ! wp_verify_nonce( $_POST['cgr_award_meta_nonce'], 'cgr_award_meta_nonce' ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $fields = array(
        'year'   => 'sanitize_text_field',
        'issuer' => 'sanitize_text_field',
        'type'   => 'sanitize_text_field',
        'link'   => 'esc_url_raw',
    );

    foreach ( $fields as $field => $sanitize_callback ) {
        $meta_key = '_cgr_award_' . $field;
        $value    = isset( $_POST[ "cgr_award_{$field}" ] ) ? call_user_func( $sanitize_callback, $_POST[ "cgr_award_{$field}" ] ) : '';
        if ( '' === $value ) {
            delete_post_meta( $post_id, $meta_key );
        } else {
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
add_action( 'save_post', 'cgr_save_award_meta' );

/**
 * Customize admin columns.
 */
function cgr_award_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $label ) {
        if ( 'title' === $key ) {
            $new_columns['cb']    = $columns['cb'];
            $new_columns['title'] = __( 'Award / Achievement', 'cgr-child' );
            $new_columns['year']  = __( 'Year', 'cgr-child' );
            $new_columns['type']  = __( 'Type', 'cgr-child' );
            $new_columns['issuer'] = __( 'Issued by', 'cgr-child' );
        }
        if ( 'date' === $key ) {
            $new_columns['date'] = $label;
        }
    }
    return $new_columns;
}
add_filter( 'manage_cgr_award_posts_columns', 'cgr_award_columns' );

function cgr_award_column_content( $column, $post_id ) {
    if ( 'year' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_cgr_award_year', true ) );
    }
    if ( 'issuer' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_cgr_award_issuer', true ) );
    }
    if ( 'type' === $column ) {
        echo esc_html( get_post_meta( $post_id, '_cgr_award_type', true ) );
    }
}
add_action( 'manage_cgr_award_posts_custom_column', 'cgr_award_column_content', 10, 2 );

/**
 * Add a dashboard page for quick award registration.
 */
function cgr_register_awards_dashboard_menu() {
    add_submenu_page(
        'edit.php?post_type=cgr_award',
        __( 'Awards Dashboard', 'cgr-child' ),
        __( 'Awards Dashboard', 'cgr-child' ),
        'edit_posts',
        'cgr-awards-dashboard',
        'cgr_render_awards_dashboard'
    );
}
add_action( 'admin_menu', 'cgr_register_awards_dashboard_menu' );

/**
 * Render dashboard page.
 */
function cgr_render_awards_dashboard() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'You do not have permission to access this page.', 'cgr-child' ) );
    }

    $status_message = '';
    $status_class   = 'notice notice-success';
    $saved_values   = array(
        'title'   => '',
        'summary' => '',
        'details' => '',
        'year'    => '',
        'issuer'  => '',
        'type'    => '',
        'link'    => '',
    );

    if ( isset( $_POST['cgr_award_dashboard_nonce'] ) && wp_verify_nonce( $_POST['cgr_award_dashboard_nonce'], 'cgr_award_dashboard' ) ) {
        $saved_values = array(
            'title'   => sanitize_text_field( wp_unslash( $_POST['cgr_award_title'] ?? '' ) ),
            'summary' => sanitize_text_field( wp_unslash( $_POST['cgr_award_summary'] ?? '' ) ),
            'details' => wp_kses_post( wp_unslash( $_POST['cgr_award_details'] ?? '' ) ),
            'year'    => sanitize_text_field( wp_unslash( $_POST['cgr_award_year'] ?? '' ) ),
            'issuer'  => sanitize_text_field( wp_unslash( $_POST['cgr_award_issuer'] ?? '' ) ),
            'type'    => sanitize_text_field( wp_unslash( $_POST['cgr_award_type'] ?? '' ) ),
            'link'    => esc_url_raw( wp_unslash( $_POST['cgr_award_link'] ?? '' ) ),
        );
        $title   = $saved_values['title'];
        $details = $saved_values['details'];
        $summary = $saved_values['summary'];
        $year    = $saved_values['year'];
        $issuer  = $saved_values['issuer'];
        $type    = $saved_values['type'];
        $link    = $saved_values['link'];

        if ( empty( $title ) ) {
            $status_message = __( 'Please provide a title for the award.', 'cgr-child' );
            $status_class   = 'notice notice-error';
        } else {
            $post_id = wp_insert_post( array(
                'post_type'    => 'cgr_award',
                'post_title'   => $title,
                'post_content' => $details,
                'post_excerpt' => $summary,
                'post_status'  => 'publish',
            ), true );

            if ( is_wp_error( $post_id ) ) {
                $status_message = __( 'There was an error creating the award. Please try again.', 'cgr-child' );
                $status_class   = 'notice notice-error';
            } else {
                update_post_meta( $post_id, '_cgr_award_year', $year );
                update_post_meta( $post_id, '_cgr_award_issuer', $issuer );
                update_post_meta( $post_id, '_cgr_award_type', $type );
                update_post_meta( $post_id, '_cgr_award_link', $link );

                $status_message = __( 'Award saved successfully.', 'cgr-child' );
                $status_class   = 'notice notice-success';
                $saved_values   = array_fill_keys( array_keys( $saved_values ), '' );
            }
        }
    }

    $awards = get_posts( array(
        'post_type'      => 'cgr_award',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_key'       => '_cgr_award_year',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ) );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Awards & Achievements Dashboard', 'cgr-child' ); ?></h1>
        <?php if ( $status_message ) : ?>
            <div class="<?php echo esc_attr( $status_class ); ?>">
                <p><?php echo esc_html( $status_message ); ?></p>
            </div>
        <?php endif; ?>

        <p class="description">
            <?php esc_html_e( 'Use this tool to quickly register new awards or achievements. The shortcode [cgr_awards] renders the list on the frontend.', 'cgr-child' ); ?>
        </p>

        <form method="post" style="max-width:900px;">
            <?php wp_nonce_field( 'cgr_award_dashboard', 'cgr_award_dashboard_nonce' ); ?>
            <div class="cgr-awards-dashboard-grid">
                <p>
                    <label for="cgr-award-dashboard-title"><?php esc_html_e( 'Title', 'cgr-child' ); ?></label><br>
                    <input type="text" id="cgr-award-dashboard-title" name="cgr_award_title" value="<?php echo esc_attr( $saved_values['title'] ); ?>" class="regular-text" required>
                </p>
                <p>
                    <label for="cgr-award-dashboard-summary"><?php esc_html_e( 'Summary (used on cards)', 'cgr-child' ); ?></label><br>
                    <input type="text" id="cgr-award-dashboard-summary" name="cgr_award_summary" value="<?php echo esc_attr( $saved_values['summary'] ); ?>" class="regular-text">
                </p>
                <p>
                    <label for="cgr-award-dashboard-year"><?php esc_html_e( 'Year', 'cgr-child' ); ?></label><br>
                    <input type="text" id="cgr-award-dashboard-year" name="cgr_award_year" value="<?php echo esc_attr( $saved_values['year'] ); ?>" class="regular-text">
                </p>
                <p>
                    <label for="cgr-award-dashboard-type"><?php esc_html_e( 'Type / Category', 'cgr-child' ); ?></label><br>
                    <input type="text" id="cgr-award-dashboard-type" name="cgr_award_type" value="<?php echo esc_attr( $saved_values['type'] ); ?>" class="regular-text">
                </p>
                <p>
                    <label for="cgr-award-dashboard-issuer"><?php esc_html_e( 'Issued by', 'cgr-child' ); ?></label><br>
                    <input type="text" id="cgr-award-dashboard-issuer" name="cgr_award_issuer" value="<?php echo esc_attr( $saved_values['issuer'] ); ?>" class="regular-text">
                </p>
                <p>
                    <label for="cgr-award-dashboard-link"><?php esc_html_e( 'External link', 'cgr-child' ); ?></label><br>
                    <input type="url" id="cgr-award-dashboard-link" name="cgr_award_link" value="<?php echo esc_attr( $saved_values['link'] ); ?>" class="regular-text">
                </p>
                <p class="cgr-award-dashboard-details">
                    <label for="cgr-award-dashboard-details"><?php esc_html_e( 'Full details', 'cgr-child' ); ?></label><br>
                    <textarea id="cgr-award-dashboard-details" name="cgr_award_details" rows="5" class="large-text"><?php echo esc_textarea( $saved_values['details'] ); ?></textarea>
                </p>
            </div>
            <?php submit_button( __( 'Save Award', 'cgr-child' ) ); ?>
        </form>

        <h2><?php esc_html_e( 'Existing Awards', 'cgr-child' ); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'cgr-child' ); ?></th>
                    <th><?php esc_html_e( 'Year', 'cgr-child' ); ?></th>
                    <th><?php esc_html_e( 'Type', 'cgr-child' ); ?></th>
                    <th><?php esc_html_e( 'Issuer', 'cgr-child' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'cgr-child' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $awards ) : ?>
                    <?php foreach ( $awards as $award ) : ?>
                        <tr>
                            <td><?php echo esc_html( get_the_title( $award ) ); ?></td>
                            <td><?php echo esc_html( get_post_meta( $award->ID, '_cgr_award_year', true ) ); ?></td>
                            <td><?php echo esc_html( get_post_meta( $award->ID, '_cgr_award_type', true ) ); ?></td>
                            <td><?php echo esc_html( get_post_meta( $award->ID, '_cgr_award_issuer', true ) ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $award->ID ) ); ?>"><?php esc_html_e( 'Edit', 'cgr-child' ); ?></a>
                                |
                                <a href="<?php echo esc_url( get_permalink( $award ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'cgr-child' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php esc_html_e( 'No awards registered yet.', 'cgr-child' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <style>
        .cgr-awards-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .cgr-award-dashboard-details {
            grid-column: 1 / -1;
        }
    </style>
    <?php
}
