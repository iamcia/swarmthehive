<?php
include 'dbconn.php';
session_start();

// Check if the user is logged in and has the correct position (Finance)
if (!isset($_SESSION['Management_Code']) || $_SESSION['position'] != 'Admin') {
    header("Location: management-index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swarm | Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/admin_style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="./css/adm-comminsights-style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Add Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Add Alpine.js for easier modal handling -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="./img/logo swarm.png" alt="Logo" class="logo">
                <span class="logo-text">SWARM</span>
            </div>

            <div class="menu-title">
                <i class='bx bxs-group'></i>
                <span>Admin</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="adm-dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-usermanage.php">
                            <i class='bx bx-user'></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-manageannounce.php">
                            <i class='bx bx-notification'></i>
                            <span>Announcement</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-servicerequest.php">
                            <i class='bx bx-file'></i>
                            <span>Service Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-financialrec.php">
                            <i class='bx bx-wallet'></i>
                            <span>Finance Records</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="#">
                            <i class='bx bx-chat'></i>
                            <span>Community Insights</span>
                        </a>
                    </li>
                    <li>
                        <a href="adm-auditlogs.php">
                            <i class='bx bx-file-blank'></i>
                            <span>Audit Logs</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="man-logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

      <!-- --------------
        end sidebar
      -------------------- -->

      <!-- --------------
        start main part
      --------------- -->

      <main>
        <?php
        // Database connection
        include 'dbconn.php';
        
        // Default filter values
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $concern_type_filter = isset($_GET['concern_type']) ? $_GET['concern_type'] : '';
        $search_query = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Build SQL query with filters
        $sql = "SELECT * FROM ownertenantconcerns WHERE 1=1";
        
        if (!empty($status_filter)) {
            $sql .= " AND concern_status = '$status_filter'";
        }
        
        if (!empty($concern_type_filter)) {
            $sql .= " AND concern_type = '$concern_type_filter'";
        }
        
        if (!empty($search_query)) {
            $sql .= " AND (concern_details LIKE '%$search_query%' OR unit_number LIKE '%$search_query%' OR user_email LIKE '%$search_query%')";
        }
        
        $sql .= " ORDER BY submitted_at DESC";
        
        $result = mysqli_query($conn, $sql);
        
        // Get unique concern types for filter dropdown
        $types_sql = "SELECT DISTINCT concern_type FROM ownertenantconcerns";
        $types_result = mysqli_query($conn, $types_sql);
        $concern_types = [];
        
        while ($type = mysqli_fetch_assoc($types_result)) {
            $concern_types[] = $type['concern_type'];
        }
        ?>

        <div class="bg-white p-6 rounded-lg shadow-md w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Community Concerns & Insights</h1>
                <div class="flex space-x-2">
                    <a href="?export=csv" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded">
                        <i class='bx bx-export mr-2'></i> Export CSV
                    </a>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <form action="" method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="">All Statuses</option>
                            <option value="Unresolved" <?php echo $status_filter == 'Unresolved' ? 'selected' : ''; ?>>Unresolved</option>
                            <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved" <?php echo $status_filter == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Concern Type</label>
                        <select name="concern_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <option value="">All Types</option>
                            <?php foreach ($concern_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $concern_type_filter == $type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[240px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by details, unit, email..." 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-filter-alt mr-2'></i> Filter
                        </button>
                    </div>
                    
                    <?php if (!empty($status_filter) || !empty($concern_type_filter) || !empty($search_query)): ?>
                    <div class="flex items-end">
                        <a href="adm-comminsights.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                            <i class='bx bx-reset mr-2'></i> Reset
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Concerns Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while ($concern = mysqli_fetch_assoc($result)) {
                        // Determine status class for badge
                        $status_class = '';
                        switch ($concern['concern_status']) {
                            case 'Resolved':
                                $status_class = 'bg-green-100 text-green-800';
                                break;
                            case 'In Progress':
                                $status_class = 'bg-yellow-100 text-yellow-800';
                                break;
                            default:
                                $status_class = 'bg-red-100 text-red-800';
                                break;
                        }
                        
                        // Format date
                        $date = new DateTime($concern['submitted_at']);
                        $formatted_date = $date->format('M d, Y - h:i A');
                ?>
                <div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-4 border-b">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-lg text-gray-800"><?php echo htmlspecialchars($concern['concern_type']); ?></h3>
                                <p class="text-sm text-gray-600">Unit: <?php echo htmlspecialchars($concern['unit_number']); ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($concern['concern_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <p class="text-gray-700 mb-4 text-sm h-20 overflow-y-auto">
                            <?php echo htmlspecialchars($concern['concern_details']); ?>
                        </p>
                        
                        <?php if (!empty($concern['media_path'])): ?>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php 
                            $media_paths = explode(',', $concern['media_path']);
                            foreach ($media_paths as $path): 
                                $file_extension = pathinfo($path, PATHINFO_EXTENSION);
                                $is_image = in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif']);
                            ?>
                            <?php if ($is_image): ?>
                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="block w-16 h-16 bg-gray-100 rounded overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($path); ?>" alt="Concern media" class="w-full h-full object-cover">
                                </a>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="block w-16 h-16 bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                                    <i class='bx bx-file text-gray-500 text-2xl'></i>
                                </a>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-xs text-gray-500 mb-4">
                            <div class="mb-1"><span class="font-medium">From:</span> <?php echo htmlspecialchars($concern['user_email']); ?></div>
                            <div class="mb-1"><span class="font-medium">Contact:</span> <?php echo htmlspecialchars($concern['user_number']); ?></div>
                            <div><span class="font-medium">Submitted:</span> <?php echo $formatted_date; ?></div>
                        </div>
                        
                        <div class="flex justify-between pt-3 border-t">
                            <!-- Change the link to a button that triggers the modal -->
                            <button 
                                onclick="openConcernModal(<?php echo $concern['ID']; ?>)" 
                                class="text-blue-600 hover:text-blue-800 text-sm cursor-pointer">
                                <i class='bx bx-show mr-1'></i> View Details
                            </button>
                            <div class="flex space-x-2">
                                <?php if ($concern['concern_status'] != 'Resolved'): ?>
                                <a href="update-concern.php?id=<?php echo $concern['ID']; ?>&action=resolve" class="text-green-600 hover:text-green-800 text-sm">
                                    <i class='bx bx-check-circle mr-1'></i> Resolve
                                </a>
                                <?php endif; ?>
                                <?php if ($concern['concern_status'] == 'Unresolved'): ?>
                                <a href="update-concern.php?id=<?php echo $concern['ID']; ?>&action=progress" class="text-yellow-600 hover:text-yellow-800 text-sm">
                                    <i class='bx bx-time mr-1'></i> In Progress
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div class="col-span-full p-8 text-center bg-gray-50 rounded-lg">
                    <i class='bx bx-search-alt text-gray-400 text-4xl mb-2'></i>
                    <p class="text-gray-500 text-lg">No concerns found matching your criteria.</p>
                </div>
                <?php } ?>
            </div>
        </div>

      </main>
      <!------------------
         end main
        ------------------->

    <!-- Concern Details Modal -->
    <div id="concernModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden overflow-y-auto px-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl my-8 max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center border-b p-3 sticky top-0 bg-white z-10">
                <h2 class="text-xl font-bold text-gray-800">Concern Details</h2>
                <button onclick="closeConcernModal()" class="text-gray-500 hover:text-gray-700">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>
            
            <div id="modalContent" class="p-4 overflow-y-auto flex-grow">
                <div class="flex justify-center items-center h-40">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>
            </div>
            
            <div class="border-t p-3 flex justify-end bg-gray-50 sticky bottom-0">
                <button onclick="closeConcernModal()" class="bg-gray-500 hover:bg-gray-600 text-white py-1.5 px-3 rounded mr-2 text-sm">
                    Close
                </button>
                <button onclick="printConcernDetails()" class="bg-blue-500 hover:bg-blue-600 text-white py-1.5 px-3 rounded text-sm">
                    <i class='bx bx-printer mr-1'></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Print-friendly div for JavaScript print function -->
    <div id="print-version" class="hidden"></div>

</div>

<script>
    // Variables
    let currentConcernId = null;
    
    // Function to open the modal and load concern details
    function openConcernModal(concernId) {
        currentConcernId = concernId;
        document.getElementById('concernModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling behind modal
        
        // Adjust modal height
        adjustModalHeight();
        
        // Show loading spinner
        document.getElementById('modalContent').innerHTML = `
            <div class="flex justify-center items-center h-40">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        `;
        
        // Fetch concern details using AJAX
        fetch('get-concern-details.php?id=' + concernId)
            .then(response => {
                // Check if response is ok before parsing JSON
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error('Server error: ' + text);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                            Error: ${data.error}
                        </div>
                    `;
                    return;
                }
                
                // Render the concern details
                renderConcernDetails(data);
            })
            .catch(error => {
                document.getElementById('modalContent').innerHTML = `
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <p class="font-bold">Error loading concern details.</p>
                        <p class="text-sm">${error.message || 'Unknown error occurred'}</p>
                    </div>
                `;
                console.error('Error fetching concern details:', error);
            });
    }
    
    // Function to close the modal
    function closeConcernModal() {
        document.getElementById('concernModal').classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Function to render concern details in the modal
    function renderConcernDetails(data) {
        const concern = data.concern;
        const resident = data.resident_info || null;
        
        // Determine status class for badge - using normalized_status which prioritizes concern_status
        const status = concern.normalized_status || concern.concern_status || concern.status || 'Unresolved';
        let statusClass = '';
        switch (status) {
            case 'Resolved':
                statusClass = 'bg-green-100 text-green-800';
                break;
            case 'In Progress':
                statusClass = 'bg-yellow-100 text-yellow-800';
                break;
            default:
                statusClass = 'bg-red-100 text-red-800';
                break;
        }
        
        // Format submitted date
        const submittedDate = new Date(concern.submitted_at);
        const formattedDate = submittedDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
        
        // Generate HTML for media/attachments
        let mediaHtml = '';
        if (concern.processed_media_path || concern.media_path) {
            const mediaPath = concern.processed_media_path || concern.media_path;
            if (mediaPath) {
                const mediaFiles = mediaPath.split(',');
                mediaHtml = `
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-500 mb-2">Attachments</h3>
                        <div class="flex flex-wrap gap-3">
                `;
                
                mediaFiles.forEach(path => {
                    // Ensure path is not empty
                    if (!path.trim()) return;
                    
                    // Process path to ensure it's correctly formatted
                    let processedPath = path.trim();
                    if (!processedPath.startsWith('http') && !processedPath.startsWith('/') && !processedPath.startsWith('concern_media/')) {
                        processedPath = 'concern_media/' + processedPath;
                    }
                    
                    const fileExtension = processedPath.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
                    
                    if (isImage) {
                        mediaHtml += `
                            <a href="${processedPath}" target="_blank" class="block w-32 h-32 bg-gray-100 rounded overflow-hidden">
                                <img src="${processedPath}" alt="Concern media" class="w-full h-full object-cover">
                            </a>
                        `;
                    } else {
                        mediaHtml += `
                            <a href="${processedPath}" download class="flex items-center p-3 border border-gray-200 rounded bg-gray-50 hover:bg-gray-100">
                                <i class='bx bx-file text-gray-500 text-2xl mr-2'></i>
                                <span class="text-sm text-gray-700">Download File</span>
                            </a>
                        `;
                    }
                });
                
                mediaHtml += `
                        </div>
                    </div>
                `;
            }
        }
        
        // Generate HTML for signature if exists
        let signatureHtml = '';
        if (concern.signature) {
            let signaturePath = concern.signature;
            // If signature doesn't already have a path, add one
            if (!signaturePath.startsWith('http') && !signaturePath.startsWith('/')) {
                signaturePath = 'concern_media/' + signaturePath;
            }
            
            signatureHtml = `
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">Signature</h3>
                    <div class="bg-white p-4 rounded border border-gray-200 max-w-xs">
                        <img src="${signaturePath}" alt="Signature" class="w-full">
                    </div>
                </div>
            `;
        }

        // Generate HTML for resident code if exists
        let residentCodeHtml = '';
        if (concern.Resident_Code) {
            residentCodeHtml = `
                <div class="mb-4 bg-gray-100 p-3 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700">Resident Code</h3>
                    <p class="text-lg font-mono">${concern.Resident_Code}</p>
                </div>
            `;
        }
        
        // Generate HTML for resident info if exists
        let residentHtml = '';
        if (resident) {
            residentHtml = `
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2">Additional Resident Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            `;
            
            for (const [key, value] of Object.entries(resident)) {
                if (key !== 'password' && key !== 'id' && value) {
                    const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    residentHtml += `
                        <div>
                            <span class="block text-xs text-blue-600">${formattedKey}</span>
                            <span class="text-sm">${value}</span>
                        </div>
                    `;
                }
            }
            
            residentHtml += `
                    </div>
                </div>
            `;
        }
        
        // Build the complete HTML - make content more compact
        const html = `
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="flex flex-wrap justify-between mb-3">
                    <div class="mb-2 md:mb-0">
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded mb-1">ID: ${concern.ID}</span>
                        <h2 class="text-lg font-bold text-gray-800">${concern.concern_type}</h2>
                        <p class="text-sm text-gray-600">Unit: ${concern.unit_number}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${statusClass}">
                        ${status}
                    </span>
                </div>
                
                ${residentCodeHtml}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 mb-0.5">Submitted By</h3>
                        <p class="text-sm text-gray-800">${concern.user_type}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 mb-0.5">Email</h3>
                        <p class="text-sm text-gray-800">${concern.user_email}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 mb-0.5">Contact Number</h3>
                        <p class="text-sm text-gray-800">${concern.user_number}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 mb-0.5">Submitted At</h3>
                        <p class="text-sm text-gray-800">${formattedDate}</p>
                    </div>
                </div>
                
                ${residentHtml}
                
                <div class="mb-4">
                    <h3 class="text-xs font-semibold text-gray-500 mb-1">Concern Details</h3>
                    <div class="bg-white p-3 rounded border border-gray-200">
                        <p class="text-sm text-gray-800 whitespace-pre-line">${concern.concern_details}</p>
                    </div>
                </div>
                
                ${mediaHtml}
                
                ${signatureHtml}
                
                <div class="flex flex-wrap gap-2 mt-4">
                    ${status !== 'Resolved' ? `
                        <a href="update-concern.php?id=${concern.ID}&action=resolve" class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded text-sm">
                            <i class='bx bx-check-circle mr-1'></i> Mark as Resolved
                        </a>
                    ` : ''}
                    
                    ${status === 'Unresolved' ? `
                        <a href="update-concern.php?id=${concern.ID}&action=progress" class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded text-sm">
                            <i class='bx bx-time mr-1'></i> Mark as In Progress
                        </a>
                    ` : ''}
                </div>
            </div>
        `;
        
        // Update modal content
        document.getElementById('modalContent').innerHTML = html;
        
        // Also update the print version
        updatePrintVersion(concern);
    }
    
    // Function to update print version
    function updatePrintVersion(concern) {
        const printDiv = document.getElementById('print-version');
        
        printDiv.innerHTML = `
            <style>
                @media print {
                    body { font-family: Arial, sans-serif; }
                    .header { text-align: center; margin-bottom: 20px; }
                    .section { margin-bottom: 15px; }
                    .section-title { font-weight: bold; margin-bottom: 5px; }
                    .concern-details { white-space: pre-line; }
                }
            </style>
            <div class="header">
                <h1>Concern Report #${concern.ID}</h1>
                <p>Generated on ${new Date().toLocaleDateString()} - ${new Date().toLocaleTimeString()}</p>
            </div>
            
            <div class="section">
                <div class="section-title">Concern Type:</div>
                <div>${concern.concern_type}</div>
            </div>
            
            <div class="section">
                <div class="section-title">Status:</div>
                <div>${concern.concern_status || concern.status || 'Unresolved'}</div>
            </div>
            
            <div class="section">
                <div class="section-title">Submitted By:</div>
                <div>${concern.user_type} (${concern.user_email})</div>
                <div>Unit: ${concern.unit_number}</div>
                <div>Contact: ${concern.user_number}</div>
                <div>Date: ${new Date(concern.submitted_at).toLocaleString()}</div>
            </div>
            
            <div class="section">
                <div class="section-title">Concern Details:</div>
                <div class="concern-details">${concern.concern_details}</div>
            </div>
        `;
    }
    
    // Function to print concern details
    function printConcernDetails() {
        const printContents = document.getElementById('print-version').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // Re-establish event listeners after printing
        if (currentConcernId) {
            openConcernModal(currentConcernId);
        }
    }
    
    // Event listener to close modal when clicking outside
    document.getElementById('concernModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConcernModal();
        }
    });
    
    // Event listener for ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !document.getElementById('concernModal').classList.contains('hidden')) {
            closeConcernModal();
        }
    });
    
    // Add this function to handle modal dimensions on open
    function adjustModalHeight() {
        const modalContent = document.getElementById('modalContent');
        const windowHeight = window.innerHeight;
        const maxHeight = windowHeight * 0.7; // 70% of window height
        
        // Set a reasonable max-height for the content area
        if (modalContent) {
            modalContent.style.maxHeight = `${maxHeight}px`;
        }
    }
    
    // Add window resize event listener to adjust modal size on window resize
    window.addEventListener('resize', function() {
        if (!document.getElementById('concernModal').classList.contains('hidden')) {
            adjustModalHeight();
        }
    });
</script>
</body>
</html>