<?php

/**
 * Function registration js files
 */
function ms3_scripts() {

  wp_enqueue_script('ms3-core-js', plugins_url('../assets/scripts/core.js', __FILE__), array('jquery'), '1.4.0', true);

}