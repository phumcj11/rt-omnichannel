<?php
/**
 * รีเซ็ตรหัส admin เป็น admin123 — รันครั้งเดียว
 * CLI:  php scripts/reset-admin-password.php
 * Web:  https://your-site/rt/allchat/scripts/reset-admin-password.php?key=YOUR_SETUP_KEY
 *        ตั้ง setup_key ใน config/local.php แล้วลบ/ปิดไฟล์นี้หลังใช้
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Helpers\Db;

$app = require dirname(__DIR__) . '/config/app.php';
$setupKey = trim((string) ($app['setup_key'] ?? ''));
if ($setupKey === '') {
    $setupKey = trim((string) ($app['internal_reset_token'] ?? ''));
}

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');
    $key = (string) ($_GET['key'] ?? '');
    if ($setupKey === '' || !hash_equals($setupKey, $key)) {
        http_response_code(403);
        echo 'Forbidden — ตั้ง setup_key ใน config/local.php ก่อน';
        exit;
    }
}

$hash = password_hash('admin123', PASSWORD_DEFAULT);
$emails = ['admin@100bahtshop.local', 'agent@100bahtshop.local'];

try {
    $pdo = Db::pdo();
    $st = $pdo->prepare('UPDATE users SET password_hash = :h WHERE email = :e');
    $updated = 0;
    foreach ($emails as $email) {
        $st->execute(['h' => $hash, 'e' => $email]);
        $updated += $st->rowCount();
    }
    $verify = $pdo->prepare('SELECT email FROM users WHERE email = :e AND password_hash = :h LIMIT 1');
    $verify->execute(['e' => 'admin@100bahtshop.local', 'h' => $hash]);
    $ok = $verify->fetch() !== false;

    echo "Updated rows: {$updated}\n";
    echo $ok ? "OK — login: admin@100bahtshop.local / admin123\n" : "Verify failed — check database connection\n";
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
