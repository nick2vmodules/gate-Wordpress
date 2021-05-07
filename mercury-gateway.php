<?php

/**
 * Created by 2vModules.
 * User: dudenko.vadim@gmail.com
 * Date: 08.12.2020
 * Time: 15:14
 */

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Mercury_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       Mercury Payment Gateway
 * Plugin URI:        http://example.com/mercury-gateway-uri/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            2vModules
 * Author URI:        https://2vmodules.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mercury-gateway
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';

function sample_admin_notice__error() {
    $class = 'notice notice-error';
    $message = sprintf( esc_html__( 'Mercury Gateway requires the WooCommerce plugin to be installed and active. You can download %s here.', 'sample-text-domain' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' );
    wp_sprintf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', esc_attr( $class ), $message );
}
if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    add_action( 'admin_notices', 'sample_admin_notice__error' );
    return;
}

if(class_exists('Mercury_Gateway') !== true)
{
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
    define('MERCURY_GATEWAY_VERSION', $plugin_data['Version']);

    define('MERCURY_GATEWAY_URL', plugin_dir_url(__FILE__));
    define('MERCURY_GATEWAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('MERCURY_GATEWAY_PLUGIN_NAME', plugin_basename(__FILE__));

    require MERCURY_GATEWAY_PLUGIN_DIR . 'mercury-cash-sdk/vendor/autoload.php';
    include_once __DIR__ . '/includes/functions-mercury-gateway.php';
    include_once __DIR__ . '/includes/class-mercury-gateway.php';
}

add_action('plugins_loaded', 'Mercury_Gateway', 5);
