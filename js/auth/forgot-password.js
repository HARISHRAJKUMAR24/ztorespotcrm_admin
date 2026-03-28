/**
 * Forgot Password JavaScript
 * Handles email verification, OTP verification, and password reset
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("Forgot password JS loaded");

    // DOM Elements
    const step1Form = document.getElementById("step1Form");
    const step2Form = document.getElementById("step2Form");
    const step3Form = document.getElementById("step3Form");
    const step1Indicator = document.getElementById("step1");
    const step2Indicator = document.getElementById("step2");
    const step3Indicator = document.getElementById("step3");

    const emailForm = document.getElementById("emailForm");
    const otpForm = document.getElementById("otpForm");
    const resetPasswordForm = document.getElementById("resetPasswordForm");

    const verifyEmailBtn = document.getElementById("verifyEmailBtn");
    const verifyOtpBtn = document.getElementById("verifyOtpBtn");
    const resetPasswordBtn = document.getElementById("resetPasswordBtn");
    const resendOtpBtn = document.getElementById("resendOtpBtn");
    const backToEmailBtn = document.getElementById("backToEmailBtn");
    const backToOtpBtn = document.getElementById("backToOtpBtn");

    const emailInput = document.getElementById("email");
    const newPasswordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById("confirm_password");

    // OTP inputs
    const otpInputs = [
        document.getElementById("otp1"),
        document.getElementById("otp2"),
        document.getElementById("otp3"),
        document.getElementById("otp4")
    ];

    let currentEmail = "";
    let timerInterval = null;
    let timeLeft = 60;

    // Password strength checker
    function checkPasswordStrength(password) {
        const strengthBar = document.querySelector(".strength-bar-fill");
        const strengthText = document.getElementById("strengthText");
        
        let strength = 0;
        let message = "";
        let color = "";

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                message = "Very Weak";
                color = "#ef4444";
                break;
            case 2:
                message = "Weak";
                color = "#f59e0b";
                break;
            case 3:
                message = "Good";
                color = "#10b981";
                break;
            case 4:
                message = "Strong";
                color = "#10b981";
                break;
            case 5:
                message = "Very Strong";
                color = "#10b981";
                break;
        }

        if (password.length === 0) {
            message = "";
            strengthBar.style.width = "0%";
        } else {
            strengthBar.style.width = (strength * 20) + "%";
        }
        
        strengthBar.style.backgroundColor = color;
        strengthText.textContent = message;
        strengthText.style.color = color;
    }

    // Update step UI
    function updateStep(step) {
        // Hide all forms
        step1Form.classList.remove("active");
        step2Form.classList.remove("active");
        step3Form.classList.remove("active");

        // Update step indicators
        step1Indicator.classList.remove("active", "completed");
        step2Indicator.classList.remove("active", "completed");
        step3Indicator.classList.remove("active", "completed");

        switch(step) {
            case 1:
                step1Form.classList.add("active");
                step1Indicator.classList.add("active");
                break;
            case 2:
                step2Form.classList.add("active");
                step1Indicator.classList.add("completed");
                step2Indicator.classList.add("active");
                break;
            case 3:
                step3Form.classList.add("active");
                step1Indicator.classList.add("completed");
                step2Indicator.classList.add("completed");
                step3Indicator.classList.add("active");
                break;
        }
    }

    // Start OTP timer
    function startTimer() {
        timeLeft = 60;
        const timerElement = document.getElementById("timer");
        resendOtpBtn.disabled = true;

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                timerElement.textContent = "Resend available now";
                resendOtpBtn.disabled = false;
            } else {
                timerElement.textContent = `Resend available in ${timeLeft}s`;
            }
        }, 1000);
    }

    // Auto-focus OTP inputs
    otpInputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            if (e.target.value.length === 1 && index < 3) {
                otpInputs[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && index > 0 && !e.target.value) {
                otpInputs[index - 1].focus();
            }
        });
    });

    // Get OTP value
    function getOtp() {
        return otpInputs.map(input => input.value).join("");
    }

    // Clear OTP inputs
    function clearOtp() {
        otpInputs.forEach(input => input.value = "");
        otpInputs[0].focus();
    }

    // Show error message
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#4f46e5',
            background: '#fff',
            iconColor: '#ef4444'
        });
    }

    // Show success message
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            confirmButtonColor: '#4f46e5',
            background: '#fff',
            iconColor: '#10b981'
        });
    }

    // Set loading state
    function setLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add("loading");
            button.disabled = true;
        } else {
            button.classList.remove("loading");
            button.disabled = false;
        }
    }

    // Step 1: Verify Email
    if (emailForm) {
        emailForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            
            if (!email) {
                showError("Please enter your email address");
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError("Please enter a valid email address");
                return;
            }

            setLoading(verifyEmailBtn, true);

            try {
                const response = await fetch(MAIN_URL + "ajax/auth/forgot-password.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "action=send_otp&email=" + encodeURIComponent(email)
                });

                const data = await response.json();

                if (data.success) {
                    currentEmail = email;
                    showSuccess("OTP sent to your email! Default OTP: 1111");
                    updateStep(2);
                    startTimer();
                    clearOtp();
                } else {
                    showError(data.message || "Failed to send OTP");
                }
            } catch (error) {
                console.error("Error:", error);
                showError("Connection error. Please try again.");
            } finally {
                setLoading(verifyEmailBtn, false);
            }
        });
    }

    // Step 2: Verify OTP
    if (otpForm) {
        otpForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const otp = getOtp();
            
            if (otp.length !== 4) {
                showError("Please enter the 4-digit OTP");
                return;
            }

            setLoading(verifyOtpBtn, true);

            try {
                const response = await fetch(MAIN_URL + "ajax/auth/forgot-password.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "action=verify_otp&email=" + encodeURIComponent(currentEmail) + "&otp=" + encodeURIComponent(otp)
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess("OTP verified! Please set your new password");
                    updateStep(3);
                } else {
                    showError(data.message || "Invalid OTP");
                }
            } catch (error) {
                console.error("Error:", error);
                showError("Connection error. Please try again.");
            } finally {
                setLoading(verifyOtpBtn, false);
            }
        });
    }

    // Resend OTP
    if (resendOtpBtn) {
        resendOtpBtn.addEventListener("click", async function() {
            setLoading(resendOtpBtn, true);

            try {
                const response = await fetch(MAIN_URL + "ajax/auth/forgot-password.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "action=send_otp&email=" + encodeURIComponent(currentEmail)
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess("OTP resent successfully! Default OTP: 1111");
                    startTimer();
                    clearOtp();
                } else {
                    showError(data.message || "Failed to resend OTP");
                }
            } catch (error) {
                console.error("Error:", error);
                showError("Connection error. Please try again.");
            } finally {
                setLoading(resendOtpBtn, false);
            }
        });
    }

    // Step 3: Reset Password
    if (resetPasswordForm) {
        // Password strength check
        if (newPasswordInput) {
            newPasswordInput.addEventListener("input", function() {
                checkPasswordStrength(this.value);
            });
        }

        resetPasswordForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (!newPassword || !confirmPassword) {
                showError("Please fill in all fields");
                return;
            }
            
            if (newPassword.length < 6) {
                showError("Password must be at least 6 characters long");
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showError("Passwords do not match");
                return;
            }

            setLoading(resetPasswordBtn, true);

            try {
                const response = await fetch(MAIN_URL + "ajax/auth/forgot-password.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "action=reset_password&email=" + encodeURIComponent(currentEmail) + "&password=" + encodeURIComponent(newPassword)
                });

                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Password Reset Successfully!',
                        text: 'You can now login with your new password',
                        confirmButtonColor: '#4f46e5',
                        background: '#fff'
                    });
                    
                    // Redirect to login page
                    window.location.href = MAIN_URL + "auth/login.php";
                } else {
                    showError(data.message || "Failed to reset password");
                }
            } catch (error) {
                console.error("Error:", error);
                showError("Connection error. Please try again.");
            } finally {
                setLoading(resetPasswordBtn, false);
            }
        });
    }

    // Navigation buttons
    if (backToEmailBtn) {
        backToEmailBtn.addEventListener("click", function() {
            updateStep(1);
            if (timerInterval) clearInterval(timerInterval);
        });
    }

    if (backToOtpBtn) {
        backToOtpBtn.addEventListener("click", function() {
            updateStep(2);
        });
    }
});