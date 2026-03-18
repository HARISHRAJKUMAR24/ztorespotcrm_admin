/**
 * Login page JavaScript
 * Handles form submission, validation, and AJAX login
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("Login.js loaded");
    console.log("MAIN_URL:", MAIN_URL);

    const loginForm = document.getElementById("loginForm");
    const loginBtn = document.getElementById("loginBtn");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");

    // Auto-focus email field
    if (emailInput) {
        emailInput.focus();
    }

    // Toggle password visibility
    window.togglePassword = function () {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.querySelector('.toggle-password');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.textContent = '🔒';
        } else {
            passwordInput.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    };

    // Handle forgot password
    window.handleForgotPassword = function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Reset Password',
            text: 'Please contact your administrator to reset your password.',
            icon: 'info',
            confirmButtonColor: '#4f46e5',
            background: '#fff'
        });
    };

    // Handle login form submission
    if (loginForm) {
        loginForm.addEventListener("submit", async function (e) {
            e.preventDefault();

            console.log("Login form submitted");

            // Get form values
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const remember = document.getElementById("remember") ? document.getElementById("remember").checked : false;

            // Validate inputs
            if (!validateForm(email, password)) {
                return;
            }

            // Show loading state
            setLoadingState(true);

            // Prepare form data
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            formData.append('remember', remember ? '1' : '0');

            try {
                const loginUrl = MAIN_URL + "ajax/auth/login.php";
                console.log("Fetching:", loginUrl);

                const response = await fetch(loginUrl, {
                    method: "POST",
                    body: formData
                });

                console.log("Response status:", response.status);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log("Response data:", data);

                if (data.success) {
                    // Show success message
                    await Swal.fire({
                        icon: 'success',
                        title: 'Welcome Back!',
                        text: data.message || 'Login successful!',
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#fff',
                        iconColor: '#4f46e5'
                    });

                    // Redirect to dashboard
                    window.location.href = data.redirect || MAIN_URL + "dashboard.php";
                } else {
                    // Show error message
                    showError(data.message || "Login failed. Please check your credentials.");
                    setLoadingState(false);
                }

            } catch (error) {
                console.error("Login error:", error);
                showError("Connection error. Please check your internet and try again.");
                setLoadingState(false);
            }
        });
    }

    /**
     * Validate email and password
     */
    function validateForm(email, password) {
        // Check if fields are empty
        if (!email || !password) {
            showError("Please fill in all fields");
            return false;
        }

        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError("Please enter a valid email address");
            highlightField(emailInput, true);
            return false;
        }

        // Clear any error highlights
        highlightField(emailInput, false);
        highlightField(passwordInput, false);

        return true;
    }

    /**
     * Show error message using SweetAlert
     */
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: message,
            confirmButtonColor: '#4f46e5',
            background: '#fff',
            iconColor: '#ef4444'
        });
    }

    /**
     * Set loading state on button
     */
    function setLoadingState(isLoading) {
        if (isLoading) {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        } else {
            loginBtn.classList.remove('loading');
            loginBtn.disabled = false;
        }
    }

    /**
     * Highlight field on error
     */
    function highlightField(field, isError) {
        if (isError) {
            field.classList.add('error');
        } else {
            field.classList.remove('error');
        }
    }

    // Enter key press handler
    document.addEventListener('keypress', function (e) {
        if (e.key === 'Enter' && !loginBtn.disabled) {
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Add error class styling dynamically
    const style = document.createElement('style');
    style.textContent = `
        .input-group input.error {
            border-color: #ef4444;
            animation: shake 0.3s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    `;
    document.head.appendChild(style);
});