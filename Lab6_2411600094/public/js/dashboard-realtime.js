/*
 * Lab 6 Part 7 Step 3 — AJAX polling for the dashboard (30s).
 * Module pattern: one object owns the interval, fetches the three API
 * endpoints, animates changed numbers, rebuilds the alert + movement
 * lists, and reports staleness without ever breaking the page.
 */
(function () {
    'use strict';

    const cfg = window.LAB6_REALTIME;
    if (!cfg) return;

    const INTERVAL_MS = cfg.intervalMs || 30000;

    const els = {
        products:  document.getElementById('statProducts'),
        units:     document.getElementById('statUnits'),
        low:       document.getElementById('statLowStock'),
        out:       document.getElementById('statOutOfStock'),
        value:     document.getElementById('statValue'),
        alerts:    document.getElementById('alertsList'),
        moves:     document.getElementById('recentMovesBody'),
        updated:   document.getElementById('lastUpdated'),
        status:    document.getElementById('liveStatus'),
        badge:     document.getElementById('notifBadge'),
    };

    function setStatus(text, className) {
        if (!els.status) return;
        els.status.textContent = text;
        els.status.className = className;
    }

    async function getJson(url) {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`${url} -> HTTP ${res.status}`);
        return res.json();
    }

    // Count-up animation so refreshed numbers visibly change.
    function animateValue(el, target, decimals = 0, prefix = '') {
        if (!el) return;
        const startText = (el.textContent || '').replace(/[^\d.-]/g, '');
        const start = parseFloat(startText) || 0;
        if (start === target) return;

        const duration = 500;
        const t0 = performance.now();
        const step = (t) => {
            const p = Math.min(1, (t - t0) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            const val = start + (target - start) * eased;
            el.textContent = prefix + val.toLocaleString('en-US', {
                minimumFractionDigits: decimals, maximumFractionDigits: decimals,
            });
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
        el.classList.remove('stat-flash');
        void el.offsetWidth; // restart CSS animation
        el.classList.add('stat-flash');
    }

    function renderStats(payload) {
        const d = payload.data;
        animateValue(els.products, d.total_products);
        animateValue(els.units, d.total_units);
        animateValue(els.low, d.low_stock_count);
        animateValue(els.out, d.out_of_stock_count);
        animateValue(els.value, d.inventory_value, 2);
        if (els.updated) els.updated.textContent = new Date().toLocaleTimeString();

        // Notification bell: new low-stock alerts show up without a reload.
        if (els.badge && typeof d.unread_alerts === 'number') {
            els.badge.textContent = d.unread_alerts > 9 ? '9+' : d.unread_alerts;
            els.badge.classList.toggle('d-none', d.unread_alerts === 0);
        }
    }

    function link(name, href, cls) {
        const a = document.createElement('a');
        a.href = href;
        a.textContent = name;
        if (cls) a.className = cls;
        return a;
    }

    function renderAlerts(items) {
        if (!els.alerts) return;
        els.alerts.textContent = '';

        if (!items.length) {
            const ok = document.createElement('div');
            ok.className = 'alert alert-success mb-0';
            ok.textContent = 'No items are running low on stock.';
            els.alerts.appendChild(ok);
            return;
        }

        items.forEach((p) => {
            const row = document.createElement('div');
            row.className = 'alert mb-2 d-flex justify-content-between align-items-center '
                + (p.quantity <= 0 ? 'alert-danger' : 'alert-warning');

            const info = document.createElement('div');
            const strong = document.createElement('strong');
            strong.appendChild(link(p.name, p.url, 'text-decoration-none'));
            info.appendChild(strong);
            info.appendChild(document.createElement('br'));
            const small = document.createElement('small');
            small.textContent = `${p.category || ''} - low stock level ${p.reorder_level}`;
            info.appendChild(small);

            const badge = document.createElement('span');
            badge.className = 'badge bg-dark';
            badge.textContent = `${p.quantity} left`;

            row.appendChild(info);
            row.appendChild(badge);
            els.alerts.appendChild(row);
        });
    }

    function renderMoves(items) {
        if (!els.moves) return;
        els.moves.textContent = '';

        if (!items.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 5;
            td.className = 'text-center text-muted py-3';
            td.textContent = 'No movements recorded yet.';
            tr.appendChild(td);
            els.moves.appendChild(tr);
            return;
        }

        items.forEach((t) => {
            const tr = document.createElement('tr');

            const when = document.createElement('td');
            when.className = 'small text-nowrap';
            when.textContent = t.created_for_humans;

            const product = document.createElement('td');
            product.appendChild(link(t.product ? t.product.name : '—', t.product && t.product.url
                ? t.product.url : '#', 'text-decoration-none'));

            const type = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge ' + t.badge_class;
            badge.textContent = t.type_label;
            type.appendChild(badge);

            const qty = document.createElement('td');
            qty.className = 'text-end fw-bold ' + (t.quantity_signed >= 0 ? 'text-success' : 'text-danger');
            qty.textContent = (t.quantity_signed > 0 ? '+' : '') + t.quantity_signed;

            const by = document.createElement('td');
            by.className = 'small';
            by.textContent = (t.user && t.user.name) || 'System';

            tr.append(when, product, type, qty, by);
            els.moves.appendChild(tr);
        });
    }

    async function refresh() {
        try {
            const [stats, lowStock, recent] = await Promise.all([
                getJson(cfg.endpoints.stats),
                getJson(cfg.endpoints.lowStock),
                getJson(cfg.endpoints.recent),
            ]);
            renderStats(stats);
            renderAlerts(lowStock.data);
            renderMoves(recent.data);
            setStatus('', '');
        } catch (err) {
            // Docx: handle errors gracefully — keep showing the last good
            // render and tell the user we are stale.
            console.warn('Dashboard polling failed:', err);
            setStatus('· live updates paused, retrying…', 'text-warning');
        }
    }

    setInterval(refresh, INTERVAL_MS);
})();
