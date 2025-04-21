<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
include('dbconn.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if username is set in the session
if (!isset($_SESSION['username'])) {
    die("Username not found in session. Please log in.");
}

$username = $_SESSION['username'];

// Retrieve Owner_ID, Tower, Unit_Number, and Status from ownerinformation
$sql = "SELECT Owner_ID, Tower, Unit_Number, Status FROM ownerinformation WHERE Username = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Owner information not found for the logged-in user.");
}

$row = $result->fetch_assoc();
$ownerID = $row['Owner_ID'];
$tower = $row['Tower'];
$unitNumber = $row['Unit_Number'];
$status = $row['Status']; // ✅ Ensure status is assigned

// Retrieve Owner_ID and Tower/Unit_Number from ownerinformation
$sql = "SELECT Owner_ID, Tower, Unit_Number, Status FROM ownerinformation WHERE Username = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Owner information not found for the logged-in user.");
}

$row = $result->fetch_assoc();
$ownerID = $row['Owner_ID'];
$tower = $row['Tower'];
$unitNumber = $row['Unit_Number'];
$stmt->close();

// Retrieve Owner_ID from ownerinformation
$sql_owner = "SELECT Owner_ID FROM ownerinformation WHERE Username = ?";
$stmt_owner = $conn->prepare($sql_owner);
if (!$stmt_owner) die("Prepare failed: " . $conn->error);

$stmt_owner->bind_param("s", $username);
$stmt_owner->execute();
$result_owner = $stmt_owner->get_result();

if ($result_owner->num_rows === 0) {
    die("Owner information not found for the logged-in user.");
}

$row_owner = $result_owner->fetch_assoc();
$ownerID = $row_owner['Owner_ID'];
$stmt_owner->close();

// Retrieve tenants linked to this owner
$sql_tenant = "SELECT CONCAT(First_Name, ' ', Last_Name) AS Full_Name FROM tenantinformation WHERE Owner_ID = ?";
$stmt_tenant = $conn->prepare($sql_tenant);
if (!$stmt_tenant) die("Prepare failed (tenant): " . $conn->error);

$stmt_tenant->bind_param("s", $ownerID);
$stmt_tenant->execute();
$result_tenant = $stmt_tenant->get_result();

$tenant_names = [];
while ($row_tenant = $result_tenant->fetch_assoc()) {
    $tenant_names[] = $row_tenant['Full_Name'];
}
$stmt_tenant->close();

$noTenantTransactions = empty($tenant_names);

// Tables to query
$tables_to_display = [
    'ownertenantreservation' => ['user_type', 'user_email', 'amenity', 'reservation_date', 'reservation_time', 'Status'],
    'workpermit' => ['user_type', 'user_email', 'work_type', 'authorize_rep', 'period_from', 'period_to', 'Status'],
    'ownertenantvisitor' => ['user_type', 'start_date', 'end_date', 'guest_info', 'Status'],
    'ownertenantmovein' => ['Resident_Name', 'Resident_Contact', 'parkingSlotNumber', 'representativeName', 'Status'],
    'ownertenantmoveout' => ['Resident_Name', 'Resident_Contact', 'parkingSlotNumber', 'representativeName', 'Status'],
    'guestcheckinout' => ['user_type', 'Checkin_Date', 'Checkout_Date', 'Guest_Info', 'Status'],
    'gatepass' => ['Ticket_No', 'user_type', 'Authorization', 'Items', 'Status'],
    'poolreserve' => ['user_type', 'names', 'towerunitnum', 'schedule', 'Status']
];

$records = [];

foreach ($tables_to_display as $table_name => $columns) {
    $conditions = [];
    $params = [];
    $types = '';

    // Filter by user_type if applicable
    if (in_array('user_type', $columns)) {
        $conditions[] = "user_type = ?";
        $params[] = 'Tenant';
        $types .= 's';
    }

    // Ensure Resident_Name matches the tenant's Full_Name
    if (in_array('Resident_Name', $columns) && !empty($tenant_names)) {
        $placeholders = implode(',', array_fill(0, count($tenant_names), '?'));
        $conditions[] = "Resident_Name IN ($placeholders)";
        $params = array_merge($params, $tenant_names);
        $types .= str_repeat('s', count($tenant_names));
    }

    // Skip if no conditions are set
    if (empty($conditions)) continue;

    $sql = "SELECT " . implode(', ', $columns) . " FROM `" . strtolower(str_replace(' ', '', $table_name)) . "` WHERE " . implode(' OR ', $conditions);
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Error preparing statement for $table_name: " . $conn->error . "<br>";
        continue;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $records[$table_name] = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Tenant Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#fdf9e7',
                            100: '#faf3d0',
                            200: '#f5e7a1',
                            300: '#f0db72',
                            400: '#ebcf43',
                            500: '#e9be3a',
                            600: '#d4a813',
                            700: '#e9be3a', // Main yellow
                            800: '#d4a813',
                            900: '#a8850f',
                        },
                        accent: '#e9be3a',
                        dark: '#333333',
                        light: '#F5F5F5'
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(233, 190, 58, 0.1), 0 2px 4px -1px rgba(233, 190, 58, 0.06)',
                        'card-hover': '0 10px 15px -3px rgba(233, 190, 58, 0.1), 0 4px 6px -2px rgba(233, 190, 58, 0.05)',
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

        /* Keep existing tenant status specific styles */
        .navbar {
            @apply flex justify-center gap-4 mb-6;
        }

        .nav-link {
            @apply bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-3 rounded-full 
                   font-semibold transition-all duration-300 hover:from-primary-700 hover:to-primary-800 
                   shadow-md hover:shadow-lg transform hover:-translate-y-0.5;
        }

        .filter-section {
            @apply flex flex-wrap gap-4 items-center mb-6 bg-white p-4 rounded-xl shadow-card;
        }

        .filter-section label {
            @apply text-sm font-medium text-gray-700;
        }

        .filter-section input[type="text"],
        .filter-section select {
            @apply w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md focus:ring-2 
                   focus:ring-primary-500 focus:border-primary-500 text-sm;
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
                <a href="OwnerAnnouncement.php" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bxs-bell-ring text-xl mr-3 text-gray-700'></i>
                    <span class="font-medium">Announcements</span>
                </a>
                
                <!-- Services -->
                <a href="<?php echo ($status == 'Approved') ? 'OwnerServices.php' : '#'; ?>" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-wrench text-xl mr-3'></i>
                    <span>Services</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Payment Info -->
                <a href="<?php echo ($status == 'Approved') ? 'OwnerPaymentinfo.php' : '#'; ?>" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-credit-card text-xl mr-3'></i>
                    <span>Payment Info</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Tenant Status -->
                <a href="<?php echo ($status == 'Approved') ? 'OwnerTenantFormStatus.php' : '#'; ?>" 
                   class="flex items-center px-6 py-3 text-[#e9be3a] bg-primary-50 border-r-4 border-[#e9be3a]">
                    <i class='bx bx-user-check text-xl mr-3'></i>
                    <span>Tenant Status</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Safety Guidelines -->
                <a href="OwnerSafetyguidelines.php" 
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-shield-quarter text-xl mr-3'></i>
                    <span>Safety Guidelines</span>
                </a>
                
                <!-- Community Feedback -->
                <a href="OwnerCommunityfeedback.php" 
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
                        <h1 class="text-2xl font-bold text-gray-900">Tenant Status</h1>
                        <div class="flex items-center mt-2">
                            <i class='bx bx-calendar text-primary-700 mr-2'></i>
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

            <!-- Navigation Tabs -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2 md:gap-4">
                    <a href="OwnerTenantFormStatus.php" 
                       class="flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg shadow-sm hover:bg-primary-700 transition-colors">
                        <i class='bx bx-file mr-2'></i>
                        <span>Form Status</span>
                    </a>
                    <a href="OwnerPaymentStatus.php" 
                       class="flex items-center px-6 py-3 bg-white text-gray-700 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <i class='bx bx-money mr-2'></i>
                        <span>Payment Status</span>
                    </a>
                    <a href="OwnerAddTenant.php" 
                       class="flex items-center px-6 py-3 bg-white text-gray-700 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <i class='bx bx-user-plus mr-2'></i>
                        <span>Add Tenant Request</span>
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl shadow-card p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search Filter -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" id="search" 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                   placeholder="Search by unit or name">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class='bx bx-search text-gray-400'></i>
                            </div>
                        </div>
                    </div>

                    <!-- Request Type Filter -->
                    <div>
                        <label for="requestType" class="block text-sm font-medium text-gray-700 mb-1">Request Type</label>
                        <select id="requestType" onchange="filterTable()" 
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Requests</option>
                            <?php foreach ($tables_to_display as $table => $columns): ?>
                                <option value="<?= strtolower($table) ?>"><?= ucfirst(str_replace(['ownertenant', 'tenant'], '', $table)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" onchange="filterTable()" 
                                class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Disapproved">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Records Section -->
            <?php if (!$noTenantTransactions): ?>
                <?php foreach ($records as $table_name => $rows): ?>
                    <div class="bg-white rounded-xl shadow-card mb-6 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-800">
                                <?= ucfirst(str_replace(['ownertenant', 'tenant'], '', $table_name)) ?> Records
                            </h2>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <?php foreach ($tables_to_display[$table_name] as $column): ?>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <?= ucfirst(str_replace('_', ' ', $column)) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($rows as $row): ?>
                                        <tr data-request-type="<?= strtolower($table_name) ?>" 
                                            data-status="<?= strtolower($row['Status'] ?? '') ?>"
                                            class="hover:bg-gray-50 transition-colors">
                                            <?php foreach ($tables_to_display[$table_name] as $column): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                    <?php if ($column === 'Status'): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            <?php
                                                            $status = strtolower($row[$column] ?? '');
                                                            echo match($status) {
                                                                'approved' => 'bg-green-100 text-green-800',
                                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                                'disapproved' => 'bg-red-100 text-red-800',
                                                                default => 'bg-gray-100 text-gray-800'
                                                            };
                                                            ?>">
                                                            <?= htmlspecialchars($row[$column] ?? 'N/A') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($row[$column] ?? 'N/A') ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-card p-8 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class='bx bx-file-blank text-3xl text-gray-400'></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-800 mb-2">No Tenant Transactions</h3>
                    <p class="text-gray-500 max-w-sm mx-auto">There are currently no tenant transactions to display.</p>
                </div>
            <?php endif; ?>
        </main>

        <!-- Footer -->
    </div>

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

    <!-- Add help modal functions to existing script -->
    <script>
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

        // Mobile sidebar controls
        document.getElementById('openSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });

        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });

        function filterTable() {
            const search = document.getElementById("search").value.toLowerCase();
            const requestType = document.getElementById("requestType").value.toLowerCase();
            const status = document.getElementById("status").value.toLowerCase();
            const rows = document.querySelectorAll("table tbody tr");

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowRequestType = row.dataset.requestType;
                const rowStatus = row.dataset.status;

                const matchesSearch = text.includes(search);
                const matchesRequestType = !requestType || rowRequestType === requestType;
                const matchesStatus = !status || rowStatus === status;

                row.style.display = (matchesSearch && matchesRequestType && matchesStatus) ? "" : "none";
            });

            // Update empty state visibility
            document.querySelectorAll('table').forEach(table => {
                const visibleRows = table.querySelectorAll('tbody tr[style=""]').length;
                const tableCard = table.closest('.bg-white');
                if (tableCard) {
                    if (visibleRows === 0) {
                        tableCard.classList.add('hidden');
                    } else {
                        tableCard.classList.remove('hidden');
                    }
                }
            });
        }

        // Add event listener for search input
        document.getElementById('search').addEventListener('keyup', filterTable);
    </script>
</body>
</html>
