// LAB 6: Dashboard polling (AJAX)
// public/js/dashboard-poll.js
//
// Every 30 seconds: ask the API for fresh numbers and update the dashboard
// without a refresh. Elements marked data-live-stat="..." get the new number.

(function () {
    const POLL_MS = 30000; // every 30 seconds

    // Escape text so names cannot break the HTML
    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    // STEP 1: call one API address and return its "data" part
    async function getJson(url) {
        const res = await fetch(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return (await res.json()).data;
    }

    // STEP 2: count smoothly from the old number to the new one
    function animateTo(el, target, decimals) {
        if (!el) return;

        const from = parseFloat(el.dataset.value ?? el.textContent.replace(/[^0-9.\-]/g, '')) || 0;
        el.dataset.value = target;
        if (from === target) return;

        const start = performance.now();

        function step(now) {
            const p = Math.min((now - start) / 600, 1);
            const value = from + (target - from) * p;
            el.textContent = value.toLocaleString('en-PH', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // STEP 3: redraw the recent transactions table
    function renderRecent(items) {
        const body = document.getElementById('recent-tx-body');
        if (!body) return;

        if (!items.length) {
            body.innerHTML = "<tr><td colspan='5' class='text-center text-muted py-4'>No transactions yet.</td></tr>";
            return;
        }

        body.innerHTML = items.map(t => `<tr>
            <td>${esc(t.created_at)}</td>
            <td>${esc(t.product)}</td>
            <td><span class="badge text-bg-${esc(t.color)}">${esc(t.type_label)}</span></td>
            <td class="fw-semibold ${t.quantity > 0 ? 'text-success' : 'text-danger'}">${t.quantity > 0 ? '+' : ''}${t.quantity}</td>
            <td>${esc(t.user)}</td>
        </tr>`).join('');
    }

    // STEP 4: one refresh
    async function refresh() {
        const status = document.getElementById('poll-status');

        try {
            const [stats, recent] = await Promise.all([
                getJson('/api/dashboard/stats'),
                getJson('/api/transactions/recent'),
            ]);

            document.querySelectorAll('[data-live-stat]').forEach(el => {
                const key = el.dataset.liveStat;
                if (stats[key] !== undefined) {
                    animateTo(el, Number(stats[key]), Number(el.dataset.decimals || 0));
                }
            });

            renderRecent(recent);

            const stamp = document.getElementById('last-updated');
            if (stamp) stamp.textContent = new Date().toLocaleTimeString();
            if (status) status.textContent = '';
        } catch (e) {
            // Keep the old numbers on screen and try again at the next tick
            if (status) status.textContent = 'Could not refresh. Trying again in 30 seconds.';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const stamp = document.getElementById('last-updated');
        if (stamp) stamp.textContent = new Date().toLocaleTimeString();
    });

    setInterval(refresh, POLL_MS);
})();
