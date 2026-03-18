<?php
// lib/functions.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
global $conn;
require_once __DIR__ . '/../config/config.php';

/**
 * Check for Remember Me token and auto-login
 * Call this function at the beginning of protected pages
 */
function checkRememberToken()
{
    global $conn;

    // If already logged in via session, no need to check token
    if (isLoggedIn()) {
        return true;
    }

    // Check if remember me cookies exist
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = intval($_COOKIE['user_id']);

        // Query database for valid token
        $stmt = $conn->prepare("SELECT id, username, email, remember_token, token_expiry FROM admin_users WHERE id = ? AND remember_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $current_time = date('Y-m-d H:i:s');

            // Check if token is still valid (not expired)
            if ($user['token_expiry'] && $user['token_expiry'] > $current_time) {
                // Token is valid - log the user in
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['remember_token_login'] = true;

                error_log("Auto-login via remember token for user: " . $user['username']);

                $stmt->close();
                return true;
            } else {
                // Token expired - clear it
                $clearStmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
                $clearStmt->bind_param("i", $user_id);
                $clearStmt->execute();
                $clearStmt->close();

                // Clear cookies
                setcookie('remember_token', '', time() - 3600, '/');
                setcookie('user_id', '', time() - 3600, '/');

                error_log("Remember token expired for user ID: " . $user_id);
            }
        }
        $stmt->close();
    }

    return false;
}

/**
 * Template loader function
 */
function template($name)
{
    $file = __DIR__ . "/../templates/" . $name . ".php";

    if (file_exists($file)) {
        require $file;
    } else {
        echo "<!-- Template not found: $name -->";
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect if not logged in
 */
function requireLogin()
{
    // First check if remember token can log them in
    checkRememberToken();

    if (!isLoggedIn()) {
        header("Location: " . MAIN_URL . "auth/login.php");
        exit;
    }
}

/**
 * Get current admin info
 */
function getCurrentAdmin()
{
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'email' => $_SESSION['admin_email']
        ];
    }
    return null;
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Logout function - clears session and remember me tokens
 */
function logout()
{
    global $conn;

    // Clear remember me token from database if user is logged in
    if (isLoggedIn()) {
        $user_id = $_SESSION['admin_id'];
        $clearStmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
        $clearStmt->bind_param("i", $user_id);
        $clearStmt->execute();
        $clearStmt->close();
    }

    // Clear remember me cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_id', '', time() - 3600, '/');

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}
