<?php
/**
 * Plugin Name: HWD Social Share
 * Description: Share WordPress posts to social networks with basic scheduling and per-network limits.
 * Version: 0.1.0
 * Author: HWD
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HWD_SS_DIR', __DIR__ );
define( 'HWD_SS_URL', plugin_dir_url( __FILE__ ) );
define( 'HWD_SS_VERSION', '0.1.0' );

require_once HWD_SS_DIR . '/includes/helpers.php';
require_once HWD_SS_DIR . '/includes/class-plugin.php';
require_once HWD_SS_DIR . '/includes/class-admin.php';
require_once HWD_SS_DIR . '/includes/networks/interface-network.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-base.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-x.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-facebook.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-linkedin.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-instagram.php';
require_once HWD_SS_DIR . '/includes/networks/class-network-youtube.php';

register_activation_hook( __FILE__, [ 'HWD_SS_Plugin', 'activate' ] );

HWD_SS_Plugin::instance();
