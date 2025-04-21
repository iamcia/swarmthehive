<?php
include('dbconn.php');
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set PHP default timezone
date_default_timezone_set('Asia/Manila');

$message = '';
$userType = '';
$userEmail = '';
$signature = null;
$userNumber = '';
$unitNumber = '';
$residentCode = '';
$status = '';
$userId = null; // Added to store the numeric ID

// Fetch user details if the user is logged in
if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];

    // First, attempt to fetch information from the OwnerInformation table
    $sql = "SELECT ID, Owner_ID, Email, Mobile_Number, Unit_Number, Signature, Status FROM ownerinformation WHERE Username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID']; 
        $userId = $row['ID']; // Get the numeric ID that's used in the feedback table
        $userEmail = $row['Email'];
        $userNumber = $row['Mobile_Number'];
        $unitNumber = $row['Unit_Number'];
        $signature = $row['Signature'];
        $status = $row['Status'];
    } else {
        // If no match found in OwnerInformation, check TenantInformation
        $sql = "SELECT ID, Tenant_ID, Email, Mobile_Number, Unit_Number, Signature, Status FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $userId = $row['ID']; // Get the numeric ID that's used in the feedback table
            $userEmail = $row['Email'];
            $userNumber = $row['Mobile_Number'];
            $unitNumber = $row['Unit_Number'];
            $signature = $row['Signature'];
            $status = $row['Status'];
        }
    }

    $stmt->close();
}

// FIXED: Feedback fetch query to match feedback.sql structure
$feedbacks = [];
if (isset($userId)) {
    $sql = "SELECT 
        f.ID,
        f.concern_category,
        f.concern_details,
        f.concern_media,
        DATE_FORMAT(f.Created_At, '%M %d, %Y %h:%i %p') AS formatted_date,
        f.Created_At,
        f.concern_status,
        f.status,
        f.security_response
    FROM feedback f
    WHERE f.user_id = ?
    ORDER BY f.Created_At DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    if ($stmt->error) {
        echo "<!-- Error fetching feedback: " . $stmt->error . " -->";
    }
    
    $result = $stmt->get_result();
    $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Community Feedback</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
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
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        },
                        accent: '#eab308',
                        dark: '#333333',
                        light: '#F5F5F5'
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(234, 179, 8, 0.1), 0 2px 4px -1px rgba(234, 179, 8, 0.06)',
                        'card-hover': '0 10px 15px -3px rgba(234, 179, 8, 0.1), 0 4px 6px -2px rgba(234, 179, 8, 0.05)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #e9be3a;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #d4a813;
        }

        /* Fix for whitespace issue */
        body {
            min-height: 100vh;
            overflow-x: hidden;
        }
        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            margin: 0 auto;
        }
        /* Responsive padding adjustments */
        @media (max-width: 1024px) {
            .main-content {
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }
        @media (max-width: 640px) {
            .main-content {
                padding-top: 4rem;
            }
        }
        /* Override any margins that might be causing spacing */
        .max-w-4xl {
            margin-left: auto !important;
            margin-right: auto !important;
            margin-top: 1rem !important;
            margin-bottom: 1rem !important;
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .tab-active {
            background-color: #fef9c3;
            color: #854d0e;
        }
        /* Fix for table width */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }
        /* Make sure the table takes full width */
        .table-container table {
            width: 100%;
            min-width: 800px; /* Ensures minimum width for better readability */
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 shadow-md transform transition-transform duration-300 ease-in-out lg:translate-x-0" 
           id="sidebar">
        <!-- Logo and close button -->
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div class="flex items-center">
                <img src="img/logo swarm.png" alt="SWARM Logo" class="w-8 h-8">
                <span class="ml-3 text-lg font-bold text-[#e9be3a]">SWARM Portal</span>
            </div>
            <button class="lg:hidden text-gray-500 hover:text-primary-700 focus:outline-none" id="closeSidebar">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>
        
        <!-- User profile -->
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-[#e9be3a] font-bold">
                    <?php echo substr($_SESSION['username'], 0, 1); ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-800"><?php echo $_SESSION['username']; ?></p>
                    <div class="flex items-center">
                        <span class="inline-block w-2 h-2 rounded-full <?php echo $status == 'Approved' ? 'bg-green-500' : 'bg-yellow-500'; ?>"></span>
                        <p class="ml-1.5 text-xs text-gray-500"><?php echo $status; ?> Account</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between">
                <a href="edit_profile.php" class="text-xs text-[#e9be3a] hover:underline flex items-center">
                    <i class='bx bx-user-circle mr-1'></i> Edit Profile
                </a>
                <a href="logout.php" class="text-xs text-red-500 hover:underline flex items-center">
                    <i class='bx bx-log-out mr-1'></i> Sign Out
                </a>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="py-4">
            <p class="px-6 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main Menu</p>
            
            <?php if ($status == 'Approved' || $status == 'Pending'): ?>
                <!-- Announcements -->
                <a href="ten-announcement.php" 
                   class="flex items-center px-6 py-3 text-[#e9be3a] bg-primary-50 border-r-4 border-[#e9be3a]">
                    <i class='bx bxs-bell-ring text-xl mr-3 text-[#e9be3a]'></i>
                    <span class="font-medium">Announcements</span>
                </a>
                
                <!-- Services -->
                <a href="<?php echo ($status == 'Approved') ? 'ten-services.php' : '#'; ?>" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-wrench text-xl mr-3'></i>
                    <span>Services</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Payment Info -->
                <a href="<?php echo ($status == 'Approved') ? 'ten-paymentinfo.php' : '#'; ?>" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-credit-card text-xl mr-3'></i>
                    <span>Payment Info</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Safety Guidelines -->
                <a href="ten-safetyguidelines.php" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-shield-quarter text-xl mr-3'></i>
                    <span>Safety Guidelines</span>
                </a>
                
                <!-- Community Feedback -->
                <a href="ten-communityfeedback.php" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-message-square-dots text-xl mr-3'></i>
                    <span>Community Feedback</span>
                </a>
            <?php endif; ?>
            
            <!-- Help & Support Button -->
            <div class="mt-6 px-6">
                <button onclick="openHelpModal()" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition-all">
                    <i class='bx bx-help-circle mr-2'></i>
                    <span>Help & Support</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Mobile navbar toggle -->
    <div class="fixed top-4 left-4 z-40 lg:hidden">
        <button id="openSidebar" class="p-2 rounded-md bg-white shadow-md text-primary-700 focus:outline-none">
            <i class='bx bx-menu text-xl'></i>
        </button>
    </div>
    <!-- Main content -->
    <div class="flex-1 lg:pl-64">
        <main class="min-h-screen p-4 md:p-6">
            <!-- Dashboard Header -->
            <header class="bg-white rounded-xl shadow-card p-4 md:p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Community Feedback</h1>
                        <div class="flex items-center mt-2">
                            <i class='bx bx-calendar text-[#e9be3a] mr-2'></i>
                            <span class="text-sm text-gray-600"><?php echo date('l, F j, Y'); ?></span>
                        </div>
                    </div>
                    <div class="mt-4 md:mt-0 flex items-center">
                        <?php if ($status == 'Approved'): ?>
                            <div class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full flex items-center">
                                <i class='bx bx-check-circle mr-1'></i>
                                <span>Verified Owner</span>
                            </div>
                        <?php else: ?>
                            <div class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm rounded-full flex items-center">
                                <i class='bx bx-time mr-1'></i>
                                <span>Verification Pending</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Tab container - Keep existing structure -->
            <div class="w-full mb-6">
                <div class="bg-white rounded-lg shadow-sm p-1">
                    <div class="grid grid-cols-2 gap-1">
                        <button onclick="showTab('submit')" id="submitTab" class="py-2 px-4 rounded-lg text-sm font-medium transition-colors focus:outline-none tab-active">
                            Submit New Concern
                        </button>
                        <button onclick="showTab('requests')" id="requestsTab" class="py-2 px-4 rounded-lg text-sm font-medium transition-colors focus:outline-none">
                            My Requests
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form content -->
            <div id="submitContent" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="bg-white rounded-xl shadow-card p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-700">
                                <i class='bx bx-message-square-dots text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-xl font-bold text-gray-800">Concern Details</h3>
                        </div>
                        <form id="concernForm" method="POST" action="Insert-OwnerCommunityfeedback.php" enctype="multipart/form-data">
                            <!-- Hidden fields -->
                            <input type="hidden" name="resident_code" value="<?php echo $residentCode; ?>">
                            <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
                            <input type="hidden" name="user_email" value="<?php echo $userEmail; ?>">
                            <input type="hidden" name="user_number" value="<?php echo $userNumber; ?>">
                            <input type="hidden" name="unit_number" value="<?php echo $unitNumber; ?>">
                            <input type="hidden" name="signature" value="<?php echo $signature; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $userId; ?>">

                            <div class="space-y-4">
                                <div>
                                    <label for="concern_type" class="block text-sm font-medium text-gray-700 mb-1">Concern Category</label>
                                    <select id="concern_type" name="concern_type" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="facility">Facility Issues</option>
                                        <option value="security">Security Concerns</option>
                                        <option value="neighbor">Neighbor Relations</option>
                                        <option value="noise">Noise Complaints</option>
                                        <option value="maintenance">Maintenance Requests</option>
                                        <option value="billing">Billing & Payments</option>
                                        <option value="amenities">Amenities</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="concern_details" class="block text-sm font-medium text-gray-700 mb-1">Concern Details</label>
                                    <textarea id="concern_details" name="concern_details" rows="4" class="w-full p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500/30 focus:border-yellow-500 resize-none" required></textarea>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Right Column -->
                    <div class="bg-white rounded-xl shadow-card p-6">
                        <div class="flex items-center mb-6">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-700">
                                <i class='bx bx-image-alt text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-xl font-bold text-gray-800">Additional Information</h3>
                        </div>

                        <div class="space-y-6">
                            <!-- Priority Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Concern Priority</label>
                                <div class="grid grid-cols-4 gap-2">
                                    <!-- LOW -->
                                    <input type="radio" name="concern_status" value="LOW" id="status_low" class="peer/low hidden" required>
                                    <label for="status_low" onclick="selectStatus('status_low')" class="text-center py-2.5 border-2 border-green-500 bg-green-50 text-green-700 rounded-md cursor-pointer hover:shadow-md transition-all peer-checked/low:bg-green-100 peer-checked/low:scale-105 peer-checked/low:shadow-md">
                                        LOW
                                    </label>
                                    <!-- MEDIUM -->
                                    <input type="radio" name="concern_status" value="MEDIUM" id="status_medium" class="peer/medium hidden">
                                    <label for="status_medium" onclick="selectStatus('status_medium')" class="text-center py-2.5 border-2 border-yellow-500 bg-yellow-50 text-yellow-700 rounded-md cursor-pointer hover:shadow-md transition-all peer-checked/medium:bg-yellow-100 peer-checked/medium:scale-105 peer-checked/medium:shadow-md">
                                        MEDIUM
                                    </label>
                                    <!-- HIGH -->
                                    <input type="radio" name="concern_status" value="HIGH" id="status_high" class="peer/high hidden">
                                    <label for="status_high" onclick="selectStatus('status_high')" class="text-center py-2.5 border-2 border-orange-500 bg-orange-50 text-orange-700 rounded-md cursor-pointer hover:shadow-md transition-all peer-checked/high:bg-orange-100 peer-checked/high:scale-105 peer-checked/high:shadow-md">
                                        HIGH
                                    </label>
                                    <!-- URGENT -->
                                    <input type="radio" name="concern_status" value="URGENT" id="status_urgent" class="peer/urgent hidden">
                                    <label for="status_urgent" onclick="selectStatus('status_urgent')" class="text-center py-2.5 border-2 border-red-500 bg-red-50 text-red-700 rounded-md cursor-pointer hover:shadow-md transition-all peer-checked/urgent:bg-red-100 peer-checked/urgent:scale-105 peer-checked/urgent:shadow-md">
                                        URGENT
                                    </label>
                                </div>
                            </div>

                            <!-- Media Upload -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Add Photo (Optional)</label>
                                <input type="file" id="concern_media" name="concern_media" accept="image/*" class="hidden" onchange="previewMedia(this)">
                                <div class="h-96 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 flex flex-col justify-center items-center cursor-pointer hover:border-yellow-500 transition-colors group"
                                    onclick="document.getElementById('concern_media').click()">
                                    <div id="media_preview_container" class="w-full h-full flex items-center justify-center"></div>
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-300 group-hover:text-yellow-500 transition-colors" id="uploadIcon"></i>
                                    <span class="text-sm text-gray-500 mt-2 group-hover:text-yellow-600 transition-colors" id="uploadText">Click to upload image</span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="button" onclick="showConfirmationModal()" class="w-full py-3 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white font-medium rounded-lg hover:from-yellow-600 hover:to-yellow-700 transition-all flex items-center justify-center">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Concern
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests content -->
            <div id="requestsContent" class="hidden">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <!-- Header with title -->
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-800">My Feedback History</h2>
                        <p class="text-sm text-gray-500">View and track all your submitted concerns</p>
                    </div>
                    
                    <!-- Feedback table with improved width -->
                    <div class="table-container">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-yellow-50 to-yellow-100 border-b border-yellow-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Details</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Response</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-yellow-800 uppercase tracking-wider">Media</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($feedbacks)): ?>
                                    <tr>
                                        <td colspan="7" class="px-4 py-10 text-center">
                                            <div class="flex flex-col items-center justify-center py-6">
                                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                                <p class="text-gray-500 mb-1">No feedback requests found</p>
                                                <p class="text-sm text-gray-400">Submit a new concern to see it here</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($feedbacks as $feedback): ?>
                                        <tr class="hover:bg-yellow-50 transition-colors">
                                            <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">
                                                <?php 
                                                // Format date to be more compact
                                                $date = new DateTime($feedback['Created_At']);
                                                echo $date->format('M d, Y g:i A');
                                                ?>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?php 
                                                    // Show category icons
                                                    $categoryIcon = match($feedback['concern_category']) {
                                                        'facility' => '<i class="fas fa-building mr-1"></i>',
                                                        'security' => '<i class="fas fa-shield-alt mr-1"></i>',
                                                        'neighbor' => '<i class="fas fa-users mr-1"></i>',
                                                        'noise' => '<i class="fas fa-volume-up mr-1"></i>',
                                                        'maintenance' => '<i class="fas fa-wrench mr-1"></i>',
                                                        'billing' => '<i class="fas fa-file-invoice-dollar mr-1"></i>',
                                                        'amenities' => '<i class="fas fa-swimming-pool mr-1"></i>',
                                                        default => '<i class="fas fa-question-circle mr-1"></i>'
                                                    };
                                                    echo $categoryIcon . ucfirst($feedback['concern_category']);
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-[200px] truncate" title="<?php echo htmlspecialchars($feedback['concern_details']); ?>">
                                                    <?php echo htmlspecialchars(substr($feedback['concern_details'], 0, 50) . (strlen($feedback['concern_details']) > 50 ? '...' : '')); ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    <?php 
                                                    switch($feedback['concern_status']) {
                                                        case 'LOW': echo 'bg-green-100 text-green-800'; break;
                                                        case 'MEDIUM': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'HIGH': echo 'bg-orange-100 text-orange-800'; break;
                                                        case 'URGENT': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo $feedback['concern_status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full inline-flex items-center
                                                    <?php 
                                                    $statusColor = match($feedback['status']) {
                                                        'Pending' => 'bg-yellow-100 text-yellow-800',
                                                        'Open' => 'bg-blue-100 text-blue-800',
                                                        'In Progress' => 'bg-blue-100 text-blue-800',
                                                        'Resolved' => 'bg-green-100 text-green-800',
                                                        'Completed' => 'bg-green-100 text-green-800',
                                                        'Closed' => 'bg-gray-100 text-gray-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                    
                                                    $statusIcon = match($feedback['status']) {
                                                        'Pending' => '<i class="fas fa-clock mr-1"></i>',
                                                        'Open' => '<i class="fas fa-envelope-open mr-1"></i>',
                                                        'In Progress' => '<i class="fas fa-spinner mr-1"></i>',
                                                        'Resolved' => '<i class="fas fa-check-circle mr-1"></i>',
                                                        'Completed' => '<i class="fas fa-check-double mr-1"></i>',
                                                        'Closed' => '<i class="fas fa-lock mr-1"></i>',
                                                        default => '<i class="fas fa-question-circle mr-1"></i>'
                                                    };
                                                    echo $statusColor;
                                                    ?>">
                                                    <?php echo $statusIcon . $feedback['status']; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <?php if (!empty($feedback['security_response'])): ?>
                                                    <button onclick="showResponseModal('<?php echo htmlspecialchars(addslashes($feedback['security_response'])); ?>')" 
                                                        class="text-sm text-yellow-600 hover:text-yellow-800 hover:underline focus:outline-none">
                                                        View Response
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-sm text-gray-400 italic">Awaiting response</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-4">
                                                <?php if (!empty($feedback['concern_media'])): ?>
                                                    <button onclick="showImageModal('<?php echo htmlspecialchars($feedback['concern_media']); ?>')" 
                                                        class="text-yellow-600 hover:text-yellow-800 focus:outline-none">
                                                        <i class="fas fa-image"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 px-6 mt-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">© 2023 SWARM Community Portal. All rights reserved.</p>
                <div class="mt-3 md:mt-0 flex items-center space-x-4">
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Terms of Service</a>
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Privacy Policy</a>
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Contact</a>
                </div>
            </div>
        </footer>
    </div>

<script>
// Variables
let isSpinning = false;
let currentOpenMenu = null;

// Media Preview Function
function previewMedia(input) {
    const previewContainer = document.getElementById('media_preview_container');
    const uploadIcon = document.getElementById('uploadIcon');
    const uploadText = document.getElementById('uploadText');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Create image element
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = "max-h-full max-w-full rounded-lg object-contain";
            
            // Clear container and append new image
            previewContainer.innerHTML = '';
            previewContainer.appendChild(img);
            
            // Hide upload icon and text
            uploadIcon.classList.add('hidden');
            uploadText.classList.add('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Concern Status Selection
function selectStatus(id) {
    // The peer classes in Tailwind will handle the styling automatically
    // Just need to check the selected radio button
    document.getElementById(id).checked = true;
}

// Show confirmation modal
function showConfirmationModal() {
    // Validate the form first
    const form = document.getElementById('concernForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form values to display in confirmation
    const concernType = document.getElementById('concern_type');
    const selectedType = concernType.options[concernType.selectedIndex].text;
    
    const concernStatus = document.querySelector('input[name="concern_status"]:checked');
    if (!concernStatus) {
        alert("Please select a concern priority level");
        return;
    }
    
    // Update modal content
    document.getElementById('confirmCategory').textContent = selectedType;
    document.getElementById('confirmPriority').textContent = concernStatus.value;
    
    // Show modal
    const modal = document.getElementById('confirmationModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function submitForm() {
    // Add the submit_concern parameter before submitting
    const form = document.getElementById('concernForm');
    
    // Create hidden input for submit_concern if it doesn't exist
    if (!document.getElementById('submit_concern_input')) {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'submit_concern';
        hiddenInput.value = '1';
        hiddenInput.id = 'submit_concern_input';
        form.appendChild(hiddenInput);
    }
    
    // Submit the form to Insert-OwnerCommunityfeedback.php
    form.submit();
}

function showTab(tab) {
    const submitContent = document.getElementById('submitContent');
    const requestsContent = document.getElementById('requestsContent');
    const submitTab = document.getElementById('submitTab');
    const requestsTab = document.getElementById('requestsTab');
    
    if (tab === 'submit') {
        submitContent.classList.remove('hidden');
        requestsContent.classList.add('hidden');
        submitTab.classList.add('tab-active');
        requestsTab.classList.remove('tab-active');
    } else {
        submitContent.classList.add('hidden');
        requestsContent.classList.remove('hidden');
        submitTab.classList.remove('tab-active');
        requestsTab.classList.add('tab-active');
    }
}

// Modal functions for response and image viewing
function showResponseModal(response) {
    const modal = document.getElementById('responseModal');
    const responseText = document.getElementById('responseText');
    
    // Set the response text
    responseText.textContent = response;
    
    // Show the modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeResponseModal() {
    const modal = document.getElementById('responseModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function showImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    // Set the image source
    modalImage.src = imageSrc;
    
    // Show the modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Mobile sidebar controls
document.addEventListener('DOMContentLoaded', function() {
    const openSidebarBtn = document.getElementById('openSidebar');
    const closeSidebarBtn = document.getElementById('closeSidebar');
    const sidebar = document.getElementById('sidebar');

    if (openSidebarBtn) {
        openSidebarBtn.addEventListener('click', function() {
            sidebar.classList.remove('-translate-x-full');
        });
    }

    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', function() {
            sidebar.classList.add('-translate-x-full');
        });
    }
});

// Initialize help modal function
function openHelpModal() {
    document.getElementById('helpModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeHelpModal() {
    document.getElementById('helpModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('helpModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeHelpModal();
    }
});
</script>

<!-- Help & Support Modal -->
<div id="helpModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-lg max-w-lg w-full">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b">
                <div class="flex items-center space-x-2">
                    <div class="p-2 bg-primary-100 rounded-full">
                        <i class='bx bx-help-circle text-xl text-primary-700'></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Help & Support</h3>
                </div>
                <button onclick="closeHelpModal()" class="text-gray-500 hover:text-gray-700">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6 space-y-6">
                <!-- Contact Section -->
                <div class="space-y-4">
                    <h4 class="text-base font-medium text-gray-800">Contact Information</h4>
                    <div class="space-y-3">
                        <a href="tel:+639394620569" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <div class="p-2 bg-green-100 rounded-full">
                                <i class='bx bx-phone text-green-600'></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-800">Phone Support</p>
                                <p class="text-xs text-gray-500">0939-462-0569</p>
                            </div>
                        </a>
                        <a href="mailto:support@swarm.com" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <div class="p-2 bg-blue-100 rounded-full">
                                <i class='bx bx-envelope text-blue-600'></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-800">Email Support</p>
                                <p class="text-xs text-gray-500">support@swarm.com</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="space-y-4">
                    <h4 class="text-base font-medium text-gray-800">Frequently Asked Questions</h4>
                    <div class="space-y-3">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-800">How do I update my profile?</p>
                            <p class="text-xs text-gray-600 mt-1">Click on the "Edit Profile" link under your profile section in the sidebar.</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-800">What if I forget my password?</p>
                            <p class="text-xs text-gray-600 mt-1">Use the "Forgot Password" link on the login page to reset your password.</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm font-medium text-gray-800">How long does verification take?</p>
                            <p class="text-xs text-gray-600 mt-1">Account verification typically takes 1-2 business days.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t p-4 flex justify-end">
                <button onclick="closeHelpModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>