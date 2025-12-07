<?php 
session_start();
require_once '../config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}
if ($_SESSION['role'] !== 'Teacher') {
    die('Access Denied: Teacher privileges required');
}

// Get teacher ID from session
$teacherID = null;
$stmt = $pdo->prepare("SELECT TeacherID FROM teachers WHERE UserID = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher = $stmt->fetch();
if ($teacher) {
    $teacherID = $teacher['TeacherID'];
}

// Handle session creation and report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'create_session') {
        try {
            $stmt = $pdo->prepare("INSERT INTO sessions (TeacherID, LabID, Date, StartTime, EndTime, Subject) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $teacherID,
                $_POST['lab_id'],
                $_POST['date'],
                $_POST['start_time'],
                $_POST['end_time'],
                $_POST['subject']
            ]);
            echo json_encode(['success' => true, 'message' => 'Session created successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_session') {
        try {
            $stmt = $pdo->prepare("DELETE FROM sessions WHERE SessionID = ? AND TeacherID = ?");
            $stmt->execute([$_POST['session_id'], $teacherID]);
            echo json_encode(['success' => true, 'message' => 'Session deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'report_issue') {
        try {
            $stmt = $pdo->prepare("INSERT INTO computer_reports (ComputerID, TeacherID, IssueType, Description, ReportDate, Status) VALUES (?, ?, ?, ?, CURDATE(), 'Pending')");
            $stmt->execute([
                $_POST['computer_id'],
                $teacherID,
                $_POST['issue_type'],
                $_POST['description']
            ]);
            
            // Update computer status to maintenance
            $stmt = $pdo->prepare("UPDATE computers SET Status = 'Maintenance' WHERE ComputerID = ?");
            $stmt->execute([$_POST['computer_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Issue reported successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Fetch statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sessions WHERE TeacherID = ?");
$stmt->execute([$teacherID]);
$totalSessions = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM computer_labs WHERE Status = 'Available'");
$availableLabs = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM computers WHERE Status = 'Available'");
$availableComputers = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sessions WHERE TeacherID = ? AND Date = CURDATE()");
$stmt->execute([$teacherID]);
$todaySessions = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal</title>
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
            color: #666;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        table tr:hover {
            background: #f9f9f9;
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

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            color: #333;
            font-size: 20px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
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

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }

        .tab:hover {
            color: #667eea;
        }

        .labs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .lab-card {
            background: #f9f9f9;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .lab-card h4 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 18px;
        }

        .lab-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 0.5rem;
        }

        .lab-status {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid,
            .labs-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            table th, table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2><i class='bx bxs-chalkboard'></i> Nexus Sparta</h2>
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
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['fullname']); ?>! 👋</h1>
            <p>Manage your lab sessions and monitor laboratory resources</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class='bx bxs-calendar'></i>
                <h3><?php echo $totalSessions; ?></h3>
                <p>Total Sessions</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-calendar-check'></i>
                <h3><?php echo $todaySessions; ?></h3>
                <p>Today's Sessions</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-building'></i>
                <h3><?php echo $availableLabs; ?></h3>
                <p>Available Labs</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-desktop'></i>
                <h3><?php echo $availableComputers; ?></h3>
                <p>Available Computers</p>
            </div>
        </div>

        <!-- Report Issues Section -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class='bx bxs-error-circle'></i> Report Computer Issues</h2>
                <button class="btn-danger" onclick="openReportModal()">
                    <i class='bx bx-plus'></i> Report Issue
                </button>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Report any computer issues you encounter during your sessions. Issues will be sent to the administrator for resolution.</p>
            <div id="myReportsTable"></div>
        </div>

        <!-- My Sessions Section -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class='bx bxs-calendar'></i> My Sessions</h2>
                <button class="btn-primary" onclick="openCreateModal()">
                    <i class='bx bx-plus'></i> Create Session
                </button>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab('sessions')">Sessions</button>
                <button class="tab" onclick="switchTab('attendance')">Student Attendance</button>
            </div>
            
            <div id="sessionsTab">
                <div id="sessionsTable"></div>
            </div>
            
            <div id="attendanceTab" style="display: none;">
                <div class="form-group" style="max-width: 400px;">
                    <label>Select Session</label>
                    <select id="sessionSelect" onchange="loadAttendance()">
                        <option value="">Choose a session...</option>
                    </select>
                </div>
                <div id="attendanceTable"></div>
            </div>
        </div>

        <!-- Laboratory Status Section -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class='bx bxs-building'></i> Laboratory Status</h2>
            </div>
            <div id="labsGrid"></div>
        </div>

        <!-- Computer Status Section -->
        <div class="content-section">
            <div class="section-header">
                <h2><i class='bx bxs-devices'></i> Computer Status by Laboratory</h2>
            </div>
            <div id="computersTable"></div>
        </div>
    </div>

    <!-- Create Session Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Session</h3>
                <button class="close-btn" onclick="closeCreateModal()">&times;</button>
            </div>
            <form id="createSessionForm" onsubmit="createSession(event)">
                <div class="form-group">
                    <label>Laboratory *</label>
                    <select name="lab_id" required id="labSelect">
                        <option value="">Select a laboratory</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Start Time *</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time *</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" required placeholder="e.g., Computer Programming">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class='bx bx-save'></i> Create Session
                </button>
            </form>
        </div>
    </div>

    <!-- Report Issue Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Report Computer Issue</h3>
                <button class="close-btn" onclick="closeReportModal()">&times;</button>
            </div>
            <form id="reportIssueForm" onsubmit="reportIssue(event)">
                <div class="form-group">
                    <label>Select Computer *</label>
                    <select name="computer_id" required id="computerSelect">
                        <option value="">Choose a computer...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Issue Type *</label>
                    <select name="issue_type" required>
                        <option value="">Select issue type...</option>
                        <option value="Not working">Not working</option>
                        <option value="Lost device/mouse">Lost device/mouse</option>
                        <option value="Slow performance">Slow performance</option>
                        <option value="Software issue">Software issue</option>
                        <option value="Hardware damage">Hardware damage</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required rows="4" placeholder="Describe the issue in detail..."></textarea>
                </div>
                <button type="submit" class="btn-danger" style="width: 100%;">
                    <i class='bx bx-error'></i> Submit Report
                </button>
            </form>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            if (tab === 'sessions') {
                document.getElementById('sessionsTab').style.display = 'block';
                document.getElementById('attendanceTab').style.display = 'none';
            } else {
                document.getElementById('sessionsTab').style.display = 'none';
                document.getElementById('attendanceTab').style.display = 'block';
                loadSessionsForAttendance();
            }
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
            loadLabsForModal();
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
            document.getElementById('createSessionForm').reset();
        }

        function openReportModal() {
            document.getElementById('reportModal').classList.add('active');
            loadComputersForReport();
        }

        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('active');
            document.getElementById('reportIssueForm').reset();
        }

        async function loadLabsForModal() {
            try {
                const response = await fetch('./teacher_api.php?action=get_labs');
                const labs = await response.json();
                
                const select = document.getElementById('labSelect');
                select.innerHTML = '<option value="">Select a laboratory</option>';
                
                if (labs.length === 0) {
                    select.innerHTML = '<option value="">No laboratories available</option>';
                    return;
                }
                
                labs.forEach(lab => {
                    const option = document.createElement('option');
                    option.value = lab.LabID;
                    option.textContent = `${lab.LabName} - ${lab.Status}`;
                    if (lab.Status !== 'Available') {
                        option.disabled = true;
                        option.textContent += ' (Not Available)';
                    }
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading labs:', error);
            }
        }

        async function loadComputersForReport() {
            try {
                const response = await fetch('teacher_api.php?action=get_all_computers');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const computers = await response.json();
                console.log('Loaded computers:', computers); // Debug log
                
                const select = document.getElementById('computerSelect');
                select.innerHTML = '<option value="">Choose a computer...</option>';
                
                if (!computers || computers.length === 0) {
                    select.innerHTML = '<option value="">No computers available</option>';
                    return;
                }
                
                // Group by lab
                const labGroups = {};
                computers.forEach(computer => {
                    if (!labGroups[computer.LabName]) {
                        labGroups[computer.LabName] = [];
                    }
                    labGroups[computer.LabName].push(computer);
                });
                
                // Add optgroups by lab
                Object.keys(labGroups).sort().forEach(labName => {
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = labName;
                    
                    labGroups[labName].forEach(computer => {
                        const option = document.createElement('option');
                        option.value = computer.ComputerID;
                        option.textContent = `${computer.PC_Name} (${computer.Status})`;
                        optgroup.appendChild(option);
                    });
                    
                    select.appendChild(optgroup);
                });
            } catch (error) {
                console.error('Error loading computers:', error);
                const select = document.getElementById('computerSelect');
                select.innerHTML = '<option value="">Error loading computers</option>';
                alert('Failed to load computers. Please check the console for errors.');
            }
        }

        async function createSession(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'create_session');

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                closeCreateModal();
                loadSessions();
            } else {
                alert(result.message);
            }
        }

        async function reportIssue(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'report_issue');

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                closeReportModal();
                loadMyReports();
                loadComputerStatus();
            } else {
                alert(result.message);
            }
        }

        async function deleteSession(sessionId) {
            if (!confirm('Are you sure you want to delete this session?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_session');
            formData.append('session_id', sessionId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                alert(result.message);
                loadSessions();
            } else {
                alert(result.message);
            }
        }

        async function loadMyReports() {
            try {
                const response = await fetch('./teacher_api.php?action=get_my_reports');
                const reports = await response.json();
                
                const container = document.getElementById('myReportsTable');
                
                if (reports.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-info-circle"></i><p>No reports submitted yet</p></div>';
                    return;
                }

                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Computer</th>
                                <th>Laboratory</th>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                reports.forEach(report => {
                    const statusClass = report.Status === 'Pending' ? 'status-occupied' : 'status-available';
                    html += `
                        <tr>
                            <td>${report.PC_Name}</td>
                            <td>${report.LabName}</td>
                            <td>${report.IssueType}</td>
                            <td>${report.Description}</td>
                            <td>${report.ReportDate}</td>
                            <td><span class="status-badge ${statusClass}">${report.Status}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading reports:', error);
            }
        }

        async function loadSessions() {
            const response = await fetch('teacher_api.php?action=get_sessions');
            const sessions = await response.json();
            
            const container = document.getElementById('sessionsTable');
            
            if (sessions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class='bx bx-calendar-x'></i>
                        <p>No sessions scheduled yet. Create your first session!</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table>
                    <thead>
                        <tr>
                            <th>Laboratory</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            sessions.forEach(session => {
                html += `
                    <tr>
                        <td>${session.LabName}</td>
                        <td>${session.Subject}</td>
                        <td>${session.Date}</td>
                        <td>${session.StartTime} - ${session.EndTime}</td>
                        <td>
                            <button class="btn-danger" onclick="deleteSession(${session.SessionID})">
                                <i class='bx bx-trash'></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        async function loadSessionsForAttendance() {
            try {
                const response = await fetch('./teacher_api.php?action=get_sessions');
                const sessions = await response.json();
                
                const select = document.getElementById('sessionSelect');
                select.innerHTML = '<option value="">Choose a session...</option>';
                
                sessions.forEach(session => {
                    const option = document.createElement('option');
                    option.value = session.SessionID;
                    option.textContent = `${session.Subject} - ${session.Date} (${session.StartTime} - ${session.EndTime})`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        }

        async function loadAttendance() {
            const sessionId = document.getElementById('sessionSelect').value;
            const container = document.getElementById('attendanceTable');
            
            if (!sessionId) {
                container.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`./teacher_api.php?action=get_session_attendance&session_id=${sessionId}`);
                const students = await response.json();
                
                if (students.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-user"></i><p>No students enrolled in this session</p></div>';
                    return;
                }

                // Calculate session duration
                const select = document.getElementById('sessionSelect');
                const selectedOption = select.options[select.selectedIndex];
                const sessionText = selectedOption.textContent;
                
                let html = `
                    <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                        <p style="color: #666;"><strong>Session:</strong> ${sessionText}</p>
                        <p style="color: #666;"><strong>Total Enrolled Students:</strong> ${students.length}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Computer Used</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Session Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                students.forEach(student => {
                    const duration = student.Duration || 'In Progress';
                    const loginTime = student.LoginTime || 'Not logged in';
                    const logoutTime = student.LogoutTime || '-';
                    const computerUsed = student.ComputerUsed || 'Not selected';
                    
                    html += `
                        <tr>
                            <td>${student.FullName}</td>
                            <td>${student.StudentID}</td>
                            <td>${computerUsed}</td>
                            <td>${loginTime}</td>
                            <td>${logoutTime}</td>
                            <td>
                                <span class="status-badge ${duration === 'In Progress' ? 'status-occupied' : 'status-available'}">
                                    ${duration}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading attendance:', error);
                container.innerHTML = '<div class="empty-state"><i class="bx bx-error"></i><p>Error loading attendance data</p></div>';
            }
        }

        async function loadLabStatus() {
            const response = await fetch('./teacher_api.php?action=get_lab_status');
            const labs = await response.json();
            
            const container = document.getElementById('labsGrid');
            
            if (labs.length === 0) {
                container.innerHTML = `<div class="empty-state"><i class='bx bx-building-house'></i><p>No laboratories available</p></div>`;
                return;
            }

            let html = '<div class="labs-grid">';
            labs.forEach(lab => {
                const statusClass = lab.Status === 'Available' ? 'status-available' : 
                                   lab.Status === 'Occupied' ? 'status-occupied' : 'status-maintenance';
                html += `
                    <div class="lab-card">
                        <h4>${lab.LabName}</h4>
                        <div class="lab-status">
                            <span class="status-badge ${statusClass}">${lab.Status}</span>
                            <span style="color: #666; font-size: 14px;">${lab.computer_count} Computers</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        async function loadComputerStatus() {
            try {
                const response = await fetch('./teacher_api.php?action=get_computers');
                const computers = await response.json();
                
                const container = document.getElementById('computersTable');
                
                if (computers.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-desktop"></i><p>No computers registered</p></div>';
                    return;
                }

                // Group computers by lab
                const labGroups = {};
                computers.forEach(computer => {
                    if (!labGroups[computer.LabName]) {
                        labGroups[computer.LabName] = [];
                    }
                    labGroups[computer.LabName].push(computer);
                });

                let html = '';
                
                // Create organized display for each lab
                Object.keys(labGroups).sort().forEach(labName => {
                    const labComputers = labGroups[labName];
                    const availableCount = labComputers.filter(c => c.Status === 'Available').length;
                    const inUseCount = labComputers.filter(c => c.Status === 'In-Use' || c.Status === 'In Use').length;
                    const maintenanceCount = labComputers.filter(c => c.Status === 'Maintenance').length;
                    const totalCount = labComputers.length;
                    
                    html += `
                        <div style="background: #f9f9f9; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid #667eea;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <h3 style="color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                    <i class='bx bxs-building' style="color: #667eea;"></i> 
                                    ${labName}
                                </h3>
                                <div style="display: flex; gap: 15px; font-size: 13px; font-weight: 500;">
                                    <span style="color: #2e7d32; display: flex; align-items: center; gap: 5px;">
                                        <i class='bx bx-check-circle'></i> ${availableCount}
                                    </span>
                                    <span style="color: #ef6c00; display: flex; align-items: center; gap: 5px;">
                                        <i class='bx bx-time'></i> ${inUseCount}
                                    </span>
                                    <span style="color: #c62828; display: flex; align-items: center; gap: 5px;">
                                        <i class='bx bx-wrench'></i> ${maintenanceCount}
                                    </span>
                                    <span style="color: #666; border-left: 2px solid #ddd; padding-left: 15px;">
                                        Total: ${totalCount}
                                    </span>
                                </div>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
                    `;
                    
                    labComputers.sort((a, b) => a.PC_Name.localeCompare(b.PC_Name)).forEach(computer => {
                        const status = computer.Status === 'In Use' ? 'In-Use' : computer.Status;
                        const statusClass = status === 'Available' ? 'status-available' : 
                                           status === 'In-Use' ? 'status-occupied' : 'status-maintenance';
                        const icon = status === 'Available' ? 'bx-desktop' : 
                                    status === 'In-Use' ? 'bxs-desktop' : 'bx-wrench';
                        const color = status === 'Available' ? '#4caf50' : 
                                     status === 'In-Use' ? '#ff9800' : '#f44336';
                        
                        html += `
                            <div style="background: white; padding: 12px; border-radius: 8px; text-align: center; border: 2px solid ${color}; transition: transform 0.2s;">
                                <i class='bx ${icon}' style="font-size: 32px; color: ${color};"></i>
                                <div style="font-weight: 600; color: #333; margin-top: 5px; font-size: 13px;">${computer.PC_Name}</div>
                                <span class="status-badge ${statusClass}" style="font-size: 10px; margin-top: 5px; display: inline-block;">${status}</span>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                });
                
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading computers:', error);
                document.getElementById('computersTable').innerHTML = '<div class="empty-state"><i class="bx bx-error"></i><p>Error loading computer status</p></div>';
            }
        }

        // Load all data on page load
        loadMyReports();
        loadSessions();
        loadLabStatus();
        loadComputerStatus();

        // Refresh data every 30 seconds
        setInterval(() => {
            loadMyReports();
            loadSessions();
            loadLabStatus();
            loadComputerStatus();
        }, 30000);
    </script>
</body>
</html>