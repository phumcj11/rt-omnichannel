<?php
/**
 * Unified Inbox — Zendesk / Intercom style list + filters
 * @var array<string,mixed> $filters
 * @var list<array<string,mixed>> $rows
 * @var list<array<string,mixed>> $channels
 * @var list<array<string,mixed>> $branches
 * @var list<array<string,mixed>> $tags
 * @var string|null $dbError
 * @var string|null $flash
 */
declare(strict_types=1);

use App\Helpers\ChannelIcon;
use App\Helpers\SlaUi;
use App\Helpers\Url;

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

$flashText = match ($flash ?? '') {
    'assign' => 'อัปเดตผู้รับผิดชอบแล้ว',
    'reply' => 'ส่งข้อความถึงลูกค้าแล้ว',
    'note' => 'บันทึกโน้ตภายในแล้ว',
    'status' => 'อัปเดตสถานะแล้ว',
    'tag' => 'เพิ่มแท็กแล้ว',
    default => null,
};

$rowCount = is_array($rows) ? count($rows) : 0;
?>
<div class="flex h-full min-h-[calc(100vh-4rem)] flex-col" id="inbox-root">
    <?php if (!empty($dbError)) : ?>
        <div class="mx-5 mt-5 flex items-start gap-3 rounded-2xl border border-red-200/90 bg-red-50/95 px-4 py-3.5 text-sm text-red-900 shadow-sm ring-1 ring-red-100 sm:mx-8">
            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
            <span><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <?php if ($flashText) : ?>
        <div class="mx-5 mt-5 flex items-start gap-3 rounded-2xl border border-emerald-200/90 bg-emerald-50/95 px-4 py-3.5 text-sm font-medium text-emerald-950 shadow-sm ring-1 ring-emerald-100 sm:mx-8" id="inbox-flash">
            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                <i class="fa-solid fa-circle-check"></i>
            </span>
            <span><?= htmlspecialchars($flashText, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="inbox-hero border-b border-slate-200/80 bg-white/60 px-5 py-6 backdrop-blur-md sm:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                    <i class="fa-solid fa-headset text-slate-400"></i> Omnichannel
                </p>
                <h2 class="mt-1 flex flex-wrap items-center gap-3 text-2xl font-extrabold tracking-tight text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-brand/15 to-brand/5 text-brand shadow-sm ring-1 ring-brand/15">
                        <i class="fa-solid fa-inbox"></i>
                    </span>
                    Unified Inbox
                </h2>
                <p class="mt-2 max-w-2xl text-[13px] leading-relaxed text-slate-600">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1 text-violet-500" aria-hidden="true"></i>
                    กรองตามช่องทาง สาขา ภาษา แท็ก และสถานะ — คลิกเธรดเพื่อตอบแชทแบบ Zendesk
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/95 px-4 py-2 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-slate-200/90">
                    <i class="fa-solid fa-layer-group text-slate-400"></i>
                    <?= $rowCount ?> เธรด
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-slate-900/95 px-4 py-2 text-xs font-bold text-white shadow-soft">
                    <i class="fa-solid fa-bolt text-amber-300"></i> SLA aware
                </span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="inbox-filter-panel mx-5 mt-5 rounded-2xl border border-slate-200/90 bg-white/85 p-5 shadow-soft ring-1 ring-slate-100/90 backdrop-blur-md sm:mx-8">
        <div class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm">
                    <i class="fa-solid fa-filter text-[13px]"></i>
                </span>
                ตัวกรองขั้นสูง
            </div>
            <p class="text-[11px] font-medium text-slate-500">
                <i class="fa-regular fa-keyboard mr-1"></i> ลดคลิก — เลือกแล้วกด &ldquo;ใช้ตัวกรอง&rdquo;
            </p>
        </div>
        <div class="mt-5 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-broadcast-tower text-slate-400"></i> Channel
                    </span>
                    <select name="channel_id" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($channels as $ch) : ?>
                            <option value="<?= (int) $ch['id'] ?>" <?= (string) ($filters['channel_id'] ?? '') === (string) $ch['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $ch['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-code-branch text-slate-400"></i> สาขา
                    </span>
                    <select name="branch_id" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($branches as $b) : ?>
                            <option value="<?= (int) $b['id'] ?>" <?= (string) ($filters['branch_id'] ?? '') === (string) $b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $b['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-language text-slate-400"></i> ภาษา
                    </span>
                    <select name="language" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach (['th' => 'ไทย', 'en' => 'English'] as $lk => $lv) : ?>
                            <option value="<?= htmlspecialchars($lk, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['language'] ?? '') === $lk ? 'selected' : '' ?>><?= htmlspecialchars($lv, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-tag text-slate-400"></i> Tag
                    </span>
                    <select name="tag_id" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($tags as $tg) : ?>
                            <option value="<?= (int) $tg['id'] ?>" <?= (string) ($filters['tag_id'] ?? '') === (string) $tg['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $tg['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-flag text-slate-400"></i> สถานะ
                    </span>
                    <select name="status" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach (['new', 'open', 'pending', 'resolved', 'closed'] as $st) : ?>
                            <option value="<?= $st ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= htmlspecialchars(inbox_status_th($st), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-bolt text-amber-500"></i> Priority
                    </span>
                    <select name="priority" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <?php foreach (['low' => 'ต่ำ', 'normal' => 'ปกติ', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'] as $pk => $pv) : ?>
                            <option value="<?= htmlspecialchars($pk, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['priority'] ?? '') === $pk ? 'selected' : '' ?>><?= htmlspecialchars($pv, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-user-check text-slate-400"></i> มอบหมาย
                    </span>
                    <select name="assign" class="ui-select w-full px-3 py-2.5 text-[13px] font-semibold text-slate-800">
                        <option value="">ทั้งหมด</option>
                        <option value="unassigned" <?= ($filters['assign'] ?? '') === 'unassigned' ? 'selected' : '' ?>>ยังไม่มีผู้รับ</option>
                        <option value="me" <?= ($filters['assign'] ?? '') === 'me' ? 'selected' : '' ?>>มอบหมายให้ฉัน</option>
                    </select>
                </label>
            </div>
            <div class="flex w-full flex-col gap-3 lg:w-[320px]">
                <label class="flex flex-col gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <span class="inline-flex items-center gap-1.5 normal-case tracking-normal">
                        <i class="fa-solid fa-magnifying-glass text-slate-400"></i> ค้นหา
                    </span>
                    <input type="search" name="q" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="ชื่อ / เบอร์ / เลขเธรด" class="ui-input w-full px-3 py-2.5 text-[13px] font-medium text-slate-800 placeholder:text-slate-400" />
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-brand to-red-700 px-4 py-2.5 text-sm font-bold text-white shadow-soft transition hover:brightness-105">
                        <i class="fa-solid fa-filter"></i> ใช้ตัวกรอง
                    </button>
                    <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200/90 bg-white/95 px-3 py-2.5 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-slate-100 transition hover:bg-slate-50">
                        <i class="fa-solid fa-rotate-left"></i> ล้าง
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- List -->
    <div class="flex-1 overflow-y-auto px-5 py-6 scrollbar-premium sm:px-8">
        <?php if (empty($rows) && $dbError === null) : ?>
            <div class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-200/90 bg-white/80 py-24 text-center shadow-sm ring-1 ring-slate-100/90">
                <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-gradient-to-br from-slate-50 to-white text-slate-400 shadow-inner ring-1 ring-slate-200/80">
                    <i class="fa-solid fa-inbox text-3xl"></i>
                </div>
                <p class="mt-6 text-base font-bold text-slate-900">ไม่มีบทสนทนาที่ตรงกับตัวกรอง</p>
                <p class="mt-2 max-w-md text-sm text-slate-500">
                    <i class="fa-solid fa-lightbulb mr-1 text-amber-500"></i>
                    ลองเปลี่ยน Channel / สาขา หรือกด &ldquo;ล้าง&rdquo; เพื่อดูทั้งหมด
                </p>
            </div>
        <?php else : ?>
            <ul class="space-y-3">
                <?php foreach ($rows as $row) :
                    $sla = SlaUi::badge(isset($row['sla_due_at']) ? (string) $row['sla_due_at'] : null);
                    $chCode = (string) ($row['channel_code'] ?? '');
                    $tagsPacked = (string) ($row['tags_packed'] ?? '');
                    $tagItems = $tagsPacked !== '' ? explode('||', $tagsPacked) : [];
                    ?>
                    <li class="inbox-row">
                        <a href="<?= htmlspecialchars(Url::to('/inbox/' . (int) $row['id']), ENT_QUOTES, 'UTF-8') ?>" class="group relative flex flex-col gap-4 overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-5 shadow-soft ring-1 ring-slate-100/80 sm:flex-row sm:items-center sm:gap-5">
                            <span class="pointer-events-none absolute inset-y-0 left-0 w-[3px] bg-gradient-to-b from-brand to-red-700 opacity-90"></span>
                            <div class="flex min-w-0 flex-1 items-start gap-4">
                                <span class="mt-0.5 flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-50 to-white text-lg shadow-inner ring-1 ring-slate-200/80">
                                    <i class="<?= htmlspecialchars(ChannelIcon::faClass($chCode), ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="truncate text-base font-bold text-slate-900"><?= htmlspecialchars((string) ($row['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if (!empty($row['contact_phone'])) : ?>
                                            <span class="inline-flex items-center gap-1 truncate rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200/80">
                                                <i class="fa-solid fa-phone text-[10px] text-slate-400"></i><?= htmlspecialchars((string) $row['contact_phone'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 <?= htmlspecialchars(inbox_status_badge((string) ($row['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-circle text-[5px] opacity-60"></i>
                                            <?= htmlspecialchars(inbox_status_th((string) ($row['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if (!empty($row['is_wholesale'])) : ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-bold text-purple-900 ring-1 ring-purple-200/80">
                                                <i class="fa-solid fa-boxes-stacked text-[9px]"></i> Wholesale
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['is_complaint'])) : ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-900 ring-1 ring-red-200/80">
                                                <i class="fa-solid fa-face-angry text-[9px]"></i> Complaint
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($row['repeat_customer_ping'])) : ?>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-950 ring-1 ring-amber-200/80">
                                                <i class="fa-solid fa-fire-flame-curved"></i> ทักซ้ำ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-2 line-clamp-2 text-[13px] leading-relaxed text-slate-600">
                                        <i class="fa-solid fa-quote-left mr-1 text-[10px] text-slate-300"></i>
                                        <?= htmlspecialchars((string) ($row['last_message_preview'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <?php foreach ($tagItems as $tp) :
                                            $parts = explode(':', $tp, 3);
                                            if (count($parts) < 3) {
                                                continue;
                                            }
                                            $tname = $parts[1];
                                            $thex = $parts[2];
                                            ?>
                                            <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-bold text-white shadow-sm ring-1 ring-black/5" style="background: <?= htmlspecialchars($thex, ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa-solid fa-tag text-[9px] opacity-80"></i><?= htmlspecialchars($tname, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-shrink-0 flex-row items-center justify-between gap-4 border-t border-slate-100 pt-4 sm:flex-col sm:border-0 sm:pt-0 sm:text-right">
                                <div class="text-left text-xs sm:text-right">
                                    <span class="inline-flex items-center gap-1.5 font-bold text-slate-800">
                                        <i class="<?= htmlspecialchars(ChannelIcon::faClass($chCode), ENT_QUOTES, 'UTF-8') ?>"></i><?= htmlspecialchars((string) ($row['channel_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($row['branch_name'])) : ?>
                                        <span class="mt-1 flex items-center gap-1 text-[11px] font-medium text-slate-500 sm:justify-end">
                                            <i class="fa-solid fa-location-dot text-[10px] text-slate-400"></i><?= htmlspecialchars((string) $row['branch_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 <?= htmlspecialchars($sla['badgeClass'], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="h-1.5 w-1.5 rounded-full <?= htmlspecialchars($sla['dotClass'], ENT_QUOTES, 'UTF-8') ?>"></span>
                                        <i class="fa-solid fa-stopwatch text-[9px] opacity-70"></i>
                                        SLA <?= htmlspecialchars($sla['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($row['assignee_name'])) : ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600">
                                            <i class="fa-solid fa-user-headset text-slate-400"></i><?= htmlspecialchars((string) $row['assignee_name'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800">
                                            <i class="fa-solid fa-user-slash"></i>ยังไม่มีผู้รับ
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['unread_count']) && (int) $row['unread_count'] > 0) : ?>
                                        <span class="inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-gradient-to-r from-brand to-red-700 px-2 py-0.5 text-[10px] font-extrabold text-white shadow-sm ring-2 ring-white">
                                            <i class="fa-solid fa-envelope-open-text mr-1 text-[9px]"></i><?= (int) $row['unread_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="hidden text-[10px] font-bold uppercase tracking-widest text-brand opacity-0 transition group-hover:opacity-100 sm:inline">
                                        <i class="fa-solid fa-arrow-right ml-1"></i> เปิด
                                    </span>
                                </div>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
