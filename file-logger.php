<?php
/**
 * Plugin Name: 쇼플릭 파일 로거
 * Plugin URI: https://shoplic.kr
 * Description: 파일에 로그를 남깁니다. AI에게 로그 데이터를 넘길때 사용할 수 있습니다.
 * Version: 1.0.0
 * Author: shoplic
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants only if not already defined
if ( ! defined( 'FL_VERSION' ) ) {
    define( 'FL_VERSION', '1.0.0' );
}
if ( ! defined( 'FL_LOG_DIR' ) ) {
    define( 'FL_LOG_DIR', WP_CONTENT_DIR . '/fl-logs' );
}
if ( ! defined( 'FL_PLUGIN_DIR' ) ) {
    define( 'FL_PLUGIN_DIR', dirname( __FILE__ ) . '/file-logger' );
}

// Load required files
require_once FL_PLUGIN_DIR . '/inc/class-fl-logger.php';
require_once FL_PLUGIN_DIR . '/inc/class-fl-admin-viewer.php';
require_once FL_PLUGIN_DIR . '/inc/class-fl-ajax-handler.php';
require_once FL_PLUGIN_DIR . '/inc/class-fl-debug-settings.php';
require_once FL_PLUGIN_DIR . '/inc/class-fl-sysinfo-reporter.php';
require_once FL_PLUGIN_DIR . '/inc/class-wpconfigtransformer.php';
require_once FL_PLUGIN_DIR . '/inc/helpers.php';

// Initialize the plugin
if ( ! function_exists( 'fl_init_plugin' ) ) {
    function fl_init_plugin() {
        // Create log directory if it doesn't exist
        if ( ! file_exists( FL_LOG_DIR ) ) {
            wp_mkdir_p( FL_LOG_DIR );
            
            // Add .htaccess for security
            $htaccess = FL_LOG_DIR . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, 'deny from all' );
            }
        }
        
        // Schedule cleanup if not already scheduled
        if ( ! wp_next_scheduled( 'fl_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'fl_cleanup_logs' );
        }
        
        // Initialize components if in admin
        if ( is_admin() ) {
            new FL_Admin_Viewer();
            new FL_Ajax_Handler();
        }
    }
}

// Hook initialization
add_action( 'init', 'fl_init_plugin' );

// Schedule cleanup
add_action( 'fl_cleanup_logs', array( 'FL', 'cleanup_old_logs' ) );

// For plugin activation (when used as regular plugin)
register_activation_hook( __FILE__, 'fl_init_plugin' );

// For plugin deactivation
if ( ! function_exists( 'fl_deactivate_plugin' ) ) {
    function fl_deactivate_plugin() {
        // Remove scheduled cleanup
        wp_clear_scheduled_hook( 'fl_cleanup_logs' );
    }
}
register_deactivation_hook( __FILE__, 'fl_deactivate_plugin' );