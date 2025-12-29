<?php
// Visitor Counter

function cgr_create_visitor_counter_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_counter';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        count bigint(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    // Insert a default row if it doesn't exist
    $row = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1" );
    if ( is_null( $row ) ) {
        $wpdb->insert(
            $table_name,
            array(
                'id' => 1,
                'count' => 0
            )
        );
    }
}
add_action( 'after_switch_theme', 'cgr_create_visitor_counter_table' );

add_action( 'admin_enqueue_scripts', 'cgr_visitor_counter_styles' );

function cgr_visitor_counter_styles($hook) {
    if ( 'toplevel_page_cgr-visitor-counter' != $hook ) {
        return;
    }
    wp_enqueue_style(
        'cgr-visitor-counter-style',
        get_stylesheet_directory_uri() . '/includes/visitor-counter/visitor-counter.css',
        array(),
        '1.0.0'
    );
    wp_enqueue_script(
        'cgr-visitor-counter-script',
        get_stylesheet_directory_uri() . '/includes/visitor-counter/visitor-counter.js',
        array(),
        '1.0.0',
        true
    );
    wp_localize_script(
        'cgr-visitor-counter-script',
        'cgr_visitor_counter',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cgr_visitor_counter_nonce' ),
        )
    );
}

// Add admin menu
add_action( 'admin_menu', 'cgr_visitor_counter_menu' );

function cgr_visitor_counter_menu() {
    add_menu_page(
        __( 'Visitor Counter Settings', 'cgr-child' ),
        __( 'Visitor Counter', 'cgr-child' ),
        'manage_options',
        'cgr-visitor-counter',
        'cgr_visitor_counter_page',
        'dashicons-dashboard',
        20
    );
}

function cgr_visitor_counter_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_counter';

    $current_count = $wpdb->get_var( "SELECT count FROM $table_name WHERE id = 1" );
    ?>
    <div class="wrap">
        <h1><?php _e( 'Visitor Counter Settings', 'cgr-child' ); ?></h1>
        <div id="cgr-visitor-counter-notice-wrapper">
            <div id="cgr-visitor-counter-notice"></div>
        </div>
        <form id="cgr-visitor-counter-form">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Current Visitor Count', 'cgr-child' ); ?></th>
                    <td id="cgr-current-count"><?php echo number_format( $current_count ); ?></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Set Visitor Count', 'cgr-child' ); ?></th>
                    <td><input type="number" name="cgr_visitor_counter_count" id="cgr_visitor_counter_count" value="<?php echo $current_count; ?>" /></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Changes', 'cgr-child' ), 'primary', 'cgr_visitor_counter_submit' ); ?>
        </form>
    </div>
    <?php
}

add_action( 'wp_ajax_cgr_update_visitor_count', 'cgr_update_visitor_count' );

function cgr_update_visitor_count() {
    check_ajax_referer( 'cgr_visitor_counter_nonce' );

    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_counter';

    if ( isset( $_POST['count'] ) ) {
        $count = intval( $_POST['count'] );
        $wpdb->update(
            $table_name,
            array( 'count' => $count ),
            array( 'id' => 1 ),
            array( '%d' ),
            array( '%d' )
        );
        wp_send_json_success( array( 'new_count' => number_format( $count ) ) );
    } else {
        wp_send_json_error( 'No count provided.' );
    }
}

// Increment visitor count
function cgr_increment_visitor_count() {
    if ( ! is_admin() && !isset($_SESSION['cgr_visitor_counted']) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'visitor_counter';
        $current_count = $wpdb->get_var( "SELECT count FROM $table_name WHERE id = 1" );
        $new_count = $current_count + 1;
        $wpdb->update(
            $table_name,
            array( 'count' => $new_count ),
            array( 'id' => 1 ),
            array( '%d' ),
            array( '%d' )
        );
        $_SESSION['cgr_visitor_counted'] = true;
    }
}
add_action( 'wp_head', 'cgr_increment_visitor_count' );


// Shortcode to display visitor count
function cgr_visitor_counter_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'visitor_counter';
    $count = $wpdb->get_var( "SELECT count FROM $table_name WHERE id = 1" );
    return number_format( $count );
}
add_shortcode( 'visitor_counter', 'cgr_visitor_counter_shortcode' );
?>