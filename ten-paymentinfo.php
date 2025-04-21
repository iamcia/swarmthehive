<?php
include('dbconn.php');
session_start();

$tenantId = $_SESSION['user_id'];  
$status = '';
$billing_number = '';
$due_date = '';
$pdf_file = '';
$fees = [];

// Check if the user is an Owner
$sql = "SELECT Status FROM tenantinformation WHERE Tenant_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tenantId);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

// Fetch Latest Billing Info
$sql = "SELECT Billing_Number, Due_Date FROM soafinance WHERE Owner_ID = ? ORDER BY Due_Date DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $tenantId);
$stmt->execute();
$stmt->bind_result($billing_number, $due_date);
$stmt->fetch();
$stmt->close();

// Handle Fee Addition (Same File)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fee_type']) && isset($_POST['amount'])) {
    $fee_type = $_POST['fee_type'];
    $amount = $_POST['amount'];

    if ($billing_number) {
        $sql = "INSERT INTO soafinance_fees (Billing_Number, Fee_Type, Amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssd", $billing_number, $fee_type, $amount);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch Fee Breakdown
if ($billing_number) {
    $sql = "SELECT Fee_Type, Amount FROM soafinance_fees WHERE Billing_Number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $billing_number);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
    $stmt->close();

    // Fetch PDF file
    $sql = "SELECT PDF_File FROM soafinance_pdfs WHERE Billing_Number = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $billing_number);
    $stmt->execute();
    $stmt->bind_result($pdf_file);
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
    <title>SWARM | Payment Info</title>
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
                            700: '#e9be3a',
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

        /* Keep existing payment info specific styles */
        .pdf-container {
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
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
                        <h1 class="text-2xl font-bold text-gray-900">Payment Information</h1>
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

            <!-- Statement of Account Card -->
            <div class="bg-white rounded-xl shadow-card p-6 mb-6">
                <?php if ($billing_number): ?>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Billing Number:</span>
                            <span class="font-medium"><?= htmlspecialchars($billing_number) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Due Date:</span>
                            <span class="font-medium"><?= htmlspecialchars($due_date) ?></span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <p class="text-sm text-gray-700">Please ensure all dues are settled by the due date.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class='bx bx-file text-3xl text-gray-400'></i>
                        </div>
                        <p class="text-gray-500">No billing information found.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PDF Viewer Card -->
            <?php if ($pdf_file): ?>
                <div class="bg-white rounded-xl shadow-card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 text-red-700">
                                <i class='bx bx-file-pdf text-xl'></i>
                            </div>
                            <h3 class="ml-4 text-xl font-bold text-gray-800">Statement PDF</h3>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?= htmlspecialchars($pdf_file) ?>" download 
                               class="inline-flex items-center px-3 py-2 bg-primary-100 text-primary-700 rounded-md hover:bg-primary-200 transition-colors">
                                <i class="bx bx-download mr-1.5"></i> Download
                            </a>
                            <button onclick="openFullscreen()" 
                                    class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                                <i class="bx bx-fullscreen mr-1.5"></i> Fullscreen
                            </button>
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <iframe id="pdfViewer" src="<?= htmlspecialchars($pdf_file) ?>" class="w-full h-[600px]"></iframe>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-card p-6 mb-6">
                    <div class="text-center py-6">
                        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class='bx bx-file-pdf text-3xl text-gray-400'></i>
                        </div>
                        <p class="text-gray-500">No PDF document available for this billing record.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Two Column Layout for Breakdown and Add New Fee -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Breakdown of Fees Card -->
                <div class="bg-white rounded-xl shadow-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-700">
                            <i class='bx bx-list-ul text-xl'></i>
                        </div>
                        <h3 class="ml-4 text-xl font-bold text-gray-800">Breakdown of Fees</h3>
                    </div>
                    
                    <?php if (!empty($fees)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php 
                                    $total = 0;
                                    foreach ($fees as $fee): 
                                        $total += $fee['Amount'];
                                    ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?= htmlspecialchars($fee['Fee_Type']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">₱<?= number_format($fee['Amount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800">Total Amount</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-right text-gray-800">₱<?= number_format($total, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <p class="text-gray-500">No fees have been added to this billing.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add New Fee Card -->
                <div class="bg-white rounded-xl shadow-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 rounded-full bg-green-100 text-green-700">
                            <i class='bx bx-plus-circle text-xl'></i>
                        </div>
                        <h3 class="ml-4 text-xl font-bold text-gray-800">Add New Fee</h3>
                    </div>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="billing_number" value="<?= htmlspecialchars($billing_number) ?>">
                        
                        <div>
                            <label for="fee_type" class="block text-sm font-medium text-gray-700 mb-1">Select Fee Type</label>
                            <select name="fee_type" class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500" required>
                                <option value="Water Fee">Water Fee</option>
                                <option value="Electricity Fee">Electricity Fee</option>
                                <option value="Maintenance Fee">Maintenance Fee</option>
                                <option value="Internet Fee">Internet Fee</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (₱)</label>
                            <input type="number" name="amount" step="0.01" min="0" 
                                   class="w-full p-2 border border-gray-300 rounded-md focus:ring-primary-500 focus:border-primary-500" 
                                   required>
                        </div>

                        <button type="submit" 
                                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                            Add Fee
                        </button>
                    </form>
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
            if (window.innerWidth < 1024) {
                if (!sidebar.contains(e.target) && e.target !== openSidebarBtn) {
                    sidebar.classList.add('-translate-x-full');
                }
            }
        });

        // Keep your existing payment-specific JavaScript
        // ...existing payment scripts...
        const fullscreenButton = document.querySelector('.pdf-fullscreen');
        const pdfContainer = document.querySelector('.pdf-container iframe');

        fullscreenButton.addEventListener('click', () => {
            if (pdfContainer.requestFullscreen) {
                pdfContainer.requestFullscreen();
            } else if (pdfContainer.mozRequestFullScreen) { /* Firefox */
                pdfContainer.mozRequestFullScreen();
            } else if (pdfContainer.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
                pdfContainer.webkitRequestFullscreen();
            } else if (pdfContainer.msRequestFullscreen) { /* IE/Edge */
                pdfContainer.msRequestFullscreen();
            }
        });

        window.onload = function() {
            const savedProfilePic = localStorage.getItem('profilePic');
            const defaultProfilePic = '/img/default-profile-pic.png'; // Path to your default profile picture

            // Check if there is a saved profile picture
            if (savedProfilePic) {
                document.getElementById('mainProfilePicture').src = savedProfilePic;
                document.getElementById('previewProfilePicture').src = savedProfilePic;
            } else {
                // Set the default profile picture if no saved picture is found
                document.getElementById('mainProfilePicture').src = defaultProfilePic;
                document.getElementById('previewProfilePicture').src = defaultProfilePic;
            }
        };

        function openEditProfile() {
            document.getElementById('editProfileModal').style.display = 'flex';
        }

        function triggerUpload() {
            document.getElementById('upload').click();
        }

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                document.getElementById('previewProfilePicture').src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function saveProfile() {
            const name = document.getElementById('nameInput').value; // Get the updated name
            const profilePicSrc = document.getElementById('previewProfilePicture').src;

            // Update the main profile picture and username display
            document.getElementById('mainProfilePicture').src = profilePicSrc;
            const mainDisplaynameElement = document.getElementById('mainDisplayname');
            mainDisplaynameElement.childNodes[0].nodeValue = name; // Display the new name, but do not store it

            // Save only the profile picture to localStorage
            localStorage.setItem('profilePic', profilePicSrc);

            // Display success prompt
            alert("Profile successfully updated!");

            // Hide the modal
            document.getElementById('editProfileModal').style.display = 'none';
        }

        function closeModal() {
            document.getElementById('editProfileModal').style.display = 'none';
        }

        function openFullscreen() {
            let iframe = document.getElementById("pdfViewer");
            if (iframe.requestFullscreen) {
                iframe.requestFullscreen();
            } else if (iframe.mozRequestFullScreen) { 
                iframe.mozRequestFullScreen();
            } else if (iframe.webkitRequestFullscreen) { 
                iframe.webkitRequestFullscreen();
            } else if (iframe.msRequestFullscreen) { 
                iframe.msRequestFullscreen();
            }
        }
        
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

