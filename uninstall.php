<?php
/**
 * Plugin uninstaller
 *
 * This file contains PHP.
 * This magic file provides an alternative to register_uninstall_hook
 * and is automatically run when the users deletes the plugin.
 *
 * Deactivation Hook:
 * Flush Cache/Temp: Yes
 * Flush Permalinks: Yes
 * Remove Options from {$wpdb->prefix}_options: No
 * Remove Tables from wpdb: No
 *
 * Uninstall Hook:
 * Flush Cache/Temp: No
 * Flush Permalinks: No
 * Remove Options from {$wpdb->prefix}_options: Yes
 * Remove Tables from wpdb: Yes
 *
 * @link https://developer.wordpress.org/plugins/the-basics/best-practices/#file-organization
 * @link https://developer.wordpress.org/plugins/the-basics/uninstall-methods/#method-2-uninstall-php
 * @since      0.0.1
 *
 * @package    myphotolinks
 */

// if uninstall.php is not called by WordPress, die
if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
  die;
}

$option_name = "myphotolinks";

delete_option($option_name);

// for site options in Multisite
delete_site_option($option_name);

// drop a custom database table
//global $wpdb;
//$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mytable");

?>