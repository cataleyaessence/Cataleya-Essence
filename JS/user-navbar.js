(() => {
    const initialiseUserNavbar = () => {
        const avatar = document.getElementById('profileAvatar');
        const dropdown = document.getElementById('profileDropdown');
        const overlay = document.getElementById('dropdownOverlay');
        const wrapper = document.getElementById('profileWrapper');
        const hamburger = document.getElementById('hamburger');
        const navLinks = document.getElementById('nav-links');

        const setDropdownOpen = (open) => {
            if (!avatar || !dropdown || !overlay) return;
            dropdown.classList.toggle('open', open);
            overlay.classList.toggle('active', open);
            avatar.setAttribute('aria-expanded', String(open));
        };

        if (avatar && dropdown && overlay) {
            avatar.addEventListener('click', (event) => {
                event.stopPropagation();
                setDropdownOpen(!dropdown.classList.contains('open'));
            });

            avatar.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setDropdownOpen(!dropdown.classList.contains('open'));
                }
            });

            overlay.addEventListener('click', () => setDropdownOpen(false));
            document.addEventListener('click', (event) => {
                if (wrapper && !wrapper.contains(event.target)) setDropdownOpen(false);
            });
        }

        if (hamburger && navLinks) {
            hamburger.addEventListener('click', () => {
                const menuIsOpen = navLinks.classList.toggle('open');
                hamburger.classList.toggle('active', menuIsOpen);
                hamburger.setAttribute('aria-expanded', String(menuIsOpen));
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setDropdownOpen(false);
                if (hamburger && navLinks) {
                    navLinks.classList.remove('open');
                    hamburger.classList.remove('active');
                    hamburger.setAttribute('aria-expanded', 'false');
                }
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialiseUserNavbar, { once: true });
    } else {
        initialiseUserNavbar();
    }
})();
