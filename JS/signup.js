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
    const verifyModal = document.getElementById('verifyModal');
    const verifyClose = document.getElementById('verifyModalClose');
    const verifyContinue = document.getElementById('verifyContinueBtn');
    const changeEmailLink = document.getElementById('changeEmailLink');
    const resendCodeLink = document.getElementById('resendCodeLink');
    const verifyMessage = document.getElementById('verifyMessage');
    const codeInputs = document.querySelectorAll('.code-input');
    const hiddenCode = document.getElementById('verifyCodeHidden');
    const verifyEmailDisplay = document.getElementById('verifyEmailDisplay');
    const authForm = document.getElementById('authForm');
    const testimonial = document.querySelector('.auth-testimonial');
    const signupForm = document.getElementById('signupForm');
    const signupBtn = document.getElementById('signupBtn');
    const messageEl = document.getElementById('authMessage');

    // Terms & Conditions Modal
    const termsModal = document.getElementById('termsModal');
    const termsModalClose = document.getElementById('termsModalClose');
    const termsAcceptBtn = document.getElementById('termsAcceptBtn');
    const termsDeclineBtn = document.getElementById('termsDeclineBtn');
    const termsMessage = document.getElementById('termsMessage');

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

    // Setup code input auto-advance
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

    // ─── OPEN / CLOSE MODAL ────────────────────────────────
    function openVerifyModal(email) {
        if (email) {
            verifyEmailDisplay.textContent = email;
        }
        verifyModal.classList.add('active');
        authForm.style.filter = 'blur(4px)';
        if (testimonial) {
            testimonial.style.filter = 'blur(4px)';
        }
        document.body.style.overflow = 'hidden';
        verifyMessage.className = 'auth-message';
        verifyMessage.style.display = 'none';
        clearCodeInputs();
        setTimeout(() => {
            codeInputs[0].focus();
        }, 300);
    }

    function closeVerifyModal() {
        verifyModal.classList.remove('active');
        authForm.style.filter = 'none';
        if (testimonial) {
            testimonial.style.filter = 'none';
        }
        document.body.style.overflow = '';
    }

    // ─── TERMS & CONDITIONS MODAL FUNCTIONS ────────────────
    function openTermsModal() {
        termsModal.classList.add('active');
        authForm.style.filter = 'blur(4px)';
        if (testimonial) {
            testimonial.style.filter = 'blur(4px)';
        }
        document.body.style.overflow = 'hidden';
        termsMessage.style.display = 'none';
        termsMessage.className = 'auth-message';
    }

    function closeTermsModal() {
        termsModal.classList.remove('active');
        authForm.style.filter = 'none';
        if (testimonial) {
            testimonial.style.filter = 'none';
        }
        document.body.style.overflow = '';
    }

    function submitSignupForm() {
        // ─── Submit to backend ─────────────────────────────
        const formData = new FormData(signupForm);

        const originalText = signupBtn.textContent;
        signupBtn.disabled = true;
        signupBtn.textContent = 'Creating account...';

        fetch('../auth/register.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageEl.className = 'auth-message success';
                messageEl.textContent = data.message;
                messageEl.style.display = 'block';
                
                // Open verification modal
                setTimeout(() => {
                    closeTermsModal();
                    const email = document.getElementById('email').value.trim();
                    openVerifyModal(email);
                }, 800);
            } else {
                closeTermsModal();
                messageEl.className = 'auth-message error';
                if (data.errors) {
                    messageEl.textContent = data.errors.join(' ');
                } else {
                    messageEl.textContent = data.error || 'Registration failed.';
                }
                messageEl.style.display = 'block';
            }
        })
        .catch(error => {
            closeTermsModal();
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'An error occurred. Please try again.';
            messageEl.style.display = 'block';
        })
        .finally(() => {
            signupBtn.disabled = false;
            signupBtn.textContent = originalText;
        });
    }

    // ─── SIGNUP FORM SUBMISSION ────────────────────────────
    signupForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // ─── Form Validation ──────────────────────────────
        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const confirmPassword = document.getElementById('confirm_password').value.trim();
        const terms = document.getElementById('terms').checked;
        const privacyPolicy = document.getElementById('privacy_policy').checked;

        if (!fullName || !email || !password || !confirmPassword) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Please fill in all required fields.';
            messageEl.style.display = 'block';
            return;
        }

        if (!email.includes('@') || !email.includes('.')) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Please enter a valid email address.';
            messageEl.style.display = 'block';
            return;
        }

        if (password.length < 8) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Password must be at least 8 characters.';
            messageEl.style.display = 'block';
            return;
        }

        if (password !== confirmPassword) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'Passwords do not match.';
            messageEl.style.display = 'block';
            return;
        }

        if (!privacyPolicy) {
            messageEl.className = 'auth-message error';
            messageEl.textContent = 'You must agree to the Privacy Policy.';
            messageEl.style.display = 'block';
            return;
        }

        // ─── Show Terms & Conditions Modal ─────────────────
        openTermsModal();
    });

    // ─── MODAL CLOSE EVENTS ────────────────────────────────
    verifyClose.addEventListener('click', closeVerifyModal);

    verifyModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeVerifyModal();
        }
    });

    // ─── TERMS & CONDITIONS MODAL EVENTS ─────────────────
    termsModalClose.addEventListener('click', closeTermsModal);

    termsModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeTermsModal();
        }
    });

    termsAcceptBtn.addEventListener('click', function() {
        closeTermsModal();
        submitSignupForm();
    });

    termsDeclineBtn.addEventListener('click', function() {
        closeTermsModal();
        messageEl.className = 'auth-message error';
        messageEl.textContent = 'You must accept the Terms & Conditions to register.';
        messageEl.style.display = 'block';
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (verifyModal.classList.contains('active')) {
                closeVerifyModal();
            }
            if (termsModal.classList.contains('active')) {
                closeTermsModal();
            }
        }
    });

    // ─── CONTINUE BUTTON (verify code) ─────────────────────
    verifyContinue.addEventListener('click', function() {
        const code = updateHiddenCode();
        const msg = verifyMessage;

        if (code.length !== 6) {
            msg.className = 'auth-message error';
            msg.textContent = 'Please enter the complete 6-digit code.';
            msg.style.display = 'block';
            return;
        }

        const formData = new FormData();
        formData.append('code', code);

        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = 'Verifying...';

        fetch('../auth/verify_signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msg.className = 'auth-message success';
                msg.textContent = data.message;
                msg.style.display = 'block';

                setTimeout(() => {
                    window.location.href = data.redirect || 'signin.php';
                }, 1500);
            } else {
                msg.className = 'auth-message error';
                msg.textContent = data.error || 'Verification failed.';
                msg.style.display = 'block';
                clearCodeInputs();
            }
        })
        .catch(error => {
            msg.className = 'auth-message error';
            msg.textContent = 'An error occurred. Please try again.';
            msg.style.display = 'block';
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = originalText;
        });
    });

    // ─── CHANGE EMAIL ──────────────────────────────────────
    changeEmailLink.addEventListener('click', function(e) {
        e.preventDefault();
        closeVerifyModal();
        const emailField = document.getElementById('email');
        emailField.focus();
        emailField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // ─── RESEND CODE ───────────────────────────────────────
    resendCodeLink.addEventListener('click', function(e) {
        e.preventDefault();
        const msg = verifyMessage;
        
        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = 'Sending...';

        fetch('../auth/resend_signup.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                msg.className = 'auth-message success';
                msg.textContent = data.message;
                msg.style.display = 'block';
                clearCodeInputs();
            } else {
                msg.className = 'auth-message error';
                msg.textContent = data.error || 'Failed to resend code.';
                msg.style.display = 'block';
            }
        })
        .catch(error => {
            msg.className = 'auth-message error';
            msg.textContent = 'An error occurred. Please try again.';
            msg.style.display = 'block';
        })
        .finally(() => {
            this.disabled = false;
            this.textContent = originalText;
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

});