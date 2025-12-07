<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}
if ($_SESSION['role'] !== 'Student') {
    die('Access Denied: Student privileges required');
}

// Get student info
$studentID = null;
$mySession = null;
$selectedComputer = null;
$stmt = $pdo->prepare("SELECT StudentID, My_Session, Selected_Computer FROM students WHERE UserID = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
if ($student) {
    $studentID = $student['StudentID'];
    $mySession = $student['My_Session'];
    $selectedComputer = $student['Selected_Computer'];
}

// Handle session enrollment and computer selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'enroll_session') {
        try {
            $sessionID = $_POST['session_id'] ?? null;
            if (!$sessionID) {
                throw new Exception('Session ID required');
            }
            
            $stmt = $pdo->prepare("UPDATE students SET My_Session = ? WHERE StudentID = ?");
            $stmt->execute([$sessionID, $studentID]);
            echo json_encode(['success' => true, 'message' => 'Successfully enrolled in session']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'leave_session') {
        try {
            $stmt = $pdo->prepare("UPDATE students SET My_Session = NULL, Selected_Computer = NULL WHERE StudentID = ?");
            $stmt->execute([$studentID]);
            echo json_encode(['success' => true, 'message' => 'Successfully left the session']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'select_computer') {
        try {
            $computerID = $_POST['computer_id'] ?? null;
            if (!$computerID) {
                throw new Exception('Computer ID required');
            }
            
            // Update student's selected computer
            $stmt = $pdo->prepare("UPDATE students SET Selected_Computer = ? WHERE StudentID = ?");
            $stmt->execute([$computerID, $studentID]);
            
            // Update computer status to In-Use
            $stmt = $pdo->prepare("UPDATE computers SET Status = 'In-Use' WHERE ComputerID = ?");
            $stmt->execute([$computerID]);
            
            echo json_encode(['success' => true, 'message' => 'Computer selected successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #ad0526 0%, #f20736 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar h2 {
            color: #667eea;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #333;
        }

        .user-role {
            font-size: 12px;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .logout-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #d32f2f;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .dashboard-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            color: #333;
            font-size: 32px;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #666;
            font-size: 14px;
        }

        .content-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            color: #333;
            font-size: 24px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .btn-success {
            background: #4caf50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .my-session-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .my-session-card h3 {
            font-size: 24px;
            margin-bottom: 1rem;
        }

        .my-session-card .session-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .session-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .session-info-item i {
            font-size: 24px;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .session-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s;
        }

        .session-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .session-card h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 18px;
        }

        .session-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .session-card p i {
            color: #667eea;
        }

        .session-card-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-occupied {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-maintenance {
            background: #ffebee;
            color: #c62828;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .computer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .computer-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #e0e0e0;
        }

        .computer-card:hover:not(.disabled) {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #667eea;
        }

        .computer-card.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .computer-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .computer-card i {
            font-size: 40px;
            margin-bottom: 0.5rem;
        }

        .computer-card.selected i {
            color: white;
        }

        .computer-card h4 {
            font-size: 16px;
            margin-bottom: 0.3rem;
        }

        .computer-card p {
            font-size: 12px;
            color: #999;
        }

        .computer-card.selected p {
            color: rgba(255,255,255,0.8);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid,
            .sessions-grid {
                grid-template-columns: 1fr;
            }

            .computer-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2><i class='bx bxs-graduation'></i> Nexus Sparta</h2>
        <div class="user-info">
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['fullname']); ?></div>
                <span class="user-role"><?php echo $_SESSION['role']; ?></span>
            </div>
            <button class="logout-btn" onclick="logout()">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['fullname']); ?>! 👋</h1>
            <p>View available sessions and manage your lab schedule</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class='bx bxs-calendar'></i>
                <h3 id="totalSessions">0</h3>
                <p>Available Sessions</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-desktop'></i>
                <h3 id="availableComputers">0</h3>
                <p>Available Computers</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-time'></i>
                <h3 id="todaySessions">0</h3>
                <p>Today's Sessions</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-check-circle'></i>
                <h3 id="myEnrollment"><?php echo $mySession ? '1' : '0'; ?></h3>
                <p>My Enrollment</p>
            </div>
        </div>

        <!-- My Current Session -->
        <div id="mySessionSection"></div>

        <!-- Computer Selection -->
        <div id="computerSelectionSection" style="display: none;">
            <div class="content-section">
                <div class="section-header">
                    <h2><i class='bx bxs-devices'></i> Select Your Computer</h2>
                </div>
                <p style="color: #666; margin-bottom: 1rem;">Choose which computer you'll be using for this session</p>
                <div id="computerGrid"></div>
            </div>
        </div>

        <!-- Available Sessions -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class='bx bxs-calendar-check'></i> Available Sessions</h2>
            </div>
            <div id="sessionsGrid"></div>
        </div>
    </div>

    <script>
        const studentID = <?php echo $studentID; ?>;
        let currentSessionID = <?php echo $mySession ? $mySession : 'null'; ?>;
        let selectedComputerID = <?php echo $selectedComputer ? $selectedComputer : 'null'; ?>;

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('student_api.php?action=get_statistics');
                const stats = await response.json();
                
                document.getElementById('totalSessions').textContent = stats.total_sessions || 0;
                document.getElementById('availableComputers').textContent = stats.available_computers || 0;
                document.getElementById('todaySessions').textContent = stats.today_sessions || 0;
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        async function loadMySession() {
            const container = document.getElementById('mySessionSection');
            const computerSection = document.getElementById('computerSelectionSection');
            
            if (!currentSessionID) {
                container.innerHTML = '';
                computerSection.style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`student_api.php?action=get_my_session&session_id=${currentSessionID}`);
                const session = await response.json();
                
                if (!session || session.error) {
                    container.innerHTML = '';
                    computerSection.style.display = 'none';
                    return;
                }

                // Calculate duration
                const start = new Date(`2000-01-01 ${session.StartTime}`);
                const end = new Date(`2000-01-01 ${session.EndTime}`);
                const durationMs = end - start;
                const hours = Math.floor(durationMs / (1000 * 60 * 60));
                const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                const durationText = `${hours}h ${minutes}m`;

                container.innerHTML = `
                    <div class="my-session-card">
                        <h3><i class='bx bxs-star'></i> My Current Session</h3>
                        <div class="session-info">
                            <div class="session-info-item">
                                <i class='bx bxs-book'></i>
                                <div>
                                    <div style="font-size: 12px; opacity: 0.8;">Subject</div>
                                    <div style="font-weight: 600;">${session.Subject}</div>
                                </div>
                            </div>
                            <div class="session-info-item">
                                <i class='bx bxs-building'></i>
                                <div>
                                    <div style="font-size: 12px; opacity: 0.8;">Laboratory</div>
                                    <div style="font-weight: 600;">${session.LabName}</div>
                                </div>
                            </div>
                            <div class="session-info-item">
                                <i class='bx bxs-calendar'></i>
                                <div>
                                    <div style="font-size: 12px; opacity: 0.8;">Date</div>
                                    <div style="font-weight: 600;">${session.Date}</div>
                                </div>
                            </div>
                            <div class="session-info-item">
                                <i class='bx bxs-time'></i>
                                <div>
                                    <div style="font-size: 12px; opacity: 0.8;">Time & Duration</div>
                                    <div style="font-weight: 600;">${session.StartTime} - ${session.EndTime} (${durationText})</div>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 1.5rem;">
                            <button class="btn-danger" onclick="leaveSession()">
                                <i class='bx bx-exit'></i> Leave Session
                            </button>
                        </div>
                    </div>
                `;

                // Show computer selection if enrolled
                computerSection.style.display = 'block';
                loadComputersForSelection(session.LabID);
            } catch (error) {
                console.error('Error loading my session:', error);
                container.innerHTML = '';
                computerSection.style.display = 'none';
            }
        }

        async function loadComputersForSelection(labID) {
            try {
                const response = await fetch(`student_api.php?action=get_lab_computers&lab_id=${labID}`);
                const computers = await response.json();
                
                const container = document.getElementById('computerGrid');
                
                if (computers.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-desktop"></i><p>No computers available in this lab</p></div>';
                    return;
                }

                let html = '<div class="computer-grid">';
                computers.forEach(computer => {
                    const isSelected = selectedComputerID == computer.ComputerID;
                    const isAvailable = computer.Status === 'Available';
                    const isDisabled = !isAvailable && !isSelected;
                    
                    html += `
                        <div class="computer-card ${isSelected ? 'selected' : ''} ${isDisabled ? 'disabled' : ''}" 
                             onclick="${isDisabled ? '' : `selectComputer(${computer.ComputerID})`}">
                            <i class='bx ${isSelected ? 'bxs-check-circle' : isAvailable ? 'bx-desktop' : 'bxs-desktop'}'></i>
                            <h4>${computer.PC_Name}</h4>
                            <p>${isSelected ? 'Your Computer' : computer.Status}</p>
                        </div>
                    `;
                });
                html += '</div>';
                
                if (selectedComputerID) {
                    html += `
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <p style="color: #4caf50; font-weight: 600;">
                                <i class='bx bx-check-circle'></i> Computer selected successfully!
                            </p>
                        </div>
                    `;
                }
                
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading computers:', error);
            }
        }

        async function selectComputer(computerID) {
            if (selectedComputerID) {
                alert('You have already selected a computer for this session.');
                return;
            }

            if (!confirm('Select this computer?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'select_computer');
                formData.append('computer_id', computerID);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    selectedComputerID = computerID;
                    alert(result.message);
                    loadMySession();
                    loadStats();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error selecting computer:', error);
                alert('Error selecting computer');
            }
        }

        async function loadSessions() {
            try {
                const response = await fetch('student_api.php?action=get_available_sessions');
                const sessions = await response.json();
                
                const container = document.getElementById('sessionsGrid');
                
                if (sessions.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class='bx bx-calendar-x'></i>
                            <p>No sessions available at the moment</p>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="sessions-grid">';
                sessions.forEach(session => {
                    const isEnrolled = currentSessionID == session.SessionID;
                    const statusClass = session.LabStatus === 'Available' ? 'status-available' : 
                                       session.LabStatus === 'Occupied' ? 'status-occupied' : 'status-maintenance';
                    
                    // Calculate duration
                    const start = new Date(`2000-01-01 ${session.StartTime}`);
                    const end = new Date(`2000-01-01 ${session.EndTime}`);
                    const durationMs = end - start;
                    const hours = Math.floor(durationMs / (1000 * 60 * 60));
                    const minutes = Math.floor((durationMs % (1000 * 60 * 60)) / (1000 * 60));
                    const durationText = `${hours}h ${minutes}m`;
                    
                    html += `
                        <div class="session-card">
                            <h4>${session.Subject}</h4>
                            <p><i class='bx bxs-user'></i> ${session.TeacherName}</p>
                            <p><i class='bx bxs-building'></i> ${session.LabName}</p>
                            <p><i class='bx bxs-calendar'></i> ${session.Date}</p>
                            <p><i class='bx bxs-time'></i> ${session.StartTime} - ${session.EndTime}</p>
                            <p><i class='bx bxs-hourglass'></i> Duration: ${durationText}</p>
                            <div class="session-card-footer">
                                <span class="status-badge ${statusClass}">${session.LabStatus}</span>
                                ${isEnrolled ? 
                                    '<span style="color: #4caf50; font-weight: 600;"><i class="bx bx-check"></i> Enrolled</span>' :
                                    `<button class="btn-success" onclick="enrollSession(${session.SessionID})" ${currentSessionID ? 'disabled' : ''}>
                                        <i class='bx bx-plus'></i> Enroll
                                    </button>`
                                }
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        }

        async function enrollSession(sessionId) {
            if (currentSessionID) {
                alert('You are already enrolled in a session. Please leave your current session first.');
                return;
            }

            if (!confirm('Do you want to enroll in this session?')) return;

            const formData = new FormData();
            formData.append('action', 'enroll_session');
            formData.append('session_id', sessionId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.message);
            }
        }

        async function leaveSession() {
            if (!confirm('Are you sure you want to leave this session?')) return;

            const formData = new FormData();
            formData.append('action', 'leave_session');

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                location.reload();
            } else {
                alert(result.message);
            }
        }

        // Load all data on page load
        loadStats();
        loadMySession();
        loadSessions();

        // Refresh data every 30 seconds
        setInterval(() => {
            loadStats();
            loadSessions();
        }, 30000);
    </script>
</body>
</html>