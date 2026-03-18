<?php
require_once '../../config/config.php';
require_once '../../lib/functions.php';

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

// Enable error logging
error_log("=== Signup Attempt ===");

// Define the secret key - ONLY THIS EXACT KEY WILL WORK
define('SECRET_KEY', 'ZTORESPOT_@_SALES_CRM_ADMIN_2026');

// Get POST data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$secretKey = isset($_POST['secret_key']) ? trim($_POST['secret_key']) : '';

// Log attempt (without password)
error_log("Signup attempt - Username: $username, Email: $email");

// Validate input
if (empty($username) || empty($email) || empty($password) || empty($secretKey)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all fields'
    ]);
    exit;
}

// Validate username
if (strlen($username) < 3) {
    echo json_encode([
        'success' => false,
        'message' => 'Username must be at least 3 characters long'
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

// Validate password strength
if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters with uppercase, lowercase, number and special character'
    ]);
    exit;
}

// Check if email already exists
$checkStmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Email already registered'
    ]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Check if username already exists
$checkStmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Username already taken'
    ]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

/**
 * STRICT SECRET KEY VERIFICATION
 * ONLY EXACT MATCH WILL WORK - NO EXCEPTIONS!
 */

// Convert both to same case for comparison (case-insensitive but exact characters)
$expectedKey = strtoupper(SECRET_KEY);
$providedKey = strtoupper(trim($secretKey));

// Log for debugging
error_log("Expected key: " . $expectedKey);
error_log("Provided key: " . $providedKey);

// STRICT CHECK - Must be EXACT match
if ($providedKey !== $expectedKey) {
    error_log("❌ SECRET KEY MISMATCH - Signup blocked");
    error_log("Expected: '" . $expectedKey . "'");
    error_log("Provided: '" . $providedKey . "'");

    // Calculate and log length for debugging
    error_log("Expected length: " . strlen($expectedKey) . " characters");
    error_log("Provided length: " . strlen($providedKey) . " characters");

    // Additional debug - show character by character comparison
    $debug_msg = "Character comparison: ";
    for ($i = 0; $i < max(strlen($expectedKey), strlen($providedKey)); $i++) {
        $expectedChar = isset($expectedKey[$i]) ? $expectedKey[$i] : '?';
        $providedChar = isset($providedKey[$i]) ? $providedKey[$i] : '?';
        $match = ($expectedChar === $providedChar) ? '✓' : '✗';
        $debug_msg .= "[$i:$expectedChar=$providedChar$match] ";
    }
    error_log($debug_msg);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid secret key. Registration not allowed.'
    ]);
    exit;
}

// If we reach here, the secret key is CORRECT
error_log("✅ SECRET KEY VERIFIED - Signup proceeding");

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new admin user
$insertStmt = $conn->prepare("INSERT INTO admin_users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
$insertStmt->bind_param("sss", $username, $email, $hashedPassword);

if ($insertStmt->execute()) {
    $newUserId = $insertStmt->insert_id;
    error_log("✅ New admin user created with ID: $newUserId");

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You can now login.',
        'redirect' => MAIN_URL . 'auth/login.php'
    ]);
} else {
    error_log("❌ Failed to create user: " . $insertStmt->error);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again.'
    ]);
}

$insertStmt->close();
$conn->close();
