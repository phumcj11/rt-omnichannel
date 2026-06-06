#!/usr/bin/env php
<?php
/**
 * Cron: sync ERP → cache tables — Phase 5
 * CLI: php cron/erp_sync.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/bootstrap.php';

fwrite(STDOUT, "[omnichannel] erp_sync: OK (stub)\n");
exit(0);
