<?php
// Check if request_id is provided
if (!isset($_GET['request_id'])) {
    echo "Request ID is required";
    exit;
}

include 'dbconn.php';

$request_id = intval($_GET['request_id']);

// Get the service request data
$query = "
    SELECT 
        sr.id AS request_id,
        sr.service_id,
        sr.reject_reason,
        oi.First_Name,
        oi.Last_Name,
        oi.Tower,
        oi.Unit_Number,
        oi.Mobile_Number,
        oi.Email,
        gp.*
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    JOIN 
        gatepass gp ON sr.service_id = gp.Ticket_No
    WHERE 
        sr.id = $request_id AND sr.service_type = 'gatepass'
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Request not found";
    exit;
}

$request = mysqli_fetch_assoc($result);

// Format dates for display
function formatDate($dateStr) {
    if (empty($dateStr)) return 'N/A';
    $date = new DateTime($dateStr);
    return $date->format('F j, Y'); // E.g., "January 1, 2023"
}

// Format time for display
function formatTime($timeStr) {
    if (empty($timeStr)) return 'N/A';
    
    // Check if time is in HH:MM:SS format
    if (strpos($timeStr, ':') !== false) {
        $time = new DateTime($timeStr);
        return $time->format('h:i A'); // E.g., "3:30 PM"
    }
    
    // If it's already formatted or in a different format, return as is
    return $timeStr;
}

// Parse items JSON data
$items = [];
if (!empty($request['Items'])) {
    $items = json_decode($request['Items'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If not valid JSON, treat as regular text
        $items = [['description' => $request['Items']]];
    }
}

// Get status class for styling
$statusClass = 'orange';
$status = strtolower($request['Status']);
if ($status == 'pending') {
    $statusClass = 'orange';
} else if ($status == 'approval' || $status == 'approved') {
    $statusClass = 'green';
} else if ($status == 'completed' || $status == 'complete') {
    $statusClass = 'blue';
} else if ($status == 'rejected' || $status == 'reject') {
    $statusClass = 'red';
}
?>

<div class="flex justify-between items-center p-4 border-b border-gray-200">
    <h2 class="text-xl font-medium flex items-center gap-2">
        Gate Pass <span class="text-gray-500 font-normal">#<?php echo $request['Ticket_No']; ?></span>
    </h2>
    <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none close-modal">
        <i class='bx bx-x'></i>
    </button>
</div>
<div class="p-5">
    <!-- Status Badge -->
    <div class="flex items-center gap-2 p-3 rounded bg-<?php echo $statusClass; ?>-100 text-<?php echo $statusClass; ?>-800 mb-6">
        <i class='bx bx-info-circle'></i>
        <span>Status: <?php echo ucfirst($request['Status']); ?></span>
    </div>

    <!-- Rejection reason if any -->
    <?php if (!empty($request['reject_reason'])): ?>
        <div class="bg-red-50 p-3 rounded-md mb-6">
            <h4 class="font-medium text-red-800 mb-1">Reason for Rejection:</h4>
            <p class="text-red-700"><?php echo $request['reject_reason']; ?></p>
        </div>
    <?php endif; ?>

    <!-- Resident & Gate Pass Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Resident Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-user mr-2 text-blue-primary"></i>Resident Information
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Name:</label>
                    <p class="text-gray-800 font-medium"><?php echo $request['First_Name'] . ' ' . $request['Last_Name']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Resident Code:</label>
                    <p class="text-gray-800"><?php echo $request['Resident_Code']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">User Type:</label>
                    <p class="text-gray-800"><?php echo $request['User_Type']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Unit Number:</label>
                    <p class="text-gray-800">Tower <?php echo $request['Tower']; ?>, Unit <?php echo $request['Unit_Number']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Contact Number:</label>
                    <p class="text-gray-800"><?php echo $request['Mobile_Number']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Email:</label>
                    <p class="text-gray-800"><?php echo $request['Email']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Gate Pass Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-id-card-alt mr-2 text-blue-primary"></i>Gate Pass Details
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Pass Date:</label>
                    <p class="text-gray-800"><?php echo formatDate($request['Date']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Pass Time:</label>
                    <p class="text-gray-800"><?php echo formatTime($request['Time']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Bearer:</label>
                    <p class="text-gray-800"><?php echo $request['Bearer']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Authorization:</label>
                    <p class="text-gray-800"><?php echo $request['Authorization']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Submitted On:</label>
                    <p class="text-gray-800"><?php echo formatDate($request['Created_At']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Items List -->
    <?php if (!empty($items)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-box-open mr-2 text-blue-primary"></i>Items
        </h3>
        
        <?php foreach($items as $index => $item): ?>
            <div class="p-4 bg-gray-50 rounded-lg mb-4">
                <h4 class="font-medium text-gray-700 mb-3 border-b border-gray-200 pb-2">Item #<?php echo isset($item['item_no']) ? $item['item_no'] : ($index + 1); ?></h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <?php if (isset($item['description'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Description:</label>
                        <p class="text-gray-800"><?php echo $item['description']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($item['quantity'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Quantity:</label>
                        <p class="text-gray-800"><?php echo $item['quantity']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($item['unit'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Unit:</label>
                        <p class="text-gray-800"><?php echo $item['unit']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($item['item_pics']) && is_array($item['item_pics']) && !empty($item['item_pics'])): ?>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-2">Item Images:</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach($item['item_pics'] as $pic): ?>
                            <?php if(!empty($pic)): ?>
                            <div class="w-20 h-20 border border-gray-200 rounded-lg overflow-hidden">
                                <img src="GateItem/<?php echo $pic; ?>" alt="Item Photo" class="w-full h-full object-cover cursor-pointer view-image-btn" data-src="GateItem/<?php echo $pic; ?>">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Completion Details -->
    <?php if (!empty($request['checked_by']) || !empty($request['completed_at'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-clipboard-check mr-2 text-blue-primary"></i>Completion Details
        </h3>
        <div class="p-4 bg-gray-50 rounded-lg">
            <?php if (!empty($request['checked_by'])): ?>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-600 mb-1">Checked By:</label>
                <p class="text-gray-800"><?php echo $request['checked_by']; ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($request['completed_at'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Completed At:</label>
                <p class="text-gray-800"><?php echo formatDate($request['completed_at']) . ' ' . formatTime($request['completed_at']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Request Timeline -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Request Timeline</h3>
        <div class="space-y-4">
            <!-- Submission Log Entry -->
            <div class="flex">
                <div class="relative">
                    <div class="w-8 h-8 bg-blue-primary rounded-full flex justify-center items-center text-white z-10">
                        <i class='bx bx-plus-circle'></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="font-medium text-gray-800">Request Submitted</p>
                    <p class="text-sm text-gray-600">Submitted by <?php echo $request['First_Name'] . ' ' . $request['Last_Name']; ?></p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['Created_At']); ?></p>
                </div>
            </div>
            
            <!-- Status indicator based on current status -->
            <?php if ($status != ''): ?>
            <div class="flex">
                <div class="relative">
                    <div class="w-8 h-8 bg-<?php echo $statusClass; ?>-primary rounded-full flex justify-center items-center text-white z-10">
                        <?php if ($status == 'approval' || $status == 'approved'): ?>
                            <i class='bx bx-check'></i>
                        <?php elseif ($status == 'pending'): ?>
                            <i class='bx bx-time'></i>
                        <?php elseif ($status == 'completed' || $status == 'complete'): ?>
                            <i class='bx bx-check-double'></i>
                        <?php elseif ($status == 'rejected' || $status == 'reject'): ?>
                            <i class='bx bx-x'></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="font-medium text-gray-800">
                        <?php 
                        if ($status == 'approval' || $status == 'approved') {
                            echo 'Request Approved';
                        } elseif ($status == 'pending') {
                            echo 'Request Pending';
                        } elseif ($status == 'completed' || $status == 'complete') {
                            echo 'Request Completed';
                        } elseif ($status == 'rejected' || $status == 'reject') {
                            echo 'Request Rejected';
                        }
                        ?>
                    </p>
                    <p class="text-sm text-gray-500">Status updated</p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Completion entry if completed -->
            <?php if (!empty($request['completed_at']) && ($status == 'completed' || $status == 'complete')): ?>
            <div class="flex">
                <div class="relative">
                    <div class="w-8 h-8 bg-blue-primary rounded-full flex justify-center items-center text-white z-10">
                        <i class='bx bx-check-double'></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="font-medium text-gray-800">Gate Pass Processed</p>
                    <p class="text-sm text-gray-600">Checked by <?php echo $request['checked_by']; ?></p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['completed_at']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="flex justify-between items-center p-4 border-t border-gray-200">
    <div class="flex gap-2">
        <?php if ($status != 'completed' && $status != 'rejected'): ?>
            <?php if ($status == ''): ?>
                <button id="modal-approve-btn" class="px-4 py-2 bg-green-primary text-white rounded flex items-center gap-1 hover:bg-green-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-check'></i> Approve Request
                </button>
                <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-x'></i> Reject Request
                </button>
            <?php elseif ($status == 'approval' || $status == 'approved'): ?>
                <button id="modal-accept-btn" class="px-4 py-2 bg-orange-primary text-white rounded flex items-center gap-1 hover:bg-orange-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-check-circle'></i> Accept Request
                </button>
                <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-x'></i> Reject Request
                </button>
            <?php elseif ($status == 'pending'): ?>
                <button id="modal-complete-btn" class="px-4 py-2 bg-blue-primary text-white rounded flex items-center gap-1 hover:bg-blue-dark transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-check-double'></i> Mark as Complete
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <button class="px-4 py-2 bg-gray-light text-gray-700 rounded hover:bg-gray-border transition-colors close-modal">
        Close
    </button>
</div>

<script>
    // Initialize action buttons
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('request-modal').classList.add('hidden');
        });
    });

    // Image viewer functionality
    document.querySelectorAll('.view-image-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const imgSrc = this.getAttribute('data-src');
            
            Swal.fire({
                imageUrl: imgSrc,
                imageAlt: 'Item Image',
                width: 'auto',
                showCloseButton: true,
                showConfirmButton: false
            });
        });
    });

    // Action buttons functionality
    if (document.getElementById('modal-approve-btn')) {
        document.getElementById('modal-approve-btn').addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            updateStatus(requestId, 'Approval');
        });
    }

    if (document.getElementById('modal-accept-btn')) {
        document.getElementById('modal-accept-btn').addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            updateStatus(requestId, 'Pending');
        });
    }

    if (document.getElementById('modal-reject-btn')) {
        document.getElementById('modal-reject-btn').addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            
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
    }

    if (document.getElementById('modal-complete-btn')) {
        document.getElementById('modal-complete-btn').addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            updateStatus(requestId, 'Completed');
        });
    }
    
    // Add the updateStatus function to handle AJAX requests
    function updateStatus(requestId, status, rejectReason = '') {
        // Show loading indicator
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we update the request.',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('request_id', requestId);
        data.append('status', status);
        data.append('service_type', 'gatepass');
        
        if (rejectReason) {
            data.append('reject_reason', rejectReason);
        }
        
        // Send AJAX request to update status
        fetch('update-request-status.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message || 'Request status has been updated.',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    // Close modal and refresh the page to show updated status
                    document.getElementById('request-modal').classList.add('hidden');
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message || 'Failed to update request status.',
                    confirmButtonColor: '#3085d6'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#3085d6'
            });
        });
    }
</script>
