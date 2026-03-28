<?php
// lib/functions.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
global $conn;
require_once __DIR__ . '/../config/config.php';

/**
 * Check for Remember Me token and auto-login
 * Call this function at the beginning of protected pages
 */
function checkRememberToken()
{
    global $conn;

    // If already logged in via session, no need to check token
    if (isLoggedIn()) {
        return true;
    }

    // Check if remember me cookies exist
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
        $token = $_COOKIE['remember_token'];
        $user_id = intval($_COOKIE['user_id']);

        // Query database for valid token
        $stmt = $conn->prepare("SELECT id, username, email, remember_token, token_expiry FROM admin_users WHERE id = ? AND remember_token = ?");
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $current_time = date('Y-m-d H:i:s');

            // Check if token is still valid (not expired)
            if ($user['token_expiry'] && $user['token_expiry'] > $current_time) {
                // Token is valid - log the user in
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['remember_token_login'] = true;

                error_log("Auto-login via remember token for user: " . $user['username']);

                $stmt->close();
                return true;
            } else {
                // Token expired - clear it
                $clearStmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
                $clearStmt->bind_param("i", $user_id);
                $clearStmt->execute();
                $clearStmt->close();

                // Clear cookies
                setcookie('remember_token', '', time() - 3600, '/');
                setcookie('user_id', '', time() - 3600, '/');

                error_log("Remember token expired for user ID: " . $user_id);
            }
        }
        $stmt->close();
    }

    return false;
}

/**
 * Template loader function
 */
function template($name)
{
    $file = __DIR__ . "/../templates/" . $name . ".php";

    if (file_exists($file)) {
        require $file;
    } else {
        echo "<!-- Template not found: $name -->";
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Redirect if not logged in
 */
function requireLogin()
{
    // First check if remember token can log them in
    checkRememberToken();

    if (!isLoggedIn()) {
        header("Location: " . MAIN_URL . "auth/login.php");
        exit;
    }
}

/**
 * Get current admin info
 */
function getCurrentAdmin()
{
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'email' => $_SESSION['admin_email']
        ];
    }
    return null;
}

/**
 * Sanitize input
 */
function sanitize($input)
{
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Logout function - clears session and remember me tokens
 */
function logout()
{
    global $conn;

    // Clear remember me token from database if user is logged in
    if (isLoggedIn()) {
        $user_id = $_SESSION['admin_id'];
        $clearStmt = $conn->prepare("UPDATE admin_users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
        $clearStmt->bind_param("i", $user_id);
        $clearStmt->execute();
        $clearStmt->close();
    }

    // Clear remember me cookies
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_id', '', time() - 3600, '/');

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: " . MAIN_URL . "auth/login.php");
    exit;
}




// // Add these functions to your existing functions.php

// /**
//  * Handle profile image upload with structured folder system
//  * Stores in: uploads/profile/{user_id}/{year}/profile_timestamp.jpg
//  * 
//  * @param int $user_id The user ID
//  * @param array $file The $_FILES['profile_image'] array
//  * @param mysqli $conn Database connection
//  * @return array ['success' => bool, 'message' => string, 'path' => string]
//  */
// function uploadProfileImage($user_id, $file, $conn) {
//     // Default response
//     $response = [
//         'success' => false,
//         'message' => '',
//         'path' => ''
//     ];
    
//     // Check if file was uploaded properly
//     if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
//         $response['message'] = getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
//         return $response;
//     }
    
//     // Validate file type
//     $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
//     $finfo = finfo_open(FILEINFO_MIME_TYPE);
//     $mime_type = finfo_file($finfo, $file['tmp_name']);
//     finfo_close($finfo);
    
//     if (!in_array($mime_type, $allowed_types)) {
//         $response['message'] = 'Only JPG, PNG, GIF and WEBP images are allowed';
//         return $response;
//     }
    
//     // Validate file size (max 2MB)
//     if ($file['size'] > 2 * 1024 * 1024) {
//         $response['message'] = 'File size must be less than 2MB';
//         return $response;
//     }
    
//     // Create structured folder path: uploads/profile/{user_id}/{year}/
//     $year = date('Y');
//     $upload_base = 'uploads/profile/';
//     $upload_path = $upload_base . $user_id . '/' . $year . '/';
    
//     // Get absolute path for file storage
//     $document_root = $_SERVER['DOCUMENT_ROOT'];
//     $project_folder = 'ztorespotcrm_admin'; // Change this to your project folder name
//     $absolute_base = $document_root . '/' . $project_folder . '/';
//     $absolute_path = $absolute_base . $upload_path;
//     $absolute_path = str_replace('//', '/', $absolute_path);
//     $absolute_path = str_replace('\\', '/', $absolute_path); // For Windows
    
//     // Create directory if it doesn't exist
//     if (!file_exists($absolute_path)) {
//         if (!mkdir($absolute_path, 0777, true)) {
//             $response['message'] = 'Failed to create upload directory';
//             return $response;
//         }
//     }
    
//     // Generate unique filename
//     $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
//     $timestamp = time();
//     $filename = 'profile_' . $timestamp . '.' . $extension;
//     $absolute_filepath = $absolute_path . $filename;
    
//     // Delete old profile images
//     deleteOldProfileImages($user_id, $conn, $absolute_base);
    
//     // Move uploaded file
//     if (move_uploaded_file($file['tmp_name'], $absolute_filepath)) {
//         // Store relative path in database (without leading slash)
//         $db_path = $upload_path . $filename;
        
//         // Check if profile_image column exists
//         $checkColumn = $conn->query("SHOW COLUMNS FROM admin_users LIKE 'profile_image'");
//         if ($checkColumn->num_rows == 0) {
//             $conn->query("ALTER TABLE admin_users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER email");
//         }
        
//         // Update database
//         $updateStmt = $conn->prepare("UPDATE admin_users SET profile_image = ? WHERE id = ?");
//         $updateStmt->bind_param("si", $db_path, $user_id);
        
//         if ($updateStmt->execute()) {
//             $response['success'] = true;
//             $response['message'] = 'Profile image updated successfully';
//             $response['path'] = $db_path;
//         } else {
//             $response['message'] = 'Failed to update database';
//             // Delete uploaded file if database update fails
//             if (file_exists($absolute_filepath)) {
//                 unlink($absolute_filepath);
//             }
//         }
//         $updateStmt->close();
//     } else {
//         $response['message'] = 'Failed to move uploaded file';
//     }
    
//     return $response;
// }

// /**
//  * Delete old profile images for a user
//  * 
//  * @param int $user_id The user ID
//  * @param mysqli $conn Database connection
//  * @param string $absolute_base The absolute base path
//  */
// function deleteOldProfileImages($user_id, $conn, $absolute_base) {
//     // Get current profile image from database
//     $stmt = $conn->prepare("SELECT profile_image FROM admin_users WHERE id = ?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $oldData = $result->fetch_assoc();
//     $stmt->close();
    
//     if (!empty($oldData['profile_image'])) {
//         $old_file_absolute = $absolute_base . $oldData['profile_image'];
//         $old_file_absolute = str_replace('//', '/', $old_file_absolute);
//         $old_file_absolute = str_replace('\\', '/', $old_file_absolute);
        
//         if (file_exists($old_file_absolute)) {
//             unlink($old_file_absolute);
//         }
        
//         // Also clean up empty directories
//         cleanupEmptyDirectories($absolute_base . 'uploads/profile/' . $user_id);
//     }
// }

// /**
//  * Clean up empty directories after file deletion
//  * 
//  * @param string $user_dir The user's profile directory path
//  */
// function cleanupEmptyDirectories($user_dir) {
//     $user_dir = str_replace('//', '/', $user_dir);
//     $user_dir = str_replace('\\', '/', $user_dir);
    
//     if (!is_dir($user_dir)) {
//         return;
//     }
    
//     $year_dirs = glob($user_dir . '/*', GLOB_ONLYDIR);
    
//     foreach ($year_dirs as $year_dir) {
//         $files = glob($year_dir . '/*');
//         if (empty($files)) {
//             rmdir($year_dir);
//         }
//     }
    
//     // Check if user directory is empty (no year directories left)
//     $remaining_dirs = glob($user_dir . '/*', GLOB_ONLYDIR);
//     if (empty($remaining_dirs)) {
//         rmdir($user_dir);
//     }
// }

// /**
//  * Get user's profile image URL
//  * 
//  * @param array $profile The user profile data
//  * @param int $size Size for gravatar fallback
//  * @return string The image URL
//  */
// function getProfileImageUrl($profile, $size = 150) {
//     if (!empty($profile['profile_image'])) {
//         // Check if MAIN_URL is defined
//         if (defined('MAIN_URL')) {
//             return MAIN_URL . $profile['profile_image'];
//         } else {
//             // Fallback to relative path
//             return '/ztorespotcrm_admin/' . $profile['profile_image'];
//         }
//     } else {
//         // Use Gravatar as fallback
//         $email = $profile['email'] ?? '';
//         $hash = md5(strtolower(trim($email)));
//         return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
//     }
// }

// /**
//  * Get upload error message
//  * 
//  * @param int $error_code The upload error code
//  * @return string Error message
//  */
// function getUploadErrorMessage($error_code) {
//     switch ($error_code) {
//         case UPLOAD_ERR_INI_SIZE:
//         case UPLOAD_ERR_FORM_SIZE:
//             return 'File is too large (max 2MB)';
//         case UPLOAD_ERR_PARTIAL:
//             return 'File was only partially uploaded';
//         case UPLOAD_ERR_NO_FILE:
//             return 'No file was uploaded';
//         case UPLOAD_ERR_NO_TMP_DIR:
//             return 'Missing temporary folder';
//         case UPLOAD_ERR_CANT_WRITE:
//             return 'Failed to write file to disk';
//         case UPLOAD_ERR_EXTENSION:
//             return 'File upload stopped by extension';
//         default:
//             return 'Unknown upload error';
//     }
// }

// /**
//  * Ensure upload directories exist and are writable
//  * 
//  * @return array Status of directories
//  */
// function ensureUploadDirectories() {
//     $document_root = $_SERVER['DOCUMENT_ROOT'];
//     $project_folder = 'ztorespotcrm_admin';
//     $base_path = $document_root . '/' . $project_folder . '/';
//     $base_path = str_replace('//', '/', $base_path);
    
//     $directories = [
//         'uploads' => $base_path . 'uploads/',
//         'uploads/profile' => $base_path . 'uploads/profile/',
//     ];
    
//     $status = [];
    
//     foreach ($directories as $name => $path) {
//         $status[$name] = [
//             'path' => $path,
//             'exists' => false,
//             'writable' => false,
//             'created' => false
//         ];
        
//         if (!file_exists($path)) {
//             if (mkdir($path, 0777, true)) {
//                 $status[$name]['created'] = true;
//                 $status[$name]['exists'] = true;
//             }
//         } else {
//             $status[$name]['exists'] = true;
//         }
        
//         if ($status[$name]['exists']) {
//             $status[$name]['writable'] = is_writable($path);
//         }
//     }
    
//     return $status;
// }