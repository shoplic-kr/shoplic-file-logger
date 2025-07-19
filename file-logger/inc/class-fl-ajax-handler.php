<?php
/**
 * 파일 로거 - <span title="비동기 자바스크립트 및 XML. 페이지를 새로고침하지 않고 서버와 데이터를 주고받는 기술">AJAX</span> 핸들러 클래스
 *
 * @package FileLogger
 * @subpackage Ajax
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FL_Ajax_Handler
 * 파일 로거의 모든 <span title="비동기 자바스크립트 및 XML. 페이지를 새로고침하지 않고 서버와 데이터를 주고받는 기술">AJAX</span> 요청 처리
 */
class FL_Ajax_Handler {
    
    /**
     * 생성자
     */
    public function __construct() {
        // 로그 관련 AJAX 핸들러
        add_action( 'wp_ajax_fl_delete_log', array( $this, 'ajax_delete_log' ) );
        add_action( 'wp_ajax_fl_copy_log', array( $this, 'ajax_copy_log' ) );
        add_action( 'wp_ajax_fl_refresh_log', array( $this, 'ajax_refresh_log' ) );
        
        // 디버그 설정 AJAX 핸들러
        add_action( 'wp_ajax_fl_save_debug_settings', array( $this, 'ajax_save_debug_settings' ) );
        add_action( 'wp_ajax_fl_download_wp_config', array( $this, 'ajax_download_wp_config' ) );
    }
    
    /**
     * 로그 삭제를 위한 AJAX 핸들러
     */
    public function ajax_delete_log() {
        // 디버그 로깅
        error_log( 'FL Delete AJAX called' );
        error_log( 'POST data: ' . print_r( $_POST, true ) );
        
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'fl_ajax_nonce' ) ) {
            error_log( 'FL Delete: Nonce verification failed' );
            wp_send_json_error( 'Nonce verification failed' );
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            error_log( 'FL Delete: Permission denied' );
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        $plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( $_POST['plugin'] ) : '';
        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';
        
        if ( empty( $plugin ) || empty( $date ) ) {
            error_log( 'FL Delete: Missing parameters' );
            wp_send_json_error( 'Missing parameters' );
            return;
        }
        
        $log_file = FL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        error_log( 'FL Delete: Attempting to delete file: ' . $log_file );
        
        if ( file_exists( $log_file ) ) {
            // 파일을 삭제하는 대신 내용을 비움
            if ( file_put_contents( $log_file, '' ) !== false ) {
                error_log( 'FL Delete: File content cleared successfully' );
                wp_send_json_success();
            } else {
                error_log( 'FL Delete: Failed to clear file content' );
                wp_send_json_error( 'Failed to clear file content' );
            }
        } else {
            error_log( 'FL Delete: File not found' );
            wp_send_json_error( 'File not found' );
        }
    }
    
    /**
     * 로그 복사를 위한 AJAX 핸들러
     */
    public function ajax_copy_log() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'fl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }
        
        $plugin = sanitize_text_field( $_POST['plugin'] );
        $date = sanitize_text_field( $_POST['date'] );
        $log_file = FL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            wp_send_json_success( array( 'content' => $content ) );
        } else {
            wp_send_json_error();
        }
    }
    
    /**
     * 로그 새로고침을 위한 AJAX 핸들러
     */
    public function ajax_refresh_log() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'fl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die();
        }
        
        $plugin = sanitize_text_field( $_POST['plugin'] );
        $date = sanitize_text_field( $_POST['date'] );
        $log_file = FL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        $admin_viewer = new FL_Admin_Viewer();
        
        if ( file_exists( $log_file ) ) {
            $content = file_get_contents( $log_file );
            $formatted_content = $admin_viewer->format_log_content( $content );
            $size = size_format( filesize( $log_file ) );
            
            wp_send_json_success( array(
                'content' => $formatted_content,
                'size' => $size
            ) );
        } else {
            wp_send_json_success( array(
                'content' => '<p>로그가 없습니다.</p>',
                'size' => '0 B'
            ) );
        }
    }
    
    /**
     * 디버그 설정 저장을 위한 AJAX 핸들러
     */
    public function ajax_save_debug_settings() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'fl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
            return;
        }
        
        try {
            require_once FL_PLUGIN_DIR . '/vendor/WPConfigTransformer.php';
            
            $config_path = ABSPATH . 'wp-config.php';
            if ( ! file_exists( $config_path ) ) {
                wp_send_json_error( 'wp-config.php 파일을 찾을 수 없습니다.' );
                return;
            }
            
            if ( ! is_writable( $config_path ) ) {
                wp_send_json_error( 'wp-config.php 파일에 쓰기 권한이 없습니다.' );
                return;
            }
            
            $config_transformer = new WPConfigTransformer( $config_path );
            
            // POST에서 디버그 설정 가져오기
            $settings = array(
                'WP_DEBUG' => isset( $_POST['wp_debug'] ) && $_POST['wp_debug'] === '1',
                'WP_DEBUG_LOG' => isset( $_POST['wp_debug_log'] ) && $_POST['wp_debug_log'] === '1',
                'WP_DEBUG_DISPLAY' => isset( $_POST['wp_debug_display'] ) && $_POST['wp_debug_display'] === '1',
                'SCRIPT_DEBUG' => isset( $_POST['script_debug'] ) && $_POST['script_debug'] === '1',
                'SAVEQUERIES' => isset( $_POST['savequeries'] ) && $_POST['savequeries'] === '1',
                'WP_DISABLE_FATAL_ERROR_HANDLER' => isset( $_POST['wp_disable_fatal_error_handler'] ) && $_POST['wp_disable_fatal_error_handler'] === '1'
            );
            
            // 각 상수 업데이트
            foreach ( $settings as $constant => $value ) {
                $config_transformer->update(
                    'constant',
                    $constant,
                    $value ? 'true' : 'false',
                    array(
                        'raw' => true,
                        'normalize' => true,
                        'add' => true
                    )
                );
            }
            
            wp_send_json_success( array(
                'message' => '설정이 성공적으로 저장되었습니다.',
                'settings' => $settings
            ) );
            
        } catch ( Exception $e ) {
            wp_send_json_error( '설정 저장 중 오류가 발생했습니다: ' . $e->getMessage() );
        }
    }
    
    /**
     * <span title="워드프레스의 주요 설정 파일">wp-config.php</span> 백업 다운로드를 위한 AJAX 핸들러
     */
    public function ajax_download_wp_config() {
        if ( ! wp_verify_nonce( $_GET['nonce'], 'fl_ajax_nonce' ) || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Permission denied' );
        }
        
        $config_path = ABSPATH . 'wp-config.php';
        
        if ( ! file_exists( $config_path ) ) {
            wp_die( 'wp-config.php file not found' );
        }
        
        // 다운로드를 위한 <span title="HTTP 요청이나 응답에 포함되는 추가 정보">헤더</span> 설정
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="wp-config-backup-' . date( 'Y-m-d-H-i-s' ) . '.php"' );
        header( 'Content-Length: ' . filesize( $config_path ) );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
        
        readfile( $config_path );
        exit;
    }
}