(() => {
    'use strict';

    const dashboard = document.querySelector('[data-sitepilot-dashboard]');
    if (!dashboard || typeof SitePilotAI === 'undefined') return;

    const button = dashboard.querySelector('[data-sitepilot-scan]');
    const score = dashboard.querySelector('[data-sitepilot-score]');
    const status = dashboard.querySelector('[data-sitepilot-status]');
    const issues = dashboard.querySelector('[data-sitepilot-issues]');

    button?.addEventListener('click', async () => {
        const original = button.textContent;
        button.disabled = true;
        button.textContent = SitePilotAI.scanning;

        try {
            const body = new URLSearchParams({ action: 'sitepilot_ai_scan', nonce: SitePilotAI.nonce });
            const response = await fetch(SitePilotAI.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body
            });
            const payload = await response.json();
            if (!payload.success) throw new Error(payload.data?.message || SitePilotAI.error);

            score.textContent = payload.data.score;
            status.textContent = payload.data.status;
            issues.innerHTML = payload.data.issues.length
                ? payload.data.issues.map(issue => `<article class="sitepilot-issue sitepilot-severity-${escapeHtml(issue.severity)}"><div><strong>${escapeHtml(issue.title)}</strong><p>${escapeHtml(issue.fix)}</p></div><span>+${Number(issue.impact)}</span></article>`).join('')
                : '<p class="sitepilot-empty">No priority issues were detected.</p>';
        } catch (error) {
            window.alert(error.message || SitePilotAI.error);
        } finally {
            button.disabled = false;
            button.textContent = original;
        }
    });

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = String(value);
        return node.innerHTML;
    }
})();
