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
        mi.*
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    JOIN 
        ownertenantmovein mi ON sr.service_id = mi.id
    WHERE 
        sr.id = $request_id AND sr.service_type = 'MoveIn'
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Request not found";
    exit;
}

$request = mysqli_fetch_assoc($result);

// Parse lease_contract field to get the file paths
$leaseContractFiles = [];
if (!empty($request['lease_contract'])) {
    // Check if the field contains multiple files separated by commas
    if (strpos($request['lease_contract'], ',') !== false) {
        $leaseContractFiles = array_map('trim', explode(',', $request['lease_contract']));
    } else {
        // Single file
        $leaseContractFiles[] = trim($request['lease_contract']);
    }
}

// Format dates for display
function formatDate($dateStr) {
    if (empty($dateStr)) return 'N/A';
    $date = new DateTime($dateStr);
    return $date->format('F j, Y'); // E.g., "January 1, 2023"
}

// Function to get file extension
function getFileExtension($filename) {
    return pathinfo($filename, PATHINFO_EXTENSION);
}

// Function to check if file is an image
function isImage($extension) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    return in_array(strtolower($extension), $imageExtensions);
}

// Function to check if file is a document
function isDocument($extension) {
    $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
    return in_array(strtolower($extension), $docExtensions);
}

// Get status class for styling
$statusClass = 'orange';
$status = strtolower($request['Status']);
if ($status == 'pending') {
    $statusClass = 'green'; // Change to green for Pending status
} else if ($status == 'approval' || $status == 'approved') {
    $statusClass = 'green';
} else if ($status == 'completed' || $status == 'complete') {
    $statusClass = 'blue';
} else if ($status == 'rejected' || $status == 'reject') {
    $statusClass = 'red';
}

// Display status - convert "Pending" to "Approved" for display only
$displayStatus = $request['Status']; // Preserve original capitalization
if (strtolower($displayStatus) == 'pending') {
    $displayStatus = 'Approved';
}
?>

<div class="flex justify-between items-center p-4 border-b border-gray-200">
    <h2 class="text-xl font-medium flex items-center gap-2">
        Move In Request <span class="text-gray-500 font-normal">#<?php echo $request['request_id']; ?></span>
    </h2>
    <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none close-modal">
        <i class='bx bx-x'></i>
    </button>
</div>
<div class="p-5">
    <!-- Status Badge -->
    <div class="flex items-center gap-2 p-3 rounded bg-<?php echo $statusClass; ?>-100 text-<?php echo $statusClass; ?>-800 mb-6">
        <i class='bx bx-info-circle'></i>
        <span>Status: <?php echo $displayStatus; ?></span>
    </div>

    <!-- Rejection reason if any -->
    <?php if (!empty($request['reject_reason'])): ?>
        <div class="bg-red-50 p-3 rounded-md mb-6">
            <h4 class="font-medium text-red-800 mb-1">Reason for Rejection:</h4>
            <p class="text-red-700"><?php echo $request['reject_reason']; ?></p>
        </div>
    <?php endif; ?>

    <!-- Resident Information -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Resident Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Resident Name:</label>
                <p class="text-gray-800"><?php echo $request['Resident_Name']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Resident Code:</label>
                <p class="text-gray-800"><?php echo $request['Resident_Code']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Contact Number:</label>
                <p class="text-gray-800"><?php echo $request['Resident_Contact']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Unit/Location:</label>
                <p class="text-gray-800">Tower <?php echo $request['Tower']; ?>, Unit <?php echo $request['Unit_Number']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Move In Date:</label>
                <p class="text-gray-800"><?php echo formatDate($request['MoveInDate']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Submitted On:</label>
                <p class="text-gray-800"><?php echo formatDate($request['Created_At']); ?></p>
            </div>
        </div>
    </div>

    <!-- Lease Details -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Lease Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Parking Slot Number:</label>
                <p class="text-gray-800"><?php echo !empty($request['parkingSlotNumber']) ? $request['parkingSlotNumber'] : 'None'; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Lease Expiry Date:</label>
                <p class="text-gray-800"><?php echo formatDate($request['leaseExpiryDate']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Representative Name:</label>
                <p class="text-gray-800"><?php echo !empty($request['representativeName']) ? $request['representativeName'] : 'N/A'; ?></p>
            </div>
        </div>
    </div>

    <!-- Signature -->
    <?php if (!empty($request['Signature'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Signature</h3>
        <div class="w-64 h-32 border border-gray-200 rounded overflow-hidden">
            <img src="<?php echo $request['Signature']; ?>" alt="Resident Signature" class="w-full h-full object-contain">
        </div>
    </div>
    <?php endif; ?>

    <!-- Lease Contract Documents -->
    <?php if (!empty($leaseContractFiles)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Lease Contract Documents</h3>
        <div class="space-y-2">
            <?php foreach ($leaseContractFiles as $file): ?>
                <?php 
                $extension = getFileExtension($file);
                $isImg = isImage($extension);
                $isDoc = isDocument($extension);
                $fileName = basename($file);
                ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded">
                    <?php if ($isImg): ?>
                        <i class="fas fa-file-image text-blue-primary"></i>
                    <?php elseif ($isDoc): ?>
                        <i class="fas fa-file-pdf text-red-primary"></i>
                    <?php else: ?>
                        <i class="fas fa-file text-gray-500"></i>
                    <?php endif; ?>
                    
                    <span class="text-gray-700"><?php echo $fileName; ?></span>
                    
                    <?php if ($isImg): ?>
                        <a href="#" class="text-blue-primary ml-auto view-image" data-src="<?php echo $file; ?>">View</a>
                    <?php else: ?>
                        <a href="<?php echo $file; ?>" class="text-blue-primary ml-auto" target="_blank">Download</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Simplified Activity Log - only showing submission -->
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
                        <?php if ($status == 'approval' || $status == 'approved' || $status == 'pending'): ?>
                            <i class='bx bx-check'></i>
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
                        if ($status == 'approval' || $status == 'approved' || $status == 'pending') {
                            echo 'Request Approved';
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
                <button id="modal-complete-btn" class="px-4 py-2 bg-blue-primary text-white rounded flex items-center gap-1 hover:bg-blue-dark transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-check-double'></i> Mark as Complete
                </button>
                <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-x'></i> Reject Request
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
    document.querySelectorAll('.view-image').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
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

    // Add the missing updateStatus function
    function updateStatus(requestId, newStatus, rejectReason = '') {
        // Show loading indicator
        Swal.fire({
            title: 'Processing...',
            text: 'Updating request status',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Prepare the data
        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('status', newStatus);
        if (rejectReason) {
            formData.append('reject_reason', rejectReason);
        }

        // Send the AJAX request
        fetch('update-request-status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Status updated successfully',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    // Close the modal and refresh the page to show updated data
                    document.getElementById('request-modal').classList.add('hidden');
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to update status',
                    confirmButtonColor: '#3085d6'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An unexpected error occurred',
                confirmButtonColor: '#3085d6'
            });
        });
    }
</script>
