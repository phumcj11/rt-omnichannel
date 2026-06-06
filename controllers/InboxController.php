<?php
/**
 * Unified Inbox + Chat detail (Phase 2)
 */
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Redirect;
use App\Helpers\View;
use App\Models\Branch;
use App\Models\CannedResponse;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tag;
use App\Models\User;
use App\Services\ErpCatalogService;
use App\Services\FacebookMessengerService;
use Throwable;

final class InboxController
{
    /** @return array<string, mixed> */
    private function appConfig(): array
    {
        return require dirname(__DIR__) . '/config/app.php';
    }

    private function currentUserId(): int
    {
        $id = Auth::userId();

        return $id !== null ? $id : 0;
    }

    private function assertCsrf(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF validation failed';
            exit;
        }
    }

    public function index(): void
    {
        $filters = [
            'channel_id' => $_GET['channel_id'] ?? '',
            'branch_id' => $_GET['branch_id'] ?? '',
            'language' => $_GET['language'] ?? '',
            'tag_id' => $_GET['tag_id'] ?? '',
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'assign' => $_GET['assign'] ?? '',
            'q' => trim((string) ($_GET['q'] ?? '')),
            'current_user_id' => $this->currentUserId(),
        ];

        $dbError = null;
        $rows = [];
        $channels = [];
        $branches = [];
        $tags = [];

        try {
            $rows = Conversation::listInbox($filters);
            $channels = Channel::all();
            $branches = Branch::all();
            $tags = Tag::all();
        } catch (Throwable $e) {
            $cfg = $this->appConfig();
            $dbError = !empty($cfg['debug']) ? $e->getMessage() : 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้';
        }

        $app = $this->appConfig();
        View::render('layouts/app', [
            'title' => 'Unified Inbox',
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'inbox',
            'mainFlush' => true,
            'extraScripts' => ['/assets/js/inbox.js'],
            'contentView' => 'inbox/index',
            'contentData' => [
                'filters' => $filters,
                'rows' => $rows,
                'channels' => $channels,
                'branches' => $branches,
                'tags' => $tags,
                'dbError' => $dbError,
                'flash' => $_GET['ok'] ?? null,
            ],
        ]);
    }

    /**
     * JSON สำหรับค้นหาสินค้าในแชท (ERP cache)
     */
    public function erpSearch(string $id): void
    {
        $cid = (int) $id;
        header('Content-Type: application/json; charset=UTF-8');
        if ($cid < 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_conversation'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q) < 1) {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $conv = Conversation::findWithRelations($cid);
            if ($conv === null) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $bid = isset($conv['branch_id']) && $conv['branch_id'] !== null && $conv['branch_id'] !== ''
                ? (int) $conv['branch_id']
                : 1;
            $svc = new ErpCatalogService();
            $items = $svc->search($q, $bid, 25);
            echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            $cfg = $this->appConfig();
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => !empty($cfg['debug']) ? $e->getMessage() : 'server_error',
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    public function show(string $id): void
    {
        $cid = (int) $id;
        if ($cid < 1) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'ไม่พบบทสนทนา']);
            return;
        }

        $dbError = null;
        $conv = null;
        $messages = [];
        $agents = [];
        $canned = [];
        $convTags = [];
        $allTags = [];

        try {
            $conv = Conversation::findWithRelations($cid);
            if ($conv === null) {
                http_response_code(404);
                View::render('errors/404', ['title' => 'ไม่พบบทสนทนา']);
                return;
            }
            $messages = Message::forConversation($cid);
            $agents = User::agentsForAssign();
            $canned = CannedResponse::activeList();
            $convTags = Conversation::tagsForConversation($cid);
            $allTags = Tag::all();
        } catch (Throwable $e) {
            $cfg = $this->appConfig();
            $dbError = !empty($cfg['debug']) ? $e->getMessage() : 'ไม่สามารถโหลดข้อมูลได้';
        }

        if ($dbError !== null && $conv === null) {
            http_response_code(503);
        }

        $app = $this->appConfig();
        View::render('layouts/app', [
            'title' => 'แชท #' . $cid,
            'appName' => (string) ($app['name'] ?? 'Omnichannel'),
            'navActive' => 'inbox',
            'mainFlush' => true,
            'extraScripts' => ['/assets/js/inbox.js'],
            'contentView' => 'inbox/show',
            'contentData' => [
                'conversation' => $conv,
                'messages' => $messages,
                'agents' => $agents,
                'canned' => $canned,
                'convTags' => $convTags,
                'allTags' => $allTags,
                'dbError' => $dbError,
                'currentUserId' => $this->currentUserId(),
                'flash' => $_GET['ok'] ?? null,
            ],
        ]);
    }

    public function assign(string $id): void
    {
        $cid = (int) $id;
        if ($cid < 1 || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            Redirect::to('/inbox');
        }
        $this->assertCsrf();
        $raw = $_POST['assigned_user_id'] ?? '';
        $uid = $raw === '' || $raw === '0' ? null : (int) $raw;
        Conversation::updateAssign($cid, $uid);
        Redirect::to('/inbox/' . $cid . '?ok=assign');
    }

    public function reply(string $id): void
    {
        $cid = (int) $id;
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($cid < 1 || $body === '') {
            Redirect::to('/inbox/' . max(1, $cid));
        }
        $this->assertCsrf();
        $msgId = Message::insertOutbound($cid, $body, $this->currentUserId(), 'text');
        if (isset($_POST['set_open']) && $_POST['set_open'] === '1') {
            Conversation::updateStatus($cid, 'open');
        }
        try {
            $fb = new FacebookMessengerService();
            $fb->sendReplyForConversation($cid, $body, $msgId);
        } catch (Throwable) {
            // บันทึกใน DB แล้ว — ส่ง FB ไม่สำเร็จไม่บล็อก agent
        }
        Redirect::to('/inbox/' . $cid . '?ok=reply');
    }

    public function note(string $id): void
    {
        $cid = (int) $id;
        $body = trim((string) ($_POST['note'] ?? ''));
        if ($cid < 1 || $body === '') {
            Redirect::to('/inbox/' . max(1, $cid));
        }
        $this->assertCsrf();
        Message::insertInternalNote($cid, $body, $this->currentUserId());
        Redirect::to('/inbox/' . $cid . '?ok=note');
    }

    public function status(string $id): void
    {
        $cid = (int) $id;
        $st = (string) ($_POST['status'] ?? '');
        if ($cid < 1 || $st === '') {
            Redirect::to('/inbox');
        }
        $this->assertCsrf();
        Conversation::updateStatus($cid, $st);
        Redirect::to('/inbox/' . $cid . '?ok=status');
    }

    public function addTag(string $id): void
    {
        $cid = (int) $id;
        $tagId = (int) ($_POST['tag_id'] ?? 0);
        if ($cid < 1 || $tagId < 1) {
            Redirect::to('/inbox/' . max(1, $cid));
        }
        $this->assertCsrf();
        Conversation::addTag($cid, $tagId);
        Redirect::to('/inbox/' . $cid . '?ok=tag');
    }
}
