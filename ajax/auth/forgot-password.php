<?php
require_once '../../config/config.php';
require_once '../../lib/functions.php';

// Set header for JSON response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get action
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (empty($action)) {
    echo json_encode([
        'success' => false,
        'message' => 'Action is required'
    ]);
    exit;
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

switch ($action) {
    case 'send_otp':
        sendOtp();
        break;
    case 'verify_otp':
        verifyOtp();
        break;
    case 'reset_password':
        resetPassword();
        break;
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        break;
}

function sendOtp() {
    global $conn;
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    error_log("Send OTP request for email: " . $email);
    
    if (empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is required'
        ]);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        return;
    }
    
    try {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Email address not found in our system'
            ]);
            $stmt->close();
            return;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Generate OTP (default 1111 for testing)
        $otp = '1111';
        
        // Store OTP in session
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp_expiry'] = time() + 300; // 5 minutes expiry
        
        error_log("OTP generated for $email: $otp");
        
        // In production, you would send actual email here
        // For now, we'll just return success with OTP in response (for testing)
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully! Default OTP: 1111',
            'otp' => $otp // Only for testing, remove in production
        ]);
        
    } catch (Exception $e) {
        error_log("Send OTP error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    }
}

function verifyOtp() {
    global $conn;
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    
    error_log("Verify OTP request - Email: $email, OTP: $otp");
    
    if (empty($email) || empty($otp)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and OTP are required'
        ]);
        return;
    }
    
    // Check session for OTP
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp_expiry'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No OTP request found. Please request a new OTP.'
        ]);
        return;
    }
    
    // Check if email matches
    if ($_SESSION['reset_email'] !== $email) {
        echo json_encode([
            'success' => false,
            'message' => 'Email mismatch. Please request OTP again.'
        ]);
        return;
    }
    
    // Check if OTP expired
    if (time() > $_SESSION['reset_otp_expiry']) {
        // Clear expired OTP
        unset($_SESSION['reset_otp']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_otp_expiry']);
        
        echo json_encode([
            'success' => false,
            'message' => 'OTP has expired. Please request a new one.'
        ]);
        return;
    }
    
    // Verify OTP
    if ($_SESSION['reset_otp'] === $otp) {
        // OTP verified successfully
        $_SESSION['reset_verified'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid OTP. Please try again.'
        ]);
    }
}

function resetPassword() {
    global $conn;
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    error_log("Reset password request for email: $email");
    
    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email and password are required'
        ]);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 6 characters long'
        ]);
        return;
    }
    
    // Check if OTP was verified
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        echo json_encode([
            'success' => false,
            'message' => 'Please verify OTP first'
        ]);
        return;
    }
    
    // Check if email matches the verified one
    if (!isset($_SESSION['reset_email']) || $_SESSION['reset_email'] !== $email) {
        echo json_encode([
            'success' => false,
            'message' => 'Email mismatch. Please restart the process.'
        ]);
        return;
    }
    
    try {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password in database
        $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
        
        if ($stmt->execute()) {
            // Clear reset session data
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_otp_expiry']);
            unset($_SESSION['reset_verified']);
            
            error_log("Password reset successful for email: $email");
            
            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully! You can now login with your new password.'
            ]);
        } else {
            throw new Exception("Database update failed: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ]);
    }
}


?>