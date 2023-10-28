<?php
/**
 * Plugin Name: WP Global
 * Plugin URI: https://github.com/virtuosoft-dev/hcpp-wp-global
 * Description: Adds a "wp-global" plugins folder for HestiaCP user accounts; allowing plugins to be used by all WordPress sites without installation.
 * Version: 1.0.0
 * Author: Stephen J. Carnam
 *
 */

// Register the install and uninstall scripts
global $hcpp;
require_once( dirname(__FILE__) . '/wp-global.php' );

$hcpp->register_install_script( dirname(__FILE__) . '/install' );
$hcpp->register_uninstall_script( dirname(__FILE__) . '/uninstall' );
