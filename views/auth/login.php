<?php
/**
 * @var string|null $error
 * @var bool $okOut
 * @var string $next
 * @var bool $debug
 * @var bool $showLoginHint
 */
declare(strict_types=1);

use App\Helpers\Csrf;
use App\Helpers\Url;

$okOut = $okOut ?? false;
$error = $error ?? null;
$next = $next ?? '/';
$debug = $debug ?? false;
$showLoginHint = $showLoginHint ?? false;
?>
<div class="w-full max-w-md rounded-2xl border border-slate-200/90 bg-white/95 p-8 shadow-soft ring-1 ring-slate-100/90">
    <div class="flex flex-col items-center text-center">
        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand to-red-700 text-white shadow-soft">
            <i class="fa-solid fa-lock text-xl"></i>
        </span>
        <h1 class="mt-5 text-xl font-extrabold tracking-tight text-slate-900">เข้าสู่ระบบ</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Omnichannel · ทีมงานเท่านั้น</p>
    </div>

    <?php if ($okOut) : ?>
        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/90 px-3 py-2.5 text-center text-sm font-medium text-emerald-900">
            <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> ออกจากระบบแล้ว
        </div>
    <?php endif; ?>

    <?php if ($error !== null) : ?>
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50/90 px-3 py-2.5 text-center text-sm font-medium text-red-900">
            <i class="fa-solid fa-circle-exclamation mr-1 text-red-600"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars(Url::to('/login'), ENT_QUOTES, 'UTF-8') ?>" class="mt-8 space-y-5">
        <?= Csrf::field() ?>
        <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES, 'UTF-8') ?>" />
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
            อีเมล
            <input type="email" name="email" required autocomplete="username" value="<?= $showLoginHint ? 'admin@100bahtshop.local' : '' ?>" class="ui-input mt-1.5 w-full px-4 py-3 text-[13px] font-medium text-slate-800" placeholder="admin@100bahtshop.local" />
        </label>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">
            รหัสผ่าน
            <input type="password" name="password" required autocomplete="current-password" class="ui-input mt-1.5 w-full px-4 py-3 text-[13px] font-medium text-slate-800" placeholder="admin123" />
        </label>
        <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-brand to-red-700 py-3 text-sm font-bold text-white shadow-soft transition hover:brightness-105">
            <i class="fa-solid fa-right-to-bracket mr-2"></i> เข้าสู่ระบบ
        </button>
    </form>

    <?php if ($showLoginHint) : ?>
        <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50/90 px-4 py-3 text-center text-sm text-blue-950">
            <p class="font-bold">บัญชีทดสอบ</p>
            <p class="mt-1 font-mono text-xs">admin@100bahtshop.local</p>
            <p class="font-mono text-xs">รหัสผ่าน: <strong>admin123</strong></p>
        </div>
    <?php endif; ?>

    <?php if ($debug) : ?>
        <p class="mt-6 text-center text-[10px] font-medium text-amber-700">
            <i class="fa-solid fa-bug mr-1"></i> โหมด debug — ปิด production ด้วย APP_DEBUG=false
        </p>
    <?php endif; ?>
</div>
