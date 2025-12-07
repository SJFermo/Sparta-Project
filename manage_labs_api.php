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
        case 'get_all_computers':
            $stmt = $pdo->query("
                SELECT 
                    c.ComputerID,
                    c.PC_Name,
                    c.Status,
                    c.LabID,
                    l.LabName
                FROM computers c
                JOIN computer_labs l ON c.LabID = l.LabID
                ORDER BY l.LabName, c.PC_Name
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