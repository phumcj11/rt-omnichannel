<?php
/**
 * @var list<array<string,mixed>> $rules
 * @var string|null $dbError
 */
declare(strict_types=1);

use App\Helpers\Url;
?>
<section class="mx-auto max-w-4xl space-y-6">
    <div>
        <a href="<?= htmlspecialchars(Url::to('/settings'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-slate-500 hover:text-brand">&larr; Settings</a>
        <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">SLA Settings</h2>
        <p class="mt-2 text-sm text-slate-600">กฎ SLA จากตาราง <code class="rounded bg-slate-100 px-1 text-xs">sla_rules</code> (อ่านอย่างเดียว — แก้ใน DB / phpMyAdmin)</p>
    </div>

    <?php if ($dbError !== null) : ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else : ?>
        <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-soft ring-1 ring-slate-100">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-100 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ชื่อ</th>
                        <th class="px-4 py-3">ประเภท</th>
                        <th class="px-4 py-3">นาที</th>
                        <th class="px-4 py-3">Priority</th>
                        <th class="px-4 py-3">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($rules as $r) : ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-4 py-3 font-semibold text-slate-900"><?= htmlspecialchars((string) ($r['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3 text-slate-600">
                                <?= htmlspecialchars((string) ($r['rule_kind'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($r['channel_code'])) : ?>
                                    <span class="text-xs">(<?= htmlspecialchars((string) $r['channel_code'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php elseif (!empty($r['category'])) : ?>
                                    <span class="text-xs">(<?= htmlspecialchars((string) $r['category'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-bold tabular-nums"><?= (int) ($r['minutes'] ?? 0) ?></td>
                            <td class="px-4 py-3 tabular-nums text-slate-600"><?= (int) ($r['priority'] ?? 0) ?></td>
                            <td class="px-4 py-3">
                                <?= !empty($r['is_active']) ? '<span class="text-emerald-700 font-bold">เปิด</span>' : '<span class="text-slate-400">ปิด</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
