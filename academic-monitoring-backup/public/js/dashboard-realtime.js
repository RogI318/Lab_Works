(function () {
    'use strict';

    const cfg = window.RT || {};
    const POLL_MS = 30000;               // 30 seconds
    const $ = (id) => document.getElementById(id);

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    function getJson(url) {
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then((res) => {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    // Smoothly animate a number from its current value to a new one
    function animateNumber(el, to, decimals, suffix) {
        if (!el) return;
        decimals = decimals || 0;
        suffix = suffix || '';
        const from = parseFloat(el.dataset.value ?? el.textContent) || 0;

        if (from === to) {
            el.textContent = to.toFixed(decimals) + suffix;
            el.dataset.value = to;
            return;
        }

        const start = performance.now();
        const duration = 700;

        function step(now) {
            const p = Math.min((now - start) / duration, 1);
            el.textContent = (from + (to - from) * p).toFixed(decimals) + suffix;
            if (p < 1) {
                requestAnimationFrame(step);
            } else {
                el.dataset.value = to;
            }
        }
        requestAnimationFrame(step);

        el.classList.add('flash-update');
        setTimeout(() => el.classList.remove('flash-update'), 900);
    }

    function updateStats(s) {
        animateNumber($('statGpa'), s.avg_gpa, 2);
        animateNumber($('statCourses'), s.courses, 0);
        animateNumber($('statAssignments'), s.pending_assignments, 0);
        animateNumber($('statAttendance'), s.avg_attendance, 0, '%');

        const bell = $('bellCount');
        if (bell) {
            bell.textContent = s.unread_notifications;
            bell.classList.toggle('d-none', s.unread_notifications < 1);
        }

        if ($('lastUpdated')) $('lastUpdated').textContent = s.last_updated;
        if ($('pollStatus')) $('pollStatus').textContent = '';
    }

    function renderHeavy(list) {
        const box = $('heavyBacklogList');
        if (!box) return;
        if ($('heavyCount')) $('heavyCount').textContent = list.length;

        if (list.length === 0) {
            box.innerHTML = '<div class="text-center py-2"><i class="bi bi-check-circle-fill text-success fs-1 d-block"></i>' +
                            '<p class="text-muted mb-0">No students with a heavy backlog.</p></div>';
            return;
        }

        box.innerHTML = list.map((s) => (
            '<div class="alert bg-danger text-white d-flex align-items-center mb-2" role="alert">' +
                '<i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>' +
                '<div class="flex-grow-1"><strong>' + esc(s.name) + '</strong> (' + esc(s.student_id) + ')<br>' +
                '<small>' + esc(s.pending_assignments) + ' pending · limit ' + esc(s.backlog_limit) + '</small></div>' +
                '<a href="' + esc(s.url) + '" class="btn btn-sm btn-light">View</a>' +
            '</div>'
        )).join('');
    }

    function renderRecent(list) {
        const body = $('recentTransactionsBody');
        if (!body) return;

        if (list.length === 0) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No transactions yet.</td></tr>';
            return;
        }

        body.innerHTML = list.map((t) => (
            '<tr>' +
                '<td>' + esc(t.date) + '</td>' +
                '<td><a href="' + esc(t.student_url) + '">' + esc(t.student_name) + '</a></td>' +
                '<td><span class="badge ' + esc(t.type_badge) + '">' + esc(t.type_label) + '</span></td>' +
                '<td class="fw-bold ' + (t.quantity > 0 ? 'text-danger' : 'text-success') + '">' + esc(t.quantity_label) + '</td>' +
                '<td>' + esc(t.reference_number || '—') + '</td>' +
                '<td>' + esc(t.recorded_by) + '</td>' +
            '</tr>'
        )).join('');
    }

    function refresh() {
        if (document.hidden) return;   // don't poll while the tab is in the background

        Promise.all([getJson(cfg.stats), getJson(cfg.high), getJson(cfg.recent)])
            .then(([stats, high, recent]) => {
                updateStats(stats);
                renderHeavy(high.data);
                renderRecent(recent.data);
            })
            .catch(() => {
                // Keep showing the old data and try again at the next interval
                if ($('pollStatus')) $('pollStatus').textContent = 'Connection problem. Retrying…';
            });
    }

    refresh();                          // first load
    setInterval(refresh, POLL_MS);      // then every 30 seconds
})();