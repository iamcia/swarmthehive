<?php
include('dbconn.php');
session_start();

$tenantId = $_SESSION['user_id']; // Updated session variable to user_id based on login code
$status = '';

// Fetch the owner's status from the owner information table
$sql = "SELECT Status FROM tenantinformation WHERE Tenant_ID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $tenantId); 
    $stmt->execute();
    $stmt->bind_result($status);
    $stmt->fetch();
    $stmt->close();
}

// Fetch only approved announcements
$sql = "SELECT * FROM announcements WHERE Status = 'active' ORDER BY created_at DESC";
$result = $conn->query($sql);

// Count announcements
$announcementCount = $result ? $result->num_rows : 0;

// Get current date for dashboard
$currentDate = date("F j, Y");
$currentDay = date("l");

$conn->close();

// Add these helper functions at the top of your file
function getFileType($filePath) {
    if (empty($filePath)) return 'none';
    
    // Make sure we're working with a correct path
    $filePath = str_replace('\\', '/', $filePath);
    
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($extension === 'pdf') return 'pdf';
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) return 'image';
    if ($extension === 'gif') return 'gif';
    return 'other';
}

function getFileIcon($fileType) {
    switch ($fileType) {
        case 'pdf': return '<i class="far fa-file-pdf"></i>';
        case 'image': return '<i class="far fa-file-image"></i>';
        case 'gif': return '<i class="far fa-file-video"></i>';
        default: return '<i class="far fa-file"></i>';
    }
}

function getFileColor($fileType) {
    switch ($fileType) {
        case 'pdf': return 'red';
        case 'image': return 'blue';
        case 'gif': return 'purple';
        default: return 'gray';
    }
}
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

        /* Enhanced media viewer styles */
        .media-loader {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
        }
        
        .media-loader::after {
            content: '';
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8b4513;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pdf-controls {
            display: flex;
            justify-content: space-between;
            padding: 8px 16px;
            background: #f8f8f8;
            border-top: 1px solid #eee;
        }
        
        .pdf-pagination {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .pdf-pagination button {
            border: none;
            background: none;
            color: #555;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .pdf-pagination button:hover {
            background: #e0e0e0;
        }
        
        .pdf-pagination button:disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        .media-gallery {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
        }
        
        .media-gallery img, .media-gallery .gif-player {
            transition: transform 0.3s ease;
        }
        
        .media-gallery:hover img, .media-gallery:hover .gif-player {
            transform: scale(1.02);
        }
        
        .media-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            background: linear-gradient(to top, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0) 100%);
            display: flex;
            justify-content: space-between;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .media-gallery:hover .media-controls {
            opacity: 1;
        }
        
        .media-btn {
            background: rgba(255,255,255,0.25);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(4px);
            transition: all 0.2s;
        }
        
        .media-btn:hover {
            background: rgba(255,255,255,0.35);
            transform: scale(1.05);
        }

        /* Fancybox custom styles */
        .fancybox__backdrop {
            background: rgba(24, 24, 27, .77);
            backdrop-filter: blur(4px);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.umd.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@4.0/dist/fancybox.css"/>
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
            
            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Announcements Card -->
                <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-full bg-primary-100 text-primary-700">
                            <i class='bx bx-megaphone text-xl'></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-800">Announcements</h2>
                            <p class="text-sm text-gray-500">Community updates</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900"><?php echo $announcementCount; ?></span>
                            <span class="text-xs text-gray-500">Active Messages</span>
                        </div>
                    </div>
                </div>
                
                <!-- Status Card -->
                <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-full bg-<?php echo $status == 'Approved' ? 'green' : 'yellow'; ?>-100 text-<?php echo $status == 'Approved' ? 'green' : 'yellow'; ?>-700">
                            <i class='bx bx-<?php echo $status == 'Approved' ? 'check-shield' : 'time-five'; ?> text-xl'></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-800">Account Status</h2>
                            <p class="text-sm text-gray-500">Your current standing</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-semibold text-<?php echo $status == 'Approved' ? 'green' : 'yellow'; ?>-700"><?php echo $status; ?></span>
                            <span class="text-xs text-gray-500"><?php echo $status == 'Approved' ? 'All features unlocked' : 'Limited access'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Access Card -->
                <div class="bg-white rounded-xl shadow-card p-6 transition-all duration-300 hover:shadow-card-hover">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 rounded-full bg-blue-100 text-blue-700">
                            <i class='bx bx-link text-xl'></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-lg font-semibold text-gray-800">Quick Access</h2>
                            <p class="text-sm text-gray-500">Helpful resources</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="OwnerSafetyguidelines.php" class="text-sm text-blue-700 hover:underline flex items-center">
                            <i class='bx bx-shield-quarter mr-1'></i> Safety Guide
                        </a>
                        <a href="OwnerCommunityfeedback.php" class="text-sm text-blue-700 hover:underline flex items-center">
                            <i class='bx bx-message-square mr-1'></i> Feedback
                        </a>
                    </div>
                </div>
            </div>

            <!-- Announcements Section -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Community Announcements</h2>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500"><?php echo $announcementCount; ?> announcements</span>
                    </div>
                </div>
                
                <!-- Announcements List -->
                <div class="space-y-4">
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $result->data_seek(0); // Reset result pointer
                        
                        while ($row = $result->fetch_assoc()) {
                            $date = date("M j, Y", strtotime($row['created_at']));
                            $isPriority = strpos(strtolower($row['title']), 'urgent') !== false || 
                                         strpos(strtolower($row['title']), 'important') !== false;
                            
                            // Get media file information
                            $fileType = getFileType($row['media']);
                            $fileIcon = getFileIcon($fileType);
                            $fileColor = getFileColor($fileType);
                            $fileName = !empty($row['media']) ? basename($row['media']) : '';
                    ?>
                            <div class="bg-white rounded-xl shadow-card overflow-hidden transition-all duration-300 hover:shadow-card-hover">
                                <?php if ($isPriority): ?>
                                <div class="bg-red-50 px-4 py-2 border-b border-red-100 flex items-center">
                                    <i class='bx bx-error text-red-600 mr-2'></i>
                                    <span class="text-xs font-medium text-red-700">High Priority Announcement</span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center text-primary-700">
                                                <i class='bx bx-bell text-2xl'></i>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-1">
                                            <div class="flex justify-between items-start">
                                                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($row['title']); ?></h3>
                                                <span class="text-xs text-gray-500 mt-1"><?php echo $date; ?></span>
                                            </div>
                                            
                                            <div class="mt-3 text-gray-700 leading-relaxed">
                                                <p><?php echo nl2br(htmlspecialchars($row['body'])); ?></p>
                                            </div>
                                            
                                            <?php if (!empty($row['media'])): ?>
                                            <!-- Enhanced Media Viewer -->
                                            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                                                <?php if ($fileType === 'pdf'): ?>
                                                <!-- Enhanced PDF Viewer -->
                                                <div class="pdf-container">
                                                    <div class="bg-gray-50 p-3 flex justify-between items-center border-b border-gray-200">
                                                        <div class="flex items-center">
                                                            <div class="p-2 bg-<?php echo $fileColor; ?>-100 rounded-md mr-3">
                                                                <?php echo $fileIcon; ?> 
                                                            </div>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-700 truncate max-w-[200px] block">
                                                                    
                                                                </span>
                                                                <span class="text-xs text-gray-500">PDF Document</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex space-x-2">
                                                            <a href="<?php echo htmlspecialchars($row['media']); ?>" target="_blank"
                                                               class="px-3 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors flex items-center">
                                                                <i class="fas fa-external-link-alt mr-1.5"></i> Open
                                                            </a>
                                                            <a href="<?php echo htmlspecialchars($row['media']); ?>" download
                                                               class="px-3 py-1.5 text-xs bg-primary-50 hover:bg-primary-100 text-primary-700 rounded transition-colors flex items-center">
                                                                <i class="fas fa-download mr-1.5"></i> Download
                                                            </a>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="relative">
                                                        <div class="pdf-viewer h-[400px] bg-gray-100">
                                                            <iframe src="<?php echo htmlspecialchars($row['media']); ?>#toolbar=0&navpanes=0" 
                                                                    id="pdf-iframe-<?php echo $row['id']; ?>"
                                                                    class="w-full h-full border-0"
                                                                    loading="lazy"
                                                                    onload="this.parentNode.querySelector('.media-loader')?.remove()"></iframe>
                                                            <div class="media-loader"></div>
                                                        </div>
                                                        
                                                        <div class="pdf-controls">
                                                            <div class="pdf-pagination" id="pdf-controls-<?php echo $row['id']; ?>">
                                                                <button class="prev-page" disabled>
                                                                    <i class="fas fa-chevron-left"></i>
                                                                </button>
                                                                <span class="text-sm">Page <span class="current-page">1</span></span>
                                                                <button class="next-page">
                                                                    <i class="fas fa-chevron-right"></i>
                                                                </button>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button class="media-btn-sm text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded text-gray-700 zoom-in">
                                                                    <i class="fas fa-search-plus"></i>
                                                                </button>
                                                                <button class="media-btn-sm text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded text-gray-700 zoom-out">
                                                                    <i class="fas fa-search-minus"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php elseif ($fileType === 'image'): ?>
                                                <!-- Enhanced Image Viewer -->
                                                <div class="media-gallery relative">
                                                    <div class="bg-gray-50 p-3 flex justify-between items-center border-b border-gray-200">
                                                        <div class="flex items-center">
                                                            <div class="p-2 bg-<?php echo $fileColor; ?>-100 rounded-md mr-3">
                                                                <?php echo $fileIcon; ?>
                                                            </div>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-700 truncate max-w-[200px] block">
                                                                    <?php echo $fileName; ?>
                                                                </span>
                                                                <span class="text-xs text-gray-500">Image</span>
                                                            </div>
                                                        </div>
                                                        <a href="<?php echo htmlspecialchars($row['media']); ?>" download
                                                           class="px-3 py-1.5 text-xs bg-primary-50 hover:bg-primary-100 text-primary-700 rounded transition-colors flex items-center">
                                                            <i class="fas fa-download mr-1.5"></i> Download
                                                        </a>
                                                    </div>
                                                    
                                                    <div class="relative overflow-hidden h-64 bg-gray-50">
                                                        <a href="<?php echo htmlspecialchars($row['media']); ?>" 
                                                           data-fancybox="announcement-<?php echo $row['id']; ?>"
                                                           data-caption="<?php echo htmlspecialchars($row['title']); ?>"
                                                           class="block w-full h-full">
                                                            <img src="<?php echo htmlspecialchars($row['media']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                                 class="w-full h-full object-contain"
                                                                 loading="lazy"
                                                                 onload="this.parentNode.parentNode.querySelector('.media-loader')?.remove()"
                                                                 onerror="this.onerror=null; this.src='/img/placeholder-image.png'; this.classList.add('p-8');">
                                                            <div class="media-loader"></div>
                                                        </a>
                                                        
                                                        <div class="media-controls">
                                                            <span class="text-white text-xs px-2 py-1 bg-black/50 rounded-full backdrop-blur-sm">
                                                                Click to enlarge
                                                            </span>
                                                            <a href="<?php echo htmlspecialchars($row['media']); ?>" target="_blank" class="media-btn">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php elseif ($fileType === 'gif'): ?>
                                                <!-- Enhanced GIF Viewer -->
                                                <div class="media-gallery relative">
                                                    <div class="bg-gray-50 p-3 flex justify-between items-center border-b border-gray-200">
                                                        <div class="flex items-center">
                                                            <div class="p-2 bg-<?php echo $fileColor; ?>-100 rounded-md mr-3">
                                                                <?php echo $fileIcon; ?>
                                                            </div>
                                                            <div>
                                                                <span class="text-sm font-medium text-gray-700 truncate max-w-[200px] block">
                                                                    
                                                                </span>
                                                                <span class="text-xs text-gray-500">Animated GIF</span>
                                                            </div>
                                                        </div>
                                                        <a href="<?php echo htmlspecialchars($row['media']); ?>" download
                                                           class="px-3 py-1.5 text-xs bg-primary-50 hover:bg-primary-100 text-primary-700 rounded transition-colors flex items-center">
                                                            <i class="fas fa-download mr-1.5"></i> Download
                                                        </a>
                                                    </div>
                                                    
                                                    <div class="relative overflow-hidden h-64 bg-gray-50">
                                                        <div class="gif-player w-full h-full">
                                                            <a href="<?php echo htmlspecialchars($row['media']); ?>" 
                                                               data-fancybox="announcement-gif-<?php echo $row['id']; ?>"
                                                               data-caption="<?php echo htmlspecialchars($row['title']); ?>"
                                                               class="block w-full h-full">
                                                                <img src="<?php echo htmlspecialchars($row['media']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                                     class="w-full h-full object-contain"
                                                                     loading="lazy"
                                                                     onload="this.parentNode.parentNode.parentNode.querySelector('.media-loader')?.remove()"
                                                                     onerror="this.onerror=null; this.src='/img/placeholder-image.png'; this.classList.add('p-8');">
                                                            </a>
                                                            <div class="media-loader"></div>
                                                        </div>
                                                        
                                                        <div class="media-controls">
                                                            <div class="flex items-center gap-2">
                                                                <button class="media-btn toggle-play" data-state="playing">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                                <span class="text-white text-xs px-2 py-1 bg-black/50 rounded-full backdrop-blur-sm">
                                                                    Animated GIF
                                                                </span>
                                                            </div>
                                                            <a href="<?php echo htmlspecialchars($row['media']); ?>" target="_blank" class="media-btn">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php else: ?>
                                                <!-- Generic File Attachment -->
                                                <div class="bg-gray-50 p-4 flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <div class="p-3 bg-gray-200 rounded-md mr-4 text-gray-700">
                                                            <i class="fas fa-file-alt text-xl"></i>
                                                        </div>
                                                        <div>
                                                            <span class="font-medium text-gray-800 block"><?php echo $fileName; ?></span>
                                                            <span class="text-xs text-gray-500">Attached file</span>
                                                        </div>
                                                    </div>
                                                    <a href="<?php echo htmlspecialchars($row['media']); ?>" download
                                                       class="px-4 py-2 bg-primary-50 hover:bg-primary-100 text-primary-700 rounded-lg transition-colors flex items-center">
                                                        <i class="fas fa-download mr-2"></i> Download
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center">
                                                <div class="flex items-center text-xs text-gray-500">
                                                    <i class='bx bx-user-voice mr-1'></i>
                                                    <span>Community Management</span>
                                                </div>
                                                <div class="flex space-x-3">

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    <?php
                        }
                    } else {
                    ?>
                        <div class="bg-white rounded-xl shadow-card p-8 text-center">
                            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class='bx bx-message-rounded-x text-3xl text-gray-400'></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">No Announcements Available</h3>
                            <p class="text-gray-500 max-w-sm mx-auto">There are currently no announcements posted for the community. Check back soon for updates.</p>
                        </div>
                    <?php
                    }
                    ?>
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
        
        // Add animation to elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Fancybox
            Fancybox.bind("[data-fancybox]", {
                // Custom options
                Images: {
                    zoom: true,
                },
                Toolbar: {
                    display: [
                        { id: "prev", position: "center" },
                        { id: "counter", position: "center" },
                        { id: "next", position: "center" },
                        "zoom",
                        "slideshow",
                        "fullscreen",
                        "download",
                        "close",
                    ],
                },
            });
            
            // GIF Player controls
            document.querySelectorAll('.toggle-play').forEach(button => {
                button.addEventListener('click', function() {
                    const gifContainer = this.closest('.gif-player');
                    const img = gifContainer.querySelector('img');
                    const state = this.getAttribute('data-state');
                    
                    if (state === 'playing') {
                        // Pause by replacing with a still frame (need to store original src first time)
                        if (!img.getAttribute('data-original-src')) {
                            img.setAttribute('data-original-src', img.src);
                            // This is a simplified approach - for actual implementation,
                            // you'd need to generate a still frame server-side or use a library
                            img.style.opacity = '0.7';
                        } else {
                            img.src = img.getAttribute('data-still-src') || img.getAttribute('data-original-src');
                            img.style.opacity = '0.7';
                        }
                        this.setAttribute('data-state', 'paused');
                        this.innerHTML = '<i class="fas fa-play"></i>';
                    } else {
                        // Resume playing
                        img.src = img.getAttribute('data-original-src');
                        img.style.opacity = '1';
                        this.setAttribute('data-state', 'playing');
                        this.innerHTML = '<i class="fas fa-pause"></i>';
                    }
                });
            });
            
            // PDF viewer controls
            document.querySelectorAll('.pdf-viewer').forEach(viewer => {
                const iframe = viewer.querySelector('iframe');
                if (!iframe) return;
                
                const controlsId = iframe.id.replace('pdf-iframe-', 'pdf-controls-');
                const controls = document.getElementById(controlsId);
                if (!controls) return;
                
                const prevBtn = controls.querySelector('.prev-page');
                const nextBtn = controls.querySelector('.next-page');
                const currentPage = controls.querySelector('.current-page');
                
                let page = 1;
                let totalPages = 1;
                
                // This is a simplified approach since we can't directly control the PDF viewer
                // In a real implementation, you'd use PDF.js or a similar library
                
                prevBtn.addEventListener('click', () => {
                    if (page > 1) {
                        page--;
                        currentPage.textContent = page;
                        updateButtonStates();
                        // In a real implementation, you would navigate to the previous page 
                        // using the PDF viewer's API
                    }
                });
                
                nextBtn.addEventListener('click', () => {
                    if (page < totalPages) {
                        page++;
                        currentPage.textContent = page;
                        updateButtonStates();
                        // In a real implementation, you would navigate to the next page
                        // using the PDF viewer's API
                    }
                });
                
                function updateButtonStates() {
                    prevBtn.disabled = page <= 1;
                    nextBtn.disabled = page >= totalPages;
                }
                
                // In a real implementation, you would get the total pages from the PDF
                // and update the totalPages variable
            });
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to ensure proper URL path for media files
    function getMediaUrl(path) {
        // If path already includes http or https, it's already a full URL
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path;
        }
        
        // If path is just a filename without directory, prepend the announcement_media directory
        if (!path.includes('/')) {
            return 'announcement_media/' + path;
        }
        
        // Otherwise return the path as is
        return path;
    }
    
    // Update all media src attributes to ensure correct paths
    document.querySelectorAll('img[data-src], iframe[data-src]').forEach(elem => {
        const originalSrc = elem.getAttribute('data-src');
        if (originalSrc) {
            elem.src = getMediaUrl(originalSrc);
        }
    });
    
    // Update all download and view links
    document.querySelectorAll('a[data-media-url]').forEach(link => {
        const mediaPath = link.getAttribute('data-media-url');
        if (mediaPath) {
            link.href = getMediaUrl(mediaPath);
        }
    });
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

    <!-- Add help modal functions -->
    <script>
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
