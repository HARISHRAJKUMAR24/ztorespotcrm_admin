<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../config/config.php';
require_once '../lib/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $target_id = isset($_POST['target_id']) ? intval($_POST['target_id']) : 0;
    $user_uid = isset($_POST['user_uid']) && !empty($_POST['user_uid']) ? $_POST['user_uid'] : null;
    $target_type = isset($_POST['target_type']) ? $_POST['target_type'] : 'individual';
    $target_amount = isset($_POST['target_amount']) ? floatval($_POST['target_amount']) : 0;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    $current_admin = getCurrentAdmin();
    
    // For team targets, set user_uid to NULL
    if ($target_type === 'team') {
        $user_uid = null;
        error_log("Creating TEAM target - User UID set to NULL");
    } else {
        error_log("Creating INDIVIDUAL target - User UID: $user_uid (from users table)");
    }
    
    // Validate inputs
    if ($target_type === 'individual' && ($user_uid === null || empty($user_uid))) {
        echo json_encode(['success' => false, 'message' => 'Please select a sales person']);
        exit;
    }
    
    if ($target_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid target amount']);
        exit;
    }
    
    if (empty($start_date) || empty($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Please select start and end dates']);
        exit;
    }
    
    if (strtotime($start_date) > strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        exit;
    }
    
    if ($target_id > 0) {
        // Update existing target
        $stmt = $conn->prepare("UPDATE target_settings SET user_uid = ?, target_amount = ?, start_date = ?, end_date = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sdsssi", $user_uid, $target_amount, $start_date, $end_date, $notes, $target_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Target updated successfully']);
        } else {
            throw new Exception("Failed to update target: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Check for overlapping targets
        if ($target_type === 'team') {
            $checkStmt = $conn->prepare("SELECT id FROM target_settings WHERE target_type = 'team' AND status = 'active' AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?))");
            $checkStmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
        } else {
            // For individual target, check if this specific user already has a target for this period
            $checkStmt = $conn->prepare("SELECT id FROM target_settings WHERE user_uid = ? AND target_type = 'individual' AND status = 'active' AND ((start_date BETWEEN ? AND ?) OR (end_date BETWEEN ? AND ?))");
            $checkStmt->bind_param("sssss", $user_uid, $start_date, $end_date, $start_date, $end_date);
        }
        
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A target already exists for this period']);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
        
        // Create new target
        $stmt = $conn->prepare("INSERT INTO target_settings (user_uid, target_type, target_amount, start_date, end_date, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsssi", $user_uid, $target_type, $target_amount, $start_date, $end_date, $notes, $current_admin['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Target created successfully']);
        } else {
            throw new Exception("Failed to create target: " . $stmt->error);
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Save target error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>