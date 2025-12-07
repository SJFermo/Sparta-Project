<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get student ID
$stmt = $pdo->prepare("SELECT StudentID FROM students WHERE UserID = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
$studentID = $student['StudentID'] ?? null;

if (!$studentID) {
    http_response_code(403);
    echo json_encode(['error' => 'Student not found']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'get_statistics':
            $stats = [];
            
            // Total available sessions
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE Date >= CURDATE()");
            $stats['total_sessions'] = $stmt->fetch()['count'];
            
            // Available labs
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM computer_labs WHERE Status = 'Available'");
            $stats['available_labs'] = $stmt->fetch()['count'];
            
            // Available computers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM computers WHERE Status = 'Available'");
            $stats['available_computers'] = $stmt->fetch()['count'];
            
            // Today's sessions
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE Date = CURDATE()");
            $stats['today_sessions'] = $stmt->fetch()['count'];
            
            echo json_encode($stats);
            break;

        case 'get_my_session':
            $sessionID = $_GET['session_id'] ?? null;
            if (!$sessionID) {
                echo json_encode(['error' => 'Session ID required']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    s.SessionID,
                    s.Date,
                    s.StartTime,
                    s.EndTime,
                    s.Subject,
                    l.LabID,
                    l.LabName,
                    u.FullName as TeacherName
                FROM sessions s
                JOIN computer_labs l ON s.LabID = l.LabID
                JOIN teachers t ON s.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                WHERE s.SessionID = ?
            ");
            $stmt->execute([$sessionID]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            break;

        case 'get_available_sessions':
            $stmt = $pdo->query("
                SELECT 
                    s.SessionID,
                    s.Date,
                    s.StartTime,
                    s.EndTime,
                    s.Subject,
                    l.LabName,
                    l.Status as LabStatus,
                    u.FullName as TeacherName
                FROM sessions s
                JOIN computer_labs l ON s.LabID = l.LabID
                JOIN teachers t ON s.TeacherID = t.TeacherID
                JOIN users u ON t.UserID = u.UserID
                WHERE s.Date >= CURDATE()
                ORDER BY s.Date ASC, s.StartTime ASC
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_labs':
            $stmt = $pdo->query("
                SELECT 
                    l.LabID,
                    l.LabName,
                    l.Description,
                    l.Status,
                    COUNT(c.ComputerID) as computer_count
                FROM computer_labs l
                LEFT JOIN computers c ON l.LabID = c.LabID
                GROUP BY l.LabID
                ORDER BY l.LabName
            ");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_computers_by_labs':
            $stmt = $pdo->query("
                SELECT 
                    l.LabID,
                    l.LabName,
                    l.Status
                FROM computer_labs l
                ORDER BY l.LabName
            ");
            $labs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get computers for each lab
            foreach ($labs as &$lab) {
                $stmt = $pdo->prepare("
                    SELECT 
                        ComputerID,
                        PC_Name,
                        Status
                    FROM computers
                    WHERE LabID = ?
                    ORDER BY PC_Name
                ");
                $stmt->execute([$lab['LabID']]);
                $lab['computers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode($labs);
            break;

        case 'get_lab_computers':
            $labID = $_GET['lab_id'] ?? null;
            if (!$labID) {
                echo json_encode([]);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    ComputerID,
                    PC_Name,
                    Status
                FROM computers
                WHERE LabID = ?
                ORDER BY PC_Name
            ");
            $stmt->execute([$labID]);
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