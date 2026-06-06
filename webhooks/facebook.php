<?php
/**
 * Meta Facebook Page — Webhook endpoint
 * URL ตั้งใน Meta Developer: https://YOUR-DOMAIN/omnichannel/webhooks/facebook.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Helpers\WebhookTrace;
use App\Services\IntegrationConfigService;
use App\Services\FacebookMessengerService;

$svc = new FacebookMessengerService();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $svc->handleVerification();
    }
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $raw = $raw !== false ? $raw : '';
        WebhookTrace::log(
            'POST hit len=' . strlen($raw)
            . ' sig256=' . (IntegrationConfigService::signatureHeaderFromServer(
                IntegrationConfigService::mergeWebhookServer($_SERVER)
            ) !== '' ? 'yes' : 'no')
            . ' hubkeys=' . implode(',', IntegrationConfigService::listHubHeaderKeys($_SERVER))
        );
        $server = IntegrationConfigService::mergeWebhookServer($_SERVER);
        $svc->handleWebhook($raw, $server);
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
