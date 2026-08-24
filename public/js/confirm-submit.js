// Confirmation before a destructive form submits.
//
// These forms carried onsubmit="return confirm('...')". An inline event handler needs
// 'unsafe-inline' in script-src, which this application does not grant, so the browser
// dropped the handler and the delete went through with no prompt at all. Same prompt,
// from a data attribute the policy has no objection to.
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || typeof form.getAttribute !== 'function') {
            return;
        }

        var message = form.getAttribute('data-confirm');
        if (message === null || message === '') {
            return;
        }

        // Capturing phase is not used on purpose: a form with its own submit listener
        // should still get to run first. If the user declines, nothing submits.
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
