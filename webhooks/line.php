<?php
/**
 * LINE Webhook — Phase 6: validate signature, parse event, save message
 * URL: https://your-domain/omnichannel/webhooks/line.php
 */
declare(strict_types=1);

http_response_code(501);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['status' => 'stub', 'message' => 'Implement in Phase 6']);
