<?php
/**
 * Chat detail — ข้อความ + แอ็กชัน agent
 * @var array<string,mixed>|null $conversation
 * @var list<array<string,mixed>> $messages
 * @var list<array<string,mixed>> $agents
 * @var list<array<string,mixed>> $canned
 * @var list<array<string,mixed>> $convTags
 * @var list<array<string,mixed>> $allTags
 * @var string|null $dbError
 * @var int $currentUserId
 * @var string|null $flash
 */
declare(strict_types=1);

use App\Helpers\ChannelIcon;
use App\Helpers\Csrf;
use App\Helpers\SlaUi;
use App\Helpers\Url;

if (!function_exists('inbox_status_th')) {
    function inbox_status_th(string $s): string
    {
        return match ($s) {
            'new' => 'ใหม่',
            'open' => 'เปิด',
            'pending' => 'รอดำเนินการ',
            'resolved' => 'แก้แล้ว',
            'closed' => 'ปิด',
            default => $s,
        };
    }
}
if (!function_exists('inbox_status_badge')) {
    function inbox_status_badge(string $s): string
    {
        return match ($s) {
            'new' => 'bg-violet-100 text-violet-800 ring-violet-200',
            'open' => 'bg-sky-100 text-sky-800 ring-sky-200',
            'pending' => 'bg-amber-100 text-amber-900 ring-amber-200',
            'resolved' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'closed' => 'bg-slate-200 text-slate-700 ring-slate-300',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }
}

$flashText = match ($flash ?? '') {
    'assign' => 'อัปเดตผู้รับผิดชอบแล้ว',
    'reply' => 'ส่งข้อความถึงลูกค้าแล้ว',
    'note' => 'บันทึกโน้ตภายในแล้ว',
    'status' => 'อัปเดตสถานะแล้ว',
    'tag' => 'เพิ่มแท็กแล้ว',
    default => null,
};

if (($conversation === null || !is_array($conversation)) && $dbError !== null) : ?>
    <div class="p-6">
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-brand hover:underline">
            <i class="fa-solid fa-arrow-left"></i> กลับ Inbox
        </a>
    </div>
    <?php
    return;
endif;

if ($conversation === null || !is_array($conversation)) {
    return;
}

$cannedMap = [];
foreach ($canned as $c) {
    $cannedMap[(string) (int) ($c['id'] ?? 0)] = (string) ($c['body'] ?? '');
}

$cid = (int) $conversation['id'];
$chCode = (string) ($conversation['channel_code'] ?? '');
$sla = SlaUi::badge(isset($conversation['sla_due_at']) ? (string) $conversation['sla_due_at'] : null);

$contactName = trim((string) ($conversation['contact_name'] ?? ''));
$initials = '—';
if ($contactName !== '') {
    $words = preg_split('/\s+/u', $contactName, -1, PREG_SPLIT_NO_EMPTY);
    if (is_array($words) && count($words) >= 2) {
        $initials = mb_strtoupper(mb_substr((string) $words[0], 0, 1) . mb_substr((string) $words[count($words) - 1], 0, 1));
    } else {
        $initials = mb_strtoupper(mb_substr($contactName, 0, min(2, mb_strlen($contactName))));
    }
}
?>
<div class="flex h-full min-h-[calc(100vh-4rem)] flex-col" id="chat-root">
    <?php if ($flashText) : ?>
        <div class="mx-5 mt-5 flex items-start gap-3 rounded-2xl border border-emerald-200/90 bg-emerald-50/95 px-4 py-3.5 text-sm font-medium text-emerald-950 shadow-sm ring-1 ring-emerald-100 sm:mx-8">
            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                <i class="fa-solid fa-circle-check"></i>
            </span>
            <span><?= htmlspecialchars($flashText, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Top bar -->
    <div class="chat-hero border-b border-slate-200/80 bg-white/80 px-5 py-4 backdrop-blur-md sm:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 flex-1 items-start gap-4">
                <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl border border-slate-200/90 bg-white text-slate-600 shadow-sm ring-1 ring-slate-100 transition hover:border-slate-300 hover:bg-slate-50" title="กลับ Inbox">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="flex min-w-0 flex-1 items-start gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-sm font-extrabold text-white shadow-soft ring-2 ring-white">
                        <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-xl font-extrabold tracking-tight text-slate-900"><?= htmlspecialchars((string) ($conversation['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold ring-1 <?= htmlspecialchars(inbox_status_badge((string) ($conversation['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="fa-solid fa-circle text-[5px] opacity-60"></i>
                                <?= htmlspecialchars(inbox_status_th((string) ($conversation['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-0.5 text-[11px] font-bold text-slate-700 ring-1 ring-slate-200/80">
                                <i class="<?= htmlspecialchars(ChannelIcon::faClass($chCode), ENT_QUOTES, 'UTF-8') ?>"></i>
                                <?= htmlspecialchars((string) ($conversation['channel_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] font-medium text-slate-500">
                            <span class="inline-flex items-center gap-1"><i class="fa-solid fa-hashtag text-slate-400"></i> <?= $cid ?></span>
                            <span class="inline-flex items-center gap-1"><i class="fa-solid fa-language text-slate-400"></i> <?= htmlspecialchars((string) ($conversation['language'] ?? 'th'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($conversation['branch_name'])) : ?>
                                <span class="inline-flex items-center gap-1"><i class="fa-solid fa-code-branch text-slate-400"></i><?= htmlspecialchars((string) $conversation['branch_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-bold ring-1 <?= htmlspecialchars($sla['badgeClass'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="h-1.5 w-1.5 rounded-full <?= htmlspecialchars($sla['dotClass'], ENT_QUOTES, 'UTF-8') ?>"></span>
                    <i class="fa-solid fa-stopwatch text-[10px] opacity-70"></i>
                    SLA <?= htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <form method="post" action="<?= htmlspecialchars(Url::to('/inbox/' . $cid . '/status'), ENT_QUOTES, 'UTF-8') ?>" class="flex flex-wrap items-center gap-2">
                    <?= Csrf::field() ?>
                    <label class="sr-only" for="f-status">สถานะ</label>
                    <select name="status" id="f-status" class="ui-select rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                        <?php foreach (['new', 'open', 'pending', 'resolved', 'closed'] as $st) : ?>
                            <option value="<?= $st ?>" <?= ((string) ($conversation['status'] ?? '')) === $st ? 'selected' : '' ?>><?= htmlspecialchars(inbox_status_th($st), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow-soft transition hover:bg-slate-800">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึก
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid flex-1 min-h-0 grid-cols-1 gap-0 lg:grid-cols-12">
        <!-- Messages -->
        <section class="flex min-h-[50vh] flex-col border-slate-200/80 bg-gradient-to-b from-slate-50/90 to-white lg:col-span-8 lg:border-r">
            <div id="msg-scroll" class="app-surface-dots flex-1 space-y-4 overflow-y-auto px-5 py-6 scrollbar-premium sm:px-8">
                <?php foreach ($messages as $m) :
                    $mt = (string) ($m['message_type'] ?? 'text');
                    $dir = (string) ($m['direction'] ?? 'inbound');
                    if ($mt === 'internal_note') : ?>
                        <div class="message-bubble mx-auto max-w-3xl rounded-2xl border border-amber-200/90 bg-gradient-to-br from-amber-50 to-white px-4 py-3.5 text-sm text-amber-950 shadow-soft ring-1 ring-amber-100/90">
                            <div class="flex items-center justify-between gap-2 text-[10px] font-extrabold uppercase tracking-widest text-amber-900/90">
                                <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-lock"></i> Internal note</span>
                                <span class="inline-flex items-center gap-1 font-bold normal-case tracking-normal text-amber-800/90">
                                    <i class="fa-solid fa-user-tie text-[10px]"></i>
                                    <?= htmlspecialchars((string) ($m['agent_name'] ?? 'Agent'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($m['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <p class="mt-3 whitespace-pre-wrap text-[13px] leading-relaxed"><?= htmlspecialchars((string) ($m['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    <?php elseif ($dir === 'inbound') : ?>
                        <div class="message-bubble flex justify-start gap-3">
                            <span class="mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80" title="ลูกค้า">
                                <i class="fa-solid fa-user text-xs"></i>
                            </span>
                            <div class="max-w-[85%] rounded-2xl rounded-tl-md border border-slate-200/90 bg-white/95 px-4 py-3 text-sm text-slate-800 shadow-soft ring-1 ring-slate-100/90">
                                <p class="whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars((string) ($m['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-2 flex items-center gap-1 text-[10px] font-semibold text-slate-400">
                                    <i class="fa-regular fa-clock"></i><?= htmlspecialchars((string) ($m['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="message-bubble flex justify-end gap-3">
                            <div class="max-w-[85%] rounded-2xl rounded-tr-md bg-gradient-to-br from-brand to-red-700 px-4 py-3 text-sm text-white shadow-soft ring-1 ring-white/20">
                                <p class="whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars((string) ($m['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mt-2 flex items-center justify-end gap-1 text-[10px] font-semibold text-red-100/95">
                                    <i class="fa-solid fa-user-tie text-[10px] opacity-90"></i>
                                    <?= htmlspecialchars((string) ($m['agent_name'] ?? 'Agent'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($m['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                            <span class="mt-1 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-2xl bg-brand/10 text-brand shadow-sm ring-1 ring-brand/15" title="ทีม">
                                <i class="fa-solid fa-headset text-xs"></i>
                            </span>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>

            <!-- Composer -->
            <div class="composer-dock border-t border-slate-200/80 bg-white/95 p-5 sm:px-8">
                <div class="mb-5 rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-50/95 to-white p-4 shadow-soft ring-1 ring-slate-100/90" id="erp-product-panel">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-slate-700">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80"><i class="fa-solid fa-box-open text-sm"></i></span>
                            ค้นหาสินค้า (ERP cache)
                        </h3>
                        <span class="text-[10px] font-semibold text-slate-400">สาขา #<?= (int) ($conversation['branch_id'] ?? 1) ?></span>
                    </div>
                    <label class="mt-3 block">
                        <span class="sr-only">ค้นหา SKU หรือชื่อ</span>
                        <input type="search" id="erp-search-q" autocomplete="off" class="ui-input w-full px-3 py-2.5 text-[13px] font-medium text-slate-800 placeholder:text-slate-400" placeholder="พิมพ์ SKU หรือชื่อสินค้า..." />
                    </label>
                    <div id="erp-search-hint" class="mt-2 hidden text-[11px] font-medium text-slate-500"></div>
                    <div id="erp-search-results" class="mt-3 max-h-48 space-y-2 overflow-y-auto rounded-xl border border-slate-100/90 bg-white/80 p-2 text-[12px] empty:hidden"></div>
                </div>
                <form method="post" action="<?= htmlspecialchars(Url::to('/inbox/' . $cid . '/reply'), ENT_QUOTES, 'UTF-8') ?>" class="space-y-4">
                    <?= Csrf::field() ?>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <label class="flex-1 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <span class="mb-1.5 flex items-center gap-2 normal-case tracking-normal">
                                <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200/80"><i class="fa-solid fa-bolt text-sm"></i></span>
                                Canned response
                            </span>
                            <select id="canned-pick" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                                <option value="">— เลือกข้อความสำเร็จรูป —</option>
                                <?php foreach ($canned as $c) : ?>
                                    <option value="<?= (int) ($c['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($c['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flex items-center gap-2 rounded-2xl border border-slate-200/90 bg-slate-50/80 px-3 py-2 text-[12px] font-semibold text-slate-700 ring-1 ring-slate-100">
                            <input type="checkbox" name="set_open" value="1" class="rounded border-slate-300 text-brand focus:ring-brand" checked />
                            <i class="fa-solid fa-unlock text-slate-400"></i>
                            ตั้งสถานะเป็น &ldquo;เปิด&rdquo; หลังส่ง
                        </label>
                    </div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <span class="mb-1.5 flex items-center gap-2 normal-case tracking-normal">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-brand/10 text-brand ring-1 ring-brand/15"><i class="fa-solid fa-paper-plane text-sm"></i></span>
                            ตอบลูกค้า
                        </span>
                        <textarea id="reply-body" name="body" rows="3" class="ui-input mt-1 w-full px-4 py-3 text-[13px] font-medium leading-relaxed text-slate-800 placeholder:text-slate-400" placeholder="พิมพ์ข้อความที่จะส่งออกไปยังช่องทางนี้..." required></textarea>
                    </label>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand to-red-700 px-6 py-3 text-sm font-bold text-white shadow-soft transition hover:brightness-105">
                            <i class="fa-solid fa-paper-plane"></i> ส่งข้อความ
                        </button>
                    </div>
                </form>

                <form method="post" action="<?= htmlspecialchars(Url::to('/inbox/' . $cid . '/note'), ENT_QUOTES, 'UTF-8') ?>" class="mt-6 border-t border-slate-100 pt-6">
                    <?= Csrf::field() ?>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <span class="mb-1.5 flex items-center gap-2 normal-case tracking-normal">
                            <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-amber-100 text-amber-800 ring-1 ring-amber-200/80"><i class="fa-solid fa-note-sticky text-sm"></i></span>
                            Internal note <span class="text-[10px] font-extrabold text-amber-700">(ทีมเห็นเท่านั้น)</span>
                        </span>
                        <textarea name="note" rows="2" class="ui-input mt-1 w-full border-amber-200/90 bg-amber-50/60 px-4 py-3 text-[13px] font-medium text-amber-950 placeholder:text-amber-900/45" placeholder="บันทึกสำหรับทีม — ลูกค้าไม่เห็น"></textarea>
                    </label>
                    <div class="mt-3 flex justify-end">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-amber-300/90 bg-white px-5 py-2.5 text-xs font-bold text-amber-950 shadow-sm ring-1 ring-amber-100 transition hover:bg-amber-50">
                            <i class="fa-solid fa-lock"></i> บันทึกโน้ต
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Sidebar CRM -->
        <aside class="flex flex-col gap-4 border-t border-slate-200/80 bg-white/70 p-5 backdrop-blur-md sm:p-8 lg:col-span-4 lg:border-t-0 lg:border-l lg:border-slate-200/80">
            <div class="rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-50/90 to-white p-5 shadow-soft ring-1 ring-slate-100/90">
                <h3 class="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-brand shadow-sm ring-1 ring-brand/15">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    โปรไฟล์ลูกค้า
                </h3>
                <dl class="mt-4 space-y-3 text-xs font-medium text-slate-600">
                    <div class="flex items-start justify-between gap-3 rounded-xl bg-white/80 px-3 py-2 ring-1 ring-slate-100">
                        <dt class="inline-flex items-center gap-1.5 text-slate-500"><i class="fa-solid fa-envelope text-slate-400"></i> อีเมล</dt>
                        <dd class="text-right font-bold text-slate-900"><?= htmlspecialchars((string) ($conversation['contact_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 rounded-xl bg-white/80 px-3 py-2 ring-1 ring-slate-100">
                        <dt class="inline-flex items-center gap-1.5 text-slate-500"><i class="fa-solid fa-phone text-slate-400"></i> โทรศัพท์</dt>
                        <dd class="text-right font-bold text-slate-900"><?= htmlspecialchars((string) ($conversation['contact_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 rounded-xl bg-white/80 px-3 py-2 ring-1 ring-slate-100">
                        <dt class="inline-flex items-center gap-1.5 text-slate-500"><i class="fa-solid fa-language text-slate-400"></i> ภาษา</dt>
                        <dd class="text-right font-bold text-slate-900"><?= htmlspecialchars((string) ($conversation['contact_language'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 rounded-xl bg-white/80 px-3 py-2 ring-1 ring-slate-100">
                        <dt class="inline-flex items-center gap-1.5 text-slate-500"><i class="fa-solid fa-earth-americas text-slate-400"></i> ต่างชาติ</dt>
                        <dd class="inline-flex items-center gap-1 font-bold text-slate-900"><?= !empty($conversation['is_foreign_customer']) ? '<i class="fa-solid fa-check text-emerald-600"></i> ใช่' : '<i class="fa-solid fa-xmark text-slate-400"></i> ไม่'; ?></dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-soft ring-1 ring-slate-100/90">
                <h3 class="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm">
                        <i class="fa-solid fa-user-check"></i>
                    </span>
                    มอบหมาย Agent
                </h3>
                <form method="post" action="<?= htmlspecialchars(Url::to('/inbox/' . $cid . '/assign'), ENT_QUOTES, 'UTF-8') ?>" class="mt-4 flex flex-col gap-2">
                    <?= Csrf::field() ?>
                    <select name="assigned_user_id" class="ui-select px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">— ยังไม่มอบหมาย —</option>
                        <?php foreach ($agents as $ag) :
                            $aid = (int) $ag['id'];
                            $sel = (int) ($conversation['assigned_user_id'] ?? 0) === $aid ? 'selected' : '';
                            ?>
                            <option value="<?= $aid ?>" <?= $sel ?>><?= htmlspecialchars((string) ($ag['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($ag['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-xs font-bold text-white shadow-soft transition hover:bg-slate-800">
                        <i class="fa-solid fa-check"></i> อัปเดตการมอบหมาย
                    </button>
                </form>
                <?php if (!empty($conversation['assignee_name'])) : ?>
                    <p class="mt-3 inline-flex items-center gap-2 text-[11px] font-semibold text-slate-600">
                        <i class="fa-solid fa-user-check text-slate-400"></i>
                        ปัจจุบัน: <span class="font-extrabold text-slate-900"><?= htmlspecialchars((string) $conversation['assignee_name'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-soft ring-1 ring-slate-100/90">
                <h3 class="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-brand/10 text-brand ring-1 ring-brand/15">
                        <i class="fa-solid fa-tags"></i>
                    </span>
                    Tags
                </h3>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    <?php foreach ($convTags as $tg) : ?>
                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[10px] font-bold text-white shadow" style="background: <?= htmlspecialchars((string) $tg['color_hex'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string) $tg['name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (count($convTags) === 0) : ?>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-400">
                            <i class="fa-regular fa-bookmark"></i> ยังไม่มีแท็ก
                        </span>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= htmlspecialchars(Url::to('/inbox/' . $cid . '/tag'), ENT_QUOTES, 'UTF-8') ?>" class="mt-4 flex gap-2">
                    <?= Csrf::field() ?>
                    <select name="tag_id" class="ui-select min-w-0 flex-1 px-3 py-2 text-[12px] font-bold text-slate-800">
                        <option value="">เพิ่มแท็ก...</option>
                        <?php foreach ($allTags as $tg) : ?>
                            <option value="<?= (int) $tg['id'] ?>"><?= htmlspecialchars((string) $tg['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-gradient-to-r from-brand to-red-700 px-4 py-2 text-xs font-extrabold text-white shadow-soft transition hover:brightness-105">
                        <i class="fa-solid fa-plus"></i> เพิ่ม
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-dashed border-brand/30 bg-gradient-to-br from-brand/[0.07] to-white p-5 shadow-sm ring-1 ring-brand/10">
                <p class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-widest text-brand">
                    <i class="fa-solid fa-stopwatch"></i> SLA tracking
                </p>
                <p class="mt-3 flex items-center justify-between gap-2 text-xs font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-hourglass-start text-slate-400"></i> กำหนดเวลาตอบ</span>
                    <span class="font-extrabold text-slate-900"><?= htmlspecialchars((string) ($conversation['sla_due_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <p class="mt-2 flex items-center justify-between gap-2 text-xs font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-reply text-slate-400"></i> ตอบแรก</span>
                    <span class="font-extrabold text-slate-900"><?= htmlspecialchars((string) ($conversation['first_response_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            </div>
        </aside>
    </div>
</div>
<script>
window.__CANNED_BY_ID__ = <?= json_encode($cannedMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
window.__ERP_SEARCH_URL__ = <?= json_encode(Url::to('/inbox/' . $cid . '/erp-search'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
