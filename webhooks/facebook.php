<?php
/**
 * Meta Facebook Page — Webhook endpoint
 * URL ตั้งใน Meta Developer: https://YOUR-DOMAIN/omnichannel/webhooks/facebook.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\FacebookMessengerService;

$svc = new FacebookMessengerService();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $svc->handleVerification();
    }
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $svc->handleWebhook($raw !== false ? $raw : '', $_SERVER);
        exit;
    }
    http_response_code(405);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method Not Allowed';
} catch (Throwable $e) {
    $app = require dirname(__DIR__) . '/config/app.php';
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo !empty($app['debug']) ? $e->getMessage() : 'Error';
}
