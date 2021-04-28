<?php
/**
 * Created by 2vModules.
 * User: dudenko.vadim@gmail.com
 * Date: 08.12.2020
 * Time: 15:14
 */

/**
 * Uninstall
 *
 * @package Mercury_Gateway
 */
defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Delete plugin options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_mercury%';");
