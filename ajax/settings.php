<?php
require_once '../config/config.php';
require_once '../lib/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$currentAdmin = getCurrentAdmin();
$user_id = $currentAdmin['id'];

// Get POST data
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

// Validate
if (empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Username and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if username already exists (excluding current user)
$checkStmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
$checkStmt->bind_param("si", $username, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}
$checkStmt->close();

// Check if email already exists (excluding current user)
$checkStmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
$checkStmt->bind_param("si", $email, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}
$checkStmt->close();

// First check if all columns exist and add them if needed
$columns = ['full_name', 'phone', 'address', 'bio'];
foreach ($columns as $column) {
    $checkColumn = $conn->query("SHOW COLUMNS FROM admin_users LIKE '$column'");
    if ($checkColumn->num_rows == 0) {
        $type = ($column == 'bio' || $column == 'address') ? 'TEXT' : 'VARCHAR(255)';
        $conn->query("ALTER TABLE admin_users ADD COLUMN $column $type DEFAULT NULL");
    }
}

// Update profile
$updateStmt = $conn->prepare("UPDATE admin_users SET full_name = ?, username = ?, email = ?, phone = ?, address = ?, bio = ? WHERE id = ?");
$updateStmt->bind_param("ssssssi", $full_name, $username, $email, $phone, $address, $bio, $user_id);

if ($updateStmt->execute()) {
    // Update session
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_email'] = $email;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>