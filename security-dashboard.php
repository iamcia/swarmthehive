<?php
include 'dbconn.php';
session_start();
// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Security') {
    header("Location: management-index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Security Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/security_style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-shield'></i>
                <span>Security</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li class="active">
                        <a href="security-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-gatepass.php">
                            <i class='bx bx-key'></i>
                            <span>Gate Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-guestcheck.php">
                            <i class='bx bx-door-open'></i>
                            <span>Guest Check In/Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-visitor.php">
                            <i class='bx bx-id-card'></i>
                            <span>Visitor Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-incident.php">
                            <i class='bx bx-notepad'></i>
                            <span>Incident Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-history.php">
                            <i class='bx bx-history'></i>
                            <span>Logs</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-settings.php">
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

        <main>
            <header class="main-header">
                <div class="header-content">
                    <h1>Security Dashboard</h1>
                    <p class="header-subtitle">System Overview</p>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search security tasks...">
                    </div>
                </div>
            </header>
        </main>
    </div>
</body>
</html>