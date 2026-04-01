<?php
//update-user-status.php
require_once '../config/config.php';
require_once '../lib/functions.php';

// Add CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$user_id = $input['user_id'] ?? null;
$user_uid = $input['user_uid'] ?? null;
$user_name = $input['user_name'] ?? null;
$is_online = $input['is_online'] ?? 0;
$current_page = $input['current_page'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

try {
    global $conn;
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check if record exists
    $checkStmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ?");
    if (!$checkStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->fetch_assoc();
    $checkStmt->close();
    
    $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE user_activity_log 
                                SET last_activity = NOW(), 
                                    is_online = ?, 
                                    current_page = ?,
                                    ip_address = ?,
                                    user_agent = ?
                                WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("isssi", $is_online, $current_page, $ip_address, $user_agent, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO user_activity_log 
                                (user_id, user_uid, user_name, last_activity, is_online, current_page, ip_address, user_agent) 
                                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param("ississs", $user_id, $user_uid, $user_name, $is_online, $current_page, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>