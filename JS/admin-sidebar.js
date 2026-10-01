(function () {
    function initializeAdminNavigation() {
        const sidebar = document.querySelector('.sidebar');
        const navbar = document.querySelector('.navbar');

        if (!sidebar || !navbar || document.querySelector('.admin-nav-toggle')) {
            return;
        }

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'admin-nav-toggle';
        toggle.setAttribute('aria-label', 'Open navigation menu');
        toggle.setAttribute('aria-controls', 'adminSidebar');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
        sidebar.id = 'adminSidebar';

        const overlay = document.createElement('div');
        overlay.className = 'admin-nav-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(overlay);

        const closeMenu = function () {
            document.body.classList.remove('admin-navigation-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open navigation menu');
            toggle.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
        };

        const openMenu = function () {
            document.body.classList.add('admin-navigation-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Close navigation menu');
            toggle.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        };

        toggle.addEventListener('click', function () {
            if (document.body.classList.contains('admin-navigation-open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        overlay.addEventListener('click', closeMenu);

        sidebar.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });

        const smallScreen = window.matchMedia('(max-width: 768px)');
        const closeOnDesktop = function (event) {
            if (!event.matches) {
                closeMenu();
            }
        };

        if (typeof smallScreen.addEventListener === 'function') {
            smallScreen.addEventListener('change', closeOnDesktop);
        } else {
            smallScreen.addListener(closeOnDesktop);
        }

        const navbarChildren = Array.from(navbar.children);
        const navbarUser = navbar.querySelector('.navbar-user');
        navbar.insertBefore(toggle, navbarUser || navbarChildren[1] || null);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAdminNavigation);
    } else {
        initializeAdminNavigation();
    }
})();
