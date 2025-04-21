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
        wp.*
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    JOIN 
        workpermit wp ON sr.service_id = wp.id
    WHERE 
        sr.id = $request_id AND sr.service_type = 'WorkPermit'
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

// Parse work types - split by comma if multiple types exist
$workTypes = [];
if (!empty($request['work_type'])) {
    $workTypes = explode(',', $request['work_type']);
    // Trim whitespace from each type
    $workTypes = array_map('trim', $workTypes);
}

// Calculate duration between period_from and period_to
function calculateDuration($startDate, $endDate) {
    if (empty($startDate) || empty($endDate)) return 'N/A';
    
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end);
    
    $days = $interval->days;
    return $days + 1 . ' day' . ($days > 0 ? 's' : '');
}

// Parse personnel details if JSON
$personnelDetails = [];
if (!empty($request['personnel_details'])) {
    $personnelDetails = json_decode($request['personnel_details'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If not valid JSON, treat as regular text
        $personnelDetails = null;
    }
}

// Get status class for styling
$statusClass = 'orange';
$status = strtolower($request['status']);
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
        Work Permit Request <span class="text-gray-500 font-normal">#<?php echo $request['request_id']; ?></span>
    </h2>
    <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none close-modal">
        <i class='bx bx-x'></i>
    </button>
</div>
<div class="p-5">
    <!-- Status Badge -->
    <div class="flex items-center gap-2 p-3 rounded bg-<?php echo $statusClass; ?>-100 text-<?php echo $statusClass; ?>-800 mb-6">
        <i class='bx bx-info-circle'></i>
        <span>Status: <?php echo ucfirst($request['status']); ?></span>
    </div>

    <!-- Rejection reason if any -->
    <?php if (!empty($request['reject_reason'])): ?>
        <div class="bg-red-50 p-3 rounded-md mb-6">
            <h4 class="font-medium text-red-800 mb-1">Reason for Rejection:</h4>
            <p class="text-red-700"><?php echo $request['reject_reason']; ?></p>
        </div>
    <?php endif; ?>

    <!-- Resident & Work Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Resident Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-user-hard-hat mr-2 text-blue-primary"></i>Requester Information
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Owner Name:</label>
                    <p class="text-gray-800 font-medium"><?php echo $request['owner_name']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Resident Code:</label>
                    <p class="text-gray-800"><?php echo $request['Resident_Code']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">User Type:</label>
                    <p class="text-gray-800"><?php echo $request['user_type']; ?></p>
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
                    <p class="text-gray-800"><?php echo $request['user_email'] ? $request['user_email'] : $request['Email']; ?></p>
                </div>
                <?php if (!empty($request['authorize_rep'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Authorized Representative:</label>
                    <p class="text-gray-800"><?php echo $request['authorize_rep']; ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Work Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-tools mr-2 text-blue-primary"></i>Work Information
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Work Type:</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($workTypes as $type): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo $type; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Contractor/Company:</label>
                    <p class="text-gray-800"><?php echo $request['contractor']; ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Start Date:</label>
                        <p class="text-gray-800"><?php echo formatDate($request['period_from']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">End Date:</label>
                        <p class="text-gray-800"><?php echo formatDate($request['period_to']); ?></p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Work Duration:</label>
                    <p class="text-gray-800">
                        <?php echo calculateDuration($request['period_from'], $request['period_to']); ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Submitted On:</label>
                    <p class="text-gray-800"><?php echo formatDate($request['submitted_at']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Details -->
    <?php if (!empty($request['task_details'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-clipboard-list mr-2 text-blue-primary"></i>Task Details
        </h3>
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-700 whitespace-pre-line"><?php echo $request['task_details']; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personnel Details -->
    <?php if (!empty($personnelDetails) && is_array($personnelDetails)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-hard-hat mr-2 text-blue-primary"></i>Personnel Details
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                        <th class="py-2 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($personnelDetails as $index => $person): ?>
                    <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
                        <td class="py-2 px-4 border-b border-gray-200 text-sm"><?php echo $index + 1; ?></td>
                        <td class="py-2 px-4 border-b border-gray-200 text-sm"><?php echo isset($person['name']) ? $person['name'] : 'N/A'; ?></td>
                        <td class="py-2 px-4 border-b border-gray-200 text-sm"><?php echo isset($person['position']) ? $person['position'] : 'N/A'; ?></td>
                        <td class="py-2 px-4 border-b border-gray-200 text-sm"><?php echo isset($person['id_number']) ? $person['id_number'] : 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php elseif (!empty($request['personnel_details'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-hard-hat mr-2 text-blue-primary"></i>Personnel Details
        </h3>
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-700 whitespace-pre-line"><?php echo $request['personnel_details']; ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Signature -->
    <?php if (!empty($request['signature'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">
            <i class="fas fa-signature mr-2 text-blue-primary"></i>Owner's Signature
        </h3>
        <div class="border border-gray-200 rounded-lg overflow-hidden inline-block">
            <?php 
            // Check if the signature is a file path reference
            $signaturePath = $request['signature'];
            // If it looks like a Base64 string that's actually a path reference, handle it
            if (strpos($signaturePath, '/') === false && strpos($signaturePath, '\\') === false) {
                // Try to see if it's a Base64 encoded path
                $decoded = base64_decode($signaturePath, true);
                if ($decoded !== false && strpos($decoded, 'Signature/') === 0) {
                    $signaturePath = $decoded;
                }
            }
            ?>
            <div class="bg-white p-4">
                <img src="<?php echo $signaturePath; ?>" alt="Owner Signature" class="h-24 object-contain">
            </div>
            <div class="p-3 bg-gray-50 border-t border-gray-200">
                <button class="text-blue-primary w-full view-image-btn" data-src="<?php echo $signaturePath; ?>">
                    <i class="fas fa-search-plus mr-1"></i> View Full Image
                </button>
            </div>
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
                    <p class="text-sm text-gray-600">Submitted by <?php echo $request['owner_name']; ?></p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['submitted_at']); ?></p>
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
                imageAlt: 'Document Image',
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
        data.append('service_type', 'WorkPermit');
        
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
