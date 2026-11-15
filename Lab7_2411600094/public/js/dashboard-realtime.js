/*
 * Lab 6 Part 7 Step 3 — AJAX polling for the dashboard (30s).
 * Module pattern: one object owns the interval, fetches the three API
 * endpoints, animates changed numbers, rebuilds the alert + movement
 * lists, and reports staleness without ever breaking the page.
 *
 * Lab 7 Part 5 Step 4 — the same tick also repaints the four procurement
 * widgets, each from its own endpoint and with its own failure handling.
 */
(function () {
    'use strict';

    const cfg = window.LAB6_REALTIME;
    if (!cfg) return;

    const INTERVAL_MS = cfg.intervalMs || 30000;

    // Lab 7 Part 5 Step 4: the procurement widgets ride this same timer. The
    // dashboard only prints the config for people who pass
    // @can('view-procurement'), so staff have nothing to poll and this stays
    // null instead of asking for JSON the gate would refuse.
    const procurement = window.LAB7_PROCUREMENT || null;

    const els = {
        products:  document.getElementById('statProducts'),
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

    /* ==================================================================
       Lab 7 Part 5 Step 4 — the four procurement widgets.

       Same contract as renderAlerts/renderMoves above: the JSON is flat and
       small, the browser rebuilds the widget from it, and every node is created
       with createElement and filled through textContent, so a supplier named
       "<script>" stays a name. Nothing here runs for staff.
       ================================================================== */

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = String(text);
        return node;
    }

    function codeLink(text, href) {
        const a = link('', href, 'text-decoration-none');
        a.appendChild(el('code', '', text));
        return a;
    }

    function money(value) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    // The card body the composer rendered — the one place each widget's
    // repaintable markup lives, marked with data-widget-body in Blade.
    function widgetBody(name) {
        const card = document.querySelector(`[data-widget="${name}"]`);
        return card ? card.querySelector('[data-widget-body]') : null;
    }

    function emptyRow(body, colspan, text) {
        const tr = el('tr');
        const td = el('td', 'text-center text-muted py-3', text);
        td.colSpan = colspan;
        tr.appendChild(td);
        body.appendChild(tr);
    }

    function renderPending(payload) {
        const body = widgetBody('pending-orders');
        if (!body) return;
        const d = payload.data;
        body.textContent = '';

        if (!d.count) {
            const box = el('div', 'text-center text-muted py-4');
            box.appendChild(el('i', 'bi bi-check2-circle d-block mb-2 fs-3'));
            box.appendChild(el('div', '', 'Nothing is waiting for a signature.'));
            body.appendChild(box);
            return;
        }

        const left = el('div');
        const count = el('div', 'display-6 fw-bold text-primary lh-1', d.count);
        count.id = 'pendingCount';
        left.appendChild(count);
        left.appendChild(el('div', 'small text-muted', 'order(s) awaiting approval'));

        const right = el('div', 'text-end');
        const value = el('div', 'h4 fw-bold text-success mb-0', money(d.value));
        value.id = 'pendingValue';
        right.appendChild(value);
        right.appendChild(el('div', 'small text-muted', 'PHP committed'));

        const wrap = el('div', 'd-flex justify-content-between align-items-center flex-wrap gap-3');
        wrap.append(left, right);
        body.appendChild(wrap);
    }

    function renderRecentOrders(payload) {
        const body = widgetBody('recent-orders');
        if (!body) return;
        body.textContent = '';

        if (!payload.data.length) {
            emptyRow(body, 4, 'No purchase orders yet.');
            return;
        }

        payload.data.forEach((o) => {
            const ref = el('td');
            ref.appendChild(codeLink(o.reference, o.url));
            ref.appendChild(document.createElement('br'));
            ref.appendChild(el('small', 'text-muted',
                o.order_date + (o.is_auto_generated ? ' - auto-generated' : '')));

            const badge = el('span', 'badge ' + o.badge_class, o.status_label);
            const status = el('td');
            status.appendChild(badge);

            const tr = el('tr');
            tr.append(
                ref,
                el('td', 'small', o.supplier),
                status,
                el('td', 'text-end', money(o.total)),
            );
            body.appendChild(tr);
        });
    }

    function renderSupplierPerformance(payload) {
        const body = widgetBody('supplier-performance');
        if (!body) return;
        body.textContent = '';

        if (!payload.data.length) {
            const li = el('li', 'text-center text-muted py-3');
            li.appendChild(el('i', 'bi bi-clock-history d-block mb-2 fs-3'));
            li.appendChild(el('div', '', 'Nothing has been received yet, so there is no delivery history to score.'));
            body.appendChild(li);
            return;
        }

        payload.data.forEach((s) => {
            const head = el('div', 'd-flex justify-content-between align-items-baseline small');
            head.appendChild(link(s.name, s.url, 'text-decoration-none fw-semibold'));
            head.appendChild(el('span', 'text-muted', `${s.percent.toFixed(1)} % (${s.on_time}/${s.received})`));

            const track = el('div', 'progress');
            track.setAttribute('role', 'progressbar');
            track.style.height = '8px';
            const bar = el('div', 'progress-bar ' + s.bar_class);
            bar.style.width = Math.min(100, s.percent) + '%';
            track.appendChild(bar);

            const li = el('li', 'mb-3');
            li.append(head, track);
            body.appendChild(li);
        });
    }

    function renderReorderAlerts(payload) {
        const body = widgetBody('reorder-alerts');
        if (!body) return;
        body.textContent = '';

        if (!payload.data.length) {
            emptyRow(body, 5, 'Every item is above its reorder point.');
            return;
        }

        payload.data.forEach((p) => {
            const name = el('td');
            name.appendChild(link(p.name, p.url, 'text-decoration-none'));
            name.appendChild(document.createElement('br'));
            const sku = el('small', 'text-muted');
            sku.appendChild(el('code', '', p.sku));
            name.appendChild(sku);

            const suggested = el('td', 'text-end');
            suggested.appendChild(p.suggested > 0
                ? el('span', 'badge bg-primary', p.suggested)
                : el('span', 'badge bg-secondary', 'covered'));

            const tr = el('tr');
            tr.append(
                name,
                el('td', 'text-end' + (p.on_hand <= 0 ? ' text-danger fw-bold' : ''), p.on_hand),
                el('td', 'text-end text-muted', p.point),
                suggested,
                el('td', 'small', p.supplier),
            );
            body.appendChild(tr);
        });
    }

    const widgetRenderers = {
        'pending-orders': renderPending,
        'recent-orders': renderRecentOrders,
        'supplier-performance': renderSupplierPerformance,
        'reorder-alerts': renderReorderAlerts,
    };

    // Last payload per widget, so a poll that changes nothing repaints nothing:
    // the cards stop flashing every thirty seconds whether or not the numbers
    // moved, and an item nobody touched stays exactly where it was.
    const lastWidgets = {};

    async function refreshWidgets() {
        const names = Object.keys(widgetRenderers);
        const settled = await Promise.allSettled(
            names.map((name) => getJson(procurement.endpoints[name]).then((payload) => ({ name, payload })))
        );

        let repainted = false;

        settled.forEach((result) => {
            if (result.status !== 'fulfilled') {
                // One widget whose endpoint is down is not a reason to stop
                // polling the other three, and not a reason to alarm the user:
                // their page still shows the last good render of that card.
                console.warn('Procurement widget polling failed:', result.reason);
                return;
            }

            const { name, payload } = result.value;
            const fingerprint = JSON.stringify(payload.data);
            if (lastWidgets[name] === fingerprint) return;
            lastWidgets[name] = fingerprint;

            widgetRenderers[name](payload);
            const body = widgetBody(name);
            if (body) {
                body.classList.remove('stat-flash');
                void body.offsetWidth;
                body.classList.add('stat-flash');
            }
            repainted = true;
        });

        if (repainted) {
            const stamp = document.getElementById('procurementUpdated');
            if (stamp) stamp.textContent = 'Updated ' + new Date().toLocaleTimeString();
        }
    }

    setInterval(() => {
        refresh();
        if (procurement) refreshWidgets();
    }, INTERVAL_MS);
})();
