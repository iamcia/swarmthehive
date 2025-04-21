<?php
include('dbconn.php');
session_start();

$ownerId = $_SESSION['user_id']; // Updated session variable to user_id based on login code
$status = '';

// Fetch status from the tenant information table
$sql = "SELECT Status FROM ownerinformation WHERE Owner_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $ownerId); 
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();

}
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Safety Guidelines</title>
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
                            700: '#a8850f',
                            800: '#7c620b',
                            900: '#503f07',
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

        /* Remove old styles and replace with new ones */
        .emergency-card {
            @apply bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover;
        }

        .contact-table {
            @apply min-w-full divide-y divide-gray-200;
        }

        .contact-table th {
            @apply px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
        }

        .contact-table td {
            @apply px-6 py-4 whitespace-nowrap text-sm text-gray-700;
        }

        .guidelines-card {
            @apply bg-white rounded-xl shadow-card p-6 transition-all duration-300;
        }

        .safety-card {
            @apply bg-white rounded-xl shadow-card p-6 hover:shadow-card-hover border-t-4 transition-all duration-300;
        }

        .safety-card .icon {
            @apply p-3 rounded-full flex items-center justify-center text-xl;
        }

        .safety-card .content {
            @apply mt-4 space-y-4;
        }

        .safety-card .instructions {
            @apply mt-4 space-y-3 text-sm text-gray-600;
        }

        .safety-card .instructions strong {
            @apply block text-gray-800 font-medium mb-2;
        }

        .safety-card .instructions ol {
            @apply list-decimal list-inside space-y-2;
        }

        .action-button {
            @apply w-full flex items-center justify-center px-4 py-2 rounded-lg transition-colors text-sm font-medium;
        }

        .safety-card ol {
            @apply mt-4 space-y-3;
        }

        .safety-card ol li {
            @apply flex items-start space-x-3 text-gray-600;
        }

        .safety-card ol li::before {
            @apply flex-shrink-0 h-6 w-6 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 text-sm font-medium;
            content: counter(list-item);
        }

        .safety-card strong {
            @apply block text-gray-800 font-medium mt-6 mb-3;
        }

        /* Add new drawer styles */
        .drawer-content {
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease-in-out;
        }
        
        .drawer.active .drawer-content {
            max-height: 2000px; /* Increased to handle all content */
        }
        
        .drawer-icon {
            transition: transform 0.3s ease;
        }
        
        .drawer.active .drawer-icon {
            transform: rotate(180deg);
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
                   class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100 transition-all">
                    <i class='bx bx-user-check text-xl mr-3'></i>
                    <span>Tenant Status</span>
                    <?php if ($status != 'Approved'): ?>
                        <span class="ml-auto px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded">Locked</span>
                    <?php endif; ?>
                </a>
                
                <!-- Safety Guidelines -->
                <a href="OwnerSafetyguidelines.php" 
                   class="flex items-center px-6 py-3 text-[#e9be3a] bg-primary-50 border-r-4 border-[#e9be3a]">
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

    <!-- Main content -->
    <div class="flex-1 lg:pl-64">
        <main class="min-h-screen p-4 md:p-6">
            <!-- Dashboard Header -->
            <header class="bg-white rounded-xl shadow-card p-4 md:p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Safety Guidelines</h1>
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

            <!-- Emergency Contacts Section -->
            <div class="bg-white rounded-xl shadow-card p-6 mb-6">
                <div class="flex items-center mb-6">
                    <div class="p-3 rounded-full bg-red-100 text-red-700">
                        <i class='bx bx-phone text-xl'></i>
                    </div>
                    <h2 class="ml-4 text-xl font-bold text-gray-800">Emergency Contact Numbers</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Property Management -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-primary-50 flex items-center justify-center text-[#e9be3a]">
                                <i class='bx bx-building-house text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Property Management</h3>
                        </div>
                        <p class="text-sm text-gray-600">0939-462-0569</p>
                        <p class="text-sm text-gray-600">0966-715-4160</p>
                    </div>

                    <!-- Barangay San Isidro -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700">
                                <i class='bx bx-buildings text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Barangay San Isidro</h3>
                        </div>
                        <p class="text-sm text-gray-600">(02) 8669-1096</p>
                    </div>

                    <!-- Meralco -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-700">
                                <i class='bx bx-bolt-circle text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Meralco</h3>
                        </div>
                        <p class="text-sm text-gray-600">16211</p>
                    </div>

                    <!-- Municipal Health Office -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700">
                                <i class='bx bx-plus-medical text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Municipal Health Office</h3>
                        </div>
                        <p class="text-sm text-gray-600">0998-595-1146 (Smart)</p>
                        <p class="text-sm text-gray-600">0919-086-1576 (Smart)</p>
                        <p class="text-sm text-gray-600">0998-595-1145 (Globe)</p>
                        <p class="text-sm text-gray-600">0917-625-0065 (Globe)</p>
                    </div>

                    <!-- Police -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700">
                                <i class='bx bx-shield-quarter text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Taytay PNP</h3>
                        </div>
                        <p class="text-sm text-gray-600">0936-950-7608</p>
                        <p class="text-sm text-gray-600">0998-598-5730</p>
                    </div>

                    <!-- Fire Department -->
                    <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-700">
                                <i class='bx bx-fire text-xl'></i>
                            </div>
                            <h3 class="ml-3 font-medium text-gray-800">Taytay Fire Department</h3>
                        </div>
                        <p class="text-sm text-gray-600">0917-150-2966</p>
                    </div>
                </div>

                <!-- Hospital Numbers Section -->
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Hospital Emergency Numbers</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-gray-800 mb-2">Antipolo Doctors Hospital</h4>
                            <p class="text-sm text-gray-600">(02) 8650-8269</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-gray-800 mb-2">Manila East Medical Center</h4>
                            <p class="text-sm text-gray-600">(02) 8660-0000</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                            <h4 class="font-medium text-gray-800 mb-2">Metro Rizal Doctors Hospital</h4>
                            <p class="text-sm text-gray-600">(02) 8251-6922</p>
                            <p class="text-sm text-gray-600">(02) 8532-6505</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guidelines Section -->
            <div class="space-y-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary-100 text-primary-700">
                        <i class='bx bx-book-open text-xl'></i>
                    </div>
                    <h2 class="ml-4 text-xl font-bold text-gray-800">Safety Guidelines</h2>
                </div>

                <!-- Fire Safety Drawer -->
                <div class="drawer bg-white rounded-xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between p-6 cursor-pointer hover:bg-orange-50/50"
                         onclick="toggleDrawer(this.parentElement)">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-100 text-orange-700">
                                <i class='bx bxs-flame text-2xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Fire Safety Protocol</h3>
                        </div>
                        <i class='bx bx-chevron-down text-xl text-gray-500 drawer-icon'></i>
                    </div>
                    <div class="drawer-content px-6 pb-6">
                        <div class="mt-4 p-4 bg-orange-50 rounded-lg border border-orange-100">
                            <div class="flex items-center text-orange-700 mb-2">
                                <i class='bx bxs-exit mr-2'></i>
                                <strong class="font-medium">Emergency Response</strong>
                            </div>
                            <p class="text-orange-600 text-sm">Immediate actions required during a fire emergency.</p>
                        </div>
                        <strong>If You Discover a Fire:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Activate the nearest fire alarm</li>
                            <li>Call Property Management Office immediately</li>
                            <li>Alert others in the immediate area</li>
                            <li>Only attempt to use a fire extinguisher if:
                                <ul class="list-disc list-inside ml-6 mt-1 text-gray-600">
                                    <li>The fire is small and contained</li>
                                    <li>You have a clear escape path</li>
                                    <li>You are trained to use it</li>
                                </ul>
                            </li>
                        </ol>
                        <strong class="mt-4 block">Evacuation Procedures:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Immediately leave the building using nearest exit</li>
                            <li>Do not use elevators</li>
                            <li>Close doors behind you to contain fire</li>
                            <li>Proceed to designated assembly area</li>
                            <li>Wait for further instructions from authorities</li>
                        </ol>
                    </div>
                </div>

                <!-- Earthquake Drawer -->
                <div class="drawer bg-white rounded-xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between p-6 cursor-pointer hover:bg-amber-50/50"
                         onclick="toggleDrawer(this.parentElement)">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-amber-100 text-amber-700">
                                <i class='bx bxs-landscape text-2xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Earthquake Response</h3>
                        </div>
                        <i class='bx bx-chevron-down text-xl text-gray-500 drawer-icon'></i>
                    </div>
                    <div class="drawer-content px-6 pb-6">
                        <div class="mt-4 p-4 bg-amber-50 rounded-lg border border-amber-100">
                            <div class="flex items-center text-amber-700 mb-2">
                                <i class='bx bxs-bell-ring mr-2'></i>
                                <strong class="font-medium">Before an Earthquake:</strong>
                            </div>
                            <ul class="list-disc list-inside text-amber-700">
                                <li>Identify safe spots in each room</li>
                                <li>Secure heavy furniture and objects</li>
                                <li>Keep emergency supplies ready</li>
                            </ul>
                        </div>
                        <strong class="mt-4 block">During an Earthquake:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>DROP to the ground</li>
                            <li>Take COVER under sturdy furniture</li>
                            <li>HOLD ON until shaking stops</li>
                            <li>Stay away from windows and exterior walls</li>
                        </ol>
                        <strong class="mt-4 block">After an Earthquake:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Check yourself and others for injuries</li>
                            <li>Be prepared for aftershocks</li>
                            <li>Listen for official instructions</li>
                            <li>Help neighbors if assistance is needed</li>
                        </ol>
                    </div>
                </div>

                <!-- Security Measures Drawer -->
                <div class="drawer bg-white rounded-xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between p-6 cursor-pointer hover:bg-blue-50/50"
                         onclick="toggleDrawer(this.parentElement)">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-700">
                                <i class='bx bxs-shield-plus text-2xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Security Guidelines</h3>
                        </div>
                        <i class='bx bx-chevron-down text-xl text-gray-500 drawer-icon'></i>
                    </div>
                    <div class="drawer-content px-6 pb-6">
                        <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-center text-blue-700 mb-2">
                                <i class='bx bxs-lock-alt mr-2'></i>
                                <strong class="font-medium">Daily Security Measures</strong>
                            </div>
                            <ul class="list-disc list-inside text-blue-700">
                                <li>Always lock doors and windows</li>
                                <li>Report suspicious activities</li>
                                <li>Keep emergency contacts handy</li>
                            </ul>
                        </div>
                        <strong class="mt-4 block">Suspicious Activity:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Do not confront suspicious persons</li>
                            <li>Contact security immediately</li>
                            <li>Note important details (description, location)</li>
                            <li>Keep a safe distance</li>
                        </ol>
                        <strong class="mt-4 block">Break-in Response:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Do not enter if you suspect a break-in</li>
                            <li>Call security and police immediately</li>
                            <li>Wait for authorities to arrive</li>
                            <li>Document any damage or losses</li>
                        </ol>
                    </div>
                </div>

                <!-- Medical Emergency Drawer -->
                <div class="drawer bg-white rounded-xl shadow-card overflow-hidden">
                    <div class="flex items-center justify-between p-6 cursor-pointer hover:bg-green-50/50"
                         onclick="toggleDrawer(this.parentElement)">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-700">
                                <i class='bx bxs-first-aid text-2xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Medical Emergency</h3>
                        </div>
                        <i class='bx bx-chevron-down text-xl text-gray-500 drawer-icon'></i>
                    </div>
                    <div class="drawer-content px-6 pb-6">
                        <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-100">
                            <div class="flex items-center text-green-700 mb-2">
                                <i class='bx bxs-ambulance mr-2'></i>
                                <strong class="font-medium">Emergency Response Steps</strong>
                            </div>
                            <p class="text-green-600 text-sm">Quick action can save lives in medical emergencies.</p>
                        </div>
                        <strong class="mt-4 block">Immediate Actions:</strong>
                        <ol class="list-decimal list-inside space-y-2 mt-3 text-gray-700">
                            <li>Check responsiveness and breathing</li>
                            <li>Call emergency services immediately</li>
                            <li>Send someone to guide emergency responders</li>
                            <li>Stay with the person until help arrives</li>
                            <li>Only provide first aid if trained</li>
                        </ol>
                        <strong class="mt-4 block">Important Notes:</strong>
                        <ul class="list-disc list-inside space-y-2 mt-3 text-gray-600">
                            <li>Keep emergency numbers readily available</li>
                            <li>Know location of nearest AED</li>
                            <li>Maintain an updated first aid kit</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 px-6 mt-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">© 2025 SWARM Community Portal. All rights reserved.</p>
                <div class="mt-3 md:mt-0 flex items-center space-x-4">
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Terms of Service</a>
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Privacy Policy</a>
                    <a href="#" class="text-xs text-gray-500 hover:text-primary-700">Contact</a>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Mobile sidebar controls
        const sidebar = document.getElementById('sidebar');
        const openSidebarBtn = document.getElementById('openSidebar');
        const closeSidebarBtn = document.getElementById('closeSidebar');
        
        // Add missing event listeners for sidebar
        openSidebarBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
        });
        
        closeSidebarBtn.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
        
        // Fix drawer toggle function
        function toggleDrawer(drawer) {
            console.log("Toggling drawer", drawer);
            
            // Close all other drawers
            document.querySelectorAll('.drawer').forEach(d => {
                if (d !== drawer && d.classList.contains('active')) {
                    d.classList.remove('active');
                }
            });
            
            // Toggle current drawer
            drawer.classList.toggle('active');
        }
        
        // Make first drawer open by default (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Open first drawer by default (optional)
            // const firstDrawer = document.querySelector('.drawer');
            // if (firstDrawer) toggleDrawer(firstDrawer);
        });

        // Help Modal Functions
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
</body>
</html>