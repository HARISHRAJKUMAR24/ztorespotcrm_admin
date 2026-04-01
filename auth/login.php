<?php
require_once "../config/config.php";
require_once "../lib/functions.php";

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: " . MAIN_URL . "index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>

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

        .login-container {
            background: #fff;
            padding: 40px;
            width: 100%;
            max-width: 400px;
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

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            color: #333;
            margin-bottom: 5px;
        }

        .login-header p {
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

        .login-btn {
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

        .login-btn:hover {
            background: #4338ca;
        }

        .login-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .signup-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #4f46e5;
            font-size: 13px;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .login-footer {
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

        .login-btn.loading .spinner {
            display: inline-block;
        }

        .login-btn.loading span {
            display: none;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .remember-me label {
            color: #555;
            font-size: 14px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
        }
    </style>

    <script>
        const MAIN_URL = "<?= MAIN_URL ?>";
    </script>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Welcome Back! 👋</h2>
            <p>Sign in to your account</p>
        </div>

        <form id="loginForm">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="admin@ztorespot.com" required>
            </div>

            <div class="input-group password-toggle">
                <label>Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" value="Ztorespotcrm@2026" required>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <div class="forgot-password">
                <a href="#" onclick="handleForgotPassword()">Forgot Password?</a>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                <span>Sign In</span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="signup-link">
            Don't have an account? <a href="<?= MAIN_URL ?>auth/signup.php">Sign Up</a>
        </div>

        <div class="login-footer">
            © 2026 <?= APP_NAME ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= MAIN_URL ?>js/auth/login.js"></script>
</body>

</html>