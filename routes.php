<?php
/**
 * กำหนดเส้นทาง HTTP — เพิ่มเมื่อแต่ละ Phase พร้อม
 */
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CrmController;
use App\Controllers\HomeController;
use App\Controllers\InboxController;
use App\Controllers\SettingsController;
use App\Helpers\Router;

$router = new Router();

$router->get('/login', AuthController::class, 'showLogin');
$router->post('/login', AuthController::class, 'login');
$router->post('/logout', AuthController::class, 'logout');

$router->get('/', HomeController::class, 'index');

$router->get('/crm', CrmController::class, 'index');

$router->get('/settings', SettingsController::class, 'index');
$router->get('/settings/channels', SettingsController::class, 'channels');
$router->post('/settings/channels/facebook', SettingsController::class, 'saveFacebook');
$router->post('/settings/channels/facebook/page', SettingsController::class, 'saveFacebookPage');
$router->post('/settings/channels/facebook/test', SettingsController::class, 'testFacebook');
$router->get('/settings/sla', SettingsController::class, 'sla');

$router->get('/inbox', InboxController::class, 'index');
$router->get('/inbox/:id/erp-search', InboxController::class, 'erpSearch');
$router->get('/inbox/:id', InboxController::class, 'show');
$router->post('/inbox/:id/assign', InboxController::class, 'assign');
$router->post('/inbox/:id/reply', InboxController::class, 'reply');
$router->post('/inbox/:id/note', InboxController::class, 'note');
$router->post('/inbox/:id/status', InboxController::class, 'status');
$router->post('/inbox/:id/tag', InboxController::class, 'addTag');

return $router;
