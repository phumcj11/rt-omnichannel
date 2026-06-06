<?php
/**
 * Layout สำหรับหน้า login — ไม่มี sidebar
 * @var string $title
 * @var string $appName
 * @var string $contentView
 * @var array<string,mixed> $contentData
 */
declare(strict_types=1);

$cfg = require dirname(__DIR__, 2) . '/config/app.php';
$base = rtrim((string) ($cfg['base_path'] ?? ''), '/');
$assetBase = $base === '' ? '' : $base;
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'เข้าสู่ระบบ', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($appName ?? '', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: { brand: { DEFAULT: '#DC2626', dark: '#B91C1C', light: '#FEE2E2' } },
                    boxShadow: { soft: '0 4px 18px -6px rgba(15,23,42,0.12), 0 2px 6px -2px rgba(15,23,42,0.06)' }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8') ?>/assets/css/app.css">
</head>
<body class="min-h-full bg-gradient-to-br from-slate-100 via-white to-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <?php
        if (!empty($contentView)) {
            extract($contentData ?? [], EXTR_SKIP);
            $inc = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $contentView) . '.php';
            if (is_readable($inc)) {
                include $inc;
            }
        }
        ?>
    </div>
</body>
</html>
