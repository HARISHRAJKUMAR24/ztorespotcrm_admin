<?php
require_once '../config/config.php';
require_once '../lib/functions.php';

header('Content-Type: application/json');

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
    
    // Check if record exists
    $checkStmt = $conn->prepare("SELECT id FROM user_activity_log WHERE user_id = ?");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $exists = $result->fetch_assoc();
    $checkStmt->close();
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE user_activity_log 
                                SET last_activity = NOW(), 
                                    is_online = ?, 
                                    current_page = ?,
                                    ip_address = ?,
                                    user_agent = ?
                                WHERE user_id = ?");
        $stmt->bind_param("isssi", $is_online, $current_page, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO user_activity_log 
                                (user_id, user_uid, user_name, last_activity, is_online, current_page, ip_address, user_agent) 
                                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
        $stmt->bind_param("ississs", $user_id, $user_uid, $user_name, $is_online, $current_page, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
        $stmt->execute();
        $stmt->close();
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>