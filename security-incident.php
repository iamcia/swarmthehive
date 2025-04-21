<?php
include 'dbconn.php';
session_start(); 

// Check if the user is logged in and has the correct position (Security)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Security') {
    header("Location: management-index.php");
    exit();
}

// Fetch incident reports from feedback table with owner and tenant information
$sql = "SELECT f.*, 
        o.Last_Name as owner_last_name, o.First_Name as owner_first_name, o.Tower as owner_tower, o.Unit_Number as owner_unit,
        t.Last_Name as tenant_last_name, t.First_Name as tenant_first_name, t.Tower as tenant_tower, t.Unit_Number as tenant_unit
        FROM feedback f
        LEFT JOIN ownerinformation o ON f.user_id = o.Owner_ID
        LEFT JOIN tenantinformation t ON f.user_id = t.Tenant_ID
        ORDER BY f.Created_At DESC";
$result = $conn->query($sql);

// Get security personnel for dropdown
$securityQuery = "
    SELECT Management_ID, Management_Code, firstname, middlename, lastname 
    FROM managementaccount 
    WHERE position = 'Security'
    ORDER BY lastname, firstname
";

$securityResult = $conn->query($securityQuery);
$securityPersonnel = [];

if ($securityResult && $securityResult->num_rows > 0) {
    while ($row = $securityResult->fetch_assoc()) {
        $securityPersonnel[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Security Incident Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- AOS Animation Library with defer for performance -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
    <!-- Keep existing styles for sidebar compatibility -->
    <link rel="stylesheet" href="./css/security_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/sec-gatepass-style.css?v=<?php echo time(); ?>">
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
                    },
                    fontFamily: {
                        sans: ['"Google Sans"', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        /* Base */
        body {
            font-family: 'Google Sans', sans-serif;
        }
        
        /* Card specific styles */
        .card-container {
            transition: transform 0.3s ease;
        }
        .card-container:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
        }
        
        /* Status pills styling */
        .status-pill {
            @apply px-3 py-1 rounded-full text-xs font-medium;
        }
        .status-open {
            @apply bg-purple-100 text-purple-800;
        }
        .status-pending {
            @apply bg-yellow-100 text-yellow-800;
        }
        .status-completed {
            @apply bg-green-100 text-green-800;
        }
        
        /* Priority styling */
        .priority-open {
            @apply bg-blue-100 text-blue-800;
        }
        .priority-pending {
            @apply bg-yellow-100 text-yellow-800;
        }
        .priority-approval {
            @apply bg-indigo-100 text-indigo-800;
        }
        .priority-completed {
            @apply bg-green-100 text-green-800;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        
        /* Loading Indicator */
        .loading-overlay {
            @apply fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center;
        }
        
        .loading-spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive layout */
        @media (max-width: 768px) {
            .sidebar {
                width: 240px;
                position: fixed;
                left: -240px;
                transition: left 0.3s ease;
                z-index: 50;
                height: 100vh;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        /* Accessibility improvements */
        .focus-visible:focus-visible {
            @apply outline-none ring-2 ring-blue-500;
        }
        
        /* Print styles for PDF export */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            body {
                font-size: 12pt;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-spinner"></div>
    </div>

    <div class="container">
        <!-- Sidebar - Kept the same as original incident page -->
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
                    <li>
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
                    <li class="active">
                        <a href="security-incident.php">
                            <i class='bx bx-notepad'></i>
                            <span>Incident Reports</span>
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

        <!-- Main Content -->
        <main class="w-full">
            <div class="sm:p-6 p-3 sm:pt-4 pt-2">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl p-5 shadow-lg mb-6" data-aos="fade-down">
                    <div>
                        <h1 class="text-3xl font-bold flex items-center gap-3">
                            <i class='bx bx-notepad text-yellow-300 text-4xl'></i>
                            Incident Reports
                        </h1>
                        <div class="mt-2 flex items-center gap-2 text-red-100">
                            <i class='bx bx-shield-quarter'></i>
                            <p class="tracking-wide">Review and process resident incident reports</p>
                        </div>
                    </div>
                    
                    <!-- Stats Summary -->
                    <div class="mt-4 sm:mt-0 flex gap-4">
                        <?php
                        // Get counts for each status
                        $openCount = 0;
                        $pendingCount = 0;
                        $completedCount = 0;
                        
                        if ($result && $result->num_rows > 0) {
                            // Save result in array to avoid losing it
                            $incidents = [];
                            while ($row = $result->fetch_assoc()) {
                                $incidents[] = $row;
                                $status = strtolower($row['status'] ?? 'open');
                                
                                if ($status === 'open') $openCount++;
                                else if ($status === 'pending') $pendingCount++;
                                else if ($status === 'completed') $completedCount++;
                            }
                            // Reset result pointer
                            $result = new ArrayObject($incidents);
                        }
                        ?>
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $openCount; ?></span>
                            <span class="text-xs">Open</span>
                        </div>
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $pendingCount; ?></span>
                            <span class="text-xs">Pending</span>
                        </div>
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $completedCount; ?></span>
                            <span class="text-xs">Completed</span>
                        </div>
                    </div>
                    
                    <!-- Mobile menu toggle -->
                    <button id="mobile_menu_toggle" class="sm:hidden bg-white text-red-600 p-2 rounded-lg absolute top-4 right-4" aria-label="Toggle menu">
                        <i class='bx bx-menu text-xl'></i>
                    </button>
                </div>
                
                <!-- Search and Filter Section -->
                <div class="bg-white rounded-xl shadow-md p-5 mb-6" data-aos="fade-up">
                    <div class="flex flex-col gap-4">
                        <!-- Title and Description -->
                        <div class="mb-2">
                            <h2 class="text-lg font-semibold text-gray-800">Search & Filter</h2>
                            <p class="text-sm text-gray-500">Find incident reports by keyword, status, priority or category</p>
                        </div>
                        
                        <!-- Search Filters -->
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4 w-full">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class='bx bx-search text-gray-400'></i>
                                </div>
                                <input type="text" id="searchInput" 
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 focus-visible shadow-sm" 
                                    placeholder="Search by keyword..." aria-label="Search incidents">
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <!-- Status Filter -->
                                <div class="relative w-full sm:w-auto">
                                    <select id="statusFilter" 
                                        class="block w-full px-4 py-3 text-base border border-gray-300 focus:ring-red-500 focus:border-red-500 rounded-lg appearance-none bg-white cursor-pointer focus-visible shadow-sm" 
                                        aria-label="Filter by status">
                                        <option value="">All Workflow Statuses</option>
                                        <option value="Open">Open</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                
                                <!-- Priority Filter -->
                                <div class="relative w-full sm:w-auto">
                                    <select id="priorityFilter" 
                                        class="block w-full px-4 py-3 text-base border border-gray-300 focus:ring-red-500 focus:border-red-500 rounded-lg appearance-none bg-white cursor-pointer focus-visible shadow-sm" 
                                        aria-label="Filter by priority">
                                        <option value="">All Priorities</option>
                                        <option value="Open">Open</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approval">Approval</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                                
                                <!-- Category Filter -->
                                <div class="relative w-full sm:w-auto">
                                    <select id="categoryFilter" 
                                        class="block w-full px-4 py-3 text-base border border-gray-300 focus:ring-red-500 focus:border-red-500 rounded-lg appearance-none bg-white cursor-pointer focus-visible shadow-sm" 
                                        aria-label="Filter by category">
                                        <option value="">All Categories</option>
                                        <option value="Security">Security</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Noise">Noise Complaint</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Section -->
                <div id="activeFilters" class="flex flex-wrap gap-2 mb-4">
                    <!-- Active filters will be added here via JS -->
                </div>

                <!-- No Results Message (hidden by default) -->
                <div id="noResultsMessage" class="hidden col-span-full flex flex-col items-center justify-center p-10 bg-white rounded-xl shadow-md mb-6" data-aos="fade-up">
                    <div class="rounded-full bg-red-100 p-6 mb-4">
                        <i class='bx bx-search-alt text-5xl text-red-500'></i>
                    </div>
                    <p class="text-xl text-gray-700 font-medium">No matching incident reports found</p>
                    <p class="text-gray-500 text-center mt-2 mb-4">Try adjusting your search criteria</p>
                    <button id="clearSearchButton" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors focus-visible">
                        Clear Search
                    </button>
                </div>

                <!-- Incident Reports Grid -->
                <div id="incidentGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php
                    if ($result && count($result) > 0) {
                        $counter = 0;
                        foreach ($result as $index => $row) {
                            $delay = $counter * 50;
                            
                            // Get concern status (priority) styling
                            $priorityClass = "";
                            $priorityBg = "";
                            $priorityIcon = "bx-error-circle";
                            
                            switch(strtolower($row["concern_status"] ?? "")) {
                                case "open":
                                    $priorityClass = "text-blue-800 bg-blue-100 border-blue-300";
                                    $priorityBg = "bg-blue-50";
                                    $priorityIcon = "bx-envelope-open";
                                    break;
                                case "pending":
                                    $priorityClass = "text-yellow-800 bg-yellow-100 border-yellow-300";
                                    $priorityBg = "bg-yellow-50";
                                    $priorityIcon = "bx-time";
                                    break;
                                case "approval":
                                    $priorityClass = "text-indigo-800 bg-indigo-100 border-indigo-300";
                                    $priorityBg = "bg-indigo-50";
                                    $priorityIcon = "bx-check-circle";
                                    break;
                                case "completed":
                                    $priorityClass = "text-green-800 bg-green-100 border-green-300";
                                    $priorityBg = "bg-green-50";
                                    $priorityIcon = "bx-check-double";
                                    break;
                                default:
                                    $priorityClass = "text-gray-800 bg-gray-100 border-gray-300";
                                    $priorityBg = "bg-gray-50";
                            }
                            
                            // Get workflow status styling
                            $statusClass = "";
                            $statusBg = "";
                            $statusIcon = "bx-loader-circle";
                            
                            switch(strtolower($row["status"] ?? "open")) {
                                case "open":
                                    $statusClass = "text-purple-800 bg-purple-100 border-purple-300";
                                    $statusBg = "bg-purple-50";
                                    $statusIcon = "bx-book-open";
                                    break;
                                case "pending":
                                    $statusClass = "text-orange-800 bg-orange-100 border-orange-300";
                                    $statusBg = "bg-orange-50";
                                    $statusIcon = "bx-time-five";
                                    break;
                                case "completed":
                                    $statusClass = "text-emerald-800 bg-emerald-100 border-emerald-300";
                                    $statusBg = "bg-emerald-50";
                                    $statusIcon = "bx-task";
                                    break;
                                default:
                                    $statusClass = "text-gray-800 bg-gray-100 border-gray-300";
                                    $statusBg = "bg-gray-50";
                            }
                            
                            // Get category icon
                            $categoryIcon = "bx-question-mark";
                            $categoryColor = "text-gray-600";
                            
                            switch(strtolower($row["concern_category"] ?? "")) {
                                case "security":
                                    $categoryIcon = "bx-shield-quarter";
                                    $categoryColor = "text-red-600";
                                    break;
                                case "maintenance":
                                    $categoryIcon = "bx-wrench";
                                    $categoryColor = "text-blue-600";
                                    break;
                                case "noise":
                                    $categoryIcon = "bx-volume-full";
                                    $categoryColor = "text-purple-600";
                                    break;
                                case "other":
                                    $categoryIcon = "bx-detail";
                                    $categoryColor = "text-gray-600";
                                    break;
                            }
                            
                            // Determine reporter info (owner or tenant)
                            $reporterInfo = "";
                            $reporterType = "";
                            $reporterIcon = "";
                            
                            if (!empty($row["user_id"]) && empty($row["tenant_id"])) {
                                // This is an owner-reported incident
                                if (!empty($row["owner_first_name"]) && !empty($row["owner_last_name"])) {
                                    $reporterInfo = $row["owner_first_name"] . " " . $row["owner_last_name"];
                                    if (!empty($row["owner_tower"]) && !empty($row["owner_unit"])) {
                                        $reporterInfo .= " (" . $row["owner_tower"] . "-" . $row["owner_unit"] . ")";
                                    }
                                } else {
                                    $reporterInfo = "Owner #" . $row["user_id"];
                                }
                                $reporterType = "Owner";
                                $reporterIcon = "bx-user";
                            } 
                            else if (!empty($row["tenant_id"]) && empty($row["user_id"])) {
                                // This is a tenant-reported incident
                                if (!empty($row["tenant_first_name"]) && !empty($row["tenant_last_name"])) {
                                    $reporterInfo = $row["tenant_first_name"] . " " . $row["tenant_last_name"];
                                    if (!empty($row["tenant_tower"]) && !empty($row["tenant_unit"])) {
                                        $reporterInfo .= " (" . $row["tenant_tower"] . "-" . $row["tenant_unit"] . ")";
                                    }
                                } else {
                                    $reporterInfo = "Tenant #" . $row["tenant_id"];
                                }
                                $reporterType = "Tenant";
                                $reporterIcon = "bx-building-house";
                            }
                            else if (!empty($row["user_id"]) && !empty($row["tenant_id"])) {
                                // Both IDs exist - show owner as primary
                                if (!empty($row["owner_first_name"]) && !empty($row["owner_last_name"])) {
                                    $reporterInfo = $row["owner_first_name"] . " " . $row["owner_last_name"];
                                    if (!empty($row["owner_tower"]) && !empty($row["owner_unit"])) {
                                        $reporterInfo .= " (" . $row["owner_tower"] . "-" . $row["owner_unit"] . ")";
                                    }
                                } else {
                                    $reporterInfo = "Owner #" . $row["user_id"];
                                }
                                $reporterType = "Owner";
                                $reporterIcon = "bx-user";
                            }
                            else {
                                // No user or tenant ID
                                $reporterInfo = "Unknown Reporter";
                                $reporterType = "Unknown";
                                $reporterIcon = "bx-question-mark";
                            }
                    ?>
                    <div data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>" 
                         class="card-container relative rounded-xl shadow-md overflow-hidden border-l-4 <?php echo $priorityClass; ?> <?php echo $priorityBg; ?>" 
                         data-category="<?php echo htmlspecialchars($row["concern_category"] ?? ''); ?>" 
                         data-priority="<?php echo htmlspecialchars($row["concern_status"] ?? ''); ?>"
                         data-status="<?php echo htmlspecialchars($row["status"] ?? 'Open'); ?>"
                         data-id="<?php echo $row["ID"] ?? ''; ?>"
                         data-owner-id="<?php echo $row["user_id"] ?? ''; ?>"
                         data-tenant-id="<?php echo $row["tenant_id"] ?? ''; ?>"
                         data-reporter-type="<?php echo htmlspecialchars($reporterType); ?>"
                         data-modal-id="incident-modal-<?php echo $index; ?>"
                         data-created-at="<?php echo $row["Created_At"] ?? ''; ?>">
                        
                        <!-- Priority badge -->
                        <span class="status-badge px-3 py-1 text-xs font-semibold rounded-full <?php echo $priorityClass; ?>">
                            <i class='bx <?php echo $priorityIcon; ?> mr-1'></i>
                            <?php echo htmlspecialchars($row["concern_status"] ?? ''); ?>
                        </span>
                        
                        <div class="p-5">
                            <div class="flex items-start mb-4">
                                <div class="p-2 rounded-full <?php echo $categoryColor; ?> bg-opacity-10 mr-3">
                                    <i class='bx <?php echo $categoryIcon; ?> text-2xl'></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($row["concern_category"] ?? "Uncategorized"); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo !empty($row["Created_At"]) ? date('M d, Y - h:i A', strtotime($row["Created_At"])) : 'Unknown date'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Priority status indicator -->
                            <div class="mb-2 p-2 rounded-md <?php echo $priorityBg; ?> border border-gray-200">
                                <div class="flex items-center">
                                    <div class="mr-2 h-3 w-3 rounded-full <?php echo str_replace('bg-', 'bg-', str_replace('text-', 'bg-', explode(' ', $priorityClass)[0])); ?>"></div>
                                    <p class="text-sm font-medium <?php echo explode(' ', $priorityClass)[0]; ?>">
                                        Priority: <span class="font-bold"><?php echo htmlspecialchars($row["concern_status"] ?? ''); ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Workflow status indicator -->
                            <div class="mb-4 p-2 rounded-md <?php echo $statusBg; ?> border border-gray-200">
                                <div class="flex items-center">
                                    <div class="mr-2 h-3 w-3 rounded-full <?php echo str_replace('bg-', 'bg-', str_replace('text-', 'bg-', explode(' ', $statusClass)[0])); ?>"></div>
                                    <p class="text-sm font-medium <?php echo explode(' ', $statusClass)[0]; ?>">
                                        Status: <span class="font-bold"><?php echo htmlspecialchars($row["status"] ?? 'Open'); ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-gray-700 line-clamp-3"><?php echo htmlspecialchars($row["concern_details"] ?? 'No details provided'); ?></p>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-3 mt-3">
                                <!-- Show reporter info -->
                                <div class="flex items-center text-sm text-gray-600 mb-2">
                                    <i class='bx <?php echo $reporterIcon; ?> mr-2'></i>
                                    <span>
                                        <span class="font-medium"><?php echo htmlspecialchars($reporterType); ?>:</span> 
                                        <?php echo htmlspecialchars($reporterInfo); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-4">
                                <button class="view-details text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-150 flex items-center open-modal" data-modal-id="incident-modal-<?php echo $index; ?>">
                                    <i class='bx bx-show-alt mr-2'></i> View Details
                                </button>
                                
                                <?php if(!empty($row["concern_media"])) { ?>
                                <a href="<?php echo $row["concern_media"]; ?>" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center">
                                    <i class='bx bx-file mr-1'></i> 
                                    <span>Media</span>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal Section -->
                    <div id="incident-modal-<?php echo $index; ?>" class="modal-container fixed inset-0 z-[1000] hidden" role="dialog" aria-labelledby="modalTitle-<?php echo $index; ?>" aria-modal="true">
                        <div class="modal-overlay absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" data-close-id="incident-modal-<?php echo $index; ?>"></div>
                        <div class="modal-content relative bg-white rounded-xl shadow-2xl w-11/12 max-w-4xl mx-auto mt-[2%] h-[96vh] max-h-[700px] flex flex-col">
                            <div class="sticky top-0 bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                                <h3 id="modalTitle-<?php echo $index; ?>" class="text-xl font-bold flex items-center">
                                    <i class='bx bx-notepad mr-2 text-2xl'></i>
                                    Incident Report <span class="ml-2 text-sm opacity-80">#<?php echo $row["ID"] ?? ''; ?></span>
                                </h3>
                                <button class="text-white hover:text-gray-200 close focus-visible" data-close-id="incident-modal-<?php echo $index; ?>" aria-label="Close">
                                    <i class='bx bx-x text-2xl'></i>
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6">
                                <!-- Reporter Information Card -->
                                <div class="bg-red-50 p-5 rounded-xl mb-6 border border-red-100">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <div>
                                            <h4 class="text-sm font-medium text-red-500 uppercase mb-1">Reporter</h4>
                                            <p class="text-lg font-semibold text-gray-800 flex items-center">
                                                <i class="bx <?php echo $reporterIcon; ?> text-red-500 mr-2"></i>
                                                <?php echo htmlspecialchars($reporterInfo); ?>
                                                <span class="inline-block ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full"><?php echo htmlspecialchars($reporterType); ?></span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-red-500 uppercase mb-1">Category</h4>
                                            <p class="text-lg font-semibold text-gray-800">
                                                <span class="inline-flex items-center">
                                                    <i class="bx <?php echo $categoryIcon; ?> mr-2 <?php echo $categoryColor; ?>"></i>
                                                    <?php echo htmlspecialchars($row["concern_category"] ?? "Uncategorized"); ?>
                                                </span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-red-500 uppercase mb-1">Date & Time</h4>
                                            <p class="text-md font-semibold text-gray-800">
                                                <span class="inline-flex items-center">
                                                    <i class="bx bx-calendar mr-2 text-red-500"></i>
                                                    <?php echo !empty($row["Created_At"]) ? date('F d, Y, h:i A', strtotime($row["Created_At"])) : 'Unknown date'; ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status Cards Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                    <!-- Priority Card -->
                                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                            <h4 class="font-medium text-gray-700 flex items-center">
                                                <i class="bx bx-error-circle text-red-500 mr-2"></i>
                                                Priority Status
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="flex items-center mb-2">
                                                <div class="mr-2 h-4 w-4 rounded-full <?php echo str_replace('bg-', 'bg-', str_replace('text-', 'bg-', explode(' ', $priorityClass)[0])); ?>"></div>
                                                <h3 class="text-lg font-semibold <?php echo explode(' ', $priorityClass)[0]; ?>">
                                                    <?php echo htmlspecialchars($row["concern_status"] ?? ''); ?>
                                                </h3>
                                            </div>
                                            
                                            <p class="text-gray-600 text-sm">
                                                <?php
                                                $priorityDesc = "";
                                                switch(strtolower($row["concern_status"] ?? "")) {
                                                    case "open":
                                                        $priorityDesc = "This incident has been reported and is waiting for initial review.";
                                                        break;
                                                    case "pending":
                                                        $priorityDesc = "This incident is being investigated by security personnel.";
                                                        break;
                                                    case "approval":
                                                        $priorityDesc = "This incident has been investigated and is awaiting final approval.";
                                                        break;
                                                    case "completed":
                                                        $priorityDesc = "This incident has been resolved and is now closed.";
                                                        break;
                                                    default:
                                                        $priorityDesc = "Status information is not available.";
                                                }
                                                echo $priorityDesc;
                                                ?>
                                            </p>
                                            
                                            <?php if (strtolower($row["concern_status"] ?? "") != "completed") { ?>
                                            <div class="mt-4">
                                                <label for="priority-select-<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 mb-2">Update Priority:</label>
                                                <select id="priority-select-<?php echo $index; ?>" class="priority-select block w-full px-3 py-2 text-base border border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 rounded-lg" data-id="<?php echo $row["ID"] ?? ''; ?>">
                                                    <option value="">-- Select Priority --</option>
                                                    <option value="Open" <?php echo (strtolower($row["concern_status"] ?? "") == "open") ? "selected" : ""; ?>>Open</option>
                                                    <option value="Pending" <?php echo (strtolower($row["concern_status"] ?? "") == "pending") ? "selected" : ""; ?>>Pending</option>
                                                    <option value="Approval" <?php echo (strtolower($row["concern_status"] ?? "") == "approval") ? "selected" : ""; ?>>Approval</option>
                                                    <option value="Completed" <?php echo (strtolower($row["concern_status"] ?? "") == "completed") ? "selected" : ""; ?>>Completed</option>
                                                </select>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Workflow Status Card -->
                                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                            <h4 class="font-medium text-gray-700 flex items-center">
                                                <i class="bx bx-loader-circle text-red-500 mr-2"></i>
                                                Workflow Status
                                            </h4>
                                        </div>
                                        <div class="p-5">
                                            <div class="flex items-center mb-2">
                                                <div class="mr-2 h-4 w-4 rounded-full <?php echo str_replace('bg-', 'bg-', str_replace('text-', 'bg-', explode(' ', $statusClass)[0])); ?>"></div>
                                                <h3 class="text-lg font-semibold <?php echo explode(' ', $statusClass)[0]; ?>">
                                                    <?php echo htmlspecialchars($row["status"] ?? 'Open'); ?>
                                                </h3>
                                            </div>
                                            
                                            <p class="text-gray-600 text-sm">
                                                <?php
                                                $statusDesc = "";
                                                switch(strtolower($row["status"] ?? "open")) {
                                                    case "open":
                                                        $statusDesc = "This incident case is open and needs to be addressed by security.";
                                                        break;
                                                    case "pending":
                                                        $statusDesc = "This incident is pending review or additional information.";
                                                        break;
                                                    case "completed":
                                                        $statusDesc = "This incident case has been completed and closed.";
                                                        break;
                                                    default:
                                                        $statusDesc = "Workflow status information is not available.";
                                                }
                                                echo $statusDesc;
                                                ?>
                                            </p>
                                            
                                            <?php if (strtolower($row["status"] ?? "open") != "completed") { ?>
                                            <div class="mt-4">
                                                <label for="status-select-<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 mb-2">Update Status:</label>
                                                <select id="status-select-<?php echo $index; ?>" class="status-select block w-full px-3 py-2 text-base border border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 rounded-lg" data-id="<?php echo $row["ID"] ?? ''; ?>">
                                                    <option value="">-- Select Status --</option>
                                                    <option value="Open" <?php echo (strtolower($row["status"] ?? "open") == "open") ? "selected" : ""; ?>>Open</option>
                                                    <option value="Pending" <?php echo (strtolower($row["status"] ?? "open") == "pending") ? "selected" : ""; ?>>Pending</option>
                                                    <option value="Completed" <?php echo (strtolower($row["status"] ?? "open") == "completed") ? "selected" : ""; ?>>Completed</option>
                                                </select>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Incident Details Card -->
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                        <h4 class="font-medium text-gray-700 flex items-center">
                                            <i class="bx bx-detail text-red-500 mr-2"></i>
                                            Incident Details
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($row["concern_details"] ?? 'No details provided'); ?></p>
                                        
                                        <?php if(!empty($row["concern_media"])) { ?>
                                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                            <h5 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                                                <i class="bx bx-file text-red-500 mr-2"></i>
                                                Attached Media
                                            </h5>
                                            <div class="flex gap-2">
                                                <a href="<?php echo $row["concern_media"]; ?>" target="_blank" class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                                    <i class="bx bx-file-find mr-2"></i>
                                                    View Attachment
                                                </a>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <!-- Security Response Card -->
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                        <h4 class="font-medium text-gray-700 flex items-center">
                                            <i class="bx bx-shield-quarter text-red-500 mr-2"></i>
                                            Security Response
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <div class="flex flex-col gap-4">
                                            <div class="w-full">
                                                <label for="assignedTo-<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 mb-2">Assigned To:</label>
                                                <div class="relative">
                                                    <select id="assignedTo-<?php echo $index; ?>" class="security-personnel-select block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-red-500 focus:border-red-500 rounded-lg focus-visible" data-index="<?php echo $index; ?>" <?php echo (strtolower($row["status"] ?? "open") == "completed") ? 'disabled' : ''; ?>>
                                                        <option value="">-- Select Security Personnel --</option>
                                                        <?php foreach ($securityPersonnel as $personnel): ?>
                                                            <option value="<?php echo htmlspecialchars($personnel['Management_ID']); ?>" 
                                                                    data-fullname="<?php echo htmlspecialchars($personnel['firstname'] . ' ' . $personnel['lastname']); ?>">
                                                                <?php echo htmlspecialchars($personnel['firstname'] . ' ' . $personnel['lastname']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                                        <i class='bx bx-chevron-down text-gray-500'></i>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="storedValue-<?php echo $index; ?>" value="">
                                                <input type="hidden" id="storedName-<?php echo $index; ?>" value="">
                                                <p class="mt-1 text-sm text-gray-500">*Required for security action</p>
                                            </div>
                                            
                                            <div class="w-full">
                                                <label for="securityNotes-<?php echo $index; ?>" class="block text-sm font-medium text-gray-700 mb-2">Security Notes:</label>
                                                <textarea id="securityNotes-<?php echo $index; ?>" rows="4" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-red-500 focus:border-red-500 focus-visible" placeholder="Add security investigation notes here..." <?php echo (strtolower($row["status"] ?? "open") == "completed") ? 'disabled' : ''; ?>></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                                    <button class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center justify-center focus-visible shadow-sm" onclick="exportPDF('incident-modal-<?php echo $index; ?>')" aria-label="Export to PDF">
                                        <i class='bx bx-download mr-2'></i> Export Report
                                    </button>
                                    
                                    <?php if (strtolower($row["status"] ?? "open") != "completed"): ?>
                                    <button class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center update-incident-btn focus-visible shadow-sm" data-id="<?php echo $row["ID"] ?? ''; ?>" data-index="<?php echo $index; ?>" aria-label="Update incident">
                                        <i class='bx bx-save mr-2'></i> Update Incident
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                            $counter++;
                        }
                    } else {
                    ?>
                    <div id="emptyState" class="col-span-full flex flex-col items-center justify-center p-10 bg-white rounded-xl shadow-md">
                        <div class="rounded-full bg-red-100 p-6 mb-4">
                            <i class='bx bx-notepad text-6xl text-red-500'></i>
                        </div>
                        <p class="text-xl text-gray-700 font-medium">No incident reports found</p>
                        <p class="text-gray-500 text-center mt-2">When incident reports are submitted, they will appear here</p>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center hidden" role="dialog" aria-modal="true" aria-label="Image preview">
        <div class="relative max-w-4xl max-h-[90vh] w-full animate-fade-in">
            <button id="closeImageModal" class="absolute top-4 right-4 text-white hover:text-gray-200 focus:outline-none focus-visible" aria-label="Close image preview">
                <i class='bx bx-x text-3xl'></i>
            </button>
            <img id="modalImage" src="" alt="Enlarged image" class="max-w-full max-h-[80vh] mx-auto object-contain">
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-[1001] hidden" role="status" aria-live="polite">
        <div class="flex items-center">
            <i class='bx bx-check-circle text-xl mr-2'></i>
            <span id="toastMessage">Action completed successfully!</span>
        </div>
    </div>

    <script>
        // Initialize AOS animation library and setup UI when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS with optimized settings
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,  // Only animate once
                disable: 'mobile'  // Disable on mobile for better performance
            });
            
            // Cache DOM elements for better performance
            const sidebar = document.querySelector('.sidebar');
            const mobileMenuToggle = document.getElementById('mobile_menu_toggle');
            const modals = document.querySelectorAll('.modal-container');
            const openModalButtons = document.querySelectorAll('.open-modal');
            const closeButtons = document.querySelectorAll('.close');
            const overlays = document.querySelectorAll('.modal-overlay');
            const statusFilter = document.getElementById('statusFilter');
            const priorityFilter = document.getElementById('priorityFilter');
            const categoryFilter = document.getElementById('categoryFilter');
            const searchInput = document.getElementById('searchInput');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const cardContainers = document.querySelectorAll('.card-container');
            const updateButtons = document.querySelectorAll('.update-incident-btn');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const incidentGrid = document.getElementById('incidentGrid');
            const emptyState = document.getElementById('emptyState');
            const activeFilters = document.getElementById('activeFilters');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active') && 
                    !sidebar.contains(event.target) && event.target !== mobileMenuToggle) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Helper function to show loading overlay
            function showLoading() {
                if (loadingOverlay) loadingOverlay.classList.remove('hidden');
            }
            
            // Helper function to hide loading overlay
            function hideLoading() {
                if (loadingOverlay) loadingOverlay.classList.add('hidden');
            }
            
            // Function to handle filtering and search
            function applyFiltersAndSearch() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value;
                const priorityValue = priorityFilter.value;
                const categoryValue = categoryFilter.value;
                let visibleCount = 0;
                
                // Show loading for better UX
                showLoading();
                
                // Use setTimeout to allow the UI to update before processing
                setTimeout(() => {
                    cardContainers.forEach(card => {
                        const cardText = card.textContent.toLowerCase();
                        const status = card.getAttribute('data-status');
                        const priority = card.getAttribute('data-priority');
                        const category = card.getAttribute('data-category');
                        
                        const matchesSearch = searchTerm === '' || cardText.includes(searchTerm);
                        const matchesStatus = statusValue === '' || (status && status.toLowerCase() === statusValue.toLowerCase());
                        const matchesPriority = priorityValue === '' || (priority && priority.toLowerCase() === priorityValue.toLowerCase());
                        const matchesCategory = categoryValue === '' || (category && category.toLowerCase() === categoryValue.toLowerCase());
                        
                        if (matchesSearch && matchesStatus && matchesPriority && matchesCategory) {
                            card.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            card.classList.add('hidden');
                        }
                    });
                    
                    // Show/hide no results message
                    if (visibleCount === 0 && cardContainers.length > 0) {
                        noResultsMessage.classList.remove('hidden');
                        incidentGrid.classList.add('hidden');
                        if (emptyState) emptyState.classList.add('hidden');
                    } else {
                        noResultsMessage.classList.add('hidden');
                        incidentGrid.classList.remove('hidden');
                        if (emptyState && cardContainers.length === 0) emptyState.classList.remove('hidden');
                    }
                    
                    // Hide loading
                    hideLoading();
                    
                    // Update active filters display
                    updateActiveFilters(searchTerm, statusValue, priorityValue, categoryValue);
                    
                    // Show toast for better feedback
                    if (searchTerm !== '' || statusValue !== '' || priorityValue !== '' || categoryValue !== '') {
                        let message = `Showing ${visibleCount} results`;
                        showToast(message);
                    }
                }, 100); // Short delay for better UI responsiveness
            }
            
            // Update active filters display
            function updateActiveFilters(searchTerm, statusValue, priorityValue, categoryValue) {
                activeFilters.innerHTML = '';
                
                // Add search term chip if exists
                if (searchTerm) {
                    const chip = document.createElement('div');
                    chip.className = 'inline-flex items-center bg-red-100 text-red-800 px-3 py-1 rounded-full gap-1 mr-2 mb-2';
                    chip.innerHTML = `
                        <i class='bx bx-search text-xs mr-1'></i>
                        <span>${searchTerm}</span>
                        <button class="hover:text-red-600 ml-1" onclick="clearSearch()">
                            <i class='bx bx-x'></i>
                        </button>
                    `;
                    activeFilters.appendChild(chip);
                }
                
                // Add status filter chip if not empty
                if (statusValue) {
                    const chip = document.createElement('div');
                    chip.className = 'inline-flex items-center bg-purple-100 text-purple-800 px-3 py-1 rounded-full gap-1 mr-2 mb-2';
                    chip.innerHTML = `
                        <i class='bx bx-loader-circle text-xs mr-1'></i>
                        <span>Status: ${statusValue}</span>
                        <button class="hover:text-purple-600 ml-1" onclick="clearStatusFilter()">
                            <i class='bx bx-x'></i>
                        </button>
                    `;
                    activeFilters.appendChild(chip);
                }
                
                // Add priority filter chip if not empty
                if (priorityValue) {
                    const chip = document.createElement('div');
                    chip.className = 'inline-flex items-center bg-blue-100 text-blue-800 px-3 py-1 rounded-full gap-1 mr-2 mb-2';
                    chip.innerHTML = `
                        <i class='bx bx-error-circle text-xs mr-1'></i>
                        <span>Priority: ${priorityValue}</span>
                        <button class="hover:text-blue-600 ml-1" onclick="clearPriorityFilter()">
                            <i class='bx bx-x'></i>
                        </button>
                    `;
                    activeFilters.appendChild(chip);
                }
                
                // Add category filter chip if not empty
                if (categoryValue) {
                    const chip = document.createElement('div');
                    chip.className = 'inline-flex items-center bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full gap-1 mr-2 mb-2';
                    chip.innerHTML = `
                        <i class='bx bx-category text-xs mr-1'></i>
                        <span>Category: ${categoryValue}</span>
                        <button class="hover:text-indigo-600 ml-1" onclick="clearCategoryFilter()">
                            <i class='bx bx-x'></i>
                        </button>
                    `;
                    activeFilters.appendChild(chip);
                }
            }
            
            // Attach event to clear search button
            if (clearSearchButton) {
                clearSearchButton.addEventListener('click', function() {
                    clearAllFilters();
                });
            }
            
            // Function to clear all filters
            window.clearAllFilters = function() {
                clearSearch();
                clearStatusFilter();
                clearPriorityFilter();
                clearCategoryFilter();
            };
            
            // Function to clear search
            window.clearSearch = function() {
                searchInput.value = '';
                applyFiltersAndSearch();
            };
            
            // Function to clear status filter
            window.clearStatusFilter = function() {
                statusFilter.value = '';
                applyFiltersAndSearch();
            };
            
            // Function to clear priority filter
            window.clearPriorityFilter = function() {
                priorityFilter.value = '';
                applyFiltersAndSearch();
            };
            
            // Function to clear category filter
            window.clearCategoryFilter = function() {
                categoryFilter.value = '';
                applyFiltersAndSearch();
            };
            
            // Open modals with improved accessibility
            openModalButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modalId = this.getAttribute('data-modal-id');
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        modal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                        
                        // Focus first interactive element for accessibility
                        setTimeout(() => {
                            const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                            if (firstFocusable) firstFocusable.focus();
                        }, 100);
                    }
                });
            });
            
            // Close modals
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-close-id');
                    closeModal(modalId);
                });
            });
            
            // Close modals on overlay click
            overlays.forEach(overlay => {
                overlay.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-close-id');
                    closeModal(modalId);
                });
            });
            
            // Helper function to close a modal
            function closeModal(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                    
                    // Return focus to the button that opened the modal
                    const opener = document.querySelector(`[data-modal-id="${modalId}"]`);
                    if (opener) opener.focus();
                }
            }
            
            // Filter functionality
            if (statusFilter) {
                statusFilter.addEventListener('change', applyFiltersAndSearch);
            }
            
            if (priorityFilter) {
                priorityFilter.addEventListener('change', applyFiltersAndSearch);
            }
            
            if (categoryFilter) {
                categoryFilter.addEventListener('change', applyFiltersAndSearch);
            }
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        applyFiltersAndSearch();
                    }
                });
            }
            
            // Update incident functionality
            updateButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const index = this.getAttribute('data-index');
                    
                    // Get assigned security personnel
                    const assignedTo = document.getElementById(`assignedTo-${index}`);
                    const securityPersonnelId = assignedTo ? assignedTo.value : '';
                    
                    if (!securityPersonnelId) {
                        showToast('Please select a security personnel to assign this incident to', 'error');
                        if (assignedTo) assignedTo.focus();
                        return;
                    }
                    
                    // Get security personnel name
                    const selectedOption = assignedTo.options[assignedTo.selectedIndex];
                    const securityPersonnelName = selectedOption.getAttribute('data-fullname') || '';
                    
                    // Get updated status and priority
                    const statusSelect = document.getElementById(`status-select-${index}`);
                    const prioritySelect = document.getElementById(`priority-select-${index}`);
                    const securityNotes = document.getElementById(`securityNotes-${index}`);
                    
                    const newStatus = statusSelect ? statusSelect.value : '';
                    const newPriority = prioritySelect ? prioritySelect.value : '';
                    const notes = securityNotes ? securityNotes.value : '';
                    
                    if (!newStatus) {
                        showToast('Please select a status for this incident', 'error');
                        if (statusSelect) statusSelect.focus();
                        return;
                    }
                    
                    if (!newPriority) {
                        showToast('Please select a priority for this incident', 'error');
                        if (prioritySelect) prioritySelect.focus();
                        return;
                    }
                    
                    // Confirm before proceeding
                    if (confirm(`Are you sure you want to update this incident?\n\nAssigned to: ${securityPersonnelName}\nStatus: ${newStatus}\nPriority: ${newPriority}`)) {
                        // Show loading
                        showLoading();
                        
                        // In a real implementation, you would send an AJAX request to update the incident
                        // This is a simulated response for demonstration
                        setTimeout(() => {
                            hideLoading();
                            
                            // Simulate successful update
                            // Update UI elements on success
                            const card = document.querySelector(`.card-container[data-id="${id}"]`);
                            if (card) {
                                // Update status display on card
                                const statusElement = card.querySelector('[data-status]');
                                if (statusElement) {
                                    statusElement.textContent = newStatus;
                                }
                                
                                // Update priority display on card
                                const priorityElement = card.querySelector('[data-priority]');
                                if (priorityElement) {
                                    priorityElement.textContent = newPriority;
                                }
                                
                                // Update data attributes for filtering
                                card.setAttribute('data-status', newStatus);
                                card.setAttribute('data-priority', newPriority);
                            }
                            
                            // If status is completed, disable form elements
                            if (newStatus.toLowerCase() === 'completed') {
                                if (assignedTo) assignedTo.disabled = true;
                                if (statusSelect) statusSelect.disabled = true;
                                if (prioritySelect) prioritySelect.disabled = true;
                                if (securityNotes) securityNotes.disabled = true;
                                
                                // Hide update button
                                button.style.display = 'none';
                            }
                            
                            // Close modal
                            closeModal(`incident-modal-${index}`);
                            
                            // Show success toast
                            showToast('Incident updated successfully');
                            
                            // In a real application, you would refresh the data or update the UI with the response
                            // For now, we'll just simulate success
                        }, 1000);
                    }
                });
            });
            
            // ESC key to close modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Close regular modals
                    modals.forEach(modal => {
                        if (!modal.classList.contains('hidden')) {
                            const modalId = modal.id;
                            closeModal(modalId);
                        }
                    });
                    
                    // Close image modal
                    const imageModal = document.getElementById('imageModal');
                    if (imageModal && !imageModal.classList.contains('hidden')) {
                        imageModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                }
            });
            
            // Initial load - apply filters
            applyFiltersAndSearch();
            
            // Add resize handler for responsiveness
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    // Any resize-specific adjustments
                }, 100);
            });
        });
        
        // Function to open image modal
        function openImageModal(src) {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            if (modalImage) modalImage.src = src;
            if (imageModal) {
                imageModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }
        
        // Export to PDF function
        function exportPDF(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            
            showToast('Preparing PDF for download...');
            
            // Simple implementation - in a real app you'd use a proper PDF generation library
            setTimeout(() => {
                showToast('PDF export feature would be implemented with a proper PDF library', 'info');
            }, 1500);
        }
        
        // Toast notification function with support for different types
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            
            if (!toast || !toastMessage) return;
            
            // Set toast style based on type
            switch (type) {
                case 'error':
                    toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                    break;
                case 'warning':
                    toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                    break;
                case 'info':
                    toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                    break;
                default:
                    toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
            }
            
            toastMessage.textContent = message;
            toast.classList.remove('hidden');
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }
    </script>
</body>
</html>