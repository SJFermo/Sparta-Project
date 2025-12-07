<?php
// direct_test.php - Direct database test without API
require_once '../config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct Database Test</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .test-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .test-section h2 {
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }

        .status {
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .status.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status.error {
            background: #ffebee;
            color: #c62828;
        }

        .status.warning {
            background: #fff3e0;
            color: #ef6c00;
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
            border-bottom: 2px solid #e0e0e0;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }

        .big-number {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }

        pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
        }

        .test-link {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-right: 10px;
            margin-top: 10px;
        }

        .test-link:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Direct Database & File System Test</h1>

        <!-- Database Connection -->
        <div class="test-section">
            <h2>1. Database Connection Test</h2>
            <?php
            try {
                $pdo->query("SELECT 1");
                echo '<div class="status success">✅ Database Connected Successfully!</div>';
                echo '<p>Host: localhost | Database: web_db</p>';
            } catch (Exception $e) {
                echo '<div class="status error">❌ Database Connection Failed: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- Table Counts -->
        <div class="test-section">
            <h2>2. Database Tables & Counts</h2>
            <?php
            try {
                $tables = [
                    'users' => 'Users (Admin, Teacher, Student)',
                    'teachers' => 'Teachers',
                    'students' => 'Students',
                    'admin' => 'Admin',
                    'computer_labs' => 'Computer Labs',
                    'computers' => 'Computers',
                    'sessions' => 'Sessions'
                ];

                echo '<table>';
                echo '<tr><th>Table Name</th><th>Description</th><th>Count</th><th>Status</th></tr>';
                
                $allGood = true;
                foreach ($tables as $table => $description) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
                        $count = $stmt->fetch()['count'];
                        $status = $count > 0 ? '<span style="color: green;">✅ Has Data</span>' : '<span style="color: orange;">⚠️ Empty</span>';
                        if ($count == 0) $allGood = false;
                        echo "<tr><td>{$table}</td><td>{$description}</td><td><strong>{$count}</strong></td><td>{$status}</td></tr>";
                    } catch (Exception $e) {
                        echo "<tr><td>{$table}</td><td>{$description}</td><td>-</td><td><span style='color: red;'>❌ Error</span></td></tr>";
                        $allGood = false;
                    }
                }
                
                echo '</table>';
                
                if (!$allGood) {
                    echo '<div class="status warning">⚠️ Some tables are empty. You need to add sample data!</div>';
                    echo '<details><summary><strong>Click here to see SQL to add sample data</strong></summary>';
                    echo '<pre>';
echo "-- Add sample teacher
INSERT INTO users (FullName, Username, Password, Role)
VALUES ('John Teacher', 'teacher1', '\$2y\$10\$yX7EwErOQkXHVCVfK1ZMIeM06Z70KUXji2cN/rkLgYMuFB9bCFEnG', 'Teacher');

INSERT INTO teachers (UserID, FullName)
VALUES (LAST_INSERT_ID(), 'John Teacher');

-- Add sample student
INSERT INTO users (FullName, Username, Password, Role)
VALUES ('Jane Student', 'student1', '\$2y\$10\$yX7EwErOQkXHVCVfK1ZMIeM06Z70KUXji2cN/rkLgYMuFB9bCFEnG', 'Student');

INSERT INTO students (UserID, FullName)
VALUES (LAST_INSERT_ID(), 'Jane Student');

-- Add sample labs
INSERT INTO computer_labs (LabName, Description, Status) VALUES
('Computer Lab 1', 'Main computer laboratory', 'Available'),
('Computer Lab 2', 'Programming lab', 'Available'),
('Computer Lab 3', 'Multimedia lab', 'Maintenance');

-- Add sample computers
INSERT INTO computers (LabID, PC_Name, Status) VALUES
(1, 'PC-01', 'Available'),
(1, 'PC-02', 'Available'),
(1, 'PC-03', 'In-Use'),
(2, 'PC-04', 'Available'),
(2, 'PC-05', 'UnderMaintenance');

-- Add sample sessions (TeacherID should be the actual ID from your teachers table)
INSERT INTO sessions (TeacherID, LabID, Date, StartTime, EndTime, Subject) VALUES
(1, 1, '2024-12-10', '09:00:00', '11:00:00', 'Computer Programming'),
(1, 2, '2024-12-11', '13:00:00', '15:00:00', 'Web Development');";
                    echo '</pre></details>';
                }
                
            } catch (Exception $e) {
                echo '<div class="status error">❌ Error checking tables: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- File Check -->
        <div class="test-section">
            <h2>3. Required Files Check</h2>
            <?php
            $requiredFiles = [
                'Dashboard Files' => [
                    'admin_dashboard.php',
                    'teacher_dashboard.php',
                    'student_dashboard.php'
                ],
                'API Files' => [
                    'admin_api.php',
                    'teacher_api.php',
                    'student_api.php'
                ],
                'Core Files' => [
                    '../config.php',
                    '../login.php',
                    '../register.php',
                    '../logout.php'
                ]
            ];

            $allFilesExist = true;
            foreach ($requiredFiles as $category => $files) {
                echo "<h3>{$category}</h3>";
                echo '<table>';
                echo '<tr><th>File Name</th><th>Status</th><th>Path</th></tr>';
                
                foreach ($files as $file) {
                    $exists = file_exists($file);
                    $status = $exists ? '<span style="color: green;">✅ EXISTS</span>' : '<span style="color: red;">❌ MISSING</span>';
                    $fullPath = realpath($file);
                    $displayPath = $fullPath ? $fullPath : 'Not found';
                    echo "<tr><td>{$file}</td><td>{$status}</td><td style='font-size: 11px;'>{$displayPath}</td></tr>";
                    if (!$exists) $allFilesExist = false;
                }
                
                echo '</table><br>';
            }

            if (!$allFilesExist) {
                echo '<div class="status error">❌ Some required files are missing!</div>';
            } else {
                echo '<div class="status success">✅ All required files exist!</div>';
            }
            ?>
        </div>

        <!-- Sample Data Display -->
        <div class="test-section">
            <h2>4. Sample Data Preview</h2>
            
            <h3>Users in Database:</h3>
            <?php
            try {
                $stmt = $pdo->query("SELECT UserID, FullName, Username, Role FROM users LIMIT 10");
                $users = $stmt->fetchAll();
                
                if (count($users) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Full Name</th><th>Username</th><th>Role</th></tr>';
                    foreach ($users as $user) {
                        echo "<tr><td>{$user['UserID']}</td><td>{$user['FullName']}</td><td>{$user['Username']}</td><td>{$user['Role']}</td></tr>";
                    }
                    echo '</table>';
                } else {
                    echo '<div class="status warning">⚠️ No users found in database</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status error">❌ Error: ' . $e->getMessage() . '</div>';
            }
            ?>

            <h3 style="margin-top: 2rem;">Computer Labs:</h3>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM computer_labs LIMIT 10");
                $labs = $stmt->fetchAll();
                
                if (count($labs) > 0) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Lab Name</th><th>Description</th><th>Status</th></tr>';
                    foreach ($labs as $lab) {
                        echo "<tr><td>{$lab['LabID']}</td><td>{$lab['LabName']}</td><td>{$lab['Description']}</td><td>{$lab['Status']}</td></tr>";
                    }
                    echo '</table>';
                } else {
                    echo '<div class="status warning">⚠️ No labs found in database</div>';
                }
            } catch (Exception $e) {
                echo '<div class="status error">❌ Error: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <!-- Test Login Credentials -->
        <div class="test-section">
            <h2>5. Test Login Credentials</h2>
            <p>Use these credentials to login (password is <strong>password</strong> for all):</p>
            <table>
                <tr>
                    <th>Role</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Dashboard Link</th>
                </tr>
                <tr>
                    <td>Admin</td>
                    <td><strong>admin</strong></td>
                    <td>password</td>
                    <td><a href="../index.html" class="test-link">Login Page</a></td>
                </tr>
                <tr>
                    <td>Teacher</td>
                    <td><strong>teacher1</strong></td>
                    <td>password</td>
                    <td><a href="../index.html" class="test-link">Login Page</a></td>
                </tr>
                <tr>
                    <td>Student</td>
                    <td><strong>student1</strong></td>
                    <td>password</td>
                    <td><a href="../index.html" class="test-link">Login Page</a></td>
                </tr>
            </table>
        </div>

        <!-- Next Steps -->
        <div class="test-section">
            <h2>6. Next Steps - Test Dashboards Manually</h2>
            <div class="status success">✅ Your database and files are ready!</div>
            
            <h3>Manual Testing Steps:</h3>
            <ol style="line-height: 2;">
                <li>Go to: <a href="../index.html" class="test-link">http://localhost/web-project/index.html</a></li>
                <li>Login as <strong>admin</strong> with password <strong>password</strong></li>
                <li>Check if statistics load (should show numbers, not 0)</li>
                <li>Click on "Manage Users", "Manage Labs", etc.</li>
                <li>Logout and repeat for <strong>teacher1</strong> and <strong>student1</strong></li>
            </ol>

            <h3>What to Look For:</h3>
            <ul style="line-height: 2;">
                <li>✅ Numbers in statistics cards (not all zeros)</li>
                <li>✅ Tables showing data when you click action cards</li>
                <li>✅ No errors in browser console (Press F12)</li>
                <li>✅ Responsive design when resizing window</li>
            </ul>
        </div>

        <!-- Responsive Test -->
        <div class="test-section">
            <h2>7. Test Responsive Design</h2>
            <p><strong>How to test:</strong></p>
            <ol style="line-height: 2;">
                <li>Login to any dashboard</li>
                <li>Press <strong>F12</strong> to open Developer Tools</li>
                <li>Press <strong>Ctrl + Shift + M</strong> (or click phone icon)</li>
                <li>Select different devices from dropdown</li>
                <li>Check if layout adjusts properly</li>
            </ol>
            
            <p><strong>Test these sizes:</strong></p>
            <ul>
                <li>Desktop: 1920 x 1080</li>
                <li>Tablet: 768 x 1024</li>
                <li>Mobile: 375 x 667 (iPhone SE)</li>
            </ul>
        </div>
    </div>
</body>
</html>