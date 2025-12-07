<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'get_statistics':
            $stats = [];
            
            // Total users
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $stats['total_users'] = $stmt->fetch()['count'];
            
            // Total teachers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Teacher'");
            $stats['total_teachers'] = $stmt->fetch()['count'];
            
            // Total students
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE Role = 'Student'");
            $stats['total_students'] = $stmt->fetch()['count'];
            
            // Total labs
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM computer_labs");
            $stats['total_labs'] = $stmt->fetch()['count'];
            
            echo json_encode($stats);
            break;

        case 'get_all_users':
            $stmt = $pdo->query("SELECT UserID, Username, FullName, Role FROM users ORDER BY FullName");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_labs':
            $stmt = $pdo->query("
                SELECT 
                    l.*,
                    COUNT(c.ComputerID) as computer_count
                FROM computer_labs l
                LEFT JOIN computers c ON l.LabID = c.LabID
                GROUP BY l.LabID
                ORDER BY l.LabName
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_computers':
            $stmt = $pdo->query("
                SELECT 
                    c.ComputerID,
                    c.PC_Name,
                    c.Status,
                    l.LabName
                FROM computers c
                JOIN computer_labs l ON c.LabID = l.LabID
                ORDER BY l.LabName, c.PC_Name
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_all_sessions':
            $stmt = $pdo->query("
                SELECT 
                    s.SessionID,
                    s.Date,
                    s.StartTime,
                    s.EndTime,
                    s.Subject,
                    u.FullName as TeacherName,
                    l.LabName
                FROM sessions s
                JOIN teachers t ON s.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                JOIN computer_labs l ON s.LabID = l.LabID
                ORDER BY s.Date DESC, s.StartTime DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_lab_utilization':
            $stmt = $pdo->query("
                SELECT 
                    l.LabID,
                    l.LabName,
                    l.Status,
                    COUNT(DISTINCT s.SessionID) as session_count
                FROM computer_labs l
                LEFT JOIN sessions s ON l.LabID = s.LabID
                GROUP BY l.LabID
                ORDER BY session_count DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_teacher_sessions_count':
            $stmt = $pdo->query("
                SELECT 
                    u.FullName,
                    COUNT(s.SessionID) as session_count
                FROM users u
                JOIN teachers t ON u.UserID = t.UserID
                LEFT JOIN sessions s ON t.TeacherID = s.TeacherID
                WHERE u.Role = 'Teacher'
                GROUP BY u.UserID
                ORDER BY session_count DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_computer_reports':
            $stmt = $pdo->query("
                SELECT 
                    cr.ReportID,
                    cr.IssueType,
                    cr.Description,
                    cr.ReportDate,
                    cr.Status,
                    c.ComputerID,
                    c.PC_Name,
                    l.LabName,
                    u.FullName as TeacherName
                FROM computer_reports cr
                JOIN computers c ON cr.ComputerID = c.ComputerID
                JOIN computer_labs l ON c.LabID = l.LabID
                JOIN teachers t ON cr.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                ORDER BY 
                    CASE WHEN cr.Status = 'Pending' THEN 0 ELSE 1 END,
                    cr.ReportDate DESC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>