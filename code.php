<?php

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Creates settings page and sets default options
 */
function ms3_settings_page () {

  // Default settings
  if ( get_option('upload_path') == 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' || get_option('upload_path') == null  ) {
    update_option('upload_path', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads');
  }

  if ( get_option('ms3_endpoint') == null ) {
    update_option('ms3_endpoint', 'https://s3.endpoint.com');
  }

  if ( get_option('ms3_filter') == null ) {
    update_option('ms3_filter', '');
  }

  if ( get_option('ms3_storage_path') == null ) {
    update_option('ms3_storage_path', '/');
  }

  include_once('code/settings_page.php');

}

/**
 * Adds menu item for plugin
 */
function ms3_create_menu (){

  add_options_page(
    'Minio Server Sync',
    'Minio Server Sync',
    'manage_options',
    __FILE__,
    'ms3_settings_page'
  );

}

/**
 * Creates storage instance and returns it
 * 
 * @param  boolean $test
 * @return instance
 */
function __DOS ($test = false) {

  if ( $test ) {

    // ms3_key
    if ( isset( $_POST['ms3_key'] ) ) {
      $ms3_key = $_POST['ms3_key'];
    } else { 
      $ms3_key = get_option('ms3_key');
    }

    // ms3_secret
    if ( isset( $_POST['ms3_secret'] ) ) {
      $ms3_secret = $_POST['ms3_secret'];
    } else {
      $ms3_secret = get_option('ms3_secret');
    }

    // ms3_endpoint
    if ( isset( $_POST['ms3_endpoint'] ) ) {
      $ms3_endpoint = $_POST['ms3_endpoint'];
    } else {
      $ms3_endpoint = get_option('ms3_endpoint');
    }

    // ms3_container
    if ( isset( $_POST['ms3_container'] ) ) {
      $ms3_container = $_POST['ms3_container'];
    } else {
      $ms3_container = get_option('ms3_container');
    }

  } else {
    $ms3_key = get_option('ms3_key');
    $ms3_secret = get_option('ms3_secret');
    $ms3_endpoint = get_option('ms3_endpoint');
    $ms3_container = get_option('ms3_container');
  }

  $client = S3Client::factory([
    'credentials' => [
      'key'    => $ms3_key,
      'secret' => $ms3_secret,
    ],
    'endpoint' => $ms3_endpoint,
    'region'  => 'us-east-1',
    'version' => 'latest',
    'use_path_style_endpoint' => true,
  ]);

  $connection = new AwsS3Adapter($client, $ms3_container);
  $filesystem = new Filesystem($connection);

  return $filesystem;

}

/**
 * Displays formatted message
 *
 * @param string $message
 * @param bool $errormsg = false
 */
function ms3_show_message ($message, $errormsg = false) {

  if ($errormsg) {

    echo '<div id="message" class="error">';

  } else {

    echo '<div id="message" class="updated fade">';

  }

  echo "<p><strong>$message</strong></p></div>";

}

/**
 * Tests connection to container
 */
function ms3_test_connection () {

  try {
    
    $filesystem = __DOS( true );
    $filesystem->write('test.txt', 'test');
    $filesystem->delete('test.txt');
    ms3_show_message(__('Connection is successfully established. Save the settings.', 'dos'));

    exit();

  } catch (Exception $e) {

    ms3_show_message( __('Connection is not established.','dos') . ' : ' . $e->getMessage() . ($e->getCode() == 0 ? '' : ' - ' . $e->getCode() ), true);
    exit();

  }

}

/**
 * Trims an absolute path to relative
 *
 * @param string $file Full url path. Example /var/www/example.com/wm-content/uploads/2015/05/simple.jpg
 * @return string Short path. Example 2015/05/simple.jpg
 */
function ms3_filepath ($file) {

  $dir = get_option('upload_path');
  $file = str_replace($dir, '', $file);
  $file = get_option('ms3_storage_path') . $file;
  $file = str_replace('\\', '/', $file);
  $file = str_replace('//', '/', $file);
  $file = str_replace(' ', '%20', $file);
  //$file = ltrim($file, '/');

  return $file;
}

/**
 * Returns data as a string
 *
 * @param mixed $data
 * @return string
 */
function ms3_dump ($data) {

  ob_start();
  print_r($data);
  $content = ob_get_contents();
  ob_end_clean();

  return $content;

}

/**
 * Uploads a file to storage
 * 
 * @param  string *Full path to upload file
 * @param  int Number of attempts to upload the file
 * @param  bool *Delete the file from the server after unloading
 * @return bool Successful load returns true, false otherwise
 */
function ms3_file_upload ($pathToFile, $attempt = 0, $del = false) {

  // init cloud filesystem
  $filesystem = __DOS();
  $regex = get_option('ms3_filter');

  // prepare regex
  if ( $regex == '*' ) {
    $regex = '';
  }

  if (get_option('ms3_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(
      plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_' . time() . '_', 'extension' => 'log')
    );

    if ($attempt > 0) {
      $log->notice('Attempt # ' . $attempt);
    }

  }

  try {

    if ( get_option('ms3_debug') == 1 and isset($log) ) {

      $log->info("Path to thumbnail: " . $pathToFile);

      if ( ms3_check_for_sync($pathToFile) ) {

        $log->info('File ' . $pathToFile . ' will be uploaded.');

      } else {

        $log->info('File ' . $pathToFile . ' does not fit the mask.');

      }
    }

    // check if readable and regex matched
    if ( is_readable($pathToFile) && !preg_match( $regex, $pathToFile) ) {

      $filesystem->put( ms3_filepath($pathToFile), file_get_contents($pathToFile), [
        'visibility' => AdapterInterface::VISIBILITY_PUBLIC
      ]);

      if (get_option('ms3_storage_file_only') == 1) {
        ms3_file_delete($pathToFile);
      }

      if (get_option('ms3_debug') == 1 and isset($log)) {
        $log->info("Instance - OK");
        $log->info("Name ObJ: " . ms3_filepath($pathToFile));
      }
      
    }

    return true;

  } catch (Exception $e) {

    if ( get_option('ms3_debug') == 1 and isset($log) ) {
      $log->error($e->getCode() . ' :: ' . $e->getMessage());
    }

    if ( $attempt < 3 ) {
      wp_schedule_single_event(time() + 5, 'ms3_schedule_upload', array($pathToFile, ++$attempt));
    }

    return false;

  }

}

/**
 * Deletes a file from local filesystem 
 * 
 * @param  string $file Absolute path to file
 * @param  integer $attempt Number of attempts to upload the file
 */
function ms3_file_delete ($file, $attempt = 0) {

  if (file_exists($file)) {

    if (is_writable($file)) {

      if (get_option('ms3_debug') == 1) {

        $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
          array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));

      }

      unlink($file);

      if (get_option('ms3_debug') == 1 and isset($log)) {
        $log->info("File " . $file . ' deleted');
      }

    } elseif ($attempt < 3) {

      wp_schedule_single_event(time() + 10, 'ms3_file_delete', array($file, ++$attempt));

    }

  }

}

/**
 * Upload files to storage
 *
 * @param int $postID Id upload file
 * @return bool
 */
function ms3_storage_upload ($postID) {

  if ( wp_attachment_is_image($postID) == false ) {

    $file = get_attached_file($postID);

    if ( get_option('ms3_debug') == 1 ) {

      $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
        array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
      $log->info('Starts unload file');
      $log->info('File path: ' . $file);
      //$log->info("MetaData: \n" . ms3_dump($meta));

    }

    if ( get_option('ms3_lazy_upload') == 1 ) {

      wp_schedule_single_event( time(), 'ms3_schedule_upload', array($file));

    } else {

      ms3_file_upload($file);

    }

  }

  return true;
  
}

/**
 * Deletes the file from storage
 * @param string $file Full path to file
 * @return string
 */
function ms3_storage_delete ($file) {

  try {

    if (get_option('ms3_debug') == 1) {
      $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
        array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    }

    $filesystem = __DOS();

    $filesystem->delete( ms3_filepath($file) );
    ms3_file_delete($file);

    if (get_option('ms3_debug') == 1 and isset($log)) {
      $log->info("Delete file:\n" . $file);
    }

    return $file;

  } catch (Exception $e) {

    return $file;

  }

}

/**
 * Uploads thumbnails using data from $metadata and adds schedule processes
 * @param array $metadata
 * @return array Returns $metadata array without changes
 */
function ms3_thumbnail_upload ($metadata) {

  $paths = array();
  $upload_dir = wp_upload_dir();

  if (get_option('ms3_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    $log->debug("Metadata dump:\n" . ms3_dump($metadata));

  }

  // collect original file path
  if ( isset($metadata['file']) ) {

    $path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $metadata['file'];
    array_push($paths, $path);

    // set basepath for other sizes
    $file_info = pathinfo($path);
    $basepath = isset($file_info['extension'])
        ? str_replace($file_info['filename'] . "." . $file_info['extension'], "", $path)
        : $path;

  }

  // collect size files path
  if ( isset($metadata['sizes']) ) {

    foreach ( $metadata['sizes'] as $size ) {

      if ( isset($size['file']) ) {

        $path = $basepath . $size['file'];
        array_push($paths, $path);

      }

    }

  }

  // process paths
  foreach ($paths as $filepath) {
    
    if ( get_option('ms3_lazy_upload') ) {

      wp_schedule_single_event(time() + 2, 'ms3_schedule_upload', array($filepath, 0, true));

      if (get_option('ms3_debug') == 1 and isset($log)) {
        $log->info("Add schedule. File - " . $filepath);
      }

    } else {

      // upload file
      ms3_file_upload($filepath, 0, true);

      // log data
      if ( get_option('ms3_debug') ) {
        $log->info("Uploaded file - " . $filepath);
      }

    }

  }

  if ( get_option('ms3_debug') == 1 and isset($log) ) {

    $log->debug("Schedules dump: " . ms3_dump(_get_cron_array()));

  }

  return $metadata;

}

/**
 * @param string $pattern
 * @param int $flags = 0
 *
 * @return array|false
 */
function ms3_glob_recursive ($pattern, $flags = 0) {

  $files = glob($pattern, $flags);
  foreach (glob(dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
    $files = array_merge($files, ms3_glob_recursive($dir . DIRECTORY_SEPARATOR . basename($pattern), $flags));
  }

  return $files;

}

/**
 * Faster search in an array with a large number of files
 * @param string $needle
 * @param array $haystack
 * @return bool
 */
function ms3_in_array ($needle, $haystack) {

  $flipped_haystack = array_flip($haystack);
  if (isset($flipped_haystack[$needle])) {
    return true;
  }

  return false;

}

/**
 * Checks if the file falls under the mask specified in the settings.
 * @param string @path Full path to file
 * @return bool
 */
function ms3_check_for_sync ($path) {

  get_option('ms3_filter') != '' ?
    $mask = trim(get_option('ms3_filter')) :
    $mask = '*';

  if (get_option('ms3_debug') == 1) {

    $log = new Katzgrau\KLogger\Logger(plugin_dir_path(__FILE__) . '/logs', Psr\Log\LogLevel::DEBUG,
      array('prefix' => __FUNCTION__ . '_', 'extension' => 'log'));
    $log->info('File path: ' . $path);
    $log->info('Short path: ' . ms3_filepath($path));
    $log->info('File mask: ' . $mask);

  }

  $dir = dirname($path);
  if (get_option('ms3_debug') == 1 and isset($log)) {

    $log->info('Directory: ' . $dir);

  }

  $files = glob($dir . DIRECTORY_SEPARATOR . '{' . $mask . '}', GLOB_BRACE);
  if (get_option('ms3_debug') == 1 and isset($log)) {
    $log->debug("Files dump (full name):\n" . ms3_dump($files));
  }

  $count = count($files) - 1;
  for ($i = 0; $i <= $count; $i++) {
    $files[$i] = ms3_filepath($files[$i]);
  }

  if (get_option('ms3_debug') == 1 and isset($log)) {
    $log->debug("Files dump (full name):\n" . ms3_dump($files));
  }

  //$result = in_array(ms3_filepath($path), $files,true);
  $result = ms3_in_array(ms3_filepath($path), $files);
  if (get_option('ms3_debug') == 1 and isset($log)) {
    $result ? $log->info('Path found in files') : $log->info('Path not found in files');
  }

  return $result;

}

/**
 * Includes
 */
include_once('code/styles.php');
include_once('code/scripts.php');
include_once('code/settings.php');
include_once('code/actions.php');
include_once('code/filters.php');