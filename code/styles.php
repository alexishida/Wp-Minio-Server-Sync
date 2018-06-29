<?php

/**
 * Function registration css files
 */
function ms3_styles() {

  wp_enqueue_style('ms3-flexboxgrid', plugins_url('../assets/styles/flexboxgrid.min.css', __FILE__) );
  wp_enqueue_style('ms3-core-css', plugins_url('../assets/styles/core.css', __FILE__) );

}