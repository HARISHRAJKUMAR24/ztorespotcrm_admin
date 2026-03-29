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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $plan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($plan_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid plan ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plan = $result->fetch_assoc();
    $stmt->close();
    
    if ($plan) {
        echo json_encode(['success' => true, 'plan' => $plan]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Plan not found']);
    }
    
} catch (Exception $e) {
    error_log("Get plan error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

$conn->close();
?>