<?php
require_once '../config/config.php';
require_once '../lib/functions.php';

// Check if admin is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user_id is provided
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = intval($_POST['user_id']);

// Get the sales team member details
$conn = $conn();

$sql = "SELECT id, user_uid, name, phone, email, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $result->fetch_assoc();

// Generate a unique token for this direct login
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store the direct login token in a temporary table (create if not exists)
$createTableSQL = "CREATE TABLE IF NOT EXISTS direct_login_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

$conn->query($createTableSQL);

// Clean up expired tokens
$cleanSQL = "DELETE FROM direct_login_tokens WHERE expires_at < NOW() OR used = TRUE";
$conn->query($cleanSQL);

// Store the token
$insertSQL = "INSERT INTO direct_login_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
$insertStmt = $conn->prepare($insertSQL);
$insertStmt->bind_param("iss", $user['id'], $token, $expires_at);
$insertStmt->execute();

// Get the sales panel URL
$salesPanelURL = "http://localhost/ztorespot_sales_team_panel/index.php?direct_login=" . $token;

// Return success with the URL
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'redirect_url' => $salesPanelURL,
    'user_name' => $user['name']
]);

$stmt->close();
$conn->close();
?>