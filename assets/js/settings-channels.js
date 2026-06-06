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

    var refreshBtn = document.getElementById('btn-refresh-webhook');
    var logTbody = document.getElementById('webhook-log-tbody');
    var refreshedAt = document.getElementById('webhook-refreshed-at');
    var refreshIcon = document.getElementById('webhook-refresh-icon');
    var fileLogBox = document.getElementById('webhook-file-log-box');
    var inboxConv = document.getElementById('webhook-inbox-conv');
    var inboxMsgs = document.getElementById('webhook-inbox-msgs');

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderLogRows(logs) {
        if (!logTbody) {
            return;
        }
        if (!logs || !logs.length) {
            logTbody.innerHTML =
                '<tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">ยังไม่มี log — ลอง Meta Send to server แล้วกดรีเฟรช</td></tr>';
            return;
        }
        logTbody.innerHTML = logs
            .map(function (row) {
                var sig = row.signature_ok;
                var sigLabel = sig === null || sig === '' || sig === undefined ? '—' : sig === 1 || sig === '1' ? 'OK' : 'fail';
                var sigClass =
                    sigLabel === 'OK' ? 'text-emerald-700' : sigLabel === '—' ? 'text-slate-500' : 'text-red-700';
                var preview = (row.body_preview || '').trim();
                if (!preview) {
                    preview = '(ไม่มี body — error log)';
                }
                return (
                    '<tr class="hover:bg-slate-50/80">' +
                    '<td class="whitespace-nowrap px-3 py-2 font-mono text-slate-700">' +
                    escHtml(row.created_at || '') +
                    '</td>' +
                    '<td class="px-3 py-2 font-bold ' +
                    sigClass +
                    '">' +
                    escHtml(sigLabel) +
                    '</td>' +
                    '<td class="max-w-xs px-3 py-2 text-slate-700">' +
                    escHtml(row.error_message || '—') +
                    '</td>' +
                    '<td class="max-w-md truncate px-3 py-2 font-mono text-[11px] text-slate-500" title="' +
                    escHtml(preview) +
                    '">' +
                    escHtml(preview) +
                    '</td></tr>'
                );
            })
            .join('');
    }

    if (refreshBtn && logTbody) {
        refreshBtn.addEventListener('click', function () {
            var url = refreshBtn.getAttribute('data-url');
            if (!url) {
                location.reload();
                return;
            }
            refreshBtn.disabled = true;
            if (refreshIcon) {
                refreshIcon.classList.add('fa-spin');
            }
            fetch(url, { credentials: 'same-origin' })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data || !data.ok) {
                        throw new Error((data && data.error) || 'load failed');
                    }
                    renderLogRows(data.logs || []);
                    if (refreshedAt) {
                        refreshedAt.textContent = 'อัปเดต ' + (data.refreshed_at || '');
                    }
                    if (fileLogBox) {
                        var lines = data.file_log || [];
                        if (!lines.length) {
                            fileLogBox.outerHTML =
                                '<p id="webhook-file-log-box" class="mt-2 font-mono text-xs text-amber-300">ยังไม่มี — กด Meta Send to server แล้วกด「รีเฟรช log」</p>';
                        } else {
                            fileLogBox.textContent = lines.join('\n');
                        }
                    }
                    if (inboxConv && data.inbox) {
                        inboxConv.textContent = String(data.inbox.conversations || 0);
                    }
                    if (inboxMsgs && data.inbox) {
                        inboxMsgs.textContent = String(data.inbox.inbound_messages || 0);
                    }
                })
                .catch(function () {
                    location.reload();
                })
                .finally(function () {
                    refreshBtn.disabled = false;
                    if (refreshIcon) {
                        refreshIcon.classList.remove('fa-spin');
                    }
                });
        });
    }
})();
