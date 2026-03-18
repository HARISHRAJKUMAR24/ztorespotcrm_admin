<?php
require_once "../config/config.php";
require_once "../lib/functions.php";

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: " . MAIN_URL . "dashboard.php");
    exit;
}

// Generate a random string for the secret key field (optional)
$secretKeyPlaceholder = str_repeat('•', 16);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup - <?= APP_NAME ?></title>

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

        .signup-container {
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

        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .signup-header h2 {
            color: #333;
            margin-bottom: 5px;
        }

        .signup-header p {
            color: #666;
            font-size: 14px;
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

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 38px;
            cursor: pointer;
            color: #888;
            font-size: 18px;
        }

        .secret-key-info {
            background: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4b5563;
            border-left: 4px solid #4f46e5;
        }

        .secret-key-info i {
            color: #4f46e5;
            margin-right: 5px;
        }

        .signup-btn {
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

        .signup-btn:hover {
            background: #4338ca;
        }

        .signup-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .signup-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #888;
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

        .signup-btn.loading .spinner {
            display: inline-block;
        }

        .signup-btn.loading span {
            display: none;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }

        .strength-weak {
            background: #ef4444;
            width: 33.33%;
        }

        .strength-medium {
            background: #f59e0b;
            width: 66.66%;
        }

        .strength-strong {
            background: #10b981;
            width: 100%;
        }

        .requirements {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .requirement.met {
            color: #10b981;
        }

        .requirement i {
            font-size: 12px;
        }

        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
        }
    </style>

    <script>
        const MAIN_URL = "<?= MAIN_URL ?>";
        // This is just for reference - actual validation happens on server
        const SECRET_KEY = "ZTORESPOT_@_SALES_CRM_ADMIN_2026";
    </script>
</head>

<body>
    <div class="signup-container">
        <div class="signup-header">
            <h2>Create Account</h2>
        </div>



        <form id="signupForm">
            <div class="input-group">
                <label>Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>

            <div class="input-group password-toggle">
                <label>Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
                <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                <div class="password-strength">
                    <div class="password-strength-bar" id="passwordStrength"></div>
                </div>
            </div>

            <div class="input-group password-toggle">
                <label>Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                <span class="toggle-password" onclick="togglePassword('confirmPassword')">👁️</span>
            </div>

            <div class="input-group">
                <label>Secret Key</label>
                <input type="password" id="secretKey" name="secretKey" placeholder="Enter secret key" required>
                <div class="requirements">
                    <span class="requirement" id="secretKeyHint">
                        <i>🔑</i> Required for registration
                    </span>
                </div>
            </div>

            <div class="requirements" id="passwordRequirements">
                <span class="requirement" id="reqLength">
                    <i>⚪</i> 8+ characters
                </span>
                <span class="requirement" id="reqUpper">
                    <i>⚪</i> Uppercase
                </span>
                <span class="requirement" id="reqLower">
                    <i>⚪</i> Lowercase
                </span>
                <span class="requirement" id="reqNumber">
                    <i>⚪</i> Number
                </span>
                <span class="requirement" id="reqSpecial">
                    <i>⚪</i> Special char
                </span>
            </div>

            <button type="submit" class="signup-btn" id="signupBtn">
                <span>Create Account</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="<?= MAIN_URL ?>login.php">Sign In</a>
        </div>

        <div class="signup-footer">
            © 2026 <?= APP_NAME ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= MAIN_URL ?>js/auth/signup.js"></script>
</body>

</html>