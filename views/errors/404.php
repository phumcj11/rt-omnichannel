<?php
/** @var string $title */
declare(strict_types=1);
$cfg = require dirname(__DIR__, 2) . '/config/app.php';
$base = rtrim((string) ($cfg['base_path'] ?? ''), '/');
$assetBase = $base === '' ? '' : $base;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · <?= htmlspecialchars($title ?? 'ไม่พบหน้า', ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 p-6">
    <div class="max-w-md text-center">
        <p class="text-6xl font-black text-brand/20">404</p>
        <h1 class="mt-2 text-xl font-bold text-slate-900"><?= htmlspecialchars($title ?? 'ไม่พบหน้า', ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="mt-2 text-sm text-slate-600">ตรวจสอบ URL หรือกลับไปที่แดชบอร์ด</p>
        <a href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-red-700">
            <i class="fa-solid fa-house"></i> กลับหน้าหลัก
        </a>
    </div>
</body>
</html>
