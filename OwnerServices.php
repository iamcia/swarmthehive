<?php
include('dbconn.php');
session_start();

$ownerId = $_SESSION['user_id']; // Updated session variable to user_id based on login code
$status = '';

// Fetch the owner's information with correct column names
$sql = "SELECT o.ID as user_db_id, o.Owner_ID, o.Status 
        FROM ownerinformation o 
        WHERE o.Owner_ID = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $_SESSION['user_id']); 
    $stmt->execute();
    $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
    
    if ($userData) {
        $user_id = $userData['user_db_id'];
        $owner_id = $userData['Owner_ID'];
        $status = $userData['Status'];
        
        // Store in session for future use
        $_SESSION['user_db_id'] = $user_id;
        $_SESSION['owner_id'] = $owner_id;
    }
    $stmt->close();
}

// Add this for debugging
$_SESSION['owner_id'] = $owner_id;
$_SESSION['user_db_id'] = $user_id;

// Get current date for dashboard
$currentDate = date("F j, Y");
$currentDay = date("l");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SWARM | Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>
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
        [x-cloak] { display: none !important; }
        
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
                   class="flex items-center px-6 py-3 text-[#e9be3a] bg-primary-50 border-r-4 border-[#e9be3a]">
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
                        <h1 class="text-2xl font-bold text-gray-900">Community Portal</h1>
                        <div class="flex items-center mt-2">
                            <i class='bx bx-calendar text-primary-700 mr-2'></i>
                            <span class="text-sm text-gray-600"><?php echo $currentDay; ?>, <?php echo $currentDate; ?></span>
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

            <div class="mb-8">
                <div class="bg-white rounded-xl shadow-card p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Service Requests</h3>
                    <div id="recentServices" class="space-y-4">
                        <!-- Loading placeholder will be replaced by actual content -->
                        <div class="loading-placeholder animate-pulse flex space-x-4">
                            <div class="flex-1 space-y-4 py-1">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="space-y-2">
                                    <div class="h-4 bg-gray-200 rounded"></div>
                                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Available Services</h2>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">Quick access to community services</span>
                    </div>
                </div>
                
                <!-- Services Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Move In Notice Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-primary-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-primary-100 text-primary-700">
                                <i class='bx bx-home-alt text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Move In Notice</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Submit your move-in details to inform management about your arrival schedule.</p>
                        <a href="MoveInNotice.php" class="w-full flex items-center justify-center px-4 py-2 bg-primary-100 hover:bg-primary-200 text-primary-700 rounded-lg transition-colors">
                            <span>Submit Notice</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Move Out Notice Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-red-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-red-100 text-red-700">
                                <i class='bx bx-exit text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Move Out Notice</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Notify management about your plan to vacate the property and schedule inspections.</p>
                        <a href="MoveOutNotice.php" class="w-full flex items-center justify-center px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg transition-colors">
                            <span>Submit Notice</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Guest Check-In Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-blue-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-700">
                                <i class='bx bx-user-check text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Guest Check-In</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Register your visitors for seamless entry and enhanced community security.</p>
                        <a href="GuestInOutForm.php" class="w-full flex items-center justify-center px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-colors">
                            <span>Check-In Guest</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Gate Pass Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-green-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-green-100 text-green-700">
                                <i class='bx bx-id-card text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Gate Pass</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Request a gate pass for deliveries, service providers, or recurring visitors.</p>
                        <a href="Gatepass.php" class="w-full flex items-center justify-center px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition-colors">
                            <span>Request Pass</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Pet Registration Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-purple-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-700">
                                <i class='bx bx-dog text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Pet Registration</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Register your pets with the community for compliance with pet policies.</p>
                        <a href="PetRegistration.php" class="w-full flex items-center justify-center px-4 py-2 bg-purple-100 hover:bg-purple-200 text-purple-700 rounded-lg transition-colors">
                            <span>Register Pet</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Visitor Pass Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-orange-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-orange-100 text-orange-700">
                                <i class='bx bx-user-plus text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Visitor Pass</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Generate temporary passes for your expected visitors to grant them access.</p>
                        <a href="VisitorPass.php" class="w-full flex items-center justify-center px-4 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-lg transition-colors">
                            <span>Create Pass</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Work Permit Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-yellow-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-700">
                                <i class='bx bx-hard-hat text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Work Permit</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Apply for permits before starting any renovation or construction work.</p>
                        <a href="WorkPermit.php" class="w-full flex items-center justify-center px-4 py-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-700 rounded-lg transition-colors">
                            <span>Apply for Permit</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Amenity Reservation Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-teal-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-teal-100 text-teal-700">
                                <i class='bx bx-calendar-check text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Amenity Reservation</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Book community amenities for private events or personal use.</p>
                        <a href="OwnerReservation.php" class="w-full flex items-center justify-center px-4 py-2 bg-teal-100 hover:bg-teal-200 text-teal-700 rounded-lg transition-colors">
                            <span>Make Reservation</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                    
                    <!-- Pool Reserve Card -->
                    <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover border-t-4 border-cyan-500">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-cyan-100 text-cyan-700">
                                <i class='bx bx-swim text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-800">Pool Reservation</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Reserve pool access for yourself and guests during designated hours.</p>
                        <a href="ReservePool.php" class="w-full flex items-center justify-center px-4 py-2 bg-cyan-100 hover:bg-cyan-200 text-cyan-700 rounded-lg transition-colors">
                            <span>Reserve Pool</span>
                            <i class='bx bx-right-arrow-alt ml-2'></i>
                        </a>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 px-6 mt-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">© 2023 SWARM Community Portal. All rights reserved.</p>
                <div class="mt-3 md:mt-0 flex items-center space-x-4">

                </div>
            </div>
        </footer>
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

    <!-- Add these functions to your existing script section -->
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
    </script>

    <script>
        // Mobile sidebar controls
        const sidebar = document.getElementById('sidebar');
        const openSidebarBtn = document.getElementById('openSidebar');
        const closeSidebarBtn = document.getElementById('closeSidebar');
        
        openSidebarBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
        });
        
        closeSidebarBtn.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024) { // Only for mobile view
                if (!sidebar.contains(e.target) && e.target !== openSidebarBtn) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });
        
        // Document ready function
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Fancybox properly
            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind("[data-fancybox]", {
                    // Options
                });
            }
            
            // Fetch recent services
            fetchRecentServices();
        });

        function formatDate(dateString) {
            if (!dateString) return 'Date not available';
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function getServiceColor(type) {
            const colors = {
                'gatepass': 'bg-green-100 text-green-700',
                'Gate Pass': 'bg-green-100 text-green-700',
                'GuestCheckIn': 'bg-blue-100 text-blue-700',
                'Guest Check-In': 'bg-blue-100 text-blue-700',
                'MoveIn': 'bg-primary-100 text-primary-700',
                'MoveOut': 'bg-red-100 text-red-700',
                'AmenityReservation': 'bg-teal-100 text-teal-700',
                'VisitorPass': 'bg-orange-100 text-orange-700',
                'PetRegistration': 'bg-purple-100 text-purple-700',
                'poolreserve': 'bg-cyan-100 text-cyan-700',
                'WorkPermit': 'bg-yellow-100 text-yellow-700'
            };
            return colors[type] || 'bg-gray-100 text-gray-700';
        }

        function getServiceIcon(type) {
            const icons = {
                'gatepass': '<i class="bx bx-id-card"></i>',
                'Gate Pass': '<i class="bx bx-id-card"></i>',
                'GuestCheckIn': '<i class="bx bx-user-check"></i>',
                'Guest Check-In': '<i class="bx bx-user-check"></i>',
                'MoveIn': '<i class="bx bx-home-alt"></i>',
                'MoveOut': '<i class="bx bx-exit"></i>',
                'AmenityReservation': '<i class="bx bx-calendar-check"></i>',
                'VisitorPass': '<i class="bx bx-user-plus"></i>',
                'PetRegistration': '<i class="bx bx-dog"></i>',
                'poolreserve': '<i class="bx bx-swim"></i>',
                'WorkPermit': '<i class="bx bx-hard-hat"></i>'
            };
            return icons[type] || '<i class="bx bx-question-mark"></i>';
        }

        function getStatusColor(status) {
            // Normalize status to lowercase for consistent matching
            const normalizedStatus = (status || '').toLowerCase();
            const colors = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'approval': 'bg-green-100 text-green-800',
                'approved': 'bg-green-100 text-green-800',
                'rejected': 'bg-red-100 text-red-800',
                'completed': 'bg-blue-100 text-blue-800'
            };
            return colors[normalizedStatus] || 'bg-gray-100 text-gray-800';
        }

        // Clean version of fetchRecentServices without debug logging
        function fetchRecentServices() {
            const container = document.getElementById('recentServices');
            
            fetch('get_recent_services.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(services => {
                    // Clear the loading placeholder
                    container.innerHTML = '';
                    
                    if (!services) {
                        throw new Error('No data received');
                    }
                    
                    if (services.error) {
                        throw new Error(services.error);
                    }
                    
                    if (services.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-center">No recent service requests found.</p>';
                        return;
                    }

                    // Build and insert the content
                    container.innerHTML = services.map(service => {
                        // Normalize service type display
                        let displayType = service.type;
                        switch(service.type) {
                            case 'gatepass': displayType = 'Gate Pass'; break;
                            case 'GuestCheckIn': displayType = 'Guest Check-In'; break;
                            case 'MoveIn': displayType = 'Move In Notice'; break;
                            case 'MoveOut': displayType = 'Move Out Notice'; break;
                            case 'AmenityReservation': displayType = 'Amenity Reservation'; break;
                            case 'VisitorPass': displayType = 'Visitor Pass'; break;
                            case 'PetRegistration': displayType = 'Pet Registration'; break;
                            case 'poolreserve': displayType = 'Pool Reservation'; break;
                            case 'WorkPermit': displayType = 'Work Permit'; break;
                        }
                        
                        return `
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <span class="p-2 rounded-full ${getServiceColor(service.type)}">
                                            ${getServiceIcon(service.type)}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">${displayType}</p>
                                        <p class="text-sm text-gray-500">
                                            ${service.created_at ? `Submitted on ${formatDate(service.created_at)}` : 'Date not available'}
                                        </p>
                                        ${service.reject_reason ? `<p class="text-sm text-red-500 mt-1">Reason: ${service.reject_reason}</p>` : ''}
                                        
                                    </div>
                                </div>
                                <span class="px-3 py-1 text-sm rounded-full ${getStatusColor(service.status)}">
                                    ${service.status || 'Pending'}
                                </span>
                            </div>
                        `;
                    }).join('');
                })
                .catch(error => {
                    // Clear the loading placeholder and show error
                    container.innerHTML = `<p class="text-red-500 text-center">Error: ${error.message}</p>`;
                });
        }
    </script>
</body>
</html>
