<?php
/**
 * Executive Dashboard — KPI จากฐานข้อมูล (Phase 3)
 * @var array<string,mixed>|null $dashboard
 * @var string|null $dbError
 */
declare(strict_types=1);

use App\Helpers\ChannelIcon;
use App\Helpers\Url;

$kpis = $dashboard['kpis'] ?? null;
$byChannel = $dashboard['by_channel'] ?? [];
$pipelineStages = $dashboard['pipeline_stages'] ?? [];
$slaHot = $dashboard['sla_hot'] ?? [];
$generatedAt = $dashboard['generated_at'] ?? null;

$openConv = $kpis !== null ? (int) ($kpis['open_conversations'] ?? 0) : null;
$totalUnread = $kpis !== null ? (int) ($kpis['total_unread'] ?? 0) : null;
$msgToday = $kpis !== null ? (int) ($kpis['messages_today'] ?? 0) : null;
$openLeads = $kpis !== null ? (int) ($kpis['open_leads'] ?? 0) : null;
$pipeVal = $kpis !== null ? (float) ($kpis['pipeline_value_thb'] ?? 0) : null;
$slaOver = $kpis !== null ? (int) ($kpis['sla_overdue'] ?? 0) : null;

$maxStageLeads = 0;
foreach ($pipelineStages as $ps) {
    $mc = (int) ($ps['lead_count'] ?? 0);
    if ($mc > $maxStageLeads) {
        $maxStageLeads = $mc;
    }
}
if ($maxStageLeads < 1) {
    $maxStageLeads = 1;
}

function dash_num(?int $n): string
{
    if ($n === null) {
        return '—';
    }
    return number_format($n);
}

function dash_money(?float $v): string
{
    if ($v === null) {
        return '—';
    }
    return number_format($v, 0, '.', ',');
}
?>
<section class="mx-auto max-w-6xl space-y-10" id="exec-dashboard">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-brand shadow-sm ring-1 ring-brand/15">
                <i class="fa-solid fa-sparkles text-[10px]"></i> Phase 3 — Executive Dashboard
            </p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">ภาพรวมธุรกิจแบบเรียลไทม์</h2>
            <p class="mt-3 text-[15px] leading-relaxed text-slate-600">
                สรุปจากบทสนทนา ลีด และข้อความในวันนี้ — เชื่อมกับ MySQL โดยตรง
                <?php if ($generatedAt !== null) : ?>
                    <span class="mt-2 flex flex-wrap items-center gap-2 text-[12px] font-medium text-slate-500">
                        <i class="fa-regular fa-clock text-slate-400"></i>
                        อัปเดต <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <div class="hero-actions flex flex-wrap items-center gap-2">
            <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-brand to-red-700 px-5 py-2.5 text-xs font-bold text-white shadow-soft transition hover:brightness-105">
                <i class="fa-solid fa-inbox"></i> Unified Inbox
            </a>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200/90">
                <i class="fa-solid fa-database text-slate-400"></i> Live KPI
            </span>
        </div>
    </div>

    <?php if ($dbError !== null) : ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/95 px-4 py-3 text-sm font-medium text-amber-950 shadow-sm ring-1 ring-amber-100">
            <i class="fa-solid fa-triangle-exclamation mr-2 text-amber-600"></i><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <article class="dashboard-card group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-6 shadow-soft ring-1 ring-slate-100/90 border-l-[3px] border-brand">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/40 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500/15 to-sky-500/5 text-slate-800 shadow-inner ring-1 ring-white/60">
                    <i class="fa-solid fa-comments text-lg"></i>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-100">
                    <i class="fa-solid fa-bolt text-[9px]"></i> Active
                </span>
            </div>
            <p class="relative mt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">บทสนทนาเปิด</p>
            <p class="relative mt-2 text-3xl font-extrabold tabular-nums tracking-tight text-slate-900"><?= htmlspecialchars(dash_num($openConv), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="relative mt-2 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <i class="fa-solid fa-envelope-open-text text-[10px] text-slate-400"></i>
                ยังไม่ปิด · ยังไม่อ่านรวม <?= htmlspecialchars(dash_num($totalUnread), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </article>

        <article class="dashboard-card group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-6 shadow-soft ring-1 ring-slate-100/90 border-l-[3px] border-emerald-500">
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500/15 to-emerald-500/5 text-slate-800 shadow-inner ring-1 ring-white/60">
                    <i class="fa-solid fa-gauge-high text-lg"></i>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 ring-1 ring-slate-200/80">Today</span>
            </div>
            <p class="relative mt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">ข้อความวันนี้</p>
            <p class="relative mt-2 text-3xl font-extrabold tabular-nums tracking-tight text-slate-900"><?= htmlspecialchars(dash_num($msgToday), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="relative mt-2 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <i class="fa-solid fa-circle-info text-[10px] text-slate-400"></i>
                inbound + outbound ตามวันที่เซิร์ฟเวอร์
            </p>
        </article>

        <article class="dashboard-card group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-6 shadow-soft ring-1 ring-slate-100/90 border-l-[3px] border-violet-500">
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/15 to-violet-500/5 text-slate-800 shadow-inner ring-1 ring-white/60">
                    <i class="fa-solid fa-funnel-dollar text-lg"></i>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-violet-800 ring-1 ring-violet-100">THB</span>
            </div>
            <p class="relative mt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">Pipeline (ลีดเปิด)</p>
            <p class="relative mt-2 text-3xl font-extrabold tabular-nums tracking-tight text-slate-900"><?= htmlspecialchars(dash_money($pipeVal), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="relative mt-2 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <i class="fa-solid fa-user-tag text-[10px] text-slate-400"></i>
                <?= htmlspecialchars(dash_num($openLeads), ENT_QUOTES, 'UTF-8') ?> ลีดสถานะเปิด
            </p>
        </article>

        <article class="dashboard-card group relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-6 shadow-soft ring-1 ring-slate-100/90 <?= ($slaOver ?? 0) > 0 ? 'border-l-[3px] border-red-500 ring-red-100/40' : 'border-l-[3px] border-amber-500' ?>">
            <div class="relative flex items-start justify-between gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br <?= ($slaOver ?? 0) > 0 ? 'from-red-500/15 to-red-500/5' : 'from-amber-500/15 to-amber-500/5' ?> text-slate-800 shadow-inner ring-1 ring-white/60">
                    <i class="fa-solid <?= ($slaOver ?? 0) > 0 ? 'fa-triangle-exclamation' : 'fa-shield-halved' ?> text-lg"></i>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full <?= ($slaOver ?? 0) > 0 ? 'bg-red-50 text-red-800 ring-red-100' : 'bg-emerald-50 text-emerald-800 ring-emerald-100' ?> px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1">SLA</span>
            </div>
            <p class="relative mt-5 text-[11px] font-bold uppercase tracking-widest text-slate-400">เลยกำหนด SLA</p>
            <p class="relative mt-2 text-3xl font-extrabold tabular-nums tracking-tight <?= ($slaOver ?? 0) > 0 ? 'text-red-700' : 'text-slate-900' ?>"><?= htmlspecialchars(dash_num($slaOver), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="relative mt-2 flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <i class="fa-solid fa-hourglass-end text-[10px] text-slate-400"></i>
                บทสนทนายังเปิด + ถึงเวลาตอบแล้ว
            </p>
        </article>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="dashboard-panel lg:col-span-2 rounded-2xl border border-slate-200/90 bg-white/95 p-7 shadow-soft ring-1 ring-slate-100/90">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand/10 text-brand shadow-sm ring-1 ring-brand/15">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                    </span>
                    บทสนทนาเปิดตามช่องทาง
                </h3>
            </div>
            <?php if (count($byChannel) === 0) : ?>
                <p class="mt-6 text-sm font-medium text-slate-500">ยังไม่มีบทสนทนาในสถานะเปิด — หรือโหลดข้อมูลไม่สำเร็จ</p>
            <?php else : ?>
                <ul class="mt-6 space-y-3">
                    <?php foreach ($byChannel as $ch) :
                        $oc = (int) ($ch['open_count'] ?? 0);
                        $ur = (int) ($ch['unread_sum'] ?? 0);
                        $chFa = ChannelIcon::faClass((string) ($ch['channel_code'] ?? ''));
                        ?>
                        <li class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50/90 px-4 py-3 ring-1 ring-slate-100/90">
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-white text-lg shadow-sm ring-1 ring-slate-200/80">
                                    <i class="<?= htmlspecialchars($chFa, ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-bold text-slate-900"><?= htmlspecialchars((string) ($ch['channel_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-[11px] font-semibold text-slate-500"><?= htmlspecialchars((string) ($ch['channel_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </span>
                            <span class="flex flex-shrink-0 flex-col items-end text-right">
                                <span class="text-lg font-extrabold tabular-nums text-slate-900"><?= $oc ?></span>
                                <span class="text-[10px] font-semibold text-slate-500">ยังไม่อ่าน <?= $ur ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="dashboard-panel rounded-2xl border border-slate-200/90 bg-white/95 p-7 shadow-soft ring-1 ring-slate-100/90">
            <h3 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-700 shadow-sm ring-1 ring-red-100">
                    <i class="fa-solid fa-fire-flame-curved text-sm"></i>
                </span>
                SLA ต้องจัดการ
            </h3>
            <?php if (count($slaHot) === 0) : ?>
                <p class="mt-5 text-sm font-medium text-slate-500">ไม่มีบทสนทนาที่เลย SLA — ดีมาก</p>
            <?php else : ?>
                <ul class="mt-5 space-y-2.5 text-xs">
                    <?php foreach ($slaHot as $row) :
                        $cid = (int) ($row['id'] ?? 0);
                        ?>
                        <li>
                            <a href="<?= htmlspecialchars(Url::to('/inbox/' . $cid), ENT_QUOTES, 'UTF-8') ?>" class="flex flex-col gap-1 rounded-xl border border-red-100/90 bg-red-50/50 px-3 py-2.5 transition hover:border-red-200 hover:bg-red-50">
                                <span class="flex items-center justify-between gap-2 font-bold text-slate-900">
                                    <span class="truncate">#<?= $cid ?> · <?= htmlspecialchars((string) ($row['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ((int) ($row['unread_count'] ?? 0) > 0) : ?>
                                        <span class="flex-shrink-0 rounded-full bg-brand px-1.5 py-0.5 text-[10px] font-extrabold text-white"><?= (int) $row['unread_count'] ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="flex items-center justify-between text-[11px] font-medium text-slate-600">
                                    <span><?= htmlspecialchars((string) ($row['channel_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="tabular-nums text-red-700"><?= htmlspecialchars((string) ($row['sla_due_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-panel rounded-2xl border border-slate-200/90 bg-white/95 p-7 shadow-soft ring-1 ring-slate-100/90">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-violet-800 shadow-sm ring-1 ring-violet-100">
                    <i class="fa-solid fa-layer-group text-sm"></i>
                </span>
                Pipeline — ลีดเปิดตาม Stage
            </h3>
        </div>
        <div class="mt-6 space-y-4">
            <?php foreach ($pipelineStages as $ps) :
                $lc = (int) ($ps['lead_count'] ?? 0);
                $vs = (float) ($ps['value_sum'] ?? 0);
                $pct = $maxStageLeads > 0 ? (int) round(($lc / $maxStageLeads) * 100) : 0;
                $isWon = !empty($ps['is_won']);
                $isLost = !empty($ps['is_lost']);
                $barClass = $isWon ? 'from-emerald-500 to-emerald-400' : ($isLost ? 'from-slate-400 to-slate-300' : 'from-violet-500 to-brand');
                ?>
                <div>
                    <div class="mb-1 flex items-center justify-between gap-2 text-xs font-semibold text-slate-700">
                        <span><?= htmlspecialchars((string) ($ps['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="tabular-nums text-slate-500"><?= $lc ?> ลีด · <?= number_format($vs, 0, '.', ',') ?> THB</span>
                    </div>
                    <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200/80">
                        <div class="h-full rounded-full bg-gradient-to-r <?= htmlspecialchars($barClass, ENT_QUOTES, 'UTF-8') ?> transition-all duration-500" style="width: <?= max(4, $pct) ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="dashboard-panel lg:col-span-2 rounded-2xl border border-slate-200/90 bg-white/95 p-7 shadow-soft ring-1 ring-slate-100/90">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 shadow-sm ring-1 ring-emerald-100">
                        <i class="fa-solid fa-list-check text-sm"></i>
                    </span>
                    สถานะโมดูล
                </h3>
            </div>
            <ul class="mt-6 space-y-3 text-sm">
                <li class="flex items-center justify-between rounded-2xl bg-emerald-50/90 px-4 py-3.5 ring-1 ring-emerald-100/90 shadow-sm">
                    <span class="flex items-center gap-2.5 font-semibold text-emerald-950">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i> SQL schema + seed
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Done</span>
                </li>
                <li class="flex items-center justify-between rounded-2xl bg-emerald-50/90 px-4 py-3.5 ring-1 ring-emerald-100/90 shadow-sm">
                    <span class="flex items-center gap-2.5 font-semibold text-emerald-950">
                        <i class="fa-solid fa-comments text-emerald-600"></i> Unified Inbox + Chat + ERP search
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Done</span>
                </li>
                <li class="flex items-center justify-between rounded-2xl bg-emerald-50/90 px-4 py-3.5 ring-1 ring-emerald-100/90 shadow-sm">
                    <span class="flex items-center gap-2.5 font-semibold text-emerald-950">
                        <i class="fa-solid fa-chart-line text-emerald-600"></i> Executive Dashboard (KPI)
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">Done</span>
                </li>
            </ul>
        </div>
        <div class="dashboard-panel rounded-2xl border border-dashed border-brand/25 bg-gradient-to-br from-brand/[0.06] via-white to-white p-7 shadow-soft ring-1 ring-brand/10">
            <h3 class="flex items-center gap-2.5 text-lg font-bold text-slate-900">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/90 text-brand shadow-sm ring-1 ring-brand/15">
                    <i class="fa-solid fa-bolt text-sm"></i>
                </span>
                SLA rules
            </h3>
            <ul class="mt-5 space-y-3 text-xs font-medium text-slate-600">
                <li class="flex items-center justify-between gap-2 rounded-xl bg-white/70 px-3 py-2 ring-1 ring-slate-100">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-globe text-slate-400"></i> Web Chat</span>
                    <span class="font-bold text-slate-900">2 นาที</span>
                </li>
                <li class="flex items-center justify-between gap-2 rounded-xl bg-white/70 px-3 py-2 ring-1 ring-slate-100">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-share-nodes text-slate-400"></i> Social</span>
                    <span class="font-bold text-slate-900">5 นาที</span>
                </li>
                <li class="flex items-center justify-between gap-2 rounded-xl bg-white/70 px-3 py-2 ring-1 ring-slate-100">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-triangle-exclamation text-amber-500"></i> Wholesale / Complaint</span>
                    <span class="font-bold text-slate-900">3 นาที</span>
                </li>
            </ul>
            <p class="mt-5 flex items-start gap-2 text-[11px] leading-relaxed text-slate-500">
                <i class="fa-solid fa-database mt-0.5 text-slate-400"></i>
                <span>นิยาม &ldquo;เลย SLA&rdquo; บนแดชบอร์ด: สถานะเปิด + <code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-700 ring-1 ring-slate-200/80">sla_due_at &lt; NOW()</code></span>
            </p>
        </div>
    </div>
</section>
