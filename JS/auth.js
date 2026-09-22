document.addEventListener('DOMContentLoaded', function() {

    // ─── PASSWORD TOGGLE ─────────────────────────────────────
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // ─── AJAX FORM HANDLING ───────────────────────────────────
    const forms = document.querySelectorAll('form[data-ajax]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const action = this.getAttribute('data-action');
            const formData = new FormData(this);
            const messageEl = document.getElementById('authMessage');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            fetch(`../auth/${action}.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageEl.className = 'auth-message success';
                    messageEl.textContent = data.message;
                    messageEl.style.display = 'block';
                    
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    messageEl.className = 'auth-message error';
                    messageEl.textContent = data.error;
                    messageEl.style.display = 'block';
                }
            })
            .catch(error => {
                messageEl.className = 'auth-message error';
                messageEl.textContent = 'An error occurred. Please try again.';
                messageEl.style.display = 'block';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    });
});
