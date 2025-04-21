<?php
include "dbconn.php";

// Fetch visitor pass records - keep the original SQL query
$sql = "SELECT o.*, 
        CONCAT(u.First_Name, ' ', IFNULL(u.Middle_Name, ''), ' ', u.Last_Name) AS full_name, 
        u.Unit_Number AS unit_no 
        FROM ownertenantvisitor o 
        LEFT JOIN ownerinformation u ON o.user_id = u.ID 
        ORDER BY o.submitted_at DESC";
$result = $conn->query($sql);

// Count statuses for statistics (only approved and completed now)
$approvedCount = 0;
$completedCount = 0;

if ($result && $result->num_rows > 0) {
    $visitorData = [];
    mysqli_data_seek($result, 0); // Reset result pointer
    while($row = $result->fetch_assoc()) {
        $visitorData[] = $row;
        
        // Count by status
        switch(strtolower($row['Status'])) {
            case 'approved':
                $approvedCount++;
                break;
            case 'completed':
                $completedCount++;
                break;
        }
    }
    // Reset pointer for later use
    mysqli_data_seek($result, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Security Visitor Pass</title>
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
        /* Custom Styles with improved organization */
        /* Base */
        body {
            font-family: 'Google Sans', sans-serif;
        }
        
        /* Components */
        .status-pill {
            @apply px-3 py-1 rounded-full text-xs font-medium;
        }
        .status-approved {
            @apply bg-green-100 text-green-800;
        }
        .status-completed {
            @apply bg-blue-100 text-blue-800;
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
        <!-- Sidebar - Keep original as specified -->
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Swarm Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bx-shield'></i>
                <span>Security</span>
            </div>

            <nav class="sidebar-menu" aria-label="Main Navigation">
                <ul>
                    <li>
                        <a href="security-dashboard.php" class="focus-visible">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-gatepass.php" class="focus-visible">
                            <i class='bx bx-key'></i>
                            <span>Gate Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-guestcheck.php" class="focus-visible">
                            <i class='bx bx-door-open'></i>
                            <span>Guest Check In/Out</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="security-visitor.php" class="focus-visible" aria-current="page">
                            <i class='bx bx-id-card'></i>
                            <span>Visitor Pass</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-incident.php" class="focus-visible">
                            <i class='bx bx-notepad'></i>
                            <span>Incident Reports</span>
                        </a>
                    </li>

                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout focus-visible">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content - Adapted from gatepass design -->
        <main class="w-full">
            <div class="sm:p-6 p-3 sm:pt-4 pt-2">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-5 shadow-lg mb-6" data-aos="fade-down">
                    <div>
                        <h1 class="text-3xl font-bold flex items-center gap-3">
                            <i class='bx bx-id-card text-yellow-300 text-4xl'></i>
                            Visitor Pass Management
                        </h1>
                        <div class="mt-2 flex items-center gap-2 text-blue-100">
                            <i class='bx bx-shield-quarter'></i>
                            <p class="tracking-wide">Review and manage visitor pass requests</p>
                        </div>
                    </div>
                    
                    <!-- Stats Summary -->
                    <div class="mt-4 sm:mt-0 flex gap-4">
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $approvedCount; ?></span>
                            <span class="text-xs">Approved</span>
                        </div>
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $completedCount; ?></span>
                            <span class="text-xs">Completed</span>
                        </div>
                    </div>
                    
                    <!-- Mobile menu toggle -->
                    <button id="mobile_menu_toggle" class="sm:hidden bg-white text-blue-600 p-2 rounded-lg absolute top-4 right-4" aria-label="Toggle menu">
                        <i class='bx bx-menu text-xl'></i>
                    </button>
                </div>
                
                <!-- Search and Filter Section -->
                <div class="bg-white rounded-xl shadow-md p-5 mb-6" data-aos="fade-up">
                    <div class="flex flex-col gap-4">
                        <!-- Title and Description -->
                        <div class="mb-2">
                            <h2 class="text-lg font-semibold text-gray-800">Search & Filter</h2>
                            <p class="text-sm text-gray-500">Find visitor passes by name, unit, or status</p>
                        </div>
                        
                        <!-- Search Bar Group -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class='bx bx-search text-gray-400'></i>
                                </div>
                                <input type="text" id="searchInput" 
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 focus-visible shadow-sm" 
                                    placeholder="Search by name, unit or resident code..." aria-label="Search visitor passes">
                            </div>
                            
                            <!-- Filter Dropdown -->
                            <div class="relative w-full sm:w-[200px]">
                                <select id="filterSelect" 
                                    class="block w-full pl-3 pr-10 py-3 text-base border border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg appearance-none bg-white cursor-pointer focus-visible shadow-sm" 
                                    aria-label="Filter by status">
                                    <option value="all">All Statuses</option>
                                    <option value="approved">Approved</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>

                            <!-- Search Button -->
                            <button id="searchButton" 
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 focus-visible shadow-sm" 
                                aria-label="Search">
                                <i class='bx bx-search'></i>
                                <span>Search</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Bar -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class='bx bx-list-ul text-blue-600'></i>
                        Visitor Pass Records
                    </h2>
                    <div class="flex gap-2">
                        <button class="bg-white hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-lg shadow-sm flex items-center gap-1 focus-visible text-sm font-medium" onclick="window.print()">
                            <i class='bx bx-printer'></i>
                            <span class="hidden sm:inline">Print</span>
                        </button>
                    </div>
                </div>

                <!-- No Results Message (hidden by default) -->
                <div id="noResultsMessage" class="hidden col-span-full flex flex-col items-center justify-center p-10 bg-white rounded-xl shadow-md mb-6" data-aos="fade-up">
                    <div class="rounded-full bg-blue-100 p-6 mb-4">
                        <i class='bx bx-search-alt text-5xl text-blue-500'></i>
                    </div>
                    <p class="text-xl text-gray-700 font-medium">No matching visitor passes found</p>
                    <p class="text-gray-500 text-center mt-2 mb-4">Try adjusting your search criteria</p>
                    <button id="clearSearchButton" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus-visible">
                        Clear Search
                    </button>
                </div>

                <!-- Visitor Pass Cards Grid -->
                <div id="visitorPassGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php 
                    if ($result && $result->num_rows > 0) {
                        $counter = 0;
                        while($row = $result->fetch_assoc()) {
                            $delay = $counter * 50;
                            $status = strtolower($row['Status']);
                            
                            // Skip if not approved or completed
                            if ($status !== 'approved' && $status !== 'completed') {
                                continue;
                            }
                            
                            $statusClass = '';
                            $statusIcon = '';
                            
                            switch($status) {
                                case 'approved':
                                    $statusClass = 'status-approved';
                                    $statusIcon = 'bx-check';
                                    break;
                                case 'completed':
                                    $statusClass = 'status-completed';
                                    $statusIcon = 'bx-check-circle';
                                    break;
                                default:
                                    // Skip this iteration of the loop and move to the next one
                                    continue 2; // This skips to the next iteration of the while loop
                            }
                            
                            // Safely access data with null coalescing
                            $firstName = htmlspecialchars($row['full_name'] ?? 'Unknown');
                            $unit = htmlspecialchars($row['unit_no'] ?? 'No Unit');
                            $residentCode = htmlspecialchars($row['Resident_Code'] ?? 'N/A');
                            $userType = htmlspecialchars(ucfirst($row['user_type'] ?? 'Unknown'));
                            $startDate = !empty($row['start_date']) ? date('M d, Y', strtotime($row['start_date'])) : 'Unknown';
                            $endDate = !empty($row['end_date']) ? date('M d, Y', strtotime($row['end_date'])) : 'Unknown';
                            $createdDate = !empty($row['submitted_at']) ? date('M d, Y', strtotime($row['submitted_at'])) : 'Unknown';
                            $createdTime = !empty($row['submitted_at']) ? date('h:i A', strtotime($row['submitted_at'])) : '';
                    ?>
                    <div data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all cursor-pointer open-modal tile-item overflow-hidden relative border border-gray-100" data-modal-id="modal-<?php echo $row['id']; ?>" data-status="<?php echo $status; ?>">
                        <!-- Status Badge (top-right corner) -->
                        <div class="absolute top-0 right-0">
                            <span class="<?php echo $statusClass; ?> flex items-center px-3 py-1 rounded-bl-xl text-xs font-medium border-l border-b">
                                <i class='bx <?php echo $statusIcon; ?> mr-1'></i>
                                <?php echo ucfirst($row['Status']); ?>
                            </span>
                        </div>

                        <!-- Card Header with Resident Info -->
                        <div class="px-6 pt-6 pb-3">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-blue-100 rounded-full p-2">
                                    <i class='bx bx-user text-2xl text-blue-600'></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800"><?php echo $firstName; ?></h3>
                                    <p class="text-gray-500 text-sm flex items-center gap-1">
                                        <i class='bx bx-building-house text-blue-500'></i>
                                        <?php echo $unit; ?>
                                        <span class="inline-block ml-1 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full"><?php echo $userType; ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Divider -->
                        <div class="border-t border-gray-100"></div>
                        
                        <!-- Card Content -->
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Resident Code</p>
                                    <p class="font-medium"><?php echo $residentCode; ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Valid From</p>
                                    <p class="font-medium"><?php echo $startDate; ?></p>
                                </div>
                                
                                <div class="col-span-1">
                                    <p class="text-gray-500 text-xs mb-1">Valid Until</p>
                                    <p class="font-medium"><?php echo $endDate; ?></p>
                                </div>
                                
                                <div class="col-span-1">
                                    <p class="text-gray-500 text-xs mb-1">Submitted On</p>
                                    <p class="font-medium"><?php echo $createdDate; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Footer with Actions -->
                        <div class="border-t border-gray-100 px-6 py-3 bg-gray-50 flex justify-between items-center">
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center open-modal focus-visible" data-modal-id="modal-<?php echo $row['id']; ?>" aria-label="View details">
                                <i class='bx bx-info-circle mr-1'></i> Details
                            </button>
                            
                            <?php if ($status === 'approved'): ?>
                            <button class="text-white bg-blue-600 hover:bg-blue-700 text-sm font-medium flex items-center complete-btn px-3 py-1 rounded-lg focus-visible" data-id="<?php echo $row['id']; ?>" aria-label="Complete visitor pass">
                                <i class='bx bx-check-double mr-1'></i> Complete
                            </button>
                            <?php else: ?>
                            <span class="text-gray-600 text-sm font-medium flex items-center">
                                <i class='bx bx-check-double mr-1'></i> Verified
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Modal Section -->
                    <div id="modal-<?php echo $row['id']; ?>" class="modal-container fixed inset-0 z-[1000] hidden" role="dialog" aria-labelledby="modalTitle-<?php echo $row['id']; ?>" aria-modal="true">
                        <div class="modal-overlay absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" data-close-id="modal-<?php echo $row['id']; ?>"></div>
                        <div class="modal-content relative bg-white rounded-xl shadow-2xl w-11/12 max-w-4xl mx-auto mt-[2%] h-[96vh] max-h-[700px] flex flex-col">
                            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                                <h3 id="modalTitle-<?php echo $row['id']; ?>" class="text-xl font-bold flex items-center">
                                    <i class='bx bx-id-card mr-2 text-2xl'></i>
                                    Visitor Pass Details <span class="ml-2 text-sm opacity-80">#<?php echo $row['id']; ?></span>
                                </h3>
                                <button class="text-white hover:text-gray-200 close focus-visible" data-close-id="modal-<?php echo $row['id']; ?>" aria-label="Close">
                                    <i class='bx bx-x text-2xl'></i>
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6">
                                <!-- Request Information Card -->
                                <div class="bg-blue-50 p-5 rounded-xl mb-6 border border-blue-100">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <div>
                                            <h4 class="text-sm font-medium text-blue-500 uppercase mb-1">Resident</h4>
                                            <p class="text-lg font-semibold text-gray-800 flex items-center">
                                                <i class="bx bx-user-circle text-blue-500 mr-2"></i>
                                                <?php echo $firstName; ?>
                                                <span class="inline-block ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full"><?php echo $userType; ?></span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-blue-500 uppercase mb-1">Unit</h4>
                                            <p class="text-lg font-semibold text-gray-800">
                                                <span class="inline-flex items-center">
                                                    <i class="bx bx-building-house mr-2 text-blue-500"></i>
                                                    <?php echo $unit; ?>
                                                </span>
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <h4 class="text-sm font-medium text-blue-500 uppercase mb-1">Date Submitted</h4>
                                            <p class="text-md font-semibold text-gray-800">
                                                <span class="inline-flex items-center">
                                                    <i class="bx bx-calendar mr-2 text-blue-500"></i>
                                                    <?php echo $createdDate . ' ' . $createdTime; ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Visit Period Card -->
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                        <h4 class="font-medium text-gray-700 flex items-center">
                                            <i class="bx bx-calendar-check text-blue-500 mr-2"></i>
                                            Visit Period
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-gray-500 text-sm mb-1">Start Date</p>
                                                <p class="text-lg font-medium text-gray-800"><?php echo $startDate; ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-sm mb-1">End Date</p>
                                                <p class="text-lg font-medium text-gray-800"><?php echo $endDate; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Guest Information Card -->
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                        <h4 class="font-medium text-gray-700 flex items-center">
                                            <i class="bx bx-group text-blue-500 mr-2"></i>
                                            Guest Information
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <?php 
                                        if (!empty($row['guest_info'])) {
                                            try {
                                                $guestData = json_decode($row['guest_info'], true);
                                                if (is_array($guestData) && count($guestData) > 0) {
                                                    echo '<div class="space-y-4">';
                                                    foreach ($guestData as $index => $guest) {
                                                        echo '<div class="' . ($index > 0 ? 'pt-4 border-t border-gray-200' : '') . '">';
                                                        echo '<h5 class="font-semibold mb-2">Guest ' . (count($guestData) > 1 ? ($index + 1) : '') . '</h5>';
                                                        echo '<div class="grid grid-cols-2 gap-y-2">';
                                                        echo '<div class="text-gray-500">Name:</div>';
                                                        echo '<div class="font-medium">' . htmlspecialchars($guest['name'] ?? 'N/A') . '</div>';
                                                        echo '<div class="text-gray-500">Contact:</div>';
                                                        echo '<div class="font-medium">' . htmlspecialchars($guest['contact'] ?? 'N/A') . '</div>';
                                                        echo '<div class="text-gray-500">Relationship:</div>';
                                                        echo '<div class="font-medium">' . htmlspecialchars($guest['relationship'] ?? 'N/A') . '</div>';
                                                        echo '</div>';
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<p class="text-gray-500 italic">No guest information provided</p>';
                                                }
                                            } catch (Exception $e) {
                                                echo '<p class="text-gray-500 italic">Invalid guest information format</p>';
                                            }
                                        } else {
                                            echo '<p class="text-gray-500 italic">No guest information provided</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Valid ID and Signature Cards -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                            <h4 class="font-medium text-gray-700 flex items-center">
                                                <i class="bx bx-id-card text-blue-500 mr-2"></i>
                                                Valid ID
                                            </h4>
                                        </div>
                                        <div class="p-5 flex justify-center">
                                            <?php if (!empty($row['valid_id'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['valid_id']); ?>" alt="Valid ID" class="max-w-full max-h-40 object-contain cursor-pointer hover:opacity-90" onclick="openImageModal(this.src)">
                                            <?php else: ?>
                                                <p class="text-gray-500 italic">No ID uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                            <h4 class="font-medium text-gray-700 flex items-center">
                                                <i class="bx bx-pen text-blue-500 mr-2"></i>
                                                Signature
                                            </h4>
                                        </div>
                                        <div class="p-5 flex justify-center">
                                            <?php if (!empty($row['signature'])): ?>
                                                <img src="<?php echo htmlspecialchars($row['signature']); ?>" alt="Signature" class="max-w-full max-h-40 object-contain cursor-pointer hover:opacity-90" onclick="openImageModal(this.src)">
                                            <?php else: ?>
                                                <p class="text-gray-500 italic">No signature available</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Security Verification Card for Approved passes -->
                                <?php if ($status === 'approved'): ?>
                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
                                    <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                                        <h4 class="font-medium text-gray-700 flex items-center">
                                            <i class="bx bx-shield-quarter text-blue-500 mr-2"></i>
                                            Security Verification
                                        </h4>
                                    </div>
                                    <div class="p-5">
                                        <div class="w-full mb-4">
                                            <label for="remarks-<?php echo $row['id']; ?>" class="block text-sm font-medium text-gray-700 mb-2">Remarks:</label>
                                            <textarea id="remarks-<?php echo $row['id']; ?>" rows="3" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus-visible" placeholder="Add any additional notes or remarks here..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                                    <button class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center justify-center focus-visible shadow-sm" onclick="exportPDF('modal-<?php echo $row['id']; ?>')" aria-label="Export to PDF">
                                        <i class='bx bx-download mr-2'></i> Export to PDF
                                    </button>
                                    
                                    <?php if ($status === 'approved'): ?>
                                    <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center complete-btn focus-visible shadow-sm" data-id="<?php echo $row['id']; ?>" aria-label="Complete visitor pass">
                                        <i class='bx bx-check-double mr-2'></i> Complete
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
                    <div id="emptyState" class="col-span-full flex flex-col items-center justify-center p-10 bg-white rounded-xl shadow-md" data-aos="fade-up">
                        <div class="rounded-full bg-blue-100 p-6 mb-4">
                            <i class='bx bx-id-card text-6xl text-blue-500'></i>
                        </div>
                        <p class="text-xl text-gray-700 font-medium">No visitor pass records found</p>
                        <p class="text-gray-500 text-center mt-2">When visitor passes are submitted, they will appear here</p>
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
            const filterSelect = document.getElementById('filterSelect');
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const clearSearchButton = document.getElementById('clearSearchButton');
            const tileItems = document.querySelectorAll('.tile-item');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const visitorPassGrid = document.getElementById('visitorPassGrid');
            const emptyState = document.getElementById('emptyState');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const completeButtons = document.querySelectorAll('.complete-btn');
            const imageModal = document.getElementById('imageModal');
            const closeImageModal = document.getElementById('closeImageModal');
            const modalImage = document.getElementById('modalImage');
            
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
                const filterValue = filterSelect.value;
                let visibleCount = 0;
                
                // Show loading for better UX
                showLoading();
                
                // Use setTimeout to allow the UI to update before processing
                setTimeout(() => {
                    tileItems.forEach(item => {
                        const itemText = item.textContent.toLowerCase();
                        const status = item.getAttribute('data-status');
                        const matchesSearch = searchTerm === '' || itemText.includes(searchTerm);
                        const matchesFilter = filterValue === 'all' || status === filterValue;
                        
                        if (matchesSearch && matchesFilter) {
                            item.classList.remove('hidden');
                            visibleCount++;
                        } else {
                            item.classList.add('hidden');
                        }
                    });
                    
                    // Show/hide no results message
                    if (visibleCount === 0 && tileItems.length > 0) {
                        noResultsMessage.classList.remove('hidden');
                        visitorPassGrid.classList.add('hidden');
                        if (emptyState) emptyState.classList.add('hidden');
                    } else {
                        noResultsMessage.classList.add('hidden');
                        visitorPassGrid.classList.remove('hidden');
                        if (emptyState && tileItems.length === 0) emptyState.classList.remove('hidden');
                    }
                    
                    // Hide loading
                    hideLoading();
                    
                    // Show toast for better feedback
                    if (searchTerm !== '' || filterValue !== 'all') {
                        let message = `Showing ${visibleCount} results`;
                        if (searchTerm !== '') message += ` for "${searchTerm}"`;
                        if (filterValue !== 'all') message += ` with status "${filterValue}"`;
                        showToast(message);
                    }
                }, 100); // Short delay for better UI responsiveness
            }
            
            // Attach event to clear search button
            if (clearSearchButton) {
                clearSearchButton.addEventListener('click', function() {
                    clearSearch();
                    clearFilter();
                });
            }
            
            // Function to clear search
            window.clearSearch = function() {
                searchInput.value = '';
                applyFiltersAndSearch();
            };
            
            // Function to clear filter
            window.clearFilter = function() {
                filterSelect.value = 'all';
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
            if (filterSelect) {
                filterSelect.addEventListener('change', applyFiltersAndSearch);
            }
            
            // Search functionality
            if (searchButton) {
                searchButton.addEventListener('click', applyFiltersAndSearch);
            }
            
            // Allow Enter key for search
            if (searchInput) {
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        applyFiltersAndSearch();
                    }
                });
            }
            
            // Complete visitor pass
            completeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    
                    // Get remarks if any
                    const remarks = document.getElementById(`remarks-${id}`);
                    const remarksText = remarks ? remarks.value : '';
                    
                    // Confirm before proceeding
                    if (confirm('Are you sure you want to mark this visitor pass as completed?')) {
                        // Show loading
                        showLoading();
                        
                        // AJAX request with improved error handling
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'update_visitor_status.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            hideLoading();
                            
                            if (xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        // Update UI
                                        const tileItem = document.querySelector(`.tile-item[data-modal-id="modal-${id}"]`);
                                        if (tileItem) {
                                            const statusElement = tileItem.querySelector('.status-approved');
                                            if (statusElement) {
                                                statusElement.textContent = 'Completed';
                                                statusElement.classList.remove('status-approved');
                                                statusElement.classList.add('status-completed');
                                                statusElement.innerHTML = '<i class="bx bx-check-circle mr-1"></i> Completed';
                                            }
                                            tileItem.setAttribute('data-status', 'completed');
                                        }
                                        
                                        // Close modal
                                        closeModal(`modal-${id}`);
                                        
                                        // Show success toast
                                        showToast('Visitor pass marked as completed');
                                        
                                        // Refresh the page after a delay
                                        setTimeout(() => {
                                            location.reload();
                                        }, 1500);
                                    } else {
                                        showToast('Error: ' + (response.message || 'Unknown error'), 'error');
                                    }
                                } catch (e) {
                                    console.error("JSON parsing error:", e);
                                    showToast('Error processing response. Please try again.', 'error');
                                }
                            } else {
                                showToast('Server error: ' + xhr.status + ' ' + xhr.statusText, 'error');
                            }
                        };
                        xhr.onerror = function() {
                            hideLoading();
                            showToast('Network error. Please check your connection and try again.', 'error');
                        };
                        xhr.send(`id=${id}&action=complete&remarks=${encodeURIComponent(remarksText)}`);
                    }
                });
            });
            
            // Image modal functionality
            if (closeImageModal) {
                closeImageModal.addEventListener('click', function() {
                    if (imageModal) {
                        imageModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }
            
            // Close image modal on background click
            if (imageModal) {
                imageModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        imageModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                });
            }
            
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
                    if (imageModal && !imageModal.classList.contains('hidden')) {
                        imageModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    }
                }
            });
            
            // Add resize handler for responsiveness
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    // Handle any resize-specific adjustments here
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
            
            // Simple implementation - in a real app you'd use a library like jsPDF or make a server request
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