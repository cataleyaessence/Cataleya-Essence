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

    // ─── DOM REFS ──────────────────────────────────────────
    const forgotModal = document.getElementById('forgotModal');
    const verifyModal = document.getElementById('verifyModal');
    const resetModal = document.getElementById('resetModal');
    const openLink = document.getElementById('forgotPasswordLink');
    const closeForgotBtn = document.getElementById('modalClose');
    const closeVerifyBtn = document.getElementById('verifyModalClose');
    const closeResetBtn = document.getElementById('resetModalClose');
    const backLink = document.getElementById('modalBackLink');
    const resetBackLink = document.getElementById('resetBackLink');
    const changeEmailLink = document.getElementById('changeEmailLink');
    const resendCodeLink = document.getElementById('resendCodeLink');
    const authForm = document.getElementById('authForm');
    const testimonial = document.querySelector('.auth-testimonial');
    const signinForm = document.getElementById('signinForm');
    const signinBtn = document.getElementById('signinBtn');
    const messageEl = document.getElementById('authMessage');
    const forgotForm = document.getElementById('forgotForm');
    const forgotBtn = document.getElementById('forgotBtn');
    const modalMessage = document.getElementById('modalMessage');
    const verifyMessage = document.getElementById('verifyMessage');
    const verifyContinue = document.getElementById('verifyContinueBtn');
    const codeInputs = document.querySelectorAll('.code-input');
    const hiddenCode = document.getElementById('verifyCodeHidden');
    const verifyEmailDisplay = document.getElementById('verifyEmailDisplay');
    const resetForm = document.getElementById('resetForm');
    const resetBtn = document.getElementById('resetBtn');
    const resetMessage = document.getElementById('resetMessage');

    const forgotEndpoint = '../auth/forgot_password.php';
    const verifyEndpoint = '../auth/verify_reset.php';
    const resendEndpoint = '../auth/resend_reset.php';
    const resetEndpoint = '../auth/set_new_password.php';

    // ─── CODE INPUT LOGIC ──────────────────────────────────
    function updateHiddenCode() {
        let code = '';
        codeInputs.forEach(inp => code += inp.value);
        hiddenCode.value = code;
        return code;
    }

    function clearCodeInputs() {
        codeInputs.forEach(inp => {
            inp.value = '';
            inp.classList.remove('filled');
        });
        codeInputs[0].focus();
        hiddenCode.value = '';
        verifyMessage.style.display = 'none';
        verifyMessage.className = 'auth-message';
    }

    codeInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length === 1) {
                this.classList.add('filled');
                if (index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }
            } else {
                this.classList.remove('filled');
            }
            updateHiddenCode();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                codeInputs[index - 1].focus();
                codeInputs[index - 1].value = '';
                codeInputs[index - 1].classList.remove('filled');
                updateHiddenCode();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/\D/g, '').slice(0, codeInputs.length);

            digits.split('').forEach((digit, i) => {
                if (codeInputs[i]) {
                    codeInputs[i].value = digit;
                    codeInputs[i].classList.add('filled');
                }
            });

            const lastIndex = Math.min(digits.length, codeInputs.length - 1);
            if (codeInputs[lastIndex]) {
                codeInputs[lastIndex].focus();
            }
            updateHiddenCode();
        });
    });

    // ─── OPEN / CLOSE FORGOT MODAL ──────────────────────────
    function openForgotModal() {
        forgotModal.classList.add('active');
        authForm.style.filter = 'blur(4px)';
        if (testimonial) testimonial.style.filter = 'blur(4px)';
        document.body.style.overflow = 'hidden';
        modalMessage.className = 'auth-message';
        modalMessage.style.display = 'none';
        document.getElementById('modalEmail').value = '';
    }

    function closeForgotModal() {
        forgotModal.classList.remove('active');
        if (!verifyModal.classList.contains('active') && !resetModal.classList.contains('active')) {
            authForm.style.filter = 'none';
            if (testimonial) testimonial.style.filter = 'none';
            document.body.style.overflow = '';
        }
    }

    // ─── OPEN / CLOSE VERIFY MODAL ──────────────────────────
    function openVerifyModal(email) {
        if (email) verifyEmailDisplay.textContent = email;
        verifyModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        verifyMessage.className = 'auth-message';
        verifyMessage.style.display = 'none';
        clearCodeInputs();
        setTimeout(() => codeInputs[0].focus(), 300);
    }

    function closeVerifyModal() {
        verifyModal.classList.remove('active');
        if (!forgotModal.classList.contains('active') && !resetModal.classList.contains('active')) {
            authForm.style.filter = 'none';
            if (testimonial) testimonial.style.filter = 'none';
            document.body.style.overflow = '';
        }
    }

    // ─── OPEN / CLOSE RESET MODAL ────────────────────────────
    function openResetModal() {
        resetModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        resetMessage.className = 'auth-message';
        resetMessage.style.display = 'none';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
    }

    function closeResetModal() {
        resetModal.classList.remove('active');
        if (!forgotModal.classList.contains('active') && !verifyModal.classList.contains('active')) {
            authForm.style.filter = 'none';
            if (testimonial) testimonial.style.filter = 'none';
            document.body.style.overflow = '';
        }
    }

    // ─── OPEN FORGOT MODAL ON LINK CLICK ──────────────────
    openLink.addEventListener('click', function(e) {
        e.preventDefault();
        openForgotModal();
    });

    // ─── CLOSE FORGOT MODAL EVENTS ──────────────────────────
    closeForgotBtn.addEventListener('click', function() {
        closeForgotModal();
        if (verifyModal.classList.contains('active')) closeVerifyModal();
        if (resetModal.classList.contains('active')) closeResetModal();
    });

    backLink.addEventListener('click', function(e) {
        e.preventDefault();
        closeForgotModal();
        if (verifyModal.classList.contains('active')) closeVerifyModal();
        if (resetModal.classList.contains('active')) closeResetModal();
    });

    forgotModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeForgotModal();
            if (verifyModal.classList.contains('active')) closeVerifyModal();
            if (resetModal.classList.contains('active')) closeResetModal();
        }
    });

    // ─── CLOSE VERIFY MODAL EVENTS ──────────────────────────
    closeVerifyBtn.addEventListener('click', function() {
        closeVerifyModal();
        if (resetModal.classList.contains('active')) {
            closeResetModal();
        }
    });

    verifyModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeVerifyModal();
            if (!forgotModal.classList.contains('active') && !resetModal.classList.contains('active')) {
                authForm.style.filter = 'none';
                if (testimonial) testimonial.style.filter = 'none';
                document.body.style.overflow = '';
            }
        }
    });

    // ─── CLOSE RESET MODAL EVENTS ────────────────────────────
    closeResetBtn.addEventListener('click', function() {
        closeResetModal();
    });

    resetModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeResetModal();
            if (!forgotModal.classList.contains('active') && !verifyModal.classList.contains('active')) {
                authForm.style.filter = 'none';
                if (testimonial) testimonial.style.filter = 'none';
                document.body.style.overflow = '';
            }
        }
    });

    resetBackLink.addEventListener('click', function(e) {
        e.preventDefault();
        closeResetModal();
        if (!forgotModal.classList.contains('active') && !verifyModal.classList.contains('active')) {
            authForm.style.filter = 'none';
            if (testimonial) testimonial.style.filter = 'none';
            document.body.style.overflow = '';
        }
    });

    // ─── ESCAPE KEY ──────────────────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (resetModal.classList.contains('active')) {
                closeResetModal();
            } else if (verifyModal.classList.contains('active')) {
                closeVerifyModal();
            } else if (forgotModal.classList.contains('active')) {
                closeForgotModal();
            }
            if (!forgotModal.classList.contains('active') && !verifyModal.classList.contains('active') && !resetModal.classList.contains('active')) {
                authForm.style.filter = 'none';
                if (testimonial) testimonial.style.filter = 'none';
                document.body.style.overflow = '';
            }
        }
    });

    // ─── SIGNIN FORM SUBMISSION ────────────────────────────
    signinForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const rememberMe = document.getElementById('rememberMe');

        if (!email || !password) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Please enter your email and password.';
            messageEl.style.display = 'block';
            return;
        }

        if (!email.includes('@') || !email.includes('.')) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Please enter a valid email address.';
            messageEl.style.display = 'block';
            return;
        }

        if (!rememberMe || !rememberMe.checked) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Please check Remember me before signing in.';
            messageEl.style.display = 'block';
            rememberMe?.focus();
            return;
        }

        const originalText = signinBtn.textContent;
        signinBtn.disabled = true;
        signinBtn.textContent = 'Signing in...';

        const formData = new FormData(signinForm);

        fetch('../auth/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageEl.className = 'auth-message success';
                messageEl.textContent = data.message || '✅ Welcome back! Redirecting...';
                messageEl.style.display = 'block';
                setTimeout(() => {
                    // ✅ Redirect to home.php
                    window.location.href = data.redirect || '../Php/home.php';
                }, 1000);
            } else {
                messageEl.className = 'auth-message error';
                messageEl.textContent = data.error || 'Invalid email or password.';
                messageEl.style.display = 'block';
                signinBtn.disabled = false;
                signinBtn.textContent = originalText;
            }
        })
        .catch(error => {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'An error occurred. Please try again.';
            messageEl.style.display = 'block';
            signinBtn.disabled = false;
            signinBtn.textContent = originalText;
        });
    });

    // ─── FORGOT PASSWORD FORM SUBMISSION ──────────────────
    forgotForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = document.getElementById('modalEmail').value.trim();

        if (!email) {
            modalMessage.className = 'auth-message error';
            modalMessage.textContent = 'Please enter your email address.';
            modalMessage.style.display = 'block';
            return;
        }

        if (!email.includes('@') || !email.includes('.')) {
            modalMessage.className = 'auth-message error';
            modalMessage.textContent = 'Please enter a valid email address.';
            modalMessage.style.display = 'block';
            return;
        }

        const originalText = forgotBtn.textContent;
        forgotBtn.disabled = true;
        forgotBtn.textContent = 'Sending...';

        const formData = new FormData();
        formData.append('email', email);

        fetch(forgotEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalMessage.className = 'auth-message success';
                modalMessage.textContent = data.message || '✅ Code sent! Check your email.';
                modalMessage.style.display = 'block';

                setTimeout(() => {
                    forgotModal.classList.remove('active');
                    openVerifyModal(email);
                    forgotBtn.disabled = false;
                    forgotBtn.textContent = originalText;
                }, 600);
            } else {
                modalMessage.className = 'auth-message error';
                modalMessage.textContent = data.error || 'Failed to send reset code.';
                modalMessage.style.display = 'block';
                forgotBtn.disabled = false;
                forgotBtn.textContent = originalText;
            }
        })
        .catch(() => {
            modalMessage.className = 'auth-message error';
            modalMessage.textContent = 'An error occurred. Please try again.';
            modalMessage.style.display = 'block';
            forgotBtn.disabled = false;
            forgotBtn.textContent = originalText;
        });
    });

    // ─── VERIFY CODE CONTINUE ──────────────────────────────
    verifyContinue.addEventListener('click', function() {
        const code = updateHiddenCode();
        const msg = verifyMessage;

        if (code.length !== 6 || !/^\d{6}$/.test(code)) {
            msg.className = 'auth-message error';
            msg.textContent = 'Please enter the complete 6-digit code.';
            msg.style.display = 'block';
            return;
        }

        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = 'Verifying...';

        const formData = new FormData();
        formData.append('code', code);

        fetch(verifyEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msg.className = 'auth-message success';
                msg.textContent = data.message || '✅ Code verified! Please set a new password.';
                msg.style.display = 'block';

                setTimeout(() => {
                    verifyModal.classList.remove('active');
                    openResetModal();
                    this.disabled = false;
                    this.textContent = originalText;
                }, 600);
            } else {
                msg.className = 'auth-message error';
                msg.textContent = data.error || 'Invalid code. Please try again.';
                msg.style.display = 'block';
                clearCodeInputs();
                this.disabled = false;
                this.textContent = originalText;
            }
        })
        .catch(() => {
            msg.className = 'auth-message error';
            msg.textContent = 'An error occurred. Please try again.';
            msg.style.display = 'block';
            this.disabled = false;
            this.textContent = originalText;
        });
    });

    // ─── CHANGE EMAIL ──────────────────────────────────────
    changeEmailLink.addEventListener('click', function(e) {
        e.preventDefault();
        closeVerifyModal();
        openForgotModal();
        setTimeout(() => {
            document.getElementById('modalEmail').focus();
        }, 300);
    });

    // ─── RESEND CODE ───────────────────────────────────────
    resendCodeLink.addEventListener('click', function(e) {
        e.preventDefault();
        const msg = verifyMessage;
        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = 'Sending...';

        fetch(resendEndpoint, {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msg.className = 'auth-message success';
                msg.textContent = data.message || '✅ A new code has been sent to your email.';
                msg.style.display = 'block';
                clearCodeInputs();
            } else {
                msg.className = 'auth-message error';
                msg.textContent = data.error || 'Failed to resend code.';
                msg.style.display = 'block';
            }
        })
        .catch(() => {
            msg.className = 'auth-message error';
            msg.textContent = 'An error occurred. Please try again.';
            msg.style.display = 'block';
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = originalText;
        });
    });

    // ─── RESET PASSWORD FORM SUBMISSION ────────────────────
    resetForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const newPassword = document.getElementById('newPassword').value.trim();
        const confirmPassword = document.getElementById('confirmPassword').value.trim();

        if (!newPassword || !confirmPassword) {
            resetMessage.className = 'auth-message error';
            resetMessage.textContent = 'Please fill in both password fields.';
            resetMessage.style.display = 'block';
            return;
        }

        if (newPassword.length < 8) {
            resetMessage.className = 'auth-message error';
            resetMessage.textContent = 'Password must be at least 8 characters long.';
            resetMessage.style.display = 'block';
            return;
        }

        if (newPassword !== confirmPassword) {
            resetMessage.className = 'auth-message error';
            resetMessage.textContent = 'Passwords do not match.';
            resetMessage.style.display = 'block';
            return;
        }

        const originalText = resetBtn.textContent;
        resetBtn.disabled = true;
        resetBtn.textContent = 'Resetting...';

        const formData = new FormData();
        formData.append('password', newPassword);
        formData.append('confirm_password', confirmPassword);

        fetch(resetEndpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resetMessage.className = 'auth-message success';
                resetMessage.textContent = data.message || '✅ Password reset successful! Redirecting to sign in...';
                resetMessage.style.display = 'block';

                setTimeout(() => {
                    resetModal.classList.remove('active');
                    authForm.style.filter = 'none';
                    if (testimonial) testimonial.style.filter = 'none';
                    document.body.style.overflow = '';
                    window.location.href = 'signin.php';
                }, 1200);
            } else {
                resetMessage.className = 'auth-message error';
                resetMessage.textContent = data.error || 'Failed to reset password.';
                resetMessage.style.display = 'block';
                resetBtn.disabled = false;
                resetBtn.textContent = originalText;
            }
        })
        .catch(() => {
            resetMessage.className = 'auth-message error';
            resetMessage.textContent = 'An error occurred. Please try again.';
            resetMessage.style.display = 'block';
            resetBtn.disabled = false;
            resetBtn.textContent = originalText;
        });
    });

    // ─── ENTER KEY ON CODE INPUTS ─────────────────────────
    codeInputs.forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyContinue.click();
            }
        });
    });

    // ─── ENTER KEY ON RESET FORM ───────────────────────────
    document.getElementById('confirmPassword').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            resetBtn.click();
        }
    });

});
