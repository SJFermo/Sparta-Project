<?php
// register.php - Handles user registration
require_once 'config.php';

// Set response header to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$role = isset($input['role']) ? $input['role'] : 'Student';
$course = isset($input['course']) ? trim($input['course']) : null;
$yearLevel = isset($input['year_level']) ? $input['year_level'] : null;

// Validation checks
if (empty($fullname) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Validate role
if (!in_array($role, ['Student', 'Teacher', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role selected']);
    exit;
}

try {
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT UserID FROM users WHERE Username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit;
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Insert into users table
    $stmt = $pdo->prepare("INSERT INTO users (FullName, Username, Password, Role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$fullname, $username, $hashedPassword, $role]);
    $userId = $pdo->lastInsertId();
    
    // Insert into role-specific table
    if ($role === 'Student') {
        $stmt = $pdo->prepare("INSERT INTO students (UserID, FullName, Course, YearLevel, My_Session) VALUES (?, ?, ?, ?, NULL)");
        $stmt->execute([$userId, $fullname, $course, $yearLevel]);
    } elseif ($role === 'Teacher') {
        $stmt = $pdo->prepare("INSERT INTO teachers (UserID, FullName) VALUES (?, ?)");
        $stmt->execute([$userId, $fullname]);
    } elseif ($role === 'Admin') {
        $stmt = $pdo->prepare("INSERT INTO admin (UserID) VALUES (?)");
        $stmt->execute([$userId]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You can now login.'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again later.'
    ]);
}
?>