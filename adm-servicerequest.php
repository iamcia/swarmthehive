<?php
include 'dbconn.php';
include 'service-request-queries.php'; // Include the new queries file

session_start();

// Helper function to format dates
function formatDate($dateStr) {
    $date = new DateTime($dateStr);
    return $date->format('F j, Y'); // E.g., "January 1, 2023"
}

// Get filter parameters (default to all types if no filter specified)
$serviceType = isset($_GET['service_type']) ? $_GET['service_type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get service requests with details and statistics
$serviceData = getServiceRequests($conn, $serviceType, $status, $dateFrom, $dateTo, $search);
$serviceRequests = $serviceData['requests'];
$statistics = $serviceData['stats'];

// Extract statistics
$totalCount = $statistics['total'];
$pendingCount = $statistics['pending'];
$approvedCount = $statistics['approved'];
$completedCount = $statistics['completed'];
$rejectedCount = $statistics['rejected'];

// Get service types for dropdown
$serviceTypeOptions = getServiceTypeOptions();

// Handle status update if submitted via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'updateStatus') {
    $requestId = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    $rejectReason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';
    $checkerName = isset($_POST['checker_name']) ? $_POST['checker_name'] : '';
    
    $result = updateServiceRequestStatus($conn, $requestId, $newStatus, $rejectReason, $checkerName);
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit;
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
    <link rel="stylesheet" href="./css/adm-servicerequest-style.css?v=<?php echo time(); ?>">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Add SweetAlert2 CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'blue-primary': '#2196F3',
                        'blue-dark': '#1976D2',
                        'green-primary': '#4CAF50',
                        'orange-primary': '#FF9800',
                        'red-primary': '#F44336',
                        'gray-light': '#f5f5f5',
                        'gray-border': '#e0e0e0',
                    },
                    keyframes: {
                        modalFadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        spin: {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' }
                        }
                    },
                    animation: {
                        'modal-fade-in': 'modalFadeIn 0.3s ease-out',
                        'spinner': 'spin 1s linear infinite'
                    }
                },
            },
        }
    </script>
    <!-- Add FontAwesome for file icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <li class="active">
                        <a href="#">
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
                    <li>
                        <a href="adm-comminsights.php">
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
                    <li>
                        <a href="adm-systemsettings.php">
                            <i class='bx bx-cog'></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <div class="divider"></div>
                    <li>
                        <a href="logout.php" class="nav-item logout">
                            <i class='bx bx-log-out'></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
      <!-- --------------
        start main part
      --------------- -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
        <div class="flex justify-between items-center px-6 py-4 bg-white border-b">
            <h1 class="text-2xl font-semibold text-gray-800">Service Requests Management</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Search requests..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-primary">
                    <i class='bx bx-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                </div>

            </div>
        </div>

        <!-- Service Requests Dashboard -->
        <div class="p-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Requests Card -->
                <div class="bg-blue-primary rounded-lg shadow-md overflow-hidden transform transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex justify-between items-center p-5 text-white">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Total Requests</h3>
                            <h2 class="text-3xl font-bold mt-1"><?php echo $totalCount; ?></h2>
                        </div>
                        <div>
                            <i class='bx bx-file text-3xl'></i>
                        </div>
                    </div>
                </div>
                <!-- Pending Card -->
                <div class="bg-orange-primary rounded-lg shadow-md overflow-hidden transform transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex justify-between items-center p-5 text-white">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Approved</h3>
                            <h2 class="text-3xl font-bold mt-1"><?php echo $pendingCount; ?></h2>
                        </div>
                        <div>
                            <i class='bx bx-time-five text-3xl'></i>
                        </div>
                    </div>
                </div>
                <!-- Approval Card (changed from Approved) -->
                <div class="bg-green-primary rounded-lg shadow-md overflow-hidden transform transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex justify-between items-center p-5 text-white">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Approval</h3>
                            <h2 class="text-3xl font-bold mt-1"><?php echo $approvedCount; ?></h2>
                        </div>
                        <div>
                            <i class='bx bx-check-circle text-3xl'></i>
                        </div>
                    </div>
                </div>
                <!-- Rejected Card -->
                <div class="bg-red-primary rounded-lg shadow-md overflow-hidden transform transition-transform duration-300 hover:-translate-y-1 hover:shadow-lg">
                    <div class="flex justify-between items-center p-5 text-white">
                        <div>
                            <h3 class="text-sm font-medium opacity-90">Rejected</h3>
                            <h2 class="text-3xl font-bold mt-1"><?php echo $rejectedCount; ?></h2>
                        </div>
                        <div>
                            <i class='bx bx-x-circle text-3xl'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex flex-wrap gap-2 mb-6">
                <button class="px-5 py-2 rounded-full font-medium transition-colors <?php echo empty($status) ? 'bg-blue-primary text-white' : 'bg-gray-light text-gray-700 hover:bg-gray-border'; ?>" data-filter="all">All Requests</button>
                <button class="px-5 py-2 rounded-full font-medium transition-colors <?php echo $status == 'Pending' ? 'bg-blue-primary text-white' : 'bg-gray-light text-gray-700 hover:bg-gray-border'; ?>" data-filter="pending">Pending</button>
                <button class="px-5 py-2 rounded-full font-medium transition-colors <?php echo $status == 'Approved' ? 'bg-blue-primary text-white' : 'bg-gray-light text-gray-700 hover:bg-gray-border'; ?>" data-filter="approved">Approved</button>
                <button class="px-5 py-2 rounded-full font-medium transition-colors <?php echo $status == 'Completed' ? 'bg-blue-primary text-white' : 'bg-gray-light text-gray-700 hover:bg-gray-border'; ?>" data-filter="completed">Completed</button>
                <button class="px-5 py-2 rounded-full font-medium transition-colors <?php echo $status == 'Rejected' ? 'bg-blue-primary text-white' : 'bg-gray-light text-gray-700 hover:bg-gray-border'; ?>" data-filter="rejected">Rejected</button>
            </div>

            <!-- Advanced Filters -->
            <div class="flex flex-wrap items-center gap-4 p-4 bg-gray-light rounded-lg mb-8">
                <form id="filter-form" method="get" class="w-full flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-gray-700 font-medium">Service Type:</label>
                        <select name="service_type" id="service-type-filter" class="border border-gray-border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-primary bg-white">
                            <option value="">All Types</option>
                            <?php foreach ($serviceTypeOptions as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $serviceType === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-gray-700 font-medium">Date Range:</label>
                        <input type="date" name="date_from" id="date-from" value="<?php echo $dateFrom; ?>" class="border border-gray-border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-primary bg-white">
                        <span class="text-gray-700">to</span>
                        <input type="date" name="date_to" id="date-to" value="<?php echo $dateTo; ?>" class="border border-gray-border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-primary bg-white">
                    </div>
                    <input type="hidden" name="status" id="status-filter" value="<?php echo $status; ?>">
                    <input type="text" name="search" id="search-filter" value="<?php echo $search; ?>" placeholder="Search..." class="border border-gray-border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-primary bg-white">
                    <button type="submit" id="apply-filters" class="flex items-center gap-1 bg-blue-primary text-white px-4 py-2 rounded hover:bg-blue-dark transition-colors ml-auto">
                        <i class='bx bx-filter'></i> Apply Filters
                    </button>
                </form>
            </div>

            <!-- Service Requests List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php if (count($serviceRequests) > 0): ?>
                    <?php foreach ($serviceRequests as $request): ?>
                        <?php 
                            // Determine status class colors
                            $statusClass = 'gray';
                            if (isset($request['status'])) {
                                $status = strtolower($request['status']);
                                if ($status == 'pending') {
                                    $statusClass = 'green'; // Change from orange to green for Pending status
                                } else if ($status == 'approval') {
                                    $statusClass = 'green';
                                } else if ($status == 'completed' || $status == 'complete') {
                                    $statusClass = 'blue';
                                } else if ($status == 'rejected' || $status == 'reject') {
                                    $statusClass = 'red';
                                }
                            }
                            // Create display status - show "Approved" when status is "Pending"
                            $displayStatus = isset($request['status']) ? $request['status'] : 'Unknown';
                            if (strtolower($displayStatus) == 'pending') {
                                $displayStatus = 'Approved';
                            }
                            // Determine which flow this service type follows
                            $needsPendingState = in_array($request['service_type'], ['GuestCheckIn', 'gatepass', 'VisitorPass', 'WorkPermit']);
                            $status = strtolower($request['status'] ?? '');
                        ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-<?php echo $statusClass; ?>-primary transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex justify-between items-center p-4 bg-gray-50">
                                <span class="text-gray-600 font-medium">#<?php echo $request['request_id']; ?></span>
                                <div class="flex items-center">
                                    <?php if ($needsPendingState && $status == 'approval'): ?>
                                        <div class="mr-2 text-xs text-gray-500">
                                            <i class="bx bx-right-arrow-alt"></i>
                                            <span>Needs Acceptance</span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="px-3 py-1 bg-<?php echo $statusClass; ?>-100 text-<?php echo $statusClass; ?>-800 rounded text-xs font-medium">
                                        <?php echo ucfirst($displayStatus); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3"><?php echo $request['service_type']; ?> Request</h3>
                                <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                                    <div class="flex items-center gap-1 text-gray-600">
                                        <i class='bx bx-user'></i>
                                        <span><?php echo $request['First_Name'] . ' ' . $request['Last_Name']; ?></span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-600">
                                        <i class='bx bx-calendar'></i>
                                        <span><?php echo isset($request['created_at']) ? formatDate($request['created_at']) : 'N/A'; ?></span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-600">
                                        <i class='bx bx-building'></i>
                                        <span>Tower <?php echo $request['Tower'] . ', Unit ' . $request['Unit_Number']; ?></span>
                                    </div>
                                    <div class="flex items-center gap-1 text-gray-600">
                                        <i class='bx bx-phone'></i>
                                        <span><?php echo $request['Mobile_Number']; ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($request['reject_reason'])): ?>
                                    <p class="text-gray-600 text-sm bg-red-50 p-2 rounded">
                                        <strong>Rejection reason:</strong> <?php echo $request['reject_reason']; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($needsPendingState && $status == 'approval'): ?>
                                    <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded-md text-sm text-yellow-700">
                                        <div class="flex items-center">
                                            <i class='bx bx-info-circle mr-2'></i>
                                            <span>This request needs to be accepted to proceed to processing</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex justify-between items-center p-4 border-t border-gray-100">
                                <button class="text-blue-primary font-medium flex items-center gap-1 view-details" data-id="<?php echo $request['request_id']; ?>">
                                    <i class='bx bx-show'></i> View Details
                                </button>
                                <div class="flex gap-2">
                                    <?php 
                                    // For services with Approval -> Completed flow (non-pending state services)
                                    if (!$needsPendingState): 
                                        if ($status == 'approval'): ?>
                                            <button class="complete-btn flex items-center gap-1 bg-blue-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-check-double'></i> Complete
                                            </button>
                                            <!-- Also show reject button for Approval status -->
                                            <button class="reject-btn flex items-center gap-1 bg-red-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-x'></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    <?php 
                                    // For services with Approval -> Pending -> Completed flow
                                    else: 
                                        if ($status == ''): ?>
                                            <button class="approve-btn flex items-center gap-1 bg-green-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-check'></i> Approve
                                            </button>
                                            <button class="reject-btn flex items-center gap-1 bg-red-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-x'></i> Reject
                                            </button>
                                        <?php elseif ($status == 'approval'): ?>
                                            <button class="accept-btn flex items-center gap-1 bg-orange-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-check-circle'></i> Accept Request
                                            </button>
                                        <?php elseif ($status == 'pending'): ?>
                                            <button class="complete-btn flex items-center gap-1 bg-blue-primary text-white px-2 py-1 rounded text-xs" data-id="<?php echo $request['request_id']; ?>">
                                                <i class='bx bx-check-double'></i> Mark as Complete
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 p-8 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <i class='bx bx-file text-6xl text-gray-300 mb-3'></i>
                            <h3 class="text-xl font-medium text-gray-700 mb-1">No Service Requests Found</h3>
                            <p class="text-gray-500">There are no service requests matching your filters.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>


        </div>

        <!-- Modal for Request Details -->
        <div id="request-modal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 hidden">
            <div class="bg-white rounded-lg w-11/12 max-w-3xl max-h-[90vh] overflow-y-auto animate-modal-fade-in">
                <div class="flex justify-between items-center p-4 border-b border-gray-200">
                    <h2 class="text-xl font-medium flex items-center gap-2">
                        Request Details <span class="text-gray-500 font-normal">#SR-2023-001</span>
                    </h2>
                    <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-2 p-3 rounded bg-orange-100 text-orange-800 mb-6">
                        <i class='bx bx-time-five'></i>
                        <span>Status: Pending Review</span>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Request Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Submitted By:</label>
                                <p class="text-gray-800">John Doe</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Unit/Location:</label>
                                <p class="text-gray-800">Unit 204</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Request Date:</label>
                                <p class="text-gray-800">June 15, 2023</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Service Type:</label>
                                <p class="text-gray-800">Maintenance</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Priority:</label>
                                <p class="text-gray-800">High</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Contact Number:</label>
                                <p class="text-gray-800">(555) 123-4567</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Description</h3>
                        <p class="text-gray-700 leading-relaxed">
                            Water leaking from ceiling in the bathroom. The leak appears to be coming from the unit above.
                            I've placed a bucket to catch the water for now, but need this fixed as soon as possible as it's
                            causing damage to the ceiling paint. Available any day after 4pm for access to the apartment.
                        </p>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Attachments</h3>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                                <i class="fas fa-file-image text-blue-primary"></i>
                                <span class="text-gray-700">leak-photo-1.jpg</span>
                                <a href="#" class="text-blue-primary ml-auto">View</a>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                                <i class="fas fa-file-image text-blue-primary"></i>
                                <span class="text-gray-700">leak-photo-2.jpg</span>
                                <a href="#" class="text-blue-primary ml-auto">View</a>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                                <i class="fas fa-file-pdf text-blue-primary"></i>
                                <span class="text-gray-700">previous-repair-doc.pdf</span>
                                <a href="#" class="text-blue-primary ml-auto">Download</a>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Activity Log</h3>
                        <div class="space-y-4">
                            <div class="flex">
                                <div class="relative">
                                    <div class="w-8 h-8 bg-blue-primary rounded-full flex justify-center items-center text-white z-10">
                                        <i class='bx bx-plus-circle'></i>
                                    </div>
                                    <div class="absolute top-8 bottom-0 left-1/2 w-0.5 bg-gray-200 -translate-x-1/2"></div>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-gray-800">Request Submitted</p>
                                    <p class="text-sm text-gray-600">Submitted by John Doe</p>
                                    <p class="text-sm text-gray-500">June 15, 2023 - 10:23 AM</p>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="relative">
                                    <div class="w-8 h-8 bg-blue-primary rounded-full flex justify-center items-center text-white z-10">
                                        <i class='bx bx-envelope'></i>
                                    </div>
                                    <div class="absolute top-8 bottom-0 left-1/2 w-0.5 bg-gray-200 -translate-x-1/2"></div>
                                </div>
                                <div class="ml-4">
                                    <p class="font-medium text-gray-800">Notification Sent</p>
                                    <p class="text-sm text-gray-600">Automatic email confirmation sent to requester</p>
                                    <p class="text-sm text-gray-500">June 15, 2023 - 10:25 AM</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center p-4 border-t border-gray-200">
                    <div class="flex gap-2">
                        <button id="modal-approve-btn" class="px-4 py-2 bg-blue-primary text-white rounded flex items-center gap-1 hover:bg-blue-dark transition-colors">
                            <i class='bx bx-check'></i> Approve Request
                        </button>
                        <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors">
                            <i class='bx bx-x'></i> Reject Request
                        </button>
                    </div>
                    <button class="px-4 py-2 bg-gray-light text-gray-700 rounded hover:bg-gray-border transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Simple JavaScript for tab switching and modal functionality -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Tab switching
                const tabBtns = document.querySelectorAll('[data-filter]');
                tabBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const filter = this.getAttribute('data-filter');
                        document.getElementById('status-filter').value = 
                            filter === 'all' ? '' : 
                            filter === 'pending' ? 'Pending' : 
                            filter === 'approved' ? 'Approved' : 
                            filter === 'completed' ? 'Completed' : 
                            filter === 'rejected' ? 'Rejected' : '';
                        document.getElementById('filter-form').submit();
                    });
                });

                // Modal functionality - updated to support different service types
                const viewBtns = document.querySelectorAll('.view-details');
                const modal = document.getElementById('request-modal');
                const modalContent = modal.querySelector('.animate-modal-fade-in');
                
                viewBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.getAttribute('data-id');
                        const serviceType = this.closest('.bg-white').querySelector('h3').textContent.split(' ')[0];

                        // Show loading state
                        modalContent.innerHTML = `
                            <div class="flex justify-center items-center p-20">
                                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                            </div>
                        `;
                        modal.classList.remove('hidden');

                        // Load the appropriate modal based on service type
                        let modalUrl = '';
                        switch(serviceType) {
                            case 'MoveIn':
                                modalUrl = 'modal-movein.php';
                                break;
                            case 'MoveOut':
                                modalUrl = 'modal-moveout.php';
                                break;
                            case 'GuestCheckIn':
                                modalUrl = 'modal-guestcheckin.php';
                                break;
                            case 'PetRegistration':
                                modalUrl = 'modal-petregistration.php';
                                break;
                            case 'VisitorPass':
                                modalUrl = 'modal-visitorpass.php';
                                break;
                            case 'WorkPermit':
                                modalUrl = 'modal-workpermit.php';
                                break;
                            case 'AmenityReservation':
                                modalUrl = 'modal-amenityreservation.php';
                                break;
                            case 'poolreserve':
                                modalUrl = 'modal-poolreserve.php';
                                break;
                            case 'gatepass':
                                modalUrl = 'modal-gatepass.php';
                                break;
                            // Add more cases for other service types as you create them
                            default:
                                // Use a default modal for now
                                modalUrl = 'modal-default.php';
                        }

                        // Fetch the modal content
                        fetch(`${modalUrl}?request_id=${requestId}`)
                            .then(response => response.text())
                            .then(html => {
                                modalContent.innerHTML = html;
                                // Execute any scripts in the loaded content
                                const scripts = modalContent.querySelectorAll('script');
                                scripts.forEach(script => {
                                    const newScript = document.createElement('script');
                                    Array.from(script.attributes).forEach(attr => {
                                        newScript.setAttribute(attr.name, attr.value);
                                    });
                                    newScript.appendChild(document.createTextNode(script.innerHTML));
                                    script.parentNode.replaceChild(newScript, script);
                                });
                            })
                            .catch(error => {
                                console.error('Error loading modal:', error);
                                modalContent.innerHTML = `
                                    <div class="p-5 text-center">
                                        <p class="text-red-500">Error loading request details. Please try again.</p>
                                        <button class="mt-4 px-4 py-2 bg-gray-light text-gray-700 rounded hover:bg-gray-border transition-colors close-modal">
                                            Close
                                        </button>
                                    </div>
                                `;
                                document.querySelector('.close-modal').addEventListener('click', () => {
                                    modal.classList.add('hidden');
                                });
                            });
                    });
                });

                // Close modal if clicked outside the content
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.classList.add('hidden');
                    }
                });

                // Approve button functionality
                const approveBtns = document.querySelectorAll('.approve-btn');
                approveBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.getAttribute('data-id');
                        updateStatus(requestId, 'Approval');
                    });
                });

                // Accept button functionality (transitions from Approval to Pending for special service types)
                const acceptBtns = document.querySelectorAll('.accept-btn');
                acceptBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.getAttribute('data-id');
                        updateStatus(requestId, 'Pending');
                    });
                });

                // Reject button functionality
                const rejectBtns = document.querySelectorAll('.reject-btn');
                rejectBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.getAttribute('data-id');
                        // Replace prompt with SweetAlert2
                        Swal.fire({
                            title: 'Reject Request',
                            text: 'Please provide a reason for rejection:',
                            input: 'textarea',
                            inputPlaceholder: 'Type your rejection reason here...',
                            showCancelButton: true,
                            confirmButtonText: 'Reject',
                            confirmButtonColor: '#F44336',
                            cancelButtonText: 'Cancel',
                            inputValidator: (value) => {
                                if (!value) {
                                    return 'You need to provide a reason for rejection!';
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateStatus(requestId, 'Rejected', result.value);
                            }
                        });
                    });
                });

                // Complete button functionality
                const completeBtns = document.querySelectorAll('.complete-btn');
                completeBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const requestId = this.getAttribute('data-id');
                        updateStatus(requestId, 'Completed');
                    });
                });

                // Function to update status via AJAX
                function updateStatus(requestId, status, rejectReason = '', checkerName = '') {
                    // Show loading indicator
                    const btn = document.querySelector(`button[data-id="${requestId}"]`) || document.querySelector(`[data-id="${requestId}"]`);
                    if (btn) {
                        const originalHtml = btn.innerHTML;
                        btn.innerHTML = '<i class="bx bx-loader-alt animate-spin"></i> Processing...';
                        btn.disabled = true;
                    }

                    // Build request data
                    let formData = new FormData();
                    formData.append('action', 'updateStatus');
                    formData.append('request_id', requestId);
                    formData.append('status', status);
                    if (rejectReason) {
                        formData.append('reject_reason', rejectReason);
                    }
                    if (checkerName) {
                        formData.append('checker_name', checkerName);
                    }

                    // Send AJAX request
                    fetch('adm-servicerequest.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: `Status has been updated to ${status}`,
                                icon: 'success',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload the page to reflect the changes
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update status. Please try again.',
                                icon: 'error'
                            });
                            // Restore button state
                            if (btn) {
                                btn.innerHTML = originalHtml;
                                btn.disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred. Please try again.',
                            icon: 'error'
                        });
                        // Restore button state
                        if (btn) {
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                        }
                    });
                }

                // Search functionality
                const searchInput = document.getElementById('search-input');
                searchInput.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter') {
                        document.getElementById('search-filter').value = this.value;
                        document.getElementById('filter-form').submit();
                    }
                });

                // Pagination functionality
                const paginationBtns = document.querySelectorAll('[data-page]');
                paginationBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (this.hasAttribute('disabled')) return;
                        const page = this.getAttribute('data-page');
                        const url = new URL(window.location);
                        // Get current URL and parameters
                        url.searchParams.set('page', page);
                        // Update the page parameter
                        window.location.href = url.toString();
                        // Navigate to the new URL
                    });
                });
            });
        </script>
      </main>
</body>
</html>