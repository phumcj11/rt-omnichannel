<?php
/**
 * REST stub — /api/erp/* (ตั้งค่า Apache/nginx ให้ชี้มาที่โฟลเดอร์ api หรือ rewrite)
 * Phase 5: เชื่อม ErpIntegrationService + auth + rate limit
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

$path = $_GET['path'] ?? ($_SERVER['PATH_INFO'] ?? '');
$path = trim((string) $path, '/');

$routes = [
    'products' => ['GET', 'erp_products_cache'],
    'prices' => ['GET', 'erp_prices_cache'],
    'stocks' => ['GET', 'erp_stocks_cache'],
    'promotions' => ['GET', 'erp_promotions_cache'],
];

if ($path === '' || $path === 'health') {
    echo json_encode(['status' => 'ok', 'service' => 'erp-api', 'phase' => 1]);
    exit;
}

if (!isset($routes[$path])) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found', 'path' => $path]);
    exit;
}

http_response_code(501);
echo json_encode([
    'error' => 'not_implemented',
    'hint' => 'Phase 5 — อ่านจากตาราง cache + service layer',
    'resource' => $routes[$path][1],
]);
