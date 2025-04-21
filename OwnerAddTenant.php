<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
include('dbconn.php');
// Include email notification functionality
include('email-OwnerAddTenant.php');

// Check if username is set in the session
if (!isset($_SESSION['username'])) {
    die("Username not found in session. Please log in.");
}

$username = $_SESSION['username'];
$message = '';
$messageType = '';

// Retrieve Owner_ID and primary key ID from ownerinformation
$sql = "SELECT ID, Owner_ID, Tower, Unit_Number, Status FROM ownerinformation WHERE Username = ?";
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

$ownerData = $result->fetch_assoc();
$ownerID = $ownerData['Owner_ID'];
$ownerPrimaryID = $ownerData['ID']; // This is the actual primary key ID needed for foreign key relationships
$status = $ownerData['Status'];
$stmt->close();

// Handle assigning tenant to unit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_tenant'])) {
    $unitId = $_POST['unit_id'];
    $tenantEmail = $_POST['tenant_email'];
    
    // Check if tenant exists with the provided email
    $checkTenantSql = "SELECT ID, Status FROM tenantinformation WHERE Email = ? LIMIT 1";
    $checkTenantStmt = $conn->prepare($checkTenantSql);
    $checkTenantStmt->bind_param("s", $tenantEmail);
    $checkTenantStmt->execute();
    $tenantResult = $checkTenantStmt->get_result();
    
    if ($tenantResult->num_rows > 0) {
        // Tenant exists, get their ID
        $tenantData = $tenantResult->fetch_assoc();
        $tenantId = $tenantData['ID'];
        
        // Update status to "For Approval" if it's not already approved
        if ($tenantData['Status'] != 'Approved') {
            $updateStatusSql = "UPDATE tenantinformation SET Status = 'For Approval' WHERE ID = ?";
            $updateStatusStmt = $conn->prepare($updateStatusSql);
            $updateStatusStmt->bind_param("i", $tenantId);
            $updateStatusStmt->execute();
            $updateStatusStmt->close();
        }
        
        // Update the unit with the tenant ID
        $updateUnitSql = "UPDATE ownerunits SET tenant_id = ?, invited_email = ?, status = 'For Approval' WHERE id = ? AND owner_id = ?";
        $updateUnitStmt = $conn->prepare($updateUnitSql);
        $updateUnitStmt->bind_param("isii", $tenantId, $tenantEmail, $unitId, $ownerPrimaryID);
    } else {
        // No tenant found with this email - but we'll still proceed
        // Update the unit with the invited email address only
        $updateUnitSql = "UPDATE ownerunits SET tenant_id = NULL, invited_email = ?, status = 'For Approval' WHERE id = ? AND owner_id = ?";
        $updateUnitStmt = $conn->prepare($updateUnitSql);
        $updateUnitStmt->bind_param("sii", $tenantEmail, $unitId, $ownerPrimaryID);
    }
    $checkTenantStmt->close();
    
    // Execute the update statement
    if ($updateUnitStmt->execute()) {
        // Get unit details for the email notification
        $unitDetailsSql = "SELECT tower, unit_num FROM ownerunits WHERE id = ?";
        $unitDetailsStmt = $conn->prepare($unitDetailsSql);
        $unitDetailsStmt->bind_param("i", $unitId);
        $unitDetailsStmt->execute();
        $unitDetailsResult = $unitDetailsStmt->get_result();
        $unitDetails = $unitDetailsResult->fetch_assoc();
        $unitDetailsStmt->close();
        
        // Get owner details
        $ownerDetailsSql = "SELECT First_Name, Last_Name, Email FROM ownerinformation WHERE ID = ?";
        $ownerDetailsStmt = $conn->prepare($ownerDetailsSql);
        $ownerDetailsStmt->bind_param("i", $ownerPrimaryID);
        $ownerDetailsStmt->execute();
        $ownerDetailsResult = $ownerDetailsStmt->get_result();
        $ownerDetails = $ownerDetailsResult->fetch_assoc();
        $ownerDetailsStmt->close();
        
        // Prepare owner info for email
        $ownerInfo = [
            'name' => $ownerDetails['First_Name'] . ' ' . $ownerDetails['Last_Name'],
            'email' => $ownerDetails['Email']
        ];
        
        // Send email notification to tenant
        $emailResult = sendTenantAssignmentEmail($tenantEmail, $ownerInfo, $unitDetails);
        
        $message = "Tenant email successfully assigned to the unit!";
        if ($emailResult['status'] === 'success') {
            $message .= " An invitation email has been sent to {$tenantEmail} and the unit status has been set to 'For Approval'.";
        } else {
            $message .= " However, there was an issue sending the email invitation: " . $emailResult['message'];
        }
        $messageType = "success";
    } else {
        $message = "Error assigning tenant: " . $conn->error;
        $messageType = "error";
    }
    $updateUnitStmt->close();
}

// Handle removing tenant from unit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_tenant'])) {
    $unitId = $_POST['unit_id'];
    
    // Update the unit to remove tenant and clear invited_email
    $updateUnitSql = "UPDATE ownerunits SET tenant_id = NULL, invited_email = NULL, status = 'Vacant' WHERE id = ? AND owner_id = ?";
    $updateUnitStmt = $conn->prepare($updateUnitSql);
    $updateUnitStmt->bind_param("ii", $unitId, $ownerPrimaryID);
    
    if ($updateUnitStmt->execute()) {
        $message = "Tenant successfully removed from the unit!";
        $messageType = "success";
    } else {
        $message = "Error removing tenant: " . $conn->error;
        $messageType = "error";
    }
    $updateUnitStmt->close();
}

// Handle approving tenant for unit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['approve_tenant'])) {
    $unitId = $_POST['unit_id'];
    $tenantId = $_POST['tenant_id'];
    
    // Update the unit status to Taken and confirm tenant
    $updateUnitSql = "UPDATE ownerunits SET status = 'Taken', tenant_id = ? WHERE id = ? AND owner_id = ?";
    $updateUnitStmt = $conn->prepare($updateUnitSql);
    $updateUnitStmt->bind_param("iii", $tenantId, $unitId, $ownerPrimaryID);
    
    if ($updateUnitStmt->execute()) {
        $message = "Tenant has been approved and assigned to the unit!";
        $messageType = "success";
    } else {
        $message = "Error approving tenant: " . $conn->error;
        $messageType = "error";
    }
    $updateUnitStmt->close();
}

// Fetch all units owned by this owner with tenant information
// Fix the collation mismatch in the join condition
$unitsQuery = "SELECT 
               ou.id, ou.tower, ou.unit_num, ou.status AS unit_status, ou.created_at, ou.invited_email, 
               ou.tenant_id AS unit_tenant_id,
               t.ID AS tenant_id, t.First_Name, t.Last_Name, t.Email, t.Mobile_Number, t.Status AS tenant_status
               FROM ownerunits ou
               LEFT JOIN tenantinformation t ON ou.tenant_id = t.ID OR 
                                               (ou.invited_email COLLATE utf8mb4_unicode_ci = t.Email COLLATE utf8mb4_unicode_ci AND ou.tenant_id IS NULL)
               WHERE ou.owner_id = ?
               ORDER BY ou.tower, ou.unit_num";

$unitsStmt = $conn->prepare($unitsQuery);
$unitsStmt->bind_param("i", $ownerPrimaryID);
$unitsStmt->execute();
$unitsResult = $unitsStmt->get_result();

$units = [];
while ($unitRow = $unitsResult->fetch_assoc()) {
    // If tenant_id is NULL but we matched by email, use the joined tenant_id
    if ($unitRow['unit_tenant_id'] === NULL && $unitRow['tenant_id'] !== NULL) {
        $unitRow['tenant_id'] = $unitRow['tenant_id'];
        
        // Update the ownerunits table to set the tenant_id for future reference
        $updateQuery = "UPDATE ownerunits SET tenant_id = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $unitRow['tenant_id'], $unitRow['id']);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Use the original tenant_id if it exists
        $unitRow['tenant_id'] = $unitRow['unit_tenant_id'];
    }
    
    // If we still don't have tenant information but we have an invited email, look it up
    if (($unitRow['tenant_status'] === NULL || $unitRow['tenant_id'] === NULL) && !empty($unitRow['invited_email'])) {
        $tenantQuery = "SELECT ID, First_Name, Last_Name, Email, Mobile_Number, Status FROM tenantinformation WHERE Email = ? LIMIT 1";
        $tenantStmt = $conn->prepare($tenantQuery);
        $tenantStmt->bind_param("s", $unitRow['invited_email']);
        $tenantStmt->execute();
        $tenantResult = $tenantStmt->get_result();
        
        if ($tenantResult->num_rows > 0) {
            $tenantData = $tenantResult->fetch_assoc();
            $unitRow['tenant_id'] = $tenantData['ID'];
            $unitRow['First_Name'] = $tenantData['First_Name'];
            $unitRow['Last_Name'] = $tenantData['Last_Name'];
            $unitRow['Email'] = $tenantData['Email'];
            $unitRow['Mobile_Number'] = $tenantData['Mobile_Number'];
            $unitRow['tenant_status'] = $tenantData['Status'];
        }
        $tenantStmt->close();
    }
    
    // Make sure we have a status property for backwards compatibility
    $unitRow['status'] = $unitRow['unit_status'];
    
    // Clean up redundant fields
    unset($unitRow['unit_tenant_id']);
    
    $units[] = $unitRow;
}
$unitsStmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Manage Units</title>
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
                            50: '#f9f5f1',
                            100: '#f0e6db',
                            200: '#e2cfba',
                            300: '#d1b392',
                            400: '#c09169',
                            500: '#b07a4f',
                            600: '#a26542',
                            700: '#8b4513',
                            800: '#723d18',
                            900: '#5c3518',
                        },
                        accent: '#CD853F',
                        dark: '#333333',
                        light: '#F5F5F5'
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(139, 69, 19, 0.1), 0 2px 4px -1px rgba(139, 69, 19, 0.06)',
                        'card-hover': '0 10px 15px -3px rgba(139, 69, 19, 0.1), 0 4px 6px -2px rgba(139, 69, 19, 0.05)',
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
            background: #c9af98;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #8b4513;
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
                <div class="bg-primary-700 text-white p-2 rounded-lg">
                    <i class='bx bx-building-house text-xl'></i>
                </div>
                <span class="ml-3 text-lg font-bold text-gray-800">SWARM Portal</span>
            </div>
            <button class="lg:hidden text-gray-500 hover:text-primary-700 focus:outline-none" id="closeSidebar">
                <i class='bx bx-x text-2xl'></i>
            </button>
        </div>
        
        <!-- User profile -->
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold">
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
                <a href="edit-profile.php" class="text-xs text-primary-700 hover:underline flex items-center">
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
                    <i class='bx bx-megaphone text-xl mr-3'></i>
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
                   class="flex items-center px-6 py-3 text-primary-700 bg-primary-50 border-r-4 border-primary-700">
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
            
            <div class="mt-6 px-6">
                <a href="#" class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-lg transition-all">
                    <i class='bx bx-help-circle mr-2'></i>
                    <span>Help & Support</span>
                </a>
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
                        <h1 class="text-2xl font-bold text-gray-900">Manage Units & Tenants</h1>
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
                       class="flex items-center px-6 py-3 bg-white text-gray-700 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <i class='bx bx-file mr-2'></i>
                        <span>Form Status</span>
                    </a>
                    <a href="OwnerPaymentStatus.php" 
                       class="flex items-center px-6 py-3 bg-white text-gray-700 rounded-lg shadow-sm hover:bg-gray-50 transition-colors">
                        <i class='bx bx-money mr-2'></i>
                        <span>Payment Status</span>
                    </a>
                    <a href="OwnerAddTenant.php" 
                       class="flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg shadow-sm hover:bg-primary-700 transition-colors">
                        <i class='bx bx-user-plus mr-2'></i>
                        <span>Manage Units</span>
                    </a>
                </div>
            </div>

            <!-- Alert Message -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 px-4 py-3 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> flex items-center">
                    <i class="bx <?php echo $messageType === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?> text-xl mr-2"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Units Management Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($units) > 0): ?>
                    <?php foreach ($units as $unit): ?>
                        <div class="bg-white rounded-xl shadow-card overflow-hidden hover:shadow-card-hover transition-shadow duration-300 relative">
                            <!-- Unit Header -->
                            <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 text-white">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-semibold">Tower <?php echo $unit['tower']; ?>, Unit <?php echo $unit['unit_num']; ?></h3>
                                    <span class="text-xs px-2 py-1 rounded-full 
                                        <?php if ($unit['status'] === 'Vacant'): ?>
                                            bg-green-200 text-green-800
                                        <?php elseif ($unit['status'] === 'For Approval'): ?>
                                            bg-yellow-200 text-yellow-800 font-medium
                                        <?php else: ?>
                                            bg-blue-200 text-blue-800
                                        <?php endif; ?>">
                                        <?php echo $unit['status']; ?>
                                    </span>
                                </div>
                                <p class="text-xs mt-1 text-primary-100">Added on <?php echo date('M j, Y', strtotime($unit['created_at'])); ?></p>
                            </div>
                            
                            <!-- Unit Content -->
                            <div class="px-6 py-4">
                                <?php if ($unit['tenant_id'] || $unit['status'] === 'For Approval'): ?>
                                    <!-- Tenant Information if assigned -->
                                    <div class="mb-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                                            <i class='bx bx-user mr-2 text-primary-500'></i> 
                                            <?php if ($unit['status'] === 'For Approval'): ?>
                                                Pending Tenant
                                            <?php else: ?>
                                                Current Tenant
                                            <?php endif; ?>
                                        </h4>
                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100
                                            <?php if ($unit['status'] === 'For Approval'): ?> border-l-4 border-l-yellow-500 <?php endif; ?>">
                                            <?php if ($unit['First_Name'] && $unit['Last_Name']): ?>
                                                <p class="font-medium text-gray-800"><?php echo $unit['First_Name'] . ' ' . $unit['Last_Name']; ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600 mt-1 flex items-center">
                                                <i class='bx bx-envelope text-gray-400 mr-1'></i>
                                                <?php echo $unit['Email'] ?? $unit['invited_email']; ?>
                                            </p>
                                            <?php if ($unit['Mobile_Number']): ?>
                                            <p class="text-sm text-gray-600 mt-1 flex items-center">
                                                <i class='bx bx-phone text-gray-400 mr-1'></i>
                                                <?php echo $unit['Mobile_Number']; ?>
                                            </p>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            // Improved debug display with clearer information
                                            /*
                                            echo '<div class="p-2 mt-2 text-xs bg-gray-100 roundeds">';
                                            echo '<p><span class="font-medium">Unit Status:</span> <span class="px-1 py-0.5 rounded bg-' 
                                                . ($unit['status'] === 'For Approval' ? 'yellow' : ($unit['status'] === 'Vacant' ? 'green' : 'blue')) 
                                                . '-100">' . htmlspecialchars($unit['status']) . '</span></p>';

                                            echo '<p class="mt-1"><span class="font-medium">Tenant Status:</span> ';
                                            if (isset($unit['tenant_status']) && $unit['tenant_status'] !== NULL) {
                                                $statusColor = 'gray';
                                                if ($unit['tenant_status'] === 'Approved') $statusColor = 'green';
                                                elseif ($unit['tenant_status'] === 'For Approval') $statusColor = 'yellow';
                                                elseif ($unit['tenant_status'] === 'Pending') $statusColor = 'orange';
                                                
                                                echo '<span class="px-1 py-0.5 rounded bg-'.$statusColor.'-100">' 
                                                    . htmlspecialchars($unit['tenant_status']) . '</span>';
                                            } else {
                                                echo '<span class="text-gray-500">Not set</span>';
                                            }
                                            echo '</p>';

                                            echo '<p class="mt-1"><span class="font-medium">Tenant ID:</span> ' 
                                                . (isset($unit['tenant_id']) && $unit['tenant_id'] !== NULL ? htmlspecialchars($unit['tenant_id']) : '<span class="text-gray-500">Not set</span>') . '</p>';

                                            echo '<p class="mt-1"><span class="font-medium">Email:</span> ' 
                                                . (isset($unit['invited_email']) ? htmlspecialchars($unit['invited_email']) : '<span class="text-gray-500">Not set</span>') . '</p>';
                                            echo '</div>';
                                            */
                                            // Use invited_email to check tenant status directly from database
                                            $isUnitForApproval = trim($unit['status']) === 'For Approval';
                                            $isTenantApproved = isset($unit['tenant_status']) && 
                                                                strtolower(trim($unit['tenant_status'])) === 'approved'; 

                                            if ($isUnitForApproval && $isTenantApproved): 
                                            ?>
                                                <div class="mt-3 pt-3 border-t border-gray-200">
                                                    <p class="text-sm text-green-700 flex items-center font-medium">
                                                        <i class='bx bx-check-circle mr-1'></i>
                                                        This tenant has been approved by admin. Accept this tenant?
                                                    </p>
                                                    
                                                    <div class="mt-3 flex space-x-2">
                                                        <form method="post" action="" class="flex-1">
                                                            <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                                            <input type="hidden" name="tenant_id" value="<?php echo $unit['tenant_id']; ?>">
                                                            <button type="submit" name="approve_tenant" class="w-full px-3 py-1.5 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors flex items-center justify-center">
                                                                <i class='bx bx-check mr-1'></i> Yes, Accept
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="post" action="" class="flex-1">
                                                            <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                                            <button type="submit" name="remove_tenant" class="w-full px-3 py-1.5 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors flex items-center justify-center">
                                                                <i class='bx bx-x mr-1'></i> No, Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                    
                                                    <p class="text-xs text-gray-500 mt-2">
                                                        <i class='bx bx-info-circle'></i> Accepting will mark this unit as "Taken" and assign the tenant permanently.
                                                    </p>
                                                </div>
                                            <?php 
                                                // Show waiting message only when not showing the approval buttons
                                                elseif ($unit['status'] === 'For Approval' && (!isset($unit['tenant_status']) || $unit['tenant_status'] !== 'Approved')):
                                            ?>
                                                <div class="mt-3 pt-3 border-t border-gray-200">
                                                    <p class="text-xs text-yellow-700 flex items-center">
                                                        <i class='bx bx-info-circle mr-1'></i>
                                                        Invitation email sent. Waiting for tenant registration and admin approval.
                                                    </p>
                                                </div>
                                            <?php
                                                endif;
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Show Remove tenant button only if not presenting approval options -->
                                    <?php 
                                    $showRemoveButton = true;
                                    
                                    if ($unit['status'] === 'For Approval' && isset($unit['tenant_status']) && $unit['tenant_status'] === 'Approved') {
                                        $showRemoveButton = false;
                                    }
                                    
                                    if ($showRemoveButton):
                                    ?>
                                    <!-- Remove tenant form -->
                                    <form method="post" action="" class="mt-3">
                                        <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                        <button type="submit" name="remove_tenant" class="w-full px-4 py-2 bg-red-50 text-red-600 rounded-md border border-red-100 hover:bg-red-100 transition-colors flex items-center justify-center">
                                            <i class='bx bx-user-x mr-2'></i> 
                                            <?php echo ($unit['status'] === 'For Approval') ? 'Cancel Invitation' : 'Remove Tenant'; ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Assign tenant form -->
                                    <form method="post" action="" class="space-y-3" id="assignTenantForm_<?php echo $unit['id']; ?>">
                                        <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                                        
                                        <div>
                                            <label for="tenant_email_<?php echo $unit['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                                Tenant Email Address
                                            </label>
                                            <div class="relative">
                                                <input type="email" id="tenant_email_<?php echo $unit['id']; ?>" name="tenant_email" 
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                                       placeholder="Enter tenant's email" required>
                                                <div id="email_status_<?php echo $unit['id']; ?>" class="absolute right-3 top-2 hidden">
                                                    <i class='bx bx-loader-alt animate-spin text-primary-600'></i>
                                                </div>
                                            </div>
                                            <div id="email_feedback_<?php echo $unit['id']; ?>" class="mt-1 text-xs hidden"></div>
                                        </div>
                                        
                                        <button type="submit" name="assign_tenant" class="w-full px-4 py-2 bg-primary-50 text-primary-700 rounded-md border border-primary-100 hover:bg-primary-100 transition-colors flex items-center justify-center" id="assignBtn_<?php echo $unit['id']; ?>">
                                            <i class='bx bx-user-plus mr-2'></i> Assign Tenant
                                        </button>
                                    </form>
                                    
                                    <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-500" id="assignInfo_<?php echo $unit['id']; ?>">
                                        <p class="flex items-center">
                                            <i class='bx bx-info-circle mr-1 text-gray-400'></i>
                                            Email will be checked against existing tenant records
                                        </p>
                                        <p class="flex items-center mt-1">
                                            <i class='bx bx-envelope mr-1 text-gray-400'></i>
                                            New tenants will receive an invitation email
                                        </p>
                                    </div>

                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const emailInput = document.getElementById('tenant_email_<?php echo $unit['id']; ?>');
                                            const emailStatus = document.getElementById('email_status_<?php echo $unit['id']; ?>');
                                            const emailFeedback = document.getElementById('email_feedback_<?php echo $unit['id']; ?>');
                                            const assignBtn = document.getElementById('assignBtn_<?php echo $unit['id']; ?>');
                                            const assignForm = document.getElementById('assignTenantForm_<?php echo $unit['id']; ?>');
                                            
                                            let checkTimeout = null;
                                            
                                            emailInput.addEventListener('input', function() {
                                                // Clear previous timeout
                                                if (checkTimeout) clearTimeout(checkTimeout);
                                                
                                                const email = this.value.trim();
                                                // Basic email validation
                                                if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                                                    emailStatus.classList.remove('hidden');
                                                    emailFeedback.classList.add('hidden');
                                                    
                                                    // Set a timeout to prevent too many requests
                                                    checkTimeout = setTimeout(function() {
                                                        // Use fetch API to check if email exists
                                                        fetch('check_tenant_email.php?email=' + encodeURIComponent(email))
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                emailStatus.classList.add('hidden');
                                                                emailFeedback.classList.remove('hidden');
                                                                
                                                                if (data.exists) {
                                                                    // Email exists in database
                                                                    emailFeedback.innerHTML = '<span class="text-green-600"><i class="bx bx-check-circle"></i> Existing tenant found! Will be assigned directly.</span>';
                                                                    emailFeedback.classList.add('text-green-600');
                                                                } else {
                                                                    // Email does not exist
                                                                    emailFeedback.innerHTML = '<span class="text-blue-600"><i class="bx bx-envelope"></i> New tenant - invitation will be sent.</span>';
                                                                    emailFeedback.classList.add('text-blue-600');
                                                                }
                                                            })
                                                            .catch(error => {
                                                                emailStatus.classList.add('hidden');
                                                                console.error('Error checking email:', error);
                                                            });
                                                    }, 500);
                                                } else {
                                                    emailStatus.classList.add('hidden');
                                                    emailFeedback.classList.add('hidden');
                                                }
                                            });
                                            
                                            assignForm.addEventListener('submit', function(e) {
                                                const email = emailInput.value.trim();
                                                if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                                                    e.preventDefault();
                                                    emailFeedback.classList.remove('hidden');
                                                    emailFeedback.innerHTML = '<span class="text-red-600"><i class="bx bx-error-circle"></i> Please enter a valid email address.</span>';
                                                }
                                                // Form will submit normally if validation passes
                                            });
                                        });
                                    </script>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Empty state when no units exist -->
                    <div class="col-span-full bg-white rounded-xl shadow-card p-8 text-center">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class='bx bx-building text-3xl text-gray-400'></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No Units Found</h3>
                        <p class="text-gray-500 max-w-sm mx-auto">You don't have any units registered. Please contact administration to register your units.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 p-4 text-center text-xs text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> SWARM - The Hive Residences. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Mobile sidebar controls
        document.getElementById('openSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
        });

        document.getElementById('closeSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
        });
    </script>
</body>
</html>
