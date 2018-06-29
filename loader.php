<?php
/**
 * Plugin Name: Minio Server Sync
 * Plugin URI: https://github.com/alexishida/Wp-Minio-Server-Sync
 * Description: This WordPress plugin syncs your media library with Minio Server Container.
 * Version: 1.0
 * Author: Alex Ishida
 * Author URI: https://github.com/alexishida
 * License: MIT
 * Text Domain: ms3
 * Domain Path: /languages

 */
load_plugin_textdomain('ms3', false, dirname(plugin_basename(__FILE__)) . '/lang');

function ms3_incompatibile($msg) {
  require_once ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
  deactivate_plugins(__FILE__);
  wp_die($msg);
}

if ( is_admin() && ( !defined('DOING_AJAX') || !DOING_AJAX ) ) {

  if ( version_compare(PHP_VERSION, '5.3.3', '<') ) {

    ms3_incompatibile(
      __(
        'Plugin Minio Server Sync requires PHP 5.3.3 or higher. The plugin has now disabled itself.',
        'dos'
      )
    );

  } elseif ( !function_exists('curl_version')
    || !($curl = curl_version()) || empty($curl['version']) || empty($curl['features'])
    || version_compare($curl['version'], '7.16.2', '<')
  ) {

    ms3_incompatibile(
      __('Plugin Minio Server Sync requires cURL 7.16.2+. The plugin has now disabled itself.', 'dos')
    );

  } elseif (!($curl['features'] & CURL_VERSION_SSL)) {

    ms3_incompatibile(
      __(
        'Plugin Minio Server Sync requires that cURL is compiled with OpenSSL. The plugin has now disabled itself.',
        'dos'
      )
    );

  }

}
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'code.php';