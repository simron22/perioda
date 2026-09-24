document.addEventListener("DOMContentLoaded", function () {

    const registrationForm =
        document.getElementById("registrationForm");

    const nameInput =
        document.getElementById("name");

    const dobInput =
        document.getElementById("dob");

    const phoneInput =
        document.getElementById("phone");

    const emailInput =
        document.getElementById("email");

    const passwordInput =
        document.getElementById("password");

    const confirmPasswordInput =
        document.getElementById("confirmPassword");

    const togglePassword =
        document.getElementById("togglePassword");

    const toggleConfirmPassword =
        document.getElementById("toggleConfirmPassword");


    /* =========================
       SHOW / HIDE PASSWORD
    ========================== */

    togglePassword.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";
            togglePassword.textContent = "Hide";

        } else {

            passwordInput.type = "password";
            togglePassword.textContent = "Show";

        }

    });


    /* =========================
       SHOW / HIDE CONFIRM PASSWORD
    ========================== */

    toggleConfirmPassword.addEventListener(
        "click",
        function () {

            if (confirmPasswordInput.type === "password") {

                confirmPasswordInput.type = "text";
                toggleConfirmPassword.textContent = "Hide";

            } else {

                confirmPasswordInput.type = "password";
                toggleConfirmPassword.textContent = "Show";

            }

        }
    );


    /* =========================
       ERROR MESSAGE FUNCTION
    ========================== */

    function showError(input, errorId, message) {

        input.classList.add("input-error");

        document.getElementById(errorId).textContent =
            message;

    }


    /* =========================
       CLEAR ERROR FUNCTION
    ========================== */

    function clearError(input, errorId) {

        input.classList.remove("input-error");

        document.getElementById(errorId).textContent =
            "";

    }


    /* =========================
       NAME VALIDATION
    ========================== */

    function validateName() {

        const name =
            nameInput.value.trim();

        /*
         * Only letters and spaces.
         * Supports English alphabet names.
         */

        const namePattern =
            /^[A-Za-z]+(?:\s+[A-Za-z]+)*$/;


        if (name === "") {

            showError(
                nameInput,
                "nameError",
                "Please enter your name."
            );

            return false;

        }


        if (name.length < 2) {

            showError(
                nameInput,
                "nameError",
                "Name must contain at least 2 characters."
            );

            return false;

        }


        if (name.length > 50) {

            showError(
                nameInput,
                "nameError",
                "Name cannot be longer than 50 characters."
            );

            return false;

        }


        if (!namePattern.test(name)) {

            showError(
                nameInput,
                "nameError",
                "Name can contain only letters and spaces."
            );

            return false;

        }


        /*
         * Prevent obvious repeated-character
         * or keyboard-style gibberish.
         */

        const words =
            name.toLowerCase().split(/\s+/);


        for (const word of words) {

            if (word.length >= 4) {

                const uniqueCharacters =
                    new Set(word).size;

                /*
                 * Examples:
                 * aaaa
                 * ssssss
                 */

                if (
                    uniqueCharacters <= 2 &&
                    word.length >= 4
                ) {

                    showError(
                        nameInput,
                        "nameError",
                        "Please enter a valid name."
                    );

                    return false;

                }

            }

        }


        clearError(
            nameInput,
            "nameError"
        );

        return true;

    }


    /* =========================================
       DOB VALIDATION
       ========================================= */
    function validateDob() {
        const dob = dobInput.value;

        // Check if empty
        if (dob.trim() === "") {
            showError(
                dobInput,
                "dobError",
                "Please enter your date of birth."
            );
            return false;
        }

        // Check age is at least 13
        const dobDate = new Date(dob);
        const ageDifMs = Date.now() - dobDate.getTime();
        const ageDate = new Date(ageDifMs);
        const age = Math.abs(ageDate.getUTCFullYear() - 1970);

        if (age < 13) {
            showError(
                dobInput,
                "dobError",
                "You must be at least 13 years old to use this service."
            );
            return false;
        }

        clearError(
            dobInput,
            "dobError"
        );
        return true;
    }

    /* =========================================
       PHONE VALIDATION
       ========================================= */
    function validatePhone() {
        const phone = phoneInput.value.trim();

        if (phone === "") {
            showError(phoneInput, "phoneError", "Please enter your phone number.");
            return false;
        }

        if (!/^\+977(96|97|98)\d{8}$/.test(phone)) {
            showError(phoneInput, "phoneError", "Please enter a valid Nepali mobile number (e.g., +9779812345678).");
            return false;
        }

        clearError(phoneInput, "phoneError");
        return true;
    }

    /* =========================
       EMAIL VALIDATION
    ========================== */

    function validateEmail() {

        const email =
            emailInput.value.trim();


        /*
         * Requires:
         * something@something.com
         */

        const emailPattern =
            /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/i;


        if (email === "") {

            showError(
                emailInput,
                "emailError",
                "Please enter your email."
            );

            return false;

        }


        if (!emailPattern.test(email)) {

            showError(
                emailInput,
                "emailError",
                "Please enter a valid email address."
            );

            return false;

        }


        clearError(
            emailInput,
            "emailError"
        );

        return true;

    }


    /* =========================
       PASSWORD VALIDATION
    ========================== */

    function validatePassword() {

        const password =
            passwordInput.value;


        /*
         * At least 8 characters
         */

        if (password.length < 8) {

            showError(
                passwordInput,
                "passwordError",
                "Password must contain at least 8 characters."
            );

            return false;

        }


        /*
         * At least one uppercase letter
         */

        if (!/[A-Z]/.test(password)) {

            showError(
                passwordInput,
                "passwordError",
                "Password must contain at least one uppercase letter."
            );

            return false;

        }


        /*
         * At least one lowercase letter
         */

        if (!/[a-z]/.test(password)) {

            showError(
                passwordInput,
                "passwordError",
                "Password must contain at least one lowercase letter."
            );

            return false;

        }


        /*
         * At least one number
         */

        if (!/[0-9]/.test(password)) {

            showError(
                passwordInput,
                "passwordError",
                "Password must contain at least one number."
            );

            return false;

        }


        /*
         * At least one special character
         */

        if (!/[@#$%^&*!]/.test(password)) {

            showError(
                passwordInput,
                "passwordError",
                "Password must contain a special character such as @, #, $, %, ^, or &."
            );

            return false;

        }


        clearError(
            passwordInput,
            "passwordError"
        );

        return true;

    }


    /* =========================
       CONFIRM PASSWORD
    ========================== */

    function validateConfirmPassword() {

        const password =
            passwordInput.value;

        const confirmPassword =
            confirmPasswordInput.value;


        if (confirmPassword === "") {

            showError(
                confirmPasswordInput,
                "confirmPasswordError",
                "Please confirm your password."
            );

            return false;

        }


        if (password !== confirmPassword) {

            showError(
                confirmPasswordInput,
                "confirmPasswordError",
                "Passwords do not match."
            );

            return false;

        }


        clearError(
            confirmPasswordInput,
            "confirmPasswordError"
        );

        return true;

    }


    /* =========================
       LIVE VALIDATION
    ========================== */

    nameInput.addEventListener(
        "input",
        validateName
    );

    dobInput.addEventListener(
        "blur",
        validateDob
    );

    emailInput.addEventListener(
        "input",
        validateEmail
    );

    passwordInput.addEventListener(
        "input",
        function () {

            validatePassword();

            if (
                confirmPasswordInput.value !== ""
            ) {

                validateConfirmPassword();

            }

        }
    );

    confirmPasswordInput.addEventListener(
        "input",
        validateConfirmPassword
    );


    /* =========================
       REGISTRATION
    ========================== */

    registrationForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const validName =
                validateName();

            const validDob =
                validateDob();

            const validPhone =
                validatePhone();

            const validEmail =
                validateEmail();

            const validPassword =
                validatePassword();

            const validConfirmPassword =
                validateConfirmPassword();


            /*
             * Stop registration if
             * any validation fails.
             */

            if (
                !validName ||
                !validDob ||
                !validPhone ||
                !validEmail ||
                !validPassword ||
                !validConfirmPassword
            ) {

                alert(
                    "Please correct the errors before creating your account."
                );

                return;

            }


            /* =========================
               GET VALUES
            ========================== */

            const name =
                nameInput.value.trim();

            const dob =
                dobInput.value;
                
            const phone =
                phoneInput.value.trim();

            const email =
                emailInput.value.trim().toLowerCase();

            const password =
                passwordInput.value;

            /* =========================
               SEND TO API
            ========================== */

            fetch('api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: name,
                    dob: dob,
                    phone: phone,
                    email: email,
                    password: password
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        // Show email-specific errors on the email field
                        if (data.error.toLowerCase().includes('email')) {
                            showError(emailInput, "emailError", data.error);
                        } else {
                            document.getElementById("formError").textContent = data.error;
                        }
                    } else if (data.success) {
                        localStorage.setItem("periodaLoggedIn", "true");
                        localStorage.setItem("periodaCurrentUser", JSON.stringify(data.user));
                        alert("Account created successfully! Welcome to your dashboard.");
                        window.location.href = "dashboard.html";
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById("formError").textContent = "Unable to connect to the server. Please make sure the server is running.";
                });

        }
    );

});