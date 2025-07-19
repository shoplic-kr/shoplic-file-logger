<?php
/**
 * 파일 로거 - 디버그 설정 클래스
 *
 * @package FileLogger
 * @subpackage Debug
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FL_Debug_Settings
 * 워드프레스 디버그 상수 설정 처리
 */
class FL_Debug_Settings {
    
    /**
     * 디버그 설정 페이지 렌더링
     */
    public function render_page() {
        // 현재 상수 값 가져오기
        $current_settings = $this->get_current_settings();
        ?>
        <div class="fl-debug-settings-wrap" style="margin-top: 20px;">
            
            <div class="notice notice-info">
                <p>
                    <strong>주의:</strong>
                    이 설정들은 <span title="워드프레스의 주요 설정 파일">wp-config.php</span> 파일을 직접 수정합니다. 변경 전 백업을 권장합니다.
                </p>
            </div>
            
            <div style="margin-bottom: 20px;">
                <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=fl_download_wp_config&nonce=' . wp_create_nonce( 'fl_ajax_nonce' ) ) ); ?>" 
                   class="button button-secondary">
                    wp-config.php 백업 다운로드
                </a>
            </div>
            
            <form id="fl-debug-settings-form">
                <table class="form-table">
                    <tbody>
                        <!-- WP_DEBUG -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug">
                                    <span title="워드프레스 디버그 모드를 활성화하는 상수">WP_DEBUG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug" 
                                           name="wp_debug" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG'] ); ?>>
                                    워드프레스 <span title="개발 중 문제를 해결하기 위해 사용하는 모드">디버그 모드</span> 활성화
                                </label>
                                <p class="description">
                                    <span title="프로그래밍 언어">PHP</span> 오류, 알림, 경고를 표시하거나 기록합니다. 개발 환경에서만 사용하세요.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DEBUG_LOG -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug_log">
                                    <span title="디버그 메시지를 파일에 기록하는 상수">WP_DEBUG_LOG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug_log" 
                                           name="wp_debug_log" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG_LOG'] ); ?>>
                                    디버그 로그 파일 생성
                                </label>
                                <p class="description">
                                    오류를 /wp-content/debug.log 파일에 저장합니다. <span title="워드프레스 디버그 모드를 활성화하는 상수">WP_DEBUG</span>가 true일 때만 작동합니다.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DEBUG_DISPLAY -->
                        <tr>
                            <th scope="row">
                                <label for="wp_debug_display">
                                    <span title="오류를 화면에 표시할지 결정하는 상수">WP_DEBUG_DISPLAY</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_debug_display" 
                                           name="wp_debug_display" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DEBUG_DISPLAY'] ); ?>>
                                    화면에 오류 표시
                                </label>
                                <p class="description">
                                    오류를 웹페이지에 직접 표시합니다. 운영 사이트에서는 false로 설정하세요.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- SCRIPT_DEBUG -->
                        <tr>
                            <th scope="row">
                                <label for="script_debug">
                                    <span title="스크립트 디버그 모드를 활성화하는 상수">SCRIPT_DEBUG</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="script_debug" 
                                           name="script_debug" 
                                           value="1" 
                                           <?php checked( $current_settings['SCRIPT_DEBUG'] ); ?>>
                                    스크립트 디버그 모드
                                </label>
                                <p class="description">
                                    워드프레스가 압축되지 않은 <span title="자바스크립트/캐스케이딩 스타일 시트">JS/CSS</span> 파일을 사용합니다. 테마/플러그인 개발 시 유용합니다.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- SAVEQUERIES -->
                        <tr>
                            <th scope="row">
                                <label for="savequeries">
                                    <span title="데이터베이스 쿼리를 저장하는 상수">SAVEQUERIES</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="savequeries" 
                                           name="savequeries" 
                                           value="1" 
                                           <?php checked( $current_settings['SAVEQUERIES'] ); ?>>
                                    <span title="데이터베이스에 요청하는 SQL 명령어">데이터베이스 쿼리</span> 저장
                                </label>
                                <p class="description">
                                    모든 <span title="데이터베이스에 요청하는 SQL 명령어">데이터베이스 쿼리</span>를 메모리에 저장합니다. 성능 분석에 유용하지만 메모리 사용량이 증가합니다.
                                </p>
                            </td>
                        </tr>
                        
                        <!-- WP_DISABLE_FATAL_ERROR_HANDLER -->
                        <?php if ( version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) ) : ?>
                        <tr>
                            <th scope="row">
                                <label for="wp_disable_fatal_error_handler">
                                    <span title="치명적 오류 핸들러를 비활성화하는 상수">WP_DISABLE_FATAL_ERROR_HANDLER</span>
                                </label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="wp_disable_fatal_error_handler" 
                                           name="wp_disable_fatal_error_handler" 
                                           value="1" 
                                           <?php checked( $current_settings['WP_DISABLE_FATAL_ERROR_HANDLER'] ); ?>>
                                    <span title="사이트를 다운시킬 수 있는 심각한 오류">치명적 오류</span> 핸들러 비활성화
                                </label>
                                <p class="description">
                                    워드프레스의 <span title="치명적 오류 발생 시 자동으로 대체 코드를 실행하는 기능">자동 복구 모드</span>를 비활성화합니다. 개발 중 실제 오류를 확인할 때 유용합니다. (워드프레스 5.2 이상)
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary" id="fl-save-debug-settings">
                        설정 저장
                    </button>
                    <span class="spinner"></span>
                </p>
            </form>
        </div>
        
        <style>
            .fl-debug-settings-wrap .form-table th {
                width: 250px;
            }
            .fl-debug-settings-wrap .spinner {
                float: none;
                margin-left: 10px;
            }
            .fl-debug-settings-wrap .notice {
                margin-left: 0;
            }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#fl-debug-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $('#fl-save-debug-settings');
                var $spinner = $form.find('.spinner');
                
                // 로딩 상태 표시
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                
                // 데이터 준비
                var data = {
                    action: 'fl_save_debug_settings',
                    nonce: fl_ajax.nonce,
                    wp_debug: $('#wp_debug').is(':checked') ? '1' : '0',
                    wp_debug_log: $('#wp_debug_log').is(':checked') ? '1' : '0',
                    wp_debug_display: $('#wp_debug_display').is(':checked') ? '1' : '0',
                    script_debug: $('#script_debug').is(':checked') ? '1' : '0',
                    savequeries: $('#savequeries').is(':checked') ? '1' : '0',
                    wp_disable_fatal_error_handler: $('#wp_disable_fatal_error_handler').is(':checked') ? '1' : '0'
                };
                
                // AJAX 요청 전송
                $.post(fl_ajax.ajax_url, data, function(response) {
                    if (response.success) {
                        // 성공 메시지 표시
                        var $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                        $form.before($notice);
                        
                        // 5초 후 자동 사라짐
                        setTimeout(function() {
                            $notice.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    } else {
                        // 오류 메시지 표시
                        var $notice = $('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>');
                        $form.before($notice);
                    }
                })
                .fail(function() {
                    var $notice = $('<div class="notice notice-error is-dismissible"><p>설정 저장 중 오류가 발생했습니다.</p></div>');
                    $form.before($notice);
                })
                .always(function() {
                    // 로딩 상태 초기화
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 현재 디버그 상수 설정 가져오기
     */
    private function get_current_settings() {
        $settings = array(
            'WP_DEBUG' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'WP_DEBUG_LOG' => defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG,
            'WP_DEBUG_DISPLAY' => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
            'SCRIPT_DEBUG' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
            'SAVEQUERIES' => defined( 'SAVEQUERIES' ) && SAVEQUERIES,
            'WP_DISABLE_FATAL_ERROR_HANDLER' => defined( 'WP_DISABLE_FATAL_ERROR_HANDLER' ) && WP_DISABLE_FATAL_ERROR_HANDLER
        );
        
        return $settings;
    }
}