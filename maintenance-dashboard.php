<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Maintenance Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/maintenance_style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-wrench'></i>
                <span>Maintenance</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li class="active">
                        <a href="maintenance-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="maintenance-repair.php">
                            <i class='bx bx-wrench'></i>
                            <span>Operations</span>
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
                    <h1>Maintenance Dashboard</h1>
                    <p class="header-subtitle">System Overview</p>
                </div>
                <div class="header-actions">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search maintenance tasks...">
                    </div>
                </div>
            </header>
        </main>
    </div>
</body>
</html>