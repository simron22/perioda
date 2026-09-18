/* =====================================================
   PERIODA - Registration form validation (client side)
   Rules here MUST match includes validation done in PHP
   (JS = user experience only, PHP = real security)
   ===================================================== */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const fullName = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    // ---------- validators ----------
    function validateFullName(value) {
        if (value.trim() === '') return { ok: false, msg: '' }; // don't scare on empty
        if (!/^[A-Za-z\s]+$/.test(value)) {
            return { ok: false, msg: 'Only letters and spaces are allowed.' };
        }
        if (value.trim().length < 3) {
            return { ok: false, msg: 'Full name is too short.' };
        }
        return { ok: true, msg: 'Looks good.' };
    }

    function validateEmail(value) {
        if (value.trim() === '') return { ok: false, msg: '' };
        const pattern = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i;
        if (!pattern.test(value)) {
            return { ok: false, msg: 'Please enter a valid email address.' };
        }
        return { ok: true, msg: 'Looks good.' };
    }

    function validatePhone(value) {
        if (value.trim() === '') return { ok: false, msg: '' };
        if (!/^\d+$/.test(value)) {
            return { ok: false, msg: 'Phone number must contain digits only.' };
        }
        if (value.length !== 10) {
            return { ok: false, msg: 'Phone number must contain exactly 10 digits.' };
        }
        if (!value.startsWith('97') && !value.startsWith('98')) {
            return { ok: false, msg: 'Phone number must start with 97 or 98.' };
        }
        return { ok: true, msg: 'Looks good.' };
    }

    function validatePassword(value) {
        if (value === '') return { ok: false, msg: '' };
        if (value.length < 8) {
            return { ok: false, msg: 'Password must contain at least 8 characters.' };
        }
        if (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value)) {
            return { ok: false, msg: 'Password must contain both letters and numbers.' };
        }
        return { ok: true, msg: 'Strong enough.' };
    }

    function validateConfirm(value) {
        if (value === '') return { ok: false, msg: '' };
        if (value !== password.value) {
            return { ok: false, msg: 'Passwords do not match.' };
        }
        return { ok: true, msg: 'Passwords match.' };
    }

    // ---------- apply visual state ----------
    function applyState(input, result) {
        const group = input.closest('.form-group');
        const msg = group.querySelector('.field-msg');
        group.classList.remove('valid', 'invalid');
        if (input.value.trim() === '') {
            msg.textContent = '';
            msg.className = 'field-msg';
            return;
        }
        if (result.ok) {
            group.classList.add('valid');
            msg.textContent = result.msg;
            msg.className = 'field-msg success';
        } else if (result.msg !== '') {
            group.classList.add('invalid');
            msg.textContent = result.msg;
            msg.className = 'field-msg error';
        }
    }

    function wireField(input, validator, nextField) {
        input.addEventListener('input', function () {
            applyState(input, validator(input.value));
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const result = validator(input.value);
                applyState(input, result);
                if (result.ok && nextField) {
                    nextField.focus();
                }
            }
        });
    }

    wireField(fullName, validateFullName, email);
    wireField(email, validateEmail, phone);
    wireField(phone, validatePhone, password);
    wireField(password, validatePassword, confirmPassword);
    wireField(confirmPassword, validateConfirm, null);

    // Re-check confirm password whenever password changes
    password.addEventListener('input', function () {
        if (confirmPassword.value !== '') {
            applyState(confirmPassword, validateConfirm(confirmPassword.value));
        }
    });

    // ---------- final submit check ----------
    form.addEventListener('submit', function (e) {
        const checks = [
            { input: fullName, result: validateFullName(fullName.value) },
            { input: email, result: validateEmail(email.value) },
            { input: phone, result: validatePhone(phone.value) },
            { input: password, result: validatePassword(password.value) },
            { input: confirmPassword, result: validateConfirm(confirmPassword.value) },
        ];

        let allOk = true;
        checks.forEach(function (c) {
            applyState(c.input, c.result);
            if (!c.result.ok) allOk = false;
        });

        if (!allOk) {
            e.preventDefault();
        }
    });
});
