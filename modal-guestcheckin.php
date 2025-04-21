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
        gc.*
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    JOIN 
        guestcheckinout gc ON sr.service_id = gc.id
    WHERE 
        sr.id = $request_id AND sr.service_type = 'GuestCheckIn'
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Request not found";
    exit;
}

$request = mysqli_fetch_assoc($result);

// Helper function to convert status for display
function getDisplayStatus($status) {
    // Convert "Pending" to "Approved" for display only
    if (strtolower($status) == 'pending') {
        return 'Approved';
    }
    return $status; // Return original status for all other values
}

// Helper function to get status color class
function getStatusClass($status) {
    $statusLower = strtolower($status);
    if ($statusLower == 'pending') {
        return 'green'; // Pending shows as green (like Approved)
    } else if ($statusLower == 'approval' || $statusLower == 'approved') {
        return 'green';
    } else if ($statusLower == 'completed' || $statusLower == 'complete') {
        return 'blue';
    } else if ($statusLower == 'rejected' || $statusLower == 'reject') {
        return 'red';
    }
    return 'orange'; // Default color
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
$statusClass = getStatusClass($request['Status']);
$status = strtolower($request['Status']);

// Display status - convert "Pending" to "Approved" for display only
$displayStatus = getDisplayStatus($request['Status']);

// Parse guest info JSON
$guestInfo = [];
if (!empty($request['Guest_Info'])) {
    $guestInfo = json_decode($request['Guest_Info'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If not valid JSON, try to handle it as a string
        $guestInfo = [
            ['info' => $request['Guest_Info']]
        ];
    }
}

// Calculate number of days between check-in and check-out
$daysOfStay = '';
if (!empty($request['Checkin_Date']) && !empty($request['Checkout_Date'])) {
    $checkinDate = new DateTime($request['Checkin_Date']);
    $checkoutDate = new DateTime($request['Checkout_Date']);
    $interval = $checkinDate->diff($checkoutDate);
    $daysOfStay = $interval->days;
}
?>

<div class="flex justify-between items-center p-4 border-b border-gray-200">
    <h2 class="text-xl font-medium flex items-center gap-2">
        Guest Check-In Request <span class="text-gray-500 font-normal">#<?php echo $request['request_id']; ?></span>
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
                <p class="text-gray-800"><?php echo $request['First_Name'] . ' ' . $request['Last_Name']; ?></p>
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
                <label class="block text-sm font-medium text-gray-600 mb-1">Unit/Location:</label>
                <p class="text-gray-800">Tower <?php echo $request['Tower']; ?>, Unit <?php echo $request['Unit_Number']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Unit Type:</label>
                <p class="text-gray-800"><?php echo $request['Unit_Type']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Contact Number:</label>
                <p class="text-gray-800"><?php echo $request['Mobile_Number']; ?></p>
            </div>
        </div>
    </div>

    <!-- Stay Details -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Stay Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Check-in Date:</label>
                <p class="text-gray-800"><?php echo formatDate($request['Checkin_Date']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Check-out Date:</label>
                <p class="text-gray-800"><?php echo formatDate($request['Checkout_Date']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Duration of Stay:</label>
                <p class="text-gray-800">
                    <?php 
                    if (!empty($request['Days_Of_Stay'])) {
                        echo $request['Days_Of_Stay'] . ' days';
                    } elseif ($daysOfStay !== '') {
                        echo $daysOfStay . ' days';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Submitted On:</label>
                <p class="text-gray-800"><?php echo formatDate($request['Created_At']); ?></p>
            </div>
        </div>
    </div>

    <!-- Guest Information -->
    <?php if (!empty($guestInfo)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Guest Information</h3>
        
        <?php foreach($guestInfo as $index => $guest): ?>
            <div class="p-4 bg-gray-50 rounded-lg mb-3">
                <h4 class="font-medium text-gray-700 mb-2">Guest <?php echo $index + 1; ?></h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php if (isset($guest['name'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name:</label>
                        <p class="text-gray-800"><?php echo $guest['name']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($guest['guest_no'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Guest Number:</label>
                        <p class="text-gray-800"><?php echo $guest['guest_no']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($guest['contact'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Contact:</label>
                        <p class="text-gray-800"><?php echo $guest['contact']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($guest['relationship'])): ?>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Relationship:</label>
                        <p class="text-gray-800"><?php echo $guest['relationship']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($guest['info'])): ?>
                    <div class="col-span-full">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Information:</label>
                        <p class="text-gray-800"><?php echo $guest['info']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Documents -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Uploaded Documents</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if (!empty($request['Valid_ID'])): ?>
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-id-card text-blue-primary"></i>
                    <h4 class="font-medium text-gray-700">Valid ID</h4>
                </div>
                <?php 
                $extension = getFileExtension($request['Valid_ID']);
                $isImg = isImage($extension);
                ?>
                <?php if ($isImg): ?>
                <div class="h-32 border border-gray-200 rounded bg-white p-2 mb-2">
                    <img src="<?php echo $request['Valid_ID']; ?>" alt="Valid ID" class="h-full object-contain mx-auto">
                </div>
                <div class="flex justify-center">
                    <button class="text-blue-primary view-image-btn" data-src="<?php echo $request['Valid_ID']; ?>">
                        View Full Image
                    </button>
                </div>
                <?php else: ?>
                <div class="flex items-center justify-center p-4">
                    <a href="<?php echo $request['Valid_ID']; ?>" target="_blank" class="text-blue-primary flex items-center gap-2">
                        <i class="fas fa-file-download"></i> Download Document
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($request['Vaccine_Card'])): ?>
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-syringe text-green-primary"></i>
                    <h4 class="font-medium text-gray-700">Vaccine Card</h4>
                </div>
                <?php 
                $extension = getFileExtension($request['Vaccine_Card']);
                $isImg = isImage($extension);
                ?>
                <?php if ($isImg): ?>
                <div class="h-32 border border-gray-200 rounded bg-white p-2 mb-2">
                    <img src="<?php echo $request['Vaccine_Card']; ?>" alt="Vaccine Card" class="h-full object-contain mx-auto">
                </div>
                <div class="flex justify-center">
                    <button class="text-blue-primary view-image-btn" data-src="<?php echo $request['Vaccine_Card']; ?>">
                        View Full Image
                    </button>
                </div>
                <?php else: ?>
                <div class="flex items-center justify-center p-4">
                    <a href="<?php echo $request['Vaccine_Card']; ?>" target="_blank" class="text-blue-primary flex items-center gap-2">
                        <i class="fas fa-file-download"></i> Download Document
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
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
    
    // Add the updateStatus function
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
        formData.append('service_type', 'GuestCheckIn'); // Specify this is a guest check-in request
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
