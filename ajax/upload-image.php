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

// Check if file was uploaded
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded or upload error';
    if (isset($_FILES['profile_image'])) {
        switch ($_FILES['profile_image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File is too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded';
                break;
            default:
                $error_message = 'Unknown upload error';
        }
    }
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

$file = $_FILES['profile_image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF and WEBP images are allowed']);
    exit;
}

// Validate file size (max 2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
    exit;
}

// IMPORTANT: Define the correct upload directory
// For XAMPP, the document root is usually: E:\xampp\htdocs\
$document_root = $_SERVER['DOCUMENT_ROOT'];
$project_folder = 'ztorespotcrm_admin'; // Your project folder name
$upload_dir_relative = '/uploads/profile/';

// Absolute path where files should be saved
$upload_dir_absolute = $document_root . '/' . $project_folder . $upload_dir_relative;
$upload_dir_absolute = str_replace('//', '/', $upload_dir_absolute); // Remove double slashes

// Create upload directory if it doesn't exist
if (!file_exists($upload_dir_absolute)) {
    if (!mkdir($upload_dir_absolute, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory: ' . $upload_dir_absolute]);
        exit;
    }
}

// Check if directory is writable
if (!is_writable($upload_dir_absolute)) {
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable: ' . $upload_dir_absolute]);
    exit;
}

// Generate unique filename
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
$filepath_absolute = $upload_dir_absolute . $filename;

// Delete old profile image if exists
$oldStmt = $conn->prepare("SELECT profile_image FROM admin_users WHERE id = ?");
$oldStmt->bind_param("i", $user_id);
$oldStmt->execute();
$oldResult = $oldStmt->get_result();
$oldData = $oldResult->fetch_assoc();
$oldStmt->close();

if (!empty($oldData['profile_image'])) {
    // Get just the filename
    $old_filename = basename($oldData['profile_image']);
    $old_file_absolute = $upload_dir_absolute . $old_filename;
    
    if (file_exists($old_file_absolute)) {
        unlink($old_file_absolute);
    }
}

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath_absolute)) {
    // Store in database: relative path from project root WITHOUT leading slash
    $image_url = 'uploads/profile/' . $filename;
    
    // Check if profile_image column exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM admin_users LIKE 'profile_image'");
    if ($checkColumn->num_rows == 0) {
        $conn->query("ALTER TABLE admin_users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER email");
    }
    
    $updateStmt = $conn->prepare("UPDATE admin_users SET profile_image = ? WHERE id = ?");
    $updateStmt->bind_param("si", $image_url, $user_id);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile image updated successfully',
            'file_path' => $image_url,
            'absolute_path' => $filepath_absolute
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
    
    $updateStmt->close();
} else {
    $error = error_get_last();
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to upload file',
        'error' => $error['message'] ?? 'Unknown error',
        'target_path' => $filepath_absolute
    ]);
}

$conn->close();
?>