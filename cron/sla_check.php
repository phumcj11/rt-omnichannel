#!/usr/bin/env php
<?php
/**
 * Cron: ตรวจ SLA / alert — รันทุก 1 นาที (Phase 7 เติม checkSLA + sendAlert)
 * CLI: php cron/sla_check.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/bootstrap.php';

// Placeholder — ไม่ exit error เพื่อให้ cron test ผ่าน
fwrite(STDOUT, "[omnichannel] sla_check: OK (stub)\n");
exit(0);
