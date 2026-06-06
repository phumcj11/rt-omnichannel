/**
 * Inbox / Chat — premium stagger + canned → textarea
 */
(function () {
    'use strict';

    var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (typeof gsap !== 'undefined' && !prefersReduced) {
        gsap.from('#inbox-root .inbox-hero, #inbox-root .inbox-filter-panel', {
            opacity: 0,
            y: 10,
            duration: 0.42,
            stagger: 0.06,
            ease: 'power3.out',
        });

        gsap.from('.inbox-row', {
            opacity: 0,
            y: 12,
            duration: 0.38,
            stagger: 0.045,
            ease: 'power3.out',
            delay: 0.05,
        });

        gsap.from('#inbox-flash', {
            opacity: 0,
            y: -8,
            duration: 0.35,
            ease: 'power2.out',
        });

        gsap.from('#chat-root .chat-hero', {
            opacity: 0,
            y: 8,
            duration: 0.4,
            ease: 'power3.out',
        });

        gsap.from('#chat-root .message-bubble', {
            opacity: 0,
            y: 14,
            duration: 0.32,
            stagger: 0.035,
            ease: 'power3.out',
            delay: 0.06,
        });

        gsap.from('#chat-root .composer-dock', {
            opacity: 0,
            y: 16,
            duration: 0.45,
            ease: 'power3.out',
            delay: 0.1,
        });

        gsap.from('#chat-root aside > *', {
            opacity: 0,
            x: 10,
            duration: 0.38,
            stagger: 0.06,
            ease: 'power3.out',
            delay: 0.08,
        });
    }

    var canned = window.__CANNED_BY_ID__ || {};
    var pick = document.getElementById('canned-pick');
    var scrollEl = document.getElementById('msg-scroll');
    if (scrollEl) {
        scrollEl.scrollTop = scrollEl.scrollHeight;
    }

    if (pick) {
        pick.addEventListener('change', function () {
            var id = String(this.value || '');
            var body = canned[id];
            if (!body) {
                return;
            }
            var form = pick.closest('form');
            if (!form) {
                return;
            }
            var ta = form.querySelector('textarea[name="body"]');
            if (ta) {
                ta.value = body;
                ta.focus();
            }
        });
    }

    var erpUrl = window.__ERP_SEARCH_URL__;
    var erpInput = document.getElementById('erp-search-q');
    var erpBox = document.getElementById('erp-search-results');
    var erpHint = document.getElementById('erp-search-hint');
    var replyTa = document.getElementById('reply-body');
    var erpTimer = null;

    function formatErpLine(it) {
        var name = it.name_th || '';
        var sku = it.erp_sku || '';
        var line = '[' + sku + '] ' + name;
        if (it.price && typeof it.price.price === 'number') {
            line += ' — ราคา ' + it.price.price + ' ' + (it.price.currency || 'THB');
        }
        if (it.stock) {
            var avail = it.stock.on_hand - it.stock.reserved;
            line += ' — คงเหลือประมาณ ' + avail + ' ' + (it.unit || 'pcs');
        }
        return line;
    }

    function renderErpItems(items) {
        if (!erpBox) {
            return;
        }
        erpBox.innerHTML = '';
        if (!items || !items.length) {
            return;
        }
        items.forEach(function (it) {
            var row = document.createElement('div');
            row.className =
                'flex flex-col gap-2 rounded-xl border border-slate-100 bg-white px-3 py-2.5 shadow-sm sm:flex-row sm:items-center sm:justify-between';
            var left = document.createElement('div');
            left.className = 'min-w-0 flex-1';
            left.innerHTML =
                '<p class="truncate font-bold text-slate-900">' +
                (it.name_th || '') +
                '</p><p class="text-[11px] font-semibold text-slate-500">SKU ' +
                (it.erp_sku || '') +
                '</p>';
            var meta = document.createElement('p');
            meta.className = 'text-[11px] text-slate-600';
            var parts = [];
            if (it.price) {
                parts.push('ราคา ' + it.price.price + ' ' + it.price.currency);
            }
            if (it.stock) {
                parts.push('คงเหลือ ' + (it.stock.on_hand - it.stock.reserved));
            }
            meta.textContent = parts.join(' · ');
            left.appendChild(meta);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'inline-flex flex-shrink-0 items-center justify-center gap-1 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-slate-800';
            btn.innerHTML = '<i class="fa-solid fa-plus text-[10px]"></i> ใส่ในข้อความ';
            btn.addEventListener('click', function () {
                if (!replyTa) {
                    return;
                }
                var block = formatErpLine(it);
                if (replyTa.value.trim()) {
                    replyTa.value = replyTa.value.replace(/\s*$/, '') + '\n\n' + block;
                } else {
                    replyTa.value = block;
                }
                replyTa.focus();
            });

            row.appendChild(left);
            row.appendChild(btn);
            erpBox.appendChild(row);
        });
    }

    function runErpSearch(q) {
        if (!erpUrl || !erpBox) {
            return;
        }
        if (!q || q.length < 1) {
            erpBox.innerHTML = '';
            if (erpHint) {
                erpHint.classList.add('hidden');
                erpHint.textContent = '';
            }
            return;
        }
        if (erpHint) {
            erpHint.classList.remove('hidden');
            erpHint.textContent = 'กำลังค้นหา…';
        }
        var url = erpUrl + (erpUrl.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data || !data.ok) {
                    renderErpItems([]);
                    if (erpHint) {
                        erpHint.classList.remove('hidden');
                        erpHint.textContent = 'โหลดรายการไม่สำเร็จ';
                    }
                    return;
                }
                var list = data.items || [];
                renderErpItems(list);
                if (erpHint) {
                    if (list.length === 0) {
                        erpHint.classList.remove('hidden');
                        erpHint.textContent = 'ไม่พบสินค้า — ลองคำอื่น';
                    } else {
                        erpHint.classList.add('hidden');
                        erpHint.textContent = '';
                    }
                }
            })
            .catch(function () {
                if (erpHint) {
                    erpHint.classList.remove('hidden');
                    erpHint.textContent = 'โหลดไม่สำเร็จ — ลองอีกครั้ง';
                }
                renderErpItems([]);
            });
    }

    if (erpInput && erpUrl) {
        erpInput.addEventListener('input', function () {
            var v = String(this.value || '').trim();
            clearTimeout(erpTimer);
            erpTimer = setTimeout(function () {
                runErpSearch(v);
            }, 320);
        });
    }
})();
