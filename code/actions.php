<?php

add_action('admin_menu', 'ms3_create_menu');
add_action('admin_init', 'ms3_register_settings');

add_action('add_attachment', 'ms3_storage_upload', 100, 1);

add_action('admin_enqueue_scripts', 'ms3_styles');
add_action('admin_enqueue_scripts', 'ms3_scripts');

add_action('wp_ajax_ms3_test_connection', 'ms3_test_connection');

add_action('ms3_file_delete', 'ms3_file_delete');
add_action('ms3_schedule_upload', 'ms3_file_upload');