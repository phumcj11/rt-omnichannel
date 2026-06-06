/**
 * Channel settings — copy webhook, test Facebook connection, smooth how-to anchors
 */
(function () {
    'use strict';

    document.querySelectorAll('#channel-settings a[href^="#howto-"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var id = link.getAttribute('href');
            if (!id || id.charAt(0) !== '#') {
                return;
            }
            var target = document.querySelector(id);
            if (!target) {
                return;
            }
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            if (history.replaceState) {
                history.replaceState(null, '', id);
            }
        });
    });

    var copyBtn = document.getElementById('btn-copy-webhook');
    var webhookInput = document.getElementById('webhook-url');
    if (copyBtn && webhookInput) {
        copyBtn.addEventListener('click', function () {
            webhookInput.select();
            webhookInput.setSelectionRange(0, 99999);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(webhookInput.value).then(function () {
                    copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> คัดลอกแล้ว';
                    setTimeout(function () {
                        copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> คัดลอก';
                    }, 2000);
                });
                return;
            }
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (err) {
                ok = false;
            }
            copyBtn.textContent = ok ? 'คัดลอกแล้ว' : 'คัดลอกไม่ได้';
        });
    }

    var genBtn = document.getElementById('btn-gen-verify');
    var verifyInput = document.getElementById('verify-token');
    if (genBtn && verifyInput) {
        genBtn.addEventListener('click', function () {
            var s = genBtn.getAttribute('data-suggest') || '';
            if (s) {
                verifyInput.value = s + Math.random().toString(16).slice(2, 6);
            }
        });
    }

    var statusBox = document.getElementById('fb-action-status');

    function showStatus(kind, message) {
        if (!statusBox) {
            window.alert(message);
            return;
        }
        statusBox.classList.remove('hidden');
        statusBox.textContent = message;
        if (kind === 'ok') {
            statusBox.className =
                'mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900';
        } else if (kind === 'err') {
            statusBox.className =
                'mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-900';
        } else {
            statusBox.className =
                'mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700';
        }
        statusBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function postJson(url, csrf) {
        var body = new URLSearchParams();
        body.set('_csrf', csrf);

        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: body.toString(),
        }).then(function (r) {
            return r.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (err) {
                    throw new Error(
                        text && text.indexOf('<') === 0
                            ? 'เซิร์ฟเวอร์ตอบ HTML (อาจ session หมดอายุ — ลอง refresh แล้ว login ใหม่)'
                            : text.slice(0, 160) || 'HTTP ' + r.status
                    );
                }
            });
        });
    }

    function bindTestButton(btn, options) {
        if (!btn) {
            return;
        }
        var label = btn.innerHTML;

        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-url');
            var csrf = window.__SETTINGS_CSRF__ || '';
            if (!url) {
                showStatus('err', 'ไม่พบ URL สำหรับทดสอบ — ลอง refresh หน้า');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = options.loadingHtml;
            showStatus('loading', options.loadingText);

            postJson(url, csrf)
                .then(function (data) {
                    btn.disabled = false;
                    btn.innerHTML = label;
                    if (data && data.ok) {
                        showStatus('ok', options.successText(data));
                        if (typeof options.onSuccess === 'function') {
                            options.onSuccess(data);
                        }
                    } else {
                        showStatus('err', options.failPrefix + ((data && data.error) || 'unknown'));
                        if (typeof options.onFail === 'function') {
                            options.onFail(data);
                        }
                    }
                })
                .catch(function (err) {
                    btn.disabled = false;
                    btn.innerHTML = label;
                    showStatus('err', 'เรียก API ไม่สำเร็จ: ' + (err && err.message ? err.message : ''));
                });
        });
    }

    var testBtn = document.getElementById('btn-test-fb');
    var testBadge = document.getElementById('fb-test-badge');
    bindTestButton(testBtn, {
        loadingHtml: '<i class="fa-solid fa-spinner fa-spin"></i> กำลังทดสอบ…',
        loadingText: 'กำลังทดสอบ Page Access Token…',
        successText: function (data) {
            return 'เชื่อมต่อสำเร็จ — Page: ' + (data.page_name || '') + ' (ID ' + (data.page_id || '') + ')';
        },
        failPrefix: 'ทดสอบ Page Token ไม่สำเร็จ: ',
        onSuccess: function () {
            if (testBadge) {
                testBadge.className =
                    'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-800 ring-1 ring-emerald-100';
                testBadge.textContent = '✓ เชื่อมต่อ OK';
            }
        },
        onFail: function () {
            if (testBadge) {
                testBadge.className =
                    'inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-red-800 ring-1 ring-red-100';
                testBadge.textContent = '✗ ทดสอบไม่ผ่าน';
            }
        },
    });

    bindTestButton(document.getElementById('btn-test-app'), {
        loadingHtml: '<i class="fa-solid fa-spinner fa-spin"></i> กำลังตรวจ…',
        loadingText: 'กำลังตรวจ App ID / App Secret กับ Meta…',
        successText: function () {
            return 'App ID/Secret ถูกต้อง — ลอง Meta Send to server อีกครั้ง (Subscribe ฟิลด์ messages ด้วย)';
        },
        failPrefix: 'App ID/Secret ไม่ผ่าน: ',
    });
})();
