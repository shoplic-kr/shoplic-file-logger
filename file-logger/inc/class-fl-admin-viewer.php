<?php
/**
 * 파일 로거 - 관리자 뷰어 클래스
 *
 * @package FileLogger
 * @subpackage Admin
 */

// 직접 접근 방지
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class FL_Admin_Viewer
 * 로그를 볼 수 있는 관리자 인터페이스 처리
 */
class FL_Admin_Viewer {
    
    /**
     * 생성자
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }
    
    /**
     * 관리자 메뉴 추가
     */
    public function add_admin_menu() {
        add_menu_page(
            '파일 로거',
            '파일 로거',
            'manage_options',
            'file-logger',
            array( $this, 'render_page' ),
            'dashicons-media-text',
            80
        );
    }
    
    /**
     * 스크립트 등록
     */
    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_file-logger' !== $hook ) {
            return;
        }
        
        // 로컬라이즈된 데이터와 함께 인라인 스크립트 추가
        wp_add_inline_script( 'jquery', 'var fl_ajax = ' . json_encode( array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'fl_ajax_nonce' )
        ) ) . ';' );
        
        // 시스템 정보 복사 기능을 위한 스크립트 추가
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'logs';
        if ( 'system-info' === $current_tab ) {
            wp_add_inline_script( 'jquery', "
                jQuery(document).ready(function($) {
                    // 시스템 정보를 클립보드에 복사
                    $('#fl-copy-sysinfo').on('click', function(e) {
                        e.preventDefault();
                        var copyText = document.getElementById('fl-sysinfo-text');
                        copyText.select();
                        copyText.setSelectionRange(0, 99999); // 모바일 기기를 위해
                        
                        try {
                            document.execCommand('copy');
                            $('.fl-copy-message').show().delay(2000).fadeOut();
                        } catch (err) {
                            alert('복사에 실패했습니다. 수동으로 선택하여 복사해주세요.');
                        }
                    });
                });
            " );
        }
    }
    
    /**
     * 관리자 페이지 렌더링
     */
    public function render_page() {
        // 현재 탭 가져오기
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'logs';
        ?>
        <div class="wrap">
            <h1>파일 로거</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=file-logger&tab=logs" class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <span title="애플리케이션에서 기록된 이벤트나 오류 메시지">로그</span>
                </a>
                <a href="?page=file-logger&tab=manual" class="nav-tab <?php echo $current_tab === 'manual' ? 'nav-tab-active' : ''; ?>">
                    <span title="파일 로거 사용 방법 및 예제">사용법</span>
                </a>
                <a href="?page=file-logger&tab=debug-settings" class="nav-tab <?php echo $current_tab === 'debug-settings' ? 'nav-tab-active' : ''; ?>">
                    <span title="개발 중 문제 해결을 위한 워드프레스 디버그 모드 설정">디버그 설정</span>
                </a>
                <a href="?page=file-logger&tab=system-info" class="nav-tab <?php echo $current_tab === 'system-info' ? 'nav-tab-active' : ''; ?>">
                    <span title="워드프레스와 서버 환경에 대한 자세한 정보">시스템 정보</span>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'manual':
                        $this->render_manual_tab();
                        break;
                        
                    case 'debug-settings':
                        $debug_settings = new FL_Debug_Settings();
                        $debug_settings->render_page();
                        break;
                        
                    case 'system-info':
                        FL_SysInfo_Reporter::display();
                        break;
                    
                    case 'logs':
                    default:
                        $this->render_logs_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 로그 탭 컨텐츠 렌더링
     */
    private function render_logs_tab() {
        ?>
        
        <?php
        // 사용 가능한 플러그인 가져오기
        $plugins = $this->get_logged_plugins();
        
        if ( ! empty( $plugins ) ) : ?>
            <div id="fl-logs-grid">
                <?php
                foreach ( $plugins as $plugin ) {
                    $this->display_log_card( $plugin );
                }
                ?>
            </div>
        <?php else : ?>
            <p>아직 로그가 없습니다.</p>
        <?php endif; ?>
        
        <style>
            #fl-logs-grid {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 20px !important;
                margin-top: 20px !important;
            }
            .fl-log-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                width: 100%;
                box-sizing: border-box;
            }
            .fl-log-card h3 {
                margin-top: 0;
                margin-bottom: 10px;
                font-size: 16px;
                color: #23282d;
            }
            .fl-log-actions {
                display: flex;
                gap: 5px;
                margin-bottom: 10px;
            }
            .fl-log-actions button {
                flex: 1;
                font-size: 12px;
                padding: 4px 8px;
            }
            .fl-log-content {
                background: #f1f1f1;
                border: 1px solid #e5e5e5;
                padding: 10px;
                height: 300px;
                overflow: auto;
                font-family: monospace;
                font-size: 11px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .fl-log-date-selector {
                margin-bottom: 10px;
            }
            .fl-log-date-selector select {
                width: 100%;
                font-size: 13px;
            }
            .fl-loading {
                opacity: 0.5;
                pointer-events: none;
            }
            .fl-delete-log.fl-delete-confirm {
                background: #dc3545;
                color: #fff !important;
                border-color: #dc3545;
            }
            .fl-delete-log.fl-delete-confirm:hover {
                background: #c82333;
                border-color: #bd2130;
            }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 로그 삭제
            $(document).on('click', '.fl-delete-log', function() {
                var button = $(this);
                var card = button.closest('.fl-log-card');
                
                // 이미 확인 상태인지 체크
                if (button.hasClass('fl-delete-confirm')) {
                    // 두 번째 클릭 - 실제 삭제 수행
                    var plugin = button.data('plugin');
                    var date = button.data('date');
                    
                    console.log('Deleting log:', plugin, date); // 디버그
                    
                    card.addClass('fl-loading');
                    
                    $.ajax({
                        url: fl_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'fl_delete_log',
                            plugin: plugin,
                            date: date,
                            nonce: fl_ajax.nonce
                        },
                        success: function(response) {
                            console.log('Delete response:', response); // 디버그
                            if (response.success) {
                                // 파일 내용이 비워졌으므로 로그를 새로고침
                                card.find('.fl-log-content').html('<p>로그가 없습니다.</p>');
                                card.find('.fl-log-size').text('0 B');
                                card.removeClass('fl-loading');
                                
                                // 버튼 원래 상태로 복원
                                button.removeClass('fl-delete-confirm');
                                button.text('삭제');
                            } else {
                                card.removeClass('fl-loading');
                                // 버튼 원래 상태로 복원
                                button.removeClass('fl-delete-confirm');
                                button.text('삭제');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete error:', status, error); // 디버그
                            card.removeClass('fl-loading');
                            // 버튼 원래 상태로 복원
                            button.removeClass('fl-delete-confirm');
                            button.text('삭제');
                        }
                    });
                } else {
                    // 첫 번째 클릭 - 확인 상태로 변경
                    button.addClass('fl-delete-confirm');
                    button.text('한번 더 눌러주세요');
                    
                    // 3초 후 원래 상태로 복원
                    setTimeout(function() {
                        if (button.hasClass('fl-delete-confirm')) {
                            button.removeClass('fl-delete-confirm');
                            button.text('삭제');
                        }
                    }, 3000);
                }
            });
            
            // 로그 복사
            $(document).on('click', '.fl-copy-log', function() {
                var button = $(this);
                var content = button.closest('.fl-log-card').find('.fl-log-content').text();
                
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(content).then(function() {
                        var originalText = button.text();
                        button.text('✓ 복사됨');
                        setTimeout(function() {
                            button.text(originalText);
                        }, 2000);
                    });
                } else {
                    // <span title="주 기능이 실패했을 때 사용하는 대체 방법">폴백</span>
                    var textArea = $('<textarea>').val(content).css({
                        position: 'fixed',
                        left: '-999999px'
                    }).appendTo('body');
                    textArea[0].select();
                    document.execCommand('copy');
                    textArea.remove();
                    
                    var originalText = button.text();
                    button.text('✓ 복사됨');
                    setTimeout(function() {
                        button.text(originalText);
                    }, 2000);
                }
            });
            
            // 로그 새로고침
            $(document).on('click', '.fl-refresh-log', function() {
                var button = $(this);
                var card = button.closest('.fl-log-card');
                var plugin = button.data('plugin');
                var date = card.find('.fl-log-date-select').val();
                
                card.addClass('fl-loading');
                
                $.post(fl_ajax.ajax_url, {
                    action: 'fl_refresh_log',
                    plugin: plugin,
                    date: date,
                    nonce: fl_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        card.find('.fl-log-content').html(response.data.content);
                        card.find('.fl-log-size').text(response.data.size);
                    }
                    card.removeClass('fl-loading');
                });
            });
            
            // 날짜 변경
            $(document).on('change', '.fl-log-date-select', function() {
                var select = $(this);
                var card = select.closest('.fl-log-card');
                var plugin = card.find('.fl-refresh-log').data('plugin');
                var date = select.val();
                
                card.addClass('fl-loading');
                
                $.post(fl_ajax.ajax_url, {
                    action: 'fl_refresh_log',
                    plugin: plugin,
                    date: date,
                    nonce: fl_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        card.find('.fl-log-content').html(response.data.content);
                        card.find('.fl-log-size').text(response.data.size);
                        
                        // data-date 속성 업데이트
                        card.find('.fl-delete-log, .fl-copy-log, .fl-refresh-log').attr('data-date', date);
                    }
                    card.removeClass('fl-loading');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * 로그 카드 표시
     */
    private function display_log_card( $plugin ) {
        $log_files = $this->get_log_files( $plugin );
        $current_date = ! empty( $log_files ) ? $log_files[0]['date'] : date( 'Y-m-d' );
        $log_file = FL_LOG_DIR . '/' . $plugin . '/log-' . $current_date . '.log';
        
        ?>
        <div class="fl-log-card">
            <h3><?php echo esc_html( $plugin ); ?></h3>
            
            <div class="fl-log-date-selector">
                <select class="fl-log-date-select">
                    <?php foreach ( $log_files as $file ) : ?>
                        <option value="<?php echo esc_attr( $file['date'] ); ?>" <?php selected( $current_date, $file['date'] ); ?>>
                            <?php echo esc_html( $file['date'] ); ?> (<?php echo esc_html( $file['size'] ); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="fl-log-actions">
                <button type="button" class="button fl-delete-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">삭제</button>
                <button type="button" class="button fl-copy-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">복사</button>
                <button type="button" class="button fl-refresh-log" data-plugin="<?php echo esc_attr( $plugin ); ?>" data-date="<?php echo esc_attr( $current_date ); ?>">새로고침</button>
            </div>
            
            <div class="fl-log-content">
                <?php
                if ( file_exists( $log_file ) ) {
                    $content = file_get_contents( $log_file );
                    echo $this->format_log_content( $content );
                } else {
                    echo '<p>로그가 없습니다.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * 플러그인의 로그 파일 가져오기
     */
    private function get_log_files( $plugin ) {
        $files = array();
        $dir = FL_LOG_DIR . '/' . $plugin;
        
        if ( is_dir( $dir ) ) {
            $log_files = glob( $dir . '/log-*.log' );
            rsort( $log_files ); // 최신 순으로 정렬
            
            foreach ( $log_files as $file ) {
                if ( preg_match( '/log-(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches ) ) {
                    $files[] = array(
                        'date' => $matches[1],
                        'size' => size_format( filesize( $file ) ),
                        'path' => $file
                    );
                }
            }
        }
        
        return $files;
    }
    
    /**
     * 로그 내용을 색상으로 형식화
     */
    public function format_log_content( $content ) {
        // 먼저 HTML 이스케이프
        $content = esc_html( $content );
        
        // 로그 레벨별 색상 코드
        $content = preg_replace( '/\[ERROR\]/', '<span style="color: #dc3545;">[ERROR]</span>', $content );
        $content = preg_replace( '/\[WARNING\]/', '<span style="color: #ffc107;">[WARNING]</span>', $content );
        $content = preg_replace( '/\[INFO\]/', '<span style="color: #17a2b8;">[INFO]</span>', $content );
        $content = preg_replace( '/\[DEBUG\]/', '<span style="color: #6c757d;">[DEBUG]</span>', $content );
        $content = preg_replace( '/\[LOG\]/', '<span style="color: #28a745;">[LOG]</span>', $content );
        
        return $content;
    }
    
    /**
     * 로그가 있는 플러그인 목록 가져오기
     */
    private function get_logged_plugins() {
        $plugins = array();
        
        if ( is_dir( FL_LOG_DIR ) ) {
            $dirs = glob( FL_LOG_DIR . '/*', GLOB_ONLYDIR );
            foreach ( $dirs as $dir ) {
                $plugins[] = basename( $dir );
            }
        }
        
        return $plugins;
    }
    
    /**
     * 액션 URL 가져오기
     */
    private function get_action_url( $action, $plugin, $date ) {
        return wp_nonce_url(
            add_query_arg( array(
                'page' => 'file-logger',
                'action' => $action,
                'plugin' => $plugin,
                'date' => $date
            ), admin_url( 'admin.php' ) ),
            'fl_' . $action
        );
    }
    
    /**
     * 액션 처리
     */
    public function handle_actions() {
        if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
            return;
        }
        
        $action = sanitize_text_field( $_GET['action'] );
        $plugin = isset( $_GET['plugin'] ) ? sanitize_text_field( $_GET['plugin'] ) : '';
        $date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
        
        if ( ! $plugin || ! $date ) {
            return;
        }
        
        $log_file = FL_LOG_DIR . '/' . $plugin . '/log-' . $date . '.log';
        
        switch ( $action ) {
            case 'download':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'fl_download' ) && file_exists( $log_file ) ) {
                    header( 'Content-Type: text/plain' );
                    header( 'Content-Disposition: attachment; filename="' . $plugin . '-' . $date . '.log"' );
                    header( 'Content-Length: ' . filesize( $log_file ) );
                    readfile( $log_file );
                    exit;
                }
                break;
                
            case 'clear':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'fl_clear' ) && file_exists( $log_file ) ) {
                    unlink( $log_file );
                    wp_redirect( add_query_arg( array(
                        'page' => 'file-logger',
                        'plugin' => $plugin,
                        'cleared' => 1
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
                
            case 'refresh':
                if ( wp_verify_nonce( $_GET['_wpnonce'], 'fl_refresh' ) ) {
                    wp_redirect( add_query_arg( array(
                        'page' => 'file-logger',
                        'plugin' => $plugin,
                        'date' => $date
                    ), admin_url( 'admin.php' ) ) );
                    exit;
                }
                break;
        }
    }
    
    /**
     * 메뉴얼 탭 컨텐츠 렌더링
     */
    private function render_manual_tab() {
        ?>
        <div class="wrap">
            <div style="max-width: 800px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0;">
                    <h2 style="margin-top: 0;">파일 로거 사용법</h2>
                    
                    <h3>1. 기본 사용법 (헬퍼 함수)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
fl_log('일반 로그 메시지');
fl_error('에러 메시지');
fl_info('정보 메시지');
fl_debug('디버그 메시지');  // WP_DEBUG가 true일 때만 기록
fl_warning('경고 메시지');</pre>
                    
                    <h3>2. 데이터와 함께 로깅</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 사용자 정보 로깅
$user_data = get_userdata($user_id);
fl_log('사용자 정보 조회', $user_data);

// 에러 상황 로깅
try {
    // 코드 실행
} catch (Exception $e) {
    fl_error('결제 처리 실패', [
        'order_id' => $order_id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

// 옵션값 디버깅
fl_debug('플러그인 설정 확인', get_option('my_plugin_settings'));</pre>
                    
                    <h3>3. 컨텍스트 정보 포함 (권장)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 파일명과 메서드명을 포함하여 로그 위치를 명확히 표시
fl_info(
    sprintf('[%s - %s] 주문 생성 완료', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'total' => $total]
);

// 클래스 메서드에서 사용
fl_error(
    sprintf('[%s - %s] 재고 부족', basename(__FILE__), __METHOD__),
    ['product_id' => $product_id, 'requested' => $quantity]
);</pre>
                    
                    <h3>4. 주요 기능</h3>
                    <ul style="line-height: 1.8;">
                        <li><strong>자동 분류:</strong> 플러그인/테마별로 로그가 자동으로 분류됩니다</li>
                        <li><strong>날짜별 파일:</strong> 매일 새로운 로그 파일이 생성됩니다</li>
                        <li><strong>자동 정리:</strong> 7일 이상 된 로그는 자동으로 삭제됩니다</li>
                        <li><strong>레벨별 구분:</strong> LOG, ERROR, INFO, DEBUG, WARNING 레벨 지원</li>
                        <li><strong>관리 기능:</strong> 로그 보기, 다운로드, 복사, 삭제 기능 제공</li>
                        <li><strong>보안:</strong> 로그 디렉토리는 웹에서 직접 접근 불가</li>
                    </ul>
                    
                    <h3>5. 로그 위치</h3>
                    <p><code>/wp-content/fl-logs/[플러그인명]/log-YYYY-MM-DD.log</code></p>
                    
                    <h3>6. 클래스 직접 사용 (대체 방법)</h3>
                    <pre style="background: #f1f1f1; padding: 15px; overflow-x: auto;">
// 헬퍼 함수 대신 클래스를 직접 사용할 수도 있습니다
\FL::log('일반 로그 메시지');
\FL::error('에러 메시지');
\FL::info('정보 메시지');
\FL::debug('디버그 메시지');
\FL::warning('경고 메시지');</pre>
                </div>
            </div>
        </div>
        <?php
    }
}