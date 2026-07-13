(function(){
    'use strict';

    function getPath(object, path) {
        return path.split('.').reduce(function(value, key){
            return value && Object.prototype.hasOwnProperty.call(value, key) ? value[key] : '';
        }, object);
    }

    function renderIssues(issues) {
        var container = document.getElementById('sitepilot-recommendations');
        if (!container) return;

        if (!issues || !issues.length) {
            container.innerHTML = '<div class="sitepilot-empty-state">No important issues were found.</div>';
            return;
        }

        var list = document.createElement('ul');
        list.className = 'sitepilot-issues';
        issues.forEach(function(issue){
            var item = document.createElement('li');
            item.className = 'sitepilot-issue sitepilot-issue--' + issue.severity;
            item.textContent = issue.message;
            list.appendChild(item);
        });
        container.replaceChildren(list);
    }

    document.addEventListener('DOMContentLoaded', function(){
        var button = document.getElementById('sitepilot-run-scan');
        var dashboard = document.getElementById('sitepilot-dashboard');
        var status = document.getElementById('sitepilot-scan-status');
        if (!button || !dashboard || !status || typeof SitePilotAI === 'undefined') return;

        button.addEventListener('click', function(){
            dashboard.classList.add('sitepilot-is-scanning');
            status.hidden = false;
            status.classList.remove('is-error');
            status.textContent = SitePilotAI.scanning;

            var body = new URLSearchParams({
                action: 'sitepilot_ai_run_scan',
                nonce: SitePilotAI.nonce
            });

            fetch(SitePilotAI.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            })
            .then(function(response){ return response.json(); })
            .then(function(response){
                if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : SitePilotAI.error);
                var data = response.data;
                document.querySelectorAll('[data-sitepilot-field]').forEach(function(node){
                    node.textContent = getPath(data, node.dataset.sitepilotField);
                });
                document.querySelectorAll('[data-sitepilot-boolean]').forEach(function(node){
                    node.textContent = getPath(data, node.dataset.sitepilotBoolean) ? 'Enabled' : 'Disabled';
                });
                var ring = document.querySelector('.sitepilot-score-ring');
                if (ring) ring.style.setProperty('--sitepilot-score', data.health.score);
                renderIssues(data.health.issues);
                status.textContent = 'Website scan completed successfully.';
            })
            .catch(function(error){
                status.classList.add('is-error');
                status.textContent = error.message || SitePilotAI.error;
            })
            .finally(function(){
                dashboard.classList.remove('sitepilot-is-scanning');
            });
        });
    });
})();
