// ============================================================
// REWARDS PAGE — Hamburger toggle & utilities
// (mirrors landing page behavior)
// ============================================================

(function() {
    'use strict';

    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');

    if (hamburger && navLinks) {
        // Toggle menu on hamburger click
        hamburger.addEventListener('click', function() {
            const isOpen = navLinks.classList.toggle('open');
            hamburger.classList.toggle('active');
            hamburger.setAttribute('aria-expanded', isOpen);
        });

        // Close menu when a nav link is clicked (on mobile)
        navLinks.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                navLinks.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Close menu when clicking outside (on mobile)
    document.addEventListener('click', function(e) {
        if (navLinks && navLinks.classList.contains('open')) {
            const isClickInside = navLinks.contains(e.target) || hamburger.contains(e.target);
            if (!isClickInside) {
                navLinks.classList.remove('open');
                hamburger.classList.remove('active');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        }
    });

})();