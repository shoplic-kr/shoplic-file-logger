# File Logger (FL) - WordPress 로깅 유틸리티

## 개요

File Logger(FL)는 WordPress 프로젝트를 위한 간편하고 강력한 로깅 유틸리티입니다. 이 플러그인은 사용자가 직접 WordPress의 mu-plugins 디렉토리에 설치해야 하며, 설치 후에는 모든 WordPress 프로젝트에서 전역적으로 사용할 수 있습니다.

## 주요 특징

- **헬퍼 함수 제공**: `fl_log()`, `fl_error()` 등 간편한 함수로 빠르게 로깅
- **다양한 로그 레벨**: log, error, info, warning, debug 지원
- **자동 컨텍스트 추가**: 파일명과 함수명 자동 포함
- **글로벌 네임스페이스**: 어디서든 사용 가능
- **데이터 로깅**: 배열, 객체 등 모든 타입의 데이터 로깅 지원
- **mu-plugin 지원**: mu-plugins 디렉토리에 설치하면 모든 플러그인과 테마에서 사용 가능

## 설치

### mu-plugin으로 설치 (권장)

1. 이 플러그인 파일을 다운로드합니다.
2. WordPress 설치 디렉토리의 `wp-content/mu-plugins/` 폴더에 파일을 복사합니다.
   ```
   wp-content/
   └── mu-plugins/
       └── file-logger/
           ├── file-logger.php
           └── inc/
               └── helpers.php
   ```
3. mu-plugins 폴더가 없다면 직접 생성해야 합니다.
4. 설치가 완료되면 자동으로 활성화되며, 별도의 활성화 과정이 필요하지 않습니다.

**중요**: mu-plugins 디렉토리에 설치된 플러그인은 WordPress 관리자 화면에 표시되지 않지만, 항상 활성화되어 있습니다.

## 사용법

### 헬퍼 함수 (권장)

File Logger는 더 간편한 사용을 위해 헬퍼 함수를 제공합니다:

#### 기본 사용법
```php
// 일반 로그
fl_log('데이터 처리를 시작합니다');

// 에러 로그
fl_error('결제 처리 중 오류가 발생했습니다');

// 정보 로그
fl_info('새 주문이 생성되었습니다');

// 경고 로그
fl_warning('캐시가 만료되었습니다');

// 디버그 로그 (WP_DEBUG가 true일 때만 작동)
fl_debug('디버그 정보');
```

#### 조건부 로깅 비활성화
```php
// 특정 조건에서 로깅 비활성화 (disable 파라미터 사용)
$is_production = defined('WP_ENV') && WP_ENV === 'production';
fl_log('개발 환경에서만 기록되는 로그', null, $is_production); // 프로덕션에서는 로깅 안함

// 에러가 없을 때만 로깅 비활성화
$no_error = empty($error_message);
fl_error('에러 발생', $error_details, $no_error); // 에러가 있을 때만 로깅

// 관리자가 아닌 경우 로깅 비활성화
$is_not_admin = !current_user_can('manage_options');
fl_info('관리자 작업', $admin_data, $is_not_admin);
```

#### 데이터와 함께 로깅
```php
// 사용자 데이터 로깅
$user_data = ['id' => 123, 'email' => 'user@example.com'];
fl_log('사용자 로그인', $user_data);

// 에러 상세 정보
fl_error('API 호출 실패', [
    'endpoint' => '/api/orders',
    'response_code' => 500,
    'error_message' => 'Internal Server Error'
]);

// 조건부 데이터 로깅 비활성화
$no_error = $response_code < 400;
fl_error('API 응답 에러', $response_data, $no_error); // 400 이상일 때만 로깅
```

#### 컨텍스트 정보 포함 (권장)
```php
// 파일명과 함수명을 포함한 로깅
fl_info(
    sprintf('[%s - %s] 주문이 처리되었습니다', basename(__FILE__), __METHOD__),
    ['order_id' => $order_id, 'total' => $total]
);

// 클래스 메서드 내에서
class MyPlugin {
    public function process_payment($order_id) {
        fl_log(
            sprintf('[%s - %s] 결제 처리 시작', basename(__FILE__), __METHOD__),
            ['order_id' => $order_id]
        );
        
        // 처리 로직...
        
        $failed = $payment_result !== 'success';
        fl_info(
            sprintf('[%s - %s] 결제 완료', basename(__FILE__), __METHOD__),
            null,
            $failed // 실패 시에는 로깅 안함
        );
    }
}
```

### 클래스 직접 사용

헬퍼 함수 대신 FL 클래스를 직접 사용할 수도 있습니다:

```php
// 네임스페이스 충돌 방지를 위해 항상 백슬래시 사용
\FL::log('메시지');
\FL::error('에러 메시지');
\FL::info('정보 메시지');
\FL::warning('경고 메시지');
\FL::debug('디버그 메시지');
```

## API 레퍼런스

### 헬퍼 함수

| 함수 | 설명 | 파라미터 |
|------|------|----------|
| `fl_log()` | 일반 로그 메시지 기록 | `$message` (string), `$data` (mixed, optional), `$disable` (bool, optional) |
| `fl_error()` | 에러 메시지 기록 | `$message` (string), `$data` (mixed, optional), `$disable` (bool, optional) |
| `fl_info()` | 정보성 메시지 기록 | `$message` (string), `$data` (mixed, optional), `$disable` (bool, optional) |
| `fl_warning()` | 경고 메시지 기록 | `$message` (string), `$data` (mixed, optional), `$disable` (bool, optional) |
| `fl_debug()` | 디버그 메시지 기록 (WP_DEBUG 필요) | `$message` (string), `$data` (mixed, optional), `$disable` (bool, optional) |

### FL 클래스 메서드

| 메서드 | 설명 |
|--------|------|
| `FL::log()` | 일반 로그 메시지 |
| `FL::error()` | 에러 메시지 |
| `FL::info()` | 정보성 메시지 |
| `FL::warning()` | 경고 메시지 |
| `FL::debug()` | 디버그 메시지 |

## 모범 사례

### 권장 사항

1. **헬퍼 함수 사용**
   ```php
   // 권장 - 기본 로깅
   fl_log('메시지');
   
   // 조건부 로깅 비활성화
   $is_production = WP_ENV === 'production';
   $disabled = $is_production;
   fl_log('개발 메시지', null, $disabled);
   
   // 가능하지만 타이핑이 더 필요함
   \FL::log('메시지');
   ```

2. **컨텍스트 정보 포함**
   ```php
   fl_error(
       sprintf('[%s - %s] 데이터베이스 연결 실패', basename(__FILE__), __METHOD__)
   );
   ```

3. **적절한 로그 레벨 사용**
   - `fl_log()`: 일반적인 이벤트나 흐름
   - `fl_error()`: 심각한 오류나 예외 상황
   - `fl_warning()`: 주의가 필요한 상황
   - `fl_info()`: 중요한 정보나 이벤트
   - `fl_debug()`: 개발 중 디버깅 정보

4. **구조화된 데이터 로깅**
   ```php
   fl_info('주문 생성됨', [
       'order_id' => $order->get_id(),
       'customer_email' => $order->get_billing_email(),
       'total' => $order->get_total(),
       'items' => count($order->get_items())
   ]);
   ```

5. **조건부 로깅 비활성화 활용**
   ```php
   // 프로덕션에서는 디버그 로깅 비활성화
   $is_production = defined('WP_ENV') && WP_ENV === 'production';
   fl_debug('개발 환경 디버그 정보', $debug_data, $is_production);
   
   // 에러가 없을 때는 로깅 비활성화
   $no_error = empty($error_message);
   fl_error('처리 중 에러 발생', $error_details, $no_error);
   ```

### 피해야 할 사항

1. **민감한 정보 로깅 금지**
   ```php
   // 나쁜 예
   fl_log('로그인 시도', ['password' => $password]);
   
   // 좋은 예
   fl_log('로그인 시도', ['username' => $username]);
   ```

2. **루프 내 과도한 로깅**
   ```php
   // 피해야 할 예
   foreach ($large_array as $item) {
       fl_debug('아이템 처리', $item); // 성능 저하
   }
   
   // 좋은 예 - 요약 정보만 로깅
   fl_debug('처리할 아이템 수', count($large_array));
   
   // 또는 루프에서 특정 항목만 로깅
   foreach ($large_array as $index => $item) {
       // 첫 번째와 마지막 항목만 로깅
       $should_skip = $index > 0 && $index < count($large_array) - 1;
       fl_debug('아이템 처리', $item, $should_skip);
       // 처리 로직...
   }
   ```

3. **컨텍스트 없는 로깅**
   ```php
   // 나쁜 예
   fl_error('오류 발생'); // 어디서 발생했는지 알 수 없음
   
   // 좋은 예
   fl_error(sprintf('[%s - %s] 오류 발생', basename(__FILE__), __METHOD__));
   ```

## 문제 해결

### 로그가 기록되지 않는 경우

1. **설치 확인**: 플러그인이 `wp-content/mu-plugins/` 디렉토리에 올바르게 설치되었는지 확인
2. **디버그 모드**: `fl_debug()` 사용 시 `wp-config.php`에서 `WP_DEBUG`가 `true`로 설정되어 있는지 확인
3. **파일 권한**: 로그 파일이 저장될 디렉토리의 쓰기 권한 확인
4. **함수 존재 여부**: `function_exists('fl_log')`로 헬퍼 함수가 로드되었는지 확인

### 성능 최적화

- 프로덕션 환경에서는 `fl_debug()` 사용을 최소화
- 대용량 데이터는 필요한 부분만 추출하여 로깅
- 빈번히 호출되는 함수에서는 로깅을 선택적으로 사용

## 라이선스

이 유틸리티는 WordPress 프로젝트의 일부로 제공됩니다.