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
        }

        // Respond to invoke-plugin wp_global_patch_all.
        public function hcpp_invoke_plugin( $args ) {
            if ( $args[0] === 'wp_global_patch_all' ) {
                $this->patch_template_files();
                $this->patch_live_conf_files();
                $this->restart_php_fpm();
            }
            return $args;
        }

        // Remove wp-global from open_basedir in live and template conf files
        public function hcpp_plugin_uninstall() {
            $this->unpatch_live_conf_files();
            $this->unpatch_template_files();
            $this->restart_php_fpm();
        }

        // Restart all PHP-FPM services
        public function restart_php_fpm() {
            $php_services = ['php5.6-fpm', 'php7.0-fpm', 'php7.1-fpm', 'php7.2-fpm',
                'php7.3-fpm', 'php7.4-fpm', 'php7.4xdbg-fpm', 'php8.0-fpm', 'php8.0xdbg-fpm',
                'php8.1-fpm', 'php8.1xdbg-fpm', 'php8.2-fpm', 'php8.2xdbg-fpm'];
            $cmd = '';
            foreach( $php_services as $service ) {
                $cmd .= 'service ' . $service . ' restart; ';
            }
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

        // Patch live conf files to include wp-global in open_basedir
        public function patch_live_conf_files() {

        }

        // Unpatch live conf files to remove wp-global from open_basedir
        public function unpatch_live_conf_files() {

        }

        // Patch template files to include wp-global in open_basedir
        public function patch_template_files() {

        }

        // Unpatch template files to remove wp-global from open_basedir
        public function unpatch_template_files() {

        }
    }
    new WP_Global();
 }