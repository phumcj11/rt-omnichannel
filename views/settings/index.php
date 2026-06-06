<?php
declare(strict_types=1);

use App\Helpers\Url;
?>
<section class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Settings</h2>
        <p class="mt-2 text-sm text-slate-600">การตั้งค่าระบบ Omnichannel</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <a href="<?= htmlspecialchars(Url::to('/settings/channels'), ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/90 bg-white p-6 shadow-soft ring-1 ring-slate-100 transition hover:border-brand/30 hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                <i class="fa-solid fa-comments"></i>
            </span>
            <h3 class="mt-4 font-bold text-slate-900 group-hover:text-brand">Channel Settings</h3>
            <p class="mt-1 text-sm text-slate-500">คู่มือ How To + ฟอร์มตั้งค่า Facebook — ไม่ต้องแก้ไฟล์</p>
        </a>
        <a href="<?= htmlspecialchars(Url::to('/settings/sla'), ENT_QUOTES, 'UTF-8') ?>" class="group rounded-2xl border border-slate-200/90 bg-white p-6 shadow-soft ring-1 ring-slate-100 transition hover:border-brand/30 hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                <i class="fa-solid fa-stopwatch"></i>
            </span>
            <h3 class="mt-4 font-bold text-slate-900 group-hover:text-brand">SLA Settings</h3>
            <p class="mt-1 text-sm text-slate-500">ดูกฎ SLA จากฐานข้อมูล</p>
        </a>
    </div>
</section>
