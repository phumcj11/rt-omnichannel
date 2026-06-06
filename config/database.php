<?php
/**
 * การเชื่อมต่อ MySQL — ค่าดีฟอลต์เหมาะกับ XAMPP
 * Override ด้วย getenv หรือ config/local.php
 */

declare(strict_types=1);

$local = [];
$localFile = dirname(__DIR__) . '/config/local.php';
if (is_readable($localFile)) {
    $local = require $localFile;
}

$db = $local['database'] ?? [];

return [
    'host' => $db['host'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) ($db['port'] ?? getenv('DB_PORT') ?: '3306'),
    'name' => $db['name'] ?? getenv('DB_NAME') ?: 'omnichannel_100baht',
    'user' => $db['user'] ?? getenv('DB_USER') ?: 'root',
    'pass' => $db['pass'] ?? getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];
