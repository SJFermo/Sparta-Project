<?php
// check_session.php - Add this at the top of dashboard pages to protect them
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page
    header('Location: ../index.html');
    exit;
}

// Optional: Check role authorization
// Uncomment and modify as needed for specific dashboards
/*
function checkRole($allowedRoles) {
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        die('Access denied. Insufficient permissions.');
    }
}

// Example usage in admin_dashboard.php:
// checkRole(['Admin']);

// Example usage in teacher_dashboard.php:
// checkRole(['Admin', 'Teacher']);
*/

// Get current user info (available for use in dashboard)
$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'],
    'fullname' => $_SESSION['fullname']
];
?>