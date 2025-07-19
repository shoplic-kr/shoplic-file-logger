<?php
/**
 * 파일 로거 - 로거 클래스
 *
 * @package FileLogger
 * @subpackage Logger
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FL
 * 파일 로거의 메인 로거 클래스
 */
class FL {
    
    /**
     * 로그 레벨
     */
    const LOG = 'LOG';
    const ERROR = 'ERROR';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const WARNING = 'WARNING';
    
    /**
     * 메시지 로그 기록
     */
    public static function log( $message, $data = null ) {
        self::write( self::LOG, $message, $data );
    }
    
    /**
     * 오류 로그 기록
     */
    public static function error( $message, $data = null ) {
        self::write( self::ERROR, $message, $data );
    }
    
    /**
     * 정보 로그 기록
     */
    public static function info( $message, $data = null ) {
        self::write( self::INFO, $message, $data );
    }
    
    /**
     * <span title="개발 중 문제를 해결하기 위해 사용하는 상세한 정보 로그">디버그</span> 정보 로그 기록
     */
    public static function debug( $message, $data = null ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            self::write( self::DEBUG, $message, $data );
        }
    }
    
    /**
     * 경고 로그 기록
     */
    public static function warning( $message, $data = null ) {
        self::write( self::WARNING, $message, $data );
    }
    
    /**
     * 로그 파일에 쓰기
     */
    private static function write( $level, $message, $data = null ) {
        // 호출자 정보 가져오기
        $backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 );
        $caller = isset( $backtrace[2] ) ? $backtrace[2] : $backtrace[1];
        
        // 어떤 플러그인이 호출했는지 확인
        $plugin_name = self::get_calling_plugin( $caller['file'] );
        
        // 필요한 경우 로그 디렉토리 생성
        $log_dir = FL_LOG_DIR . '/' . $plugin_name;
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            
            // 보안을 위해 .htaccess 추가
            $htaccess = FL_LOG_DIR . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, 'deny from all' );
            }
        }
        
        // 로그 항목 생성
        $timestamp = date( 'Y-m-d H:i:s' );
        $file_info = basename( $caller['file'] ) . ':' . $caller['line'];
        
        $log_entry = sprintf(
            "[%s] [%s] %s - %s",
            $timestamp,
            $level,
            $file_info,
            $message
        );
        
        // 데이터가 제공된 경우 추가
        if ( $data !== null ) {
            $log_entry .= "\n    Data: " . print_r( $data, true );
        }
        
        $log_entry .= "\n";
        
        // 파일에 쓰기
        $log_file = $log_dir . '/log-' . date( 'Y-m-d' ) . '.log';
        error_log( $log_entry, 3, $log_file );
    }
    
    /**
     * 어떤 플러그인이 호출했는지 확인
     */
    private static function get_calling_plugin( $file ) {
        $file = str_replace( '\\', '/', $file );
        $plugins_dir = str_replace( '\\', '/', WP_PLUGIN_DIR );
        $theme_dir = str_replace( '\\', '/', get_theme_root() );
        
        // 플러그인인지 확인
        if ( strpos( $file, $plugins_dir ) !== false ) {
            $relative = str_replace( $plugins_dir . '/', '', $file );
            $parts = explode( '/', $relative );
            return $parts[0]; // 플러그인 폴더 이름
        }
        
        // 테마인지 확인
        if ( strpos( $file, $theme_dir ) !== false ) {
            return 'theme';
        }
        
        // 워드프레스 <span title="워드프레스의 핵심 기능을 담당하는 기본 파일들">코어</span>인지 확인
        if ( strpos( $file, ABSPATH ) !== false && strpos( $file, 'wp-content' ) === false ) {
            return 'wordpress-core';
        }
        
        return 'unknown';
    }
    
    /**
     * 로그 디렉토리 가져오기
     */
    public static function get_log_dir() {
        return FL_LOG_DIR;
    }
    
    /**
     * <span title="개발 중 문제를 해결하기 위해 사용하는 모드">디버그 모드</span>가 활성화되었는지 확인
     */
    public static function is_debug_mode() {
        return defined( 'WP_DEBUG' ) && WP_DEBUG;
    }
    
    /**
     * 오래된 로그 정리 (7일 이상 된 로그)
     */
    public static function cleanup_old_logs() {
        if ( ! is_dir( FL_LOG_DIR ) ) {
            return;
        }
        
        $dirs = glob( FL_LOG_DIR . '/*', GLOB_ONLYDIR );
        $now = time();
        
        foreach ( $dirs as $dir ) {
            $files = glob( $dir . '/log-*.log' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    if ( $now - filemtime( $file ) >= 7 * 24 * 60 * 60 ) {
                        unlink( $file );
                    }
                }
            }
        }
    }
}