/**
 * Civentral Employee Portal - Forgot Password & Password Reset Controller
 * Integrates with:
 * - POST /api/employee/forgot-password.php
 * - POST /api/employee/verify-reset-otp.php
 * - POST /api/employee/reset-password.php
 */

let fpCurrentStep = 1;
let fpResendTimerInterval = null;
let fpSavedIdentifier = '';

// Toggle Modal Open
function openForgotPasswordModal(e) {
  if (e) e.preventDefault();
  
  const modal = document.getElementById('forgotPasswordModal');
  if (!modal) return;

  // Pre-fill identifier from login form if user already typed it
  const loginIdInput = document.getElementById('employeeId');
  const fpIdInput = document.getElementById('fpIdentifier');
  if (loginIdInput && fpIdInput && loginIdInput.value.trim() !== '') {
    fpIdInput.value = loginIdInput.value.trim();
  }

  // Reset to Step 1
  resetFpForms();
  goToFpStep(1);

  modal.classList.remove('hidden');
  setTimeout(() => {
    modal.classList.remove('opacity-0');
    const inner = modal.querySelector('div.transform');
    if (inner) inner.classList.remove('scale-95');
  }, 10);

  if (fpIdInput) {
    setTimeout(() => fpIdInput.focus(), 100);
  }
}

// Toggle Modal Close
function closeForgotPasswordModal() {
  const modal = document.getElementById('forgotPasswordModal');
  if (!modal) return;

  modal.classList.add('opacity-0');
  const inner = modal.querySelector('div.transform');
  if (inner) inner.classList.add('scale-95');

  if (fpResendTimerInterval) {
    clearInterval(fpResendTimerInterval);
    fpResendTimerInterval = null;
  }

  setTimeout(() => {
    modal.classList.add('hidden');
  }, 250);
}

// Reset all forms & alerts
function resetFpForms() {
  const form1 = document.getElementById('fpForm1');
  const form2 = document.getElementById('fpForm2');
  const form3 = document.getElementById('fpForm3');
  if (form1) form1.reset();
  if (form2) form2.reset();
  if (form3) form3.reset();

  hideFpAlert('fpAlert1');
  hideFpAlert('fpAlert2');
  hideFpAlert('fpAlert3');

  // Reset OTP boxes
  const otpInputs = document.querySelectorAll('.fp-otp-input');
  otpInputs.forEach(input => input.value = '');
}

// Switch Steps & Update Progress Dots
function goToFpStep(step) {
  fpCurrentStep = step;

  const step1 = document.getElementById('fpStep1');
  const step2 = document.getElementById('fpStep2');
  const step3 = document.getElementById('fpStep3');
  const step4 = document.getElementById('fpStep4');

  if (step1) step1.classList.toggle('hidden', step !== 1);
  if (step2) step2.classList.toggle('hidden', step !== 2);
  if (step3) step3.classList.toggle('hidden', step !== 3);
  if (step4) step4.classList.toggle('hidden', step !== 4);

  // Update dots
  const dot1 = document.getElementById('fpDot1');
  const dot2 = document.getElementById('fpDot2');
  const dot3 = document.getElementById('fpDot3');

  const updateDot = (dot, active) => {
    if (!dot) return;
    if (active) {
      dot.className = 'h-2 w-8 rounded-full bg-brand-medium transition-all duration-300';
    } else {
      dot.className = 'h-2 w-2 rounded-full bg-slate-200 transition-all duration-300';
    }
  };

  if (step === 4) {
    // Hide dots on success screen
    if (dot1) dot1.parentElement.classList.add('hidden');
  } else {
    if (dot1) dot1.parentElement.classList.remove('hidden');
    updateDot(dot1, step === 1);
    updateDot(dot2, step === 2);
    updateDot(dot3, step === 3);
  }
}

// Alert Box Helpers
function showFpAlert(elementId, message, isError = true) {
  const el = document.getElementById(elementId);
  if (!el) return;

  el.classList.remove('hidden', 'bg-rose-50', 'text-rose-700', 'border-rose-200', 'bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
  if (isError) {
    el.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-200');
    el.innerHTML = `<i class="fa-solid fa-circle-exclamation mr-1.5"></i> ${message}`;
  } else {
    el.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
    el.innerHTML = `<i class="fa-solid fa-circle-check mr-1.5"></i> ${message}`;
  }
}

function hideFpAlert(elementId) {
  const el = document.getElementById(elementId);
  if (el) el.classList.add('hidden');
}

// Button loading state helper
function setButtonLoading(btn, isLoading, defaultText = 'Submit') {
  if (!btn) return;
  btn.disabled = isLoading;
  if (isLoading) {
    btn.innerHTML = `<i class="fa-solid fa-circle-notch animate-spin text-sm"></i><span>Processing...</span>`;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
  } else {
    btn.innerHTML = `<span>${defaultText}</span>`;
    btn.classList.remove('opacity-75', 'cursor-not-allowed');
  }
}

// Mask email / identifier for security display
function maskIdentifier(id) {
  if (!id) return 'your registered contact';
  if (id.includes('@')) {
    const parts = id.split('@');
    const name = parts[0];
    const domain = parts[1];
    const visible = name.length > 2 ? name.substring(0, 2) : name.substring(0, 1);
    return `${visible}••••@${domain}`;
  }
  return id.length > 4 ? `${id.substring(0, 3)}••••` : id;
}

// STEP 1: Handle Initial Forgot Password Request
async function handleForgotPasswordRequest(event) {
  event.preventDefault();
  hideFpAlert('fpAlert1');

  const idInput = document.getElementById('fpIdentifier');
  const identifier = idInput ? idInput.value.trim() : '';
  const submitBtn = document.getElementById('fpBtnSubmit1');

  if (!identifier) {
    showFpAlert('fpAlert1', 'Please enter your Employee ID or Email address.', true);
    return;
  }

  setButtonLoading(submitBtn, true, 'Send Reset Code');

  try {
    const response = await fetch('api/employee/forgot-password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ identifier: identifier })
    });

    const data = await response.json().catch(() => ({}));

    if (response.ok && (data.success || data.status === 'success')) {
      fpSavedIdentifier = identifier;
      
      // Update contact display in Step 2
      const contactEl = document.getElementById('fpMaskedContact');
      if (contactEl) {
        contactEl.textContent = maskIdentifier(identifier);
      }

      goToFpStep(2);
      startFpResendCountdown(60);

      // Focus first OTP input
      setTimeout(() => {
        const firstOtp = document.querySelector('.fp-otp-input');
        if (firstOtp) firstOtp.focus();
      }, 150);
    } else {
      const errMsg = data.message || 'Unable to process request. Please verify your identifier.';
      showFpAlert('fpAlert1', errMsg, true);
    }
  } catch (err) {
    console.error('Forgot password error:', err);
    showFpAlert('fpAlert1', 'Network error connecting to Civentral server. Please try again.', true);
  } finally {
    setButtonLoading(submitBtn, false, 'Send Reset Code');
  }
}

// STEP 2: Setup OTP Digit Input Auto-Advance & Paste
document.addEventListener('DOMContentLoaded', () => {
  const otpInputs = document.querySelectorAll('.fp-otp-input');

  otpInputs.forEach((input, index) => {
    // Only accept numbers
    input.addEventListener('input', (e) => {
      input.value = input.value.replace(/[^0-9]/g, '');
      if (input.value.length === 1) {
        if (index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      }
    });

    // Handle backspace navigation
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace') {
        if (input.value === '' && index > 0) {
          otpInputs[index - 1].focus();
        }
      }
    });

    // Handle Paste (user pastes 6-digit code)
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').trim();
      const digits = pasted.replace(/[^0-9]/g, '').slice(0, 6);
      if (digits.length > 0) {
        digits.split('').forEach((d, idx) => {
          if (otpInputs[idx]) {
            otpInputs[idx].value = d;
          }
        });
        const focusIdx = Math.min(digits.length, otpInputs.length - 1);
        otpInputs[focusIdx].focus();
      }
    });
  });
});

// STEP 2: Verify OTP Submission
async function handleVerifyResetOTP(event) {
  event.preventDefault();
  hideFpAlert('fpAlert2');

  const otpInputs = document.querySelectorAll('.fp-otp-input');
  let otpCode = '';
  otpInputs.forEach(i => otpCode += i.value.trim());

  const submitBtn = document.getElementById('fpBtnSubmit2');

  if (otpCode.length < 6) {
    showFpAlert('fpAlert2', 'Please enter all 6 digits of the verification code.', true);
    return;
  }

  setButtonLoading(submitBtn, true, 'Verify & Continue');

  try {
    const response = await fetch('api/employee/verify-reset-otp.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        otp: otpCode,
        identifier: fpSavedIdentifier
      })
    });

    const data = await response.json().catch(() => ({}));

    if (response.ok && (data.status === 'success' || data.success)) {
      if (fpResendTimerInterval) {
        clearInterval(fpResendTimerInterval);
        fpResendTimerInterval = null;
      }
      
      goToFpStep(3);
      setTimeout(() => {
        const newPass = document.getElementById('fpNewPassword');
        if (newPass) newPass.focus();
      }, 150);
    } else {
      const errMsg = data.message || 'Invalid or expired verification code. Please try again.';
      showFpAlert('fpAlert2', errMsg, true);
    }
  } catch (err) {
    console.error('Verify reset OTP error:', err);
    showFpAlert('fpAlert2', 'Network error verifying security code. Please try again.', true);
  } finally {
    setButtonLoading(submitBtn, false, 'Verify & Continue');
  }
}

// Resend OTP in Step 2
async function handleResendResetOTP() {
  const resendBtn = document.getElementById('btnFpResend');
  if (!fpSavedIdentifier || (resendBtn && resendBtn.disabled)) return;

  hideFpAlert('fpAlert2');

  try {
    const response = await fetch('api/employee/forgot-password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ identifier: fpSavedIdentifier })
    });

    const data = await response.json().catch(() => ({}));

    if (response.ok && (data.success || data.status === 'success')) {
      showFpAlert('fpAlert2', 'A new 6-digit code has been sent to your email.', false);
      startFpResendCountdown(60);
    } else {
      showFpAlert('fpAlert2', data.message || 'Failed to resend code. Please try again.', true);
    }
  } catch (err) {
    showFpAlert('fpAlert2', 'Network error resending code.', true);
  }
}

// Countdown Timer for OTP Resend
function startFpResendCountdown(seconds) {
  const btn = document.getElementById('btnFpResend');
  const timerText = document.getElementById('fpResendTimer');

  if (fpResendTimerInterval) {
    clearInterval(fpResendTimerInterval);
    fpResendTimerInterval = null;
  }

  let remaining = seconds;
  if (btn) {
    btn.disabled = true;
    btn.classList.add('opacity-50', 'cursor-not-allowed');
    btn.classList.remove('hover:underline', 'text-brand-dark');
  }
  if (timerText) {
    timerText.classList.remove('hidden');
    timerText.textContent = `(${remaining}s)`;
  }

  fpResendTimerInterval = setInterval(() => {
    remaining--;
    if (timerText) timerText.textContent = `(${remaining}s)`;

    if (remaining <= 0) {
      clearInterval(fpResendTimerInterval);
      fpResendTimerInterval = null;
      if (btn) {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.classList.add('hover:underline', 'text-brand-dark');
      }
      if (timerText) {
        timerText.classList.add('hidden');
      }
    }
  }, 1000);
}

// Toggle Password Visibility
function toggleFpPasswordVisibility(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (!input || !icon) return;

  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  }
}

// STEP 3: Handle Reset Password Submission
async function handleResetPasswordSubmit(event) {
  event.preventDefault();
  hideFpAlert('fpAlert3');

  const newPass = document.getElementById('fpNewPassword').value;
  const confirmPass = document.getElementById('fpConfirmPassword').value;
  const submitBtn = document.getElementById('fpBtnSubmit3');

  if (!newPass || !confirmPass) {
    showFpAlert('fpAlert3', 'Please fill in both password fields.', true);
    return;
  }

  if (newPass.length < 8) {
    showFpAlert('fpAlert3', 'Password must be at least 8 characters in length.', true);
    return;
  }

  if (newPass !== confirmPass) {
    showFpAlert('fpAlert3', 'New password and confirmation do not match.', true);
    return;
  }

  setButtonLoading(submitBtn, true, 'Update Password');

  try {
    const response = await fetch('api/employee/reset-password.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        new_password: newPass,
        confirm_password: confirmPass,
        identifier: fpSavedIdentifier
      })
    });

    const data = await response.json().catch(() => ({}));

    if (response.ok && (data.status === 'success' || data.success)) {
      goToFpStep(4);
    } else {
      const errMsg = data.message || 'Failed to reset password. Session may have expired.';
      showFpAlert('fpAlert3', errMsg, true);
    }
  } catch (err) {
    console.error('Reset password error:', err);
    showFpAlert('fpAlert3', 'Network error connecting to Civentral server. Please try again.', true);
  } finally {
    setButtonLoading(submitBtn, false, 'Update Password');
  }
}

// STEP 4: Finish and Return to Sign In
function finishForgotPassword() {
  closeForgotPasswordModal();

  // Populate employee ID field on login form with their identifier
  const empInput = document.getElementById('employeeId');
  const passInput = document.getElementById('password');
  if (empInput && fpSavedIdentifier) {
    empInput.value = fpSavedIdentifier;
  }
  if (passInput) {
    passInput.value = '';
    setTimeout(() => passInput.focus(), 300);
  }

  // Show success alert on main login screen
  if (typeof showStatusAlert === 'function') {
    showStatusAlert('success', 'Your password has been successfully reset! You can now sign in.');
  }
}
