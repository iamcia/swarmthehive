<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Finance') {
    header("Location: management-index.php");
    exit();
}

// Fetch data for dashboard charts (e.g., payment statuses, billing numbers, etc.)
$sql = "SELECT COUNT(*) AS total_billings, 
               SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) AS completed,
               SUM(CASE WHEN Status = 'Approval' THEN 1 ELSE 0 END) AS approval,
               SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS pending
        FROM soafinance";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

// Get total number of SOAs uploaded
$sql2 = "SELECT COUNT(*) AS total_uploads FROM soafinance_pdfs";
$result2 = $conn->query($sql2);
$data2 = $result2->fetch_assoc();

// Fetch data for monthly trends (e.g., billing data per month)
$sql3 = "SELECT MONTH(Billing_Date) AS month, COUNT(*) AS monthly_billings
         FROM soafinance
         GROUP BY MONTH(Billing_Date)";
$result3 = $conn->query($sql3);
$monthly_data = [];
while ($row = $result3->fetch_assoc()) {
    $monthly_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Finance Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/finance_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/finance-upload-style.css?v=<?php echo time(); ?>">

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li class="active">
                        <a href="#">
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
                    <li>
                        <a href="finance-history.php">
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
                    <h1>Finance Dashboard</h1>
                    <p class="header-subtitle">System Overview</p>
                </div>
            </header>

            <!-- Chart Section -->
            <div class="dashboard-cards">
                <!-- Bar Chart for Billing Status -->
                <div class="card">
                    <canvas id="billingStatusChart"></canvas>
                </div>

                <!-- Pie Chart for Payment Status -->
                <div class="card">
                    <canvas id="paymentStatusChart"></canvas>
                </div>

                <!-- Line Chart for Monthly Billing Trends -->
                <div class="card">
                    <canvas id="monthlyTrendsChart"></canvas>
                </div>

                <!-- Radar Chart for Comparison of Completed, Approval, Pending -->
                <div class="card">
                    <canvas id="statusComparisonChart"></canvas>
                </div>
            </div>

        </main>
    </div>

    <!-- Styles for the Dashboard Cards -->
    <style>
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        canvas {
            width: 100%;
            height: 300px;
        }
    </style>

    <!-- JavaScript for Chart.js -->
    <script>
       // Billing Status Chart (Bar Chart)
var ctx1 = document.getElementById('billingStatusChart').getContext('2d');
var billingStatusChart = new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: ['Completed', 'Approval', 'Pending'],
        datasets: [{
            label: 'Billing Status',
            data: [<?php echo $data['completed']; ?>, <?php echo $data['approval']; ?>, <?php echo $data['pending']; ?>],
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
                text: 'Billing Status',  // Title for the chart
                font: {
                    size: 18
                },
                padding: {
                    top: 10,
                    bottom: 10
                }
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

// Payment Status Chart (Pie Chart)
var ctx2 = document.getElementById('paymentStatusChart').getContext('2d');
var paymentStatusChart = new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: ['Paid', 'Unpaid'],
        datasets: [{
            label: 'Payment Status',
            data: [<?php echo $data['completed']; ?>, <?php echo $data['total_billings'] - $data['completed']; ?>],
            backgroundColor: ['#4CAF50', '#FF5722'],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Payment Status',  // Title for the chart
                font: {
                    size: 18
                },
                padding: {
                    top: 10,
                    bottom: 10
                }
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

// Monthly Trends Chart (Line Chart)
var ctx3 = document.getElementById('monthlyTrendsChart').getContext('2d');
var monthlyTrendsChart = new Chart(ctx3, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>,
        datasets: [{
            label: 'Monthly Billings',
            data: <?php echo json_encode(array_column($monthly_data, 'monthly_billings')); ?>,
            borderColor: '#FF5722',
            backgroundColor: 'rgba(255, 87, 34, 0.2)',
            fill: true,
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Monthly Comparison',  // Title for the chart
                font: {
                    size: 18
                },
                padding: {
                    top: 10,
                    bottom: 10
                }
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return 'Billings: ' + tooltipItem.raw;
                    }
                }
            }
        }
    }
});

// Status Comparison Chart (Radar Chart)
var ctx4 = document.getElementById('statusComparisonChart').getContext('2d');
var statusComparisonChart = new Chart(ctx4, {
    type: 'radar',
    data: {
        labels: ['Completed', 'Approval', 'Pending'],
        datasets: [{
            label: 'Status Comparison',
            data: [<?php echo $data['completed']; ?>, <?php echo $data['approval']; ?>, <?php echo $data['pending']; ?>],
            backgroundColor: 'rgba(76, 175, 80, 0.2)',
            borderColor: '#4CAF50',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Statuses',  // Title for the chart
                font: {
                    size: 18
                },
                padding: {
                    top: 10,
                    bottom: 10
                }
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        return tooltipItem.label + ': ' + tooltipItem.raw + ' records';
                    }
                }
            }
        },
        scale: {
            ticks: {
                beginAtZero: true,
                max: <?php echo max($data['completed'], $data['approval'], $data['pending']) + 5; ?>
            }
        }
    }
});

    </script>

</body>
</html>

<?php
$conn->close();
?>
