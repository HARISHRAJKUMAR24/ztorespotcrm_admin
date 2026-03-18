<?php
// MAIN BASE URL - Fix this to your correct path
define('MAIN_URL', 'http://localhost/ztorespotcrm_admin/');  // Remove 'htdocs' from URL

// Other URLs (auto derived)
define('ASSETS_URL', MAIN_URL . 'assets/');
define('UPLOADS_URL', MAIN_URL . 'uploads/');

// App Info
define('APP_NAME', 'Ztorespot CRM Admin Panel');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ztorespot_sales_panel');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>