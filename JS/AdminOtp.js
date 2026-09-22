// ── OTP Box Auto-advance & Backspace Logic ──
const otpBoxes = Array.from(document.querySelectorAll('.otp-box'));

otpBoxes.forEach((box, i) => {

  box.addEventListener('input', (e) => {
    // Allow digits only
    box.value = box.value.replace(/\D/g, '');

    if (box.value) {
      box.classList.add('filled');
      // Move to next box
      if (i < otpBoxes.length - 1) {
        otpBoxes[i + 1].focus();
      }
    } else {
      box.classList.remove('filled');
    }
  });

  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) {
      otpBoxes[i - 1].value = '';
      otpBoxes[i - 1].classList.remove('filled');
      otpBoxes[i - 1].focus();
    }
    if (e.key === 'Enter') {
      document.getElementById('continueBtn').click();
    }
  });

  // Handle paste on any box — distribute digits across all boxes
  box.addEventListener('paste', (e) => {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData)
      .getData('text')
      .replace(/\D/g, '')
      .slice(0, otpBoxes.length);

    pasted.split('').forEach((char, idx) => {
      if (otpBoxes[i + idx]) {
        otpBoxes[i + idx].value = char;
        otpBoxes[i + idx].classList.add('filled');
      }
    });

    // Focus the box after the last pasted digit
    const nextIdx = Math.min(i + pasted.length, otpBoxes.length - 1);
    otpBoxes[nextIdx].focus();
  });
});

// ── Continue Button ──
document.getElementById('continueBtn').addEventListener('click', () => {
  const code = otpBoxes.map(b => b.value).join('');

  if (code.length < otpBoxes.length) {
    showToast('Please enter the full 6-digit code.');
    // Focus first empty box
    const firstEmpty = otpBoxes.find(b => !b.value);
    if (firstEmpty) firstEmpty.focus();
    return;
  }

  // Demo success
  showToast('✓ Code verified! Redirecting…', 2500);
});

// ── Resend Code with Cooldown ──
const resendBtn = document.getElementById('resendBtn');
let cooldownTimer = null;

resendBtn.addEventListener('click', (e) => {
  e.preventDefault();
  if (resendBtn.classList.contains('cooldown')) return;

  showToast('A new code has been sent to your email.');

  // Clear all boxes
  otpBoxes.forEach(b => {
    b.value = '';
    b.classList.remove('filled');
  });
  otpBoxes[0].focus();

  // Start 30s cooldown
  let secs = 30;
  resendBtn.classList.add('cooldown');
  resendBtn.textContent = `Resend Code (${secs}s)`;

  cooldownTimer = setInterval(() => {
    secs--;
    resendBtn.textContent = `Resend Code (${secs}s)`;
    if (secs <= 0) {
      clearInterval(cooldownTimer);
      resendBtn.classList.remove('cooldown');
      resendBtn.textContent = 'Resend Code';
    }
  }, 1000);
});