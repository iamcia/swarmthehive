<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Finance') {
    // If the user is not logged in or is not a finance user, redirect to login page
    header("Location: management-index.php");
    exit();
}

// Fetch logs from the database
$sql = "SELECT l.Log_ID, l.Action_Type, l.Action_Description, l.Action_Date, m.firstname, m.lastname 
        FROM finance_logs l 
        JOIN managementaccount m ON l.Management_Code = m.Management_Code 
        ORDER BY l.Action_Date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Finance Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/finance_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/finance-upload-style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-money'></i>
                <span>Finance</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="finance-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-upload.php">
                            <i class='bx bx-upload'></i>
                            <span>Upload SOA</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-status.php">
                            <i class='bx bx-check-circle'></i>
                            <span>Status</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-history'></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="finance-settings.php">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main>
            <header class="main-header">
                <div class="header-content">
                    <h1>Finance Logs</h1>
                    <p class="header-subtitle">View all finance logs</p>
                </div>
            </header>

            <!-- Logs Display -->
            <div class="logs-table">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Action Type</th>
                                <th>Action Description</th>
                                <th>By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['Action_Type']; ?></td>
                                    <td><?php echo $row['Action_Description']; ?></td>
                                    <td><?php echo $row['firstname'] . ' ' . $row['lastname']; ?></td>
                                    <td><?php echo $row['Action_Date']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No logs available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Styles for the Logs Table -->
    <style>
        .logs-table table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .logs-table th, .logs-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .logs-table th {
            background-color: #f4f4f4;
        }

        .logs-table tr:hover {
            background-color: #f1f1f1;
        }
    </style>

</body>
</html>

<?php
$conn->close();
?>
