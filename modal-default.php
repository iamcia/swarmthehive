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
        sr.service_type,
        sr.reject_reason,
        oi.First_Name,
        oi.Last_Name,
        oi.Tower,
        oi.Unit_Number,
        oi.Mobile_Number,
        oi.Email
    FROM 
        servicerequests sr
    JOIN 
        ownerinformation oi ON sr.user_id = oi.ID
    WHERE 
        sr.id = $request_id
";

$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Request not found";
    exit;
}

$request = mysqli_fetch_assoc($result);

// Function to format dates
function formatDate($dateStr) {
    if (empty($dateStr)) return 'N/A';
    $date = new DateTime($dateStr);
    return $date->format('F j, Y'); // E.g., "January 1, 2023"
}

?>

<div class="flex justify-between items-center p-4 border-b border-gray-200">
    <h2 class="text-xl font-medium flex items-center gap-2">
        <?php echo $request['service_type']; ?> Request <span class="text-gray-500 font-normal">#<?php echo $request['request_id']; ?></span>
    </h2>
    <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none close-modal">
        <i class='bx bx-x'></i>
    </button>
</div>
<div class="p-5">
    <div class="flex items-center gap-2 p-3 rounded bg-blue-100 text-blue-800 mb-6">
        <i class='bx bx-info-circle'></i>
        <span>This service type doesn't have a specialized view yet. Basic information is shown below.</span>
    </div>
    
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Request Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Service Type:</label>
                <p class="text-gray-800"><?php echo $request['service_type']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Request ID:</label>
                <p class="text-gray-800">#<?php echo $request['request_id']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Service ID:</label>
                <p class="text-gray-800"><?php echo $request['service_id']; ?></p>
            </div>
        </div>
    </div>
    
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Resident Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Resident Name:</label>
                <p class="text-gray-800"><?php echo $request['First_Name'] . ' ' . $request['Last_Name']; ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Unit/Location:</label>
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

    <?php if (!empty($request['reject_reason'])): ?>
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-800 border-b border-gray-100 pb-2 mb-4">Rejection Reason</h3>
        <p class="text-gray-700 p-3 bg-red-50 rounded"><?php echo $request['reject_reason']; ?></p>
    </div>
    <?php endif; ?>
</div>

<div class="flex justify-between items-center p-4 border-t border-gray-200">
    <div class="flex gap-2">
        <!-- Action buttons would go here -->
    </div>
    <button class="px-4 py-2 bg-gray-light text-gray-700 rounded hover:bg-gray-border transition-colors close-modal">
        Close
    </button>
</div>

<script>
    // Initialize close buttons
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('request-modal').classList.add('hidden');
        });
    });
</script>
