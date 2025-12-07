<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'Teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get teacher ID
$stmt = $pdo->prepare("SELECT TeacherID FROM teachers WHERE UserID = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
$teacherID = $teacher['TeacherID'] ?? null;

if (!$teacherID) {
    http_response_code(403);
    echo json_encode(['error' => 'Teacher not found']);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch($action) {
        case 'get_labs':
            $stmt = $pdo->query("SELECT LabID, LabName, Status FROM computer_labs ORDER BY LabName");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_sessions':
            $stmt = $pdo->prepare("
                SELECT 
                    s.SessionID,
                    s.Date,
                    s.StartTime,
                    s.EndTime,
                    s.Subject,
                    l.LabName
                FROM sessions s
                JOIN computer_labs l ON s.LabID = l.LabID
                WHERE s.TeacherID = ?
                ORDER BY s.Date DESC, s.StartTime DESC
            ");
            $stmt->execute([$teacherID]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_lab_status':
            $stmt = $pdo->query("
                SELECT 
                    l.LabID,
                    l.LabName,
                    l.Status,
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

        case 'get_all_computers':
            // For report dropdown - all computers
            $stmt = $pdo->query("
                SELECT 
                    c.ComputerID,
                    c.PC_Name,
                    c.Status,
                    l.LabName,
                    l.LabID
                FROM computers c
                JOIN computer_labs l ON c.LabID = l.LabID
                ORDER BY l.LabName, c.PC_Name
            ");
            $computers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($computers);
            break;

        case 'get_my_reports':
            $stmt = $pdo->prepare("
                SELECT 
                    cr.ReportID,
                    cr.IssueType,
                    cr.Description,
                    cr.ReportDate,
                    cr.Status,
                    c.PC_Name,
                    l.LabName
                FROM computer_reports cr
                JOIN computers c ON cr.ComputerID = c.ComputerID
                JOIN computer_labs l ON c.LabID = l.LabID
                WHERE cr.TeacherID = ?
                ORDER BY cr.ReportDate DESC, cr.Status ASC
            ");
            $stmt->execute([$teacherID]);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_session_attendance':
            $sessionID = $_GET['session_id'] ?? null;
            if (!$sessionID) {
                echo json_encode([]);
                break;
            }

            // Verify session belongs to this teacher
            $stmt = $pdo->prepare("SELECT SessionID FROM sessions WHERE SessionID = ? AND TeacherID = ?");
            $stmt->execute([$sessionID, $teacherID]);
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }

            $stmt = $pdo->prepare("
                SELECT 
                    st.StudentID,
                    u.FullName,
                    st.LoginTime,
                    st.LogoutTime,
                    c.PC_Name as ComputerUsed,
                    CASE 
                        WHEN st.LoginTime IS NOT NULL AND st.LogoutTime IS NOT NULL 
                        THEN CONCAT(
                            TIMESTAMPDIFF(HOUR, st.LoginTime, st.LogoutTime), 'h ',
                            MOD(TIMESTAMPDIFF(MINUTE, st.LoginTime, st.LogoutTime), 60), 'm'
                        )
                        WHEN st.LoginTime IS NOT NULL 
                        THEN 'In Progress'
                        ELSE 'Not Started'
                    END AS Duration
                FROM students st
                JOIN users u ON st.UserID = u.UserID
                LEFT JOIN computers c ON st.Selected_Computer = c.ComputerID
                WHERE st.My_Session = ?
                ORDER BY u.FullName
            ");
            $stmt->execute([$sessionID]);
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