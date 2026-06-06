<?php
/**
 * @var list<array<string,mixed>> $channels
 * @var array<string,mixed> $facebook
 * @var string $webhookUrl
 * @var bool $canEdit
 * @var string $tokenMask
 * @var string $secretMask
 * @var string $suggestedVerify
 * @var string|null $flash
 * @var string|null $flashError
 * @var list<array<string,mixed>> $fbPages
 * @var int $fbPageCount
 * @var list<array<string,mixed>> $branches
 * @var bool $isLocalhost
 * @var string $activeVerifyToken
 * @var bool $verifyTokenInDb
 * @var list<array<string,mixed>> $webhookLogs
 * @var array<string,mixed> $webhookAnalysis
 */
declare(strict_types=1);

use App\Helpers\ChannelIcon;
use App\Helpers\Csrf;
use App\Helpers\Url;

$hasFbToken = trim((string) ($facebook['page_access_token'] ?? '')) !== '';
$hasFbSecret = trim((string) ($facebook['app_secret'] ?? '')) !== '';
$verifyToken = (string) ($facebook['verify_token'] ?? '');
$pageId = (string) ($facebook['page_id'] ?? '');
$appId = (string) ($facebook['app_id'] ?? '');
$canEdit = $canEdit ?? false;
$fbPages = $fbPages ?? [];
$fbPageCount = $fbPageCount ?? count($fbPages);
$branches = $branches ?? [];
$isLocalhost = $isLocalhost ?? false;
$activeVerifyToken = $activeVerifyToken ?? $verifyToken;
$verifyTokenInDb = $verifyTokenInDb ?? false;
$webhookLogs = $webhookLogs ?? [];
$webhookAnalysis = $webhookAnalysis ?? ['has_log' => false];
?>
<section class="mx-auto max-w-4xl space-y-8" id="channel-settings">
    <div>
        <a href="<?= htmlspecialchars(Url::to('/settings'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-slate-500 hover:text-brand">&larr; Settings</a>
        <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">เชื่อมต่อช่องทาง</h2>
        <p class="mt-2 text-sm text-slate-600">ตั้งค่า Facebook Page ผ่านฟอร์ม — อ่านคู่มือ How To ด้านล่างแล้วทำตามทีละขั้น</p>
    </div>

    <?php require __DIR__ . '/partials/facebook-howto.php'; ?>

    <?php if ($flash !== null) : ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
            <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if ($flashError !== null) : ?>
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900">
            <i class="fa-solid fa-circle-exclamation mr-1 text-red-600"></i><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($isLocalhost) : ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm ring-1 ring-amber-100" id="localhost-guide">
            <h3 class="flex items-center gap-2 font-bold text-amber-950">
                <i class="fa-solid fa-laptop-code text-amber-600"></i> ทดสอบบน Localhost — เชื่อมได้จริงไหม?
            </h3>
            <p class="mt-2 text-sm text-amber-900/90">ตั้งค่าครบแล้วบน XAMPP <strong>ใช้งานได้บางส่วน</strong> — Webhook รับข้อความเข้าต้องมี URL ที่ Facebook เข้าถึงจากอินเทอร์เน็ตได้</p>
            <div class="mt-4 overflow-hidden rounded-xl border border-amber-200/80 bg-white text-sm">
                <table class="w-full text-left">
                    <thead class="bg-amber-50/80 text-xs font-bold uppercase tracking-wide text-amber-900">
                        <tr>
                            <th class="px-4 py-2.5">ฟังก์ชัน</th>
                            <th class="px-4 py-2.5">localhost อย่างเดียว</th>
                            <th class="px-4 py-2.5">localhost + ngrok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100 text-slate-700">
                        <tr>
                            <td class="px-4 py-2.5">ทดสอบ Token (ปุ่มทดสอบการเชื่อมต่อ)</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5">ตอบข้อความจาก Inbox ออก Messenger</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5">Verify Webhook ใน Meta</td>
                            <td class="px-4 py-2.5 text-red-700 font-semibold">✗ ไม่ได้</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5">รับข้อความลูกค้าเข้า Inbox (Webhook)</td>
                            <td class="px-4 py-2.5 text-red-700 font-semibold">✗ ไม่ได้</td>
                            <td class="px-4 py-2.5 text-emerald-700 font-semibold">✓ ได้</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-amber-900/80">
                <strong>สรุป:</strong> ใส่ Token ครบ → กดทดสอบและตอบแชทที่มีอยู่แล้วได้บน localhost
                · ถ้าต้องการให้ลูกค้าทักแล้วเข้า Inbox → รัน <code class="rounded bg-amber-100 px-1">ngrok http 80</code> แล้วใช้ Webhook URL แบบ <code class="rounded bg-amber-100 px-1">https://xxxx.ngrok-free.app/omnichannel/webhooks/facebook.php</code>
                · <a href="#howto-webhook" class="font-semibold text-brand hover:underline">ดูขั้นตอน ngrok ในคู่มือ</a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Facebook wizard -->
    <div class="rounded-2xl border border-slate-200/90 bg-white shadow-soft ring-1 ring-slate-100">
        <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50/80 to-white px-6 py-5">
            <h3 class="flex items-center gap-2 text-lg font-bold text-slate-900">
                <i class="fa-brands fa-facebook text-blue-600"></i> Facebook Messenger
            </h3>
            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold">
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 ring-1 <?= $hasFbToken ? 'bg-emerald-50 text-emerald-800 ring-emerald-100' : 'bg-amber-50 text-amber-800 ring-amber-100' ?>">
                    <?= $hasFbToken ? '✓ Token' : '○ Token' ?>
                </span>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 ring-1 <?= $hasFbSecret ? 'bg-emerald-50 text-emerald-800 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200' ?>">
                    <?= $hasFbSecret ? '✓ App Secret' : '○ App Secret (แนะนำ)' ?>
                </span>
                <span id="fb-test-badge" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">○ ยังไม่ทดสอบ</span>
                <?php if ($fbPageCount > 0) : ?>
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-blue-800 ring-1 ring-blue-100">
                        <?= (int) $fbPageCount ?> เพจในระบบ
                    </span>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-xs text-slate-500">Webhook เดียวรองรับหลาย Page — แต่ละเพจใช้ Token ของตัวเอง · ฟอร์มด้านล่างคือ <strong>เพจหลัก</strong></p>
        </div>

        <div class="space-y-8 p-6">
            <!-- Step 1 -->
            <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-5">
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-brand">ขั้นที่ 1</p>
                <h4 class="mt-1 font-bold text-slate-900">คัดลอก Webhook URL ไป Meta Developer</h4>
                <p class="mt-1 text-xs text-slate-500">Messenger → Settings → Webhooks → Callback URL · <a href="#howto-webhook" class="font-semibold text-brand hover:underline">ดูวิธีทำในคู่มือ →</a></p>
                <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                    <input type="text" readonly id="webhook-url" value="<?= htmlspecialchars($webhookUrl, ENT_QUOTES, 'UTF-8') ?>" class="ui-input min-w-0 flex-1 px-3 py-2.5 font-mono text-xs text-slate-800" />
                    <button type="button" id="btn-copy-webhook" class="inline-flex flex-shrink-0 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-800">
                        <i class="fa-regular fa-copy"></i> คัดลอก
                    </button>
                </div>
            </div>

            <!-- Step 2 form -->
            <form method="post" action="<?= htmlspecialchars(Url::to('/settings/channels/facebook'), ENT_QUOTES, 'UTF-8') ?>" class="space-y-6">
                <?= Csrf::field() ?>

                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-brand">ขั้นที่ 2</p>
                    <h4 class="mt-1 font-bold text-slate-900">Verify Token</h4>
                    <p class="mt-1 text-xs text-slate-500">ใส่ค่าเดียวกันใน Meta ตอน Verify webhook · <strong class="text-amber-700">กด「บันทึกการตั้งค่า」ก่อน</strong> แล้วค่อย Verify ใน Meta</p>
                    <?php if ($activeVerifyToken !== '') : ?>
                        <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-xs text-blue-950">
                            <p class="font-bold">ค่าที่ระบบใช้จริงตอนนี้ (ให้ Meta ใช้ตัวนี้):</p>
                            <code id="active-verify-token" class="mt-1 block break-all font-mono text-[13px]"><?= htmlspecialchars($activeVerifyToken, ENT_QUOTES, 'UTF-8') ?></code>
                            <?php if (!$verifyTokenInDb) : ?>
                                <p class="mt-1 text-amber-800">มาจาก config/local.php — ถ้ากด「สุ่มใหม่」ต้องบันทึกก่อน Meta ถึงจะเห็นค่าใหม่</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                        <input type="text" name="verify_token" id="verify-token" value="<?= htmlspecialchars($verifyToken !== '' ? $verifyToken : $suggestedVerify, ENT_QUOTES, 'UTF-8') ?>" class="ui-input min-w-0 flex-1 px-3 py-2.5 font-mono text-sm" <?= $canEdit ? '' : 'readonly' ?> />
                        <?php if ($canEdit) : ?>
                            <button type="button" id="btn-gen-verify" data-suggest="<?= htmlspecialchars($suggestedVerify, ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                สุ่มใหม่
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100 p-5">
                    <p class="text-[11px] font-extrabold uppercase tracking-wider text-brand">ขั้นที่ 3</p>
                    <h4 class="mt-1 font-bold text-slate-900">ข้อมูลจาก Meta Developer</h4>
                    <p class="mt-1 text-xs text-slate-500"><a href="#howto-tokens" class="font-semibold text-brand hover:underline">วิธีหา Token / Secret / Page ID ในคู่มือ →</a></p>
                    <div class="mt-3 rounded-lg border border-red-200 bg-red-50/90 p-4 text-xs text-red-950">
                        <p class="font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> ถ้าทดสอบแล้วขึ้น “Token ไม่ใช่ Page Access Token…”</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-4">
                            <li>Meta → <strong>Use cases</strong> → <strong>Engage with customers on Messenger</strong> → <strong>Customize</strong></li>
                            <li>เปิด <strong>API Setup</strong> (หรือ Messenger → Settings)</li>
                            <li><strong>Add or Remove Pages</strong> → เลือก Page → <strong>Generate Token</strong></li>
                            <li>คัดลอก <strong>EAA…</strong> → วางในช่อง Page Access Token ด้านล่าง (ต้องวางใหม่ ห้ามเว้นว่าง)</li>
                            <li>ใส่ <strong>App ID</strong> + <strong>App Secret</strong> จาก App settings → Basic</li>
                            <li>กด <strong>บันทึกการตั้งค่า</strong> ก่อน แล้วค่อยกดทดสอบ</li>
                        </ol>
                        <p class="mt-2 font-semibold text-amber-900">ห้ามใช้ Token จาก Graph API Explorer / Marketing API — ต้องเป็น Generate Token ของ Page ใน Messenger เท่านั้น</p>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                            App ID
                            <input type="text" name="app_id" value="<?= htmlspecialchars($appId, ENT_QUOTES, 'UTF-8') ?>" placeholder="จาก App settings → Basic" class="ui-input mt-1.5 w-full px-3 py-2.5 text-sm font-mono" <?= $canEdit ? '' : 'readonly' ?> />
                        </label>
                        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                            Page ID
                            <input type="text" name="page_id" value="<?= htmlspecialchars($pageId, ENT_QUOTES, 'UTF-8') ?>" placeholder="123456789012345" class="ui-input mt-1.5 w-full px-3 py-2.5 text-sm" <?= $canEdit ? '' : 'readonly' ?> />
                        </label>
                        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                            Page Access Token
                            <?php if ($tokenMask !== '') : ?>
                                <span class="ml-1 font-normal text-emerald-600">(บันทึกแล้ว: <?= htmlspecialchars($tokenMask, ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                            <input type="password" name="page_access_token" autocomplete="off" placeholder="<?= $hasFbToken ? 'เว้นว่าง = คงค่าเดิม' : 'EAA... จาก Messenger → API Setup' ?>" class="ui-input mt-1.5 w-full px-3 py-2.5 text-sm font-mono" <?= $canEdit ? '' : 'readonly' ?> />
                            <span class="mt-1 block font-normal text-slate-500">ต้องเป็น <strong>Page Access Token</strong> จาก Messenger → API Setup → Generate Token (ไม่ใช่ User Token)</span>
                        </label>
                        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                            App Secret
                            <?php if ($secretMask !== '') : ?>
                                <span class="ml-1 font-normal text-emerald-600">(บันทึกแล้ว: <?= htmlspecialchars($secretMask, ENT_QUOTES, 'UTF-8') ?>)</span>
                            <?php endif; ?>
                            <input type="password" name="app_secret" autocomplete="off" placeholder="<?= $hasFbSecret ? 'เว้นว่าง = คงค่าเดิม' : 'จาก App settings → Basic' ?>" class="ui-input mt-1.5 w-full px-3 py-2.5 text-sm font-mono" <?= $canEdit ? '' : 'readonly' ?> />
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <?php if ($canEdit) : ?>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand to-red-700 px-6 py-3 text-sm font-bold text-white shadow-soft hover:brightness-105">
                            <i class="fa-solid fa-floppy-disk"></i> บันทึกการตั้งค่า
                        </button>
                        <button type="button" id="btn-test-fb" data-url="<?= htmlspecialchars(Url::to('/settings/channels/facebook/test'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-800 shadow-sm hover:bg-slate-50">
                            <i class="fa-solid fa-plug"></i> ทดสอบ Page Token
                        </button>
                        <button type="button" id="btn-test-app" data-url="<?= htmlspecialchars(Url::to('/settings/channels/facebook/test-app'), ENT_QUOTES, 'UTF-8') ?>" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-bold text-amber-950 shadow-sm hover:bg-amber-100">
                            <i class="fa-solid fa-key"></i> ตรวจ App ID/Secret
                        </button>
                    <?php else : ?>
                        <p class="text-sm text-slate-500"><i class="fa-solid fa-lock mr-1"></i> ต้อง login เป็น Admin หรือ Manager จึงจะแก้ไขได้</p>
                    <?php endif; ?>
                </div>
                <div
                    id="fb-action-status"
                    class="mt-4 hidden rounded-xl border px-4 py-3 text-sm font-medium"
                    role="status"
                    aria-live="polite"
                ></div>
            </form>

            <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5">
                <h4 class="font-bold text-slate-900"><i class="fa-solid fa-satellite-dish mr-1 text-slate-500"></i> สถานะ Webhook (รับข้อความเข้า Inbox)</h4>
                <?php if ($webhookLogs === []) : ?>
                    <p class="mt-2 text-sm text-amber-800"><strong>ยังไม่เคยได้รับ webhook จาก Facebook</strong> — Meta ยังไม่ส่ง event มาที่เซิร์ฟเวอร์ หรือ URL/Subscribe ยังไม่ครบ</p>
                <?php else :
                    $latest = $webhookLogs[0];
                    $storedSigOk = !empty($latest['signature_ok']);
                    $currentSecretOk = !empty($webhookAnalysis['current_secret_ok']);
                    $missingHeader = ($webhookAnalysis['failure_reason'] ?? '') === 'missing_header';
                    ?>
                    <?php if ($storedSigOk || $currentSecretOk) : ?>
                        <p class="mt-2 text-sm text-emerald-800">
                            <i class="fa-solid fa-circle-check mr-1"></i>
                            <?php if ($storedSigOk) : ?>
                                ได้รับ webhook ล่าสุด <?= htmlspecialchars((string) ($latest['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?> — ลายเซ็น OK
                            <?php else : ?>
                                App Secret ถูกต้องแล้ว — log แดงด้านล่างเป็นรายการเก่าก่อนแก้ไข
                                (<?= htmlspecialchars((string) ($latest['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </p>
                        <?php if (!$storedSigOk && $currentSecretOk) : ?>
                            <p class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
                                ขั้นต่อไป: ไป Meta → Webhooks → ฟิลด์ <strong>messages</strong> → กด <strong>Send to server</strong> อีกครั้ง
                                แล้ว refresh หน้านี้ — ควรขึ้นลายเซ็น OK
                            </p>
                        <?php endif; ?>
                    <?php elseif ($missingHeader) : ?>
                        <p class="mt-2 text-sm text-amber-900">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            ได้รับ webhook แต่เซิร์ฟเวอร์<strong>ไม่ได้รับ header X-Hub-Signature-256</strong>
                            — ติดต่อ hosting ให้เปิด header นี้ (ModSecurity / proxy อาจ block)
                        </p>
                    <?php else : ?>
                        <p class="mt-2 text-sm text-red-800">
                            <i class="fa-solid fa-circle-xmark mr-1"></i>
                            ได้รับ webhook ล่าสุด <?= htmlspecialchars((string) ($latest['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            แต่<strong>ลายเซ็นไม่ผ่าน</strong>
                            — วาง App Secret ใหม่จาก Meta → Basic แล้วกดบันทึก + ตรวจ App ID/Secret
                        </p>
                        <?php if (!empty($latest['error_message'])) : ?>
                            <p class="mt-1 text-xs text-red-700"><?= htmlspecialchars((string) $latest['error_message'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
                <ul class="mt-3 list-disc space-y-1 pl-5 text-xs text-slate-600">
                    <li>Webhook URL ต้องเป็น <strong><?= htmlspecialchars(str_replace('http://', 'https://', $webhookUrl), ENT_QUOTES, 'UTF-8') ?></strong></li>
                    <li>กด <strong>ตรวจ App ID/Secret</strong> ต้องผ่านก่อน — ถ้าไม่ผ่าน Meta Send to server จะ fail ทุกครั้ง</li>
                    <li>Subscribe ฟิลด์ <strong>messages</strong> (กด Subscribe ไม่ใช่แค่ Test) + เลือก Page</li>
                    <li>App โหมด <strong>Development</strong>: คนทักต้องเป็น Admin/Developer/Tester ของ App — หรือสลับ App เป็น <strong>Live</strong></li>
                    <li>ทดสอบ: ส่งข้อความจาก Messenger ไปที่ Page (ไม่ใช่แชทตัวเองใน Page)</li>
                </ul>
            </div>

            <p class="text-center text-xs text-slate-500">
                ติดขัดตรงไหน? อ่าน <a href="#howto-faq" class="font-semibold text-brand hover:underline">แก้ปัญหาที่พบบ่อย</a> ในคู่มือด้านบน
            </p>

            <!-- Multi-page (เตรียมไว้) -->
            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/40 p-5" id="multi-page-section">
                <h4 class="font-bold text-slate-900"><i class="fa-solid fa-layer-group mr-1 text-blue-600"></i> หลาย Facebook Page</h4>
                <p class="mt-1 text-xs text-slate-600">Subscribe ทุก Page กับ Webhook URL เดียวกันใน Meta — ระบบเลือก Token ตาม Page ที่ลูกค้าทักมา</p>

                <?php if ($fbPages !== []) : ?>
                    <ul class="mt-4 space-y-2">
                        <?php foreach ($fbPages as $fp) :
                            $fpName = trim((string) ($fp['page_name'] ?? ''));
                            $fpId = (string) ($fp['page_id'] ?? '');
                            $isPrimary = !empty($fp['is_primary']);
                            ?>
                            <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-white px-3 py-2.5 ring-1 ring-slate-100">
                                <span class="text-sm font-semibold text-slate-800">
                                    <?= htmlspecialchars($fpName !== '' ? $fpName : 'Page ' . $fpId, ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isPrimary) : ?>
                                        <span class="ml-1 rounded bg-brand/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-brand">เพจหลัก</span>
                                    <?php endif; ?>
                                </span>
                                <span class="font-mono text-xs text-slate-500">ID <?= htmlspecialchars($fpId, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="mt-3 text-sm text-slate-500">ยังไม่มีเพจในระบบ — บันทึกเพจหลักจากฟอร์มด้านบนก่อน</p>
                <?php endif; ?>

                <?php if ($canEdit) : ?>
                    <form method="post" action="<?= htmlspecialchars(Url::to('/settings/channels/facebook/page'), ENT_QUOTES, 'UTF-8') ?>" class="mt-5 space-y-3 border-t border-blue-100 pt-5">
                        <?= Csrf::field() ?>
                        <p class="text-xs font-bold text-slate-700">เพิ่มเพจที่ 2, 3, …</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="block text-xs font-bold text-slate-600">
                                ชื่อเพจ (ไม่บังคับ)
                                <input type="text" name="extra_page_name" placeholder="100 Baht Shop สาขา 2" class="ui-input mt-1 w-full px-3 py-2 text-sm" />
                            </label>
                            <label class="block text-xs font-bold text-slate-600">
                                Page ID
                                <input type="text" name="extra_page_id" required placeholder="123456789012345" class="ui-input mt-1 w-full px-3 py-2 text-sm font-mono" />
                            </label>
                            <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                                Page Access Token
                                <input type="password" name="extra_page_access_token" required autocomplete="off" placeholder="EAA..." class="ui-input mt-1 w-full px-3 py-2 text-sm font-mono" />
                            </label>
                            <?php if ($branches !== []) : ?>
                                <label class="block text-xs font-bold text-slate-600 sm:col-span-2">
                                    สาขา (ถ้ามี)
                                    <select name="extra_branch_id" class="ui-input mt-1 w-full px-3 py-2 text-sm">
                                        <option value="">— ไม่ระบุ —</option>
                                        <?php foreach ($branches as $br) : ?>
                                            <option value="<?= (int) ($br['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($br['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-xs font-bold text-blue-800 hover:bg-blue-50">
                            <i class="fa-solid fa-plus"></i> เพิ่ม Page
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-soft ring-1 ring-slate-100">
        <h3 class="text-lg font-bold text-slate-900">ช่องทางอื่นในระบบ</h3>
        <ul class="mt-4 space-y-2">
            <?php foreach ($channels as $ch) :
                $code = (string) ($ch['code'] ?? '');
                $isFb = $code === 'facebook_messenger';
                ?>
                <li class="flex items-center justify-between rounded-xl bg-slate-50/90 px-4 py-3">
                    <span class="flex items-center gap-3">
                        <i class="<?= htmlspecialchars(ChannelIcon::faClass($code), ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($ch['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                    <span class="text-xs font-bold <?= $isFb && ($hasFbToken || $fbPageCount > 0) ? 'text-emerald-700' : 'text-slate-500' ?>">
                        <?= $isFb ? (($hasFbToken || $fbPageCount > 0) ? ($fbPageCount > 1 ? $fbPageCount . ' เพจพร้อมใช้' : 'พร้อมรับข้อความ') : 'รอตั้งค่า') : 'เร็วๆ นี้' ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<script>
window.__SETTINGS_CSRF__ = <?= json_encode(Csrf::token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
