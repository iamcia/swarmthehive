<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Security Guest Check In/Out</title>
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
        /* Custom Styles with improved organization */
        /* Base */
        body {
            font-family: 'Google Sans', sans-serif;
        }
        
        /* Components */
        .status-pill {
            @apply px-3 py-1 rounded-full text-xs font-medium;
        }
        .status-pending {
            @apply bg-yellow-100 text-yellow-800;
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="loading-spinner"></div>
    </div>

    <?php
    include('dbconn.php');

    // Initialize search variables
    $search = "";
    $searchCondition = "";
    $statusFilter = "";
    
    // Handle search functionality
    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $searchCondition = "AND (o.Last_Name LIKE '%$search%' 
                          OR o.First_Name LIKE '%$search%' 
                          OR o.Unit_Number LIKE '%$search%'
                          OR CONCAT(o.Tower, ' ', o.Unit_Number) LIKE '%$search%')";
    }
    
    // Handle status filter
    if(isset($_GET['status']) && !empty($_GET['status'])) {
        $statusFilter = $_GET['status'];
        $statusCondition = "AND LOWER(g.Status) = LOWER('$statusFilter')";
    } else {
        $statusCondition = "";
    }

    // Query to get guest records joined with owner information
    $query = "SELECT g.*, o.Tower, o.Unit_Number, o.Last_Name, o.First_Name, o.Middle_Name 
              FROM guestcheckinout g
              JOIN ownerinformation o ON g.user_id = o.ID
              WHERE 1=1 $searchCondition $statusCondition
              ORDER BY g.Created_At DESC";
    
    $result = $conn->query($query);
    ?>

    <div class="container">
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
                    <li class="active">
                        <a href="security-guestcheck.php" class="focus-visible" aria-current="page">
                            <i class='bx bx-door-open'></i>
                            <span>Guest Check In/Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="security-visitor.php" class="focus-visible">
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

        <!-- Main Content -->
        <main class="w-full">
            <div class="sm:p-6 p-3 sm:pt-4 pt-2">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl p-5 shadow-lg mb-6" data-aos="fade-down">
                    <div>
                        <h1 class="text-3xl font-bold flex items-center gap-3">
                            <i class='bx bx-door-open text-yellow-300 text-4xl'></i>
                            Guest Check In/Out Management
                        </h1>
                        <div class="mt-2 flex items-center gap-2 text-blue-100">
                            <i class='bx bx-shield-quarter'></i>
                            <p class="tracking-wide">Review and process guest check-in/out requests</p>
                        </div>
                    </div>
                    
                    <!-- Stats Summary -->
                    <div class="mt-4 sm:mt-0 flex gap-4">
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold">
                                <?php 
                                    $pendingCount = 0;
                                    $approvedCount = 0;
                                    $completedCount = 0;
                                    
                                    if ($result && $result->num_rows > 0) {
                                        $result->data_seek(0);
                                        while($row = $result->fetch_assoc()) {
                                            if (strtolower($row['Status']) == 'pending') $pendingCount++;
                                            if (strtolower($row['Status']) == 'approved' || strtolower($row['Status']) == 'approval') $approvedCount++;
                                            if (strtolower($row['Status']) == 'completed') $completedCount++;
                                        }
                                        $result->data_seek(0);
                                    }
                                    echo $approvedCount; 
                                ?>
                            </span>
                            <span class="text-xs">Approved</span>
                        </div>
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-lg p-3 text-center">
                            <span class="block text-2xl font-bold"><?php echo $pendingCount; ?></span>
                            <span class="text-xs">Pending</span>
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
                            <p class="text-sm text-gray-500">Find guest check-in/out records by name, unit, or status</p>
                        </div>
                        
                        <!-- Search Bar Group -->
                        <form method="GET" action="" class="flex flex-col sm:flex-row gap-4">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class='bx bx-search text-gray-400'></i>
                                </div>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 focus-visible shadow-sm" 
                                    placeholder="Search by name or unit number..." aria-label="Search guest records">
                            </div>
                            
                            <!-- Filter Dropdown -->
                            <div class="relative w-full sm:w-[200px]">
                                <select name="status" 
                                    class="block w-full pl-3 pr-10 py-3 text-base border border-gray-300 focus:ring-blue-500 focus:border-blue-500 rounded-lg appearance-none bg-white cursor-pointer focus-visible shadow-sm" 
                                    aria-label="Filter by status">
                                    <option value="" <?php echo empty($statusFilter) ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                    <i class='bx bx-chevron-down'></i>
                                </div>
                            </div>

                            <!-- Search Button -->
                            <button type="submit"
                                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 focus-visible shadow-sm" 
                                aria-label="Search">
                                <i class='bx bx-search'></i>
                                <span>Search</span>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Actions Bar -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class='bx bx-list-ul text-blue-600'></i>
                        Guest Records
                    </h2>
                    <div class="flex gap-2">
                        <button class="bg-white hover:bg-gray-50 text-gray-700 py-2 px-4 rounded-lg shadow-sm flex items-center gap-1 focus-visible text-sm font-medium" onclick="window.print()">
                            <i class='bx bx-printer'></i>
                            <span class="hidden sm:inline">Print</span>
                        </button>
                    </div>
                </div>

                <!-- Guest Records Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <?php 
                    if ($result && $result->num_rows > 0) {
                        $counter = 0;
                        while($row = $result->fetch_assoc()) {
                            $delay = $counter * 50;
                            
                            // Determine status class and color
                            $statusClass = "";
                            $statusIcon = "";
                            switch(strtolower($row['Status'])) {
                                case 'approval':
                                case 'approved':
                                    $statusClass = "status-approved";
                                    $statusIcon = "bx-check";
                                    break;
                                case 'pending':
                                    $statusClass = "status-pending";
                                    $statusIcon = "bx-time-five";
                                    break;
                                case 'completed':
                                    $statusClass = "status-completed";
                                    $statusIcon = "bx-check-circle";
                                    break;
                                default:
                                    $statusClass = "status-pending";
                                    $statusIcon = "bx-time-five";
                            }
                            
                            // Format dates
                            $checkinDate = date("M d, Y", strtotime($row['Checkin_Date']));
                            $checkoutDate = !empty($row['Checkout_Date']) ? date("M d, Y", strtotime($row['Checkout_Date'])) : "Not set";
                            $createdDate = date("M d, Y", strtotime($row['Created_At']));
                            $createdTime = date("h:i A", strtotime($row['Created_At']));
                    ?>
                    <div data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all cursor-pointer view-guest-details relative border border-gray-100 overflow-hidden" data-guest-id="<?php echo $row['id']; ?>">
                        <!-- Status Badge (top-right corner) -->
                        <div class="absolute top-0 right-0">
                            <span class="<?php echo $statusClass; ?> flex items-center px-3 py-1 rounded-bl-xl text-xs font-medium border-l border-b">
                                <i class='bx <?php echo $statusIcon; ?> mr-1'></i>
                                <?php echo htmlspecialchars($row['Status']); ?>
                            </span>
                        </div>
                        
                        <!-- Card Header with Resident Info -->
                        <div class="px-6 pt-6 pb-3">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="bg-blue-100 rounded-full p-2">
                                    <i class='bx bx-user text-2xl text-blue-600'></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-800">
                                        <?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?>
                                    </h3>
                                    <p class="text-gray-500 text-sm flex items-center gap-1">
                                        <i class='bx bx-building-house text-blue-500'></i>
                                        <?php echo htmlspecialchars($row['Tower'] . ' ' . $row['Unit_Number']); ?>
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
                                    <p class="text-gray-500 text-xs mb-1">Check-in</p>
                                    <p class="font-medium"><?php echo $checkinDate; ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Check-out</p>
                                    <p class="font-medium"><?php echo $checkoutDate; ?></p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Stay Duration</p>
                                    <p class="font-medium"><?php echo $row['Days_Of_Stay']; ?> days</p>
                                </div>
                                
                                <div>
                                    <p class="text-gray-500 text-xs mb-1">Date Created</p>
                                    <p class="font-medium"><?php echo $createdDate; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card Footer with Actions -->
                        <div class="border-t border-gray-100 px-6 py-3 bg-gray-50 flex justify-between items-center">
                            <button class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center focus-visible" aria-label="View details">
                                <i class='bx bx-info-circle mr-1'></i> Details
                            </button>
                            
                            <?php if(strtolower($row['Status']) == 'pending'): ?>
                            <button class="text-white bg-green-600 hover:bg-green-700 text-sm font-medium flex items-center px-3 py-1 rounded-lg focus-visible" data-guest-id="<?php echo $row['id']; ?>" data-action="approve" aria-label="Approve guest">
                                <i class='bx bx-check mr-1'></i> Approve
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                            $counter++;
                        }
                    } else {
                    ?>
                    <div class="col-span-full flex flex-col items-center justify-center p-10 bg-white rounded-xl shadow-md" data-aos="fade-up">
                        <div class="rounded-full bg-blue-100 p-6 mb-4">
                            <i class='bx bx-user-x text-5xl text-blue-500'></i>
                        </div>
                        <p class="text-xl text-gray-700 font-medium">No guest records found</p>
                        <p class="text-gray-500 text-center mt-2 mb-4">Try adjusting your search criteria</p>
                        <a href="security-guestcheck.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus-visible">
                            Clear Filters
                        </a>
                    </div>
                    <?php } ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($result && $result->num_rows > 9): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                        <a href="#" class="py-2 px-4 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-l-md focus-visible">
                            <i class='bx bx-chevron-left'></i>
                        </a>
                        <a href="#" class="py-2 px-4 bg-blue-600 text-white border border-blue-600 focus-visible">1</a>
                        <a href="#" class="py-2 px-4 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus-visible">2</a>
                        <a href="#" class="py-2 px-4 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 focus-visible">3</a>
                        <a href="#" class="py-2 px-4 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-r-md focus-visible">
                            <i class='bx bx-chevron-right'></i>
                        </a>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <!-- Guest Details Modal -->
        <div id="guestDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-50 flex items-center justify-center hidden" role="dialog" aria-modal="true">
            <div class="bg-white rounded-xl shadow-2xl w-11/12 max-w-4xl max-h-[90vh] overflow-y-auto animate-slide-in">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl flex justify-between items-center">
                    <h3 class="text-xl font-bold flex items-center">
                        <i class='bx bx-user-circle text-2xl mr-2'></i>
                        Guest Details
                    </h3>
                    <button id="closeModal" class="text-white hover:text-gray-200 focus-visible" aria-label="Close modal">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div id="modalContent" class="p-6">
                    <!-- Loading spinner shown while fetching data -->
                    <div id="loadingSpinner" class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                    </div>
                    
                    <!-- Content will be loaded via AJAX -->
                    <div id="guestData" class="hidden">
                        <!-- Guest Info Section -->
                        <div class="mb-6 border-b pb-6">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                                <div class="flex items-center mb-4 md:mb-0">
                                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                        <i class="bx bx-user text-3xl text-blue-600"></i>
                                    </div>
                                    <div>
                                        <h4 id="guestName" class="text-2xl font-bold text-gray-800">Guest Name</h4>
                                        <p id="residentCode" class="text-gray-500">Resident Code: <span class="text-gray-700 font-medium"></span></p>
                                    </div>
                                </div>
                                <div id="statusBadge" class="status-pending rounded-full text-sm font-medium px-3 py-1 flex items-center">
                                    <i class='bx bx-time-five mr-1'></i> Pending
                                </div>
                            </div>
                        </div>
                        
                        <!-- Guest Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- Left Column - Stay Details -->
                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                                    <i class='bx bx-calendar text-blue-500 mr-2'></i> Stay Information
                                </h4>
                                
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-log-in text-green-600'></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Check-in Date</p>
                                            <p id="checkinDate" class="font-medium text-gray-800">-</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-log-out text-red-600'></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Check-out Date</p>
                                            <p id="checkoutDate" class="font-medium text-gray-800">-</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-time text-purple-600'></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Duration of Stay</p>
                                            <p id="stayDuration" class="font-medium text-gray-800">-</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-buildings text-yellow-600'></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Unit Type</p>
                                            <p id="unitType" class="font-medium text-gray-800">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Guest & Documents -->
                            <div class="space-y-6">
                                <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 flex items-center">
                                    <i class='bx bx-file text-blue-500 mr-2'></i> Guest Details & Documents
                                </h4>
                                
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-info-circle text-blue-600'></i>
                                        </div>
                                        <div class="w-full">
                                            <p class="text-sm text-gray-500">Guest Information</p>
                                            <div id="guestInfo" class="font-medium text-gray-800 mt-1">
                                                <!-- Guest info will be dynamically populated -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-id-card text-indigo-600'></i>
                                        </div>
                                        <div class="w-full">
                                            <p class="text-sm text-gray-500">Valid ID</p>
                                            <div id="validID" class="mt-2">
                                                <!-- Image will be dynamically loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-plus-medical text-green-600'></i>
                                        </div>
                                        <div class="w-full">
                                            <p class="text-sm text-gray-500">Vaccine Card</p>
                                            <div id="vaccineCard" class="mt-2">
                                                <!-- Image will be dynamically loaded here -->
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                                            <i class='bx bx-user-voice text-gray-600'></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">User Type</p>
                                            <p id="userType" class="font-medium text-gray-800">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Owner Information -->
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4 flex items-center">
                                <i class='bx bx-home-alt text-blue-500 mr-2'></i> Unit Owner Information
                            </h4>
                            
                            <div class="bg-gray-50 rounded-lg p-4 flex items-start">
                                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center mr-4">
                                    <i class='bx bx-user-pin text-2xl text-amber-600'></i>
                                </div>
                                <div>
                                    <h5 id="ownerName" class="font-semibold text-gray-800">-</h5>
                                    <p id="unitDetails" class="text-gray-600 text-sm">-</p>
                                    <p id="ownerContact" class="text-gray-600 text-sm mt-1">-</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Created At Information -->
                        <div class="text-right text-gray-500 text-sm">
                            <span>Created on <span id="createdAt">-</span></span>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer with Actions -->
                <div class="bg-gray-100 px-6 py-4 rounded-b-xl flex justify-end space-x-3">
                    <button id="cancelModal" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 font-medium transition-colors focus-visible">
                        Close
                    </button>
                    <button id="approveButton" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 rounded-lg text-white font-medium transition-colors focus-visible">
                        <i class='bx bx-check mr-1'></i> Approve
                    </button>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <div id="imageViewerModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center hidden" role="dialog" aria-modal="true" aria-label="Image preview">
            <div class="relative max-w-4xl max-h-[90vh] w-full animate-fade-in">
                <button id="closeImageViewer" class="absolute top-4 right-4 text-white hover:text-gray-200 focus-visible" aria-label="Close image preview">
                    <i class='bx bx-x text-3xl'></i>
                </button>
                <img id="viewerImage" src="" alt="Enlarged image" class="max-w-full max-h-[80vh] mx-auto object-contain">
            </div>
        </div>

        <!-- Toast Notification -->
        <div id="toast" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-[1001] hidden" role="status" aria-live="polite">
            <div class="flex items-center">
                <i class='bx bx-check-circle text-xl mr-2'></i>
                <span id="toastMessage">Action completed successfully!</span>
            </div>
        </div>

        <!-- Main page scripts -->
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
                
                // Mobile menu toggle
                const mobileMenuToggle = document.getElementById('mobile_menu_toggle');
                const sidebar = document.querySelector('.sidebar');
                
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
                
                // Modal functionality
                const modal = document.getElementById('guestDetailsModal');
                const closeModal = document.getElementById('closeModal');
                const cancelModal = document.getElementById('cancelModal');
                const guestCards = document.querySelectorAll('.view-guest-details');
                const loadingSpinner = document.getElementById('loadingSpinner');
                const guestData = document.getElementById('guestData');
                
                // Open modal and fetch guest details
                guestCards.forEach(card => {
                    card.addEventListener('click', function() {
                        const guestId = this.getAttribute('data-guest-id');
                        modal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                        
                        // Show loading spinner, hide data
                        loadingSpinner.classList.remove('hidden');
                        guestData.classList.add('hidden');
                        
                        // Fetch guest details
                        fetchGuestDetails(guestId);
                    });
                });
                
                // Close modal functions
                function closeModalFunction() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }
                
                if (closeModal) closeModal.addEventListener('click', closeModalFunction);
                if (cancelModal) cancelModal.addEventListener('click', closeModalFunction);
                
                // Close modal when clicking outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModalFunction();
                });
                
                // ESC key to close modal
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModalFunction();
                    }
                });
                
                // Approval functionality for buttons in card view
                const approveButtons = document.querySelectorAll('[data-action="approve"]');
                approveButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.stopPropagation(); // Prevent opening modal
                        const guestId = this.getAttribute('data-guest-id');
                        
                        if (confirm('Are you sure you want to approve this guest check-in?')) {
                            const loadingOverlay = document.getElementById('loadingOverlay');
                            if (loadingOverlay) loadingOverlay.classList.remove('hidden');
                            
                            // AJAX request to approve guest
                            fetch('approve-guest.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'id=' + guestId
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (loadingOverlay) loadingOverlay.classList.add('hidden');
                                
                                if (data.success) {
                                    // Update the card status
                                    const card = this.closest('.view-guest-details');
                                    if (card) {
                                        const statusSpan = card.querySelector('.status-pending');
                                        if (statusSpan) {
                                            statusSpan.className = 'status-approved flex items-center px-3 py-1 rounded-bl-xl text-xs font-medium border-l border-b';
                                            statusSpan.innerHTML = '<i class="bx bx-check mr-1"></i> Approved';
                                        }
                                        
                                        // Remove the approve button
                                        this.remove();
                                    }
                                    
                                    // Show success toast
                                    showToast('Guest check-in approved successfully');
                                } else {
                                    showToast('Failed to approve: ' + (data.message || 'Unknown error'), 'error');
                                }
                            })
                            .catch(error => {
                                if (loadingOverlay) loadingOverlay.classList.add('hidden');
                                console.error('Error approving guest:', error);
                                showToast('Failed to approve. Please try again.', 'error');
                            });
                        }
                    });
                });
                
                // Fetch guest details via AJAX - This would need the actual implementation from your backend
                function fetchGuestDetails(guestId) {
                    // Simulate AJAX request with setTimeout - replace this with your actual fetch
                    setTimeout(() => {
                        // Simulated data - you would replace this with data from your API
                        const data = {
                            id: guestId,
                            guest_name: "John Smith",
                            resident_code: "RES-12345",
                            status: "Pending",
                            checkin_date: "Apr 15, 2025",
                            checkout_date: "Apr 20, 2025",
                            days_of_stay: "5",
                            unit_type: "2-Bedroom",
                            guest_info: [
                                { guest_no: "1", name: "Jane Smith", contact: "123-456-7890", relationship: "Wife" },
                                { guest_no: "2", name: "Tommy Smith", contact: "123-456-7891", relationship: "Son" }
                            ],
                            valid_id: "https://via.placeholder.com/300x200",
                            vaccine_card: "https://via.placeholder.com/300x200",
                            user_type: "Owner",
                            owner_name: "Robert Johnson",
                            tower: "Tower A",
                            unit_number: "1201",
                            mobile_number: "987-654-3210",
                            created_at: "Apr 10, 2025"
                        };
                        
                        // Hide loading, show data
                        loadingSpinner.classList.add('hidden');
                        guestData.classList.remove('hidden');
                        
                        // Populate modal with data
                        document.getElementById('guestName').textContent = data.guest_name;
                        document.getElementById('residentCode').querySelector('span').textContent = data.resident_code;
                        
                        // Set status with appropriate color
                        const statusBadge = document.getElementById('statusBadge');
                        statusBadge.textContent = data.status;
                        
                        // Set status class based on status value
                        statusBadge.className = 'px-3 py-1 rounded-full text-xs font-medium flex items-center';
                        
                        if (data.status === 'Approved' || data.status === 'Approval') {
                            statusBadge.classList.add('bg-green-100', 'text-green-800');
                            statusBadge.innerHTML = '<i class="bx bx-check mr-1"></i> ' + data.status;
                        } else if (data.status === 'Pending') {
                            statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                            statusBadge.innerHTML = '<i class="bx bx-time-five mr-1"></i> ' + data.status;
                        } else if (data.status === 'Completed') {
                            statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                            statusBadge.innerHTML = '<i class="bx bx-check-circle mr-1"></i> ' + data.status;
                        } else {
                            statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                            statusBadge.innerHTML = '<i class="bx bx-info-circle mr-1"></i> ' + data.status;
                        }
                        
                        // Update stay information
                        document.getElementById('checkinDate').textContent = data.checkin_date;
                        document.getElementById('checkoutDate').textContent = data.checkout_date || 'Not set';
                        document.getElementById('stayDuration').textContent = data.days_of_stay + ' days';
                        document.getElementById('unitType').textContent = data.unit_type;
                        
                        // Update guest details with structured format
                        const guestInfoContainer = document.getElementById('guestInfo');
                        guestInfoContainer.innerHTML = ''; // Clear previous content
                        
                        if (data.guest_info && Array.isArray(data.guest_info)) {
                            // Create a table for guest information
                            const table = document.createElement('table');
                            table.className = 'min-w-full divide-y divide-gray-200 text-sm';
                            
                            // Create table header
                            const thead = document.createElement('thead');
                            thead.className = 'bg-gray-50';
                            thead.innerHTML = `
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guest No.</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Relationship</th>
                                </tr>
                            `;
                            table.appendChild(thead);
                            
                            // Create table body
                            const tbody = document.createElement('tbody');
                            tbody.className = 'bg-white divide-y divide-gray-200';
                            
                            data.guest_info.forEach((guest, index) => {
                                const row = document.createElement('tr');
                                row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                                
                                row.innerHTML = `
                                    <td class="px-3 py-2 whitespace-nowrap">${guest.guest_no || '-'}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">${guest.name || '-'}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">${guest.contact || '-'}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">${guest.relationship || '-'}</td>
                                `;
                                
                                tbody.appendChild(row);
                            });
                            
                            table.appendChild(tbody);
                            guestInfoContainer.appendChild(table);
                        } else {
                            // Fallback if not an array or no guest info
                            guestInfoContainer.textContent = typeof data.guest_info === 'string' ? 
                                data.guest_info : 'No detailed guest information available';
                        }
                        
                        // Handle Valid ID display
                        const validIDContainer = document.getElementById('validID');
                        validIDContainer.innerHTML = '';
                        
                        if (data.valid_id) {
                            validIDContainer.innerHTML = `
                                <div class="relative">
                                    <img src="${data.valid_id}" alt="Valid ID" class="max-h-48 rounded border border-gray-200 cursor-pointer" 
                                         onclick="openImageViewer('${data.valid_id}', 'Valid ID')">
                                    <div class="absolute bottom-2 right-2">
                                        <button class="bg-blue-500 hover:bg-blue-600 text-white rounded-full p-2 shadow-md focus-visible" 
                                                onclick="openImageViewer('${data.valid_id}', 'Valid ID')">
                                            <i class="bx bx-fullscreen"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Click to enlarge</p>
                            `;
                        } else {
                            validIDContainer.innerHTML = '<p class="text-gray-500 italic">No ID provided</p>';
                        }
                        
                        // Handle Vaccine Card display
                        const vaccineCardContainer = document.getElementById('vaccineCard');
                        vaccineCardContainer.innerHTML = '';
                        
                        if (data.vaccine_card) {
                            vaccineCardContainer.innerHTML = `
                                <div class="relative">
                                    <img src="${data.vaccine_card}" alt="Vaccine Card" class="max-h-48 rounded border border-gray-200 cursor-pointer" 
                                         onclick="openImageViewer('${data.vaccine_card}', 'Vaccine Card')">
                                    <div class="absolute bottom-2 right-2">
                                        <button class="bg-blue-500 hover:bg-blue-600 text-white rounded-full p-2 shadow-md focus-visible"
                                                onclick="openImageViewer('${data.vaccine_card}', 'Vaccine Card')">
                                            <i class="bx bx-fullscreen"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Click to enlarge</p>
                            `;
                        } else {
                            vaccineCardContainer.innerHTML = '<p class="text-gray-500 italic">No vaccine card provided</p>';
                        }
                        
                        // Update guest details
                        document.getElementById('userType').textContent = data.user_type;
                        
                        // Update owner information
                        document.getElementById('ownerName').textContent = data.owner_name;
                        document.getElementById('unitDetails').textContent = data.tower + ' ' + data.unit_number;
                        document.getElementById('ownerContact').textContent = 'Contact: ' + data.mobile_number;
                        
                        // Update created at date
                        document.getElementById('createdAt').textContent = data.created_at;
                        
                        // Hide approve button if already approved
                        const approveButton = document.getElementById('approveButton');
                        if (data.status === 'Approved' || data.status === 'Approval' || data.status === 'Completed') {
                            approveButton.classList.add('hidden');
                        } else {
                            approveButton.classList.remove('hidden');
                            approveButton.setAttribute('data-guest-id', guestId);
                        }
                    }, 500);
                }
                
                // Handle approve button click
                const approveButton = document.getElementById('approveButton');
                if (approveButton) {
                    approveButton.addEventListener('click', function() {
                        const guestId = this.getAttribute('data-guest-id');
                        
                        if (confirm('Are you sure you want to approve this guest check-in?')) {
                            const loadingOverlay = document.getElementById('loadingOverlay');
                            if (loadingOverlay) loadingOverlay.classList.remove('hidden');
                            
                            // AJAX request to approve guest - this is just a simulation, replace with actual implementation
                            setTimeout(() => {
                                if (loadingOverlay) loadingOverlay.classList.add('hidden');
                                
                                // Update status in the modal
                                const statusBadge = document.getElementById('statusBadge');
                                statusBadge.className = 'bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium flex items-center';
                                statusBadge.innerHTML = '<i class="bx bx-check mr-1"></i> Approved';
                                
                                // Hide approve button
                                approveButton.classList.add('hidden');
                                
                                // Show success toast
                                showToast('Guest check-in approved successfully');
                                
                                // Close modal after a delay
                                setTimeout(() => {
                                    closeModalFunction();
                                    
                                    // Refresh page to show updated status
                                    location.reload();
                                }, 1500);
                            }, 1000);
                        }
                    });
                }
                
                // Image viewer functionality
                const imageViewerModal = document.getElementById('imageViewerModal');
                const viewerImage = document.getElementById('viewerImage');
                const closeImageViewer = document.getElementById('closeImageViewer');
                
                if (closeImageViewer) {
                    closeImageViewer.addEventListener('click', function() {
                        imageViewerModal.classList.add('hidden');
                        document.body.style.overflow = '';
                    });
                }
                
                // Close image viewer on background click
                if (imageViewerModal) {
                    imageViewerModal.addEventListener('click', function(e) {
                        if (e.target === imageViewerModal) {
                            imageViewerModal.classList.add('hidden');
                            document.body.style.overflow = '';
                        }
                    });
                }
                
                // Global function to open image viewer
                window.openImageViewer = function(imageSrc, title) {
                    viewerImage.src = imageSrc;
                    viewerImage.alt = title;
                    imageViewerModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                };
            });
            
            // Toast notification function with support for different types
            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toastMessage');
                
                if (!toast || !toastMessage) return;
                
                // Set toast style based on type
                switch (type) {
                    case 'error':
                        toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                        toast.querySelector('i').className = 'bx bx-error-circle text-xl mr-2';
                        break;
                    case 'warning':
                        toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-yellow-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                        toast.querySelector('i').className = 'bx bx-error text-xl mr-2';
                        break;
                    case 'info':
                        toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                        toast.querySelector('i').className = 'bx bx-info-circle text-xl mr-2';
                        break;
                    default:
                        toast.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg z-[1001]';
                        toast.querySelector('i').className = 'bx bx-check-circle text-xl mr-2';
                }
                
                toastMessage.textContent = message;
                toast.classList.remove('hidden');
                
                // Auto hide after 3 seconds
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 3000);
            }
        </script>
            
    <?php
    // Close database connection
    $conn->close();
    ?>
</body>
</html>