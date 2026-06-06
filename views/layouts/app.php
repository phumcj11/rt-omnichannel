<?php
/**
 * Layout หลัก — Premium SaaS (Intercom / Zendesk inspired)
 * @var string $title
 * @var string $appName
 * @var string $contentView view ย่อย เช่น home/index
 * @var array<string,mixed> $contentData
 */
declare(strict_types=1);

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Heroicon;
use App\Helpers\Url;

$authUserName = Auth::userName() ?? 'ผู้ใช้งาน';
$authUserEmail = Auth::userEmail();

$cfg = require dirname(__DIR__, 2) . '/config/app.php';
$base = rtrim((string) ($cfg['base_path'] ?? ''), '/');
$assetBase = $base === '' ? '' : $base;

$navActive = $navActive ?? '';
$mainFlush = $mainFlush ?? false;
$extraScripts = $extraScripts ?? [];

/** Heroicons (outline) สำหรับหัวข้อหลัก */
$headerHero = match ($navActive) {
    'dashboard' => 'chart-bar',
    'inbox' => 'inbox',
    'crm' => 'user-group',
    'settings', 'settings-channels', 'settings-sla' => 'cog-6-tooth',
    default => 'cube',
};

$navLink = static function (string $key, string $base = 'border-l-[3px] border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50/90') use ($navActive): string {
    if ($navActive === $key) {
        return 'border-l-[3px] border-brand bg-gradient-to-r from-brand/[0.09] to-transparent text-brand shadow-sm ring-1 ring-brand/10';
    }

    return $base;
};

$navIconBox = static function (string $key) use ($navActive): string {
    return $navActive === $key
        ? 'bg-brand/10 text-brand ring-brand/25'
        : 'bg-white/70 text-slate-600 ring-slate-200/80';
};

$dashNav = $navLink('dashboard');
$inboxNav = $navLink('inbox');
$crmNav = $navLink('crm');
$settingsNav = $navLink('settings');
$settingsChannelsNav = $navLink('settings-channels');
$settingsSlaNav = $navLink('settings-sla');

$dashIconBox = $navIconBox('dashboard');
$inboxIconBox = $navIconBox('inbox');

$mainClass = $mainFlush
    ? 'flex flex-1 min-h-0 flex-col overflow-hidden'
    : 'flex-1 px-5 py-8 sm:px-8 sm:py-10 lg:px-10';
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($appName ?? '', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { DEFAULT: '#DC2626', dark: '#B91C1C', light: '#FEE2E2' }
                    },
                    boxShadow: {
                        soft: '0 4px 18px -6px rgba(15,23,42,0.12), 0 2px 6px -2px rgba(15,23,42,0.06)',
                        'soft-lg': '0 18px 50px -24px rgba(15,23,42,0.18), 0 8px 20px -12px rgba(15,23,42,0.08)',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="app-shell h-full text-slate-900 antialiased" data-page="<?= htmlspecialchars($navActive ?: 'app', ENT_QUOTES, 'UTF-8') ?>">
    <div class="flex min-h-full">
        <!-- Sidebar -->
        <aside class="sidebar-shell relative z-50 hidden w-[272px] flex-shrink-0 flex-col border-r border-slate-200/90 bg-white/85 shadow-soft backdrop-blur-xl lg:flex" id="app-sidebar">
            <div class="flex h-[72px] items-center gap-3 border-b border-slate-200/80 px-5">
                <span class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand to-red-700 text-white shadow-soft">
                    <?= Heroicon::svg('building-storefront', ['class' => 'h-5 w-5']) ?>
                    <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-white" title="ระบบพร้อมใช้งาน">
                        <?= Heroicon::svg('check', ['class' => 'h-2 w-2 text-white']) ?>
                    </span>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold tracking-tight text-slate-900">100 Baht Shop</p>
                    <p class="truncate text-[11px] font-medium text-slate-500">Omnichannel · CRM</p>
                </div>
            </div>
            <nav class="flex-1 space-y-5 overflow-y-auto px-3 py-5 text-[13px]">
                <div>
                    <p class="mb-2 flex items-center gap-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <span class="text-slate-400 opacity-80"><?= Heroicon::svg('squares-2x2', ['class' => 'h-3.5 w-3.5']) ?></span> หลัก
                    </p>
                    <div class="space-y-0.5">
                        <a href="<?= htmlspecialchars(Url::to('/'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $dashNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg shadow-sm ring-1 <?= htmlspecialchars($dashIconBox, ENT_QUOTES, 'UTF-8') ?>">
                                <?= Heroicon::svg('chart-bar', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="min-w-0 flex-1 font-semibold">Executive Dashboard</span>
                            <span class="text-slate-300 opacity-0 transition-opacity group-hover:opacity-100 <?= $navActive === 'dashboard' ? 'opacity-100 text-brand' : '' ?>"><?= Heroicon::svg('chevron-right', ['class' => 'h-3.5 w-3.5']) ?></span>
                        </a>
                        <a href="<?= htmlspecialchars(Url::to('/inbox'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $inboxNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg shadow-sm ring-1 <?= htmlspecialchars($inboxIconBox, ENT_QUOTES, 'UTF-8') ?>">
                                <?= Heroicon::svg('inbox', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="min-w-0 flex-1 font-semibold">Unified Inbox</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-100">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Live
                            </span>
                        </a>
                        <a href="<?= htmlspecialchars(Url::to('/crm'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $crmNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg shadow-sm ring-1 <?= htmlspecialchars($navIconBox('crm'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= Heroicon::svg('user-group', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="min-w-0 flex-1 font-semibold">CRM</span>
                            <span class="rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold text-violet-700 ring-1 ring-violet-100">Beta</span>
                        </a>
                    </div>
                </div>
                <div>
                    <p class="mb-2 flex items-center gap-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        <span class="text-slate-400 opacity-80"><?= Heroicon::svg('cog-6-tooth', ['class' => 'h-3.5 w-3.5']) ?></span> ตั้งค่า
                    </p>
                    <div class="space-y-0.5">
                        <a href="<?= htmlspecialchars(Url::to('/settings'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $settingsNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg shadow-sm ring-1 <?= htmlspecialchars($navIconBox('settings'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= Heroicon::svg('cog-6-tooth', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="font-semibold">Settings</span>
                        </a>
                        <a href="<?= htmlspecialchars(Url::to('/settings/sla'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $settingsSlaNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200/60">
                                <?= Heroicon::svg('clock', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="font-semibold">SLA Settings</span>
                        </a>
                        <a href="<?= htmlspecialchars(Url::to('/settings/channels'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $settingsChannelsNav ?> group flex items-center gap-3 rounded-xl px-3 py-2.5 pl-[10px] transition-all duration-200 ease-out">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 text-emerald-700 ring-1 ring-slate-200/60">
                                <?= Heroicon::svg('chat-bubble-left-right', ['class' => 'h-4 w-4']) ?>
                            </span>
                            <span class="font-semibold">Channel Settings</span>
                        </a>
                    </div>
                </div>
            </nav>
            <div class="border-t border-slate-200/80 p-4">
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50/80 p-3 shadow-sm ring-1 ring-slate-100">
                    <div class="relative flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-brand-light to-white text-brand ring-2 ring-white shadow-sm">
                        <?= Heroicon::svg('user', ['class' => 'h-5 w-5']) ?>
                        <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500" title="Online"></span>
                    </div>
                    <div class="min-w-0 flex-1 text-xs">
                        <p class="flex items-center gap-1 font-bold text-slate-900">
                            <span class="text-slate-400"><?= Heroicon::svg('user', ['class' => 'h-3.5 w-3.5']) ?></span> <?= htmlspecialchars($authUserName, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500"><?= $authUserEmail !== null ? htmlspecialchars($authUserEmail, ENT_QUOTES, 'UTF-8') : 'เข้าสู่ระบบแล้ว' ?></p>
                    </div>
                    <form method="post" action="<?= htmlspecialchars(Url::to('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="inline">
                        <?= Csrf::field() ?>
                        <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-white hover:text-red-600 hover:shadow-sm" title="ออกจากระบบ" aria-label="ออกจากระบบ">
                            <i class="fa-solid fa-right-from-bracket text-sm"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 flex min-h-[64px] items-center gap-3 border-b border-slate-200/70 bg-white/75 px-4 py-3 shadow-sm shadow-slate-200/40 backdrop-blur-xl sm:gap-4 sm:px-6">
                <button type="button" class="inline-flex rounded-xl p-2.5 text-slate-600 transition hover:bg-slate-100/90 lg:hidden" id="btn-sidebar" aria-label="เปิดเมนู">
                    <?= Heroicon::svg('bars-3', ['class' => 'h-6 w-6']) ?>
                </button>
                <div class="flex min-w-0 flex-1 flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-3">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-50 to-white text-slate-700 shadow-sm ring-1 ring-slate-200/90">
                            <?= Heroicon::svg($headerHero, ['class' => 'h-5 w-5']) ?>
                        </span>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl"><?= htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="hidden text-[11px] font-medium text-slate-500 sm:flex sm:items-center sm:gap-1.5">
                                <span class="text-slate-400"><?= Heroicon::svg('building-storefront', ['class' => 'h-3.5 w-3.5']) ?></span>
                                <span class="truncate"><?= htmlspecialchars($appName ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </p>
                        </div>
                    </div>
                    <span class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-100 sm:inline-flex">
                        <span class="text-emerald-600"><?= Heroicon::svg('shield-check', ['class' => 'h-3.5 w-3.5']) ?></span> Production-ready
                    </span>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <button type="button" class="hidden items-center gap-2 rounded-xl border border-slate-200/90 bg-white/90 px-3 py-2 text-[13px] font-medium text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-white sm:inline-flex" title="ค้นหา (เร็วๆ นี้)">
                        <span class="text-slate-400"><?= Heroicon::svg('magnifying-glass', ['class' => 'h-4 w-4']) ?></span>
                        <span>ค้นหา</span>
                        <kbd class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200/80">/</kbd>
                    </button>
                    <button type="button" class="hidden rounded-xl p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 sm:inline-flex" title="ช่วยเหลือ">
                        <?= Heroicon::svg('question-mark-circle', ['class' => 'h-5 w-5']) ?>
                    </button>
                    <button type="button" class="relative rounded-xl p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="แจ้งเตือน">
                        <?= Heroicon::svg('bell', ['class' => 'h-5 w-5']) ?>
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-brand shadow-sm ring-2 ring-white"></span>
                    </button>
                </div>
            </header>

            <main class="<?= htmlspecialchars($mainClass, ENT_QUOTES, 'UTF-8') ?>">
                <?php
                if (!empty($contentView)) {
                    extract($contentData ?? [], EXTR_SKIP);
                    $inc = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $contentView) . '.php';
                    if (is_readable($inc)) {
                        include $inc;
                    }
                }
                ?>
            </main>
        </div>
    </div>

    <script src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js"></script>
    <?php foreach ($extraScripts as $src) : ?>
        <script src="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endforeach; ?>
</body>
</html>
