// ─── SIDEBAR TOGGLE (hamburger) ───
const hamburger = document.getElementById('hamburgerBtn');
const sidebar = document.querySelector('.sidebar');

hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});

// Close sidebar when clicking outside on mobile (optional)
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnHamburger = hamburger.contains(e.target);
        if (!isClickInsideSidebar && !isClickOnHamburger && sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
        }
    }
});

// ─── HANDLE REPLY & MARK AS READ BUTTONS (demo) ───
document.querySelectorAll('.btn-reply').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Reply functionality – you can wire this to a modal or form.');
    });
});

document.querySelectorAll('.btn-mark').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const item = btn.closest('.feedback-item');
        if (item) {
            item.style.opacity = '0.6';
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Read';
            btn.disabled = true;
            btn.style.cursor = 'default';
        }
    });
});

// ─── LOGOUT BUTTON (demo) ───
document.querySelector('.logout-btn')?.addEventListener('click', () => {
    if (confirm('Log out of the dashboard?')) {
        alert('You have been logged out (demo).');
    }
});