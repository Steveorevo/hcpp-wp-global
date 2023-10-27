<?php
/**
 * Extend the HestiaCP Pluginable object with our WP Global Runtime
 * object for loading plugins from the user's web/wp-globals folder.
 * 
 * @version 1.0.0
 * @license GPL-3.0
 * @link https://github.com/virtuosoft-dev/hcpp-wp-global
 * 
 */

 if ( ! class_exists( 'WP_Global_Runtime') ) {
    class WP_Global_Runtime {
        public function __construct() {
            if ( ! isset( $_SERVER['HOME'] ) ) return;

            // Brute force hook muplugins_loaded event to load any wp-global plugins
            global $wp_filter;
            $wp_filter['muplugins_loaded'][0]['wpgr_load_plugins'] = array( 'function' =>  array(&$this, 'wpgr_load_plugins'), 'accepted_args' => 3 );
            $wp_filter['init'][1]['wpgr_init'] = array( 'function' => array(&$this, 'wpgr_init') );
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

        // Correct the plugins_url to point to the wp-global folder
        public function wpgr_plugins_url( $url ) {
            $fix_wp_global = WP_PLUGIN_URL . $_SERVER['HOME'] . '/web/wp-global/';
            $url = str_replace( $fix_wp_global, WP_PLUGIN_URL . '/wp-global/', $url );
            return $url;
        }

        // Serve/process files from the wp-global folder
        public function wpgr_init() {
            $wp_global_url = WP_PLUGIN_URL . '/wp-global/';
            $request_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            if ( substr( $request_url, 0, strlen( $wp_global_url ) ) !== $wp_global_url ) return;

            // Check if the request path is within the wp-global folder
            $wp_global_path =  realpath( $_SERVER['HOME'] . '/web/wp-global' ) . '/';               
            $request_path = str_replace( $wp_global_url, $wp_global_path, $request_url );
            $request_path = realpath( $request_path );
            if ( $request_path !== false && strpos( $request_path, $wp_global_path ) === 0 ) {
                
                // Serve the index.html, index.htm, or index.php if its just a directory
                if ( is_dir( $request_path ) ) {
                    if ( file_exists( $request_path . '/index.html') ) {
                        $request_path = $request_path . '/index.html';
                    }else{
                        if ( file_exists( $request_path . '/index.htm') ) {
                            $request_path = $request_path . '/index.htm';
                        }else{
                            $request_path = $request_path . '/index.php';
                        }
                    }
                    if ( file_exists( $request_path ) ) {

                        // Check if the directory contains an index.php file
                        include( $request_path );
                        exit();
                    } else {

                        // Return a 403 Forbidden error
                        header( 'HTTP/1.0 403 Forbidden' );
                        exit();
                    }

                // Serve the requested file content
                } else if ( is_file( $request_path ) ) {

                    // Process the php file
                    if ( substr( $request_path, -4 ) == '.php' ) {
                        include( $request_path );
                        exit();
                    } else {
                        
                        // Get the content mime type by extension using Nginx' mime.types
                        $mime_types = trim( shell_exec( 'cat /etc/nginx/mime.types' ) );
                        $mime_types = preg_replace( '/^.*\{(.*)\}.*$/s', '$1', $mime_types );
                        $mime_types = str_replace( "\n", "", $mime_types );
                        $mime_types = explode( ";", $mime_types );
                        $mime_types = array_filter( $mime_types, function( $line ) {
                            $line = trim( $line );
                            if ( substr( $line, 0, 1 ) == '#' ) return false;
                            if ( $line == '' ) return false;
                            return true;
                        } );
                        
                        // Create an extension to mime type array
                        $mime_type_by_extension = [];
                        foreach( $mime_types as $line ) {
                            $line = trim( $line );
                            $line = explode( ' ', $line );
                            $mime_type = array_shift( $line );
                            foreach( $line as $extension ) {
                                $extension = trim( $extension );
                                if ( $extension == '' ) continue;
                                $mime_type_by_extension[$extension] = $mime_type;
                            }
                        }
                        $ext = pathinfo($request_path, PATHINFO_EXTENSION);
                        if ( isset( $mime_type_by_extension[$ext] ) ) {

                            // Serve the known mime type
                            header('Content-Type: ' . $mime_type_by_extension[$ext] );
                            readfile( $request_path );
                        }else{

                            // Return a 404 Not Found error
                            header( 'HTTP/1.0 415 Unsupported Media Type' );
                            exit();
                        }
                        echo $ext . '<br>';
                        echo '<pre>' . json_encode( $mime_type_by_extension, JSON_PRETTY_PRINT ) . '</pre>';
                        exit();
                    }
                } else {
                    // Return a 404 Not Found error
                    header( 'HTTP/1.0 404 Not Found' );
                    exit();
                }
            }else{

                // Return a 403 Forbidden error
                header( 'HTTP/1.0 403 Forbidden' );
                exit();
            }
        }
    }
    global $wp_global_runtime;
    $wp_global_runtime = new WP_Global_Runtime();
 }