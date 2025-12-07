<?php 
session_start();
require_once '../config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.html');
    exit;
}
if ($_SESSION['role'] !== 'Admin') {
    die('Access Denied: Admin privileges required');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch($_POST['action']) {
                case 'add_lab':
                    $stmt = $pdo->prepare("INSERT INTO computer_labs (LabName, Status) VALUES (?, 'Available')");
                    $stmt->execute([$_POST['lab_name']]);
                    echo json_encode(['success' => true, 'message' => 'Laboratory added successfully']);
                    break;
                    
                case 'delete_lab':
                    $stmt = $pdo->prepare("DELETE FROM computer_labs WHERE LabID = ?");
                    $stmt->execute([$_POST['lab_id']]);
                    echo json_encode(['success' => true, 'message' => 'Laboratory deleted successfully']);
                    break;
                    
                case 'add_computer':
                    $stmt = $pdo->prepare("INSERT INTO computers (PC_Name, LabID, Status) VALUES (?, ?, 'Available')");
                    $stmt->execute([$_POST['pc_name'], $_POST['lab_id']]);
                    echo json_encode(['success' => true, 'message' => 'Computer added successfully']);
                    break;
                    
                case 'delete_computer':
                    $stmt = $pdo->prepare("DELETE FROM computers WHERE ComputerID = ?");
                    $stmt->execute([$_POST['computer_id']]);
                    echo json_encode(['success' => true, 'message' => 'Computer deleted successfully']);
                    break;
                    
                case 'move_computer':
                    $stmt = $pdo->prepare("UPDATE computers SET LabID = ? WHERE ComputerID = ?");
                    $stmt->execute([$_POST['new_lab_id'], $_POST['computer_id']]);
                    echo json_encode(['success' => true, 'message' => 'Computer moved successfully']);
                    break;
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch labs with computer counts
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
$labs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Labs</title>
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

        .btn-back {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #5568d3;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            color: #333;
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        .labs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }

        .lab-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }

        .lab-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .lab-card-header h3 {
            color: #333;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .lab-actions {
            display: flex;
            gap: 8px;
        }

        .computers-list {
            margin-top: 1rem;
        }

        .computers-list h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .computer-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: background 0.2s;
        }

        .computer-item:hover {
            background: #f0f0f0;
        }

        .computer-name {
            font-weight: 500;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .computer-actions {
            display: flex;
            gap: 8px;
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

        .empty-computers {
            text-align: center;
            padding: 2rem;
            color: #999;
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        @media (max-width: 768px) {
            .labs-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h2><i class='bx bxs-building'></i> Manage Laboratories</h2>
        <a href="admin_dashboard.php" class="btn-back">
            <i class='bx bx-arrow-back'></i> Back to Dashboard
        </a>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Laboratory Management</h1>
            <button class="btn-primary" onclick="openAddLabModal()">
                <i class='bx bx-plus'></i> Add Laboratory
            </button>
        </div>

        <div class="labs-grid" id="labsContainer">
            <?php foreach ($labs as $lab): ?>
                <div class="lab-card" data-lab-id="<?= $lab['LabID'] ?>">
                    <div class="lab-card-header">
                        <div>
                            <h3>
                                <i class='bx bxs-building' style="color: #667eea;"></i>
                                <?= htmlspecialchars($lab['LabName']) ?>
                            </h3>
                            <p style="color: #999; font-size: 13px; margin-top: 5px;">
                                <?= $lab['computer_count'] ?> computer(s) assigned
                            </p>
                        </div>
                        <div class="lab-actions">
                            <button class="btn-success" onclick="openAddComputerModal(<?= $lab['LabID'] ?>, '<?= htmlspecialchars($lab['LabName']) ?>')">
                                <i class='bx bx-plus'></i> Add PC
                            </button>
                            <button class="btn-danger" onclick="deleteLab(<?= $lab['LabID'] ?>)">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    </div>

                    <div class="computers-list">
                        <h4>
                            <i class='bx bx-desktop'></i>
                            Assigned Computers
                        </h4>
                        <div id="computers-<?= $lab['LabID'] ?>"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Lab Modal -->
    <div id="addLabModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Laboratory</h3>
                <button class="close-btn" onclick="closeAddLabModal()">&times;</button>
            </div>
            <form onsubmit="addLab(event)">
                <div class="form-group">
                    <label>Laboratory Name *</label>
                    <input type="text" name="lab_name" required placeholder="e.g., Computer Lab 1">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class='bx bx-save'></i> Add Laboratory
                </button>
            </form>
        </div>
    </div>

    <!-- Add Computer Modal -->
    <div id="addComputerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Computer to <span id="targetLabName"></span></h3>
                <button class="close-btn" onclick="closeAddComputerModal()">&times;</button>
            </div>
            <form onsubmit="addComputer(event)">
                <input type="hidden" id="targetLabId" name="lab_id">
                <div class="form-group">
                    <label>Computer Name *</label>
                    <input type="text" name="pc_name" required placeholder="e.g., PC-001">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class='bx bx-save'></i> Add Computer
                </button>
            </form>
        </div>
    </div>

    <!-- Move Computer Modal -->
    <div id="moveComputerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Move Computer</h3>
                <button class="close-btn" onclick="closeMoveComputerModal()">&times;</button>
            </div>
            <form onsubmit="moveComputer(event)">
                <input type="hidden" id="moveComputerId" name="computer_id">
                <div class="form-group">
                    <label>Select Destination Lab *</label>
                    <select name="new_lab_id" required id="destinationLabSelect">
                        <option value="">Choose a laboratory...</option>
                        <?php foreach ($labs as $lab): ?>
                            <option value="<?= $lab['LabID'] ?>"><?= htmlspecialchars($lab['LabName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">
                    <i class='bx bx-transfer'></i> Move Computer
                </button>
            </form>
        </div>
    </div>

    <script>
        function openAddLabModal() {
            document.getElementById('addLabModal').classList.add('active');
        }

        function closeAddLabModal() {
            document.getElementById('addLabModal').classList.remove('active');
        }

        function openAddComputerModal(labId, labName) {
            document.getElementById('targetLabId').value = labId;
            document.getElementById('targetLabName').textContent = labName;
            document.getElementById('addComputerModal').classList.add('active');
        }

        function closeAddComputerModal() {
            document.getElementById('addComputerModal').classList.remove('active');
        }

        function openMoveComputerModal(computerId, currentLabId) {
            document.getElementById('moveComputerId').value = computerId;
            
            // Remove current lab from options
            const select = document.getElementById('destinationLabSelect');
            Array.from(select.options).forEach(option => {
                if (option.value == currentLabId) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            });
            
            document.getElementById('moveComputerModal').classList.add('active');
        }

        function closeMoveComputerModal() {
            document.getElementById('moveComputerModal').classList.remove('active');
        }

        async function addLab(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'add_lab');

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

        async function deleteLab(labId) {
            if (!confirm('Are you sure you want to delete this laboratory? All computers in this lab will also be deleted.')) return;

            const formData = new FormData();
            formData.append('action', 'delete_lab');
            formData.append('lab_id', labId);

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

        async function addComputer(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'add_computer');

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

        async function deleteComputer(computerId) {
            if (!confirm('Are you sure you want to delete this computer?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_computer');
            formData.append('computer_id', computerId);

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

        async function moveComputer(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'move_computer');

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

        async function loadComputers() {
            try {
                const response = await fetch('manage_labs_api.php?action=get_all_computers');
                const computers = await response.json();

                // Group by lab
                const labGroups = {};
                computers.forEach(computer => {
                    if (!labGroups[computer.LabID]) {
                        labGroups[computer.LabID] = [];
                    }
                    labGroups[computer.LabID].push(computer);
                });

                // Display computers for each lab
                Object.keys(labGroups).forEach(labId => {
                    const container = document.getElementById(`computers-${labId}`);
                    const labComputers = labGroups[labId];

                    if (labComputers.length === 0) {
                        container.innerHTML = '<div class="empty-computers">No computers assigned yet</div>';
                        return;
                    }

                    let html = '';
                    labComputers.forEach(computer => {
                        const statusClass = computer.Status === 'Available' ? 'status-available' : 
                                           computer.Status === 'In-Use' ? 'status-occupied' : 'status-maintenance';
                        html += `
                            <div class="computer-item">
                                <div class="computer-name">
                                    <i class='bx bx-desktop'></i>
                                    ${computer.PC_Name}
                                    <span class="status-badge ${statusClass}">${computer.Status}</span>
                                </div>
                                <div class="computer-actions">
                                    <button class="btn-success" onclick="openMoveComputerModal(${computer.ComputerID}, ${computer.LabID})" title="Move to another lab">
                                        <i class='bx bx-transfer'></i>
                                    </button>
                                    <button class="btn-danger" onclick="deleteComputer(${computer.ComputerID})">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    container.innerHTML = html;
                });

                // Handle labs with no computers
                document.querySelectorAll('[id^="computers-"]').forEach(container => {
                    const labId = container.id.replace('computers-', '');
                    if (!labGroups[labId] || labGroups[labId].length === 0) {
                        container.innerHTML = '<div class="empty-computers"><i class="bx bx-desktop" style="font-size: 32px; opacity: 0.3;"></i><p>No computers assigned yet</p></div>';
                    }
                });

            } catch (error) {
                console.error('Error loading computers:', error);
            }
        }

        // Load computers on page load
        loadComputers();
    </script>
</body>
</html>