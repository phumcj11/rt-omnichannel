<?php
/**
 * WhatsApp Cloud API Webhook — Phase 6
 */
declare(strict_types=1);

http_response_code(501);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['status' => 'stub', 'message' => 'Implement in Phase 6']);
