<?php
/**
 * คู่มือ How To — เชื่อมต่อ Facebook Messenger (แสดงในหน้า Channel Settings)
 * @var string $webhookUrl
 */
declare(strict_types=1);

$webhookUrl = $webhookUrl ?? '';
$isLocalhost = str_contains($webhookUrl, 'localhost') || str_contains($webhookUrl, '127.0.0.1');
?>
<div class="rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/90 via-white to-white shadow-soft ring-1 ring-blue-50" id="facebook-howto">
    <div class="border-b border-blue-100/80 px-6 py-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wider text-blue-700">How To</p>
                <h3 class="mt-1 text-lg font-bold text-slate-900">คู่มือเชื่อมต่อ Facebook Messenger</h3>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    ทำตามลำดับด้านล่าง — ใช้เวลาประมาณ 10–15 นาที ครั้งแรก ไม่ต้องแก้ไฟล์ config เอง กรอกในฟอร์มด้านล่างได้เลย
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-blue-800 ring-1 ring-blue-100">
                <i class="fa-solid fa-book-open text-blue-600"></i> อ่านก่อนตั้งค่า
            </span>
        </div>
        <nav class="mt-4 flex flex-wrap gap-2 text-xs font-bold" aria-label="ลิงก์ไปยังขั้นตอนในคู่มือ">
            <a href="#howto-prep" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">เตรียมอะไรบ้าง</a>
            <a href="#howto-meta-app" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">สร้าง App</a>
            <a href="#howto-webhook" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">Webhook</a>
            <a href="#howto-tokens" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">Token &amp; Secret</a>
            <a href="#howto-save" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">บันทึกในระบบ</a>
            <a href="#howto-test" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">ทดสอบ</a>
            <a href="#howto-faq" class="rounded-lg bg-white/80 px-2.5 py-1.5 text-slate-700 ring-1 ring-slate-200 hover:bg-white hover:text-brand">แก้ปัญหา</a>
        </nav>
    </div>

    <div class="space-y-6 p-6 text-sm text-slate-700">
        <!-- Prep -->
        <section id="howto-prep" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-800">0</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">เตรียมอะไรบ้างก่อนเริ่ม</h4>
                    <ul class="mt-3 space-y-2 text-slate-600">
                        <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-emerald-600"></i><span><strong>Facebook Page</strong> ของร้าน — บัญชีที่ login ต้องเป็น <strong>Admin</strong> ของ Page นั้น</span></li>
                        <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-emerald-600"></i><span><strong>บัญชี Meta for Developers</strong> — สมัครฟรีที่ <a href="https://developers.facebook.com/" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand hover:underline">developers.facebook.com</a></span></li>
                        <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-emerald-600"></i><span><strong>สิทธิ์ Admin หรือ Manager</strong> ในระบบ Omnichannel — จึงจะบันทึกการตั้งค่าได้</span></li>
                        <?php if ($isLocalhost) : ?>
                            <li class="flex gap-2 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-100"><i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i><span><strong>ทดสอบบนเครื่องตัวเอง (localhost):</strong> Facebook ต้องเรียก Webhook จากอินเทอร์เน็ต — ใช้ <strong>ngrok</strong> หรือ tunnel อื่นเปิดพอร์ต 80/443 แล้วนำ URL ที่ได้ไปใส่ใน Meta (ดูขั้นตอน Webhook)</span></li>
                        <?php else : ?>
                            <li class="flex gap-2"><i class="fa-solid fa-check mt-0.5 text-emerald-600"></i><span><strong>โดเมน HTTPS</strong> — Webhook ต้องเข้าถึงได้จากอินเทอร์เน็ต (URL ปัจจุบัน: <code class="rounded bg-slate-100 px-1 text-xs"><?= htmlspecialchars(parse_url($webhookUrl, PHP_URL_HOST) ?: '-', ENT_QUOTES, 'UTF-8') ?></code>)</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Meta App -->
        <section id="howto-meta-app" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-800">1</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">สร้าง App และเชื่อม Facebook Page</h4>
                    <ol class="mt-3 list-decimal space-y-3 pl-5 text-slate-600">
                        <li>เปิด <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand hover:underline">Meta for Developers → My Apps</a> แล้วกด <strong>Create App</strong></li>
                        <li>เลือกประเภท <strong>Business</strong> (หรือ Other ถ้าไม่มีตัวเลือก Business)</li>
                        <li>ตั้งชื่อ App แล้วเลือก Business Account (ถ้ามี)</li>
                        <li>ใน Dashboard ของ App → กด <strong>Add Product</strong> → เลือก <strong>Messenger</strong> → Set up</li>
                        <li>ที่ <strong>Messenger → API Setup</strong> → ส่วน <strong>Access Tokens</strong> → กด <strong>Add or Remove Pages</strong> → เลือก Page ของร้าน → <strong>Generate Token</strong> (เก็บไว้ใช้ขั้นตอนที่ 4)</li>
                    </ol>
                    <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500"><i class="fa-regular fa-lightbulb mr-1 text-amber-500"></i> <strong>ทิป:</strong> Page ID ดูได้ที่ <strong>Messenger → API Setup</strong> ใต้ชื่อ Page หรือที่ <strong>Page Settings → About</strong> บน Facebook</p>
                </div>
            </div>
        </section>

        <!-- Webhook -->
        <section id="howto-webhook" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-800">2</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">ตั้งค่า Webhook ใน Meta</h4>
                    <?php if ($isLocalhost) : ?>
                        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-slate-700">
                            <p class="font-bold text-amber-900"><i class="fa-solid fa-server mr-1"></i> ก่อนอื่น — เปิด tunnel (localhost)</p>
                            <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm">
                                <li>ติดตั้ง <a href="https://ngrok.com/download" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand hover:underline">ngrok</a> แล้วรันใน Terminal: <code class="block mt-1 rounded bg-slate-900 px-2 py-1.5 font-mono text-xs text-emerald-300">ngrok http 80</code></li>
                                <li>คัดลอก URL แบบ <strong>https://xxxx.ngrok-free.app</strong></li>
                                <li>Webhook ของคุณจะเป็น: <code class="break-all rounded bg-white px-1 text-xs ring-1 ring-amber-100">https://xxxx.ngrok-free.app/omnichannel/webhooks/facebook.php</code> (ปรับ path ตามโฟลเดอร์โปรเจกต์)</li>
                            </ol>
                        </div>
                    <?php endif; ?>
                    <ol class="mt-3 list-decimal space-y-3 pl-5 text-slate-600">
                        <li>ใน App → <strong>Messenger → Settings</strong> (หรือ <strong>Webhooks</strong>) → กด <strong>Add Callback URL</strong></li>
                        <li><strong>Callback URL:</strong> วาง URL จากฟอร์มด้านล่าง (ปุ่ม <strong>คัดลอก</strong> ขั้นที่ 1)<?= $isLocalhost ? ' — ใช้ URL จาก ngrok ไม่ใช่ localhost' : '' ?></li>
                        <li><strong>Verify Token:</strong> ใส่ค่าเดียวกับช่อง Verify Token ในฟอร์ม (ขั้นที่ 2) — ต้องตรงกันทุกตัวอักษร</li>
                        <li>กด <strong>Verify and Save</strong> — ถ้าผ่าน Meta จะแสดงว่า verified ✓</li>
                        <li>กด <strong>Manage</strong> ที่ Webhook → Subscribe ฟิลด์ <strong>messages</strong> (อย่างน้อยต้องมีตัวนี้)</li>
                        <li>เลือก <strong>Page ของร้าน</strong> แล้ว Subscribe Page นั้นกับ Webhook</li>
                    </ol>
                    <p class="mt-3 text-xs text-slate-500">ลำดับที่แนะนำ: บันทึก Verify Token ในระบบก่อน → แล้วค่อยกด Verify ใน Meta</p>
                </div>
            </div>
        </section>

        <!-- Tokens -->
        <section id="howto-tokens" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-800">3</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">หา Page Access Token, App Secret และ Page ID</h4>
                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-100">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 font-bold">ข้อมูล</th>
                                    <th class="px-3 py-2 font-bold">หาได้ที่ไหน</th>
                                    <th class="px-3 py-2 font-bold">ใช้ทำอะไร</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold text-slate-800">Page Access Token</td>
                                    <td class="px-3 py-2.5 text-slate-600">Messenger → <strong>API Setup</strong> → Generate Token ของ Page</td>
                                    <td class="px-3 py-2.5 text-slate-600">รับ/ส่งข้อความ Messenger</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold text-slate-800">App Secret</td>
                                    <td class="px-3 py-2.5 text-slate-600">App → <strong>Settings → Basic</strong> → App Secret (กด Show)</td>
                                    <td class="px-3 py-2.5 text-slate-600">ตรวจความถูกต้องของ webhook (ควรใส่ใน production)</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold text-slate-800">Page ID</td>
                                    <td class="px-3 py-2.5 text-slate-600">Messenger → API Setup หรือ Page → About</td>
                                    <td class="px-3 py-2.5 text-slate-600">อ้างอิง Page ในระบบ</td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-2.5 font-semibold text-slate-800">Verify Token</td>
                                    <td class="px-3 py-2.5 text-slate-600">ตั้งเองในฟอร์มด้านล่าง (ปุ่มสุ่มใหม่ได้)</td>
                                    <td class="px-3 py-2.5 text-slate-600">ยืนยัน webhook ครั้งแรกกับ Meta</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800 ring-1 ring-red-100"><i class="fa-solid fa-shield-halved mr-1"></i> <strong>อย่าแชร์ Token / Secret</strong> ในแชทหรือ commit ลง Git — เก็บเฉพาะในระบบนี้</p>
                </div>
            </div>
        </section>

        <!-- Save in system -->
        <section id="howto-save" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-extrabold text-blue-800">4</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">กรอกและบันทึกในฟอร์มด้านล่าง</h4>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-slate-600">
                        <li><strong>ขั้นที่ 1</strong> — คัดลอก Webhook URL ไป Meta (ตามขั้นตอนที่ 2)</li>
                        <li><strong>ขั้นที่ 2</strong> — ใส่ Verify Token (ให้ตรงกับ Meta)</li>
                        <li><strong>ขั้นที่ 3</strong> — ใส่ Page ID, Page Access Token, App Secret</li>
                        <li>กด <strong>บันทึกการตั้งค่า</strong> — ระบบเก็บในฐานข้อมูล ใช้ได้ทันที ไม่ต้อง restart</li>
                    </ol>
                    <p class="mt-3 text-xs text-slate-500">ถ้าเคยบันทึก Token แล้ว ช่อง password ว่างไว้ได้ = คงค่าเดิม</p>
                </div>
            </div>
        </section>

        <!-- Test -->
        <section id="howto-test" class="scroll-mt-24 rounded-xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex gap-4">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-extrabold text-emerald-800">5</span>
                <div class="min-w-0 flex-1">
                    <h4 class="font-bold text-slate-900">ทดสอบว่าใช้งานได้</h4>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-slate-600">
                        <li>กดปุ่ม <strong>ทดสอบการเชื่อมต่อ</strong> — ควรเห็นชื่อ Page และ ✓ เชื่อมต่อ OK</li>
                        <li>จากมือถือหรือ Messenger ส่งข้อความทดสอบไปที่ <strong>Facebook Page</strong> ของร้าน</li>
                        <li>เปิดเมนู <strong>Unified Inbox</strong> → กรองช่องทาง Facebook → ควรเห็นแชทใหม่</li>
                        <li>ตอบจาก Inbox — ข้อความควรไปถึงลูกค้าใน Messenger</li>
                    </ol>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="howto-faq" class="scroll-mt-24 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 p-5">
            <h4 class="font-bold text-slate-900"><i class="fa-solid fa-circle-question mr-1 text-slate-500"></i> แก้ปัญหาที่พบบ่อย</h4>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="font-bold text-slate-800">ทดสอบแล้วขึ้น “Token ไม่ใช่ Page Access Token ของ Messenger”</dt>
                    <dd class="mt-1 text-slate-600">Token เก่ายังอยู่ในระบบ (ช่องว่าง = คงค่าเดิม) — สร้างใหม่ที่ Messenger → API Setup → Generate Token แล้ว<strong>วางทับ</strong>ในช่อง Page Access Token กดบันทึกก่อนทดสอบ</dd>
                </div>
                <div>
                    <dd class="mt-1 text-slate-600">ตรวจว่า Verify Token ใน Meta ตรงกับในระบบทุกตัวอักษร, URL เข้าถึงได้จากอินเทอร์เน็ต<?= $isLocalhost ? ' (ใช้ ngrok ไม่ใช่ localhost)' : '' ?>, และบันทึก Token ในระบบก่อนกด Verify</dd>
                </div>
                <div>
                    <dt class="font-bold text-slate-800">ทดสอบการเชื่อมต่อไม่ผ่าน / Token ไม่ถูกต้อง</dt>
                    <dd class="mt-1 text-slate-600">สร้าง Page Access Token ใหม่ที่ Messenger → API Setup, วางในฟอร์มแล้วบันทึก — Token หมดอายุหรือสิทธิ์ Page เปลี่ยนได้</dd>
                </div>
                <div>
                    <dt class="font-bold text-slate-800">ส่งข้อความจาก Page แล้วไม่เข้า Inbox</dt>
                    <dd class="mt-1 text-slate-600">ตรวจว่า Subscribe ฟิลด์ <strong>messages</strong> แล้ว, Page ถูก Subscribe กับ Webhook, และ App อยู่ในโหมด Live (หรือมี tester ใน Development)</dd>
                </div>
                <div>
                    <dt class="font-bold text-slate-800">ตอบจาก Inbox แล้วลูกค้าไม่ได้รับ</dt>
                    <dd class="mt-1 text-slate-600">ต้องมี Page Access Token ที่ถูกต้อง และลูกค้าต้องทักมาภายใน 24 ชม. (นโยบาย Messenger) — ลองทดสอบการเชื่อมต่ออีกครั้ง</dd>
                </div>
            </dl>
        </section>
    </div>
</div>
