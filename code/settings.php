<?php

function ms3_register_settings() {

  register_setting('ms3_settings', 'ms3_endpoint');
  register_setting('ms3_settings', 'ms3_container');
  register_setting('ms3_settings', 'ms3_secret');
  register_setting('ms3_settings', 'ms3_key');
  register_setting('ms3_settings', 'upload_url_path');
  register_setting('ms3_settings', 'ms3_storage_path');
  register_setting('ms3_settings', 'upload_path');
  register_setting('ms3_settings', 'ms3_storage_file_only');
  register_setting('ms3_settings', 'ms3_storage_file_delete');
  register_setting('ms3_settings', 'ms3_lazy_upload');
  register_setting('ms3_settings', 'ms3_filter');
  register_setting('ms3_settings', 'ms3_debug');

}
