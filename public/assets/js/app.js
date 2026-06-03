// Global JavaScript utilities

(function () {
    'use strict';

    // Simple utility namespace
    window.App = window.App || {};

    // Initialize any global behaviors
    document.addEventListener('DOMContentLoaded', function () {
        // Auto-hide flash messages after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function () {
                    alert.remove();
                }, 500);
            }, 5000);
        });

        // Sidebar Toggling and Persistence
        const toggleBtn = document.getElementById('sidebar-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
            });
        }
    });
})();
