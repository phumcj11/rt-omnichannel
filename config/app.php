<?php
/**
 * การตั้งค่าแอปพลิเคชันหลัก — โหลดจาก environment / local override
 * ไม่ hardcode secret ใน repo: ใช้ getenv() + config/local.php (ถ้ามี)
 */

declare(strict_types=1);

$local = [];
$localFile = dirname(__DIR__) . '/config/local.php';
if (is_readable($localFile)) {
    /** @var array $local */
    $local = require $localFile;
}

return array_merge([
    'name' => '100 Baht Shop — Omnichannel',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(getenv('APP_URL') ?: 'http://localhost/omnichannel', '/'),
    /** เส้นทาง URL ภายใต้โดเมน (XAMPP: /omnichannel) — ว่าง = อยู่ที่ root ของโดเมน */
    'base_path' => getenv('APP_BASE_PATH') !== false && getenv('APP_BASE_PATH') !== ''
        ? rtrim((string) getenv('APP_BASE_PATH'), '/')
        : '/omnichannel',
    'timezone' => 'Asia/Bangkok',
    /** production: ต้องเป็น false — ใช้ login เท่านั้น */
    'allow_dev_auto_login' => filter_var(getenv('ALLOW_DEV_AUTO_LOGIN') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    /** ก่อนมีระบบ Login — ใช้เมื่อ allow_dev_auto_login เท่านั้น */
    'dev_impersonate_user_id' => (int) (getenv('DEV_USER_ID') ?: 1),
    'session_name' => getenv('SESSION_NAME') !== false && getenv('SESSION_NAME') !== ''
        ? (string) getenv('SESSION_NAME')
        : 'OMNI_SESSID',
    'session_cookie_lifetime' => (int) (getenv('SESSION_LIFETIME') !== false ? getenv('SESSION_LIFETIME') : 0),
    /** null = ตรวจจาก HTTPS อัตโนมัติ */
    'session_cookie_secure' => getenv('SESSION_SECURE') !== false && getenv('SESSION_SECURE') !== ''
        ? filter_var(getenv('SESSION_SECURE'), FILTER_VALIDATE_BOOLEAN)
        : null,
    'session_samesite' => getenv('SESSION_SAMESITE') !== false && getenv('SESSION_SAMESITE') !== ''
        ? (string) getenv('SESSION_SAMESITE')
        : 'Lax',
    /** Facebook Messenger — override ใน config/local.php */
    'facebook' => [
        'page_access_token' => getenv('FB_PAGE_ACCESS_TOKEN') ?: '',
        'verify_token' => getenv('FB_VERIFY_TOKEN') ?: '',
        'app_secret' => getenv('FB_APP_SECRET') ?: '',
        'page_id' => getenv('FB_PAGE_ID') ?: '',
        'app_id' => getenv('FB_APP_ID') ?: '',
        'graph_version' => getenv('FB_GRAPH_VERSION') ?: 'v21.0',
        'default_branch_id' => (int) (getenv('FB_DEFAULT_BRANCH_ID') ?: 1),
    ],
], $local);
