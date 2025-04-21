<?php
include "dbconn.php";
include "getStatusCounts.php";

// Get the status counts
$counts = getStatusCounts($conn);
$pendingCount = isset($counts['Pending']) ? $counts['Pending'] : 0;
$approvalCount = isset($counts['Approval']) ? $counts['Approval'] : 0;
$completeCount = isset($counts['Complete']) ? $counts['Complete'] : 0;
$rejectedCount = isset($counts['Rejected']) ? $counts['Rejected'] : 0;

// Calculate percentages for progress bars
$total = $pendingCount + $approvalCount + $completeCount + $rejectedCount;
$pendingPercent = $total > 0 ? round(($pendingCount / $total) * 100) : 0;
$approvalPercent = $total > 0 ? round(($approvalCount / $total) * 100) : 0;
$completePercent = $total > 0 ? round(($completeCount / $total) * 100) : 0;
$rejectedPercent = $total > 0 ? round(($rejectedCount / $total) * 100) : 0;

// Get tenant and owner counts for pie chart
$ownersQuery = "SELECT COUNT(*) as count FROM ownerinformation";
$tenantsQuery = "SELECT COUNT(*) as count FROM tenantinformation";

$ownersResult = $conn->query($ownersQuery);
$tenantsResult = $conn->query($tenantsQuery);

$ownersCount = 0;
$tenantsCount = 0;

if ($ownersResult && $ownersResult->num_rows > 0) {
    $ownersCount = $ownersResult->fetch_assoc()['count'];
}

if ($tenantsResult && $tenantsResult->num_rows > 0) {
    $tenantsCount = $tenantsResult->fetch_assoc()['count'];
}

$residentsTotal = $ownersCount + $tenantsCount;

// Get concern counts by severity level
$concernsQuery = "
    SELECT 
        CASE 
            WHEN LOWER(concern_status) = 'low' THEN 'Low'
            WHEN LOWER(concern_status) = 'medium' THEN 'Medium'
            WHEN LOWER(concern_status) = 'high' THEN 'High'
            WHEN LOWER(concern_status) = 'urgent' THEN 'Urgent'
            ELSE 'Other'
        END AS severity_level,
        COUNT(*) as count
    FROM ownertenantconcerns
    GROUP BY severity_level
    ORDER BY FIELD(severity_level, 'Low', 'Medium', 'High', 'Urgent', 'Other')";

$concernsResult = $conn->query($concernsQuery);
$concernCounts = [
    'Low' => 0,
    'Medium' => 0,
    'High' => 0,
    'Urgent' => 0
];

if ($concernsResult && $concernsResult->num_rows > 0) {
    while ($row = $concernsResult->fetch_assoc()) {
        if (isset($concernCounts[$row['severity_level']])) {
            $concernCounts[$row['severity_level']] = $row['count'];
        }
    }
}

$totalConcerns = array_sum($concernCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Scorecard Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        dark: {
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    },
                    boxShadow: {
                        card: '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Any additional custom styles */
        [x-cloak] { display: none !important; }
    </style>
    <!-- Add Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="bg-dark-900 text-white w-64 flex-shrink-0 hidden md:block">
            <div class="flex items-center justify-center h-16 border-b border-gray-700">
                <img src="./img/logo swarm.png" alt="Logo" class="h-8 w-8 mr-2">
                <span class="text-xl font-bold">SWARM</span>
            </div>
            
            <div class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Admin Portal
            </div>
            
            <nav class="mt-2">
                <a href="adm-dashboard.php" class="flex items-center py-3 px-4 text-gray-300 hover:bg-dark-800 hover:text-white rounded-md transition-all duration-200">
                    <i class='bx bxs-dashboard text-xl mr-3'></i>
                    <span>Dashboard</span>
                </a>

                
                <div class="mt-8 border-t border-gray-700 pt-4">
                    <a href="logout.php" class="flex items-center py-3 px-4 text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-md transition-all duration-200">
                        <i class='bx bx-log-out text-xl mr-3'></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Mobile sidebar toggle -->
        <div class="md:hidden fixed bottom-4 right-4 z-50">
            <button type="button" id="sidebarToggle" class="bg-primary-600 hover:bg-primary-700 text-white rounded-full p-3 shadow-lg focus:outline-none">
                <i class='bx bx-menu text-xl'></i>
            </button>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top header -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Performance Scorecard</h1>
                        <p class="text-sm text-gray-600">Monitor key metrics and performance indicators</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">

                        
                        <div class="flex items-center">
                            <img src="https://randomuser.me/api/portraits/men/1.jpg" alt="User" class="h-8 w-8 rounded-full">
                            <span class="ml-2 text-sm font-medium text-gray-700 hidden md:inline-block">Admin User</span>
                        </div>
                    </div>
                </div>
                
                <!-- Time period selector -->
                <div class="flex items-center space-x-2 px-6 pb-4">
                    <button class="bg-primary-100 text-primary-700 px-3 py-1 rounded-md text-sm font-medium">Overall</button>
                </div>
            </header>

            <!-- Main content area -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <!-- Service Status Overview Title -->
                <h2 class="text-xl font-bold text-gray-800 mb-4">Service Status Overview</h2>
                
                <!-- KPI Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Approval Card -->
                    <div class="bg-white rounded-lg shadow-card overflow-hidden transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg border-t-4 border-blue-500">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Approvals</p>
                                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $approvalCount; ?></h3>
                                </div>
                                <div class="rounded-full bg-blue-100 p-3 text-blue-600">
                                    <i class='bx bx-check-circle text-xl'></i>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex items-center">
                                    <div class="h-2 w-full bg-gray-200 rounded-full">
                                        <div class="h-2 bg-blue-500 rounded-full" style="width: <?php echo $approvalPercent; ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-blue-600"><?php echo $approvalPercent; ?>%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Of total requests</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Card -->
                    <div class="bg-white rounded-lg shadow-card overflow-hidden transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg border-t-4 border-amber-500">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Pending</p>
                                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $pendingCount; ?></h3>
                                </div>
                                <div class="rounded-full bg-amber-100 p-3 text-amber-600">
                                    <i class='bx bx-time text-xl'></i>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex items-center">
                                    <div class="h-2 w-full bg-gray-200 rounded-full">
                                        <div class="h-2 bg-amber-500 rounded-full" style="width: <?php echo $pendingPercent; ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-amber-600"><?php echo $pendingPercent; ?>%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Awaiting processing</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Completed Card -->
                    <div class="bg-white rounded-lg shadow-card overflow-hidden transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg border-t-4 border-emerald-500">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Completed</p>
                                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $completeCount; ?></h3>
                                </div>
                                <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
                                    <i class='bx bx-check-double text-xl'></i>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex items-center">
                                    <div class="h-2 w-full bg-gray-200 rounded-full">
                                        <div class="h-2 bg-emerald-500 rounded-full" style="width: <?php echo $completePercent; ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-emerald-600"><?php echo $completePercent; ?>%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Successfully processed</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rejected Card (replacing Active Users) -->
                    <div class="bg-white rounded-lg shadow-card overflow-hidden transition-all duration-300 transform hover:-translate-y-1 hover:shadow-lg border-t-4 border-red-500">
                        <div class="p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm font-medium">Rejected</p>
                                    <h3 class="text-3xl font-bold text-gray-800 mt-1"><?php echo $rejectedCount; ?></h3>
                                </div>
                                <div class="rounded-full bg-red-100 p-3 text-red-600">
                                    <i class='bx bx-x-circle text-xl'></i>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <div class="flex items-center">
                                    <div class="h-2 w-full bg-gray-200 rounded-full">
                                        <div class="h-2 bg-red-500 rounded-full" style="width: <?php echo $rejectedPercent; ?>%"></div>
                                    </div>
                                    <span class="ml-2 text-sm font-medium text-red-600"><?php echo $rejectedPercent; ?>%</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Declined requests</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                    <!-- Residents Distribution Chart -->
                    <div class="bg-white rounded-lg shadow-card p-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Residents Distribution</h3>
                        <div class="flex items-center justify-center">
                            <canvas id="residentsChart" width="300" height="300"></canvas>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Owners: <?php echo $ownersCount; ?> (<?php echo $residentsTotal > 0 ? round(($ownersCount / $residentsTotal) * 100) : 0; ?>%)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Tenants: <?php echo $tenantsCount; ?> (<?php echo $residentsTotal > 0 ? round(($tenantsCount / $residentsTotal) * 100) : 0; ?>%)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional chart or stats can go here -->
                    <div class="bg-white rounded-lg shadow-card p-6">
                        <h3 class="text-lg font-medium text-gray-800 mb-4">Status Overview</h3>
                        <div class="flex flex-col space-y-4">
                            <div class="flex flex-col">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-600">Pending</span>
                                    <span class="text-sm font-medium text-gray-600"><?php echo $pendingPercent; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-amber-500 h-2 rounded-full" style="width: <?php echo $pendingPercent; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-600">Approved</span>
                                    <span class="text-sm font-medium text-gray-600"><?php echo $approvalPercent; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $approvalPercent; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-600">Completed</span>
                                    <span class="text-sm font-medium text-gray-600"><?php echo $completePercent; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: <?php echo $completePercent; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-600">Rejected</span>
                                    <span class="text-sm font-medium text-gray-600"><?php echo $rejectedPercent; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $rejectedPercent; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Concerns Section -->
                <h2 class="text-xl font-bold text-gray-800 mb-4 mt-8">Concern Severity Analysis</h2>
                <div class="grid grid-cols-1 gap-6">
                    <!-- Concerns Chart -->
                    <div class="bg-white rounded-lg shadow-card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Concerns by Severity Level</h3>
                            <span class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded-full">
                                Total: <?php echo $totalConcerns; ?>
                            </span>
                        </div>
                        
                        <div class="h-64">
                            <canvas id="concernsChart"></canvas>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Low: <?php echo $concernCounts['Low']; ?></span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Medium: <?php echo $concernCounts['Medium']; ?></span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
                                <span class="text-sm text-gray-600">High: <?php echo $concernCounts['High']; ?></span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Urgent: <?php echo $concernCounts['Urgent']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.toggle('hidden');
        });
        
        // Animation for stat bars
        document.addEventListener('DOMContentLoaded', function() {
            const bars = document.querySelectorAll('[class*="h-2 bg-"]');
            
            setTimeout(() => {
                bars.forEach(bar => {
                    const targetWidth = bar.style.width;
                    bar.style.transition = 'width 1s ease-out';
                    bar.style.width = '0%';
                    
                    setTimeout(() => {
                        bar.style.width = targetWidth;
                    }, 100);
                });
            }, 200);
        });
        
        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Residents distribution chart
            const residentsChart = document.getElementById('residentsChart').getContext('2d');
            new Chart(residentsChart, {
                type: 'pie',
                data: {
                    labels: ['Owners', 'Tenants'],
                    datasets: [{
                        data: [<?php echo $ownersCount; ?>, <?php echo $tenantsCount; ?>],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)', // Blue for owners
                            'rgba(16, 185, 129, 0.8)'  // Green for tenants
                        ],
                        borderColor: [
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = <?php echo $residentsTotal; ?>;
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Concerns chart
            const concernsCtx = document.getElementById('concernsChart').getContext('2d');
            new Chart(concernsCtx, {
                type: 'bar',
                data: {
                    labels: ['Low', 'Medium', 'High', 'Urgent'],
                    datasets: [{
                        label: 'Number of Concerns',
                        data: [
                            <?php echo $concernCounts['Low']; ?>,
                            <?php echo $concernCounts['Medium']; ?>,
                            <?php echo $concernCounts['High']; ?>,
                            <?php echo $concernCounts['Urgent']; ?>
                        ],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.7)',  // green for Low
                            'rgba(234, 179, 8, 0.7)',  // yellow for Medium
                            'rgba(249, 115, 22, 0.7)', // orange for High
                            'rgba(239, 68, 68, 0.7)'   // red for Urgent
                        ],
                        borderColor: [
                            'rgba(34, 197, 94, 1)',
                            'rgba(234, 179, 8, 1)',
                            'rgba(249, 115, 22, 1)',
                            'rgba(239, 68, 68, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 5,
                        barThickness: 40
                    }]
                },
                options: {
                    indexAxis: 'y',  // Makes the bars horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw || 0;
                                    const total = <?php echo $totalConcerns; ?>;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${value} concerns (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>