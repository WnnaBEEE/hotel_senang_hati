<?php
// config.php - Database Configuration
session_start();

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hotel_senang_hati';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper function to send JSON response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Helper function to validate required fields
function validateRequired($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    return true;
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate booking code
function generateBookingCode() {
    return 'RSV' . date('Ymd') . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
}

// Calculate nights between dates
function calculateNights($checkin, $checkout) {
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $diff = $date2->diff($date1);
    return $diff->days;
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['pegawai_id']);
}

// Logout function
function logout() {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>