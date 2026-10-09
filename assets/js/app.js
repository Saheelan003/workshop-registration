(function () {
    // Confirm dialogs + prevent double submits
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
            return;
        }
        var btn = form.querySelector('button[type="submit"]:not([disabled])');
        if (btn && !event.defaultPrevented) {
            setTimeout(function () {
                btn.disabled = true;
                btn.classList.add('is-loading');
            }, 0);
        }
    });

    // Back/forward cache: restore buttons
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.querySelectorAll('button.is-loading').forEach(function (b) {
                b.disabled = false;
                b.classList.remove('is-loading');
            });
        }
    });

    document.addEventListener('click', function (event) {
        // Dismiss notices
        var close = event.target.closest('[data-dismiss]');
        if (close) {
            var n = close.closest('.notice');
            if (n) n.remove();
            return;
        }
        // Show / hide password
        var toggle = event.target.closest('[data-toggle-password]');
        if (toggle) {
            var input = toggle.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.textContent = show ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        }
    });

    // Live table search
    document.querySelectorAll('[data-table-filter]').forEach(function (input) {
        var table = document.getElementById(input.dataset.tableFilter);
        if (!table) return;
        var empty = document.createElement('tr');
        empty.className = 'search-empty';
        empty.hidden = true;
        empty.innerHTML = '<td colspan="' + table.querySelectorAll('thead th').length + '" class="empty-cell"><strong>No matches</strong><br><span class="muted">Try a different search term.</span></td>';
        table.tBodies[0].appendChild(empty);
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var visible = 0;
            table.tBodies[0].querySelectorAll('tr:not(.search-empty):not(.static-row)').forEach(function (row) {
                var match = row.textContent.toLowerCase().indexOf(q) !== -1;
                row.hidden = !match;
                if (match) visible++;
            });
            empty.hidden = visible !== 0 || q === '';
        });
    });
})();
