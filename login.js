/* =========================================
   PERIODA LOGIN JAVASCRIPT
   ========================================= */


/* =========================
   ELEMENTS
   ========================= */

const loginForm =
    document.getElementById("loginForm");

const usernameInput =
    document.getElementById("username");

const passwordInput =
    document.getElementById("password");

const showPassword =
    document.getElementById("togglePassword");

const errorMessage =
    document.getElementById("errorMessage");

const forgotPassword =
    document.getElementById("forgotPassword");

const googleLogin =
    document.getElementById("googleLogin");

const appleLogin =
    document.getElementById("appleLogin");


/* =========================
   SHOW / HIDE PASSWORD
   ========================= */

if (showPassword) {
    showPassword.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            showPassword.textContent = "Hide";

        } else {

            passwordInput.type = "password";

            showPassword.textContent = "Show";

        }

    });
}


/* =========================
   ROLE SELECTION
   ========================= */

let currentRole = "user";



/* =========================
   LOGIN
   ========================= */

if (loginForm) {
loginForm.addEventListener("submit", function (event) {

    event.preventDefault();


    /* Clear previous error */

    if (errorMessage) {
        errorMessage.textContent = "";
    }


    /* Get values */

    const username =
        usernameInput.value.trim().toLowerCase();

    const password =
        passwordInput.value;

    if (!username || !password) {
        if (errorMessage) {
            errorMessage.textContent = "Please enter both email and password.";
        }
        return;
    }


    /* =========================
       SEND TO API
       ========================= */

    fetch('api/login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            username: username,
            password: password,
            role: currentRole
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            if (errorMessage) errorMessage.textContent = data.error;
        } else if (data.success) {
            if (data.role === "admin") {
                localStorage.setItem("periodaAdmin", JSON.stringify(data.admin));
                window.location.href = "admin-dashboard.html";
            } else {
                localStorage.setItem("periodaLoggedIn", "true");
                localStorage.setItem("periodaCurrentUser", JSON.stringify(data.user));
                window.location.href = "dashboard.html";
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (errorMessage) errorMessage.textContent = "An error occurred. Please try again.";
    });

});
}


/* =========================
   FORGOT PASSWORD
   ========================= */

if (forgotPassword) {
    forgotPassword.addEventListener("click", function (event) {

        event.preventDefault();

        alert(
            "Password recovery will be available soon."
        );

    });
}

