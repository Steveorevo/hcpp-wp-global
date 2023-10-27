<?php
 if ( ! class_exists( 'WP_Global_Runtime') ) {
    class WP_Global_Runtime {
        public function __construct() {
            if ( ! isset( $_SERVER['HOME'] ) ) return;

            // Brute force hook muplugins_loaded event to load any wp-global plugins
            global $wp_filter;
            $wp_filter['muplugins_loaded'][0]['wpgr_load_plugins'] = array( 'function' =>  array(&$this, 'wpgr_load_plugins'), 'accepted_args' => 3 );
        }

        // Load any plugins from the user's web/wp-global folder
        public function wpgr_load_plugins() {
            $wp_global_folder = $_SERVER['HOME'] . '/web/wp-global';

            // Gather a list of folders that do not have .disabled suffix in the wp-global folder
            $plugin_folders = array();
            if ( is_dir( $wp_global_folder ) == true ) {
                $files = scandir( $wp_global_folder );
                foreach ( $files as $file ) {
                    if ( $file == '.' || $file == '..' ) continue;
                    if ( is_dir( $wp_global_folder . '/' . $file ) && substr( $file, -9 ) != '.disabled' ) {
                        $plugin_folders[] = $wp_global_folder . '/' . $file;
                    }
                }
            }else{
                return;
            }

            // Check if an existing JSON file exists in the user's home folder
            $find_plugin_files = true;
            $plugin_files = [];
            $wp_global_plugins_json = $_SERVER['HOME'] . '/tmp/wp-global-plugins.json';
            if ( file_exists( $wp_global_plugins_json ) ) {
                $json = file_get_contents( $wp_global_plugins_json );
                $plugin_files = json_decode( $json, true );

                // Check if list of plugin folders matches the JSON file
                $plugin_folders_list = implode( ',', $plugin_folders );
                $plugin_files_list = implode( ',', array_keys( $plugin_files ) );
                if ( $plugin_folders_list == $plugin_files_list ) {
                    $find_plugin_files = false; // Invalidate cache of wp-global-plugins.json
                }
            }
            
            // Check each plugin folder for a php file with a valid plugin header
            if ( $find_plugin_files ) {
                $plugin_files = [];
                foreach ( $plugin_folders as $plugin_folder ) {
                    $files = scandir( $plugin_folder );
                    foreach ( $files as $file ) {
                        if ( substr( $file, -4 ) == '.php' ) {
                            
                            // Read the first 60 lines of the file
                            $lines = file( $plugin_folder . '/' . $file, FILE_IGNORE_NEW_LINES );
                            $header = '';
                            for ( $i = 0; $i < 60; $i++ ) {
                                $header .= $lines[$i] . "\n";
                            }
    
                            // Check if the header contains the bare minimum of 'Plugin Name:' and 'Description:'
                            if ( preg_match( '/Plugin Name:/', $header ) && preg_match( '/Description:/', $header ) ) {
                                $plugin_files[$plugin_folder] = $plugin_folder . '/' . $file;
                            }
                        }
                    }
                }

                // Store the plugin_files as a JSON object in the user's home folder
                $json = json_encode( $plugin_files );
                file_put_contents( $wp_global_plugins_json, $json );
            }
            
            // Hook plugins_url to correct for wp-global folder
            add_filter( 'plugins_url', array( &$this, 'wpgr_plugins_url' ), 0 );
        
            // Load the plugin_files listed in the JSON file
            foreach ( $plugin_files as $folder => $file ) {
                include_once( $file );
            }
        }

        // TODO: Correct the plugins_url to point to the wp-global folder
        public function wpgr_plugins_url( $url ) {
            file_put_contents( '/tmp/wpgr_plugins_url.txt', $url, FILE_APPEND );
            // $wp_global_folder = $_SERVER['HOME'] . '/web/wp-global';
            // $url = str_replace( WP_PLUGIN_URL, $wp_global_folder, $url );
            return $url;
        }
    }
    global $wp_global_runtime;
    $wp_global_runtime = new WP_Global_Runtime();
 }