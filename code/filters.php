<?php

add_filter('wp_generate_attachment_metadata', 'ms3_thumbnail_upload', 100, 1);

if ( get_option('ms3_storage_file_delete') == 1 ) {
  add_filter('wp_delete_file', 'ms3_storage_delete', 10, 1);
}