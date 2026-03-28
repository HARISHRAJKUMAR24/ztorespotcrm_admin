<?php
require_once "../config/config.php";
require_once "../lib/functions.php";

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: " . MAIN_URL . "dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= APP_NAME ?></title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4f46e5, #9333ea);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .forgot-container {
            background: #fff;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .forgot-header p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 0;
        }

        .step {
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: #4f46e5;
            color: white;
        }

        .step.completed .step-number {
            background: #10b981;
            color: white;
        }

        .step-label {
            font-size: 12px;
            color: #666;
        }

        .step.active .step-label {
            color: #4f46e5;
            font-weight: 500;
        }

        .step.completed .step-label {
            color: #10b981;
        }

        .form-step {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .form-step.active {
            display: block;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            color: #555;
            margin-bottom: 6px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            outline: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .otp-input-group {
            display: flex;
            gap: 10px;
        }

        .otp-input-group input {
            flex: 1;
            text-align: center;
            font-size: 24px;
            letter-spacing: 4px;
        }

        .resend-otp {
            text-align: center;
            margin-top: 15px;
        }

        .resend-otp button {
            background: none;
            border: none;
            color: #4f46e5;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .resend-otp button:disabled {
            color: #ccc;
            cursor: not-allowed;
        }

        .timer {
            color: #666;
            font-size: 13px;
            margin-top: 10px;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn:hover {
            background: #4338ca;
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6b7280;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .back-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .btn.loading .spinner {
            display: inline-block;
        }

        .btn.loading span {
            display: none;
        }

        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 5px;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }

        @media (max-width: 480px) {
            .forgot-container {
                padding: 30px 20px;
            }
            
            .otp-input-group input {
                font-size: 20px;
                padding: 10px;
            }
        }
    </style>

    <script>
        const MAIN_URL = "<?= MAIN_URL ?>";
    </script>
</head>

<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h2>🔐 Forgot Password?</h2>
            <p>Don't worry! We'll help you reset your password</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div class="step-label">Verify Email</div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div class="step-label">Verify OTP</div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div class="step-label">Reset Password</div>
            </div>
        </div>

        <!-- Step 1: Email Verification -->
        <div class="form-step active" id="step1Form">
            <form id="emailForm">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
                <button type="submit" class="btn" id="verifyEmailBtn">
                    <span>Send OTP</span>
                    <div class="spinner"></div>
                </button>
            </form>
        </div>

        <!-- Step 2: OTP Verification -->
        <div class="form-step" id="step2Form">
            <form id="otpForm">
                <div class="input-group">
                    <label>Enter OTP</label>
                    <div class="otp-input-group">
                        <input type="text" id="otp1" maxlength="1" autocomplete="off" required>
                        <input type="text" id="otp2" maxlength="1" autocomplete="off" required>
                        <input type="text" id="otp3" maxlength="1" autocomplete="off" required>
                        <input type="text" id="otp4" maxlength="1" autocomplete="off" required>
                    </div>
                </div>
                <div class="resend-otp">
                    <button type="button" id="resendOtpBtn" disabled>Resend OTP</button>
                </div>
                <div class="timer" id="timer">Resend available in 60s</div>
                <button type="submit" class="btn" id="verifyOtpBtn">
                    <span>Verify OTP</span>
                    <div class="spinner"></div>
                </button>
                <button type="button" class="btn btn-secondary" id="backToEmailBtn">
                    <span>Back</span>
                </button>
            </form>
        </div>

        <!-- Step 3: Reset Password -->
        <div class="form-step" id="step3Form">
            <form id="resetPasswordForm">
                <div class="input-group">
                    <label>New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                </div>
                <div class="input-group">
                    <label>Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn" id="resetPasswordBtn">
                    <span>Reset Password</span>
                    <div class="spinner"></div>
                </button>
                <button type="button" class="btn btn-secondary" id="backToOtpBtn">
                    <span>Back</span>
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="<?= MAIN_URL ?>login.php">← Back to Login</a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= MAIN_URL ?>js/auth/forgot-password.js"></script>
</body>

</html>