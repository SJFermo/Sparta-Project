<?php 
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}
if ($_SESSION['role'] !== 'Admin') {
    die('Access Denied: Admin privileges required');
}

// Handle computer status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once '../config.php';
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_computer_status') {
        try {
            $stmt = $pdo->prepare("UPDATE computers SET Status = ? WHERE ComputerID = ?");
            $stmt->execute([$_POST['status'], $_POST['computer_id']]);
            echo json_encode(['success' => true, 'message' => 'Computer status updated']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'resolve_report') {
        try {
            $stmt = $pdo->prepare("UPDATE computer_reports SET Status = 'Resolved' WHERE ReportID = ?");
            $stmt->execute([$_POST['report_id']]);
            
            // Also update computer status
            $stmt = $pdo->prepare("UPDATE computers SET Status = 'Available' WHERE ComputerID = (SELECT ComputerID FROM computer_reports WHERE ReportID = ?)");
            $stmt->execute([$_POST['report_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Report resolved']);
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
    <title>Admin Portal</title>
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

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .action-card i {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .action-card h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .action-card p {
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

        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-resolved {
            background: #e8f5e9;
            color: #2e7d32;
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

        select.status-selector {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            background: white;
        }

        select.status-selector:focus {
            outline: none;
            border-color: #667eea;
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

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2><i class='bx bxs-dashboard'></i> Admin Panel</h2>
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
            <p>Manage your computer laboratory system from this dashboard</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class='bx bxs-user'></i>
                <h3 id="totalUsers">0</h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-school'></i>
                <h3 id="totalTeachers">0</h3>
                <p>Teachers</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-graduation'></i>
                <h3 id="totalStudents">0</h3>
                <p>Students</p>
            </div>
            <div class="stat-card">
                <i class='bx bxs-desktop'></i>
                <h3 id="totalLabs">0</h3>
                <p>Computer Labs</p>
            </div>
        </div>

        <h2 style="color: white; margin-bottom: 1rem;">Quick Actions</h2>
        <div class="actions-grid">
            <div class="action-card" onclick="showSection('users')">
                <i class='bx bxs-user-account'></i>
                <h3>Manage Users</h3>
                <p>View and manage system users</p>
            </div>
            <a href="manage_labs.php" class="action-card">
                <i class='bx bxs-building'></i>
                <h3>Manage Labs</h3>
                <p>Add, edit, and configure laboratories</p>
            </a>
            <div class="action-card" onclick="showSection('computers')">
                <i class='bx bxs-devices'></i>
                <h3>Manage Computers</h3>
                <p>Track and maintain computers</p>
            </div>
            <div class="action-card" onclick="showSection('sessions')">
                <i class='bx bxs-calendar'></i>
                <h3>View Sessions</h3>
                <p>Monitor all lab sessions</p>
            </div>
            <div class="action-card" onclick="showSection('reports')">
                <i class='bx bxs-report'></i>
                <h3>Reports</h3>
                <p>View system usage and maintenance reports</p>
            </div>
        </div>

        <!-- Users Section -->
        <div id="usersSection" class="content-section" style="display: none;">
            <div class="section-header">
                <h2><i class='bx bxs-user-account'></i> System Users</h2>
            </div>
            <div id="usersTable"></div>
        </div>

        <!-- Computers Section -->
        <div id="computersSection" class="content-section" style="display: none;">
            <div class="section-header">
                <h2><i class='bx bxs-devices'></i> Manage Computers</h2>
            </div>
            <div id="computersTable"></div>
        </div>

        <!-- Sessions Section -->
        <div id="sessionsSection" class="content-section" style="display: none;">
            <div class="section-header">
                <h2><i class='bx bxs-calendar'></i> All Sessions</h2>
            </div>
            <div id="sessionsTable"></div>
        </div>

        <!-- Reports Section -->
        <div id="reportsSection" class="content-section" style="display: none;">
            <div class="section-header">
                <h2><i class='bx bxs-report'></i> System Reports</h2>
            </div>
            <div id="reportsContent"></div>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        async function loadStats() {
            try {
                const response = await fetch('admin_api.php?action=get_statistics');
                const stats = await response.json();
                
                document.getElementById('totalUsers').textContent = stats.total_users || 0;
                document.getElementById('totalTeachers').textContent = stats.total_teachers || 0;
                document.getElementById('totalStudents').textContent = stats.total_students || 0;
                document.getElementById('totalLabs').textContent = stats.total_labs || 0;
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        function showSection(section) {
            document.getElementById('usersSection').style.display = 'none';
            document.getElementById('computersSection').style.display = 'none';
            document.getElementById('sessionsSection').style.display = 'none';
            document.getElementById('reportsSection').style.display = 'none';

            switch(section) {
                case 'users':
                    document.getElementById('usersSection').style.display = 'block';
                    loadUsers();
                    break;
                case 'computers':
                    document.getElementById('computersSection').style.display = 'block';
                    loadComputers();
                    break;
                case 'sessions':
                    document.getElementById('sessionsSection').style.display = 'block';
                    loadSessions();
                    break;
                case 'reports':
                    document.getElementById('reportsSection').style.display = 'block';
                    loadReports();
                    break;
            }

            document.querySelector('.content-section[style*="block"]').scrollIntoView({ behavior: 'smooth' });
        }

        async function loadUsers() {
            try {
                const response = await fetch('admin_api.php?action=get_all_users');
                const users = await response.json();
                
                const container = document.getElementById('usersTable');
                
                if (users.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-user"></i><p>No users found</p></div>';
                    return;
                }

                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>User ID</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                users.forEach(user => {
                    html += `
                        <tr>
                            <td>${user.FullName}</td>
                            <td>${user.Username}</td>
                            <td><span class="status-badge status-available">${user.Role}</span></td>
                            <td>${user.UserID}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function loadComputers() {
            try {
                const response = await fetch('admin_api.php?action=get_computers');
                const computers = await response.json();
                
                const container = document.getElementById('computersTable');
                
                if (computers.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-desktop"></i><p>No computers found</p></div>';
                    return;
                }

                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Computer Name</th>
                                <th>Laboratory</th>
                                <th>Status</th>
                                <th>Set Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                computers.forEach(computer => {
                    const statusClass = computer.Status === 'Available' ? 'status-available' : 
                                       computer.Status === 'In-Use' ? 'status-occupied' : 'status-maintenance';
                    html += `
                        <tr>
                            <td>${computer.PC_Name}</td>
                            <td>${computer.LabName}</td>
                            <td><span class="status-badge ${statusClass}">${computer.Status}</span></td>
                            <td>
                                <select class="status-selector" onchange="updateComputerStatus(${computer.ComputerID}, this.value)">
                                    <option value="Available" ${computer.Status === 'Available' ? 'selected' : ''}>Available</option>
                                    <option value="In-Use" ${computer.Status === 'In-Use' ? 'selected' : ''}>In-Use</option>
                                    <option value="Maintenance" ${computer.Status === 'Maintenance' ? 'selected' : ''}>Maintenance</option>
                                </select>
                            </td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading computers:', error);
            }
        }

        async function updateComputerStatus(computerId, newStatus) {
            try {
                const formData = new FormData();
                formData.append('action', 'update_computer_status');
                formData.append('computer_id', computerId);
                formData.append('status', newStatus);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    loadComputers();
                    loadStats();
                } else {
                    alert(result.message);
                    loadComputers();
                }
            } catch (error) {
                console.error('Error updating status:', error);
                alert('Error updating computer status');
                loadComputers();
            }
        }

        async function loadSessions() {
            try {
                const response = await fetch('admin_api.php?action=get_all_sessions');
                const sessions = await response.json();
                
                const container = document.getElementById('sessionsTable');
                
                if (sessions.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="bx bx-calendar"></i><p>No sessions found</p></div>';
                    return;
                }

                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Subject</th>
                                <th>Laboratory</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                sessions.forEach(session => {
                    html += `
                        <tr>
                            <td>${session.TeacherName}</td>
                            <td>${session.Subject}</td>
                            <td>${session.LabName}</td>
                            <td>${session.Date}</td>
                            <td>${session.StartTime} - ${session.EndTime}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        }

        async function resolveReport(reportId) {
            if (!confirm('Mark this report as resolved?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'resolve_report');
                formData.append('report_id', reportId);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    loadReports();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error resolving report:', error);
                alert('Error resolving report');
            }
        }

        async function loadReports() {
            try {
                const [utilResponse, teacherResponse, reportResponse] = await Promise.all([
                    fetch('admin_api.php?action=get_lab_utilization'),
                    fetch('admin_api.php?action=get_teacher_sessions_count'),
                    fetch('admin_api.php?action=get_computer_reports')
                ]);

                const utilization = await utilResponse.json();
                const teacherData = await teacherResponse.json();
                const computerReports = await reportResponse.json();
                
                const container = document.getElementById('reportsContent');
                
                let html = '<h3 style="margin-bottom: 1rem; color: #333;">Computer Maintenance Reports</h3>';
                if (computerReports && computerReports.length > 0) {
                    html += `
                        <table style="margin-bottom: 2rem;">
                            <thead>
                                <tr>
                                    <th>Computer</th>
                                    <th>Laboratory</th>
                                    <th>Reported By</th>
                                    <th>Issue</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    computerReports.forEach(report => {
                        const statusClass = report.Status === 'Pending' ? 'status-pending' : 'status-resolved';
                        html += `
                            <tr>
                                <td>${report.PC_Name}</td>
                                <td>${report.LabName}</td>
                                <td>${report.TeacherName}</td>
                                <td>${report.IssueType}</td>
                                <td>${report.ReportDate}</td>
                                <td><span class="status-badge ${statusClass}">${report.Status}</span></td>
                                <td>
                                    ${report.Status === 'Pending' ? 
                                        `<button class="btn-success" onclick="resolveReport(${report.ReportID})">
                                            <i class='bx bx-check'></i> Resolve
                                        </button>` : 
                                        '<span style="color: #4caf50;">Resolved</span>'}
                                </td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table>';
                } else {
                    html += '<p style="color: #999; text-align: center; padding: 2rem;">No maintenance reports</p>';
                }

                html += '<h3 style="margin-bottom: 1rem; color: #333;">Lab Utilization Report</h3>';
                html += `
                    <table style="margin-bottom: 2rem;">
                        <thead>
                            <tr>
                                <th>Laboratory</th>
                                <th>Total Sessions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                utilization.forEach(lab => {
                    const statusClass = lab.Status === 'Available' ? 'status-available' : 
                                       lab.Status === 'Occupied' ? 'status-occupied' : 'status-maintenance';
                    html += `
                        <tr>
                            <td>${lab.LabName}</td>
                            <td>${lab.session_count}</td>
                            <td><span class="status-badge ${statusClass}">${lab.Status}</span></td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';

                html += '<h3 style="margin-bottom: 1rem; color: #333;">Teacher Activity Report</h3>';
                html += `
                    <table>
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>Total Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                teacherData.forEach(teacher => {
                    html += `
                        <tr>
                            <td>${teacher.FullName}</td>
                            <td>${teacher.session_count}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table>';
                container.innerHTML = html;
            } catch (error) {
                console.error('Error loading reports:', error);
            }
        }

        loadStats();
        setInterval(loadStats, 30000);
    </script>
</body>
</html>