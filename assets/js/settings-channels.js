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
            var ok = false;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(webhookInput.value).then(function () {
                    copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> คัดลอกแล้ว';
                    setTimeout(function () {
                        copyBtn.innerHTML = '<i class="fa-regular fa-copy"></i> คัดลอก';
                    }, 2000);
                });
                return;
            }
            try {
                ok = document.execCommand('copy');
            } catch (e) {
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

    var testBtn = document.getElementById('btn-test-fb');
    var testResult = document.getElementById('fb-test-result');
    var testBadge = document.getElementById('fb-test-badge');
    if (testBtn && testResult) {
        testBtn.addEventListener('click', function () {
            var url = testBtn.getAttribute('data-url');
            var csrf = window.__SETTINGS_CSRF__ || '';
            if (!url) {
                return;
            }
            testBtn.disabled = true;
            testResult.classList.remove('hidden');
            testResult.className = 'text-sm font-medium text-slate-600';
            testResult.textContent = 'กำลังทดสอบ…';

            var body = new URLSearchParams();
            body.set('_csrf', csrf);

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: body.toString(),
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    testBtn.disabled = false;
                    if (data && data.ok) {
                        testResult.className = 'text-sm font-medium text-emerald-700';
                        testResult.textContent =
                            'เชื่อมต่อสำเร็จ — Page: ' + (data.page_name || '') + ' (ID ' + (data.page_id || '') + ')';
                        if (testBadge) {
                            testBadge.className =
                                'inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-800 ring-1 ring-emerald-100';
                            testBadge.textContent = '✓ เชื่อมต่อ OK';
                        }
                    } else {
                        testResult.className = 'text-sm font-medium text-red-700';
                        testResult.textContent = 'ไม่สำเร็จ: ' + ((data && data.error) || 'unknown');
                        if (testBadge) {
                            testBadge.className =
                                'inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-1 text-red-800 ring-1 ring-red-100';
                            testBadge.textContent = '✗ ทดสอบไม่ผ่าน';
                        }
                    }
                })
                .catch(function () {
                    testBtn.disabled = false;
                    testResult.className = 'text-sm font-medium text-red-700';
                    testResult.textContent = 'เรียก API ไม่สำเร็จ';
                });
        });
    }
})();
