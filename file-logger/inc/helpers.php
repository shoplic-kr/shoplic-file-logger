<?php
/**
 * 파일 로거 - 헬퍼 함수
 *
 * @package FileLogger
 * @subpackage Helpers
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ====================================================================
 * 더 쉬운 사용을 위한 헬퍼 함수
 * ====================================================================
 * 이 함수들은 매번 \FL::을 입력하지 않고도 FL 로거를
 * 더 간편하게 사용할 수 있는 방법을 제공합니다.
 */

// 일반 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'fl_log' ) ) {
    function fl_log( $run, $message, $data = null ) {
        $run && \FL::log( $message, $data );
    }
}

// 오류 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'fl_error' ) ) {
    function fl_error( $run, $message, $data = null ) {
        $run && \FL::error( $message, $data );
    }
}

// 정보 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'fl_info' ) ) {
    function fl_info( $run, $message, $data = null ) {
        $run && \FL::info( $message, $data );
    }
}

// 디버그 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'fl_debug' ) ) {
    function fl_debug( $run, $message, $data = null ) {
        $run && \FL::debug( $message, $data );
    }
}

// 경고 로깅을 위한 헬퍼 함수
if ( ! function_exists( 'fl_warning' ) ) {
    function fl_warning( $run, $message, $data = null ) {
        $run && \FL::warning( $message, $data );
    }
}

/**
 * ====================================================================
 * 사용법 (How to Use)
 * ====================================================================
 * 
 * 1. <span title="필수 플러그인. 워드프레스가 자동으로 로드하는 플러그인">MU-Plugin</span>으로 사용하기:
 *    - 이 파일을 /wp-content/mu-plugins/ 디렉토리에 복사
 *    - 파일명은 원하는 대로 변경 가능 (예: file-logger.php)
 *    - 자동으로 활성화됨
 * 
 * 2. 일반 플러그인으로 사용하기:
 *    - 이 파일을 /wp-content/plugins/file-logger/ 디렉토리에 복사
 *    - 워드프레스 관리자에서 플러그인 활성화
 * 
 * 3. 로그 기록하기:
 *    // <span title="더 간편하게 사용할 수 있도록 만들어진 함수">헬퍼 함수</span> 사용 (권장)
 *    fl_log('일반 로그 메시지');
 *    fl_error('에러 발생!');
 *    fl_info('정보성 메시지');
 *    fl_debug('디버그 메시지'); // <span title="워드프레스 디버그 모드 상수">WP_DEBUG</span>가 true일 때만 기록
 *    fl_warning('경고 메시지');
 *    
 *    // 클래스 직접 호출
 *    \FL::log('일반 로그 메시지');
 *    \FL::error('에러 발생!');
 *    
 *    // 데이터와 함께 로그 기록
 *    fl_log('사용자 정보', $user_data);
 *    fl_error('에러 발생', $error_details);
 * 
 * 4. 로그 확인하기:
 *    - 워드프레스 관리자 > File Logger 메뉴
 *    - 로그 파일 위치: /wp-content/fl-logs/
 * 
 * 5. 주요 기능:
 *    - 플러그인/테마별 자동 분류
 *    - 날짜별 로그 파일 생성
 *    - 7일 이상 된 로그 자동 삭제
 *    - 로그 레벨별 색상 구분
 *    - 로그 다운로드, 복사, 삭제 기능
 * 
 * ====================================================================
 */