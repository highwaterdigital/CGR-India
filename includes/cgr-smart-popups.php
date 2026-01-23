<?php
/**
 * CGR Smart Popups
 *
 * Provides the CPT, scheduling metadata, admin dashboard, and frontend rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Smart Popups CPT.
 */
function cgr_register_smart_popups_cpt() {
    $labels = array(
        'name'               => __( 'Smart Popups', 'cgr-child' ),
        'singular_name'      => __( 'Smart Popup', 'cgr-child' ),
        'add_new'            => __( 'Add Popup', 'cgr-child' ),
        'add_new_item'       => __( 'Add New Popup', 'cgr-child' ),
        'edit_item'          => __( 'Edit Popup', 'cgr-child' ),
        'new_item'           => __( 'New Popup', 'cgr-child' ),
        'view_item'          => __( 'View Popup', 'cgr-child' ),
        'search_items'       => __( 'Search Popups', 'cgr-child' ),
        'not_found'          => __( 'No popups found', 'cgr-child' ),
        'not_found_in_trash' => __( 'No popups found in trash', 'cgr-child' ),
        'all_items'          => __( 'All Popups', 'cgr-child' ),
        'menu_name'          => __( 'Smart Popups', 'cgr-child' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'exclude_from_search'=> true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_icon'          => 'dashicons-format-status',
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'rewrite'            => false,
        'show_in_rest'       => true,
    );

    register_post_type( 'cgr_popup', $args );
}
add_action( 'init', 'cgr_register_smart_popups_cpt' );

/**
 * Ensure Elementor can edit the popup CPT when active.
 */
function cgr_enable_popup_elementor_support( $post_types ) {
    if ( ! in_array( 'cgr_popup', $post_types, true ) ) {
        $post_types[] = 'cgr_popup';
    }
    return $post_types;
}
add_filter( 'elementor_cpt_support', 'cgr_enable_popup_elementor_support' );

/**
 * Default popup meta values.
 */
function cgr_popup_default_meta() {
    return array(
        'status'      => 'draft',
        'target_mode' => 'all',
        'target_ids'  => array(),
        'start'       => '',
        'end'         => '',
        'next_mode'   => 'none',
        'next_value'  => '',
        'frequency'   => 'always',
        'position'    => 'center',
        'width'       => '',
        'height'      => '',
    );
}

/**
 * Normalize a datetime-local input to a MySQL datetime string.
 */
function cgr_popup_normalize_datetime( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    $value   = str_replace( 'T', ' ', $value );
    $formats = array(
        'Y-m-d H:i',
        'Y-m-d H:i:s',
        'd-m-Y H:i',
        'd/m/Y H:i',
        'd-m-Y',
        'd/m/Y',
    );

    foreach ( $formats as $format ) {
        $date = date_create_from_format( $format, $value, wp_timezone() );
        if ( $date ) {
            return $date->format( 'Y-m-d H:i:s' );
        }
    }

    $timestamp = strtotime( $value );
    if ( ! $timestamp ) {
        return '';
    }

    return wp_date( 'Y-m-d H:i:s', $timestamp );
}

/**
 * Format stored datetime for datetime-local inputs.
 */
function cgr_popup_format_datetime( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    $timestamp = strtotime( $value );
    if ( ! $timestamp ) {
        return '';
    }

    return wp_date( 'Y-m-d\TH:i', $timestamp );
}

/**
 * Sanitize popup position value.
 */
function cgr_popup_sanitize_position( $value ) {
    $value    = sanitize_text_field( (string) $value );
    $allowed  = array( 'center', 'bottom-right', 'bottom-left' );
    return in_array( $value, $allowed, true ) ? $value : 'center';
}

/**
 * Sanitize CSS size values (px, %, vw, vh, rem, em).
 */
function cgr_popup_sanitize_css_size( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) {
        return '';
    }

    if ( 'auto' === strtolower( $value ) ) {
        return 'auto';
    }

    if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
        return $value . 'px';
    }

    if ( preg_match( '/^\d+(\.\d+)?(px|%|vw|vh|rem|em)$/', $value ) ) {
        return $value;
    }

    return '';
}

/**
 * Fetch popup meta with defaults applied.
 */
function cgr_popup_get_meta( $post_id ) {
    $defaults = cgr_popup_default_meta();

    $meta = array(
        'status'      => get_post_meta( $post_id, '_cgr_popup_status', true ),
        'target_mode' => get_post_meta( $post_id, '_cgr_popup_target_mode', true ),
        'target_ids'  => get_post_meta( $post_id, '_cgr_popup_target_ids', true ),
        'start'       => get_post_meta( $post_id, '_cgr_popup_start', true ),
        'end'         => get_post_meta( $post_id, '_cgr_popup_end', true ),
        'next_mode'   => get_post_meta( $post_id, '_cgr_popup_next_mode', true ),
        'next_value'  => get_post_meta( $post_id, '_cgr_popup_next_value', true ),
        'frequency'   => get_post_meta( $post_id, '_cgr_popup_frequency', true ),
        'position'    => get_post_meta( $post_id, '_cgr_popup_position', true ),
        'width'       => get_post_meta( $post_id, '_cgr_popup_width', true ),
        'height'      => get_post_meta( $post_id, '_cgr_popup_height', true ),
    );

    foreach ( $defaults as $key => $default ) {
        if ( '' === $meta[ $key ] || null === $meta[ $key ] ) {
            $meta[ $key ] = $default;
        }
    }

    $meta['target_ids'] = array_filter( array_map( 'absint', (array) $meta['target_ids'] ) );
    $meta['position']   = cgr_popup_sanitize_position( $meta['position'] );
    $meta['width']      = cgr_popup_sanitize_css_size( $meta['width'] );
    $meta['height']     = cgr_popup_sanitize_css_size( $meta['height'] );

    return $meta;
}

/**
 * Determine the effective status of a popup based on time window.
 */
function cgr_popup_get_effective_status( $post_id, $meta = array(), $now = null ) {
    $now  = $now ? (int) $now : current_time( 'timestamp' );
    $meta = $meta ? $meta : cgr_popup_get_meta( $post_id );

    $status = $meta['status'];
    if ( 'draft' === $status || 'draft' === get_post_status( $post_id ) ) {
        return 'draft';
    }

    $start = $meta['start'] ? strtotime( $meta['start'] ) : null;
    $end   = $meta['end'] ? strtotime( $meta['end'] ) : null;

    if ( $end && $now > $end ) {
        return 'expired';
    }

    if ( $start && $now < $start ) {
        return 'scheduled';
    }

    return 'active';
}

/**
 * Add popup meta boxes.
 */
function cgr_popup_meta_boxes() {
    add_meta_box(
        'cgr-popup-schedule',
        __( 'CGR Smart Popup Settings', 'cgr-child' ),
        'cgr_render_popup_meta_box',
        'cgr_popup',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'cgr_popup_meta_boxes' );

/**
 * Render common popup fields.
 */
function cgr_render_popup_fields( $meta, $id_prefix ) {
    $statuses   = array(
        'draft'     => __( 'Draft', 'cgr-child' ),
        'active'    => __( 'Active', 'cgr-child' ),
        'scheduled' => __( 'Scheduled', 'cgr-child' ),
    );
    $frequencies = array(
        'always'  => __( 'Always show', 'cgr-child' ),
        'session' => __( 'Once per session', 'cgr-child' ),
        'day'     => __( 'Once per day', 'cgr-child' ),
        'week'    => __( 'Once per week', 'cgr-child' ),
        'month'   => __( 'Once per month', 'cgr-child' ),
        'once'    => __( 'Once ever', 'cgr-child' ),
    );
    $positions = array(
        'center'       => __( 'Center', 'cgr-child' ),
        'bottom-right' => __( 'Bottom right', 'cgr-child' ),
        'bottom-left'  => __( 'Bottom left', 'cgr-child' ),
    );

    $pages = get_posts( array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    $posts = get_posts( array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    $start_value = cgr_popup_format_datetime( $meta['start'] );
    $end_value   = cgr_popup_format_datetime( $meta['end'] );
    $next_mode   = $meta['next_mode'];
    $next_value  = $meta['next_value'];
    $next_fixed  = 'fixed' === $next_mode ? cgr_popup_format_datetime( $next_value ) : '';
    $next_days   = 'interval' === $next_mode ? absint( $next_value ) : '';
    ?>
    <div class="cgr-popup-fields">
        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Status', 'cgr-child' ); ?></h3>
            <div class="cgr-status-toggle" role="group" aria-label="<?php esc_attr_e( 'Popup status', 'cgr-child' ); ?>">
                <?php foreach ( $statuses as $value => $label ) : ?>
                    <label class="cgr-status-option">
                        <input type="radio" name="cgr_popup_status" value="<?php echo esc_attr( $value ); ?>" <?php checked( $meta['status'], $value ); ?>>
                        <span><?php echo esc_html( $label ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Display Logic', 'cgr-child' ); ?></h3>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Target mode', 'cgr-child' ); ?></span>
                <select name="cgr_popup_target_mode" class="widefat">
                    <option value="all" <?php selected( $meta['target_mode'], 'all' ); ?>><?php esc_html_e( 'All pages', 'cgr-child' ); ?></option>
                    <option value="specific" <?php selected( $meta['target_mode'], 'specific' ); ?>><?php esc_html_e( 'Only selected pages/posts', 'cgr-child' ); ?></option>
                </select>
            </label>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Pages/Posts', 'cgr-child' ); ?></span>
                <select id="<?php echo esc_attr( $id_prefix ); ?>target-ids" class="widefat cgr-popup-select2" name="cgr_popup_target_ids[]" multiple>
                    <?php if ( $pages ) : ?>
                        <optgroup label="<?php esc_attr_e( 'Pages', 'cgr-child' ); ?>">
                            <?php foreach ( $pages as $page ) : ?>
                                <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( in_array( $page->ID, $meta['target_ids'], true ) ); ?>>
                                    <?php echo esc_html( $page->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if ( $posts ) : ?>
                        <optgroup label="<?php esc_attr_e( 'Posts', 'cgr-child' ); ?>">
                            <?php foreach ( $posts as $post_item ) : ?>
                                <option value="<?php echo esc_attr( $post_item->ID ); ?>" <?php selected( in_array( $post_item->ID, $meta['target_ids'], true ) ); ?>>
                                    <?php echo esc_html( $post_item->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </label>
        </div>

        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Temporal Control', 'cgr-child' ); ?></h3>
            <div class="cgr-field">
                <span><?php esc_html_e( 'Start Date/Time', 'cgr-child' ); ?></span>
                <input type="datetime-local" id="<?php echo esc_attr( $id_prefix ); ?>start" name="cgr_popup_start" value="<?php echo esc_attr( $start_value ); ?>" class="regular-text">
            </div>
            <div class="cgr-field">
                <span><?php esc_html_e( 'Expiry Date/Time', 'cgr-child' ); ?></span>
                <input type="datetime-local" id="<?php echo esc_attr( $id_prefix ); ?>end" name="cgr_popup_end" value="<?php echo esc_attr( $end_value ); ?>" class="regular-text">
            </div>
        </div>

        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Smart Scheduling', 'cgr-child' ); ?></h3>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Next scheduled occurrence', 'cgr-child' ); ?></span>
                <select name="cgr_popup_next_mode" class="widefat cgr-popup-next-mode" data-cgr-next-mode>
                    <option value="none" <?php selected( $next_mode, 'none' ); ?>><?php esc_html_e( 'None', 'cgr-child' ); ?></option>
                    <option value="interval" <?php selected( $next_mode, 'interval' ); ?>><?php esc_html_e( 'Reappear after dismissal', 'cgr-child' ); ?></option>
                    <option value="fixed" <?php selected( $next_mode, 'fixed' ); ?>><?php esc_html_e( 'Specific future date', 'cgr-child' ); ?></option>
                </select>
            </label>
            <div class="cgr-field cgr-popup-next-interval" data-cgr-next-interval>
                <span><?php esc_html_e( 'Days after dismissal', 'cgr-child' ); ?></span>
                <input type="number" min="1" id="<?php echo esc_attr( $id_prefix ); ?>next-interval" name="cgr_popup_next_value_interval" value="<?php echo esc_attr( $next_days ); ?>" class="small-text">
            </div>
            <div class="cgr-field cgr-popup-next-fixed" data-cgr-next-fixed>
                <span><?php esc_html_e( 'Specific date/time', 'cgr-child' ); ?></span>
                <input type="datetime-local" id="<?php echo esc_attr( $id_prefix ); ?>next-fixed" name="cgr_popup_next_value_fixed" value="<?php echo esc_attr( $next_fixed ); ?>" class="regular-text">
            </div>
        </div>

        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Frequency Cap', 'cgr-child' ); ?></h3>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Show frequency', 'cgr-child' ); ?></span>
                <select name="cgr_popup_frequency" class="widefat">
                    <?php foreach ( $frequencies as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $meta['frequency'], $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <div class="cgr-card cgr-card--tight">
            <h3><?php esc_html_e( 'Layout', 'cgr-child' ); ?></h3>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Popup position', 'cgr-child' ); ?></span>
                <select name="cgr_popup_position" class="widefat">
                    <?php foreach ( $positions as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $meta['position'], $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Popup width (e.g., 640px or 70vw)', 'cgr-child' ); ?></span>
                <input type="text" name="cgr_popup_width" value="<?php echo esc_attr( $meta['width'] ); ?>" class="regular-text" placeholder="960px">
            </label>
            <label class="cgr-field">
                <span><?php esc_html_e( 'Popup height (e.g., 420px or 70vh)', 'cgr-child' ); ?></span>
                <input type="text" name="cgr_popup_height" value="<?php echo esc_attr( $meta['height'] ); ?>" class="regular-text" placeholder="auto">
            </label>
        </div>
    </div>
    <?php
}

/**
 * Render the meta box.
 */
function cgr_render_popup_meta_box( $post ) {
    wp_nonce_field( 'cgr_popup_meta_nonce', 'cgr_popup_meta_nonce' );

    $meta             = cgr_popup_get_meta( $post->ID );
    $effective_status = cgr_popup_get_effective_status( $post->ID, $meta );
    ?>
    <div class="cgr-popup-meta">
        <p class="cgr-popup-status-note">
            <?php esc_html_e( 'Current status:', 'cgr-child' ); ?>
            <span class="cgr-status-badge cgr-status-<?php echo esc_attr( $effective_status ); ?>">
                <?php echo esc_html( ucfirst( $effective_status ) ); ?>
            </span>
        </p>
        <?php cgr_render_popup_fields( $meta, 'cgr-popup-' ); ?>
    </div>
    <?php
}

/**
 * Save popup meta box data.
 */
function cgr_save_popup_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $watched_fields = array(
        'cgr_popup_status',
        'cgr_popup_target_mode',
        'cgr_popup_target_ids',
        'cgr_popup_start',
        'cgr_popup_end',
        'cgr_popup_next_mode',
        'cgr_popup_next_value_interval',
        'cgr_popup_next_value_fixed',
        'cgr_popup_frequency',
        'cgr_popup_position',
        'cgr_popup_width',
        'cgr_popup_height',
    );

    $has_fields = false;
    foreach ( $watched_fields as $field ) {
        if ( array_key_exists( $field, $_POST ) ) {
            $has_fields = true;
            break;
        }
    }

    if ( ! $has_fields ) {
        return;
    }

    if ( ! isset( $_POST['cgr_popup_meta_nonce'] ) || ! wp_verify_nonce( $_POST['cgr_popup_meta_nonce'], 'cgr_popup_meta_nonce' ) ) {
        return;
    }

    $status = isset( $_POST['cgr_popup_status'] ) ? sanitize_text_field( wp_unslash( $_POST['cgr_popup_status'] ) ) : 'draft';
    if ( ! in_array( $status, array( 'draft', 'active', 'scheduled' ), true ) ) {
        $status = 'draft';
    }

    $target_mode = isset( $_POST['cgr_popup_target_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['cgr_popup_target_mode'] ) ) : 'all';
    if ( ! in_array( $target_mode, array( 'all', 'specific' ), true ) ) {
        $target_mode = 'all';
    }

    $target_ids = isset( $_POST['cgr_popup_target_ids'] ) ? array_map( 'absint', (array) $_POST['cgr_popup_target_ids'] ) : array();
    $target_ids = array_filter( $target_ids );

    $start = isset( $_POST['cgr_popup_start'] ) ? cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_start'] ) ) : '';
    $end   = isset( $_POST['cgr_popup_end'] ) ? cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_end'] ) ) : '';

    $next_mode = isset( $_POST['cgr_popup_next_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['cgr_popup_next_mode'] ) ) : 'none';
    if ( ! in_array( $next_mode, array( 'none', 'interval', 'fixed' ), true ) ) {
        $next_mode = 'none';
    }

    $next_value = '';
    if ( 'interval' === $next_mode ) {
        $next_value = isset( $_POST['cgr_popup_next_value_interval'] ) ? absint( $_POST['cgr_popup_next_value_interval'] ) : '';
    } elseif ( 'fixed' === $next_mode ) {
        $next_value = isset( $_POST['cgr_popup_next_value_fixed'] ) ? cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_next_value_fixed'] ) ) : '';
    }

    $frequency = isset( $_POST['cgr_popup_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['cgr_popup_frequency'] ) ) : 'always';
    if ( ! in_array( $frequency, array( 'always', 'session', 'day', 'week', 'month', 'once' ), true ) ) {
        $frequency = 'always';
    }

    $position = isset( $_POST['cgr_popup_position'] ) ? cgr_popup_sanitize_position( wp_unslash( $_POST['cgr_popup_position'] ) ) : 'center';
    $width    = isset( $_POST['cgr_popup_width'] ) ? cgr_popup_sanitize_css_size( wp_unslash( $_POST['cgr_popup_width'] ) ) : '';
    $height   = isset( $_POST['cgr_popup_height'] ) ? cgr_popup_sanitize_css_size( wp_unslash( $_POST['cgr_popup_height'] ) ) : '';

    update_post_meta( $post_id, '_cgr_popup_status', $status );
    update_post_meta( $post_id, '_cgr_popup_target_mode', $target_mode );
    update_post_meta( $post_id, '_cgr_popup_target_ids', $target_ids );
    update_post_meta( $post_id, '_cgr_popup_start', $start );
    update_post_meta( $post_id, '_cgr_popup_end', $end );
    update_post_meta( $post_id, '_cgr_popup_next_mode', $next_mode );
    update_post_meta( $post_id, '_cgr_popup_next_value', $next_value );
    update_post_meta( $post_id, '_cgr_popup_frequency', $frequency );
    update_post_meta( $post_id, '_cgr_popup_position', $position );
    update_post_meta( $post_id, '_cgr_popup_width', $width );
    update_post_meta( $post_id, '_cgr_popup_height', $height );
}
add_action( 'save_post_cgr_popup', 'cgr_save_popup_meta' );

/**
 * Register the Smart Popups dashboard page.
 */
function cgr_register_smart_popups_dashboard_menu() {
    add_submenu_page(
        'edit.php?post_type=cgr_popup',
        __( 'Smart Popups Dashboard', 'cgr-child' ),
        __( 'Dashboard', 'cgr-child' ),
        'edit_posts',
        'cgr-smart-popups',
        'cgr_render_smart_popups_dashboard'
    );
}
add_action( 'admin_menu', 'cgr_register_smart_popups_dashboard_menu' );

/**
 * Render the Smart Popups dashboard page.
 */
function cgr_render_smart_popups_dashboard() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( __( 'You do not have permission to access this page.', 'cgr-child' ) );
    }

    $status_message = '';
    $status_class   = 'notice notice-success';
    $saved_values   = cgr_popup_default_meta();
    $saved_values['title']   = '';
    $saved_values['content'] = '';

    if ( isset( $_POST['cgr_popup_dashboard_nonce'] ) && wp_verify_nonce( $_POST['cgr_popup_dashboard_nonce'], 'cgr_popup_dashboard' ) ) {
        $title   = sanitize_text_field( wp_unslash( $_POST['cgr_popup_title'] ?? '' ) );
        $content = wp_kses_post( wp_unslash( $_POST['cgr_popup_content'] ?? '' ) );

        $saved_values['title']   = $title;
        $saved_values['content'] = $content;
        $saved_values['status']  = sanitize_text_field( wp_unslash( $_POST['cgr_popup_status'] ?? 'draft' ) );
        $saved_values['target_mode'] = sanitize_text_field( wp_unslash( $_POST['cgr_popup_target_mode'] ?? 'all' ) );
        $saved_values['target_ids']  = isset( $_POST['cgr_popup_target_ids'] ) ? array_map( 'absint', (array) $_POST['cgr_popup_target_ids'] ) : array();
        $saved_values['start']   = cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_start'] ?? '' ) );
        $saved_values['end']     = cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_end'] ?? '' ) );
        $saved_values['next_mode'] = sanitize_text_field( wp_unslash( $_POST['cgr_popup_next_mode'] ?? 'none' ) );
        $saved_values['next_value'] = '';
        if ( 'interval' === $saved_values['next_mode'] ) {
            $saved_values['next_value'] = absint( $_POST['cgr_popup_next_value_interval'] ?? '' );
        } elseif ( 'fixed' === $saved_values['next_mode'] ) {
            $saved_values['next_value'] = cgr_popup_normalize_datetime( wp_unslash( $_POST['cgr_popup_next_value_fixed'] ?? '' ) );
        }
        $saved_values['frequency'] = sanitize_text_field( wp_unslash( $_POST['cgr_popup_frequency'] ?? 'always' ) );
        $saved_values['position']  = cgr_popup_sanitize_position( wp_unslash( $_POST['cgr_popup_position'] ?? 'center' ) );
        $saved_values['width']     = cgr_popup_sanitize_css_size( wp_unslash( $_POST['cgr_popup_width'] ?? '' ) );
        $saved_values['height']    = cgr_popup_sanitize_css_size( wp_unslash( $_POST['cgr_popup_height'] ?? '' ) );

        if ( empty( $title ) ) {
            $status_message = __( 'Please provide a title for the popup.', 'cgr-child' );
            $status_class   = 'notice notice-error';
        } else {
            $post_status = ( 'draft' === $saved_values['status'] ) ? 'draft' : 'publish';
            $post_id = wp_insert_post( array(
                'post_type'    => 'cgr_popup',
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => $post_status,
            ), true );

            if ( is_wp_error( $post_id ) ) {
                $status_message = __( 'There was an error creating the popup. Please try again.', 'cgr-child' );
                $status_class   = 'notice notice-error';
            } else {
                update_post_meta( $post_id, '_cgr_popup_status', $saved_values['status'] );
                update_post_meta( $post_id, '_cgr_popup_target_mode', $saved_values['target_mode'] );
                update_post_meta( $post_id, '_cgr_popup_target_ids', array_filter( $saved_values['target_ids'] ) );
                update_post_meta( $post_id, '_cgr_popup_start', $saved_values['start'] );
                update_post_meta( $post_id, '_cgr_popup_end', $saved_values['end'] );
                update_post_meta( $post_id, '_cgr_popup_next_mode', $saved_values['next_mode'] );
                update_post_meta( $post_id, '_cgr_popup_next_value', $saved_values['next_value'] );
                update_post_meta( $post_id, '_cgr_popup_frequency', $saved_values['frequency'] );
                update_post_meta( $post_id, '_cgr_popup_position', $saved_values['position'] );
                update_post_meta( $post_id, '_cgr_popup_width', $saved_values['width'] );
                update_post_meta( $post_id, '_cgr_popup_height', $saved_values['height'] );

                $status_message = __( 'Popup saved successfully.', 'cgr-child' );
                $status_class   = 'notice notice-success';
                $saved_values   = array_merge( cgr_popup_default_meta(), array( 'title' => '', 'content' => '' ) );
            }
        }
    }

    $popups = get_posts( array(
        'post_type'      => 'cgr_popup',
        'posts_per_page' => -1,
        'post_status'    => array( 'publish', 'draft' ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
    ?>
    <div class="wrap cgr-popups-dashboard">
        <h1><?php esc_html_e( 'CGR Smart Popups Dashboard', 'cgr-child' ); ?></h1>
        <?php if ( $status_message ) : ?>
            <div class="<?php echo esc_attr( $status_class ); ?>">
                <p><?php echo esc_html( $status_message ); ?></p>
            </div>
        <?php endif; ?>

        <p class="description">
            <?php esc_html_e( 'Create and schedule popups with precise targeting. Design the popup layout with Elementor, while this dashboard handles timing and frequency.', 'cgr-child' ); ?>
        </p>

        <div class="cgr-popups-dashboard-grid">
            <section class="cgr-card cgr-card--form">
                <h2><?php esc_html_e( 'Create New Popup', 'cgr-child' ); ?></h2>
                <form method="post">
                    <?php wp_nonce_field( 'cgr_popup_dashboard', 'cgr_popup_dashboard_nonce' ); ?>
                    <label class="cgr-field">
                        <span><?php esc_html_e( 'Popup title', 'cgr-child' ); ?></span>
                        <input type="text" name="cgr_popup_title" value="<?php echo esc_attr( $saved_values['title'] ); ?>" class="widefat" required>
                    </label>
                    <label class="cgr-field">
                        <span><?php esc_html_e( 'Fallback content (optional)', 'cgr-child' ); ?></span>
                        <textarea name="cgr_popup_content" rows="4" class="widefat"><?php echo esc_textarea( $saved_values['content'] ); ?></textarea>
                    </label>
                    <?php cgr_render_popup_fields( $saved_values, 'cgr-popup-dashboard-' ); ?>
                    <?php submit_button( __( 'Save Popup', 'cgr-child' ) ); ?>
                </form>
            </section>

            <section class="cgr-card cgr-card--summary">
                <h2><?php esc_html_e( 'Scheduling Snapshot', 'cgr-child' ); ?></h2>
                <p><?php esc_html_e( 'Status chips update based on the current time window. Drafts are never shown; Scheduled popups wait for their start date; Active popups are eligible now.', 'cgr-child' ); ?></p>
                <div class="cgr-status-legend">
                    <span class="cgr-status-badge cgr-status-active"><?php esc_html_e( 'Active', 'cgr-child' ); ?></span>
                    <span class="cgr-status-badge cgr-status-scheduled"><?php esc_html_e( 'Scheduled', 'cgr-child' ); ?></span>
                    <span class="cgr-status-badge cgr-status-draft"><?php esc_html_e( 'Draft', 'cgr-child' ); ?></span>
                    <span class="cgr-status-badge cgr-status-expired"><?php esc_html_e( 'Expired', 'cgr-child' ); ?></span>
                </div>
            </section>
        </div>

        <section class="cgr-card cgr-card--table">
            <h2><?php esc_html_e( 'Existing Popups', 'cgr-child' ); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Title', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Start', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'End', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Targets', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Frequency', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Next Scheduled', 'cgr-child' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'cgr-child' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $popups ) : ?>
                        <?php foreach ( $popups as $popup ) : ?>
                            <?php
                            $meta             = cgr_popup_get_meta( $popup->ID );
                            $effective_status = cgr_popup_get_effective_status( $popup->ID, $meta );
                            $targets          = $meta['target_ids'];
                            $target_titles    = array();
                            foreach ( $targets as $target_id ) {
                                $title = get_the_title( $target_id );
                                if ( $title ) {
                                    $target_titles[] = $title;
                                }
                            }
                            $next_display = '';
                            if ( 'interval' === $meta['next_mode'] && $meta['next_value'] ) {
                                $next_display = sprintf( __( 'Every %d days after dismissal', 'cgr-child' ), absint( $meta['next_value'] ) );
                            } elseif ( 'fixed' === $meta['next_mode'] && $meta['next_value'] ) {
                                $next_display = wp_date( 'M j, Y g:ia', strtotime( $meta['next_value'] ) );
                            } else {
                                $next_display = __( 'None', 'cgr-child' );
                            }
                            $edit_link = get_edit_post_link( $popup->ID );
                            $elementor_link = '';
                            if ( class_exists( '\\Elementor\\Plugin' ) ) {
                                $elementor_link = add_query_arg( 'action', 'elementor', $edit_link );
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html( get_the_title( $popup ) ); ?></td>
                                <td>
                                    <span class="cgr-status-badge cgr-status-<?php echo esc_attr( $effective_status ); ?>">
                                        <?php echo esc_html( ucfirst( $effective_status ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo $meta['start'] ? esc_html( wp_date( 'M j, Y g:ia', strtotime( $meta['start'] ) ) ) : esc_html__( '—', 'cgr-child' ); ?></td>
                                <td><?php echo $meta['end'] ? esc_html( wp_date( 'M j, Y g:ia', strtotime( $meta['end'] ) ) ) : esc_html__( '—', 'cgr-child' ); ?></td>
                                <td>
                                    <?php if ( 'all' === $meta['target_mode'] ) : ?>
                                        <?php esc_html_e( 'All pages', 'cgr-child' ); ?>
                                    <?php elseif ( $target_titles ) : ?>
                                        <?php echo esc_html( implode( ', ', $target_titles ) ); ?>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Specific pages', 'cgr-child' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( ucfirst( $meta['frequency'] ) ); ?></td>
                                <td><?php echo esc_html( $next_display ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( $edit_link ); ?>"><?php esc_html_e( 'Edit', 'cgr-child' ); ?></a>
                                    <?php if ( $elementor_link ) : ?>
                                        | <a href="<?php echo esc_url( $elementor_link ); ?>"><?php esc_html_e( 'Elementor', 'cgr-child' ); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8"><?php esc_html_e( 'No popups registered yet.', 'cgr-child' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
    <?php
}

/**
 * Load admin assets for Smart Popups.
 */
function cgr_popup_admin_assets( $hook ) {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }

    if ( 'cgr_popup' !== $screen->post_type && 'cgr_popup_page_cgr-smart-popups' !== $screen->id ) {
        return;
    }

    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cgr-design-system',
        $theme_uri . '/assets/css/design-system.css',
        array(),
        filemtime( $theme_dir . '/assets/css/design-system.css' )
    );

    $select2_css = apply_filters( 'cgr_popup_select2_css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
    $select2_js  = apply_filters( 'cgr_popup_select2_js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js' );

    wp_enqueue_style( 'cgr-popup-select2', $select2_css, array(), '4.1.0-rc.0' );
    wp_enqueue_script( 'cgr-popup-select2', $select2_js, array( 'jquery' ), '4.1.0-rc.0', true );

    wp_enqueue_style(
        'cgr-smart-popups-admin',
        $theme_uri . '/assets/css/cgr-smart-popups-admin.css',
        array( 'cgr-design-system' ),
        filemtime( $theme_dir . '/assets/css/cgr-smart-popups-admin.css' )
    );

    wp_enqueue_script(
        'cgr-smart-popups-admin',
        $theme_uri . '/assets/js/cgr-smart-popups-admin.js',
        array( 'jquery', 'cgr-popup-select2' ),
        filemtime( $theme_dir . '/assets/js/cgr-smart-popups-admin.js' ),
        true
    );
}
add_action( 'admin_enqueue_scripts', 'cgr_popup_admin_assets' );

/**
 * Build popup payloads for frontend usage.
 */
function cgr_build_smart_popup_payloads() {
    $now = current_time( 'timestamp' );

    $popups = get_posts( array(
        'post_type'      => 'cgr_popup',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );

    if ( ! $popups ) {
        return array();
    }

    $current_id = get_queried_object_id();
    $payloads   = array();

    foreach ( $popups as $popup ) {
        $meta             = cgr_popup_get_meta( $popup->ID );
        $effective_status = cgr_popup_get_effective_status( $popup->ID, $meta, $now );

        if ( 'expired' === $effective_status || 'draft' === $effective_status ) {
            continue;
        }

        $is_targeted = true;
        if ( 'specific' === $meta['target_mode'] ) {
            $is_targeted = $current_id && in_array( $current_id, $meta['target_ids'], true );
        }

        if ( ! $is_targeted ) {
            continue;
        }

        $start_timestamp = $meta['start'] ? strtotime( $meta['start'] ) : 0;
        $end_timestamp   = $meta['end'] ? strtotime( $meta['end'] ) : 0;

        $next_value = null;
        if ( 'interval' === $meta['next_mode'] ) {
            $next_value = absint( $meta['next_value'] );
        } elseif ( 'fixed' === $meta['next_mode'] && $meta['next_value'] ) {
            $next_value = strtotime( $meta['next_value'] );
        }
        $next_value = apply_filters( 'cgr_popup_next_scheduled_value', $next_value, $popup->ID, $meta );

        $popup_data = array(
            'id'          => $popup->ID,
            'status'      => $effective_status,
            'start'       => $start_timestamp,
            'end'         => $end_timestamp,
            'frequency'   => $meta['frequency'],
            'next_mode'   => $meta['next_mode'],
            'next_value'  => $next_value,
            'priority'    => (int) $popup->menu_order,
            'target_mode' => $meta['target_mode'],
            'position'    => $meta['position'],
            'width'       => $meta['width'],
            'height'      => $meta['height'],
        );

        $popup_data = apply_filters( 'cgr_smart_popup_payload', $popup_data, $popup->ID, $meta );
        $payloads[] = $popup_data;
    }

    return $payloads;
}

/**
 * Enqueue frontend assets and localize popup data.
 */
function cgr_enqueue_smart_popups_assets() {
    if ( is_admin() ) {
        return;
    }

    $payloads = cgr_build_smart_popup_payloads();
    if ( empty( $payloads ) ) {
        return;
    }

    $theme_dir = get_stylesheet_directory();
    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cgr-smart-popups',
        $theme_uri . '/assets/css/cgr-smart-popups.css',
        array( 'cgr-design-system' ),
        filemtime( $theme_dir . '/assets/css/cgr-smart-popups.css' )
    );

    wp_enqueue_script(
        'cgr-smart-popups',
        $theme_uri . '/assets/js/cgr-smart-popups.js',
        array(),
        filemtime( $theme_dir . '/assets/js/cgr-smart-popups.js' ),
        true
    );

    $payload = array(
        'now'     => current_time( 'timestamp' ),
        'popups'  => $payloads,
        'strings' => array(
            'close' => __( 'Close', 'cgr-child' ),
        ),
    );

    $payload = apply_filters( 'cgr_smart_popups_payload', $payload, $payloads );

    wp_localize_script( 'cgr-smart-popups', 'cgrSmartPopups', $payload );

    $GLOBALS['cgr_smart_popups_payloads'] = $payloads;
}
add_action( 'wp_enqueue_scripts', 'cgr_enqueue_smart_popups_assets' );

/**
 * Render popup markup in the footer.
 */
function cgr_render_smart_popups_footer() {
    if ( empty( $GLOBALS['cgr_smart_popups_payloads'] ) ) {
        return;
    }

    $payloads  = $GLOBALS['cgr_smart_popups_payloads'];
    $popup_ids = array_filter( array_map( 'absint', wp_list_pluck( $payloads, 'id' ) ) );
    if ( ! $popup_ids ) {
        return;
    }

    $popups = get_posts( array(
        'post_type'      => 'cgr_popup',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'post__in'       => $popup_ids,
        'orderby'        => 'post__in',
    ) );

    if ( ! $popups ) {
        return;
    }
    ?>
    <div class="cgr-smart-popups" data-cgr-smart-popups>
        <?php foreach ( $popups as $popup ) : ?>
            <?php
            $meta             = cgr_popup_get_meta( $popup->ID );
            $effective_status = cgr_popup_get_effective_status( $popup->ID, $meta );
            if ( 'expired' === $effective_status || 'draft' === $effective_status ) {
                continue;
            }

            $position = cgr_popup_sanitize_position( $meta['position'] );
            $classes  = 'cgr-smart-popup';
            if ( 'center' !== $position ) {
                $classes .= ' cgr-smart-popup--' . $position;
            }
            $style_vars = array();
            if ( $meta['width'] ) {
                $style_vars[] = '--cgr-popup-width: ' . $meta['width'] . ';';
            }
            if ( $meta['height'] ) {
                $style_vars[] = '--cgr-popup-height: ' . $meta['height'] . ';';
            }
            $content_style = $style_vars ? ' style="' . esc_attr( implode( ' ', $style_vars ) ) . '"' : '';

            $content = '';
            if ( class_exists( '\\Elementor\\Plugin' ) ) {
                $elementor = \Elementor\Plugin::$instance;
                if ( $elementor && method_exists( $elementor->frontend, 'get_builder_content_for_display' ) ) {
                    $content = $elementor->frontend->get_builder_content_for_display( $popup->ID );
                }
            }

            if ( ! $content ) {
                $content = apply_filters( 'the_content', $popup->post_content );
            }
            ?>
            <div class="<?php echo esc_attr( $classes ); ?>" data-cgr-popup-id="<?php echo esc_attr( $popup->ID ); ?>" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="cgr-smart-popup__backdrop" data-cgr-popup-close></div>
                <div class="cgr-smart-popup__content" role="document"<?php echo $content_style; ?>>
                    <button type="button" class="cgr-smart-popup__close" data-cgr-popup-close aria-label="<?php esc_attr_e( 'Close popup', 'cgr-child' ); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="cgr-smart-popup__body">
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
add_action( 'wp_footer', 'cgr_render_smart_popups_footer' );

