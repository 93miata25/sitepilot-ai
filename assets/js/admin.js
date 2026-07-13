(function () {
    'use strict';

    function getPath(object, path) {
        return path.split('.').reduce(function (value, key) {
            return value && Object.prototype.hasOwnProperty.call(value, key) ? value[key] : '';
        }, object);
    }

    function showStatus(message, isError) {
        var status = document.querySelector('[data-sitepilot-action-status]') || document.getElementById('sitepilot-scan-status');
        if (!status) return;
        status.hidden = false;
        status.textContent = message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function renderIssues(issues) {
        var container = document.getElementById('sitepilot-recommendations');
        var count = document.querySelector('[data-sitepilot-issue-count]');
        if (!container) return;
        if (count) count.textContent = issues ? issues.length : 0;
        if (!issues || !issues.length) {
            container.innerHTML = '<div class="sitepilot-empty-state">No important issues were found.</div>';
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'sitepilot-issues';
        issues.forEach(function (issue) {
            var article = document.createElement('article');
            article.className = 'sitepilot-issue sitepilot-issue--' + issue.severity;
            article.innerHTML = '<div class="sitepilot-issue-top"><span class="sitepilot-severity"></span><span class="sitepilot-impact"></span></div><h3></h3><p></p><div class="sitepilot-fix"><strong>Recommended fix: </strong><span></span></div>';
            article.querySelector('.sitepilot-severity').textContent = String(issue.severity).toUpperCase();
            article.querySelector('.sitepilot-impact').textContent = '+' + issue.impact;
            article.querySelector('h3').textContent = issue.title;
            article.querySelector('p').textContent = issue.description || issue.reason;
            article.querySelector('.sitepilot-fix span').textContent = issue.fix;
            if (issue.fixable && issue.fix_action) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'button button-primary sitepilot-fix-button';
                button.dataset.sitepilotFix = issue.fix_action;
                button.textContent = 'Fix Now';
                article.appendChild(button);
            }
            wrap.appendChild(article);
        });
        container.replaceChildren(wrap);
    }

    function updateDashboard(data) {
        document.querySelectorAll('[data-sitepilot-field]').forEach(function (node) {
            node.textContent = getPath(data, node.dataset.sitepilotField);
        });
        document.querySelectorAll('[data-sitepilot-boolean]').forEach(function (node) {
            node.textContent = getPath(data, node.dataset.sitepilotBoolean) ? SitePilotAI.enabled : SitePilotAI.disabled;
        });
        document.querySelectorAll('[data-sitepilot-progress]').forEach(function (node) {
            node.style.width = getPath(data, node.dataset.sitepilotProgress) + '%';
        });
        var ring = document.querySelector('.sitepilot-score-ring');
        if (ring) ring.style.setProperty('--sitepilot-score', data.health.score);
        renderIssues(data.health.issues);
    }

    function runScan() {
        var dashboard = document.getElementById('sitepilot-dashboard');
        var button = document.getElementById('sitepilot-run-scan');
        if (dashboard) dashboard.classList.add('sitepilot-is-scanning');
        if (button) button.disabled = true;
        showStatus(SitePilotAI.scanning, false);

        return fetch(SitePilotAI.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: new URLSearchParams({action: 'sitepilot_ai_run_scan', nonce: SitePilotAI.scanNonce}).toString()
        }).then(function (response) { return response.json(); }).then(function (response) {
            if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : SitePilotAI.error);
            updateDashboard(response.data);
            showStatus(SitePilotAI.completed, false);
            return response.data;
        }).finally(function () {
            if (dashboard) dashboard.classList.remove('sitepilot-is-scanning');
            if (button) button.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var scanButton = document.getElementById('sitepilot-run-scan');
        if (scanButton) {
            scanButton.addEventListener('click', function () {
                runScan().catch(function (error) { showStatus(error.message || SitePilotAI.error, true); });
            });
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-sitepilot-fix]');
            if (!button) return;
            if (!window.confirm(SitePilotAI.confirmFix)) return;

            button.disabled = true;
            showStatus(SitePilotAI.fixing, false);
            fetch(SitePilotAI.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({action: 'sitepilot_ai_fix_issue', nonce: SitePilotAI.fixNonce, fix_action: button.dataset.sitepilotFix}).toString()
            }).then(function (response) { return response.json(); }).then(function (response) {
                if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : SitePilotAI.error);
                showStatus(response.data.message, false);
                if (document.getElementById('sitepilot-dashboard')) {
                    updateDashboard(response.data.scan);
                } else {
                    window.setTimeout(function () { window.location.reload(); }, 650);
                }
            }).catch(function (error) {
                showStatus(error.message || SitePilotAI.error, true);
                button.disabled = false;
            });
        });
    });
}());
