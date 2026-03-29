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
    $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
    
    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan ID']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE subscription_plans SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $plan_id);
    
    if ($stmt->execute()) {
        $action = $status == 1 ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "Plan {$action} successfully"]);
    } else {
        throw new Exception("Failed to update status");
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Toggle plan status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

$conn->close();
?>