/**
 * Signup page JavaScript
 * Handles form submission, validation, and AJAX signup
 */

document.addEventListener("DOMContentLoaded", function() {
    console.log("Signup.js loaded");
    
    const signupForm = document.getElementById("signupForm");
    const signupBtn = document.getElementById("signupBtn");
    const usernameInput = document.getElementById("username");
    const emailInput = document.getElementById("email");
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirmPassword");
    const secretKeyInput = document.getElementById("secretKey");
    
    // Auto-focus username field
    if (usernameInput) {
        usernameInput.focus();
    }
    
    // Toggle password visibility
    window.togglePassword = function(fieldId) {
        const input = document.getElementById(fieldId);
        const toggleIcon = input.nextElementSibling;
        
        if (input.type === 'password') {
            input.type = 'text';
            toggleIcon.textContent = '🔒';
        } else {
            input.type = 'password';
            toggleIcon.textContent = '👁️';
        }
    };
    
    // Password strength checker
    if (passwordInput) {
        passwordInput.addEventListener("input", checkPasswordStrength);
    }
    
    // Confirm password match
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener("input", checkPasswordMatch);
    }
    
    function checkPasswordStrength() {
        const password = passwordInput.value;
        const strengthBar = document.getElementById("passwordStrength");
        
        // Requirements
        const hasLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Update requirement indicators
        updateRequirement("reqLength", hasLength);
        updateRequirement("reqUpper", hasUpper);
        updateRequirement("reqLower", hasLower);
        updateRequirement("reqNumber", hasNumber);
        updateRequirement("reqSpecial", hasSpecial);
        
        // Calculate strength
        const requirements = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial];
        const metCount = requirements.filter(Boolean).length;
        
        // Update strength bar
        strengthBar.className = "password-strength-bar";
        if (metCount <= 2) {
            strengthBar.classList.add("strength-weak");
        } else if (metCount <= 4) {
            strengthBar.classList.add("strength-medium");
        } else {
            strengthBar.classList.add("strength-strong");
        }
    }
    
    function updateRequirement(elementId, isMet) {
        const element = document.getElementById(elementId);
        const icon = element.querySelector('i');
        
        if (isMet) {
            element.classList.add("met");
            icon.textContent = '✅';
        } else {
            element.classList.remove("met");
            icon.textContent = '⚪';
        }
    }
    
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if (confirm.length > 0) {
            if (password === confirm) {
                confirmPasswordInput.style.borderColor = "#10b981";
            } else {
                confirmPasswordInput.style.borderColor = "#ef4444";
            }
        } else {
            confirmPasswordInput.style.borderColor = "#e5e7eb";
        }
    }
    
    // Handle signup form submission
    if (signupForm) {
        signupForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            console.log("Signup form submitted");
            
            // Get form values
            const username = usernameInput.value.trim();
            const email = emailInput.value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const secretKey = secretKeyInput.value;
            
            // Validate inputs
            if (!validateForm(username, email, password, confirmPassword, secretKey)) {
                return;
            }
            
            // Show loading state
            setLoadingState(true);
            
            // Prepare form data
            const formData = new FormData();
            formData.append('username', username);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('secret_key', secretKey);
            
            try {
                const signupUrl = MAIN_URL + "ajax/auth/signup.php";
                console.log("Fetching:", signupUrl);
                
                const response = await fetch(signupUrl, {
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
                        title: 'Account Created!',
                        text: data.message || 'Registration successful! You can now login.',
                        confirmButtonColor: '#4f46e5',
                        background: '#fff',
                        iconColor: '#10b981'
                    });
                    
                    // Redirect to login page
                    window.location.href = data.redirect || MAIN_URL + "login.php";
                } else {
                    // Show error message
                    showError(data.message || "Registration failed. Please try again.");
                    setLoadingState(false);
                }
                
            } catch (error) {
                console.error("Signup error:", error);
                showError("Connection error. Please check your internet and try again.");
                setLoadingState(false);
            }
        });
    }
    
    /**
     * Validate form inputs
     */
    function validateForm(username, email, password, confirmPassword, secretKey) {
        // Check if fields are empty
        if (!username || !email || !password || !confirmPassword || !secretKey) {
            showError("Please fill in all fields");
            return false;
        }
        
        // Validate username
        if (username.length < 3) {
            showError("Username must be at least 3 characters long");
            highlightField(usernameInput, true);
            return false;
        }
        
        // Validate email format
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError("Please enter a valid email address");
            highlightField(emailInput, true);
            return false;
        }
        
        // Validate password strength
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
        if (!passwordRegex.test(password)) {
            showError("Password must be at least 8 characters with uppercase, lowercase, number and special character");
            highlightField(passwordInput, true);
            return false;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            showError("Passwords do not match");
            highlightField(confirmPasswordInput, true);
            return false;
        }
        
        // Clear any error highlights
        highlightField(usernameInput, false);
        highlightField(emailInput, false);
        highlightField(passwordInput, false);
        highlightField(confirmPasswordInput, false);
        highlightField(secretKeyInput, false);
        
        return true;
    }
    
    /**
     * Show error message using SweetAlert
     */
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
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
            signupBtn.classList.add('loading');
            signupBtn.disabled = true;
        } else {
            signupBtn.classList.remove('loading');
            signupBtn.disabled = false;
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
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !signupBtn.disabled) {
            signupForm.dispatchEvent(new Event('submit'));
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
        
        .input-group input:valid {
            border-color: #10b981;
        }
    `;
    document.head.appendChild(style);
});