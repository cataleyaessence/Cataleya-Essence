document.addEventListener('DOMContentLoaded', function() {

    // ─── PROFILE DROPDOWN ──────────────────────────────────────
    const avatar = document.getElementById('profileAvatar');
    const dropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('dropdownOverlay');

    if (avatar && dropdown && overlay) {
        function toggleDropdown(forceState) {
            const isOpen = typeof forceState === 'boolean' ? forceState : !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', isOpen);
            avatar.setAttribute('aria-expanded', isOpen);
            overlay.classList.toggle('active', isOpen);
        }

        avatar.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });

        overlay.addEventListener('click', function() {
            toggleDropdown(false);
        });

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('profileWrapper');
            if (wrapper && !wrapper.contains(e.target) && dropdown.classList.contains('open')) {
                toggleDropdown(false);
            }
        });

        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // ─── FAVORITE TABS ──────────────────────────────────────────
    const tabBtns = document.querySelectorAll('.tab-btn');
    const upcomingGrid = document.getElementById('upcomingGrid');
    const completedGrid = document.getElementById('completedGrid');

    if (tabBtns.length && upcomingGrid && completedGrid) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                tabBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const tab = this.dataset.tab;
                if (tab === 'upcoming') {
                    upcomingGrid.style.display = 'grid';
                    completedGrid.style.display = 'none';
                } else {
                    upcomingGrid.style.display = 'none';
                    completedGrid.style.display = 'grid';
                }
            });
        });
    }

    // ─── HAMBURGER MENU ─────────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('open');
            const isOpen = navLinks.classList.contains('open');
            this.setAttribute('aria-expanded', isOpen);
        });
    }

});