<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Admin)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}

// Fetch total users (count from both ownerinformation and tenantinformation)
$sqlOwnerUsers = "SELECT COUNT(*) AS total_users FROM ownerinformation";  
$sqlTenantUsers = "SELECT COUNT(*) AS total_users FROM tenantinformation";  

$sqlServiceRequests = "SELECT COUNT(*) AS total_requests FROM ownertenantconcerns"; 
$sqlAnnouncements = "SELECT COUNT(*) AS total_announcements FROM announcements"; 
$sqlFinanceRecords = "SELECT COUNT(*) AS total_finance FROM soafinance_records";  

$resultOwnerUsers = $conn->query($sqlOwnerUsers);
$resultTenantUsers = $conn->query($sqlTenantUsers);
$resultServiceRequests = $conn->query($sqlServiceRequests);
$resultAnnouncements = $conn->query($sqlAnnouncements);
$resultFinanceRecords = $conn->query($sqlFinanceRecords);

$totalOwnerUsers = $resultOwnerUsers->fetch_assoc()['total_users'];
$totalTenantUsers = $resultTenantUsers->fetch_assoc()['total_users'];

$totalUsers = $totalOwnerUsers + $totalTenantUsers;  
$totalRequests = $resultServiceRequests->fetch_assoc()['total_requests'];
$totalAnnouncements = $resultAnnouncements->fetch_assoc()['total_announcements'];
$totalFinanceRecords = $resultFinanceRecords->fetch_assoc()['total_finance'];

// Fetch recent service requests and finance records
$sqlRecentRequests = "SELECT status, COUNT(*) AS count FROM ownertenantconcerns GROUP BY status";
$sqlRecentFinance = "SELECT MONTH(Billing_Date) AS month, COUNT(*) AS total_records FROM soafinance_records GROUP BY MONTH(Billing_Date)";

$resultRecentRequests = $conn->query($sqlRecentRequests);
$resultRecentFinance = $conn->query($sqlRecentFinance);

// Fetch login stats for chart
$loginStats = ['morning' => 0, 'afternoon' => 0, 'evening' => 0];
$loginQuery = "SELECT HOUR(Last_Login) AS login_hour FROM audittrail";
$loginResult = $conn->query($loginQuery);

while ($row = $loginResult->fetch_assoc()) {
    $hour = intval($row['login_hour']);
    if ($hour >= 6 && $hour < 12) {
        $loginStats['morning']++;
    } elseif ($hour >= 12 && $hour < 18) {
        $loginStats['afternoon']++;
    } elseif ($hour >= 18 && $hour <= 23) {
        $loginStats['evening']++;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-dashboard-style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Custom styles for graphical charts */
        .overview .card-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
        }
        .card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #4361ee;
        }
        .charts {
            margin-top: 40px;
        }
        .charts h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .charts .chart-container {
            width: 50%;
            margin-top: 30px;
        }
        /* Ensure responsive layout */
        @media (max-width: 550px) {
            .overview .card-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .charts .chart-container {
                height: 300px;
            }
        }
        /* Card-like design for charts */
        .chart-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 30px;
        }

        .chart-container {
            width: 100%;
            height: 300px;
        }

    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bxs-group'></i>
                <span>Admin</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li class="active">
                        <a href="adm-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-usermanage.php">
                            <i class='bx bx-user'></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-manageannounce.php">
                            <i class='bx bx-notification'></i>
                            <span>Announcement</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-servicerequest.php">
                            <i class='bx bx-file'></i>
                            <span>Service Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-financialrec.php">
                            <i class='bx bx-wallet'></i>
                            <span>Finance Records</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-comminsights.php">
                            <i class='bx bx-chat'></i>
                            <span>Community Insights</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-auditlogs.php">
                            <i class='bx bx-file-blank'></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="man-logout.php" class="nav-item logout">
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
                    <h1>Admin Dashboard</h1>
                    <p class="header-subtitle">System Overview</p>
                </div>
            </header>

            <!-- Overview Section -->
            <section class="overview">
                <div class="card-container">
                    <div class="card">
                        <h3>Total Users</h3>
                        <p><?php echo $totalUsers; ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Service Requests</h3>
                        <p><?php echo $totalRequests; ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Announcements</h3>
                        <p><?php echo $totalAnnouncements; ?></p>
                    </div>
                    <div class="card">
                        <h3>Total Finance Records</h3>
                        <p><?php echo $totalFinanceRecords; ?></p>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts">
                <h2>System Data Visualization</h2>

                <!-- Service Requests Bar Chart -->
                <div class="chart-card">
                    <h3>Service Requests</h3>
                    <div class="chart-container">
                        <canvas id="serviceRequestsChart"></canvas>
                    </div>
                </div>

                <!-- Finance Records Line Chart -->
<div class="chart-card">
    <h3>Finance Records</h3>
    <div class="chart-container">
        <canvas id="financeRecordsChart"></canvas>
    </div>
</div>

                <!-- User Login Activity Radar Chart -->
<div class="chart-card">
    <h3>User Login Activity</h3>
    <div class="chart-container">
        <canvas id="loginActivityChart"></canvas>
    </div>
</div>

                <script>
                    window.onload = function() {
                        // Data for Service Requests Bar Chart
                        var serviceRequestsChart = new Chart(document.getElementById('serviceRequestsChart'), {
                            type: 'bar',
                            data: {
                                labels: ['Completed', 'Approval', 'Pending'],
                                datasets: [{
                                    label: 'Service Requests',
                                    data: [
                                        <?php
                                        $completedRequests = 0;
                                        $approvalRequests = 0;
                                        $pendingRequests = 0;
                                        while ($row = $resultRecentRequests->fetch_assoc()) {
                                            if ($row['status'] == 'Completed') $completedRequests = $row['count'];
                                            if ($row['status'] == 'Approval') $approvalRequests = $row['count'];
                                            if ($row['status'] == 'Pending') $pendingRequests = $row['count'];
                                        }
                                        echo "$completedRequests, $approvalRequests, $pendingRequests";
                                        ?>
                                    ],
                                    backgroundColor: ['#4CAF50', '#FF9800', '#F44336'],
                                    borderColor: '#fff',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Service Requests'
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                return tooltipItem.label + ': ' + tooltipItem.raw + ' records';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // Data for Finance Records Donut Chart
        var financeRecordsChart = new Chart(document.getElementById('financeRecordsChart'), {
            type: 'doughnut', // Change chart type to 'doughnut'
            data: {
                labels: [
                    <?php
                    $monthlyLabels = [];
                    while ($row = $resultRecentFinance->fetch_assoc()) {
                        $monthlyLabels[] = "Month " . $row['month']; // Assuming the month is returned as a number
                    }
                    echo '"' . implode('","', $monthlyLabels) . '"';
                    ?>
                ],
                datasets: [{
                    label: 'Finance Records',
                    data: [
                        <?php
                        $financeData = [];
                        $resultRecentFinance->data_seek(0); // Reset the result pointer
                        while ($row = $resultRecentFinance->fetch_assoc()) {
                            $financeData[] = $row['total_records'];
                        }
                        echo implode(',', $financeData);
                        ?>
                    ],
                    backgroundColor: ['#4CAF50', '#FF9800', '#F44336', '#36b9cc', '#ff5733'], // Colors for segments
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Finance Records by Month'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw + ' records';
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

                        // Data for User Login Activity Radar Chart
                       // Data for User Login Activity Wavy Line Chart
        var loginActivityChart = new Chart(document.getElementById('loginActivityChart'), {
            type: 'line', // Change chart type to 'line'
            data: {
                labels: ['Morning', 'Afternoon', 'Evening'], // Time intervals
                datasets: [{
                    label: 'User Login Activity',
                    data: [
                        <?php echo $loginStats['morning']; ?>, 
                        <?php echo $loginStats['afternoon']; ?>, 
                        <?php echo $loginStats['evening']; ?>
                    ],
                    backgroundColor: 'rgba(54, 185, 204, 0.2)', // Area fill color
                    borderColor: '#36b9cc', // Line color
                    borderWidth: 3,
                    fill: true, // Fill the area under the line
                    tension: 0.4 // Wavy line (smooth curve)
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'User Login Activity (Wavy Line)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };
                </script>
            </section>
        </main>
    </div>
</body>
</html>