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
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
    $plan_name = isset($_POST['plan_name']) ? trim($_POST['plan_name']) : '';
    $duration = isset($_POST['duration']) ? trim($_POST['duration']) : '';
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $gst_percentage = isset($_POST['gst_percentage']) ? floatval($_POST['gst_percentage']) : 0;
    $gst_amount = isset($_POST['gst_amount']) ? floatval($_POST['gst_amount']) : 0;
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
    
    $current_admin = getCurrentAdmin();
    
    // Validate inputs
    if (empty($plan_name)) {
        echo json_encode(['success' => false, 'message' => 'Plan name is required']);
        exit;
    }
    
    if (empty($duration)) {
        echo json_encode(['success' => false, 'message' => 'Duration is required']);
        exit;
    }
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid amount']);
        exit;
    }
    
    if ($plan_id > 0) {
        // Update existing plan
        $stmt = $conn->prepare("UPDATE subscription_plans SET plan_name = ?, duration = ?, amount = ?, gst_percentage = ?, gst_amount = ?, total_amount = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssddddii", $plan_name, $duration, $amount, $gst_percentage, $gst_amount, $total_amount, $status, $plan_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Plan updated successfully']);
        } else {
            throw new Exception("Failed to update plan: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Check if plan with same name and duration exists
        $checkStmt = $conn->prepare("SELECT id FROM subscription_plans WHERE plan_name = ? AND duration = ?");
        $checkStmt->bind_param("ss", $plan_name, $duration);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A plan with this name and duration already exists']);
            $checkStmt->close();
            exit;
        }
        $checkStmt->close();
        
        // Create new plan
        $stmt = $conn->prepare("INSERT INTO subscription_plans (plan_name, duration, amount, gst_percentage, gst_amount, total_amount, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddddii", $plan_name, $duration, $amount, $gst_percentage, $gst_amount, $total_amount, $status, $current_admin['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Plan created successfully']);
        } else {
            throw new Exception("Failed to create plan: " . $stmt->error);
        }
        $stmt->close();
    }
    
} catch (Exception $e) {
    error_log("Save plan error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>