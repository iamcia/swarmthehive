<?php
include 'dbconn.php';
session_start();

$message = '';
$userType = '';
$userEmail = '';
$signature = '';
$userNumber = '';
$unitNumber = '';
$residentCode = '';
$ownerName = '';
$stats = 'Pending'; // Default status
$user_id = null; // Added user_id variable

// Fetch user details if the user is logged in
if (isset($_SESSION['username'])) {
    $ownerUsername = $_SESSION['username'];

    // First, attempt to fetch information from the OwnerInformation table
    $sql = "SELECT ID, Owner_ID, First_Name, Last_Name, Email, Mobile_Number, Unit_Number, Signature, Status FROM ownerinformation WHERE Username = ?"; // Added ID field
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ownerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $userType = 'Owner';
        $residentCode = $row['Owner_ID'];
        $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
        $userEmail = $row['Email'];
        $userNumber = $row['Mobile_Number'];
        $unitNumber = $row['Unit_Number'];
        $signature = $row['Signature'];
        $status = $row['Status'];
        $user_id = $row['ID']; // Store the user ID
    } else {
        // If no match found in OwnerInformation, check TenantInformation
        $sql = "SELECT Tenant_ID, First_Name, Last_Name, Email, Mobile_Number, Unit_Number, Signature, Status FROM tenantinformation WHERE Username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ownerUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userType = 'Tenant';
            $residentCode = $row['Tenant_ID'];
            $ownerName = $row['First_Name'] . " " . $row['Last_Name'];
            $userEmail = $row['Email'];
            $userNumber = $row['Mobile_Number'];
            $unitNumber = $row['Unit_Number'];
            $signature = $row['Signature'];
            $status = $row['Status'];
            
            // For tenants, we need to find their associated owner's ID
            $ownerQuery = "SELECT o.ID FROM ownerinformation o 
                           INNER JOIN tenantinformation t ON o.Owner_ID = t.Owner_ID 
                           WHERE t.Tenant_ID = ?";
            $stmt_owner = $conn->prepare($ownerQuery);
            $stmt_owner->bind_param("s", $residentCode);
            $stmt_owner->execute();
            $owner_result = $stmt_owner->get_result();
            
            if ($owner_result->num_rows > 0) {
                $owner_row = $owner_result->fetch_assoc();
                $user_id = $owner_row['ID'];
            }
            $stmt_owner->close();
        }
    }

    $stmt->close();
}

// Check for success or error messages from form processing
if (isset($_GET['success'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message'] ?? "Visitor registration submitted successfully!") . "');</script>";
}
if (isset($_GET['error'])) {
    echo "<script>alert('" . htmlspecialchars($_GET['message'] ?? "An error occurred.") . "');</script>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Swarm | Visitor Pass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet"/>
    <!-- Add Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f1c40f',
                        primaryDark: '#e1b00f',
                        primaryLight: '#fef9c3',
                        secondary: '#333333',
                        light: '#f4f4f4',
                        'vis-blue': '#3498db',
                        'vis-teal': '#2dd4bf',
                        'vis-indigo': '#6366f1',
                        'vis-orange': '#f97316',
                    },
                    boxShadow: {
                        'custom': '0 4px 20px rgba(0, 0, 0, 0.05)',
                        'hover': '0 10px 30px rgba(0, 0, 0, 0.1)',
                    },
                    animation: {
                        'bounce-slow': 'bounce 2s infinite',
                        'pulse-slow': 'pulse 3s infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-10">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <button onclick="history.back()" class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 rounded-full shadow-custom flex items-center gap-2 transition duration-300 mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span>Back</span>
                    </button>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800">
                        <span class="text-vis-orange">Visitor</span> Pass
                    </h1>
                </div>
                
                <div class="hidden md:block">
                    <div class="bg-orange-100 text-orange-800 font-medium rounded-full px-4 py-1 flex items-center">
                        <i class="fas fa-id-card mr-2"></i>
                        <span>Form #VP-<?php echo date('Ymd'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Information Box -->
            <div class="bg-white rounded-2xl p-6 shadow-custom mb-8 transition-all duration-300 hover:shadow-hover border-l-4 border-vis-orange">
                <div class="flex items-start">
                    <div class="bg-orange-100 p-3 rounded-full mr-4">
                        <i class="fas fa-info-circle text-vis-orange text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                            Visitor Pass Guidelines
                            <span class="bg-orange-100 text-orange-800 text-xs uppercase tracking-wide font-bold rounded-full px-3 py-1 ml-3">Important</span>
                        </h3>
                        
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-vis-orange mt-1 mr-2"></i>
                                <span>All visitors must be pre-registered at least 24 hours before scheduled arrival.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-vis-orange mt-1 mr-2"></i>
                                <span>Visitors must present valid identification upon arrival.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-vis-orange mt-1 mr-2"></i>
                                <span><strong>Maximum of 5 visitors per unit is allowed.</strong></span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-vis-orange mt-1 mr-2"></i>
                                <span>Visitor passes are valid for the specified dates only.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-vis-orange mt-1 mr-2"></i>
                                <span>Residents are responsible for their visitors' conduct while in the premises.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mb-8">
                <div class="inline-block bg-blue-50 text-blue-700 rounded-lg px-5 py-3 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-blue-500 mr-2 text-lg"></i>
                        <p class="text-sm font-medium">
                            Please complete all fields marked with <span class="text-red-500 font-bold">*</span> to register your visitors
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <form method="POST" enctype="multipart/form-data" action="Insert-VisitorPass.php" class="space-y-8">
            <!-- Hidden Fields -->
            <input type="hidden" id="resident_code" name="resident_code" value="<?php echo htmlspecialchars($residentCode); ?>">
            <input type="hidden" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($ownerName); ?>" readonly required>
            <input type="hidden" id="contact" name="contact" value="<?php echo htmlspecialchars($userNumber); ?>" readonly required>
            <input type="hidden" id="unit_no" name="unit_no" value="<?php echo htmlspecialchars($unitNumber); ?>" readonly required>
            <input type="hidden" id="email" name="email" value="<?php echo htmlspecialchars($userEmail); ?>" readonly required>
            <input type="hidden" id="user_type" name="user_type" value="<?php echo htmlspecialchars($userType); ?>">
            <input type="hidden" id="user_id" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">

            <!-- Resident Information -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-user text-vis-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Resident Information</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Name</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($ownerName); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Contact Number</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($userNumber); ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Unit Number</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($unitNumber); ?></p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-600 text-sm font-medium mb-1">Email</label>
                        <p class="px-3 py-2 bg-gray-50 rounded-md border border-gray-200 text-gray-700"><?php echo htmlspecialchars($userEmail); ?></p>
                    </div>
                </div>
            </div>

            <!-- Visit Schedule -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-indigo-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-calendar-alt text-vis-indigo"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Visit Schedule</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-gray-700 font-medium mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-plus text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="start_date" 
                                name="start_date" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-indigo focus:border-transparent transition-all duration-300"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            The date your visitor(s) will arrive
                        </p>
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-gray-700 font-medium mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-minus text-gray-400"></i>
                            </div>
                            <input 
                                type="date" 
                                id="end_date" 
                                name="end_date" 
                                required
                                class="w-full pl-10 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-indigo focus:border-transparent transition-all duration-300"
                            >
                        </div>
                        <p class="text-xs text-gray-500 mt-1 flex items-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            The date your visitor(s) will depart
                        </p>
                    </div>
                </div>
            </div>

            <!-- Guest Information Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-teal-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-users text-vis-teal"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Visitor Information</h3>
                    <span class="bg-teal-50 text-teal-700 text-xs uppercase font-bold rounded-full px-3 py-1 ml-3">Max 5 Visitors</span>
                </div>
                
                <div class="overflow-x-auto mb-4 rounded-lg">
                    <table id="guestTable" class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-teal-50 text-teal-800">
                                <th class="py-3 px-4 text-left font-semibold border-b">Name of Visitor</th>
                                <th class="py-3 px-4 text-left font-semibold border-b">Contact Number</th>
                                <th class="py-3 px-4 text-left font-semibold border-b">Relationship</th>
                                <th class="py-3 px-4 text-left font-semibold border-b">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="guest_info[0][name]" 
                                            placeholder="Full Name" 
                                            required
                                            class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                                        >
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-phone text-gray-400"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="guest_info[0][contact]" 
                                            placeholder="Phone Number" 
                                            required
                                            class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                                        >
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user-friends text-gray-400"></i>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="guest_info[0][relationship]" 
                                            placeholder="e.g., Friend, Family" 
                                            required
                                            class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                                        >
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <button 
                                        type="button" 
                                        class="removeRowBtn text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        <span>Please provide details for each visitor</span>
                    </p>
                    
                    <button 
                        type="button" 
                        id="addRowBtn" 
                        class="flex items-center px-4 py-2 bg-vis-teal hover:bg-teal-600 text-white font-medium rounded-lg transition duration-300 shadow-sm"
                    >
                        <i class="fas fa-plus mr-2"></i> Add Visitor
                    </button>
                </div>
            </div>

            <!-- ID Upload Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-vis-orange bg-opacity-10 p-2 rounded-lg mr-3">
                        <i class="fas fa-id-card text-vis-orange"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Identification Documents</h3>
                        <p class="text-xs text-gray-500">Upload a valid ID for verification purposes</p>
                    </div>
                </div>

                <div>
                    <label for="valid_id" class="block text-gray-700 font-medium mb-2">
                        Valid ID <span class="text-red-500">*</span>
                        <span class="text-sm font-normal text-gray-500 ml-1">(Primary visitor)</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center hover:border-vis-orange transition-colors duration-300 bg-gray-50">
                        <input 
                            id="valid_id" 
                            type="file" 
                            name="valid_id" 
                            accept="image/*" 
                            required 
                            class="hidden"
                            onchange="previewImage(event, 'valid_id_preview')"
                        >
                        <div class="mb-3">
                            <div class="mx-auto w-16 h-16 bg-orange-50 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-id-card text-vis-orange text-2xl"></i>
                            </div>
                            
                            <button 
                                type="button" 
                                onclick="document.getElementById('valid_id').click();" 
                                class="px-5 py-2.5 bg-vis-orange bg-opacity-10 hover:bg-opacity-20 text-vis-orange font-medium rounded-lg transition duration-300 flex items-center mx-auto"
                            >
                                <i class="fas fa-upload mr-2"></i> Upload ID Document
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Government-issued ID (Driver's license, Passport, etc.)</p>
                        </div>
                        <div id="valid_id_preview" class="mt-4 flex flex-wrap gap-2 justify-center"></div>
                    </div>
                </div>
            </div>
            
            <!-- Signature Section (if we have signature from DB) -->
            <?php if (!empty($signature)): ?>
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-signature text-vis-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Your Signature</h3>
                </div>
                
                <div class="flex justify-center">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 inline-block">
                        <img 
                            src="<?php echo is_file($signature) ? $signature : 'data:image/png;base64,'.base64_encode($signature); ?>" 
                            alt="Resident Signature" 
                            class="h-20 object-contain"
                        >
                    </div>
                </div>
                <p class="text-sm text-center text-gray-500 mt-2">
                    This signature will be used to verify your visitor pass request
                </p>
            </div>
            <?php else: ?>
            <!-- Upload signature if none exists -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="bg-blue-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-signature text-vis-blue"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Signature Upload</h3>
                </div>
                
                <div>
                    <label for="signature" class="block text-gray-700 font-medium mb-2">
                        Your Signature <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center hover:border-vis-blue transition-colors duration-300 bg-gray-50">
                        <input 
                            id="signature" 
                            type="file" 
                            name="signature" 
                            accept="image/*" 
                            required 
                            class="hidden"
                            onchange="previewImage(event, 'signature_preview')"
                        >
                        <div class="mb-3">
                            <div class="mx-auto w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-signature text-vis-blue text-2xl"></i>
                            </div>
                            
                            <button 
                                type="button" 
                                onclick="document.getElementById('signature').click();" 
                                class="px-5 py-2.5 bg-blue-50 hover:bg-blue-100 text-vis-blue font-medium rounded-lg transition duration-300 flex items-center mx-auto"
                            >
                                <i class="fas fa-upload mr-2"></i> Upload Signature
                            </button>
                            <p class="text-xs text-gray-500 mt-2">Upload a clear image of your signature</p>
                        </div>
                        <div id="signature_preview" class="mt-4 flex flex-wrap gap-2 justify-center"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Terms and Conditions Section -->
            <div class="bg-white rounded-xl shadow-custom p-6 transition duration-300 hover:shadow-hover border border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="bg-red-50 p-2 rounded-lg mr-3">
                        <i class="fas fa-file-contract text-red-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">Terms and Conditions</h3>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                    <div class="text-gray-700 text-sm space-y-3">
                        <p>By submitting this form, I agree to the following:</p>
                        <ol class="list-decimal ml-5 space-y-1">
                            <li>I am responsible for my visitors' conduct while on the premises.</li>
                            <li>All visitors must follow building rules and regulations at all times.</li>
                            <li>The management reserves the right to deny entry to any visitor at their discretion.</li>
                            <li>The visitor pass is valid only for the specified date range.</li>
                            <li>Any damages caused by visitors will be my responsibility.</li>
                        </ol>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input 
                            id="terms" 
                            name="terms" 
                            type="checkbox" 
                            required
                            class="w-5 h-5 border-gray-300 rounded accent-vis-orange focus:ring-vis-orange"
                        >
                    </div>
                    <div class="ml-3">
                        <label for="terms" class="text-gray-700 font-medium">
                            I have read and agree to the terms and conditions
                        </label>
                        <p class="text-gray-500 text-xs">
                            By checking this box, you acknowledge all the terms above
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="flex justify-end space-x-4 mt-8">
                <button 
                    type="button" 
                    id="clearFormBtn" 
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg shadow-sm border border-gray-200 transition duration-300 flex items-center"
                >
                    <i class="fas fa-eraser mr-2"></i> Clear Form
                </button>
                <button 
                    type="submit" 
                    class="px-8 py-3 bg-vis-orange hover:bg-orange-600 text-white font-medium rounded-lg shadow-md transition duration-300 flex items-center group"
                >
                    <i class="fas fa-check-circle mr-2 group-hover:animate-bounce-slow"></i> Submit Request
                </button>
            </div>
        </form>

        <!-- Help Card -->
        <div class="mt-10 bg-blue-50 rounded-xl p-6 border border-blue-100 flex items-start">
            <div class="bg-blue-100 p-3 rounded-full text-blue-500 mr-4">
                <i class="fas fa-question-circle text-xl"></i>
            </div>
            <div>
                <h4 class="font-bold text-blue-800 mb-1">Need Help?</h4>
                <p class="text-blue-700 text-sm mb-3">If you have questions about visitor registration or building policies:</p>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-phone-alt mr-2"></i> Contact Property Management
                    </a>
                    <a href="#" class="text-blue-600 hover:text-blue-800 font-medium flex items-center">
                        <i class="fas fa-envelope mr-2"></i> Email Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex items-center justify-center">
        <button class="absolute top-4 right-8 text-white text-4xl font-bold hover:text-gray-300" onclick="closeImageModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" class="max-w-[90%] max-h-[90%] object-contain" alt="Enlarged Image">
    </div>

    <script>
        // Set minimum date values for date inputs to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const todayFormatted = `${yyyy}-${mm}-${dd}`;
            
            document.getElementById('start_date').min = todayFormatted;
            document.getElementById('end_date').min = todayFormatted;
            
            // Set start date to today by default
            document.getElementById('start_date').value = todayFormatted;
            
            // Set end date to tomorrow by default
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowFormatted = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
            document.getElementById('end_date').value = tomorrowFormatted;
        });

        // Preview Image Function
        function previewImage(event, previewId) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById(previewId);
            
            if (file) {
                previewContainer.innerHTML = ''; // Clear previous previews
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = "Document Preview";
                    img.className = 'h-32 object-contain rounded-lg border border-gray-200 shadow-sm cursor-pointer';
                    img.onclick = function() { showImageModal(e.target.result); };
                    
                    imgContainer.appendChild(img);
                    
                    const caption = document.createElement('div');
                    caption.textContent = file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name;
                    caption.className = 'absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs py-1 px-2 text-center truncate';
                    imgContainer.appendChild(caption);
                    
                    previewContainer.appendChild(imgContainer);
                };
                reader.readAsDataURL(file);
            }
        }

        // Show image in modal
        function showImageModal(src) {
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('modalImage').src = src;
        }

        // Close image modal
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Visitor table functions
        const addRowBtn = document.getElementById('addRowBtn');
        const guestTable = document.getElementById('guestTable').getElementsByTagName('tbody')[0];
        let rowCount = 1;

        // Add row function
        addRowBtn.addEventListener('click', function() {
            if (rowCount < 5) {
                const row = guestTable.insertRow();
                row.className = 'border-b hover:bg-gray-50 transition-colors';
                row.innerHTML = `
                    <td class="py-3 px-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="guest_info[${rowCount}][name]" 
                                placeholder="Full Name" 
                                required
                                class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="guest_info[${rowCount}][contact]" 
                                placeholder="Phone Number" 
                                required
                                class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-friends text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="guest_info[${rowCount}][relationship]" 
                                placeholder="e.g., Friend, Family" 
                                required
                                class="w-full pl-9 px-4 py-2 bg-white border border-gray-200 rounded-md text-gray-700 focus:outline-none focus:ring-2 focus:ring-vis-teal focus:border-transparent transition-all duration-300"
                            >
                        </div>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <button 
                            type="button" 
                            class="removeRowBtn text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-full transition-colors"
                        >
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                `;
                rowCount++;
                
                // Reattach event listeners for new row
                attachRemoveRowListeners();
            } else {
                alert("Maximum of 5 visitors allowed per registration.");
            }
        });

        // Attach event listeners to remove row buttons
        function attachRemoveRowListeners() {
            const removeRowBtns = document.querySelectorAll('.removeRowBtn');
            removeRowBtns.forEach(button => {
                button.addEventListener('click', function() {
                    // Don't remove if it's the last row
                    if (guestTable.rows.length > 1) {
                        this.closest('tr').remove();
                        rowCount--;
                    } else {
                        alert("At least one visitor is required.");
                    }
                });
            });
        }

        // Initial setup for the first row
        attachRemoveRowListeners();

        // Clear form functionality
        document.getElementById('clearFormBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all form fields?')) {
                // Clear all inputs except the first visitor row
                const form = this.closest('form');
                
                // Reset dates to default (today/tomorrow)
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                document.getElementById('start_date').value = `${yyyy}-${mm}-${dd}`;
                
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('end_date').value = 
                    `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
                
                // Clear file inputs
                document.getElementById('valid_id').value = '';
                if (document.getElementById('signature')) {
                    document.getElementById('signature').value = '';
                }
                
                // Clear preview divs
                document.getElementById('valid_id_preview').innerHTML = '';
                if (document.getElementById('signature_preview')) {
                    document.getElementById('signature_preview').innerHTML = '';
                }
                
                // Clear visitor table except first row
                const firstRow = guestTable.rows[0];
                const inputs = firstRow.querySelectorAll('input');
                inputs.forEach(input => {
                    input.value = '';
                });
                
                // Remove extra rows
                while (guestTable.rows.length > 1) {
                    guestTable.deleteRow(1);
                }
                
                // Reset row count
                rowCount = 1;
                
                // Uncheck terms checkbox
                document.getElementById('terms').checked = false;
            }
        });

        // Start/End date validation
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(this.value);
            
            if (endDate < startDate) {
                alert("End date cannot be earlier than start date.");
                const tomorrow = new Date(startDate);
                tomorrow.setDate(tomorrow.getDate() + 1);
                this.value = tomorrow.toISOString().split('T')[0];
            }
        });

        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.getElementById('end_date');
            const endDate = new Date(endDateInput.value);
            
            if (endDate < startDate) {
                const tomorrow = new Date(startDate);
                tomorrow.setDate(tomorrow.getDate() + 1);
                endDateInput.value = tomorrow.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
