<?php
 if ( ! class_exists( 'WP_Global_Runtime') ) {
    class WP_Global_Runtime() {
        public function __construct() {

        }
    }
    global $wp_global_runtime;
    $wp_global_runtime = new WP_Global_Runtime();
 }