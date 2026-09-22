/* ============================================================
   Admin-Settings.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {

    // ── Toggle Password Visibility ──
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // ── Update Email ──
    const updateEmailBtn = document.getElementById('updateEmailBtn');

    if (updateEmailBtn) {
        updateEmailBtn.addEventListener('click', function() {
            const currentEmail = document.getElementById('currentEmail');
            const newEmail = document.getElementById('newEmail');
            const confirmEmail = document.getElementById('confirmEmail');

            if (!newEmail.value.trim()) {
                showToast('Please enter a new email address.', 'error');
                newEmail.focus();
                return;
            }

            if (!isValidEmail(newEmail.value.trim())) {
                showToast('Please enter a valid email address.', 'error');
                newEmail.focus();
                return;
            }

            if (newEmail.value.trim() !== confirmEmail.value.trim()) {
                showToast('Email addresses do not match. Please try again.', 'error');
                confirmEmail.focus();
                return;
            }

            if (newEmail.value.trim() === currentEmail.value.trim()) {
                showToast('New email is the same as the current email.', 'error');
                newEmail.focus();
                return;
            }

            showToast('Email updated successfully! ✉️', 'success');
            currentEmail.value = newEmail.value.trim();
            document.getElementById('emailAddress').textContent = newEmail.value.trim();
            newEmail.value = '';
            confirmEmail.value = '';
        });
    }

    // ── Update Password ──
    const updatePasswordBtn = document.getElementById('updatePasswordBtn');

    if (updatePasswordBtn) {
        updatePasswordBtn.addEventListener('click', function() {
            const current = document.getElementById('currentPassword');
            const newPass = document.getElementById('newPassword');
            const confirm = document.getElementById('confirmPassword');

            if (!current.value.trim()) {
                showToast('Please enter your current password.', 'error');
                current.focus();
                return;
            }

            if (!newPass.value.trim()) {
                showToast('Please enter a new password.', 'error');
                newPass.focus();
                return;
            }

            if (newPass.value.length < 6) {
                showToast('New password must be at least 6 characters.', 'error');
                newPass.focus();
                return;
            }

            if (newPass.value !== confirm.value) {
                showToast('Passwords do not match. Please try again.', 'error');
                confirm.focus();
                return;
            }

            showToast('Password updated successfully! 🔒', 'success');
            current.value = '';
            newPass.value = '';
            confirm.value = '';

            document.querySelectorAll('.toggle-password i').forEach(icon => {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            });
            document.querySelectorAll('.password-input-wrap input').forEach(inp => {
                inp.type = 'password';
            });
        });
    }

    // ── Toggle Switch Change Handler ──
    const toggles = document.querySelectorAll('.toggle-switch input[type="checkbox"]');

    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const label = this.closest('.notification-item').querySelector('.notification-label');
            const state = this.checked ? 'enabled' : 'disabled';
            console.log(`🔔 Notification "${label?.textContent || 'Unknown'}" ${state}`);
        });
    });

    // ── Logout ──
    const logoutBtn = document.getElementById('logoutBtn');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const logoutUrl = this.href || '../auth/logout.php';
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = logoutUrl;
            }
        });
    }

    // ── Helper: Validate Email ──
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // ── Toast Notification System ──
    function showToast(message, type) {
        const existing = document.querySelector('.toast-notification');
        if (existing) {
            existing.remove();
        }

        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.textContent = message;

        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '30px',
            right: '30px',
            padding: '14px 24px',
            borderRadius: '10px',
            fontSize: '13px',
            fontWeight: '500',
            fontFamily: 'Inter, sans-serif',
            color: '#ffffff',
            zIndex: '9999',
            boxShadow: '0 6px 24px rgba(0,0,0,0.15)',
            transform: 'translateY(20px)',
            opacity: '0',
            transition: 'transform 0.35s ease, opacity 0.35s ease',
            maxWidth: '400px',
        });

        const colors = {
            success: { bg: '#16a34a' },
            error: { bg: '#dc2626' },
            info: { bg: '#3b82f6' },
        };
        const color = colors[type] || colors.info;
        toast.style.background = color.bg;

        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        });

        setTimeout(() => {
            toast.style.transform = 'translateY(20px)';
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 350);
        }, 4000);
    }

    // ── Keyboard shortcut: Escape to clear focus ──
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.activeElement?.blur();
        }
    });

    console.log('⚙️ Settings page loaded successfully');
});