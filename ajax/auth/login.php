<?php
require_once '../../config/config.php';
require_once '../../lib/functions.php';

// Enable error logging
error_log("=== Login Attempt ===");

// Set header for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']) ? $_POST['remember'] : '0';

// Log attempt (without password)
error_log("Login attempt for email: " . $email . " - Remember me: " . $remember);

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter both email and password'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address'
    ]);
    exit;
}

try {
    // Query database for admin user
    $stmt = $conn->prepare("SELECT id, username, email, password FROM admin_users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("No user found with email: " . $email);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }

    $admin = $result->fetch_assoc();
    error_log("User found: " . $admin['username']);

    // Verify password
    if (password_verify($password, $admin['password'])) {
        error_log("Password verification SUCCESSFUL");

        // Check if rehashing is needed
        if (password_needs_rehash($admin['password'], PASSWORD_DEFAULT)) {
            error_log("Password needs rehashing - updating...");
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newHash, $admin['id']);
            $updateStmt->execute();
            $updateStmt->close();
            error_log("Password rehashed and updated");
        }

        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['logged_in'] = true;

        // Handle Remember Me token (30 days)
        if ($remember == '1') {
            // Generate secure random token
            $remember_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Store token in database
            $tokenStmt = $conn->prepare("UPDATE admin_users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $tokenStmt->bind_param("ssi", $remember_token, $token_expiry, $admin['id']);
            $tokenStmt->execute();
            $tokenStmt->close();

            // Set cookie for 30 days
            setcookie('remember_token', $remember_token, time() + (86400 * 30), '/', '', false, true); // 30 days
            setcookie('user_id', $admin['id'], time() + (86400 * 30), '/', '', false, true);

            error_log("Remember me token set for user ID: " . $admin['id'] . " - Expires: " . $token_expiry);
        } else {
            // Clear any existing remember me tokens
            $clearStmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
            $clearStmt->bind_param("i", $admin['id']);
            $clearStmt->execute();
            $clearStmt->close();

            // Clear cookies
            setcookie('remember_token', '', time() - 3600, '/');
            setcookie('user_id', '', time() - 3600, '/');
        }

        error_log("Session set for user ID: " . $admin['id']);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => MAIN_URL . 'dashboard.php'
        ]);
    } else {
        error_log("Password verification FAILED");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}

$conn->close();
