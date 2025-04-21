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
        p.*
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    JOIN 
        pets p ON sr.service_id = p.id
    WHERE 
        sr.id = $request_id AND sr.service_type = 'PetRegistration'
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

// Function to calculate age from date of birth
function calculateAge($dob) {
    if (empty($dob)) return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $birthDate->diff($today);
    
    if ($age->y > 0) {
        return $age->y . ' year' . ($age->y > 1 ? 's' : '') . ' old';
    } else if ($age->m > 0) {
        return $age->m . ' month' . ($age->m > 1 ? 's' : '') . ' old';
    } else {
        return $age->d . ' day' . ($age->d > 1 ? 's' : '') . ' old';
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
        Pet Registration <span class="text-gray-500 font-normal">#<?php echo $request['request_id']; ?></span>
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

    <!-- Display Pet and Owner Information Side by Side -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Pet Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-paw mr-2 text-blue-primary"></i>Pet Information
            </h3>
            
            <?php if (!empty($request['pet_pic'])): ?>
            <div class="flex justify-center mb-4">
                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-white shadow-md">
                    <img src="<?php echo $request['pet_pic']; ?>" alt="Pet Photo" class="w-full h-full object-cover">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Pet Name:</label>
                    <p class="text-gray-800 font-medium"><?php echo $request['pet_name']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Breed:</label>
                    <p class="text-gray-800"><?php echo $request['breed']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Date of Birth:</label>
                    <p class="text-gray-800"><?php echo formatDate($request['dob']); ?> (<?php echo calculateAge($request['dob']); ?>)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Vaccination Status:</label>
                    <p class="text-gray-800">
                        <?php if ($request['vaccinated'] == 'Yes'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i> Vaccinated
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i> Not Vaccinated
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if (!empty($request['vaccine_duration'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Vaccine Duration:</label>
                    <p class="text-gray-800"><?php echo $request['vaccine_duration']; ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Owner Information -->
        <div class="bg-gray-50 p-5 rounded-lg">
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-200 pb-2 mb-4">
                <i class="fas fa-user mr-2 text-blue-primary"></i>Owner Information
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
                    <p class="text-gray-800"><?php echo $request['contact']; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Email:</label>
                    <p class="text-gray-800"><?php echo $request['email']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <?php if (!empty($request['remarks'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Remarks</h3>
        <div class="p-4 bg-blue-50 rounded-lg">
            <p class="text-gray-700"><?php echo nl2br($request['remarks']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Vaccination Card & Signature -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <?php if (!empty($request['vaccine_card'])): ?>
        <div>
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Vaccination Card</h3>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <img src="<?php echo $request['vaccine_card']; ?>" alt="Vaccination Card" class="w-full object-contain h-48">
                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    <button class="text-blue-primary w-full view-image-btn" data-src="<?php echo $request['vaccine_card']; ?>">
                        <i class="fas fa-search-plus mr-1"></i> View Full Image
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($request['user_signature'])): ?>
        <div>
            <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Owner's Signature</h3>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-white p-4 flex justify-center">
                    <img src="<?php echo $request['user_signature']; ?>" alt="Owner Signature" class="h-24 object-contain">
                </div>
                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    <button class="text-blue-primary w-full view-image-btn" data-src="<?php echo $request['user_signature']; ?>">
                        <i class="fas fa-search-plus mr-1"></i> View Full Image
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
                    <p class="font-medium text-gray-800">Registration Submitted</p>
                    <p class="text-sm text-gray-600">Submitted by <?php echo $request['owner_name']; ?></p>
                    <p class="text-sm text-gray-500"><?php echo formatDate($request['created_at']); ?></p>
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
                            echo 'Registration Approved';
                        } elseif ($status == 'pending') {
                            echo 'Registration Pending';
                        } elseif ($status == 'completed' || $status == 'complete') {
                            echo 'Registration Completed';
                        } elseif ($status == 'rejected' || $status == 'reject') {
                            echo 'Registration Rejected';
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
                    <i class='bx bx-check'></i> Approve Registration
                </button>
                <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-x'></i> Reject Registration
                </button>
            <?php elseif ($status == 'approval' || $status == 'approved'): ?>
                <button id="modal-complete-btn" class="px-4 py-2 bg-blue-primary text-white rounded flex items-center gap-1 hover:bg-blue-dark transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-check-double'></i> Mark as Complete
                </button>
                <button id="modal-reject-btn" class="px-4 py-2 bg-red-primary text-white rounded flex items-center gap-1 hover:bg-red-600 transition-colors" data-id="<?php echo $request['request_id']; ?>">
                    <i class='bx bx-x'></i> Reject Registration
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

    // Pet photo viewer functionality
    <?php if (!empty($request['pet_pic'])): ?>
    document.querySelector('img[alt="Pet Photo"]').addEventListener('click', function() {
        Swal.fire({
            imageUrl: this.src,
            imageAlt: 'Pet Photo',
            width: 'auto',
            showCloseButton: true,
            showConfirmButton: false
        });
    });
    <?php endif; ?>

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
                title: 'Reject Registration',
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
        data.append('service_type', 'PetRegistration');
        
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
