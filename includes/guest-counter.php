<?php
/**
 * Guest Counter: counts visits once per browser session with a manual offset and shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Increment the counter once per session on the front end.
 */
function cgr_guest_counter_maybe_increment() {
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || wp_doing_cron() ) {
        return;
    }

    // Avoid double counting within the same browser session.
    if ( isset( $_SESSION['cgr_guest_counter_seen'] ) ) {
        return;
    }

    $total = (int) get_option( 'cgr_guest_counter_total', 0 );
    update_option( 'cgr_guest_counter_total', $total + 1, false );

    $_SESSION['cgr_guest_counter_seen'] = true;
}
add_action( 'init', 'cgr_guest_counter_maybe_increment', 1 );

/**
 * Calculate the displayed count (base offset + raw sessions).
 */
function cgr_guest_counter_display_count() {
    $base  = (int) get_option( 'cgr_guest_counter_base', 0 );
    $total = (int) get_option( 'cgr_guest_counter_total', 0 );

    return $base + $total;
}

/**
 * Shortcode: [guest_counter]
 */
function cgr_guest_counter_shortcode() {
    return number_format_i18n( cgr_guest_counter_display_count() );
}
add_shortcode( 'guest_counter', 'cgr_guest_counter_shortcode' );

/**
 * Template tag for PHP usage.
 */
function cgr_guest_counter_render() {
    echo number_format_i18n( cgr_guest_counter_display_count() );
}

/**
 * Admin settings page registration.
 */
function cgr_guest_counter_register_settings_page() {
    add_options_page(
        __( 'Guest Counter', 'cgr-child' ),
        __( 'Guest Counter', 'cgr-child' ),
        'manage_options',
        'cgr-guest-counter',
        'cgr_guest_counter_settings_page'
    );
}
add_action( 'admin_menu', 'cgr_guest_counter_register_settings_page' );

/**
 * Register stored settings for the counter.
 */
function cgr_guest_counter_register_settings() {
    register_setting(
        'cgr_guest_counter',
        'cgr_guest_counter_base',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );

    register_setting(
        'cgr_guest_counter',
        'cgr_guest_counter_total',
        array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        )
    );
}
add_action( 'admin_init', 'cgr_guest_counter_register_settings' );

/**
 * Render the admin settings form.
 */
function cgr_guest_counter_settings_page() {
    $display_count = number_format_i18n( cgr_guest_counter_display_count() );
    $base          = (int) get_option( 'cgr_guest_counter_base', 0 );
    $total         = (int) get_option( 'cgr_guest_counter_total', 0 );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Guest Counter', 'cgr-child' ); ?></h1>
        <p><?php esc_html_e( 'Set a starting offset and let the counter add one visit per browser session.', 'cgr-child' ); ?></p>
        <p><strong><?php esc_html_e( 'Current displayed total:', 'cgr-child' ); ?></strong> <?php echo esc_html( $display_count ); ?></p>
        <form method="post" action="options.php">
            <?php settings_fields( 'cgr_guest_counter' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cgr_guest_counter_base"><?php esc_html_e( 'Starting offset', 'cgr-child' ); ?></label></th>
                    <td>
                        <input
                            name="cgr_guest_counter_base"
                            id="cgr_guest_counter_base"
                            type="number"
                            min="0"
                            value="<?php echo esc_attr( $base ); ?>"
                            class="regular-text"
                        />
                        <p class="description"><?php esc_html_e( 'Set this to your known total so far (e.g., 1000000 for 10 lakhs).', 'cgr-child' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cgr_guest_counter_total"><?php esc_html_e( 'Raw session visits', 'cgr-child' ); ?></label></th>
                    <td>
                        <input
                            name="cgr_guest_counter_total"
                            id="cgr_guest_counter_total"
                            type="number"
                            min="0"
                            value="<?php echo esc_attr( $total ); ?>"
                            class="regular-text"
                        />
                        <p class="description"><?php esc_html_e( 'Auto-increments once per visitor session. Adjust or reset here if needed.', 'cgr-child' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
