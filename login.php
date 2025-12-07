<?php
// login.php - Fixed login handler
session_start();
require_once 'config.php';

// Set JSON header
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['Password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
        exit;
    }

    // Password is correct - Set session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['UserID'];
    $_SESSION['username'] = $user['Username'];
    $_SESSION['fullname'] = $user['FullName'];
    $_SESSION['role'] = $user['Role'];

    // Determine redirect URL based on role
    $redirectUrl = '';
    switch ($user['Role']) {
        case 'Admin':
            $redirectUrl = 'dashboards/admin_dashboard.php';
            break;
        case 'Teacher':
            $redirectUrl = 'dashboards/teacher_dashboard.php';
            break;
        case 'Student':
            $redirectUrl = 'dashboards/student_dashboard.php';
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid user role'
            ]);
            exit;
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'redirect' => $redirectUrl,
        'role' => $user['Role'],
        'fullname' => $user['FullName']
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>