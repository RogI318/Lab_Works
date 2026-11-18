// LAB 6: keeps the notification bell up to date without a page reload.
// public/js/bell-live.js
//
// Every 5 seconds: ask /notifications/latest for the bell "signature".
// When it changes (new alert, or alerts were read) and no dropdown is open,
// the bell is redrawn from the current page.

(function () {
    // The Audit Log page stays exactly as loaded: no background checks there
    if (window.location.pathname.startsWith('/audit-logs')) return;

    const POLL_MS = 5000;
    let lastSignature = null;
    let busy = false;

    async function check() {
        if (busy || document.hidden) return;
        busy = true;

        try {
            const res = await fetch('/notifications/latest', {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;

            const { signature } = await res.json();

            if (lastSignature === null) {
                lastSignature = signature;
                return;
            }

            // Do not redraw while the person is reading the dropdown
            if (signature !== lastSignature && !document.querySelector('[data-bell-live] .dropdown-menu.show')) {
                // X-Requested-With marks this as a background request, so it is not written to the audit log
                const page = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const html = new DOMParser().parseFromString(await page.text(), 'text/html');
                const fresh = html.querySelectorAll('[data-bell-live]');

                document.querySelectorAll('[data-bell-live]').forEach((bell, i) => {
                    if (fresh[i]) bell.innerHTML = fresh[i].innerHTML;
                });

                lastSignature = signature;
            }
        } catch (e) {
            // ignore: try again at the next tick
        } finally {
            busy = false;
        }
    }

    setInterval(check, POLL_MS);
    check();
})();
