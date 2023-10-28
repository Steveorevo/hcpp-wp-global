<?php
/**
 * Extend the HestiaCP Pluginable object with WP Global object for
 * patching HestiaCP to support WordPress wp-global folder.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-wp-global
 * 
 */

 if ( ! class_exists( 'WP_Global') ) {
    class WP_Global {
        public function __construct() {
            global $hcpp;
            $hcpp->wp_global = $this;
            $hcpp->add_action( 'post_add_user', [ $this, 'post_add_user' ] );
            $hcpp->add_action( 'hcpp_invoke_plugin', [ $this, 'hcpp_invoke_plugin' ] );
            $hcpp->add_action( 'hcpp_plugin_uninstall', [ $this, 'hcpp_plugin_uninstall' ] );
            $hcpp->add_action( 'hcpp_plugin_disabled', [ $this, 'hcpp_plugin_disabled' ] );
            $hcpp->add_action( 'hcpp_plugin_enabled', [ $this, 'hcpp_plugin_enabled' ] );
        }

        // Ensure we patch files and create/restore wp-global folders for users on enabled
        public function hcpp_plugin_enabled( $plugin ) {
            if ( $plugin != 'wp-global' ) return $plugin;
        
            // Create/restore wp-global folders
            $husers = $this->get_hestia_users();
            foreach( $husers as $user ) {
                $tmp_wp_global = "/home/$user/tmp/wp-global";
                $wp_global = "/home/$user/web/wp-global";
                if ( is_dir( $tmp_wp_global ) == true && is_dir( $wp_global ) == false ) {
                    global $hcpp;
                    $hcpp->log("restoring folder from $tmp_wp_global to $wp_global");
                    rename( $tmp_wp_global, $wp_global );
                }else{
                    if ( ! is_dir( $wp_global ) ) {
                        mkdir( $wp_global, 0755, true );
                    }    
                }
                chown( $wp_global, $user );
                chgrp( $wp_global, $user );
            }

            // Patch files
            $this->patch_template_files();
            $this->patch_live_conf_files();
            $this->restart_php_fpm();
            return $plugin;
        }

        // Move wp-global folders to /home/user/tmp
        public function hcpp_plugin_disabled( $plugin ) {
            $husers = $this->get_hestia_users();
            foreach( $husers as $user ) {
                $tmp_wp_global = "/home/$user/tmp/wp-global";
                $wp_global = "/home/$user/web/wp-global";
                if ( is_dir( $wp_global ) ) {
                    rename( $wp_global, $tmp_wp_global );
                }
            }
        }

        // Respond to invoke-plugin wp_global_patch_all.
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] === 'wp_global_patch_all' ) {
                $this->hcpp_plugin_enabled( 'wp-global' );
            }
            return $args;
        }

        // Remove wp-global from open_basedir in live and template conf files
        public function hcpp_plugin_uninstall() {
            $this->unpatch_live_conf_files();
            $this->unpatch_template_files();
            $this->restart_php_fpm();
        }

        // Restart all PHP-FPM services after delay in script (because restart kills this PHP itself)
        public function restart_php_fpm() {
            global $hcpp;
            $cmd = "nohup " . __DIR__ . "/restart-php-fpm.sh > /dev/null 2>&1 &";
            $cmd = $hcpp->do_action( 'wp_global_restart_php_fpm', $cmd );
            shell_exec( $cmd );
        }
        
        // Gather list of HestiaCP users (not incl. admin)
        public function get_hestia_users() {
            // Gather list of HestiaCP users
            $dir = '/usr/local/hestia/data/users';
            $husers = [];
            if ( is_dir( $dir ) ) {
                $husers = array_diff( scandir( $dir ), array( '..', '.', 'admin' ) );
                if ( empty( $husers ) ) {
                    exit;
                }
            }
            $husers = array_values( $husers );
            return $husers;
        }

        // Get list of live PHP-FPM pool.d conf files for HestiaCP users
        public function get_live_conf_files() {

            // Get list of all PHP-FPM pool.d conf files
            $conf_files = [];
            $folders = glob( '/etc/php/*/fpm/pool.d/', GLOB_ONLYDIR );
            foreach ( $folders as $folder ) {
                $conf_files = array_merge( $conf_files, glob( $folder . '*.conf' ) );
            }

            // Reduce list to config files belonging to HestiaCP users
            $husers = $this->get_hestia_users();
            $hconf_files = [];
            foreach( $conf_files as $file ) {
                $content = file_get_contents( $file );
                $content = str_replace( ' ', '', $content );

                // Find line with user=
                $user = '';
                preg_match( '/user=(.*)/', $content, $user );
                $user = $user[1];
                if ( in_array( $user, $husers ) ) {
                    $hconf_files[$file] = $user;
                }
            }

            // Return list of conf files as key and corresponding HestiaCP user as value
            return $hconf_files;
        }

        // Get list of template PHP-FPM pool.d conf files
        public function get_template_conf_files() {

            $folderPath = "/usr/local/hestia/data/templates/web/php-fpm";
            $files = glob( "$folderPath/*.tpl" );
            $hconf_files = [];
            foreach( $files as $file ) {
                if ( strpos( $file, 'no-php.tpl' ) !== false ) {
                    continue;
                }
                $hconf_files[] = $file;
            }
            return $hconf_files;
        }
        
        // Patch live conf files to include wp-global in open_basedir
        public function patch_live_conf_files() {
            $hconf_files = $this->get_live_conf_files();
            global $hcpp;
            foreach( $hconf_files as $file => $user ) {
                $hcpp->patch_file(
                    $file,
                    ":/usr/local/hestia/plugins:",
                    ":/usr/local/hestia/plugins:/home/$user/web/wp-global:",
                    false // Don't create backup
                );
            }
        }

        // Unpatch live conf files to remove wp-global from open_basedir
        public function unpatch_live_conf_files() {
            $hconf_files = $this->get_live_conf_files();
            global $hcpp;
            foreach( $hconf_files as $file => $user ) {
                $hcpp->patch_file(
                    $file,
                    ":/usr/local/hestia/plugins:/home/$user/web/wp-global:",
                    ":/usr/local/hestia/plugins:",
                    false // Don't create backup
                );
            }
        }

        // Patch template files to include wp-global in open_basedir
        public function patch_template_files() {
            $hconf_files = $this->get_template_conf_files();
            global $hcpp;
            foreach( $hconf_files as $file ) {
                $hcpp->patch_file(
                    $file,
                    ":/usr/local/hestia/plugins:",
                    ":/usr/local/hestia/plugins:/home/%user%/web/wp-global:",
                    false // Don't create backup
                );
            }
        }

        // Unpatch template files to remove wp-global from open_basedir
        public function unpatch_template_files() {
            $hconf_files = $this->get_template_conf_files();
            global $hcpp;
            foreach( $hconf_files as $file ) {
                $hcpp->patch_file(
                    $file,
                    ":/usr/local/hestia/plugins:/home/%user%/web/wp-global:",
                    ":/usr/local/hestia/plugins:",
                    false // Don't create backup
                );
            }
        }
    }
    new WP_Global();
 }