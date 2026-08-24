// Admin chrome behaviour.
//
// This lived in an inline <script> in the admin layout, with the toggle wired through
// an onclick attribute. Neither could ever run: the Content-Security-Policy is
// script-src 'self' with no 'unsafe-inline', so the browser refused both and the user
// menu did not open. Same behaviour, in a file the policy allows.
(function () {
    'use strict';

    function toggleUserMenu() {
        var menu = document.getElementById('user-menu');
        var chevron = document.getElementById('user-chevron');
        if (menu) {
            menu.classList.toggle('show');
        }
        if (chevron) {
            chevron.classList.toggle('rotate');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-user-toggle]');
        if (toggle) {
            toggle.addEventListener('click', toggleUserMenu);
        }
    });
})();
