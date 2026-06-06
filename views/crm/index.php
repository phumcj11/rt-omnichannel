<?php
/**
 * @var list<array<string,mixed>> $stages
 * @var list<array<string,mixed>> $leads
 * @var string|null $dbError
 */
declare(strict_types=1);

use App\Helpers\Url;
?>
<section class="mx-auto max-w-5xl space-y-8">
    <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">CRM · Pipeline</h2>
        <p class="mt-2 text-sm text-slate-600">ดูลีดและ stage จากฐานข้อมูล — หน้าจัดการเต็มรูปแบบกำลังพัฒนา</p>
    </div>

    <?php if ($dbError !== null) : ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else : ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($stages as $s) : ?>
                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-900 ring-1 ring-violet-100">
                    <?= htmlspecialchars((string) ($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endforeach; ?>
        </div>

        <div class="rounded-2xl border border-slate-200/90 bg-white shadow-soft ring-1 ring-slate-100">
            <h3 class="border-b border-slate-100 px-5 py-4 text-sm font-bold text-slate-900">ลีดล่าสุด</h3>
            <?php if (count($leads) === 0) : ?>
                <p class="px-5 py-8 text-sm text-slate-500">ยังไม่มีลีด — สร้างจากบทสนทนาใน Phase ถัดไป</p>
            <?php else : ?>
                <ul class="divide-y divide-slate-100">
                    <?php foreach ($leads as $l) : ?>
                        <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                            <div>
                                <p class="font-bold text-slate-900"><?= htmlspecialchars((string) ($l['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs text-slate-500"><?= htmlspecialchars((string) ($l['contact_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($l['stage_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <span class="text-sm font-bold tabular-nums text-slate-800"><?= number_format((float) ($l['deal_value'] ?? 0), 0) ?> <?= htmlspecialchars((string) ($l['currency'] ?? 'THB'), ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <p class="text-center">
            <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-bold text-white shadow-soft hover:brightness-105">
                <i class="fa-solid fa-inbox"></i> ไป Unified Inbox
            </a>
        </p>
    <?php endif; ?>
</section>
