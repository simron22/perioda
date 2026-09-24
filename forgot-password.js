document.addEventListener('DOMContentLoaded', function () {
    const verifyForm = document.getElementById('verifyForm');
    const resetForm = document.getElementById('resetForm');
    const formSubtitle = document.getElementById('formSubtitle');
    const verifyError = document.getElementById('verifyError');
    const resetError = document.getElementById('resetError');
    const resetSuccess = document.getElementById('resetSuccess');
    const verifyBtn = document.getElementById('verifyBtn');
    const resetBtn = document.getElementById('resetBtn');

    // Toggle password visibility
    const setupPasswordToggle = (inputId, toggleId) => {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        
        if (input && toggle) {
            toggle.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    toggle.textContent = 'Hide';
                } else {
                    input.type = 'password';
                    toggle.textContent = 'Show';
                }
            });
        }
    };

    setupPasswordToggle('newPassword', 'toggleNewPassword');
    setupPasswordToggle('confirmPassword', 'toggleConfirmPassword');

    let verifiedEmail = '';
    let verifiedPhone = '';

    // Step 1: Verify Identity
    verifyForm.addEventListener('submit', function (e) {
        e.preventDefault();
        verifyError.textContent = '';
        
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!email || !phone) {
            verifyError.textContent = 'Please enter both email and phone number.';
            return;
        }

        if (!/^\d{10}$/.test(phone)) {
            verifyError.textContent = 'Phone number must be exactly 10 digits.';
            return;
        }

        verifyBtn.disabled = true;
        verifyBtn.textContent = 'Verifying...';

        fetch('api/verify_identity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: email, phone: phone })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Identity verified
                verifiedEmail = email;
                verifiedPhone = phone;
                
                // Switch UI to Step 2
                verifyForm.classList.add('hidden');
                resetForm.classList.remove('hidden');
                formSubtitle.textContent = 'Identity verified. Enter your new password below.';
                formSubtitle.style.color = 'green';
            } else {
                verifyError.textContent = data.error || 'Verification failed. Please check your details.';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            verifyError.textContent = 'An error occurred during verification.';
        })
        .finally(() => {
            verifyBtn.disabled = false;
            verifyBtn.textContent = 'Verify Identity';
        });
    });

    // Step 2: Reset Password
    resetForm.addEventListener('submit', function (e) {
        e.preventDefault();
        resetError.textContent = '';
        resetSuccess.textContent = '';
        
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (!newPassword || !confirmPassword) {
            resetError.textContent = 'Please fill out all fields.';
            return;
        }

        if (newPassword !== confirmPassword) {
            resetError.textContent = 'Passwords do not match.';
            return;
        }

        if (newPassword.length < 6) {
            resetError.textContent = 'Password must be at least 6 characters.';
            return;
        }

        resetBtn.disabled = true;
        resetBtn.textContent = 'Resetting...';

        fetch('api/reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                email: verifiedEmail, 
                phone: verifiedPhone,
                new_password: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resetForm.reset();
                resetSuccess.textContent = 'Password reset successfully! Redirecting to login...';
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                resetError.textContent = data.error || 'Failed to reset password.';
                resetBtn.disabled = false;
                resetBtn.textContent = 'Reset Password';
            }
        })
        .catch(err => {
            console.error('Error:', err);
            resetError.textContent = 'An error occurred while resetting your password.';
            resetBtn.disabled = false;
            resetBtn.textContent = 'Reset Password';
        });
    });
});
